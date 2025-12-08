use sqlx::{PgPool, Row};
use serde_json::Value;
use crate::error::ApiError;
use chrono::{DateTime, Utc}; 

pub async fn init_db(pool: &PgPool) -> Result<(), ApiError> {
    // ISS
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS iss_fetch_log(
            id BIGSERIAL PRIMARY KEY,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            source_url TEXT NOT NULL,
            payload JSONB NOT NULL
        )"
    ).execute(pool).await?;

    // OSDR
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS osdr_items(
            id BIGSERIAL PRIMARY KEY,
            dataset_id TEXT,
            title TEXT,
            status TEXT,
            updated_at TIMESTAMPTZ,
            inserted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            raw JSONB NOT NULL
        )"
    ).execute(pool).await?;
    sqlx::query(
        "CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
         ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL"
    ).execute(pool).await?;

    // универсальный кэш космоданных
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS space_cache(
            id BIGSERIAL PRIMARY KEY,
            source TEXT NOT NULL,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            payload JSONB NOT NULL
        )"
    ).execute(pool).await?;
    sqlx::query("CREATE INDEX IF NOT EXISTS ix_space_cache_source ON space_cache(source,fetched_at DESC)").execute(pool).await?;

    Ok(())
}

pub async fn write_cache(pool: &PgPool, source: &str, payload: Value) -> Result<(), ApiError> {
    sqlx::query("INSERT INTO space_cache(source, payload) VALUES ($1,$2)")
        .bind(source).bind(payload).execute(pool).await?;
    Ok(())
}

pub async fn latest_from_cache(pool: &PgPool, src: &str) -> Value {
    sqlx::query("SELECT fetched_at, payload FROM space_cache WHERE source=$1 ORDER BY id DESC LIMIT 1")
        .bind(src)
        .fetch_optional(pool).await.ok().flatten()
        .map(|r| serde_json::json!({"at": r.get::<DateTime<Utc>,_>("fetched_at"), "payload": r.get::<Value,_>("payload")}))
        .unwrap_or(serde_json::json!({}))
}

pub async fn get_osdr_count(pool: &PgPool) -> i64 {
    sqlx::query("SELECT count(*) AS c FROM osdr_items")
        .fetch_one(pool).await.map(|r| r.get::<i64,_>("c")).unwrap_or(0)
}

pub async fn get_latest_iss(pool: &PgPool) -> Result<Option<sqlx::postgres::PgRow>, ApiError> {
    sqlx::query(
        "SELECT id, fetched_at, source_url, payload
         FROM iss_fetch_log
         ORDER BY id DESC LIMIT 1"
    ).fetch_optional(pool).await.map_err(ApiError::Sqlx)
}