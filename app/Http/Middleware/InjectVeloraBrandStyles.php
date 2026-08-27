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

        // Keep inline landing-page theme config and metadata consistent with
        // the same palette used by the global Velora brand stylesheet.
        $brandReplacements = [
            '#6C63FF' => '#16B8AD',
            '#5b4ff7' => '#0E8F8A',
            '#4d3de3' => '#075E63',
            '#4032bc' => '#075E63',
            '#362e98' => '#053F45',
            '#211c5e' => '#022C31',
            '#f0eeff' => '#E8FBFA',
            '#e4e0ff' => '#D5F5F3',
            '#ccc5ff' => '#B5E8E4',
            '#aa9eff' => '#7BD7D0',
            '#8b76ff' => '#22C7BD',
            '#a78bfa' => '#0E8F8A',
            '#7e72ff' => '#22C7BD',
            '#38bdf8' => '#16B8AD',
        ];

        $content = str_replace(array_keys($brandReplacements), array_values($brandReplacements), $content);

        if (! str_contains($content, '/css/velora-brand.css')) {
            $asset = asset('css/velora-brand.css');
            $link = '<link rel="stylesheet" href="'.e($asset).'">';
            $content = str_replace('</head>', $link . "\n</head>", $content);
        }

        $response->setContent($content);

        return $response;
    }
}
