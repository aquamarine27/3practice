<?php

namespace App\Support;

class ContentSanitizer
{
    public function sanitize(?string $content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        // scripts
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $content);

        // iframes
        $content = preg_replace('/<(iframe|object|embed|form)[^>]*>.*?<\/\1>/is', '', $content);
        $content = preg_replace('/<(iframe|object|embed|form)[^>]*\/?>/is', '', $content);

        // event handlers
        $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $content);

        // block javascript
        $content = preg_replace('/\b(href|src)\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', '', $content);

        return $content;
    }
}