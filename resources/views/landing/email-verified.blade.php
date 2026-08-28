<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email verified - Velora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="min-h-screen flex items-center justify-center px-6 py-12">
        <section class="w-full max-w-lg rounded-3xl border border-white/10 bg-white/5 p-8 text-center shadow-2xl backdrop-blur">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-400/10 text-emerald-300">
                ✓
            </div>
            <p class="mb-2 text-sm font-medium text-sky-300">Velora</p>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('Email verified') }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-300">
                {{ __('Your email has been verified. Please return to your workspace setup page to continue.') }}
            </p>
        </section>
    </main>
</body>
</html>
