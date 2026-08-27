<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectVeloraBrandStyles
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        $response = $next($request);
        $contentType = (string) $response->headers->get('Content-Type');

        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || ! str_contains($content, '</head>')) {
            return $response;
        }

        $brandReplacements = [
            '#6C63FF' => '#6D46FF', '#5b4ff7' => '#006CFF', '#4d3de3' => '#006CFF',
            '#4032bc' => '#006CFF', '#362e98' => '#0D1226', '#211c5e' => '#080B18',
            '#f0eeff' => '#F5F7FA', '#e4e0ff' => '#E5E7EB', '#ccc5ff' => '#E5E7EB',
            '#aa9eff' => '#8A5CFF', '#8b76ff' => '#6D46FF', '#a78bfa' => '#8A5CFF',
            '#7e72ff' => '#1677FF', '#38bdf8' => '#00B8FF', '#16B8AD' => '#00B8FF',
            '#0E8F8A' => '#1677FF', '#075E63' => '#0D1226', '#053F45' => '#0D1226',
            '#022C31' => '#080B18',
        ];
        $content = str_replace(array_keys($brandReplacements), array_values($brandReplacements), $content);

        if (! str_contains($content, '/css/velora-brand.css')) {
            $content = str_replace(
                '</head>',
                '<link rel="stylesheet" href="'.e(asset('css/velora-brand.css')).'">' . "\n</head>",
                $content
            );
        }

        $languages = config('locales.languages', []);
        $currentLocale = app()->getLocale();
        $languageOptions = '';

        foreach ($languages as $locale => $language) {
            $code = e(strtoupper($locale));
            $direction = e($language['direction'] ?? 'ltr');
            $active = $locale === $currentLocale;
            $href = e(route('landing.lang', ['locale' => $locale]));

            $languageOptions .= '<a class="velora-language-item'.($active ? ' is-active' : '').'" href="'.$href.'" lang="'.e($locale).'" dir="'.$direction.'" aria-current="'.($active ? 'true' : 'false').'">'
                .'<span class="velora-language-code">'.$code.'</span>'
                .($active ? '<span class="velora-language-check" aria-hidden="true">✓</span>' : '')
                .'</a>';
        }

        $currentCode = e(strtoupper($currentLocale));

        $switcher = '<div class="velora-language-switcher">'
            .'<button type="button" class="velora-language-trigger" aria-haspopup="true" aria-expanded="false" aria-controls="velora-language-menu">'
            .'<span class="velora-language-current">'.$currentCode.'</span>'
            .'<svg class="velora-language-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>'
            .'</button>'
            .'<div id="velora-language-menu" class="velora-language-menu" hidden>'
            .'<div class="velora-language-list">'.$languageOptions.'</div>'
            .'</div>'
            .'</div>';

        $legacyPattern = '~<button[^>]*onclick="window\\.dispatchEvent\\(new Event\\(\\x27velora:open-lang-switcher\\x27\\)\\)"[^>]*>.*?</button>~s';
        $content = preg_replace($legacyPattern, $switcher, $content) ?? $content;

        $styles = <<<'CSS'
