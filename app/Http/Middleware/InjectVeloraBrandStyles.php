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

        // Keep legacy inline brand colors aligned with the current Velora palette.
        $brandReplacements = [
            '#6C63FF' => '#6D46FF',
            '#5b4ff7' => '#006CFF',
            '#4d3de3' => '#006CFF',
            '#4032bc' => '#006CFF',
            '#362e98' => '#0D1226',
            '#211c5e' => '#080B18',
            '#f0eeff' => '#F5F7FA',
            '#e4e0ff' => '#E5E7EB',
            '#ccc5ff' => '#E5E7EB',
            '#aa9eff' => '#8A5CFF',
            '#8b76ff' => '#6D46FF',
            '#a78bfa' => '#8A5CFF',
            '#7e72ff' => '#1677FF',
            '#38bdf8' => '#00B8FF',
            '#16B8AD' => '#00B8FF',
            '#0E8F8A' => '#1677FF',
            '#075E63' => '#0D1226',
            '#053F45' => '#0D1226',
            '#022C31' => '#080B18',
        ];

        $content = str_replace(array_keys($brandReplacements), array_values($brandReplacements), $content);

        if (! str_contains($content, '/css/velora-brand.css')) {
            $asset = asset('css/velora-brand.css');
            $link = '<link rel="stylesheet" href="'.e($asset).'">';
            $content = str_replace('</head>', $link . "\n</head>", $content);
        }

        // Replace the old event-only language buttons with a native <details>
        // dropdown. No JavaScript is required, so the selector works even when
        // Alpine or any injected script is unavailable.
        $languages = config('locales.languages', []);
        $currentLocale = app()->getLocale();

        $languageOptions = '';
        foreach ($languages as $locale => $language) {
            $native = e($language['native'] ?? $locale);
            $code = e($locale);
            $direction = e($language['direction'] ?? 'ltr');
            $activeClass = $locale === $currentLocale ? ' is-active' : '';
            $href = e(route('landing.lang', ['locale' => $locale]));
            $check = $locale === $currentLocale
                ? '<span class="velora-language-check" aria-hidden="true">✓</span>'
                : '';

            $languageOptions .= '<a class="velora-language-option'.$activeClass.'" href="'.$href.'" lang="'.$code.'" dir="'.$direction.'">'
                .'<span class="velora-language-option-name">'.$native.'</span>'
                .'<span class="velora-language-option-meta"><span>'.$code.'</span>'.$check.'</span>'
                .'</a>';
        }

        $languageLabel = app()->getLocale() === 'ar' ? 'اللغة' : 'Language';
        $languageCurrent = e($languages[$currentLocale]['native'] ?? strtoupper($currentLocale));

        $desktopSwitcher = '<details class="velora-language-dropdown">'
            .'<summary class="v-icon-btn velora-language-trigger" aria-label="'.e($languageLabel).'" title="'.e($languageLabel).'">'
            .'<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">'
            .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h12M9 3v2m1.1 9.2A17.8 17.8 0 0 1 6.4 9m6.1 9 4.5-9 4.5 9m-.8-2h-7.4"/>'
            .'</svg>'
            .'<span class="velora-language-current">'.$languageCurrent.'</span>'
            .'</summary>'
            .'<div class="velora-language-dropdown-panel">'
            .'<div class="velora-language-dropdown-title">'.e($languageLabel).'</div>'
            .'<div class="velora-language-options">'.$languageOptions.'</div>'
            .'</div>'
            .'</details>';

        $desktopPattern = '~<button[^>]*onclick="window\.dispatchEvent\(new Event\(\x27velora:open-lang-switcher\x27\)\)"[^>]*>.*?</button>~s';
        $content = preg_replace($desktopPattern, $desktopSwitcher, $content, 1) ?? $content;

        $mobileSwitcher = '<details class="velora-language-dropdown velora-language-dropdown-mobile">'
            .'<summary class="v-icon-btn velora-language-trigger" aria-label="'.e($languageLabel).'" title="'.e($languageLabel).'">'
            .'<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">'
            .'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h12M9 3v2m1.1 9.2A17.8 17.8 0 0 1 6.4 9m6.1 9 4.5-9 4.5 9m-.8-2h-7.4"/>'
            .'</svg>'
            .'<span class="velora-language-current">'.$languageCurrent.'</span>'
            .'</summary>'
            .'<div class="velora-language-dropdown-panel">'
            .'<div class="velora-language-dropdown-title">'.e($languageLabel).'</div>'
            .'<div class="velora-language-options">'.$languageOptions.'</div>'
            .'</div>'
            .'</details>';

        $content = preg_replace($desktopPattern, $mobileSwitcher, $content, 1) ?? $content;

        $styles = <<<'CSS'
