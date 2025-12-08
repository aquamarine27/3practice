use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize}; 
use validator::Validate; 

#[derive(Serialize)]
pub struct Health { 
    pub status: &'static str, 
    pub now: DateTime<Utc> 
}


#[derive(Debug, Deserialize, Validate)]
pub struct OsdrQuery {
    // limit должен быть от 1 до 100
    #[validate(range(min = 1, max = 100, message = "Limit must be between 1 and 100"))]
    pub limit: i64,
    // query, не короче 3 символов
    #[validate(length(min = 3, message = "Query must be at least 3 chars long"))]
    pub query: Option<String>,
}

#[derive(Serialize)]
pub struct Trend {
    pub movement: bool,
    pub delta_km: f64,
    pub dt_sec: f64,
    pub velocity_kmh: Option<f64>,
    pub from_time: Option<DateTime<Utc>>,
    pub to_time: Option<DateTime<Utc>>,
    pub from_lat: Option<f64>,
    pub from_lon: Option<f64>,
    pub to_lat: Option<f64>,
    pub to_lon: Option<f64>,
}