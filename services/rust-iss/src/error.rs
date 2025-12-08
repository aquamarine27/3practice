use axum::http::StatusCode;
use axum::response::{IntoResponse, Response};
use serde_json::json;
use validator::ValidationErrors; 

// Универсальная структура для ошибок
#[derive(Debug, thiserror::Error)]
pub enum ApiError {
    #[error("SQLx error: {0}")]
    Sqlx(#[from] sqlx::Error),
    #[error("Request error: {0}")]
    Reqwest(#[from] reqwest::Error),
    #[error("Redis error: {0}")]
    Redis(#[from] redis::RedisError),
    #[error("Validation error: {0}")] 
    Validation(#[from] ValidationErrors), 
    #[error("Not Found: {0}")]
    NotFound(String),
    #[error("Bad Request: {0}")] 
    BadRequest(String),
    #[error("Too Many Requests: {0}")] 
    TooManyRequests(String),
    #[error("Internal Server Error: {0}")]
    Internal(String),
    #[error("Unauthorized: {0}")]
    Unauthorized(String),
}


impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        let (status, error_message, detail_message) = match &self {
            ApiError::Sqlx(e) => {
                tracing::error!("SQLx Error: {:?}", e);
                (StatusCode::INTERNAL_SERVER_ERROR, "Database Error", self.to_string())
            },
            ApiError::Reqwest(e) => {
                tracing::error!("Reqwest Error: {:?}", e);
                (StatusCode::BAD_GATEWAY, "External API Error", self.to_string())
            },
            ApiError::Redis(e) => {
                tracing::error!("Redis Error: {:?}", e);
                (StatusCode::INTERNAL_SERVER_ERROR, "Cache Error", self.to_string())
            },
            
            ApiError::Validation(e) => {
                (StatusCode::BAD_REQUEST, "Validation Failed", format!("Invalid input: {}", e))
            }
            ApiError::NotFound(msg) => (StatusCode::NOT_FOUND, "Not Found", msg.clone()),
            ApiError::BadRequest(msg) => (StatusCode::BAD_REQUEST, "Bad Request", msg.clone()),
            
            ApiError::TooManyRequests(msg) => (StatusCode::TOO_MANY_REQUESTS, "Too Many Requests", msg.clone()), 
            ApiError::Unauthorized(msg) => (StatusCode::UNAUTHORIZED, "Unauthorized", msg.clone()),
            ApiError::Internal(msg) => (StatusCode::INTERNAL_SERVER_ERROR, "Internal Error", msg.clone()),
        };

        let body = json!({
            "error": error_message,
            "details": detail_message,
        });

        (status, axum::Json(body)).into_response()
    }
}