<style id="velora-language-selector-styles">
.velora-language-switcher{position:relative;z-index:90}.velora-language-trigger{height:38px;min-width:54px;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:0 9px;border:1px solid var(--v-line,#E5E7EB);border-radius:10px;background:var(--v-surface,#fff);color:var(--v-ink,#0D1226);font:inherit;font-size:11px;font-weight:900;letter-spacing:.04em;cursor:pointer;transition:border-color .18s,box-shadow .18s,background .18s}.velora-language-trigger:hover,.velora-language-trigger[aria-expanded="true"]{border-color:#6D46FF;box-shadow:0 7px 18px rgba(109,70,255,.10)}.velora-language-arrow{opacity:.5;transition:transform .18s}.velora-language-trigger[aria-expanded="true"] .velora-language-arrow{transform:rotate(180deg)}.velora-language-menu{position:absolute;top:calc(100% + 8px);inset-inline-end:0;width:248px;padding:8px;border:1px solid var(--v-line,#E5E7EB);border-radius:14px;background:var(--v-surface,#fff);box-shadow:0 20px 50px rgba(13,18,38,.16);overflow:hidden}.velora-language-menu[hidden]{display:none}.velora-language-list{display:grid;grid-template-columns:repeat(3,1fr);gap:4px;max-height:300px;overflow:auto}.velora-language-item{height:38px;display:flex;align-items:center;justify-content:center;gap:5px;border-radius:9px;color:var(--v-ink,#0D1226);text-decoration:none;transition:background .14s,border-color .14s;color .14s}.velora-language-item:hover{background:rgba(22,119,255,.07);color:#1677FF}.velora-language-item.is-active{background:linear-gradient(135deg,rgba(109,70,255,.10),rgba(0,184,255,.07));color:#6D46FF}.velora-language-code{font-size:10px;font-weight:900;letter-spacing:.06em}.velora-language-check{width:15px;height:15px;display:grid;place-items:center;border-radius:5px;background:#00D4A3;color:#fff;font-size:9px;font-weight:900}.velora-language-list::-webkit-scrollbar{width:4px}.velora-language-list::-webkit-scrollbar-thumb{background:#D8DEEA;border-radius:99px}@media(max-width:980px){.velora-language-menu{position:fixed;top:72px;inset-inline-end:12px;width:248px}}@media(max-width:680px){.velora-language-trigger{height:38px;min-width:44px;padding:0 8px;border-radius:10px}.velora-language-arrow{display:none}.velora-language-menu{top:64px;inset-inline:8px;width:auto;padding:8px;border-radius:14px}.velora-language-list{grid-template-columns:repeat(3,1fr);gap:4px;max-height:calc(100dvh - 110px)}.velora-language-item{height:36px}.velora-language-code{font-size:9px}.velora-language-check{width:14px;height:14px;font-size:8px}}@media(max-width:340px){.velora-language-list{grid-template-columns:repeat(2,1fr)}}
html[data-theme="dark"] .velora-language-menu{background:#0D1226;border-color:#252E45;box-shadow:0 26px 70px rgba(0,0,0,.46)}html[data-theme="dark"] .velora-language-item{color:#F8FAFC}html[data-theme="dark"] .velora-language-item:hover{background:rgba(22,119,255,.12)}html[data-theme="dark"] .velora-language-item.is-active{background:linear-gradient(135deg,rgba(138,92,255,.14),rgba(0,184,255,.08));color:#B79CFF}html[data-theme="dark"] .velora-language-trigger{background:#0D1226;border-color:#252E45;color:#F8FAFC}
</style>
CSS;
        $content = str_replace('</head>', $styles."\n</head>", $content);

        $script = <<<'JS'
<script id="velora-language-selector-script">
(function(){
    function init(){
        document.querySelectorAll('.velora-language-switcher').forEach(function(wrapper){
            if(wrapper.dataset.ready==='1') return;
            wrapper.dataset.ready='1';
            const trigger=wrapper.querySelector('.velora-language-trigger');
            const menu=wrapper.querySelector('.velora-language-menu');
            function close(){menu.hidden=true;trigger.setAttribute('aria-expanded','false')}
            function open(){menu.hidden=false;trigger.setAttribute('aria-expanded','true')}
            trigger.addEventListener('click',function(e){e.stopPropagation();menu.hidden?open():close()});
            menu.addEventListener('click',function(e){e.stopPropagation()});
            document.addEventListener('click',close);
            document.addEventListener('keydown',function(e){if(e.key==='Escape')close()});
        });
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init); else init();
})();
</script>
JS;
        $content = str_replace('</body>', $script."\n</body>", $content);

        $response->setContent($content);
        return $response;
    }
}
