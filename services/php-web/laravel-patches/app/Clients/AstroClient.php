<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

class AstroClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $appId  = env('ASTRO_APP_ID');
        $secret = env('ASTRO_APP_SECRET');

        $authHeader = '';
        if ($appId && $secret) {
            $authHeader = 'Basic ' . base64_encode($appId . ':' . $secret);
        }

        $this->http = Http::baseUrl('https://api.astronomyapi.com/api/v2')
            ->withHeaders([
                'Authorization' => $authHeader,
                'Content-Type'  => 'application/json',
                'User-Agent'    => 'monolith-iss/1.0',
            ])
            ->timeout(25)
            ->retry(2, 500);
    }

    // request body events
    public function fetchBodyEvents(string $body, array $params): array
    {
        $response = $this->http->get("/bodies/events/{$body}", $params);

        if ($response->failed()) {
            return [
                'error' => true,
                'code'  => $response->status(),
                'raw'   => $response->body(),
            ];
        }

        return $response->json() ?? [];
    }
}