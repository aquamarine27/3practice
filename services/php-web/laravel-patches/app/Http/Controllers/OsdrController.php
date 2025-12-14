<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Clients\OsdrClient;

class OsdrController extends Controller
{
    private OsdrClient $client;

    public function __construct(OsdrClient $client)
    {
        $this->client = $client;
    }

    public function index(Request $request)
    {
        // validate limit parameter
        $limit = max(1, min(100, (int) $request->query('limit', 20)));

        // get data from OSDR service
        $data = $this->client->getList($limit);
        $rawItems = $data['items'] ?? [];
        $processedItems = $this->flattenOsdr($rawItems);

        $sourceUrl = $this->client->getBaseUrl() . '/osdr/list?limit=' . $limit;

        return view('osdr', [
            'items' => $processedItems,
            'src'   => $sourceUrl,
        ]);
    }

    private function flattenOsdr(array $items): array
    {
        $result = [];
        foreach ($items as $row) {
            $raw = $row['raw'] ?? [];
            if (is_array($raw) && $this->looksOsdrDict($raw)) {
                foreach ($raw as $key => $value) {
                    if (!is_array($value)) continue;

                    $restUrl = $value['REST_URL'] ?? $value['rest_url'] ?? $value['rest'] ?? null;
                    $title   = $value['title'] ?? $value['name'] ?? null;

                    if (!$title && is_string($restUrl)) {
                        $title = basename(rtrim($restUrl, '/'));
                    }

                    $result[] = [
                        'id'          => $row['id'],
                        'dataset_id'  => $key,
                        'title'       => $title,
                        'status'      => $row['status'] ?? null,
                        'updated_at'  => $row['updated_at'] ?? null,
                        'inserted_at' => $row['inserted_at'] ?? null,
                        'rest_url'    => $restUrl,
                        'raw'         => $value,
                    ];
                }
            } else {
                $row['rest_url'] = is_array($raw) ? ($raw['REST_URL'] ?? $raw['rest_url'] ?? null) : null;
                $result[] = $row;
            }
        }
        return $result;
    }

    private function looksOsdrDict(array $raw): bool
    {
        foreach ($raw as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'OSD-')) {
                return true;
            }
            if (is_array($value) && (isset($value['REST_URL']) || isset($value['rest_url']))) {
                return true;
            }
        }
        return false;
    }
}