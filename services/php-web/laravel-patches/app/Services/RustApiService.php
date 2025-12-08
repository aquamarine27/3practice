<?php

namespace App\Services;

class RustApiService
{
    private string $base;

    public function __construct()
    {
        $this->base = rtrim(env('RUST_BASE', 'http://rust_iss:3000'), '/');
    }

    public function get(string $path, array $query = [])
    {
        $url = $this->base . '/' . ltrim($path, '/');
        if ($query) $url .= '?' . http_build_query($query);

        $raw = @file_get_contents($url);
        return $raw ? json_decode($raw, true) : [];
    }
}