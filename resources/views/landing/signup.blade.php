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
        <div class="hidden lg:flex lg:w-[54%] flex-col justify-center px-12 xl:px-20 py-16 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-[500px] h-[400px] rounded-full blur-3xl pointer-events-none"
                style="background:radial-gradient(ellipse,rgba(108,99,255,0.18) 0%,transparent 65%)"></div>
            <div class="absolute bottom-10 right-0 w-72 h-72 rounded-full blur-3xl pointer-events-none"
                style="background:rgba(56,189,248,0.07)"></div>
            <div class="relative z-10 max-w-lg">
                <a href="{{ route('landing') }}" class="inline-flex items-center gap-3 mb-10 group">
                    <div class="w-10 h-10 rounded-xl btn-primary flex items-center justify-center text-lg flex-shrink-0 group-hover:scale-105 transition-transform">✦</div>
                    <span class="text-2xl font-extrabold text-white tracking-tight">{{ config('app.name', 'Velora') }}</span>
                </a>
                <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-4">
                    {{ __('landing.signup_hero_line1') }}<br>
                    <span class="gradient-text">{{ __('landing.signup_hero_line2', ['days' => $maxTrialDays]) }}</span>
                </h1>
                <p class="text-gray-400 text-lg leading-relaxed mb-10">{{ __('landing.signup_hero_sub') }}</p>
                <div class="flex flex-wrap gap-3 mb-10">
                    @foreach (['trust_no_card','trust_setup','trust_cancel'] as $trust)
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-green-400" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25)">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" /></svg>
                            {{ __('landing.' . $trust) }}
                        </span>
                    @endforeach
                </div>
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
                <div class="grid grid-cols-2 gap-3 mb-10">
                    @foreach ($benefits as $benefit)
                        <div class="flex items-center gap-3 p-3 rounded-xl border border-white/5 bg-white/[0.025]"><span class="text-xl">{{ $benefit['icon'] }}</span><span class="text-gray-300 text-sm">{{ $benefit['text'] }}</span></div>
                    @endforeach
                </div>
                <div class="pt-8 border-t border-white/5">
                    <h3 class="text-white font-bold mb-4">{{ __('landing.signup_what_happens_title') }}</h3>
                    <div class="space-y-5">
                        @foreach ([1,2,3] as $step)
                            <div class="flex items-start gap-3"><div class="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-xs font-extrabold flex-shrink-0">{{ $step }}</div><div><div class="text-white font-semibold text-sm">{{ __('landing.signup_step'.$step.'_title', ['days' => $maxTrialDays, 'grace_start' => $maxTrialDays + 1, 'grace_end' => $maxTrialDays + 3, 'readonly_start' => $maxTrialDays + 4]) }}</div><p class="text-gray-500 text-xs mt-1">{{ __('landing.signup_step'.$step.'_desc', ['days' => $maxTrialDays]) }}</p></div></div>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-8"><div class="flex -space-x-2 rtl:space-x-reverse">@foreach (['S','M','A','J','L'] as $i => $letter)<div class="w-8 h-8 rounded-full border-2 border-[#0D1226] flex items-center justify-center text-[10px] font-bold text-white" style="background:{{ ['#6D46FF','#006CFF','#00B8FF','#00D4A3','#F97316'][$i] }}">{{ $letter }}</div>@endforeach<div class="w-8 h-8 rounded-full border-2 border-[#0D1226] bg-white/10 flex items-center justify-center text-[9px] font-bold text-white">+2</div></div><span class="text-gray-500 text-xs">{{ __('landing.signup_social_proof') }}</span></div>
            </div>
        </div>

        <div class="w-full lg:w-[46%] flex flex-col justify-start px-6 sm:px-10 xl:px-16 py-10 lg:py-16 border-l rtl:border-l-0 rtl:border-r border-white/5">
            <div class="lg:hidden mb-8"><a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-white text-sm">← {{ __('landing.signup_back') }}</a></div>
            <div class="w-full max-w-xl mx-auto">
                <div class="mb-8 lg:mb-10"><h2 class="text-3xl font-extrabold text-white tracking-tight">{{ __('landing.signup_form_title') }}</h2><p class="text-gray-400 text-sm mt-2">{{ __('landing.signup_form_sub', ['days' => $maxTrialDays]) }}</p></div>
                <form id="signupForm" method="POST" action="{{ route('signup') }}" class="glass rounded-2xl overflow-hidden">
                    @csrf
                    <div class="px-7 pt-7 pb-6 border-b border-white/5">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><label class="block text-xs font-semibold text-gray-300 mb-2" for="business_name">{{ __('landing.signup_business_name') }} *</label><input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-brand-400 transition" required></div>
                            <div class="col-span-2"><label class="block text-xs font-semibold text-gray-300 mb-2">{{ __('landing.signup_business_type') }}</label><div class="grid grid-cols-4 gap-2">@php $types=['salon'=> '✂️','barber'=>'💈','clinic'=>'🏥','spa'=>'🧖','gym'=>'🏋️','restaurant'=>'🍽️','studio'=>'🎨','school'=>'🎓','other'=>'✏️']; @endphp @foreach($types as $type => $icon)<label class="cursor-pointer"><input type="radio" name="business_type" value="{{ $type }}" class="sr-only peer" {{ old('business_type','salon')===$type?'checked':'' }}><span class="min-h-[72px] h-full flex flex-col items-center justify-center gap-1 rounded-xl border border-white/10 bg-white/[0.03] text-gray-400 peer-checked:border-brand-400 peer-checked:bg-brand-500/10 peer-checked:text-white transition"><span class="text-xl">{{ $icon }}</span><span class="text-[10px]">{{ __('landing.signup_type_'.$type) }}</span></span></label>@endforeach</div></div>
                            <div><label class="block text-xs font-semibold text-gray-300 mb-2" for="slug">{{ __('landing.signup_booking_slug') }} *</label><div class="flex"><input id="slug" name="slug" type="text" value="{{ old('slug') }}" class="w-full bg-white/5 border border-white/10 rounded-l-xl rtl:rounded-l-none rtl:rounded-r-xl px-4 py-3 text-sm text-white outline-none focus:border-brand-400 transition" required><span class="px-3 flex items-center bg-white/5 border border-l-0 rtl:border-l rtl:border-r-0 border-white/10 rounded-r-xl rtl:rounded-r-none rtl:rounded-l-xl text-gray-500 text-xs">.velora.com</span></div><p class="text-[10px] text-gray-600 mt-2">{{ __('landing.signup_booking_slug_hint') }}</p></div>
                            <div><label class="block text-xs font-semibold text-gray-300 mb-2" for="email">{{ __('landing.signup_email') }} *</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-brand-400 transition" required></div>
                            <div><label class="block text-xs font-semibold text-gray-300 mb-2" for="password">{{ __('landing.signup_password') }} *</label><div class="relative"><input id="password" name="password" type="password" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 pr-12 rtl:pr-4 rtl:pl-12 text-sm text-white outline-none focus:border-brand-400 transition" required minlength="8"><button type="button" onclick="togglePassword('password','passwordIcon')" class="absolute right-0 rtl:right-auto rtl:left-0 top-0 h-full w-11 text-gray-500 hover:text-white"><span id="passwordIcon">◎</span></button></div><p class="text-[10px] text-gray-600 mt-2">{{ __('landing.signup_password_hint') }}</p></div>
                            <div><label class="block text-xs font-semibold text-gray-300 mb-2" for="password_confirmation">{{ __('landing.signup_password_confirmation') }} *</label><input id="password_confirmation" name="password_confirmation" type="password" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-brand-400 transition" required minlength="8"></div>
                            <div><label class="block text-xs font-semibold text-gray-300 mb-2" for="country">{{ __('landing.signup_country') }}</label><select id="country" name="country" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-brand-400 transition">@foreach(config('localizer.countries', []) as $code => $country)<option value="{{ $code }}" {{ old('country')===$code?'selected':'' }}>{{ $country['flag'] ?? '' }} {{ $country['name'] ?? $code }}</option>@endforeach</select></div>
                            <div><label class="block text-xs font-semibold text-gray-300 mb-2" for="admin_locale">{{ __('landing.signup_admin_locale') }}</label><select id="admin_locale" name="admin_locale" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-brand-400 transition">@foreach(config('localizer.supported_locales', ['en','ar']) as $loc)<option value="{{ $loc }}" {{ old('admin_locale', $landingLocale)===$loc?'selected':'' }}>{{ strtoupper($loc) }}</option>@endforeach</select></div>
                        </div>
                    </div>
                    <div class="px-7 py-6 border-b border-white/5"><label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" name="terms" value="1" class="mt-1 rounded" required><span class="text-xs text-gray-500 leading-relaxed">{{ __('landing.signup_terms_prefix') }} <a href="#" class="text-brand-300 hover:text-brand-200 underline">{{ __('landing.signup_terms') }}</a> {{ __('landing.signup_and') }} <a href="#" class="text-brand-300 hover:text-brand-200 underline">{{ __('landing.signup_privacy') }}</a></span></label></div>
                    <div class="px-7 py-5 border-b border-white/5"><button type="button" onclick="toggleCoupon()" class="text-xs text-gray-500 hover:text-gray-300 flex items-center gap-2"><span>◇</span> {{ __('landing.signup_coupon_question') }}</button><div id="couponRow" class="hidden mt-3"><input name="coupon" type="text" placeholder="{{ __('landing.signup_coupon_placeholder') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-brand-400 transition"></div></div>
                    <div class="px-7 py-7"><button id="submitBtn" type="submit" class="w-full py-3.5 btn-primary rounded-xl text-white font-extrabold text-sm flex items-center justify-center gap-2"><span>{{ __('landing.signup_submit') }}</span><span>→</span></button><p class="text-center text-xs text-gray-600 mt-5">{{ __('landing.signup_existing') }} <a href="{{ route('central.login') }}" class="text-brand-300 hover:text-brand-200 font-semibold">{{ __('landing.signup_login') }}</a></p><div class="mt-5 pt-5 border-t border-white/5 flex items-center gap-2 text-[10px] text-gray-600"><svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-5-8V7a5 5 0 0110 0v2m-8 0h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6a2 2 0 012-2z"/></svg><span>{{ __('landing.signup_isolated_data') }}</span></div></div>
                </form>
                @if ($errors->any())<div class="mt-4 rounded-xl border border-red-400/20 bg-red-500/10 p-4 text-sm text-red-300"><ul class="space-y-1">@foreach($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul></div>@endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function togglePassword(inputId, iconId){const i=document.getElementById(inputId),x=document.getElementById(iconId);i.type=i.type==='password'?'text':'password';x.textContent=i.type==='password'?'◎':'◉'}
function toggleCoupon(){document.getElementById('couponRow')?.classList.toggle('hidden')}
</script>
@endpush
