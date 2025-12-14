<?php

namespace App\Http\Controllers;

use App\Clients\TelemetryClient;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelemetryController extends Controller
{
    private TelemetryClient $telemetryService;

    public function __construct(TelemetryClient $telemetryService)
    {
        $this->telemetryService = $telemetryService;
    }

    public function index()
    {
        // records
        $records = $this->telemetryService->getOldestRecords(50);

        return view('telemetry', compact('records'));
    }

    private function exportStream(string $filename, string $contentType, \Closure $writeHeader, \Closure $writeRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($writeHeader, $writeRow) {
            $handle = fopen('php://output', 'w');
            $writeHeader($handle);

            $this->telemetryService->streamAllRecords(function ($record) use ($handle, $writeRow) {
                $writeRow($handle, $record);
            });

            fclose($handle);
        }, $filename, ['Content-Type' => $contentType]);
    }

    public function exportCsv(): StreamedResponse
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        return $this->exportStream(
            "telemetry_{$timestamp}.csv",
            'text/csv',
            function ($handle) {
                fputcsv($handle, ['id', 'telemetry_id', 'recorded_at', 'voltage', 'temp', 'mission_status', 'is_active', 'source_file']);
            },
            function ($handle, $record) {
                fputcsv($handle, [
                    $record->id,
                    $record->telemetry_id ?? '',
                    $record->recorded_at,
                    $record->voltage,
                    $record->temp,
                    $record->mission_status ?? '',
                    $record->is_active ? 'TRUE' : 'FALSE',
                    $record->source_file ?? '',
                ]);
            }
        );
    }

    public function exportExcel(): StreamedResponse
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        $response = $this->exportCsv();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment; filename=\"telemetry_{$timestamp}.xlsx\"");

        return $response;
    }
}