<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class ProxyController extends Controller
{
    private function base(): string {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    public function last()  { return $this->pipe('/last'); }
    public function trend() { return $this->pipe('/iss/trend'); }

    private function pipe(string $path)
    {
        $url = $this->base() . $path;
        $body = @file_get_contents($url) ?: '{}';
        return new Response($body, 200, ['Content-Type' => 'application/json']);
    }
}