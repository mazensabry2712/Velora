<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('Setting up your workspace') }} · Velora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <style>
        :root { --provision-bg: var(--velora-light-gray); --provision-text: var(--velora-deep-navy); --provision-muted: var(--velora-gray); --provision-line: var(--velora-border); }
        body { background: var(--provision-bg); color: var(--provision-text); }
        .brand-gradient { background: var(--velora-gradient); }
        .brand-text { color: var(--velora-primary-blue); }
        .brand-soft { background: linear-gradient(135deg, rgba(109,70,255,.07), rgba(0,184,255,.08)); }
        .language-menu { min-width: 12rem; }
        .language-menu a[aria-current="page"] { background: rgba(22,119,255,.08); color: var(--velora-primary-blue); font-weight: 700; }
    </style>
</head>
<body class="min-h-screen antialiased">
    <main class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0 brand-soft"></div>
        <header class="relative z-20 mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-5 sm:px-8">
            <a href="{{ route('landing') }}" class="inline-flex items-center" aria-label="Velora">
                <img src="{{ asset('logo.png') }}" alt="Velora" class="h-10 w-auto object-contain sm:h-11">
            </a>
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" @keydown.escape.window="open = false" class="inline-flex items-center gap-2 rounded-xl border bg-white px-3.5 py-2.5 text-sm font-semibold shadow-sm transition hover:-translate-y-0.5" style="border-color: var(--provision-line); color: var(--provision-text);" aria-haspopup="listbox" :aria-expanded="open">
                    <svg class="h-4 w-4 brand-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M3 12h18M12 3c2.2 2.4 3.4 5.4 3.4 9s-1.2 6.6-3.4 9c-2.2-2.4-3.4-5.4-3.4-9S9.8 5.4 12 3Z"/></svg>
                    <span>{{ strtoupper(app()->getLocale()) }}</span>
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div x-cloak x-show="open" @click.outside="open = false" class="language-menu absolute end-0 mt-2 overflow-hidden rounded-2xl border bg-white p-1.5 shadow-xl" style="border-color: var(--provision-line);" role="listbox">
                    @php
                        $localeLabels = ['ar'=>'العربية','en'=>'English','fr'=>'Français','es'=>'Español','de'=>'Deutsch','it'=>'Italiano','pt'=>'Português','ru'=>'Русский','zh'=>'中文','ja'=>'日本語','tr'=>'Türkçe','hi'=>'हिन्दी','ko'=>'한국어','nl'=>'Nederlands','id'=>'Bahasa Indonesia'];
                        $supportedLocales = config('localizer.supported_locales', []);
                        $currentLocale = app()->getLocale();
                        $path = request()->getPathInfo() ?: '/';
                        foreach ($supportedLocales as $supported) {
                            $prefix = '/' . $supported;
                            if ($path === $prefix || str_starts_with($path, $prefix . '/')) { $path = substr($path, strlen($prefix)) ?: '/'; break; }
                        }
                    @endphp
                    @foreach ($supportedLocales as $locale)
                        @php $target = $locale === config('localizer.omitted_locale', 'ar') ? $path : '/' . $locale . ($path === '/' ? '' : $path); @endphp
                        <a href="{{ url($target) }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition hover:bg-slate-50" style="color: var(--provision-text);" @if ($locale === $currentLocale) aria-current="page" @endif>
                            <span>{{ $localeLabels[$locale] ?? strtoupper($locale) }}</span><span class="text-xs" style="color: var(--provision-muted);">{{ strtoupper($locale) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </header>
        <div class="relative mx-auto flex min-h-[calc(100vh-88px)] w-full max-w-7xl items-center px-5 pb-10 sm:px-8">
            <div class="grid w-full gap-10 lg:grid-cols-[1fr_520px] lg:items-center">
                <section class="hidden lg:block"><div class="max-w-2xl">
                    <span class="inline-flex items-center gap-2 rounded-full border bg-white px-4 py-2 text-sm font-semibold shadow-sm" style="border-color: var(--provision-line); color: var(--provision-text);"><span class="h-2 w-2 rounded-full brand-gradient"></span>{{ __('Almost there') }}</span>
                    <h1 class="mt-6 text-5xl font-black tracking-tight xl:text-6xl" style="color: var(--provision-text);">{{ __('We are preparing your workspace.') }}</h1>
                    <p class="mt-5 max-w-xl text-lg leading-8" style="color: var(--provision-muted);">{{ __('Your account is created. We are setting up your business workspace in the background so you can continue without waiting on a long form.') }}</p>
                    <div class="mt-9 grid max-w-2xl gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border bg-white p-4 shadow-sm" style="border-color: var(--provision-line);"><div class="text-xs font-semibold" style="color: var(--provision-muted);">01</div><div class="mt-2 font-bold">{{ __('Account') }}</div><div class="mt-1 text-xs font-semibold" style="color: var(--velora-mint);">{{ __('Created') }}</div></div>
                        <div class="rounded-2xl border bg-white p-4 shadow-sm" style="border-color: var(--provision-line);"><div class="text-xs font-semibold" style="color: var(--provision-muted);">02</div><div class="mt-2 font-bold">{{ __('Workspace') }}</div><div id="workspaceStep" class="mt-1 text-xs font-semibold brand-text">{{ __('Preparing') }}</div></div>
                        <div class="rounded-2xl border bg-white p-4 shadow-sm" style="border-color: var(--provision-line);"><div class="text-xs font-semibold" style="color: var(--provision-muted);">03</div><div class="mt-2 font-bold">{{ __('Onboarding') }}</div><div id="onboardingStep" class="mt-1 text-xs font-semibold" style="color: var(--provision-muted);">{{ __('Next') }}</div></div>
                    </div>
                </div></section>
                <section class="mx-auto w-full max-w-xl"><div class="overflow-hidden rounded-[2rem] border bg-white shadow-[0_30px_80px_-35px_rgba(13,18,38,.28)]" style="border-color: var(--provision-line);">
                    <div class="h-1.5 bg-slate-100"><div id="progress" class="h-full w-1/3 brand-gradient transition-all duration-700"></div></div>
                    <div class="p-6 sm:p-8 lg:p-10">
                        <div class="flex items-start justify-between gap-4"><div><img src="{{ asset('logo.png') }}" alt="Velora" class="mb-5 h-12 w-auto max-w-[190px] object-contain object-left rtl:object-right"><p class="text-sm font-semibold brand-text">{{ __('Your workspace') }}</p><h2 id="title" class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl" style="color: var(--provision-text);">{{ __('Setting up your workspace') }}</h2></div><span id="liveBadge" class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold" style="background: rgba(0,212,163,.10); color: var(--velora-mint);"><span class="h-2 w-2 animate-pulse rounded-full" style="background: var(--velora-mint);"></span>{{ __('Live') }}</span></div>
                        <p id="description" class="mt-4 text-sm leading-6 sm:text-base" style="color: var(--provision-muted);">{{ __('We are preparing your business workspace. This page will continue automatically when everything is ready.') }}</p>
                        <div class="mt-8 rounded-2xl border p-4 sm:p-5" style="border-color: var(--provision-line); background: var(--velora-light-gray);">
                            <div class="flex items-center gap-3"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-sm font-bold shadow-sm" style="color: var(--velora-primary-blue);">{{ mb_substr($businessName, 0, 1) ?: 'V' }}</div><div class="min-w-0"><p class="text-xs font-medium" style="color: var(--provision-muted);">{{ __('Business') }}</p><p class="truncate text-sm font-bold sm:text-base" style="color: var(--provision-text);">{{ $businessName }}</p></div></div>
                            <div class="mt-5"><div class="flex items-center justify-between text-xs font-semibold" style="color: var(--provision-muted);"><span>{{ __('Setup progress') }}</span><span id="percent">33%</span></div><div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-200"><div id="progressInner" class="h-full w-1/3 rounded-full brand-gradient transition-all duration-700"></div></div><p id="status" class="mt-3 text-sm font-medium" style="color: var(--provision-text);">{{ __('Creating your workspace...') }}</p></div>
                        </div>
                        <div id="verificationBox" class="mt-5 hidden rounded-2xl border p-5" style="border-color: rgba(22,119,255,.18); background: rgba(22,119,255,.06);"><div class="flex gap-4"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm" style="color: var(--velora-primary-blue);"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6.5 12 12l8-5.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="5" width="18" height="14" rx="2"/></svg></div><div class="min-w-0 flex-1"><p class="font-bold" style="color: var(--provision-text);">{{ __('Verify your email to continue') }}</p><p class="mt-1 text-sm leading-6" style="color: var(--provision-muted);">{{ __('Your workspace is ready. Open the verification email we sent, then this page will continue automatically.') }}</p><button id="resend" type="button" class="btn-primary mt-4 inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-bold shadow-sm disabled:cursor-not-allowed disabled:opacity-50">{{ __('Resend verification email') }}</button><p id="resendStatus" class="mt-2 text-xs font-medium" style="color: var(--provision-muted);"></p></div></div></div>
                        <div id="readyBox" class="mt-5 hidden rounded-2xl border p-5" style="border-color: rgba(0,212,163,.20); background: rgba(0,212,163,.07);"><div class="flex items-start gap-4"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white shadow-sm" style="background: var(--velora-mint);"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div><p class="font-bold" style="color: var(--provision-text);">{{ __('Everything is ready') }}</p><p class="mt-1 text-sm leading-6" style="color: var(--provision-muted);">{{ __('We are taking you to onboarding now.') }}</p></div></div></div>
                        <div id="error" class="mt-5 hidden rounded-2xl border p-5" style="border-color: rgba(255,77,141,.20); background: rgba(255,77,141,.06);"><p class="font-bold" style="color: var(--velora-pink);">{{ __('We could not finish setting up your workspace.') }}</p><p id="errorText" class="mt-1 text-sm leading-6" style="color: var(--provision-muted);"></p></div>
                        <p class="mt-6 text-center text-xs leading-5" style="color: var(--velora-gray-light);">{{ __('You can safely leave this page and return to this link later.') }}</p>
                    </div>
                </div></section>
            </div>
        </div>
    </main>
    <script>
        const statusUrl=@json($statusUrl),resendUrl=@json($resendUrl);const progress=document.getElementById('progress'),progressInner=document.getElementById('progressInner'),percent=document.getElementById('percent'),status=document.getElementById('status'),error=document.getElementById('error'),errorText=document.getElementById('errorText'),title=document.getElementById('title'),description=document.getElementById('description'),verificationBox=document.getElementById('verificationBox'),readyBox=document.getElementById('readyBox'),liveBadge=document.getElementById('liveBadge'),resend=document.getElementById('resend'),resendStatus=document.getElementById('resendStatus'),workspaceStep=document.getElementById('workspaceStep'),onboardingStep=document.getElementById('onboardingStep');const labels={queued:@json(__('Creating your workspace...')),finalizing:@json(__('Finishing your workspace setup...')),ready:@json(__('Workspace ready. Please verify your email.')),failed:@json(__('We could not finish setting up your workspace.'))};let resendCooldown=0;
        function setProgress(v){progress.style.width=v+'%';progressInner.style.width=v+'%';percent.textContent=v+'%'}
        function cooldown(s){resendCooldown=s;resend.disabled=true;const tick=()=>{resend.textContent=resendCooldown>0?@json(__('Resend available in')).replace('%s',resendCooldown)+` ${resendCooldown}s`:@json(__('Resend verification email'));if(resendCooldown<=0){resend.disabled=false;return}resendCooldown--;setTimeout(tick,1000)};tick()}
        resend.addEventListener('click',async()=>{if(resendCooldown>0)return;try{const r=await fetch(resendUrl,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':@json(csrf_token())},credentials:'same-origin'}),d=await r.json();resendStatus.textContent=d.success?@json(__('Verification email sent.')):(d.message||@json(__('Unable to resend right now.')));if(d.success)cooldown(d.cooldown||60)}catch(e){resendStatus.textContent=@json(__('Unable to resend right now.'))}});
        async function poll(){try{const r=await fetch(statusUrl,{headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store'});if(!r.ok)throw 0;const d=await r.json(),ready=d.ready===true,verified=d.email_verified===true,current=d.status||'queued';if(current==='finalizing'){setProgress(75);workspaceStep.textContent=@json(__('Finalizing'))}else if(ready){setProgress(100);workspaceStep.textContent=@json(__('Ready'));onboardingStep.textContent=@json(__('Starting'))}else{setProgress(35);workspaceStep.textContent=@json(__('Preparing'))}status.textContent=d.message||labels[current]||labels.queued;if(ready&&verified&&d.redirect_url){readyBox.classList.remove('hidden');verificationBox.classList.add('hidden');title.textContent=@json(__('Everything is ready'));description.textContent=@json(__('Your workspace is ready. Taking you to onboarding...'));liveBadge.classList.add('hidden');setTimeout(()=>location.replace(d.redirect_url),450);return}if(ready&&d.verification_required){verificationBox.classList.remove('hidden');readyBox.classList.add('hidden');title.textContent=@json(__('Verify your email to continue'));description.textContent=@json(__('Your workspace is ready. Verify your email address and we will take you to onboarding automatically.'));status.textContent=labels.ready;setProgress(100);liveBadge.classList.add('hidden');workspaceStep.textContent=@json(__('Ready'));onboardingStep.textContent=@json(__('Waiting for verification'));setTimeout(poll,2500);return}if(d.failed){error.classList.remove('hidden');errorText.textContent=d.message||labels.failed;liveBadge.classList.add('hidden');return}setTimeout(poll,1200)}catch(e){status.textContent=@json(__('Still preparing your workspace...'));setTimeout(poll,1800)}}poll();
    </script>
</body>
</html>
