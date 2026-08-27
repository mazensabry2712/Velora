<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectVeloraBrandStyles
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type');

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || ! str_contains($content, '</head>')) {
            return $response;
        }

        if (str_contains($content, '/css/velora-brand.css')) {
            return $response;
        }

        $asset = asset('css/velora-brand.css');
        $link = '<link rel="stylesheet" href="'.e($asset).'">';

        $response->setContent(str_replace('</head>', $link . "\n</head>", $content));

        return $response;
    }
}
