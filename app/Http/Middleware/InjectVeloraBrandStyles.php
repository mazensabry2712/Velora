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

        // The navbar already emits a click event. Mount the actual language
        // panel globally so the trigger works consistently on every landing
        // page, desktop and mobile, without duplicating navbar markup.
        $languages = config('locales.languages', []);
        $currentLocale = app()->getLocale();
        $languageItems = [];

        foreach ($languages as $locale => $language) {
            $languageItems[] = [
                'code' => $locale,
                'native' => $language['native'] ?? $locale,
                'direction' => $language['direction'] ?? 'ltr',
                'url' => route('landing.lang', ['locale' => $locale]),
                'active' => $locale === $currentLocale,
            ];
        }

        $languageJson = json_encode(
            $languageItems,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        $languageSwitcher = <<<HTML
<style id="velora-language-switcher-styles">
#velora-language-panel{position:fixed;inset:0;z-index:9999;display:none;align-items:flex-start;justify-content:center;padding:92px 18px 24px;background:rgba(8,11,24,.48);backdrop-filter:blur(7px)}
#velora-language-panel.is-open{display:flex}
.velora-language-card{width:min(680px,100%);max-height:min(78vh,720px);overflow:auto;background:var(--v-surface,#fff);color:var(--v-ink,#0D1226);border:1px solid var(--v-line,#E5E7EB);border-radius:24px;box-shadow:0 24px 80px rgba(0,0,0,.22);padding:22px}
.velora-language-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}
.velora-language-title{font-size:18px;font-weight:800;letter-spacing:-.02em}
.velora-language-close{width:40px;height:40px;border:1px solid var(--v-line,#E5E7EB);border-radius:12px;background:transparent;color:inherit;cursor:pointer;font-size:22px;line-height:1}
.velora-language-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.velora-language-item{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:56px;padding:0 15px;border:1px solid var(--v-line,#E5E7EB);border-radius:14px;background:var(--v-surface,#fff);color:inherit;transition:.18s}
.velora-language-item:hover{border-color:#1677FF;transform:translateY(-1px)}
.velora-language-item.is-active{border-color:#6D46FF;background:linear-gradient(135deg,rgba(109,70,255,.09),rgba(0,184,255,.07))}
.velora-language-name{font-size:14px;font-weight:700}
.velora-language-code{font-size:11px;font-weight:800;opacity:.5;text-transform:uppercase}
@media (max-width:560px){#velora-language-panel{padding:82px 10px 14px}.velora-language-card{padding:16px;border-radius:20px}.velora-language-grid{grid-template-columns:1fr}.velora-language-item{min-height:52px}}
html[data-theme="dark"] .velora-language-card{background:#0D1226;color:#F8FAFC;border-color:#252E45}
html[data-theme="dark"] .velora-language-item{background:#151C32;border-color:#252E45}
html[data-theme="dark"] .velora-language-close{border-color:#252E45}
</style>
<div id="velora-language-panel" aria-hidden="true">
  <div class="velora-language-card" role="dialog" aria-modal="true" aria-labelledby="velora-language-title">
    <div class="velora-language-head">
      <div class="velora-language-title" id="velora-language-title">Change language</div>
      <button type="button" class="velora-language-close" id="velora-language-close" aria-label="Close">×</button>
    </div>
    <div class="velora-language-grid" id="velora-language-grid"></div>
  </div>
</div>
<script id="velora-language-switcher-script">
(function(){
    const panel=document.getElementById('velora-language-panel');
    const grid=document.getElementById('velora-language-grid');
    const close=document.getElementById('velora-language-close');
    const items={$languageJson};
    if(!panel||!grid||!close||!Array.isArray(items)){return;}
    grid.innerHTML=items.map(function(item){
        const active=item.active?' is-active':'';
        const href=String(item.url).replace(/&/g,'&amp;').replace(/"/g,'&quot;');
        return '<a class="velora-language-item'+active+'" href="'+href+'" lang="'+item.code+'" dir="'+item.direction+'">'
            +'<span class="velora-language-name">'+item.native+'</span>'
            +'<span class="velora-language-code">'+item.code+'</span>'
            +'</a>';
    }).join('');
    function openPanel(){panel.classList.add('is-open');panel.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';}
    function closePanel(){panel.classList.remove('is-open');panel.setAttribute('aria-hidden','true');document.body.style.overflow='';}
    document.addEventListener('click',function(event){
        const trigger=event.target.closest('[onclick*="velora:open-lang-switcher"], [data-velora-language-trigger]');
        if(trigger){event.preventDefault();openPanel();}
    });
    window.addEventListener('velora:open-lang-switcher',openPanel);
    close.addEventListener('click',closePanel);
    panel.addEventListener('click',function(event){if(event.target===panel){closePanel();}});
    document.addEventListener('keydown',function(event){if(event.key==='Escape'&&panel.classList.contains('is-open')){closePanel();}});
})();
</script>
HTML;

        $content = str_replace('</head>', $languageSwitcher . "\n</head>", $content);

        $response->setContent($content);

        return $response;
    }
}
