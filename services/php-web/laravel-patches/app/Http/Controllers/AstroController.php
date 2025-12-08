<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AstroController extends Controller
{
    public function events(Request $r)
    {
        $lat = (float) $r->query('lat', 55.7558);
        $lon = (float) $r->query('lon', 37.6176);
        $days = max(1, min(30, (int) $r->query('days', 7)));

        $from = now('UTC')->toDateString();
        $to = now('UTC')->addDays($days)->toDateString();

        $auth = base64_encode(env('ASTRO_APP_ID') . ':' . env('ASTRO_APP_SECRET'));
        $url = "https://api.astronomyapi.com/api/v2/bodies/events?" . http_build_query([
            'latitude' => $lat, 'longitude' => $lon, 'from' => $from, 'to' => $to
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $auth],
            CURLOPT_TIMEOUT => 25,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        return response()->json(json_decode($raw, true) ?? ['error' => 'API failed']);
    }
}