@extends('layouts.landing')
@section('content')

<div class="min-h-screen flex pt-16 bg-surface">

    {{-- ── Left Panel: Form ──────────────────────────────────────────── --}}
    <div class="flex-1 flex flex-col justify-center px-6 py-16 sm:px-12 lg:px-20 xl:px-32 max-w-2xl">

        <div class="mb-8">
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-white text-sm mb-8 transition-colors group">
                <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('landing.back_to_home') }}
            </a>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2">
                {{ __('landing.find_account_title') }}
            </h1>
            <p class="text-gray-400">
                {{ __('landing.find_account_sub') }}
            </p>
        </div>

        <div class="bg-gray-800/50 border border-gray-700/50 rounded-2xl p-8 backdrop-blur-sm">

            <form id="findAccountForm" onsubmit="redirectToLogin(event)" class="space-y-6">

                {{-- Subdomain Input --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        {{ __('landing.find_account_label') }}
                    </label>
                    <div class="flex items-center rounded-xl border border-gray-600 bg-gray-900/60 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 overflow-hidden transition-all">
                        <input
                            type="text"
                            id="subdomain"
                            name="subdomain"
                            required
                            autofocus
                            autocomplete="off"
                            placeholder="{{ __('landing.find_account_placeholder') }}"
                            class="flex-1 bg-transparent px-4 py-3 text-white placeholder-gray-500 focus:outline-none text-sm"
                            oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9\-]/g, '')"
                        >
                        <span class="px-4 py-3 text-gray-500 text-sm border-l border-gray-700 whitespace-nowrap select-none">
                            .{{ $baseDomain }}
                        </span>
                    </div>
                </div>

                {{-- Error --}}
                <div id="error-msg" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-sm"></div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full py-3 px-6 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all duration-200 flex items-center justify-center gap-2 group">
                    {{ __('landing.find_account_btn') }}
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>

            </form>

        </div>

        {{-- Signup Link --}}
        <p class="mt-6 text-center text-gray-400 text-sm">
            {{ __('landing.find_account_no_account') }}
            <a href="{{ route('signup') }}" class="text-indigo-400 hover:text-indigo-300 font-medium transition-colors">
                {{ __('landing.nav_start_trial') }}
            </a>
        </p>

    </div>

    {{-- ── Right Panel: Visual ────────────────────────────────────────── --}}
    <div class="hidden lg:flex flex-1 items-center justify-center bg-gradient-to-br from-indigo-600/20 to-purple-600/20 border-l border-gray-800/50 p-12">
        <div class="text-center max-w-md">
            <div class="text-8xl mb-6">🔑</div>
            <h2 class="text-2xl font-bold text-white mb-3">{{ __('landing.find_account_title') }}</h2>
            <p class="text-gray-400">{{ __('landing.find_account_sub') }}</p>

            <div class="mt-10 space-y-3">
                @foreach(['🏪 mysalon', '💅 beautystudio', '💇 hairlounge', '🌿 spacerelax'] as $demo)
                <div class="flex items-center gap-3 bg-gray-800/40 rounded-lg px-4 py-2 text-sm text-gray-400">
                    <span>{{ $demo }}</span>
                    <span class="text-gray-600">.{{ $baseDomain }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

<script>
function redirectToLogin(e) {
    e.preventDefault();
    const subdomain = document.getElementById('subdomain').value.trim();
    const errorEl = document.getElementById('error-msg');

    if (!subdomain || subdomain.length < 3) {
        errorEl.textContent = 'Please enter at least 3 characters.';
        errorEl.classList.remove('hidden');
        return;
    }

    errorEl.classList.add('hidden');

    const scheme = window.location.protocol; // http: or https:
    const baseDomain = '{{ $baseDomain }}';
    window.location.href = scheme + '//' + subdomain + '.' + baseDomain + '/login';
}

// Allow pressing Enter
document.getElementById('subdomain').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('findAccountForm').dispatchEvent(new Event('submit'));
    }
});
</script>

@endsection
