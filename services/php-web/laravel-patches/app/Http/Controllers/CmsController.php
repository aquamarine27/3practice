<?php

namespace App\Http\Controllers;

use App\Clients\CmsClient;
use App\Support\ContentSanitizer;

class CmsController extends Controller
{
    private CmsClient $cmsService;
    private ContentSanitizer $sanitizer;

    public function __construct(CmsClient $cmsService, ContentSanitizer $sanitizer)
    {
        $this->cmsService = $cmsService;
        $this->sanitizer = $sanitizer;
    }

    // main cms page
    public function index()
    {
        $welcomeContent = $this->getSafeBlock('welcome');
        $unsafeContent  = $this->getSafeBlock('unsafe');

        return view('cms', [
            'cmsWelcome' => $welcomeContent,
            'cmsUnsafe'  => $unsafeContent,
        ]);
    }

    // dynamic page by slug
    public function page(string $slug)
    {
        $pageData = $this->cmsService->fetchPageBySlug($slug);

        if (!$pageData) {
            abort(404);
        }

        return view('cms.page', [
            'title' => e($pageData->title),
            'html'  => $this->sanitizer->sanitize($pageData->body),
        ]);
    }

    // sanitize cms block 
    private function getSafeBlock(string $slug): ?string
    {
        $rawBody = $this->cmsService->getBodyBySlug($slug);

        return $rawBody ? $this->sanitizer->sanitize($rawBody) : null;
    }
}