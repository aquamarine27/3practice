<?php

namespace App\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;


 // Клиент для взаимодействия с бэкендом rust_iss
class IssClient
{
    private PendingRequest $http;
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('RUST_BASE') ?: 'http://rust_iss:3000', '/');
        $this->http = Http::baseUrl($this->baseUrl)
            ->timeout(5)
            ->retry(3, 100);
    }

    // Получает последние данные по МКС 
    public function getLastIssData(): array
    {
        $response = $this->http->get('/last');
        return $response->json() ?? [];
    }

    // Получает траекторию, графики по МКС 
    public function getIssTrend(int $limit = 240): array
    {
        $response = $this->http->get('/iss/trend', ['limit' => $limit]);
        return $response->json() ?? [];
    }

    // Получает список данных OSDR 
    public function getOsdrList(int $limit = 20): array
    {
        $response = $this->http->get('/osdr/list', ['limit' => $limit]);
        return $response->json() ?? ['items' => []];
    }
}