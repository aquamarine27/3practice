<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class CmsController extends Controller
{
    public function page(string $slug)
    {
        $row = DB::selectOne("SELECT title, body AS content FROM cms_pages WHERE slug = ?", [$slug]);
        if (!$row) abort(404);

        return view('cms.page', ['title' => $row->title, 'html' => $row->content]);
    }
}