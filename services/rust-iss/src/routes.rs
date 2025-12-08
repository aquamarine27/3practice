use axum::{routing::get, Router};
use crate::config::AppState;
use crate::handlers::*;

pub fn create_router(state: AppState) -> Router {
    Router::new()
        // общее
        .route("/health", get(health_check))
        // ISS
        .route("/last", get(last_iss))
        .route("/fetch", get(trigger_iss)) 
        .route("/iss/trend", get(iss_trend))
        // OSDR
        .route("/osdr/sync", get(osdr_sync))
        .route("/osdr/list", get(osdr_list))
        .route("/osdr/filter", get(osdr_list_validated)) 
        // Space cache
        .route("/space/:src/latest", get(space_latest))
        .route("/space/refresh", get(space_refresh))
        .route("/space/summary", get(space_summary))
        .with_state(state)
}