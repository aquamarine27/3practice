<?php

namespace App\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AstroClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $appId = env('ASTRO_APP_ID');
        $secret = env('ASTRO_APP_SECRET');

        if (empty($appId) || empty($secret)) {
            $authHeader = ''; 
        } else {
            $authHeader = 'Basic ' . base64_encode($appId . ':' . $secret);
        }

        $this->http = Http::baseUrl('https://api.astronomyapi.com/api/v2')
            ->withHeaders([
                'Authorization' => $authHeader,
                'Content-Type' => 'application/json',
                'User-Agent' => 'monolith-iss/2.0' 
            ])
            ->timeout(20) 
            ->retry(2, 500);
    }

    public function getEvents(float $lat, float $lon, int $days): array
    {
        $from = now('UTC')->toDateString();
        $to = now('UTC')->addDays($days)->toDateString();

        
        if (empty(env('ASTRO_APP_ID'))) {
            return ['error' => 'Missing ASTRO_APP_ID/ASTRO_APP_SECRET'];
        }
        
        $response = $this->http->get('/bodies/events', [
            'latitude' => $lat,
            'longitude' => $lon,
            'from' => $from,
            'to' => $to,
        ]);
        
       
        if ($response->successful()) {
             return $response->json() ?? [];
        }

        
        return [
            "ok" => false, 
            "error" => [
                "code" => "UPSTREAM_" . $response->status(), 
                "message" => "AstronomyAPI returned HTTP " . $response->status(),
                "trace_id" => "N/A" 
            ]
        ];
    }
}