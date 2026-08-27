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
            $content = str_replace('</head>', '<link rel="stylesheet" href="'.e(asset('css/velora-brand.css')).'">' . "\n</head>", $content);
        }

        $languages = config('locales.languages', []);
        $currentLocale = app()->getLocale();
        $languageOptions = '';

        foreach ($languages as $locale => $language) {
            $native = e($language['native'] ?? $locale);
            $name = e($language['name'] ?? $locale);
            $code = e(strtoupper($locale));
            $direction = e($language['direction'] ?? 'ltr');
            $active = $locale === $currentLocale;
            $class = $active ? ' is-active' : '';
            $href = e(route('landing.lang', ['locale' => $locale]));
            $languageOptions .= '<a class="velora-lang-option'.$class.'" href="'.$href.'" lang="'.e($locale).'" dir="'.$direction.'" aria-current="'.($active ? 'true' : 'false').'">'
                .'<span class="velora-lang-option-copy">'
                .'<span class="velora-lang-option-native">'.$native.'</span>'
                .'<span class="velora-lang-option-name">'.$name.'</span>'
                .'</span>'
                .'<span class="velora-lang-option-end"><span class="velora-lang-code">'.$code.'</span>'.($active ? '<span class="velora-lang-check">✓</span>' : '').'</span>'
                .'</a>';
        }

        $languageLabel = $currentLocale === 'ar' ? 'لغة الموقع' : 'Site language';
        $languageCurrent = e($languages[$currentLocale]['native'] ?? strtoupper($currentLocale));

        $switcher = '<div class="velora-lang-switcher">'
            .'<button type="button" class="velora-lang-trigger" aria-haspopup="dialog" aria-expanded="false" aria-controls="veloraLanguagePanel">'
            .'<span class="velora-lang-trigger-icon" aria-hidden="true"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 5h12M9 3v2m1.1 9.2A17.8 17.8 0 0 1 6.4 9m6.1 9 4.5-9 4.5 9m-.8-2h-7.4"/></svg></span>'
            .'<span class="velora-lang-trigger-label">'.$languageCurrent.'</span>'
            .'<svg class="velora-lang-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>'
            .'</button>'
            .'<div id="veloraLanguagePanel" class="velora-lang-panel" role="dialog" aria-label="'.e($languageLabel).'" hidden>'
            .'<div class="velora-lang-panel-head"><div><div class="velora-lang-panel-kicker">'.e($languageLabel).'</div><div class="velora-lang-panel-title">'.($currentLocale === 'ar' ? 'اختر لغة الموقع' : 'Choose your language').'</div></div><button type="button" class="velora-lang-close" data-velora-lang-close aria-label="Close">×</button></div>'
            .'<div class="velora-lang-search-wrap"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke-width="1.7"/><path stroke-linecap="round" stroke-width="1.7" d="m16 16 4.2 4.2"/></svg><input id="veloraLangSearch" type="search" autocomplete="off" placeholder="'.($currentLocale === 'ar' ? 'ابحث عن لغة...' : 'Search languages...').'" aria-label="Search languages"></div>'
            .'<div class="velora-lang-options" data-velora-lang-options>'.$languageOptions.'</div>'
            .'</div>'
            .'<div class="velora-lang-backdrop" data-velora-lang-backdrop hidden></div>'
            .'</div>';

        $legacyPattern = '~<button[^>]*onclick="window\\.dispatchEvent\\(new Event\\(\\x27velora:open-lang-switcher\\x27\\)\\)"[^>]*>.*?</button>~s';
        $content = preg_replace($legacyPattern, $switcher, $content) ?? $content;

        $styles = <<<'CSS'
