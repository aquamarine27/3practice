<?php

namespace App\Http\Controllers;

use App\Repositories\CmsRepository; 
use Illuminate\Support\Facades\App;


class CmsController extends Controller {

    public function __construct(
        protected CmsRepository $cmsRepository 
    ) {}

    public function page(string $slug) {

        $row = $this->cmsRepository->getPageBySlug($slug); 
        
        if (!$row) abort(404);

        $purifier = App::make('html.purifier');
        $safeHtml = $purifier->purify($row->content);
        
        return view('cms.page', [
            'title' => $row->title, 
            'html' => $safeHtml 
        ]);
    }
}