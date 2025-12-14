<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;

class OsdrClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    public function getList(int $limit = 20): array
    {
        $url = $this->baseUrl . '/osdr/list?limit=' . $limit;

        $response = Http::timeout(15)->get($url);

        return $response->successful() ? ($response->json() ?? ['items' => []]) : ['items' => []];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}