<style id="velora-language-dropdown-styles">
.velora-language-dropdown{position:relative}
.velora-language-trigger{list-style:none;display:flex!important;align-items:center;justify-content:center;gap:7px;cursor:pointer}
.velora-language-trigger::-webkit-details-marker{display:none}
.velora-language-trigger::marker{display:none}
.velora-language-current{font-size:11px;font-weight:800;max-width:72px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.velora-language-dropdown-panel{position:absolute;top:calc(100% + 10px);inset-inline-end:0;width:min(360px,calc(100vw - 24px));max-height:min(620px,70vh);overflow:auto;padding:14px;background:var(--v-surface,#fff);border:1px solid var(--v-line,#E5E7EB);border-radius:18px;box-shadow:0 20px 60px rgba(13,18,38,.18);z-index:1000}
.velora-language-dropdown-title{font-size:13px;font-weight:800;color:var(--v-ink,#0D1226);padding:4px 6px 10px}
.velora-language-options{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.velora-language-option{min-height:48px;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:0 11px;border:1px solid var(--v-line,#E5E7EB);border-radius:12px;background:var(--v-surface,#fff);color:var(--v-ink,#0D1226);transition:.16s}
.velora-language-option:hover{border-color:#1677FF;background:rgba(22,119,255,.06)}
.velora-language-option.is-active{border-color:#6D46FF;background:linear-gradient(135deg,rgba(109,70,255,.10),rgba(0,184,255,.08))}
.velora-language-option-name{font-size:13px;font-weight:700}
.velora-language-option-meta{display:flex;align-items:center;gap:6px;font-size:10px;font-weight:800;opacity:.52;text-transform:uppercase}
.velora-language-check{color:#00D4A3;font-size:13px;line-height:1}
.velora-language-dropdown-mobile .velora-language-dropdown-panel{inset-inline-end:0}
html[data-theme="dark"] .velora-language-dropdown-panel{background:#0D1226;border-color:#252E45;box-shadow:0 24px 70px rgba(0,0,0,.42)}
html[data-theme="dark"] .velora-language-option{background:#151C32;border-color:#252E45;color:#F8FAFC}
html[data-theme="dark"] .velora-language-option:hover{background:rgba(22,119,255,.12);border-color:#1677FF}
html[data-theme="dark"] .velora-language-option.is-active{border-color:#8A5CFF;background:linear-gradient(135deg,rgba(138,92,255,.14),rgba(0,184,255,.10))}
@media(max-width:560px){.velora-language-current{display:inline;max-width:82px}.velora-language-dropdown-panel{width:min(340px,calc(100vw - 18px));max-height:68vh}.velora-language-options{grid-template-columns:1fr}}
</style>
CSS;

        $content = str_replace('</head>', $styles . "\n</head>", $content);

        // Remove any old language-switcher script/panel that may still exist in
        // cached/generated markup from earlier versions.
        $content = preg_replace('~<style id="velora-language-switcher-styles">.*?</style>\s*<div id="velora-language-panel".*?</div>\s*<script id="velora-language-switcher-script">.*?</script>~s', '', $content) ?? $content;
        $content = preg_replace('~<script id="velora-language-switcher-script">.*?</script>~s', '', $content) ?? $content;

        $response->setContent($content);

        return $response;
    }
}
