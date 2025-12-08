use reqwest::Client;
use serde_json::Value;
use std::time::Duration;
use chrono::{DateTime, TimeZone, Utc, NaiveDateTime};
use sqlx::PgPool;

use crate::config::AppState;
use crate::repo::write_cache;
use crate::error::ApiError;

// --- Вспомогательные функции ---

pub fn num(v: &Value) -> Option<f64> {
    if let Some(x) = v.as_f64() { return Some(x); }
    if let Some(s) = v.as_str() { return s.parse::<f64>().ok(); }
    None
}

pub fn haversine_km(lat1: f64, lon1: f64, lat2: f64, lon2: f64) -> f64 {
    let rlat1 = lat1.to_radians();
    let rlat2 = lat2.to_radians();
    let dlat = (lat2 - lat1).to_radians();
    let dlon = (lon2 - lon1).to_radians();
    let a = (dlat / 2.0).sin().powi(2) + rlat1.cos() * rlat2.cos() * (dlon / 2.0).sin().powi(2);
    let c = 2.0 * a.sqrt().atan2((1.0 - a).sqrt());
    6371.0 * c
}

pub fn last_days(n: i64) -> (String,String) {
    let to = Utc::now().date_naive();
    let from = to - chrono::Days::new(n as u64);
    (from.to_string(), to.to_string())
}

pub fn s_pick(v: &Value, keys: &[&str]) -> Option<String> {
    for k in keys {
        if let Some(x) = v.get(*k) {
            if let Some(s) = x.as_str() { if !s.is_empty() { return Some(s.to_string()); } }
            else if x.is_number() { return Some(x.to_string()); }
        }
    }
    None
}
pub fn t_pick(v: &Value, keys: &[&str]) -> Option<DateTime<Utc>> {
    for k in keys {
        if let Some(x) = v.get(*k) {
            if let Some(s) = x.as_str() {
                if let Ok(dt) = s.parse::<DateTime<Utc>>() { return Some(dt); }
                if let Ok(ndt) = NaiveDateTime::parse_from_str(s, "%Y-%m-%d %H:%M:%S") {
                    return Some(Utc.from_utc_datetime(&ndt));
                }
            } else if let Some(n) = x.as_i64() {
                return Some(Utc.timestamp_opt(n, 0).single().unwrap_or_else(Utc::now));
            }
        }
    }
    None
}

// --- Фетчеры  ---

pub async fn fetch_and_store_iss(pool: &PgPool, url: &str) -> Result<(), ApiError> {
    let client = Client::builder().timeout(Duration::from_secs(20)).build()?;
    let resp = client.get(url).send().await?;
    let json: Value = resp.json().await?;
    sqlx::query("INSERT INTO iss_fetch_log (source_url, payload) VALUES ($1, $2)")
        .bind(url).bind(json).execute(pool).await?;
    Ok(())
}

pub async fn fetch_and_store_osdr(st: &AppState) -> Result<usize, ApiError> {
    let client = Client::builder().timeout(Duration::from_secs(30)).build()?;
    let resp = client.get(&st.nasa_url).send().await?;
    if !resp.status().is_success() {
        return Err(ApiError::Reqwest(resp.error_for_status().unwrap_err()));
    }
    let json: Value = resp.json().await?;
    let items = if let Some(a) = json.as_array() { a.clone() }
        else if let Some(v) = json.get("items").and_then(|x| x.as_array()) { v.clone() }
        else if let Some(v) = json.get("results").and_then(|x| x.as_array()) { v.clone() }
        else { vec![json.clone()] };

    let mut written = 0usize;
    for item in items {
        let id = s_pick(&item, &["dataset_id","id","uuid","studyId","accession","osdr_id"]);
        let title = s_pick(&item, &["title","name","label"]);
        let status = s_pick(&item, &["status","state","lifecycle"]);
        let updated = t_pick(&item, &["updated","updated_at","modified","lastUpdated","timestamp"]);
        
        if let Some(ds) = id.clone() {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)
                 ON CONFLICT (dataset_id) DO UPDATE
                 SET title=EXCLUDED.title, status=EXCLUDED.status,
                     updated_at=EXCLUDED.updated_at, raw=EXCLUDED.raw, inserted_at=now()"
            ).bind(ds).bind(title).bind(status).bind(updated).bind(item).execute(&st.pool).await?;
        } else {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)"
            ).bind::<Option<String>>(None).bind(title).bind(status).bind(updated).bind(item).execute(&st.pool).await?;
        }
        written += 1;
    }
    Ok(written)
}

pub async fn fetch_apod(st: &AppState) -> Result<(), ApiError> {
    let url = "https://api.nasa.gov/planetary/apod";
    let client = Client::builder().timeout(Duration::from_secs(30)).build()?;
    let mut req = client.get(url).query(&[("thumbs","true")]);
    if !st.nasa_key.is_empty() { req = req.query(&[("api_key",&st.nasa_key)]); }
    let json: Value = req.send().await?.json().await?;
    write_cache(&st.pool, "apod", json).await
}

pub async fn fetch_neo_feed(st: &AppState) -> Result<(), ApiError> {
    let today = Utc::now().date_naive();
    let start = today - chrono::Days::new(2);
    let url = "https://api.nasa.gov/neo/rest/v1/feed";
    let client = Client::builder().timeout(Duration::from_secs(30)).build()?;
    let mut req = client.get(url).query(&[
        ("start_date", start.to_string()),
        ("end_date", today.to_string()),
    ]);
    if !st.nasa_key.is_empty() { req = req.query(&[("api_key",&st.nasa_key)]); }
    let json: Value = req.send().await?.json().await?;
    write_cache(&st.pool, "neo", json).await
}

pub async fn fetch_donki(st: &AppState) -> Result<(), ApiError> {
    let _ = fetch_donki_flr(st).await;
    let _ = fetch_donki_cme(st).await;
    Ok(())
}
pub async fn fetch_donki_flr(st: &AppState) -> Result<(), ApiError> {
    let (from,to) = last_days(5);
    let url = "https://api.nasa.gov/DONKI/FLR";
    let client = Client::builder().timeout(Duration::from_secs(30)).build()?;
    let mut req = client.get(url).query(&[("startDate",from),("endDate",to)]);
    if !st.nasa_key.is_empty() { req = req.query(&[("api_key",&st.nasa_key)]); }
    let json: Value = req.send().await?.json().await?;
    write_cache(&st.pool, "flr", json).await
}
pub async fn fetch_donki_cme(st: &AppState) -> Result<(), ApiError> {
    let (from,to) = last_days(5);
    let url = "https://api.nasa.gov/DONKI/CME";
    let client = Client::builder().timeout(Duration::from_secs(30)).build()?;
    let mut req = client.get(url).query(&[("startDate",from),("endDate",to)]);
    if !st.nasa_key.is_empty() { req = req.query(&[("api_key",&st.nasa_key)]); }
    let json: Value = req.send().await?.json().await?;
    write_cache(&st.pool, "cme", json).await
}

pub async fn fetch_spacex_next(st: &AppState) -> Result<(), ApiError> {
    let url = "https://api.spacexdata.com/v4/launches/next";
    let client = Client::builder().timeout(Duration::from_secs(30)).build()?;
    let json: Value = client.get(url).send().await?.json().await?;
    write_cache(&st.pool, "spacex", json).await
}