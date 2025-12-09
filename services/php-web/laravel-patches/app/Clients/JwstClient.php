<?php

namespace App\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class JwstClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $host = rtrim(env('JWST_HOST') ?: 'https://api.jwstapi.com', '/');
        $key = env('JWST_API_KEY') ?: '';
        $email = env('JWST_EMAIL');

        $headers = ['x-api-key' => $key];
        if ($email) {
            $headers['email'] = $email;
        }

        $this->http = Http::baseUrl($host)
            ->withHeaders($headers)
            ->timeout(15)
            ->retry(2, 500);
    }

    public function get(string $path, array $qs = []): array
    {
        $response = $this->http->get($path, $qs);
        return $response->json() ?? [];
    }

    public static function pickImageUrl(array $v): ?string
    {
        $stack = [$v];
        while ($stack) {
            $cur = array_pop($stack);
            foreach ($cur as $k => $val) {
                if (is_string($val) && preg_match('~^https?://.*\.(?:jpg|jpeg|png)$~i', $val)) return $val;
                if (is_array($val)) $stack[] = $val;
            }
        }
        return null;
    }
}