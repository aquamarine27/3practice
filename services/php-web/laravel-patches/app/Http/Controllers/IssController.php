<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class IssController extends Controller
{
    public function index(Request $request)
    {
        $serviceUrl = getenv('RUST_BASE') ?: 'http://rust_iss:3000';

        // МКС position
        $currentRawResponse = @file_get_contents($serviceUrl . '/last');
        $currentPosition    = $currentRawResponse ? json_decode($currentRawResponse, true) : [];
        
        // data MKC history
        $historyRawResponse = @file_get_contents($serviceUrl . '/iss/trend');
        $historyData        = $historyRawResponse ? json_decode($historyRawResponse, true) : [];

        return view('iss', [
            'last'  => $currentPosition,
            'trend' => $historyData,
            'base'  => $serviceUrl,
        ]);
    }
}