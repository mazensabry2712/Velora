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
            $native = e($language['native'] ?? $locale);
            $direction = e($language['direction'] ?? 'ltr');
            $active = $locale === $currentLocale;
            $href = e(route('landing.lang', ['locale' => $locale]));

            $languageOptions .= '<a class="velora-language-item'.($active ? ' is-active' : '').'" href="'.$href.'" lang="'.e($locale).'" dir="'.$direction.'" aria-current="'.($active ? 'true' : 'false').'">'
                .'<span class="velora-language-name">'.$native.'</span>'
                .'<span class="velora-language-code">'.e(strtoupper($locale)).'</span>'
                .($active ? '<span class="velora-language-check" aria-hidden="true">✓</span>' : '')
                .'</a>';
        }

        $languageCurrent = e($languages[$currentLocale]['native'] ?? strtoupper($currentLocale));

        $switcher = '<div class="velora-language-switcher">'
            .'<button type="button" class="velora-language-trigger" aria-haspopup="true" aria-expanded="false" aria-controls="velora-language-menu">'
            .'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M3.7 9h16.6M3.7 15h16.6M12 3.5c2.1 2.3 3.1 5.1 3.1 8.5s-1 6.2-3.1 8.5c-2.1-2.3-3.1-5.1-3.1-8.5S9.9 5.8 12 3.5Z"/></svg>'
            .'<span class="velora-language-current">'.$languageCurrent.'</span>'
            .'<svg class="velora-language-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>'
            .'</button>'
            .'<div id="velora-language-menu" class="velora-language-menu" hidden>'
            .'<div class="velora-language-menu-title">'.($currentLocale === 'ar' ? 'اختر لغة الموقع' : 'Choose language').'</div>'
            .'<div class="velora-language-list">'.$languageOptions.'</div>'
            .'</div>'
            .'</div>';

        $legacyPattern = '~<button[^>]*onclick="window\\.dispatchEvent\\(new Event\\(\\x27velora:open-lang-switcher\\x27\\)\\)"[^>]*>.*?</button>~s';
        $content = preg_replace($legacyPattern, $switcher, $content) ?? $content;

        $styles = <<<'CSS'
<style id="velora-language-selector-styles">
.velora-language-switcher{position:relative;z-index:90}.velora-language-trigger{height:42px;display:inline-flex;align-items:center;gap:7px;padding:0 10px 0 11px;border:1px solid var(--v-line,#E5E7EB);border-radius:12px;background:var(--v-surface,#fff);color:var(--v-ink,#0D1226);font:inherit;font-size:11px;font-weight:800;cursor:pointer;transition:border-color .18s,box-shadow .18s,background .18s}.velora-language-trigger:hover,.velora-language-trigger[aria-expanded="true"]{border-color:#6D46FF;box-shadow:0 7px 20px rgba(109,70,255,.10)}.velora-language-trigger svg:first-child{color:#1677FF;flex:none}.velora-language-current{max-width:84px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.velora-language-arrow{opacity:.55;transition:transform .18s}.velora-language-trigger[aria-expanded="true"] .velora-language-arrow{transform:rotate(180deg)}.velora-language-menu{position:absolute;top:calc(100% + 9px);inset-inline-end:0;width:300px;padding:10px;border:1px solid var(--v-line,#E5E7EB);border-radius:16px;background:var(--v-surface,#fff);box-shadow:0 22px 55px rgba(13,18,38,.16)}.velora-language-menu[hidden]{display:none}.velora-language-menu-title{padding:7px 8px 9px;color:var(--v-ink,#0D1226);font-size:12px;font-weight:900}.velora-language-list{display:grid;grid-template-columns:1fr 1fr;gap:4px;max-height:340px;overflow-y:auto}.velora-language-item{min-width:0;min-height:42px;display:flex;align-items:center;gap:7px;padding:0 9px;border-radius:10px;color:var(--v-ink,#0D1226);text-decoration:none;transition:background .15s,color .15s}.velora-language-item:hover{background:rgba(22,119,255,.07);color:#1677FF}.velora-language-item.is-active{background:linear-gradient(135deg,rgba(109,70,255,.10),rgba(0,108,255,.06));color:#6D46FF}.velora-language-name{min-width:0;flex:1;font-size:11px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.velora-language-code{font-size:8px;font-weight:900;opacity:.42;letter-spacing:.04em}.velora-language-check{width:17px;height:17px;display:grid;place-items:center;flex:none;border-radius:6px;background:#00D4A3;color:#fff;font-size:10px;font-weight:900}.velora-language-menu::-webkit-scrollbar{width:5px}.velora-language-menu::-webkit-scrollbar-thumb{background:#D8DEEA;border-radius:99px}@media(max-width:980px){.velora-language-menu{position:fixed;top:76px;inset-inline-end:12px;width:min(320px,calc(100vw - 24px));box-shadow:0 22px 60px rgba(13,18,38,.22)}}@media(max-width:680px){.velora-language-trigger{width:40px;height:40px;padding:0;justify-content:center;border-radius:11px}.velora-language-current,.velora-language-arrow{display:none}.velora-language-menu{top:68px;inset-inline:8px;width:auto;padding:9px;border-radius:16px}.velora-language-menu-title{padding:7px 8px 8px;font-size:11px}.velora-language-list{grid-template-columns:1fr 1fr;gap:3px;max-height:calc(100dvh - 145px)}.velora-language-item{min-height:40px;padding:0 8px;border-radius:9px}.velora-language-name{font-size:10px}.velora-language-code{font-size:7px}.velora-language-check{width:16px;height:16px;font-size:9px}}@media(max-width:340px){.velora-language-list{grid-template-columns:1fr}.velora-language-menu{inset-inline:6px}.velora-language-item{min-height:38px}}
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
