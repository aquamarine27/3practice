<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Clients\AstroClient;

class AstroController extends Controller
{
    private AstroClient $apiClient;

    public function __construct(AstroClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function index()
    {
        return view('astro');
    }

    // fetch astronomical events
    public function events(Request $request)
    {
        // Validate input parameters
        $lat        = max(-90.0,  min(90.0,  (float) $request->query('lat', 55.7558)));
        $lon        = max(-180.0, min(180.0, (float) $request->query('lon', 37.6176)));
        $elevation  = max(0,      min(10000, (int)   $request->query('elevation', 0)));
        $days       = max(1,      min(365,   (int)   $request->query('days', 7)));

        $timeInput  = $request->query('time');
        $time       = $timeInput ? $timeInput . ':00' : now('UTC')->format('H:i:s');

        $fromDate   = now('UTC')->toDateString();
        $toDate     = now('UTC')->addDays($days)->toDateString();

        // Check API credentials
        if (empty(env('ASTRO_APP_ID')) || empty(env('ASTRO_APP_SECRET'))) {
            return response()->json([
                'ok'    => false,
                'error' => [
                    'code'    => 'CONFIG_ERROR',
                    'message' => 'Missing ASTRO credentials'
                ]
            ], 500);
        }

        // Fetch events
        $bodies     = ['sun', 'moon'];
        $results    = [];

        foreach ($bodies as $body) {
            $params = [
                'latitude'   => $lat,
                'longitude'  => $lon,
                'elevation'  => $elevation,
                'from_date'  => $fromDate,
                'to_date'    => $toDate,
                'time'       => $time,
            ];

            $response = $this->apiClient->fetchBodyEvents($body, $params);

            if (isset($response['error'])) {
                $results[$body] = ['error' => true];
                continue;
            }

            // API response
            $events = $response['data']['table']['rows'][0]['cells'] ?? [];

            if (empty($events)) {
                $events = [];
                foreach ($response['data']['rows'] ?? [] as $row) {
                    foreach ($row['events'] ?? [] as $ev) {
                        $events[] = $ev;
                    }
                }
            }

            foreach ($events as &$event) {
                $event['body'] = $body;
                $event['name'] = ucfirst($body);

                if (isset($event['eventHighlights']['peak']['date'])) {
                    $event['date'] = $event['eventHighlights']['peak']['date'];
                } elseif (isset($event['time']['utc'])) {
                    $event['date'] = $event['time']['utc'];
                } elseif (isset($event['peak']['utc'])) {
                    $event['date'] = $event['peak']['utc'];
                } else {
                    $event['date'] = null;
                }
            }
            unset($event);

            $results[$body] = $events;
        }

        // all valid events
        $finalEvents = [];
        foreach ($results as $bodyEvents) {
            if (is_array($bodyEvents) && !isset($bodyEvents['error'])) {
                $finalEvents = array_merge($finalEvents, $bodyEvents);
            }
        }

        // Return response
        return response()->json([
            'ok'     => true,
            'events' => $finalEvents,
            'count'  => count($finalEvents)
        ]);
    }
}