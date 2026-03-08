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
                        surface: '#0f0e1a',
                    },
                }
            }
        }
        // Always dark mode for login page (matches landing page)
        document.documentElement.classList.add('dark');
    </script>
    <style>
        *, body { font-family: 'Plus Jakarta Sans', 'Tajawal', 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        .gradient-text {
            background: linear-gradient(135deg, #6C63FF 0%, #aa9eff 50%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(108, 99, 255, 0.2);
        }
        .btn-brand {
            background: linear-gradient(135deg, #6C63FF 0%, #8b76ff 100%);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.45);
            transition: all 0.25s ease;
        }
        .btn-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(108, 99, 255, 0.6);
        }
        .input-field {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: #fff;
            transition: all 0.2s ease;
        }
        .input-field::placeholder { color: rgba(255,255,255,0.3); }
        .input-field:focus {
            outline: none;
            border-color: #6C63FF;
            background: rgba(108,99,255,0.1);
            box-shadow: 0 0 0 3px rgba(108,99,255,0.2);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }
        .blob { animation: float 8s ease-in-out infinite; }
        .blob-2 { animation: float 10s ease-in-out infinite 2s; }
        .blob-3 { animation: float 12s ease-in-out infinite 4s; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp 0.6s ease both; }
        .fade-up-2 { animation: fadeUp 0.6s ease 0.1s both; }
    </style>
</head>
<body class="bg-surface min-h-screen flex items-center justify-center p-4 overflow-hidden">

    <!-- Ambient Blobs (same as landing) -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="blob absolute top-[-10%] left-[-5%] w-[500px] h-[500px] rounded-full opacity-20"
             style="background: radial-gradient(circle, #6C63FF 0%, transparent 70%); filter: blur(60px)"></div>
        <div class="blob-2 absolute bottom-[-10%] right-[-5%] w-[400px] h-[400px] rounded-full opacity-15"
             style="background: radial-gradient(circle, #8b76ff 0%, transparent 70%); filter: blur(60px)"></div>
        <div class="blob-3 absolute top-[40%] left-[40%] w-[300px] h-[300px] rounded-full opacity-10"
             style="background: radial-gradient(circle, #38bdf8 0%, transparent 70%); filter: blur(50px)"></div>
        <!-- Grid overlay -->
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 40px 40px;"></div>
    </div>

    <!-- Login Card -->
    <div class="relative z-10 glass-card rounded-2xl p-8 w-full max-w-md fade-up">

        <!-- Logo/Header -->
        <div class="text-center mb-8 fade-up-2">
            <!-- Brand icon -->
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-5 btn-brand">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold gradient-text mb-1" style="font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.02em">
                Super Admin
            </h1>
            <p class="text-gray-400 text-sm mt-1">{{ __('super-admin.login_subtitle') }}</p>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-5 px-4 py-3 rounded-xl flex items-center gap-2 text-sm"
                 style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="mb-5 px-4 py-3 rounded-xl flex items-start gap-2 text-sm"
                 style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fc8181">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    @if(session('error'))
                        {{ session('error') }}
                    @endif
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('super-admin.login.post') }}" class="space-y-5">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                    {{ __('super-admin.login_email') }}
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       required autofocus
                       class="input-field w-full px-4 py-3 rounded-xl text-sm"
                       placeholder="admin@velora.app">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                    {{ __('super-admin.login_password') }}
                </label>
                <input type="password" id="password" name="password"
                       required
                       class="input-field w-full px-4 py-3 rounded-xl text-sm"
                       placeholder="••••••••">
            </div>

            <!-- Remember Me -->
            <div class="flex items-center gap-2">
                <input type="checkbox" id="remember" name="remember"
                       class="w-4 h-4 rounded border-white/20 bg-white/10 checked:bg-brand-500 focus:ring-brand-500 focus:ring-offset-0">
                <label for="remember" class="text-sm text-gray-400 cursor-pointer">
                    {{ __('super-admin.login_remember') }}
                </label>
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="btn-brand w-full text-white font-semibold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 mt-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                {{ __('super-admin.login_submit') }}
            </button>
        </form>

        <!-- Footer row -->
        <div class="mt-6 pt-5 border-t border-white/[0.07] flex items-center justify-between text-xs text-gray-500">
            <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>{{ __('super-admin.login_secure') }}</span>
            </div>
            <span>Velora © {{ date('Y') }}</span>
        </div>
    </div>

</body>
</html>
