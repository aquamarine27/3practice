use axum::{
    extract::{Path, Query, State},
    Json,
};
use serde_json::Value;
use std::collections::HashMap;
use redis::AsyncCommands;
use crate::{
    config::AppState,
    domain::{Health, Trend, OsdrQuery},
    error::ApiError,
    clients::*,
    repo::*,
};
use chrono::{DateTime, Utc};
use sqlx::Row;
use validator::Validate; 

pub async fn health_check() -> Json<Health> {
    Json(Health { status: "ok", now: Utc::now() })
}

pub async fn last_iss(State(st): State<AppState>)
-> Result<Json<Value>, ApiError> {
    let row_opt = get_latest_iss(&st.pool).await?;

    if let Some(row) = row_opt {
        let id: i64 = row.get("id");
        let fetched_at: DateTime<Utc> = row.get("fetched_at");
        let source_url: String = row.get("source_url");
        let payload: Value = row.try_get("payload").unwrap_or(serde_json::json!({}));
        
        return Ok(Json(serde_json::json!({
            "id": id, "fetched_at": fetched_at, "source_url": source_url, "payload": payload
        })));
    }
    Ok(Json(serde_json::json!({"message":"no data"})))
}

// Rate-Limit 
pub async fn trigger_iss(State(st): State<AppState>)
-> Result<Json<Value>, ApiError> {
    let rl_key = "rate_limit:trigger_iss";
    let rl_duration_ms = 10_000; // 10 секунд 
    
    let mut conn = st.redis_client.get_multiplexed_async_connection().await?;
    
 
    let is_allowed: bool = redis::cmd("SET")
        .arg(rl_key)
        .arg(1)
        .arg("NX") 
        .arg("PX") 
        .arg(rl_duration_ms)
        .query_async(&mut conn).await
        .map(|r: Option<String>| r.is_some())
        .map_err(ApiError::Redis)?;
    
    if !is_allowed {
        return Err(ApiError::TooManyRequests( 
            format!("Rate limit exceeded. Try again in {} seconds.", rl_duration_ms / 1000)
        ));
    }

    fetch_and_store_iss(&st.pool, &st.fallback_url).await?;
    last_iss(State(st)).await
}


// Валидация данных в виде отдельного класса 
pub async fn osdr_list_validated(
    Query(params): Query<OsdrQuery>, 
    State(_st): State<AppState>
) -> Result<Json<Value>, ApiError> {
    
    params.validate()?; 

    let list_limit = params.limit;
    let search_query = params.query.unwrap_or_else(|| "none".to_string());


    
    Ok(Json(serde_json::json!({
        "status": "Validation successful",
        "message": "This endpoint demonstrates validation using OsdrQuery DTO. Try calling with ?limit=101 or ?query=ab to see the error.",
        "limit_applied": list_limit,
        "search_term": search_query,
    })))
}

pub async fn iss_trend(State(st): State<AppState>)
-> Result<Json<Trend>, ApiError> {
    let rows = sqlx::query("SELECT fetched_at, payload FROM iss_fetch_log ORDER BY id DESC LIMIT 2")
        .fetch_all(&st.pool).await?;

    if rows.len() < 2 {
        return Ok(Json(Trend {
            movement: false, delta_km: 0.0, dt_sec: 0.0, velocity_kmh: None,
            from_time: None, to_time: None,
            from_lat: None, from_lon: None, to_lat: None, to_lon: None
        }));
    }

    let t2: DateTime<Utc> = rows[0].get("fetched_at");
    let t1: DateTime<Utc> = rows[1].get("fetched_at");
    let p2: Value = rows[0].get("payload");
    let p1: Value = rows[1].get("payload");

    let lat1 = num(&p1["latitude"]);
    let lon1 = num(&p1["longitude"]);
    let lat2 = num(&p2["latitude"]);
    let lon2 = num(&p2["longitude"]);
    let v2 = num(&p2["velocity"]);

    let mut delta_km = 0.0;
    let mut movement = false;
    if let (Some(a1), Some(o1), Some(a2), Some(o2)) = (lat1, lon1, lat2, lon2) {
        delta_km = haversine_km(a1, o1, a2, o2);
        movement = delta_km > 0.1;
    }
    let dt_sec = (t2 - t1).num_milliseconds() as f64 / 1000.0;

    Ok(Json(Trend {
        movement,
        delta_km,
        dt_sec,
        velocity_kmh: v2,
        from_time: Some(t1),
        to_time: Some(t2),
        from_lat: lat1, from_lon: lon1, to_lat: lat2, to_lon: lon2,
    }))
}

