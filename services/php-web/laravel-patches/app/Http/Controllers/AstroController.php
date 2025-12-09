<?php

namespace App\Http\Controllers;

use App\Clients\AstroClient;
use Illuminate\Http\Request;

class AstroController extends Controller
{
    public function __construct(
        protected AstroClient $astroClient 
    ) {}

    public function events(Request $r)
    {
        // Валидация 
        $r->validate([
            'lat' => 'nullable|numeric',
            'lon' => 'nullable|numeric',
            'days' => 'nullable|integer|min:1|max:30',
        ]);
        
        $lat  = (float) $r->query('lat', 55.7558);
        $lon  = (float) $r->query('lon', 37.6176);
        $days = (int) $r->query('days', 7);

 
        $json = $this->astroClient->getEvents($lat, $lon, $days);

        // Обработка единого формата ошибок 
        if (isset($json['ok']) && $json['ok'] === false) {
             return response()->json($json, 200); // Всегда HTTP 200
        }
        
        return response()->json($json ?? ['error' => 'unknown error']);
    }
}