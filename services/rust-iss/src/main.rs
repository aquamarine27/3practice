use std::sync::Arc;
use std::time::Duration;
use sqlx::postgres::PgPoolOptions;
use tokio::sync::Mutex;
use tracing::{error, info, warn};
use tracing_subscriber::{EnvFilter, FmtSubscriber};
use redis::Client as RedisClient;

mod config;
mod domain;
mod error;
mod repo;
mod clients;
mod handlers;
mod routes;

use config::{AppState, env_u64};
use repo::init_db;
use clients::{
    fetch_and_store_osdr, fetch_and_store_iss, fetch_apod,
    fetch_neo_feed, fetch_donki, fetch_spacex_next
};

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    let subscriber = FmtSubscriber::builder()
        .with_env_filter(EnvFilter::from_default_env())
        .finish();
    let _ = tracing::subscriber::set_global_default(subscriber);

    dotenvy::dotenv().ok();

    let db_url = std::env::var("DATABASE_URL").expect("DATABASE_URL is required");
    let redis_url = std::env::var("REDIS_URL").unwrap_or_else(|_| "redis://redis:6379".to_string());

    let nasa_url = std::env::var("NASA_API_URL")
        .unwrap_or_else(|_| "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json".to_string());
    let nasa_key = std::env::var("NASA_API_KEY").unwrap_or_default();
    let fallback_url = std::env::var("WHERE_ISS_URL")
        .unwrap_or_else(|_| "https://api.wheretheiss.at/v1/satellites/25544".to_string());

    let every_osdr = env_u64("FETCH_EVERY_SECONDS", 600);
    let every_iss = env_u64("ISS_EVERY_SECONDS", 120);
    let every_apod = env_u64("APOD_EVERY_SECONDS", 43200);
    let every_neo = env_u64("NEO_EVERY_SECONDS", 7200);
    let every_donki = env_u64("DONKI_EVERY_SECONDS", 3600);
    let every_spacex = env_u64("SPACEX_EVERY_SECONDS", 3600);

    let pool = PgPoolOptions::new().max_connections(5).connect(&db_url).await?;
    info!("PostgreSQL connected");
    init_db(&pool).await?;

    let redis_client = RedisClient::open(redis_url)?;
    info!("Redis client initialized");

    let state = AppState {
        pool: pool.clone(),
        redis_client,
        nasa_url,
        nasa_key,
        fallback_url,
        every_osdr,
        every_iss,
        every_apod,
        every_neo,
        every_donki,
        every_spacex,
        lock_iss: Arc::new(Mutex::new(())),
        lock_osdr: Arc::new(Mutex::new(())),
        lock_apod: Arc::new(Mutex::new(())),
        lock_neo: Arc::new(Mutex::new(())),
        lock_donki: Arc::new(Mutex::new(())),
        lock_spacex: Arc::new(Mutex::new(())),
    };


    // ISS
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                let _guard = match st.lock_iss.try_lock() {
                    Ok(guard) => guard,
                    Err(_) => {
                        warn!("ISS fetch already running, skipping this iteration");
                        tokio::time::sleep(Duration::from_secs(10)).await;
                        continue;
                    }
                };
                if let Err(e) = fetch_and_store_iss(&st.pool, &st.fallback_url).await {
                    error!("iss err {e:?}");
                }
                tokio::time::sleep(Duration::from_secs(st.every_iss)).await;
            }
        });
    }

    // OSDR
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                let _guard = match st.lock_osdr.try_lock() {
                    Ok(guard) => guard,
                    Err(_) => {
                        warn!("OSDR sync already running, skipping this iteration");
                        tokio::time::sleep(Duration::from_secs(30)).await;
                        continue;
                    }
                };
                if let Err(e) = fetch_and_store_osdr(&st).await {
                    error!("osdr err {e:?}");
                }
                tokio::time::sleep(Duration::from_secs(st.every_osdr)).await;
            }
        });
    }

    // APOD
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                let _guard = match st.lock_apod.try_lock() {
                    Ok(guard) => guard,
                    Err(_) => {
                        warn!("APOD fetch already running, skipping");
                        tokio::time::sleep(Duration::from_secs(60)).await;
                        continue;
                    }
                };
                if let Err(e) = fetch_apod(&st).await {
                    error!("apod err {e:?}");
                }
                tokio::time::sleep(Duration::from_secs(st.every_apod)).await;
            }
        });
    }

    // NEO
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                let _guard = match st.lock_neo.try_lock() {
                    Ok(guard) => guard,
                    Err(_) => {
                        warn!("NEO fetch already running, skipping");
                        tokio::time::sleep(Duration::from_secs(60)).await;
                        continue;
                    }
                };
                if let Err(e) = fetch_neo_feed(&st).await {
                    error!("neo err {e:?}");
                }
                tokio::time::sleep(Duration::from_secs(st.every_neo)).await;
            }
        });
    }

    // DONKI (FLR + CME)
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                let _guard = match st.lock_donki.try_lock() {
                    Ok(guard) => guard,
                    Err(_) => {
                        warn!("DONKI fetch already running, skipping");
                        tokio::time::sleep(Duration::from_secs(60)).await;
                        continue;
                    }
                };
                if let Err(e) = fetch_donki(&st).await {
                    error!("donki err {e:?}");
                }
                tokio::time::sleep(Duration::from_secs(st.every_donki)).await;
            }
        });
    }

    // SpaceX
    {
        let st = state.clone();
        tokio::spawn(async move {
            loop {
                let _guard = match st.lock_spacex.try_lock() {
                    Ok(guard) => guard,
                    Err(_) => {
                        warn!("SpaceX fetch already running, skipping");
                        tokio::time::sleep(Duration::from_secs(60)).await;
                        continue;
                    }
                };
                if let Err(e) = fetch_spacex_next(&st).await {
                    error!("spacex err {e:?}");
                }
                tokio::time::sleep(Duration::from_secs(st.every_spacex)).await;
            }
        });
    }

    // Запуск HTTP-сервера
    let app = routes::create_router(state);
    let listener = tokio::net::TcpListener::bind(("0.0.0.0", 3000)).await?;
    info!("rust_iss listening on 0.0.0.0:3000");
    axum::serve(listener, app.into_make_service()).await?;

    Ok(())
}