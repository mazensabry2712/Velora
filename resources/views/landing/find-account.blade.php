@extends('layouts.landing')
@php
    $locale = app()->getLocale() ?: config('app.locale', 'ar');
    $workspace = __('landing.workspace_finder.' . $locale);
    $workspaceUi = __('landing.workspace_ui.' . $locale);
@endphp

@section('content')
<div class="relative min-h-screen pt-8 bg-surface overflow-hidden">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-24 top-24 h-96 w-96 rounded-full bg-brand-500/15 blur-3xl"></div>
        <div class="absolute right-0 top-1/3 h-[28rem] w-[28rem] rounded-full bg-violet-500/10 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(108,99,255,0.10),_transparent_35%)]"></div>
    </div>

    <div class="relative z-10 min-h-[calc(100vh-4rem)] flex items-center">
        <div class="w-full max-w-7xl mx-auto px-6 py-10 sm:px-10 sm:py-12 lg:px-16 lg:py-12">
            <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] items-center">
                <div class="max-w-xl">
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-white text-sm mb-8 transition-colors group">
                        <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform rtl:rotate-180 rtl:group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ $workspaceUi['back_to_home'] }}
                    </a>

                    <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold bg-brand-500/10 text-brand-200 border border-brand-500/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        {{ $workspaceUi['secure_sign_in'] }}
                    </span>

                    <h1 class="mt-6 text-4xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                        {{ $workspace['title'] }}
                    </h1>
                    <p class="mt-4 text-lg text-gray-400 max-w-md">
                        {{ $workspace['subtitle'] }}
                    </p>

                    <div class="mt-8 rounded-[2rem] border border-white/10 bg-white/[0.04] backdrop-blur-2xl p-8 shadow-2xl shadow-black/30">
                        <form id="findAccountForm" onsubmit="redirectToLogin(event)" class="space-y-6">
                            <div>
                                <label for="subdomain" class="block text-sm font-medium text-gray-300 mb-2">{{ $workspace['label'] }}</label>
                                <div id="subdomain-wrap" class="flex items-center rounded-2xl border border-gray-600 bg-gray-900/60 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20 overflow-hidden transition-all">
                                    <span class="pl-4 pr-1 rtl:pl-1 rtl:pr-4 text-gray-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10.5A6.5 6.5 0 114 10.5a6.5 6.5 0 0113 0z"/></svg>
                                    </span>
                                    <input type="text" id="subdomain" name="subdomain" required autofocus autocomplete="off" placeholder="{{ $workspace['placeholder'] }}" class="flex-1 bg-transparent px-3 py-3.5 text-white placeholder-gray-500 focus:outline-none text-sm" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9\-]/g,'');onSubdomainInput()">
                                    <span class="px-4 py-3.5 text-gray-500 text-sm border-s border-gray-700 whitespace-nowrap select-none">.{{ $baseDomain }}</span>
                                </div>
                                <div id="status-msg" class="hidden mt-2.5 flex items-center gap-2 text-xs font-medium"></div>
                            </div>

                            <div id="error-msg" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm"></div>

                            <button type="submit" id="submit-btn" class="w-full py-4 px-6 rounded-2xl text-white font-semibold flex items-center justify-center gap-2 group transition-all duration-200" style="background:linear-gradient(135deg,#6C63FF 0%,#8b76ff 100%);box-shadow:0 18px 50px rgba(108,99,255,.3);">
                                <span id="submit-btn-text">{{ $workspace['button'] }}</span>
                                <svg class="w-4 h-4 group-hover:translate-x-1 rtl:rotate-180 rtl:group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>

                        <div class="mt-6 pt-6 border-t border-white/10 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-gray-500">
                            <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $workspaceUi['encrypted'] }}</span>
                            <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>{{ $workspaceUi['instant_access'] }}</span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6">
                        <p class="text-gray-400 text-sm">{{ $workspace['no_account'] }}
                            <a href="{{ route('signup') }}" class="text-brand-300 hover:text-brand-200 font-medium transition-colors">{{ $workspaceUi['start_trial'] }}</a>
                        </p>
                        <span class="hidden sm:inline text-gray-700">•</span>
                        <a href="{{ route('super-admin.login') }}" class="text-gray-400 hover:text-white text-sm transition-colors">{{ $workspaceUi['super_admin'] }}</a>
                    </div>
                </div>

                <div class="hidden lg:block">
                    <div class="relative rounded-[2rem] border border-white/10 bg-white/[0.03] backdrop-blur-xl p-6 shadow-2xl shadow-black/30 animate-float">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 mb-3 px-1">{{ $workspaceUi['active_workspaces'] }}</p>
                        <div class="space-y-2.5">
                            @foreach ([['name'=>'mysalon','label'=>'The Beauty Loft','color'=>'from-pink-500 to-rose-500'],['name'=>'hairlounge','label'=>'Hair Lounge Co.','color'=>'from-indigo-500 to-violet-500'],['name'=>'spacerelax','label'=>'Relax Spa & Wellness','color'=>'from-emerald-500 to-teal-500'],['name'=>'barberking','label'=>'Barber King Studio','color'=>'from-amber-500 to-orange-500']] as $demo)
                                <div class="flex items-center gap-3 bg-gray-900/50 hover:bg-gray-900/80 border border-white/5 rounded-xl px-4 py-3 text-sm transition-colors">
                                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br {{ $demo['color'] }} flex items-center justify-center text-white text-xs font-bold shrink-0">{{ strtoupper(substr($demo['label'],0,1)) }}</span>
                                    <div class="min-w-0 flex-1"><p class="text-gray-200 font-medium truncate">{{ $demo['label'] }}</p><p class="text-gray-500 text-xs truncate">{{ $demo['name'] }}.{{ $baseDomain }}</p></div>
                                    <svg class="w-4 h-4 text-gray-600 rtl:rotate-180 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6 pt-5 border-t border-white/10 grid grid-cols-3 gap-3 text-center">
                            <div><p class="text-lg font-bold text-white">99.9%</p><p class="text-[11px] text-gray-500">{{ $workspaceUi['uptime'] }}</p></div>
                            <div><p class="text-lg font-bold text-white">24/7</p><p class="text-[11px] text-gray-500">{{ $workspaceUi['support'] }}</p></div>
                            <div><p class="text-lg font-bold text-white">30+</p><p class="text-[11px] text-gray-500">{{ $workspaceUi['countries'] }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const veloraFindAccount = {
        locale: @json($locale),
        messages: @json($workspace, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        checkUrl: @json(route('signup.check-subdomain')),
        baseDomain: @json($baseDomain),
    };

    let debounceTimer = null;

    function onSubdomainInput() {
        const input = document.getElementById('subdomain');
        const statusMsg = document.getElementById('status-msg');
        const wrap = document.getElementById('subdomain-wrap');
        const value = input.value.trim();
        clearTimeout(debounceTimer);
        wrap.classList.remove('border-emerald-500','border-amber-500');
        wrap.classList.add('border-gray-600');
        if (value.length < 3) { statusMsg.classList.add('hidden'); return; }
        statusMsg.classList.remove('hidden');
        statusMsg.className = 'mt-2.5 flex items-center gap-2 text-xs font-medium text-gray-500';
        statusMsg.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> ' + veloraFindAccount.messages.checking;
        debounceTimer = setTimeout(() => checkSubdomain(value), 450);
    }

    async function checkSubdomain(value) {
        const statusMsg = document.getElementById('status-msg');
        const wrap = document.getElementById('subdomain-wrap');
        try {
            const res = await fetch(`${veloraFindAccount.checkUrl}?subdomain=${encodeURIComponent(value)}`, {headers:{'Accept':'application/json'}, credentials:'same-origin'});
            const data = await res.json();
            const workspaceExists = data.available === false && /taken/i.test(data.message || '');
            if (workspaceExists) {
                wrap.classList.remove('border-gray-600'); wrap.classList.add('border-emerald-500');
                statusMsg.className = 'mt-2.5 flex items-center gap-2 text-xs font-medium text-emerald-400';
                statusMsg.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> ' + veloraFindAccount.messages.found;
            } else {
                wrap.classList.remove('border-gray-600'); wrap.classList.add('border-amber-500');
                statusMsg.className = 'mt-2.5 flex items-center gap-2 text-xs font-medium text-amber-400';
                statusMsg.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> ' + veloraFindAccount.messages.not_found;
            }
        } catch (_) { statusMsg.classList.add('hidden'); }
    }

    function redirectToLogin(e) {
        e.preventDefault();
        const subdomain = document.getElementById('subdomain').value.trim();
        const errorEl = document.getElementById('error-msg');
        const btn = document.getElementById('submit-btn');
        const btnText = document.getElementById('submit-btn-text');
        if (!subdomain || subdomain.length < 3) {
            errorEl.textContent = veloraFindAccount.messages.invalid;
            errorEl.classList.remove('hidden');
            return;
        }
        errorEl.classList.add('hidden'); btn.disabled = true; btn.classList.add('opacity-70'); btnText.textContent = veloraFindAccount.messages.checking;
        const scheme = window.location.protocol;
        window.location.href = scheme + '//' + subdomain + '.' + veloraFindAccount.baseDomain + '/login';
    }

    document.getElementById('subdomain').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') document.getElementById('findAccountForm').dispatchEvent(new Event('submit'));
    });
</script>
@endsection
