@extends('layouts.landing')
@section('content')
    {{-- ── Full-page ambient lighting ─────────────────────────────────────── --}}
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-32 left-1/4 w-[700px] h-[500px] rounded-full blur-3xl"
            style="background:radial-gradient(ellipse,rgba(108,99,255,0.22) 0%,transparent 70%)"></div>
        <div class="absolute top-1/2 -right-48 w-[500px] h-[500px] rounded-full blur-3xl"
            style="background:rgba(56,189,248,0.06)"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] rounded-full blur-3xl"
            style="background:rgba(108,99,255,0.08)"></div>
    </div>

    <div class="relative z-10 min-h-screen flex pt-16 bg-surface">


        {{-- ══════════════════════════════════════════════════════════════════
         LEFT PANEL — Branding & Social Proof  (hidden on mobile)
    ══════════════════════════════════════════════════════════════════ --}}
        <div class="hidden lg:flex lg:w-[54%] flex-col justify-center px-12 xl:px-20 py-16 relative overflow-hidden">

            {{-- Decorative blobs inside the panel --}}
            <div class="absolute top-0 left-0 w-[500px] h-[400px] rounded-full blur-3xl pointer-events-none"
                style="background:radial-gradient(ellipse,rgba(108,99,255,0.18) 0%,transparent 65%)"></div>
            <div class="absolute bottom-10 right-0 w-72 h-72 rounded-full blur-3xl pointer-events-none"
                style="background:rgba(56,189,248,0.07)"></div>

            <div class="relative z-10 max-w-lg">

                {{-- Logo --}}
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-3 mb-10 group">
                    <div
                        class="w-10 h-10 rounded-xl btn-primary flex items-center justify-center text-lg flex-shrink-0 group-hover:scale-105 transition-transform">
                        ✦</div>
                    <span
                        class="text-2xl font-extrabold text-white tracking-tight">{{ config('app.name', 'Velora') }}</span>
                </a>

                {{-- Headline --}}
                <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-4">
                    {{ __('landing.signup_hero_line1') }}<br>
                    <span class="gradient-text">{{ __('landing.signup_hero_line2', ['days' => $maxTrialDays]) }}</span>
                </h1>
                <p class="text-gray-400 text-lg leading-relaxed mb-10">
                    {{ __('landing.signup_hero_sub') }}
                </p>

                {{-- Trust pills --}}
                <div class="flex flex-wrap gap-3 mb-10">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-green-400"
                        style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25)">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                        </svg>
                        {{ __('landing.trust_no_card') }}
                    </span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-green-400"
                        style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25)">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                        </svg>
                        {{ __('landing.trust_setup') }}
                    </span>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-green-400"
                        style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25)">
                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                        </svg>
                        {{ __('landing.trust_cancel') }}
                    </span>
                </div>

                {{-- Benefits list --}}
                @php
                    $benefits = [
                        ['icon' => '🗓️', 'text' => __('landing.signup_benefit_1')],
                        ['icon' => '📲', 'text' => __('landing.signup_benefit_2')],
                        ['icon' => '💬', 'text' => __('landing.signup_benefit_3')],
                        ['icon' => '📊', 'text' => __('landing.signup_benefit_4')],
                        ['icon' => '🌐', 'text' => __('landing.signup_benefit_5')],
                        ['icon' => '🔒', 'text' => __('landing.signup_benefit_6')],
                    ];
                @endphp
                <div class="grid grid-cols-1 gap-3 mb-10">
                    @foreach ($benefits as $b)
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-base"
                                style="background:rgba(108,99,255,0.15);border:1px solid rgba(108,99,255,0.3)">
                                {{ $b['icon'] }}</div>
                            <span class="text-gray-300 text-sm leading-snug">{{ $b['text'] }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Separator --}}
                <div class="border-t border-white/5 mb-8"></div>

                {{-- What happens next — timeline --}}
                @php
                    $graceStart = $maxTrialDays + 1;
                    $graceEnd = $maxTrialDays + 3;
                    $roDay = $maxTrialDays + 4;
                @endphp
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-5">
                    {{ __('landing.signup_what_next') }}</p>
                <div class="space-y-0">
                    {{-- Step 1 --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                style="background:rgba(108,99,255,0.2);border:1px solid rgba(108,99,255,0.5);color:#a78bfa;">
                                1</div>
                            <div class="w-px flex-1 my-1.5" style="background:rgba(108,99,255,0.25)"></div>
                        </div>
                        <div class="pb-5">
                            <span
                                class="text-xs text-brand-400 font-semibold">{{ __('landing.signup_timeline_1_label', ['days' => $maxTrialDays]) }}</span>
                            <p class="text-sm font-semibold text-white mt-0.5">{{ __('landing.signup_timeline_1_title') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                {{ __('landing.signup_timeline_1_desc') }}</p>
                        </div>
                    </div>
                    {{-- Step 2 --}}
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold"
                                style="background:rgba(251,191,36,0.15);border:1px solid rgba(251,191,36,0.4);color:#fbbf24;">
                                2</div>
                            <div class="w-px flex-1 my-1.5" style="background:rgba(251,191,36,0.2)"></div>
                        </div>
                        <div class="pb-5">
                            <span
                                class="text-xs text-yellow-400 font-semibold">{{ __('landing.signup_timeline_2_label', ['start' => $graceStart, 'end' => $graceEnd]) }}</span>
                            <p class="text-sm font-semibold text-white mt-0.5">{{ __('landing.signup_timeline_2_title') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                {{ __('landing.signup_timeline_2_desc') }}</p>
                        </div>
                    </div>
                    {{-- Step 3 --}}
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                style="background:rgba(156,163,175,0.1);border:1px solid rgba(156,163,175,0.3);color:#9ca3af;">
                                3</div>
                        </div>
                        <div>
                            <span
                                class="text-xs text-gray-500 font-semibold">{{ __('landing.signup_timeline_3_label', ['day' => $roDay]) }}</span>
                            <p class="text-sm font-semibold text-white mt-0.5">{{ __('landing.signup_timeline_3_title') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                {{ __('landing.signup_timeline_3_desc') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Social proof --}}
                <div class="mt-8 flex items-center gap-3">
                    <div class="flex -space-x-2">
                        @foreach (['S', 'M', 'A', 'J', 'L'] as $l)
                            <div
                                class="w-9 h-9 rounded-full btn-primary flex items-center justify-center text-xs font-bold text-white border-2 border-surface">
                                {{ $l }}</div>
                        @endforeach
                    </div>
                    <p class="text-sm text-gray-400">
                        <span class="text-white font-semibold">{{ __('landing.signup_social_count') }}</span>
                        {{ __('landing.signup_social_text') }}
                    </p>
                </div>

            </div>
        </div>{{-- /left panel --}}

        {{-- ══════════════════════════════════════════════════════════════════
         RIGHT PANEL — Signup Form
    ══════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col justify-center px-5 py-12 sm:px-10 lg:px-12 xl:px-16 min-h-screen overflow-y-auto"
            style="border-left:1px solid rgba(255,255,255,0.05)">
            <div class="w-full max-w-md mx-auto">

                {{-- Back link (mobile only — desktop has logo in left panel) --}}
                <a href="{{ route('landing') }}"
                    class="lg:hidden inline-flex items-center gap-2 text-gray-500 hover:text-white text-sm mb-6 transition-colors group">
                    <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('landing.back_to_home') }}
                </a>

                {{-- ── Main form card ──────────────────────────────────────── --}}
                <div class="glass rounded-3xl border border-brand-500/30 overflow-hidden"
                    style="box-shadow:0 0 80px rgba(108,99,255,0.12),0 0 0 1px rgba(108,99,255,0.08)">

                    {{-- Card header --}}
                    <div class="px-7 pt-7 pb-6 border-b border-white/5"
                        style="background:linear-gradient(135deg,rgba(108,99,255,0.1) 0%,rgba(108,99,255,0.03) 100%)">
                        <div class="flex items-center gap-3 mb-2">
                            <div
                                class="w-10 h-10 rounded-2xl btn-primary flex items-center justify-center text-lg flex-shrink-0">
                                🚀</div>
                            <div>
                                <h2 class="text-xl font-extrabold text-white leading-snug">
                                    {{ __('landing.signup_title') }}
                                </h2>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ __('landing.signup_sub', ['days' => $maxTrialDays]) }}</p>
                            </div>
                        </div>
                        {{-- Mobile trust pills --}}
                        <div class="flex flex-wrap gap-x-3 gap-y-1.5 mt-3 lg:hidden">
                            <span class="flex items-center gap-1 text-xs text-green-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                </svg>
                                {{ __('landing.trust_no_card') }}
                            </span>
                            <span class="flex items-center gap-1 text-xs text-green-400">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" />
                                </svg>
                                {{ __('landing.trust_cancel') }}
                            </span>
                        </div>
                    </div>

                    {{-- Card body --}}
                    <div class="px-7 py-7">

                        {{-- Error Alert --}}
                        @if ($errors->has('general'))
                            <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
                                {{ $errors->first('general') }}
                            </div>
                        @endif

                        <form id="signupForm" action="{{ route('signup.store') }}" method="POST" class="space-y-5"
                            novalidate>
                            @csrf
                            <input type="hidden" name="plan_id"
                                value="{{ request('plan', old('plan_id', $plans->first()?->id)) }}">

                            {{-- Business Name --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ __('landing.business_name') }} <span class="text-red-400 normal-case">*</span>
                                </label>
                                <input type="text" name="business_name" value="{{ old('business_name') }}"
                                    placeholder="e.g. My Beauty Salon" required
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-all text-sm" />
                                @error('business_name')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Business Type — icon card grid --}}
                            <div x-data="{
                                selected: '{{ old('business_type', '') }}',
                                custom: '{{ old('business_type_custom', '') }}',
                                pick(val) {
                                    this.selected = (this.selected === val) ? '' : val;
                                    if (val !== 'other') this.custom = '';
                                }
                            }">
                                <label
                                    class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ __('landing.signup_business_type') }}</label>

                                {{-- Hidden inputs that get submitted --}}
                                <input type="hidden" name="business_type"
                                    :value="selected === 'other' && custom.trim() ? custom.trim() : selected">

                                <div class="grid grid-cols-4 gap-2">
                                    @php
                                        $btypes = [
                                            ['val' => 'salon', 'icon' => '✂️', 'key' => 'btype_salon'],
                                            ['val' => 'barbershop', 'icon' => '💈', 'key' => 'btype_barbershop'],
                                            ['val' => 'clinic', 'icon' => '🏥', 'key' => 'btype_clinic'],
                                            ['val' => 'spa', 'icon' => '🧖', 'key' => 'btype_spa'],
                                            ['val' => 'gym', 'icon' => '🏋️', 'key' => 'btype_gym'],
                                            ['val' => 'restaurant', 'icon' => '🍽️', 'key' => 'btype_restaurant'],
                                            ['val' => 'studio', 'icon' => '🎨', 'key' => 'btype_studio'],
                                            ['val' => 'school', 'icon' => '🎓', 'key' => 'btype_school'],
                                        ];
                                    @endphp
                                    @foreach ($btypes as $bt)
                                        <button type="button" @click="pick('{{ $bt['val'] }}')"
                                            :class="selected === '{{ $bt['val'] }}' ?
                                                'border-brand-500 bg-brand-500/10 text-white' :
                                                'border-white/10 bg-white/5 text-gray-400 hover:border-brand-500/40 hover:text-gray-200'"
                                            class="flex flex-col items-center justify-center gap-1 min-h-[72px] rounded-xl py-3 px-1.5 border transition-all cursor-pointer overflow-hidden">
                                            <span class="text-xl leading-none">{{ $bt['icon'] }}</span>
                                            <span
                                                class="text-[10px] leading-tight text-center line-clamp-2 break-words">{{ __('landing.' . $bt['key']) }}</span>
                                        </button>
                                    @endforeach

                                    {{-- Other / custom --}}
                                    <button type="button" @click="pick('other')"
                                        :class="selected === 'other' ? 'border-brand-500 bg-brand-500/10 text-white' :
                                            'border-white/10 bg-white/5 text-gray-400 hover:border-brand-500/40 hover:text-gray-200'"
                                        class="flex flex-col items-center justify-center gap-1 min-h-[72px] rounded-xl py-3 px-1.5 border transition-all cursor-pointer col-span-1 overflow-hidden">
                                        <span class="text-xl leading-none">✏️</span>
                                        <span
                                            class="text-[10px] leading-tight text-center line-clamp-2 break-words">{{ __('landing.btype_other') }}</span>
                                    </button>
                                </div>

                                {{-- Free-text when Other is selected --}}
                                <div x-show="selected === 'other'" x-transition class="mt-2">
                                    <input type="text" name="business_type_custom" x-model="custom"
                                        placeholder="{{ __('landing.btype_other_ph') }}" maxlength="60"
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-all text-sm" />
                                </div>

                                @error('business_type')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Subdomain / Booking URL --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ __('landing.booking_url') }} <span class="text-red-400 normal-case">*</span>
                                </label>
                                <div
                                    class="flex rounded-xl overflow-hidden border border-white/10 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500/50 transition-all">
                                    <input type="text" name="subdomain" id="subdomain"
                                        value="{{ old('subdomain') }}" placeholder="mysalon" required maxlength="32"
                                        pattern="[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]"
                                        class="flex-1 bg-white/5 px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none text-sm font-mono min-w-0" />
                                    <span
                                        class="bg-white/[0.03] px-3 py-3 text-gray-500 text-xs font-mono border-l border-white/10 flex-shrink-0 flex items-center">
                                        .velora.com
                                    </span>
                                </div>
                                <div id="subdomainStatus" class="mt-1.5 text-xs hidden"></div>
                                <p class="mt-1 text-xs text-gray-600">{{ __('landing.subdomain_hint') }}</p>
                                @error('subdomain')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                    {{ __('landing.email_address') }} <span class="text-red-400 normal-case">*</span>
                                </label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    placeholder="you@example.com" required
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-all text-sm" />
                                @error('email')
                                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Password Row --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                        {{ __('landing.password') }} <span class="text-red-400 normal-case">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" name="password" id="password"
                                            placeholder="{{ __('landing.password_min') }}" required minlength="8"
                                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 pr-10 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-all text-sm" />
                                        <button type="button" onclick="togglePassword('password')"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-300 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">
                                        {{ __('landing.confirm_password') }} <span
                                            class="text-red-400 normal-case">*</span>
                                    </label>
                                    <input type="password" name="password_confirmation"
                                        placeholder="{{ __('landing.repeat_password') }}" required
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-all text-sm" />
                                </div>
                            </div>

                            {{-- Country + Language Row --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ __('landing.country') }}</label>
                                    <div class="relative">
                                        <select name="country"
                                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-gray-300 focus:outline-none focus:border-brand-500 transition-all text-sm appearance-none">
                                            <option value="">{{ __('landing.select_country') }}</option>
                                            <option value="SA" {{ old('country') == 'SA' ? 'selected' : '' }}>🇸🇦
                                                Saudi Arabia</option>
                                            <option value="AE" {{ old('country') == 'AE' ? 'selected' : '' }}>🇦🇪 UAE
                                            </option>
                                            <option value="EG" {{ old('country') == 'EG' ? 'selected' : '' }}>🇪🇬
                                                Egypt</option>
                                            <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>🇺🇸
                                                United States</option>
                                            <option value="GB" {{ old('country') == 'GB' ? 'selected' : '' }}>🇬🇧
                                                United Kingdom</option>
                                            <option value="FR" {{ old('country') == 'FR' ? 'selected' : '' }}>🇫🇷
                                                France</option>
                                            <option value="DE" {{ old('country') == 'DE' ? 'selected' : '' }}>🇩🇪
                                                Germany</option>
                                            <option value="ES" {{ old('country') == 'ES' ? 'selected' : '' }}>🇪🇸
                                                Spain</option>
                                            <option value="BR" {{ old('country') == 'BR' ? 'selected' : '' }}>🇧🇷
                                                Brazil</option>
                                            <option value="JP" {{ old('country') == 'JP' ? 'selected' : '' }}>🇯🇵
                                                Japan</option>
                                            <option value="CN" {{ old('country') == 'CN' ? 'selected' : '' }}>🇨🇳
                                                China</option>
                                            <option value="RU" {{ old('country') == 'RU' ? 'selected' : '' }}>🇷🇺
                                                Russia</option>
                                            <option value="IT" {{ old('country') == 'IT' ? 'selected' : '' }}>🇮🇹
                                                Italy</option>
                                            <option value="TR" {{ old('country') == 'TR' ? 'selected' : '' }}>🇹🇷
                                                Turkey</option>
                                            <option value="IN" {{ old('country') == 'IN' ? 'selected' : '' }}>🇮🇳
                                                India</option>
                                            <option value="KR" {{ old('country') == 'KR' ? 'selected' : '' }}>🇰🇷
                                                South Korea</option>
                                            <option value="NL" {{ old('country') == 'NL' ? 'selected' : '' }}>🇳🇱
                                                Netherlands</option>
                                            <option value="ID" {{ old('country') == 'ID' ? 'selected' : '' }}>🇮🇩
                                                Indonesia</option>
                                        </select>
                                        <div
                                            class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1.5">{{ __('landing.dashboard_language') }}</label>
                                    <div class="relative">
                                        <select name="language"
                                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-gray-300 focus:outline-none focus:border-brand-500 transition-all text-sm appearance-none">
                                            <option value="en" {{ old('language', 'en') == 'en' ? 'selected' : '' }}>
                                                🇺🇸 English</option>
                                            <option value="ar" {{ old('language') == 'ar' ? 'selected' : '' }}>🇸🇦
                                                Arabic</option>
                                            <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>🇫🇷
                                                French</option>
                                            <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>🇪🇸
                                                Spanish</option>
                                            <option value="de" {{ old('language') == 'de' ? 'selected' : '' }}>🇩🇪
                                                German</option>
                                            <option value="it" {{ old('language') == 'it' ? 'selected' : '' }}>🇮🇹
                                                Italian</option>
                                            <option value="pt" {{ old('language') == 'pt' ? 'selected' : '' }}>🇧🇷
                                                Portuguese</option>
                                            <option value="ru" {{ old('language') == 'ru' ? 'selected' : '' }}>🇷🇺
                                                Russian</option>
                                            <option value="zh" {{ old('language') == 'zh' ? 'selected' : '' }}>🇨🇳
                                                Chinese</option>
                                            <option value="ja" {{ old('language') == 'ja' ? 'selected' : '' }}>🇯🇵
                                                Japanese</option>
                                            <option value="tr" {{ old('language') == 'tr' ? 'selected' : '' }}>🇹🇷
                                                Turkish</option>
                                            <option value="hi" {{ old('language') == 'hi' ? 'selected' : '' }}>🇮🇳
                                                Hindi</option>
                                            <option value="ko" {{ old('language') == 'ko' ? 'selected' : '' }}>🇰🇷
                                                Korean</option>
                                            <option value="nl" {{ old('language') == 'nl' ? 'selected' : '' }}>🇳🇱
                                                Dutch</option>
                                            <option value="id" {{ old('language') == 'id' ? 'selected' : '' }}>🇮🇩
                                                Indonesian</option>
                                        </select>
                                        <div
                                            class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-600">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Terms --}}
                            <div class="flex items-start gap-3 pt-1">
                                <input type="checkbox" name="terms" id="terms" value="1" required
                                    class="mt-0.5 w-4 h-4 rounded border-white/20 bg-white/5 text-brand-500 focus:ring-brand-500 focus:ring-offset-0 cursor-pointer flex-shrink-0" />
                                <label for="terms" class="text-xs text-gray-500 leading-relaxed cursor-pointer">
                                    {{ __('landing.terms_agree') }}
                                    <a href="#"
                                        class="text-brand-400 hover:text-brand-300 underline">{{ __('landing.terms_of_service') }}</a>
                                    {{ __('landing.terms_agree_and') }}
                                    <a href="#"
                                        class="text-brand-400 hover:text-brand-300 underline">{{ __('landing.privacy_policy') }}</a>
                                </label>
                            </div>
                            @error('terms')
                                <p class="text-xs text-red-400 -mt-3">{{ $message }}</p>
                            @enderror

                            {{-- Promo Code (collapsible) --}}
                            <div x-data="{ open: {{ old('promo_code') ? 'true' : 'false' }} }">
                                <button type="button" @click="open = !open"
                                    class="text-xs text-brand-400 hover:text-brand-300 transition-colors flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    {{ __('landing.signup_have_promo') }}
                                </button>
                                <div x-show="open" x-transition class="mt-2">
                                    <input type="text" name="promo_code" value="{{ old('promo_code') }}"
                                        placeholder="{{ __('landing.signup_promo_ph') }}" maxlength="32"
                                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500/50 transition-all text-sm uppercase tracking-widest" />
                                    @error('promo_code')
                                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Submit --}}
                            <button type="submit" id="submitBtn"
                                class="btn-primary w-full text-white font-bold text-sm px-6 py-4 rounded-xl flex items-center justify-center gap-2.5 transition-all mt-1">
                                <span id="btnText">{{ __('landing.create_account_btn') }}</span>
                                <svg id="btnSpinner" class="w-4 h-4 animate-spin hidden" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </button>

                        </form>
                    </div>{{-- /card body --}}

                    {{-- Card footer --}}
                    <div class="px-7 py-4 border-t border-white/5 text-center" style="background:rgba(255,255,255,0.02)">
                        <p class="text-sm text-gray-500">
                            {{ __('landing.already_have_account') }}
                            <a href="{{ route('central.login') }}"
                                class="text-brand-400 hover:text-brand-300 font-semibold transition-colors">{{ __('landing.sign_in') }}</a>
                        </p>
                    </div>

                </div>{{-- /main form card --}}

                {{-- Security micro-copy --}}
                <div class="mt-5 flex items-center justify-center gap-2 text-xs text-gray-600">
                    <svg class="w-3.5 h-3.5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    {{ __('landing.signup_isolation_1') }}
                    <strong class="text-gray-500">{{ __('landing.signup_isolation_2') }}</strong>
                    {{ __('landing.signup_isolation_3') }}
                </div>

            </div>
        </div>{{-- /right panel --}}

    </div>
