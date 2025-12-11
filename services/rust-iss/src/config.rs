use sqlx::PgPool;
use redis::Client as RedisClient;
use std::sync::Arc;
use tokio::sync::Mutex;

#[derive(Clone)]
pub struct AppState {
    pub pool: PgPool,
    pub redis_client: RedisClient,
    pub nasa_url: String,
    pub nasa_key: String,
    pub fallback_url: String,
    pub every_osdr: u64,
    pub every_iss: u64,
    pub every_apod: u64,
    pub every_neo: u64,
    pub every_donki: u64,
    pub every_spacex: u64,

    // Защита от наложения фоновых задач
    pub lock_iss: Arc<Mutex<()>>,
    pub lock_osdr: Arc<Mutex<()>>,
    pub lock_apod: Arc<Mutex<()>>,
    pub lock_neo: Arc<Mutex<()>>,
    pub lock_donki: Arc<Mutex<()>>,
    pub lock_spacex: Arc<Mutex<()>>,
}

pub fn env_u64(k: &str, d: u64) -> u64 {
    std::env::var(k).ok().and_then(|s| s.parse().ok()).unwrap_or(d)
}