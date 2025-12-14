<?php

namespace App\Clients;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TelemetryClient
{
    public function getRecentRecords(int $limit = 50): Collection
    {
        return DB::table('telemetry_legacy')
            ->orderByDesc('recorded_at')
            ->limit($limit)
            ->get();
    }

    public function getOldestRecords(int $limit = 50): Collection
    {
        // records
        return DB::table('telemetry_legacy')
            ->orderBy('recorded_at')
            ->limit($limit)
            ->get();
    }

    public function streamAllRecords(\Closure $callback): void
    {
        DB::table('telemetry_legacy')
            ->orderBy('id')
            ->chunk(1000, function ($rows) use ($callback) {
                foreach ($rows as $row) {
                    $callback($row);
                }
            });
    }
}