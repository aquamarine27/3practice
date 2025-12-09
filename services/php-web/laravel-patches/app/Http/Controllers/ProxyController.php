<?php

namespace App\Http\Controllers;

use App\Clients\IssClient; 
use Illuminate\Http\Response;

class ProxyController extends Controller
{
    public function __construct(
        protected IssClient $issClient 
    ) {}

    public function last()  
    { 
        $data = $this->issClient->getLastIssData();
        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']); 
    }

    public function trend() 
    {
        $limit = request()->query('limit', 240);
        $data = $this->issClient->getIssTrend($limit);
        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);
    }
}