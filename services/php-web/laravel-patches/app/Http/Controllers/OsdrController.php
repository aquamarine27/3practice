<?php

namespace App\Http\Controllers;

use App\Services\OsdrService; 
use Illuminate\Http\Request;

class OsdrController extends Controller
{
    public function __construct(
        protected OsdrService $osdrService 
    ) {}

    public function index(Request $request)
    {
        // Валидация
        $limit = $request->query('limit', 20); 
        $limit = max(1, min(100, (int)$limit)); 

        // Делегируем получение и нормализацию Service Layer
        $items = $this->osdrService->getNormalizedOsdrList($limit);
        
        $base = getenv('RUST_BASE') ?: 'http://rust_iss:3000';


        return view('osdr', [
            'items' => $items,
            'src'   => $base.'/osdr/list?limit='.$limit,
        ]);
    }
}