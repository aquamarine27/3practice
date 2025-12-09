<?php

namespace App\Http\Controllers;

use App\Clients\IssClient; 

class IssController extends Controller
{
    public function __construct(
        protected IssClient $issClient 
    ) {}

    public function index()
    {
        
        $lastJson = $this->issClient->getLastIssData();
        $trendJson = $this->issClient->getIssTrend();

        // Базовый URL для отображения в представлении
        $base = rtrim(env('RUST_BASE') ?: 'http://rust_iss:3000', '/');


        return view('iss', [
            'last' => $lastJson, 
            'trend' => $trendJson, 
            'base' => $base
        ]);
    }
}