<style id="velora-language-dropdown-styles">
.velora-lang-switcher{position:relative;z-index:80}.velora-lang-trigger{height:42px;display:inline-flex;align-items:center;gap:8px;padding:0 10px 0 12px;border:1px solid var(--v-line,#E5E7EB);border-radius:12px;background:var(--v-surface,#fff);color:var(--v-ink,#0D1226);cursor:pointer;font:inherit;font-weight:800;transition:.18s}.velora-lang-trigger:hover,.velora-lang-trigger[aria-expanded="true"]{border-color:#6D46FF;box-shadow:0 8px 22px rgba(109,70,255,.12)}.velora-lang-trigger-icon{display:grid;place-items:center;color:#1677FF}.velora-lang-trigger-label{font-size:11px;max-width:78px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.velora-lang-chevron{opacity:.55;transition:transform .18s}.velora-lang-trigger[aria-expanded="true"] .velora-lang-chevron{transform:rotate(180deg)}.velora-lang-panel{position:fixed;top:88px;inset-inline-end:clamp(12px,4vw,48px);width:min(430px,calc(100vw - 24px));max-height:min(680px,calc(100vh - 112px));padding:18px;border:1px solid var(--v-line,#E5E7EB);border-radius:22px;background:var(--v-surface,#fff);box-shadow:0 26px 80px rgba(13,18,38,.22);z-index:1001;overflow:hidden}.velora-lang-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}.velora-lang-panel-kicker{text-transform:uppercase;letter-spacing:.08em;font-size:10px;font-weight:900;color:#1677FF}.velora-lang-panel-title{margin-top:3px;font-size:18px;font-weight:900;color:var(--v-ink,#0D1226)}.velora-lang-close{width:32px;height:32px;border:1px solid var(--v-line,#E5E7EB);border-radius:10px;background:transparent;color:var(--v-muted,#6B7280);font-size:22px;line-height:1;cursor:pointer}.velora-lang-search-wrap{height:44px;display:flex;align-items:center;gap:9px;padding:0 12px;border:1px solid var(--v-line,#E5E7EB);border-radius:12px;margin-bottom:12px;color:var(--v-muted,#6B7280)}.velora-lang-search-wrap:focus-within{border-color:#1677FF;box-shadow:0 0 0 3px rgba(22,119,255,.10)}.velora-lang-search-wrap input{width:100%;border:0;outline:0;background:transparent;color:var(--v-ink,#0D1226);font:inherit;font-size:13px}.velora-lang-options{display:grid;grid-template-columns:1fr 1fr;gap:8px;max-height:calc(min(680px,100vh - 112px) - 150px);overflow:auto;padding-right:2px}.velora-lang-option{min-height:58px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 11px;border:1px solid var(--v-line,#E5E7EB);border-radius:14px;background:var(--v-surface,#fff);color:var(--v-ink,#0D1226);transition:.16s}.velora-lang-option:hover{border-color:#1677FF;background:rgba(22,119,255,.055);transform:translateY(-1px)}.velora-lang-option.is-active{border-color:#6D46FF;background:linear-gradient(135deg,rgba(109,70,255,.10),rgba(0,184,255,.075))}.velora-lang-option-copy{min-width:0;display:flex;flex-direction:column;gap:2px}.velora-lang-option-native{font-size:13px;font-weight:850;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.velora-lang-option-name{font-size:10px;color:var(--v-muted,#6B7280);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.velora-lang-option-end{display:flex;align-items:center;gap:6px;flex:none}.velora-lang-code{font-size:9px;font-weight:900;letter-spacing:.05em;opacity:.45}.velora-lang-check{display:grid;place-items:center;width:20px;height:20px;border-radius:7px;background:#00D4A3;color:#fff;font-size:12px;font-weight:900}.velora-lang-backdrop{position:fixed;inset:0;background:rgba(8,11,24,.18);backdrop-filter:blur(2px);z-index:1000}@media(max-width:980px){.velora-lang-panel{top:76px}}@media(max-width:680px){.velora-lang-trigger{width:42px;padding:0;justify-content:center}.velora-lang-trigger-label,.velora-lang-chevron{display:none}.velora-lang-panel{top:72px;inset-inline:9px;width:auto;max-height:calc(100vh - 84px);padding:14px;border-radius:18px}.velora-lang-options{grid-template-columns:1fr;max-height:calc(100vh - 220px)}}html[data-theme="dark"] .velora-lang-panel{background:#0D1226;border-color:#252E45;box-shadow:0 28px 90px rgba(0,0,0,.5)}html[data-theme="dark"] .velora-lang-option{background:#151C32;border-color:#252E45;color:#F8FAFC}html[data-theme="dark"] .velora-lang-option:hover{background:rgba(22,119,255,.12);border-color:#1677FF}.velora-lang-option[hidden]{display:none!important}
</style>
CSS;
        $content = str_replace('</head>', $styles . "\n</head>", $content);

        $script = <<<'JS'
<script id="velora-language-switcher-script">
(function(){
  const root=document;
  function init(){
    root.querySelectorAll('.velora-lang-switcher').forEach(function(wrapper){
      if(wrapper.dataset.ready==='1') return;
      wrapper.dataset.ready='1';
      const trigger=wrapper.querySelector('.velora-lang-trigger');
      const panel=wrapper.querySelector('.velora-lang-panel');
      const backdrop=wrapper.querySelector('.velora-lang-backdrop');
      const closeBtn=wrapper.querySelector('[data-velora-lang-close]');
      const search=wrapper.querySelector('#veloraLangSearch');
      const options=[...wrapper.querySelectorAll('.velora-lang-option')];
      function close(){panel.hidden=true;backdrop.hidden=true;trigger.setAttribute('aria-expanded','false');document.body.classList.remove('velora-lang-open')}
      function open(){panel.hidden=false;backdrop.hidden=false;trigger.setAttribute('aria-expanded','true');document.body.classList.add('velora-lang-open');setTimeout(()=>search&&search.focus(),30)}
      trigger.addEventListener('click',function(e){e.preventDefault();panel.hidden?open():close()});
      closeBtn&&closeBtn.addEventListener('click',close);backdrop&&backdrop.addEventListener('click',close);
      document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!panel.hidden)close()});
      search&&search.addEventListener('input',function(){const q=this.value.trim().toLocaleLowerCase();options.forEach(function(o){o.hidden=!!q&&!o.innerText.toLocaleLowerCase().includes(q)})});
    });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init); else init();
})();
</script>
JS;
        $content = str_replace('</body>', $script . "\n</body>", $content);
        $response->setContent($content);
        return $response;
    }
}
