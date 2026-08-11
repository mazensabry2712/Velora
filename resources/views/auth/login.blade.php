<!DOCTYPE html>
@php
    $isArabic = app()->getLocale() === 'ar';
    $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $businessLogo = $businessSettings?->logo ?? null;
    $businessName = $businessSettings?->business_name ?? null;
    if (is_array($businessName)) {
        $locale = app()->getLocale();
        $businessName = $businessName[$locale] ?? ($businessName['en'] ?? (reset($businessName) ?? null));
    }
    $businessName = is_scalar($businessName) ? (string) $businessName : null;
    $displayName = $businessName ?: tenant()->name;
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isArabic ? 'تسجيل الدخول' : 'Login' }} - {{ tenant()->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/dark-mode-enhancements.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <style>
        /* .btn-brand is referenced on the submit button below but was never
           defined for this page (it only existed in super-admin/login.blade.php),
           leaving the white-on-white button invisible. Defining it here. */
        .btn-brand {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.35);
        }
        .btn-brand:hover {
            box-shadow: 0 16px 36px rgba(79, 70, 229, 0.45);
        }
    </style>
    <!-- Dark Mode Prevention Script - يمنع وميض الوضع الفاتح -->
    <script>
        // يتم تنفيذ هذا الكود فوراً قبل عرض الصفحة
        (function() {
            if (localStorage.getItem('darkMode') === 'true' ||
                (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-indigo-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-800 min-h-screen text-slate-900 dark:text-white overflow-hidden">

    <!-- Ambient background glow -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute top-[-10%] left-[-5%] w-[480px] h-[480px] rounded-full bg-indigo-400/20 blur-3xl"></div>
        <div class="absolute bottom-[-10%] right-[-5%] w-[420px] h-[420px] rounded-full bg-cyan-400/15 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(99,102,241,0.08),_transparent_45%)]"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-6xl">
            <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] items-center">

                <section class="hidden lg:flex flex-col justify-center rounded-[2rem] bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-white/10 p-10 shadow-2xl shadow-slate-900/10 backdrop-blur-xl">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">
                        {{ $isArabic ? 'مرحباً بك في فيلورا' : 'Welcome to Velora' }}
                    </span>
                    <h1 class="mt-6 text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight">
                        {{ $isArabic ? 'ادخل إلى لوحة التحكم بسرعة وسهولة' : 'Access your dashboard with speed and clarity' }}
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-300 max-w-xl">
                        {{ $isArabic ? 'منصة فيلورا تسهل لك إدارة الحجوزات، العملاء، والمواعيد من مكان واحد.' : 'Velora helps you manage bookings, customers, and schedules from one polished dashboard.' }}
                    </p>

                    <div class="mt-10 grid gap-4 text-sm text-slate-600 dark:text-slate-300">
                        <p class="inline-flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-200">✓</span>
                            {{ $isArabic ? 'واجهة أنيقة ومناسبة لجميع الأجهزة' : 'Beautiful, responsive layout on every device' }}
                        </p>
                        <p class="inline-flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-200">✓</span>
                            {{ $isArabic ? 'تحكم كامل للشركات والمشرفين' : 'Clear access for company and super admins' }}
                        </p>
                        <p class="inline-flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-200">✓</span>
                            {{ $isArabic ? 'دعم اللغة العربية والإنجليزية بسهولة' : 'Easy language switching with instant feedback' }}
                        </p>
                    </div>
                </section>

                <main class="glass-card relative rounded-[2rem] bg-white dark:bg-slate-800/95 shadow-2xl shadow-slate-900/15 border border-slate-200/80 dark:border-slate-700/80 p-8 sm:p-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                        <div class="flex items-center gap-4">
                            @if ($businessLogo)
                                <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $displayName }}" class="h-14 w-auto rounded-2xl object-contain" />
                            @else
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-500/20">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-indigo-600 dark:text-indigo-400">
                                    {{ $isArabic ? 'مرحباً بك في' : 'Sign in to' }}
                                </p>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $displayName }}</h2>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button onclick="toggleDarkMode()"
                                class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-900 px-4 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm transition hover:bg-slate-100 dark:hover:bg-slate-800">
                                <span id="dark-mode-icon" class="text-base">🌙</span>
                                <span class="sr-only">{{ $isArabic ? 'تبديل الوضع الليلي' : 'Toggle dark mode' }}</span>
                            </button>
                            <div class="inline-flex rounded-2xl border border-slate-200/80 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-900 py-1.5">
                                <a href="{{ url('/login?lang=en') }}"
                                   class="inline-flex min-w-[60px] items-center justify-center rounded-2xl px-3 text-sm font-semibold transition {{ !$isArabic ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white' }}">
                                    EN
                                </a>
                                <a href="{{ url('/login?lang=ar') }}"
                                   class="inline-flex min-w-[60px] items-center justify-center rounded-2xl px-3 text-sm font-semibold transition {{ $isArabic ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white' }}">
                                    عربي
                                </a>
                            </div>
                        </div>
                    </div>

                    <form id="loginForm" class="space-y-6">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">{{ $isArabic ? 'البريد الإلكتروني' : 'Email' }}</label>
                                <input type="email" id="email" name="email" required
                                       class="input-field w-full rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm placeholder-slate-400 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none"
                                       placeholder="{{ $isArabic ? 'البريد الإلكتروني' : 'Email address' }}">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">{{ $isArabic ? 'كلمة المرور' : 'Password' }}</label>
                                <input type="password" id="password" name="password" required
                                       class="input-field w-full rounded-2xl border border-slate-200 dark:border-slate-700 px-4 py-3 text-sm placeholder-slate-400 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none"
                                       placeholder="{{ $isArabic ? 'كلمة المرور' : 'Password' }}">
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="checkbox" id="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $isArabic ? 'تذكرني' : 'Remember me' }}
                            </label>
                            <a href="#" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                {{ $isArabic ? 'نسيت كلمة المرور؟' : 'Forgot password?' }}
                            </a>
                        </div>

                        <div id="errorMessage" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/40 dark:text-red-300"></div>
                        <div id="successMessage" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"></div>

                        <button type="submit" id="submitBtn"
                                class="btn-brand w-full rounded-2xl py-4 text-base font-semibold text-white flex items-center justify-center gap-3 transition hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-indigo-500/20">
                            <span id="btnText">{{ $isArabic ? 'دخول' : 'Login' }}</span>
                            <svg class="hidden animate-spin w-6 h-6" id="loadingSpinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>

                    <div class="mt-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ $isArabic ? 'تسجيل دخول آمن وسريع إلى حسابك.' : 'Secure, fast access to your account.' }}
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script>
        const isArabic = {{ $isArabic ? 'true' : 'false' }};
        const texts = {
            loggingIn: isArabic ? 'جاري الدخول...' : 'Logging in...',
            login: isArabic ? 'دخول' : 'Login',
            loginSuccess: isArabic ? 'تم تسجيل الدخول بنجاح!' : 'Login successful!',
            loginError: isArabic ? 'بيانات الدخول غير صحيحة' : 'Invalid credentials',
            errorOccurred: isArabic ? 'حدث خطأ! حاول مرة أخرى' : 'An error occurred! Please try again'
        };

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const errorDiv = document.getElementById('errorMessage');
            const successDiv = document.getElementById('successMessage');
            const submitButton = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const loadingSpinner = document.getElementById('loadingSpinner');

            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');

            submitButton.disabled = true;
            submitButton.classList.add('opacity-70');
            btnText.textContent = texts.loggingIn;
            loadingSpinner.classList.remove('hidden');

            const formData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                remember: document.getElementById('remember').checked,
            };

            try {
                const response = await fetch('{{ url('/api/auth/login') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (data.access_token) {
                        localStorage.setItem('auth_token', data.access_token);
                    }
                    if (data.user) {
                        localStorage.setItem('user', JSON.stringify(data.user));
                    }

                    successDiv.classList.remove('hidden');
                    successDiv.textContent = '✓ ' + texts.loginSuccess;

                    setTimeout(() => {
                        window.location.replace(data.redirect_to || '/admin/dashboard');
                    }, 500);
                } else {
                    errorDiv.classList.remove('hidden');
                    errorDiv.textContent = '✕ ' + (data.message || data.error || texts.loginError);

                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-70');
                    btnText.textContent = texts.login;
                    loadingSpinner.classList.add('hidden');
                }
            } catch (error) {
                errorDiv.classList.remove('hidden');
                errorDiv.textContent = '✕ ' + texts.errorOccurred;

                submitButton.disabled = false;
                submitButton.classList.remove('opacity-70');
                btnText.textContent = texts.login;
                loadingSpinner.classList.add('hidden');
            }
        });
    </script>
    <script src="/js/dark-mode.js"></script>
</body>

</html>
