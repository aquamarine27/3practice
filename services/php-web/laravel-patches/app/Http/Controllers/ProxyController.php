<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProxyController extends Controller
{
    private array $allowedKeys = ['from', 'to', 'limit'];

    private function base(): string
    {
        return getenv('RUST_BASE') ?: 'http://rust_iss:3000';
    }

    public function last()
    {
        return $this->pipe('/last');
    }

    public function trend(Request $request)
    {
        
        $params = [];
        foreach ($this->allowedKeys as $key) {
            $val = $request->query($key);
            if ($val !== null && preg_match('/^[\d\-:TZ]+$/', (string) $val)) {
                $params[$key] = $val;
            }
        }
        $queryString = $params ? '?' . http_build_query($params) : '';

        return $this->pipe('/iss/trend' . $queryString);
    }

    private function pipe(string $path)
    {
        $url = $this->base() . $path;

        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 8,        
                    'ignore_errors' => true
                ],
            ]);

            $body = @file_get_contents($url, false, $ctx);

            
            if ($body === false || trim($body) === '') {
                $body = '{}';
            }

            
            json_decode($body);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $body = '{}';
            }

            return new Response($body, 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $e) {
            return new Response(
                '{"ok":false,"error":{"code":"UPSTREAM_ERROR","message":"Service unavailable"}}',
                503,  
                ['Content-Type' => 'application/json']
            );
        }
    }
}