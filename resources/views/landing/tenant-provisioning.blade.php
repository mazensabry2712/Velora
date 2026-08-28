<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Setting up your workspace') }} - Velora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="min-h-screen flex items-center justify-center px-6 py-12">
        <section class="w-full max-w-lg rounded-3xl border border-white/10 bg-white/5 p-8 text-center shadow-2xl backdrop-blur">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10">
                <svg id="spinner" class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                    <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                </svg>
            </div>

            <p class="mb-2 text-sm font-medium text-sky-300">Velora</p>
            <h1 id="title" class="text-2xl font-bold tracking-tight">{{ __('Setting up your workspace') }}</h1>
            <p id="description" class="mt-3 text-sm leading-6 text-slate-300">
                {{ __('We are preparing your business workspace. This page will continue automatically when everything is ready.') }}
            </p>

            <div class="mt-8 rounded-2xl bg-black/20 p-4 text-left rtl:text-right">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm text-slate-400">{{ __('Business') }}</span>
                    <span class="truncate text-sm font-semibold">{{ $businessName }}</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                    <div id="progress" class="h-full w-1/4 rounded-full bg-sky-400 transition-all duration-500"></div>
                </div>
                <p id="status" class="mt-3 text-sm text-slate-300">{{ __('Creating your workspace...') }}</p>
            </div>

            <div id="verificationBox" class="mt-5 hidden rounded-2xl border border-sky-400/20 bg-sky-400/10 p-4 text-left rtl:text-right">
                <p class="text-sm font-semibold">{{ __('Check your email') }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-300">
                    {{ __('Your workspace is ready. Verify your email address from the message we sent, then this page will continue automatically.') }}
                </p>
                <button id="resend" type="button" class="mt-4 rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('Resend verification email') }}
                </button>
                <p id="resendStatus" class="mt-2 text-xs text-slate-400"></p>
            </div>

            <p id="error" class="mt-5 hidden rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-200"></p>
        </section>
    </main>

    <script>
        const statusUrl = @json($statusUrl);
        const resendUrl = @json($resendUrl);
        const progress = document.getElementById('progress');
        const status = document.getElementById('status');
        const error = document.getElementById('error');
        const title = document.getElementById('title');
        const description = document.getElementById('description');
        const spinner = document.getElementById('spinner');
        const verificationBox = document.getElementById('verificationBox');
        const resend = document.getElementById('resend');
        const resendStatus = document.getElementById('resendStatus');

        const labels = {
            queued: @json(__('Creating your workspace...')),
            finalizing: @json(__('Finishing your workspace setup...')),
            ready: @json(__('Workspace ready. Please verify your email.')),
            failed: @json(__('We could not finish setting up your workspace.')),
        };

        let resendCooldown = 0;

        function startResendCooldown(seconds) {
            resendCooldown = seconds;
            resend.disabled = true;
            const timer = window.setInterval(() => {
                resendCooldown -= 1;
                resend.textContent = resendCooldown > 0
                    ? @json(__('Resend available in')) + ` ${resendCooldown}s`
                    : @json(__('Resend verification email'));
                if (resendCooldown <= 0) {
                    window.clearInterval(timer);
                    resend.disabled = false;
                }
            }, 1000);
        }

        resend.addEventListener('click', async () => {
            if (resendCooldown > 0) return;

            try {
                const response = await fetch(resendUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json();
                resendStatus.textContent = data.success
                    ? @json(__('Verification email sent.'))
                    : (data.message || @json(__('Unable to resend right now.')));
                if (data.success) startResendCooldown(data.cooldown || 60);
            } catch (e) {
                resendStatus.textContent = @json(__('Unable to resend right now.'));
            }
        });

        async function poll() {
            try {
                const response = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) throw new Error('status request failed');

                const data = await response.json();
                const current = data.status || 'queued';

                status.textContent = data.message || labels[current] || labels.queued;
                progress.style.width = current === 'finalizing' ? '75%' : current === 'ready' ? '100%' : '35%';

                if (data.ready && data.email_verified && data.redirect_url) {
                    progress.style.width = '100%';
                    window.location.replace(data.redirect_url);
                    return;
                }

                if (data.ready && data.verification_required) {
                    spinner.classList.remove('animate-spin');
                    verificationBox.classList.remove('hidden');
                    title.textContent = @json(__('Verify your email to continue'));
                    description.textContent = @json(__('Your workspace is ready. Verify your email address and we will take you to onboarding automatically.'));
                    status.textContent = labels.ready;
                    window.setTimeout(poll, 2500);
                    return;
                }

                if (data.failed) {
                    error.textContent = data.message || labels.failed;
                    error.classList.remove('hidden');
                    progress.classList.add('bg-red-400');
                    return;
                }

                window.setTimeout(poll, 1200);
            } catch (e) {
                status.textContent = @json(__('Still preparing your workspace...'));
                window.setTimeout(poll, 1800);
            }
        }

        poll();
    </script>
</body>
</html>
