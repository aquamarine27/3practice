<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CmsRepository
{
    
    public function getPageBySlug(string $slug): ?object
    {
        // рагрузка БД с кешированием
        return Cache::remember("cms_page:{$slug}", 3600, function () use ($slug) {
            // параметризованный запрос для защиты от SQL-инъекций
            return DB::table('cms_blocks')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->select(['title', 'content', 'id'])
                ->first();
        });
    }

    
    public function getContentBySlug(string $slug): ?string
    {
        $row = $this->getPageBySlug($slug);
        return $row->content ?? null;
    }
}