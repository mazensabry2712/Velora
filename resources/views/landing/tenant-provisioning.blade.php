<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('Setting up your workspace') }} · Velora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.12),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(99,102,241,0.10),_transparent_30%)]"></div>

        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid w-full gap-8 lg:grid-cols-[1.05fr_.95fr] lg:items-center">
                <section class="hidden lg:block">
                    <div class="max-w-xl">
                        <div class="mb-6 inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white/80 px-4 py-2 shadow-sm backdrop-blur">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">V</span>
                            <span class="text-sm font-semibold text-slate-800">Velora</span>
                        </div>

                        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-sky-600">{{ __('Almost there') }}</p>
                        <h1 class="text-4xl font-black tracking-tight text-slate-950 xl:text-5xl">
                            {{ __('We are preparing your workspace.') }}
                        </h1>
                        <p class="mt-5 max-w-lg text-lg leading-8 text-slate-600">
                            {{ __('Your account is created. We are setting up the business workspace in the background so you can start without waiting on a long form.') }}
                        </p>

                        <div class="mt-8 grid max-w-lg grid-cols-3 gap-3">
                            <div class="rounded-2xl border border-white/80 bg-white/70 p-4 shadow-sm backdrop-blur">
                                <div class="text-xs font-semibold text-slate-400">01</div>
                                <div class="mt-2 text-sm font-bold text-slate-800">{{ __('Account') }}</div>
                                <div class="mt-1 text-xs text-emerald-600">{{ __('Created') }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/80 bg-white/70 p-4 shadow-sm backdrop-blur">
                                <div class="text-xs font-semibold text-slate-400">02</div>
                                <div class="mt-2 text-sm font-bold text-slate-800">{{ __('Workspace') }}</div>
                                <div id="workspaceStep" class="mt-1 text-xs text-sky-600">{{ __('Preparing') }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/80 bg-white/70 p-4 shadow-sm backdrop-blur">
                                <div class="text-xs font-semibold text-slate-400">03</div>
                                <div class="mt-2 text-sm font-bold text-slate-800">{{ __('Onboarding') }}</div>
                                <div id="onboardingStep" class="mt-1 text-xs text-slate-400">{{ __('Next') }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mx-auto w-full max-w-xl">
                    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_30px_80px_-35px_rgba(15,23,42,0.30)]">
                        <div class="h-1.5 bg-slate-100">
                            <div id="progress" class="h-full w-1/3 bg-slate-900 transition-all duration-700"></div>
                        </div>

                        <div class="p-6 sm:p-8 lg:p-10">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-lg font-bold text-white shadow-lg shadow-slate-900/10">V</div>
                                    <p class="text-sm font-semibold text-sky-600">{{ __('Your workspace') }}</p>
                                    <h2 id="title" class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ __('Setting up your workspace') }}</h2>
                                </div>
                                <span id="liveBadge" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                                    <span>{{ __('Live') }}</span>
                                </span>
                            </div>

                            <p id="description" class="mt-4 text-sm leading-6 text-slate-500 sm:text-base">
                                {{ __('We are preparing your business workspace. This page will continue automatically when everything is ready.') }}
                            </p>

                            <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200">B</div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-slate-400">{{ __('Business') }}</p>
                                        <p class="truncate text-sm font-bold text-slate-900 sm:text-base">{{ $businessName }}</p>
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <div class="flex items-center justify-between text-xs font-semibold text-slate-400">
                                        <span>{{ __('Setup progress') }}</span>
                                        <span id="percent">33%</span>
                                    </div>
                                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-200">
                                        <div id="progressInner" class="h-full w-1/3 rounded-full bg-slate-900 transition-all duration-700"></div>
                                    </div>
                                    <p id="status" class="mt-3 text-sm font-medium text-slate-700">{{ __('Creating your workspace...') }}</p>
                                </div>
                            </div>

                            <div id="verificationBox" class="mt-5 hidden rounded-2xl border border-sky-200 bg-sky-50 p-5">
                                <div class="flex gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-sky-600 shadow-sm ring-1 ring-sky-100">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M4 6.5 12 12l8-5.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold text-slate-900">{{ __('Verify your email to continue') }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('Your workspace is ready. Open the verification email we sent, then this page will continue automatically.') }}</p>
                                        <button id="resend" type="button" class="mt-4 inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                                            {{ __('Resend verification email') }}
                                        </button>
                                        <p id="resendStatus" class="mt-2 text-xs font-medium text-slate-500"></p>
                                    </div>
                                </div>
                            </div>

                            <div id="readyBox" class="mt-5 hidden rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                            <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ __('Everything is ready') }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('We are taking you to onboarding now.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div id="error" class="mt-5 hidden rounded-2xl border border-rose-200 bg-rose-50 p-5">
                                <p class="font-bold text-rose-900">{{ __('We could not finish setting up your workspace.') }}</p>
                                <p id="errorText" class="mt-1 text-sm leading-6 text-rose-700"></p>
                            </div>

                            <p class="mt-6 text-center text-xs leading-5 text-slate-400">
                                {{ __('You can safely leave this page and return to this link later.') }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        const statusUrl = @json($statusUrl);
        const resendUrl = @json($resendUrl);
        const progress = document.getElementById('progress');
        const progressInner = document.getElementById('progressInner');
        const percent = document.getElementById('percent');
        const status = document.getElementById('status');
        const error = document.getElementById('error');
        const errorText = document.getElementById('errorText');
        const title = document.getElementById('title');
        const description = document.getElementById('description');
        const verificationBox = document.getElementById('verificationBox');
        const readyBox = document.getElementById('readyBox');
        const liveBadge = document.getElementById('liveBadge');
        const resend = document.getElementById('resend');
        const resendStatus = document.getElementById('resendStatus');
        const workspaceStep = document.getElementById('workspaceStep');
        const onboardingStep = document.getElementById('onboardingStep');

        const labels = {
            queued: @json(__('Creating your workspace...')),
            finalizing: @json(__('Finishing your workspace setup...')),
            ready: @json(__('Workspace ready. Please verify your email.')),
            failed: @json(__('We could not finish setting up your workspace.')),
        };

        let resendCooldown = 0;

        function setProgress(value) {
            progress.style.width = `${value}%`;
            progressInner.style.width = `${value}%`;
            percent.textContent = `${value}%`;
        }

        function startResendCooldown(seconds) {
            resendCooldown = seconds;
            resend.disabled = true;
            const tick = () => {
                resend.textContent = resendCooldown > 0
                    ? @json(__('Resend available in')) + ` ${resendCooldown}s`
                    : @json(__('Resend verification email'));
                if (resendCooldown <= 0) {
                    resend.disabled = false;
                    return;
                }
                resendCooldown -= 1;
                window.setTimeout(tick, 1000);
            };
            tick();
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
                const isReady = data.ready === true;
                const isVerified = data.email_verified === true;

                if (current === 'finalizing') {
                    setProgress(75);
                    workspaceStep.textContent = @json(__('Finalizing'));
                    workspaceStep.className = 'mt-1 text-xs text-sky-600';
                } else if (isReady) {
                    setProgress(100);
                    workspaceStep.textContent = @json(__('Ready'));
                    workspaceStep.className = 'mt-1 text-xs text-emerald-600';
                    onboardingStep.textContent = @json(__('Starting'));
                    onboardingStep.className = 'mt-1 text-xs text-sky-600';
                } else {
                    setProgress(35);
                    workspaceStep.textContent = @json(__('Preparing'));
                }

                status.textContent = data.message || labels[current] || labels.queued;

                if (isReady && isVerified && data.redirect_url) {
                    readyBox.classList.remove('hidden');
                    verificationBox.classList.add('hidden');
                    title.textContent = @json(__('Everything is ready'));
                    description.textContent = @json(__('Your workspace is ready. Taking you to onboarding...'));
                    liveBadge.classList.add('hidden');
                    window.setTimeout(() => window.location.replace(data.redirect_url), 450);
                    return;
                }

                if (isReady && data.verification_required) {
                    verificationBox.classList.remove('hidden');
                    readyBox.classList.add('hidden');
                    title.textContent = @json(__('Verify your email to continue'));
                    description.textContent = @json(__('Your workspace is ready. Verify your email address and we will take you to onboarding automatically.'));
                    status.textContent = labels.ready;
                    setProgress(100);
                    liveBadge.classList.add('hidden');
                    workspaceStep.textContent = @json(__('Ready'));
                    workspaceStep.className = 'mt-1 text-xs text-emerald-600';
                    onboardingStep.textContent = @json(__('Waiting for verification'));
                    onboardingStep.className = 'mt-1 text-xs text-amber-600';
                    window.setTimeout(poll, 2500);
                    return;
                }

                if (data.failed) {
                    error.classList.remove('hidden');
                    errorText.textContent = data.message || labels.failed;
                    liveBadge.classList.add('hidden');
                    setProgress(100);
                    progress.classList.remove('bg-slate-900');
                    progress.classList.add('bg-rose-500');
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
