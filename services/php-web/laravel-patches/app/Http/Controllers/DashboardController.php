<?php

namespace App\Http\Controllers;

use App\Clients\IssClient;      
use App\Clients\JwstClient;    
use App\Repositories\CmsRepository; 
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected IssClient $issClient,      
        protected JwstClient $jwstClient,   
        protected CmsRepository $cmsRepository 
    ) {}

    public function index()
    {
        // данные МКС через Client
        $iss = $this->issClient->getLastIssData();
        
        // контент CMS-блока через Repository 
        $cmsBlockContent = $this->cmsRepository->getContentBySlug('dashboard_experiment');
        
        // ViewModel для представления
        $viewModel = [
            'iss' => $iss,
            'cmsBlockContent' => $cmsBlockContent, 
            'metrics' => [
                'iss_speed' => $iss['payload']['velocity'] ?? null,
                'iss_alt'   => $iss['payload']['altitude'] ?? null,
                'neo_total' => 0, 
            ],
        ];

       
        return view('dashboard', $viewModel);
    }

 
    public function jwstFeed(Request $r)
    {
        // Валидация входных данных
        $src   = $r->query('source', 'jpg');
        $sfx   = trim((string)$r->query('suffix', ''));
        $prog  = trim((string)$r->query('program', ''));
        $instF = strtoupper(trim((string)$r->query('instrument', '')));
        $page  = max(1, (int)$r->query('page', 1));
        $per   = max(1, min(60, (int)$r->query('perPage', 24)));

        // Выбор эндпоинта
        $path = 'all/type/jpg';
        if ($src === 'suffix' && $sfx !== '') $path = 'all/suffix/'.ltrim($sfx,'/');
        if ($src === 'program' && $prog !== '') $path = 'program/id/'.rawurlencode($prog);

        // Вызов API через Client
        $resp = $this->jwstClient->get($path, ['page'=>$page, 'perPage'=>$per]);
        
        // Логика нормализации 
        $list = $resp['body'] ?? ($resp['data'] ?? (is_array($resp) ? $resp : []));
        $items = [];
        foreach ($list as $it) {
            if (!is_array($it)) continue;

            $url = null;
            $loc = $it['location'] ?? $it['url'] ?? null;
            $thumb = $it['thumbnail'] ?? null;
            foreach ([$loc, $thumb] as $u) {
                if (is_string($u) && preg_match('~\.(jpg|jpeg|png)(\?.*)?$~i', $u)) { $url = $u; break; }
            }
            if (!$url) {
                $url = \App\Clients\JwstClient::pickImageUrl($it); 
            }
            if (!$url) continue;

            $instList = [];
            foreach (($it['details']['instruments'] ?? []) as $I) {
                if (is_array($I) && !empty($I['instrument'])) $instList[] = strtoupper($I['instrument']);
            }
            if ($instF && $instList && !in_array($instF, $instList, true)) continue;

            $items[] = [
                'url'       => $url,
                'obs'       => (string)($it['observation_id'] ?? $it['observationId'] ?? ''),
                'program'   => (string)($it['program'] ?? ''),
                'suffix'    => (string)($it['details']['suffix'] ?? $it['suffix'] ?? ''),
                'inst'      => $instList,
                'caption'   => trim(
                    (($it['observation_id'] ?? '') ?: ($it['id'] ?? '')) .
                    ' · P' . ($it['program'] ?? '-') .
                    (($it['details']['suffix'] ?? '') ? ' · ' . $it['details']['suffix'] : '') .
                    ($instList ? ' · ' . implode('/', $instList) : '')
                ),
                'link'      => $loc ?: $url,
            ];
            if (count($items) >= $per) break;
        }

        return response()->json([
            'source' => $path,
            'count'  => count($items),
            'items'  => $items,
        ]);
    }
}