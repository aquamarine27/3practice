<?php

namespace App\Clients;

use Illuminate\Support\Facades\Http;
use App\Support\JwstHelper;

class DashboardClient
{
    private string $rustBase;

    public function __construct()
    {
        $this->rustBase = getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    // Get latest ISS position
    public function getIssLast(): array
    {
        $response = Http::timeout(10)->get($this->rustBase . '/last');

        return $response->successful() ? $response->json() : [];
    }

    // Get JWST images 
    public function getJwstFeed(string $path, array $params = []): array
    {
        $jwst = new JwstHelper();
        return $jwst->get($path, $params);
    }

    // image picker
    public static function findImageUrl(array $item): ?string
    {
        return JwstHelper::pickImageUrl($item);
    }
}