@endsection

@push('scripts')
    <script>
        // Subdomain live check
        let subdomainTimer;
        const subdomainInput = document.getElementById('subdomain');
        const statusEl = document.getElementById('subdomainStatus');

        subdomainInput?.addEventListener('input', function() {
            const val = this.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
            this.value = val;

            clearTimeout(subdomainTimer);
            if (val.length < 3) {
                statusEl.className = 'mt-1.5 text-xs hidden';
                return;
            }

            statusEl.className = 'mt-1.5 text-xs text-gray-400';
            statusEl.textContent = 'Checking availability...';

            subdomainTimer = setTimeout(() => {
                fetch(`/signup/check-subdomain?subdomain=${encodeURIComponent(val)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || ''
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        statusEl.className =
                            `mt-1.5 text-xs ${data.available ? 'text-green-400' : 'text-red-400'}`;
                        statusEl.textContent = (data.available ? '✅ ' : '❌ ') + data.message;
                    })
                    .catch(() => {
                        statusEl.className = 'mt-1.5 text-xs hidden';
                    });
            }, 500);
        });

        // Auto-fill subdomain from business name
        document.querySelector('[name="business_name"]')?.addEventListener('input', function() {
            if (subdomainInput && !subdomainInput.dataset.touched) {
                const slug = this.value.toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^a-z0-9\-]/g, '')
                    .replace(/^-|-$/g, '')
                    .substring(0, 32);
                subdomainInput.value = slug;
                subdomainInput.dispatchEvent(new Event('input'));
            }
        });

        subdomainInput?.addEventListener('input', () => {
            subdomainInput.dataset.touched = '1';
        });
        subdomainInput?.addEventListener('focus', () => {
            subdomainInput.dataset.touched = '1';
        });

        // Password toggle
        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        // Loading state
        document.getElementById('signupForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');
            btn.disabled = true;
            btnText.textContent = '{{ __('landing.creating_account') }}';
            spinner.classList.remove('hidden');
        });
    </script>
@endpush
