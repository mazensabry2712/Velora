<!DOCTYPE html>
@php $locale = app()->getLocale(); $isRtl = in_array($locale, ['ar','he','fa']); @endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('super-admin.login_page_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'Tajawal', 'Inter', 'sans-serif'] },
                    colors: {
                        brand: { 400: '#8b76ff', 500: '#6C63FF', 600: '#5b4ff7', 700: '#4d3de3' },
                    },
                }
            }
        }
        document.documentElement.classList.add('dark');
    </script>
    <style>
        *, body { font-family: 'Plus Jakarta Sans', 'Tajawal', 'Inter', sans-serif; }
        body { background: #080c1c; }
        .glass-card { background: rgba(15, 23, 42, 0.92); backdrop-filter: blur(24px); border: 1px solid rgba(148, 163, 184, 0.12); }
        .btn-brand { background: linear-gradient(135deg, #6C63FF 0%, #8b76ff 100%); box-shadow: 0 18px 60px rgba(108, 99, 255, 0.28); transition: all 0.25s ease; }
        .btn-brand:hover { transform: translateY(-2px); box-shadow: 0 24px 70px rgba(108, 99, 255, 0.35); }
        .input-field { background: rgba(255,255,255,0.06); border: 1px solid rgba(148, 163, 184, 0.18); color: #e2e8f0; }
        .input-field::placeholder { color: rgba(148, 163, 184, 0.6); }
        .input-field:focus { outline: none; border-color: #6C63FF; background: rgba(108, 99, 255, 0.14); box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.16); }
        .badge { background: rgba(99, 102, 241, 0.14); }
        .text-soft { color: rgba(203, 213, 225, 0.75); }
    </style>
</head>
<body class="min-h-screen text-slate-100 overflow-hidden">

    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-indigo-500/15 blur-3xl"></div>
        <div class="absolute right-0 top-1/4 h-96 w-96 rounded-full bg-violet-500/15 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.12),_transparent_30%)]"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-5xl">
            <div class="grid gap-10 xl:grid-cols-[1.1fr_0.9fr] items-center">
                <section class="hidden xl:flex flex-col justify-between rounded-[2rem] border border-white/10 bg-white/5 p-10 shadow-2xl shadow-black/20 backdrop-blur-xl">
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold badge text-slate-100">
                            {{ __('super-admin.login_page_title') }}
                        </span>
                        <h1 class="mt-6 text-5xl font-extrabold tracking-tight text-white">
                            {{ __('super-admin.login_heading') ?? __('super-admin.login_page_title') }}
                        </h1>
                        <p class="mt-5 max-w-xl text-base text-slate-300">
                            {{ __('super-admin.login_description') ?? 'Manage your platform, analytics, and system settings in one secure place.' }}
                        </p>
                    </div>

                    <div class="grid gap-4 text-sm text-slate-300">
                        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                            <p class="font-semibold text-white">{{ __('super-admin.login_tip_1_title') ?? 'Secure Admin Access' }}</p>
                            <p class="mt-2 text-slate-400">{{ __('super-admin.login_tip_1_desc') ?? 'Only authorized super admins can access these controls.' }}</p>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                            <p class="font-semibold text-white">{{ __('super-admin.login_tip_2_title') ?? 'Fast Control' }}</p>
                            <p class="mt-2 text-slate-400">{{ __('super-admin.login_tip_2_desc') ?? 'Login quickly and manage the platform from one dashboard.' }}</p>
                        </div>
                    </div>
                </section>

                <div class="glass-card rounded-[2rem] border border-white/10 p-8 sm:p-10 shadow-2xl shadow-black/25">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-8">
                        <div>
                            <p class="text-sm uppercase tracking-[0.24em] text-indigo-300">{{ __('super-admin.login_section_title') ?? 'Super Admin' }}</p>
                            <h2 class="mt-3 text-4xl font-bold text-white">{{ __('super-admin.login_page_title') }}</h2>
                        </div>
                        <div class="inline-flex rounded-full border border-slate-700 bg-slate-950/90 p-1">
                            <a href="{{ route('super-admin.lang', 'en') }}"
                               class="inline-flex min-w-[72px] items-center justify-center rounded-full px-4 py-2 text-sm font-semibold transition {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">EN</a>
                            <a href="{{ route('super-admin.lang', 'ar') }}"
                               class="inline-flex min-w-[72px] items-center justify-center rounded-full px-4 py-2 text-sm font-semibold transition {{ app()->getLocale() === 'ar' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">عربي</a>
                        </div>
                    </div>

                    @if(session('success') || session('error') || $errors->any())
                        <div class="space-y-3 mb-6">
                            @if(session('success'))
                                <div class="rounded-3xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-200">
                                    {{ session('success') }}
                                </div>
                            @endif
                            @if(session('error'))
                                <div class="rounded-3xl border border-rose-500/20 bg-rose-500/10 p-4 text-sm text-rose-200">
                                    {{ session('error') }}
                                </div>
                            @endif
                            @if($errors->any())
                                <div class="rounded-3xl border border-rose-500/20 bg-rose-500/10 p-4 text-sm text-rose-200">
                                    <ul class="list-disc ml-5 space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('super-admin.login.post') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-300">{{ __('super-admin.login_email') }}</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                   class="input-field mt-2 w-full rounded-3xl px-4 py-3 text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none"
                                   placeholder="admin@velora.app">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-300">{{ __('super-admin.login_password') }}</label>
                            <input type="password" id="password" name="password" required
                                   class="input-field mt-2 w-full rounded-3xl px-4 py-3 text-sm placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none"
                                   placeholder="••••••••">
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                                <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                                {{ __('super-admin.login_remember') }}
                            </label>
                            <a href="#" class="text-sm font-semibold text-indigo-300 hover:text-indigo-200">{{ __('super-admin.login_forgot') }}</a>
                        </div>

                        <button type="submit" class="btn-brand w-full rounded-3xl py-4 text-sm font-semibold text-white transition hover:-translate-y-0.5">
                            {{ __('super-admin.login_submit') }}
                        </button>
                    </form>

                    <div class="mt-8 rounded-3xl border border-white/10 bg-slate-900/80 p-5 text-sm text-slate-400">
                        <div class="flex items-center justify-between mb-3">
                            <span>{{ __('super-admin.login_secure') }}</span>
                            <span>Velora © {{ date('Y') }}</span>
                        </div>
                        <p>{{ __('super-admin.login_description_short') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