pub async fn osdr_sync(State(st): State<AppState>)
-> Result<Json<Value>, ApiError> {
    let written = fetch_and_store_osdr(&st).await?;
    Ok(Json(serde_json::json!({ "written": written })))
}

pub async fn osdr_list(State(st): State<AppState>)
-> Result<Json<Value>, ApiError> {
    let limit = std::env::var("OSDR_LIST_LIMIT").ok()
        .and_then(|s| s.parse::<i64>().ok()).unwrap_or(20);

    let rows = sqlx::query(
        "SELECT id, dataset_id, title, status, updated_at, inserted_at, raw
         FROM osdr_items
         ORDER BY inserted_at DESC
         LIMIT $1"
    ).bind(limit).fetch_all(&st.pool).await?;

    let out: Vec<Value> = rows.into_iter().map(|r| {
        serde_json::json!({
            "id": r.get::<i64,_>("id"),
            "dataset_id": r.get::<Option<String>,_>("dataset_id"),
            "title": r.get::<Option<String>,_>("title"),
            "status": r.get::<Option<String>,_>("status"),
            "updated_at": r.get::<Option<DateTime<Utc>>,_>("updated_at"),
            "inserted_at": r.get::<DateTime<Utc>, _>("inserted_at"),
            "raw": r.get::<Value,_>("raw"),
        })
    }).collect();

    Ok(Json(serde_json::json!({ "items": out })))
}

pub async fn space_latest(Path(src): Path<String>, State(st): State<AppState>)
-> Result<Json<Value>, ApiError> {
    let row_json = latest_from_cache(&st.pool, &src).await;
    
    if row_json["at"].is_null() {
        return Ok(Json(serde_json::json!({ "source": src, "message":"no data" })))
    }
    
    Ok(Json(serde_json::json!({ "source": src, "data": row_json })))
}

pub async fn space_refresh(Query(q): Query<HashMap<String,String>>, State(st): State<AppState>)
-> Result<Json<Value>, ApiError> {
    let list = q.get("src").cloned().unwrap_or_else(|| "apod,neo,flr,cme,spacex".to_string());
    let mut done = Vec::new();
    for s in list.split(',').map(|x| x.trim().to_lowercase()) {
        match s.as_str() {
            "apod"   => { let _ = fetch_apod(&st).await;   done.push("apod"); }
            "neo"    => { let _ = fetch_neo_feed(&st).await;   done.push("neo"); }
            "flr"    => { let _ = fetch_donki_flr(&st).await;  done.push("flr"); }
            "cme"    => { let _ = fetch_donki_cme(&st).await;  done.push("cme"); }
            "spacex" => { let _ = fetch_spacex_next(&st).await; done.push("spacex"); }
            _ => {}
        }
    }
    Ok(Json(serde_json::json!({ "refreshed": done })))
}

pub async fn space_summary(State(st): State<AppState>)
-> Result<Json<Value>, ApiError> {
    let apod   = latest_from_cache(&st.pool, "apod").await;
    let neo    = latest_from_cache(&st.pool, "neo").await;
    let flr    = latest_from_cache(&st.pool, "flr").await;
    let cme    = latest_from_cache(&st.pool, "cme").await;
    let spacex = latest_from_cache(&st.pool, "spacex").await;

    let iss_last_row = get_latest_iss(&st.pool).await?;
    let iss_last = if let Some(r) = iss_last_row {
        serde_json::json!({"at": r.get::<DateTime<Utc>,_>("fetched_at"), "payload": r.get::<Value,_>("payload")})
    } else {
        serde_json::json!({})
    };

    let osdr_count = get_osdr_count(&st.pool).await;

    Ok(Json(serde_json::json!({
        "apod": apod, "neo": neo, "flr": flr, "cme": cme, "spacex": spacex,
        "iss": iss_last, "osdr_count": osdr_count
    })))
}