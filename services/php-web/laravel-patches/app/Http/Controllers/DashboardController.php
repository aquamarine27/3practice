<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Clients\DashboardClient;

class DashboardController extends Controller
{
    private DashboardClient $client;

    public function __construct(DashboardClient $client)
    {
        $this->client = $client;
    }

    // Main dashboard view
    public function index()
    {
        $issData = $this->client->getIssLast();

        $refreshInterval = (int)(getenv('ISS_EVERY_SECONDS') ?: 120);

        return view('dashboard', [
            'iss'               => $issData,
            'issEverySeconds'   => $refreshInterval,
        ]);
    }

    // JWST images feed 
    public function jwstFeed(Request $request)
    {
        // Sanitize input
        $source     = $request->query('source', 'jpg');
        $suffix     = trim((string)$request->query('suffix', ''));
        $program    = trim((string)$request->query('program', ''));
        $instFilter = strtoupper(trim((string)$request->query('instrument', '')));
        $page       = max(1, (int)$request->query('page', 1));
        $perPage    = max(1, min(60, (int)$request->query('perPage', 24)));

        // Determine API path
        $apiPath = 'all/type/jpg';
        if ($source === 'suffix' && $suffix !== '') {
            $apiPath = 'all/suffix/' . ltrim($suffix, '/');
        }
        if ($source === 'program' && $program !== '') {
            $apiPath = 'program/id/' . rawurlencode($program);
        }

        $rawData = $this->client->getJwstFeed($apiPath, ['page' => $page, 'perPage' => $perPage]);

        $itemsList = $rawData['body'] ?? $rawData['data'] ?? (is_array($rawData) ? $rawData : []);

        $finalItems = [];
        foreach ($itemsList as $entry) {
            if (!is_array($entry)) continue;
            $imageUrl = null;
            $candidates = [$entry['location'] ?? null, $entry['thumbnail'] ?? null, $entry['url'] ?? null];

            foreach ($candidates as $url) {
                if (is_string($url) && preg_match('~\.(jpg|jpeg|png)(\?.*)?$~i', $url)) {
                    $imageUrl = $url;
                    break;
                }
            }

            if (!$imageUrl) {
                $imageUrl = DashboardClient::findImageUrl($entry);
            }

            if (!$imageUrl) continue;

            $instruments = [];
            foreach (($entry['details']['instruments'] ?? []) as $inst) {
                if (is_array($inst) && !empty($inst['instrument'])) {
                    $instruments[] = strtoupper($inst['instrument']);
                }
            }

            // Filters
            if ($instFilter && $instruments && !in_array($instFilter, $instruments, true)) {
                continue;
            }

            // Caption
            $caption = trim(
                (($entry['observation_id'] ?? $entry['id'] ?? '') ?: '') .
                ' · P' . ($entry['program'] ?? '-') .
                (($entry['details']['suffix'] ?? $entry['suffix'] ?? '') ? ' · ' . ($entry['details']['suffix'] ?? $entry['suffix'] ?? '') : '') .
                ($instruments ? ' · ' . implode('/', $instruments) : '')
            );

            $finalItems[] = [
                'url'      => $imageUrl,
                'obs'      => (string)($entry['observation_id'] ?? $entry['observationId'] ?? ''),
                'program'  => (string)($entry['program'] ?? ''),
                'suffix'   => (string)($entry['details']['suffix'] ?? $entry['suffix'] ?? ''),
                'inst'     => $instruments,
                'caption'  => $caption,
                'link'     => $entry['location'] ?? $imageUrl,
            ];

            if (count($finalItems) >= $perPage) break;
        }

        return response()->json([
            'source' => $apiPath,
            'count'  => count($finalItems),
            'items'  => $finalItems,
        ]);
    }
}