<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\JwstHelper;

class JwstController extends Controller
{
    public function feed(Request $r)
    {
        $per = max(1, min(60, (int)$r->query('perPage', 24)));
        $jw = new JwstHelper();
        $resp = $jw->get('all/type/jpg', ['perPage' => $per]);
        $list = $resp['body'] ?? $resp['data'] ?? $resp;

        $items = [];
        foreach ($list as $it) {
            if (!is_array($it)) continue;
            $url = $it['location'] ?? $it['url'] ?? JwstHelper::pickImageUrl($it);
            if (!$url) continue;
            $items[] = [
                'url' => $url,
                'link' => $it['location'] ?? $url,
                'caption' => ($it['observation_id'] ?? $it['id'] ?? 'JWST') . ' · P' . ($it['program'] ?? '-'),
            ];
            if (count($items) >= $per) break;
        }

        return response()->json(['items' => $items]);
    }
}