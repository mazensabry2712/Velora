@php
    // ── Footer link columns (data-driven so we don't repeat markup per column) ──
$footerColumns = [
    [
        'title' => __('landing.footer_product'),
        'links' => [
            ['label' => __('landing.footer_features'), 'href' => route('landing') . '#features'],
            ['label' => __('landing.footer_changelog'), 'href' => '#'],
            ['label' => __('landing.footer_roadmap'), 'href' => '#'],
        ],
    ],
    [
        'title' => __('landing.footer_company'),
        'links' => [
            ['label' => __('landing.footer_about'), 'href' => '#'],
            ['label' => __('landing.footer_blog'), 'href' => '#'],
            ['label' => __('landing.footer_careers'), 'href' => '#'],
            ['label' => __('landing.footer_contact'), 'href' => '#'],
        ],
    ],
    [
        'title' => __('landing.footer_legal'),
        'links' => [
            ['label' => __('landing.footer_privacy'), 'href' => '#'],
            ['label' => __('landing.footer_terms'), 'href' => '#'],
            ['label' => __('landing.footer_cookie'), 'href' => '#'],
            ['label' => __('landing.footer_gdpr'), 'href' => '#'],
        ],
    ],
];

// ── Social links (icon paths kept inline as they're single-use SVGs) ──
    $socialLinks = [
        [
            'name' => 'Twitter',
            'href' => '#',
            'path' =>
                'M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z',
        ],
        [
            'name' => 'LinkedIn',
            'href' => '#',
            'path' => 'M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z',
            'extra_circle' => true,
        ],
    ];

    // ── Availability flags (fixed the corrupted 🇬🇧 entry from the old markup) ──
    $availabilityFlags = ['🇬🇧', '🇸🇦', '🇫🇷', '🇪🇸', '🇩🇪', '🇧🇷', '🇯🇵', '🇨🇳', '🇹🇷', '🇮🇳', '🇰🇷', '🇳🇱', '🇮🇩'];
@endphp

<footer class="relative bg-surface overflow-hidden">
    {{-- Accent hairline instead of a flat border — matches the divider style used elsewhere on the page --}}
    <div class="h-px w-full bg-gradient-to-r from-transparent via-brand-500/40 to-transparent"></div>

    {{-- Soft ambient glow behind the brand column for depth, consistent with the hero glow --}}
    <div
        class="pointer-events-none absolute -top-24 ltr:-left-24 rtl:-right-24 w-96 h-96 rounded-full bg-brand-500/10 blur-3xl">
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-10">
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-x-8 gap-y-12 mb-14">

            {{-- Brand column --}}
            <div class="col-span-2 lg:col-span-3">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5 mb-5">
                    @if (!empty($appLogoUrl ?? ''))
                        <img src="{{ $appLogoUrl }}" alt="{{ $appName ?? 'Velora' }}" class="h-9 w-auto" />
                    @else
                        <div class="w-9 h-9 rounded-xl btn-primary flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                    <span class="text-xl font-bold gradient-text">{{ $appName ?? 'Velora' }}</span>
                </a>

                <p class="text-gray-400 text-sm leading-relaxed max-w-sm">
                    {{ __('landing.footer_tagline') }}
                </p>

                <div class="flex gap-2.5 mt-6">
                    @foreach ($socialLinks as $social)
                        <a href="{{ $social['href'] }}" aria-label="{{ $social['name'] }}"
                            class="w-9 h-9 rounded-full glass flex items-center justify-center text-gray-400 hover:text-white hover:border-brand-500/50 hover:-translate-y-0.5 transition-all duration-200">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="{{ $social['path'] }}" />
                                @if (!empty($social['extra_circle']))
                                    <circle cx="4" cy="4" r="2" />
                                @endif
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Link columns --}}
            @foreach ($footerColumns as $column)
                <div class="col-span-1 lg:col-span-1">
                    <h4 class="text-xs font-semibold text-white uppercase tracking-wider mb-4">
                        {{ $column['title'] }}
                    </h4>
                    <ul class="space-y-3 text-sm text-gray-400">
                        @foreach ($column['links'] as $link)
                            <li>
                                <a href="{{ $link['href'] }}"
                                    class="inline-block hover:text-white hover:ltr:translate-x-0.5 hover:rtl:-translate-x-0.5 transition-all duration-150">
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        {{-- Bottom bar --}}
        <div class="pt-8 border-t border-white/10 flex flex-col-reverse sm:flex-row items-center justify-between gap-5">
            <p class="text-gray-500 text-sm text-center sm:text-start">
                &copy; {{ date('Y') }} {{ $appName ?? 'Velora' }}. {{ __('landing.footer_rights') }}
            </p>

            <div class="flex items-center gap-4">
                {{-- Availability badge — shows the markets/languages the platform supports --}}
                <div class="hidden sm:flex items-center gap-2.5 glass rounded-full pl-3.5 pr-3 py-2">
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ __('landing.footer_available') }}</span>
                    <div class="flex items-center gap-1 flex-wrap max-w-[180px]">
                        @foreach ($availabilityFlags as $flag)
                            <span class="text-base leading-none">{{ $flag }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Back to top --}}
                <button type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                    aria-label="{{ __('landing.footer_back_to_top') }}"
                    class="w-9 h-9 rounded-full glass flex items-center justify-center text-gray-400 hover:text-white hover:border-brand-500/50 hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</footer>
