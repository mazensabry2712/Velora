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

        $switcherScript = <<<'HTML'
<script id="velora-language-switcher-script">
(function () {
    var items = __VELORA_LANGUAGE_ITEMS__;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function build() {
        if (document.getElementById('velora-language-panel')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'velora-language-switcher-styles';
        style.textContent = '\
            #velora-language-panel{position:fixed;inset:0;z-index:999999;display:none;align-items:flex-start;justify-content:center;padding:92px 16px 24px;background:rgba(8,11,24,.55);backdrop-filter:blur(7px)}\
            #velora-language-panel.is-open{display:flex}\
            .velora-language-card{width:min(720px,100%);max-height:min(80vh,720px);overflow:auto;background:var(--v-surface,#fff);color:var(--v-ink,#0D1226);border:1px solid var(--v-line,#E5E7EB);border-radius:24px;box-shadow:0 24px 80px rgba(0,0,0,.24);padding:22px}\
            .velora-language-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}\
            .velora-language-title{font-size:18px;font-weight:800;letter-spacing:-.02em}\
            .velora-language-close{width:40px;height:40px;border:1px solid var(--v-line,#E5E7EB);border-radius:12px;background:transparent;color:inherit;cursor:pointer;font-size:22px;line-height:1}\
            .velora-language-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}\
            .velora-language-item{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:56px;padding:0 15px;border:1px solid var(--v-line,#E5E7EB);border-radius:14px;background:var(--v-surface,#fff);color:inherit;transition:.18s}\
            .velora-language-item:hover{border-color:#1677FF;transform:translateY(-1px)}\
            .velora-language-item.is-active{border-color:#6D46FF;background:linear-gradient(135deg,rgba(109,70,255,.09),rgba(0,184,255,.07))}\
            .velora-language-name{font-size:14px;font-weight:700}\
            .velora-language-code{font-size:11px;font-weight:800;opacity:.5;text-transform:uppercase}\
            html[data-theme="dark"] .velora-language-card{background:#0D1226;color:#F8FAFC;border-color:#252E45}\
            html[data-theme="dark"] .velora-language-item{background:#151C32;border-color:#252E45}\
            html[data-theme="dark"] .velora-language-close{border-color:#252E45}\
            @media (max-width:560px){#velora-language-panel{padding:82px 10px 14px}.velora-language-card{padding:16px;border-radius:20px}.velora-language-grid{grid-template-columns:1fr}.velora-language-item{min-height:52px}}';
        document.head.appendChild(style);

        var panel = document.createElement('div');
        panel.id = 'velora-language-panel';
        panel.setAttribute('aria-hidden', 'true');
        panel.innerHTML = '<div class="velora-language-card" role="dialog" aria-modal="true" aria-labelledby="velora-language-title">' +
            '<div class="velora-language-head">' +
            '<div class="velora-language-title" id="velora-language-title">Change language</div>' +
            '<button type="button" class="velora-language-close" id="velora-language-close" aria-label="Close">×</button>' +
            '</div>' +
            '<div class="velora-language-grid" id="velora-language-grid"></div>' +
            '</div>';
        document.body.appendChild(panel);

        var grid = document.getElementById('velora-language-grid');
        grid.innerHTML = items.map(function (item) {
            return '<a class="velora-language-item' + (item.active ? ' is-active' : '') + '" href="' + escapeHtml(item.url) + '" lang="' + escapeHtml(item.code) + '" dir="' + escapeHtml(item.direction) + '">' +
                '<span class="velora-language-name">' + escapeHtml(item.native) + '</span>' +
                '<span class="velora-language-code">' + escapeHtml(item.code) + '</span>' +
                '</a>';
        }).join('');

        function close() {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        window.VeloraLanguageSwitcher = {
            open: function () {
                panel.classList.add('is-open');
                panel.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            },
            close: close
        };

        document.getElementById('velora-language-close').addEventListener('click', close);
        panel.addEventListener('click', function (event) {
            if (event.target === panel) close();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) close();
        });
    }

    function init() {
        build();

        document.querySelectorAll('[data-velora-language-trigger], [onclick*="velora:open-lang-switcher"]').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (window.VeloraLanguageSwitcher) window.VeloraLanguageSwitcher.open();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, {once:true});
    } else {
        init();
    }
})();
</script>
HTML;

        $switcherScript = str_replace('__VELORA_LANGUAGE_ITEMS__', $languageJson ?: '[]', $switcherScript);
        $content = str_replace('</body>', $switcherScript . "\n</body>", $content);

        // Replace the previous event-only trigger with a direct data trigger.
        $content = str_replace(
            'onclick="window.dispatchEvent(new Event(\'velora:open-lang-switcher\'))"',
            'data-velora-language-trigger="1"',
            $content
        );

        $response->setContent($content);

        return $response;
    }
}
