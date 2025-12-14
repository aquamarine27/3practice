<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RateLimit
{
    private int $requestsLimit = 60;
    private int $timeWindow = 60;

    public function handle(Request $request, Closure $next)
    {
        $clientIp = $request->ip();
        $cacheKey = 'rate_limit:' . $clientIp;

        $redisConnection = $this->connectToRedis();

        if (!$redisConnection) {
            // Если Redis недоступен — пропускаем ограничение
            return $next($request);
        }

        $currentCount = (int) $redisConnection->get($cacheKey);

        if ($currentCount >= $this->requestsLimit) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                ]
            ], 429);
        }

        $redisConnection->incr($cacheKey);
        if ($currentCount === 0) {
            $redisConnection->expire($cacheKey, $this->timeWindow);
        }

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', $this->requestsLimit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $this->requestsLimit - $currentCount - 1));

        return $response;
    }

    private function connectToRedis(): ?\Redis
    {
        try {
            $redis = new \Redis();
            $redis->connect(
                getenv('REDIS_HOST') ?: 'redis',
                (int)(getenv('REDIS_PORT') ?: 6379)
            );
            return $redis;
        } catch (\Throwable $e) {
            return null;
        }
    }
}