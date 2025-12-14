<?php

namespace App\Clients;

use Illuminate\Support\Facades\DB;

class CmsClient
{
    // slug page
    public function fetchPageBySlug(string $slug): ?object
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            return null;
        }

        return DB::table('cms_pages')
            ->where('slug', $slug)
            ->select(['title', 'body'])
            ->first();
    }
    
    // slug block
    public function getBodyBySlug(string $slug): ?string
    {
        $page = $this->fetchPageBySlug($slug);

        return $page ? $page->body : null;
    }
}