<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app()->getLocale() === 'ar' ? __('verification.title') : __('Email verified') }} · Velora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
</head>
<body class="min-h-screen antialiased" style="background: var(--velora-light-gray); color: var(--velora-deep-navy);">
    <main class="min-h-screen flex items-center justify-center px-5 py-12">
        <section class="w-full max-w-lg overflow-hidden rounded-[2rem] border bg-white shadow-[0_30px_80px_-35px_rgba(13,18,38,.28)]" style="border-color: var(--velora-border);">
            <div class="h-1.5" style="background: var(--velora-gradient);"></div>
            <div class="p-7 text-center sm:p-10">
                <img src="{{ asset('logo.png') }}" alt="Velora" class="mx-auto mb-8 h-11 w-auto max-w-[190px] object-contain">

                <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl" style="background: rgba(0,212,163,.10); color: var(--velora-mint);">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <p class="mb-2 text-sm font-semibold" style="color: var(--velora-primary-blue);">Velora</p>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    {{ app()->getLocale() === 'ar' ? __('verification.email_verified') : __('Email verified') }}
                </h1>
                <p class="mt-4 text-sm leading-7 sm:text-base" style="color: var(--velora-gray);">
                    {{ app()->getLocale() === 'ar' ? __('verification.message') : __('Your email has been verified. Please return to your workspace setup page to continue.') }}
                </p>

                @if (! empty($businessName))
                    <div class="mx-auto mt-7 max-w-sm rounded-2xl border px-4 py-3 text-start" style="border-color: var(--velora-border); background: var(--velora-light-gray);">
                        <p class="text-xs font-medium" style="color: var(--velora-gray-light);">
                            {{ app()->getLocale() === 'ar' ? __('verification.business') : __('Business') }}
                        </p>
                        <p class="mt-1 truncate text-sm font-bold" style="color: var(--velora-deep-navy);">{{ $businessName }}</p>
                    </div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
