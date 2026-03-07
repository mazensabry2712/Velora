<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Setup Your Account') }} — Velora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

<div x-data="onboardingWizard()" class="w-full max-w-lg">

    {{-- Logo + Brand --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-indigo-600 rounded-2xl mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Velora</h1>
        <p class="text-slate-500 mt-1">{{ __('Let\'s set up your account in under 5 minutes') }}</p>
    </div>

    {{-- Progress Bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
            @foreach([
                ['icon' => '🏪', 'label' => __('Business')],
                ['icon' => '💈', 'label' => __('Staff')],
                ['icon' => '✂️', 'label' => __('Service')],
                ['icon' => '🔗', 'label' => __('Go Live')],
            ] as $i => $s)
            <div class="flex flex-col items-center gap-1">
                <div :class="step >= {{ $i + 1 }}
                        ? 'w-10 h-10 rounded-full flex items-center justify-center text-lg bg-indigo-600 text-white shadow-md'
                        : 'w-10 h-10 rounded-full flex items-center justify-center text-lg bg-slate-200 text-slate-400'">
                    <template x-if="step > {{ $i + 1 }}">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="step <= {{ $i + 1 }}">
                        <span>{{ $s['icon'] }}</span>
                    </template>
                </div>
                <span :class="step >= {{ $i + 1 }} ? 'text-xs font-medium text-indigo-600' : 'text-xs text-slate-400'">
                    {{ $s['label'] }}
                </span>
            </div>
            @if($i < 3)
            <div class="flex-1 h-0.5 mx-2 mt-[-20px]"
                 :class="step > {{ $i + 1 }} ? 'bg-indigo-600' : 'bg-slate-200'"></div>
            @endif
            @endforeach
        </div>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">

        {{-- Error alert --}}
        <div x-show="errorMsg" x-cloak
             class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"
             x-text="errorMsg"></div>

        {{-- ── STEP 1: Business Info ──────────────────────────────── --}}
        <div x-show="step === 1" x-transition>
            <h2 class="text-xl font-bold text-slate-900 mb-1">{{ __('Your Business') }}</h2>
            <p class="text-slate-500 text-sm mb-6">{{ __('Basic info your customers will see') }}</p>

            <form @submit.prevent="submitStep1" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            {{ __('Business name') }} <span class="text-red-500">*</span>
                        </label>
                        <input x-model="form1.business_name" type="text" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="{{ __('e.g. Al-Nour Barbershop') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            {{ __('Phone / WhatsApp') }} <span class="text-red-500">*</span>
                        </label>
                        <input x-model="form1.phone" type="tel" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="+966 5x xxx xxxx">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 mb-1 text-xs">
                            {{ __('Address') }} ({{ __('optional') }})
                        </label>
                        <input x-model="form1.address" type="text"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="{{ __('City, District') }}">
                    </div>
                </div>
                <button type="submit" :disabled="loading"
                        class="w-full mt-6 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition-colors">
                    <span x-show="!loading">{{ __('Next') }} →</span>
                    <span x-show="loading">{{ __('Saving...') }}</span>
                </button>
            </form>
        </div>

        {{-- ── STEP 2: Staff ────────────────────────────────────────── --}}
        <div x-show="step === 2" x-transition x-cloak>
            <h2 class="text-xl font-bold text-slate-900 mb-1">{{ __('Your First Barber') }}</h2>
            <p class="text-slate-500 text-sm mb-6">{{ __('You can add more after setup') }}</p>

            <form @submit.prevent="submitStep2">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            {{ __('Barber name') }} <span class="text-red-500">*</span>
                        </label>
                        <input x-model="form2.name" type="text" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="{{ __('e.g. Ahmed Al-Sharif') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 mb-1 text-xs">
                            {{ __('Specialty') }} ({{ __('optional') }})
                        </label>
                        <input x-model="form2.specialty" type="text"
                               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="{{ __('e.g. Fade, Shave, Kids') }}">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 1"
                            class="flex-1 border border-slate-300 text-slate-600 font-semibold py-3 rounded-xl hover:bg-slate-50 transition-colors">
                        ← {{ __('Back') }}
                    </button>
                    <button type="submit" :disabled="loading"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition-colors">
                        <span x-show="!loading">{{ __('Next') }} →</span>
                        <span x-show="loading">{{ __('Saving...') }}</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ── STEP 3: Service ──────────────────────────────────────── --}}
        <div x-show="step === 3" x-transition x-cloak>
            <h2 class="text-xl font-bold text-slate-900 mb-1">{{ __('Your First Service') }}</h2>
            <p class="text-slate-500 text-sm mb-6">{{ __('Add your most popular service') }}</p>

            <form @submit.prevent="submitStep3">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            {{ __('Service name') }} <span class="text-red-500">*</span>
                        </label>
                        <input x-model="form3.name" type="text" required
                               class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               placeholder="{{ __('e.g. Haircut & Beard') }}">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                {{ __('Duration (min)') }} <span class="text-red-500">*</span>
                            </label>
                            <input x-model="form3.duration" type="number" required min="5" max="480"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="30">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                {{ __('Price (SAR)') }} <span class="text-red-500">*</span>
                            </label>
                            <input x-model="form3.price" type="number" required min="0" step="0.01"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                   placeholder="50">
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" @click="step = 2"
                            class="flex-1 border border-slate-300 text-slate-600 font-semibold py-3 rounded-xl hover:bg-slate-50 transition-colors">
                        ← {{ __('Back') }}
                    </button>
                    <button type="submit" :disabled="loading"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition-colors">
                        <span x-show="!loading">{{ __('Next') }} →</span>
                        <span x-show="loading">{{ __('Saving...') }}</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ── STEP 4: Booking Link — Go Live ───────────────────────── --}}
        <div x-show="step === 4" x-transition x-cloak>
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900 mb-1">{{ __("You're ready!") }}</h2>
                <p class="text-slate-500 text-sm mb-6">{{ __('Share this link with your customers to start booking') }}</p>
            </div>

            {{-- Booking Link --}}
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-5">
                <p class="text-xs font-medium text-indigo-700 mb-2">{{ __('Your booking link') }}</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-sm text-indigo-900 font-mono bg-white px-3 py-2 rounded-lg border border-indigo-200 truncate">
                        {{ $bookingUrl }}
                    </code>
                    <button onclick="navigator.clipboard.writeText('{{ $bookingUrl }}')"
                            class="shrink-0 text-xs bg-indigo-600 text-white px-3 py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                        {{ __('Copy') }}
                    </button>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="text-center mb-6">
                <p class="text-xs text-slate-500 mb-3">{{ __('Or print this QR code at your counter') }}</p>
                <div class="inline-block bg-white border border-slate-200 rounded-xl p-4">
                    <img src="/admin/api/appointments/qr-preview?url={{ urlencode($bookingUrl) }}"
                         onerror="this.style.display='none'"
                         class="w-32 h-32 mx-auto" alt="QR Code">
                    <p class="text-xs text-slate-400 mt-2">{{ $subdomain }}.{{ $domain }}</p>
                </div>
            </div>

            {{-- Tips --}}
            <div class="space-y-2 mb-6">
                @foreach([
                    __('Share on your Instagram bio'),
                    __('Add to your WhatsApp business profile'),
                    __('Print QR code and place on your counter'),
                ] as $tip)
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span class="text-green-500 shrink-0">✓</span>
                    {{ $tip }}
                </div>
                @endforeach
            </div>

            <button @click="completeOnboarding" :disabled="loading"
                    class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white font-semibold py-3.5 rounded-xl transition-colors text-base">
                <span x-show="!loading">🚀 {{ __('Go to Dashboard') }}</span>
                <span x-show="loading">{{ __('Loading...') }}</span>
            </button>
        </div>

    </div>{{-- /card --}}

    <p class="text-center text-xs text-slate-400 mt-6">
        {{ __('You can change all this later in Settings') }} ·
        <a href="{{ route('admin.dashboard') }}" class="underline hover:text-slate-600">
            {{ __('Skip for now') }}
        </a>
    </p>

</div>{{-- /wrapper --}}

<script>
function onboardingWizard() {
    return {
        step: {{ max(1, $currentStep) }},
        loading: false,
        errorMsg: '',
        form1: { business_name: '', phone: '', address: '' },
        form2: { name: '', specialty: '' },
        form3: { name: '', duration: 30, price: '' },

        async post(url, data) {
            this.loading = true;
            this.errorMsg = '';
            try {
                const fd = new FormData();
                Object.entries(data).forEach(([k, v]) => { if (v !== null && v !== undefined && v !== '') fd.append(k, v); });
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const res = await fetch(url, { method: 'POST', body: fd });
                const json = await res.json();

                if (!json.success) {
                    this.errorMsg = json.message || 'Something went wrong.';
                    return null;
                }
                return json;
            } catch (e) {
                this.errorMsg = 'Network error — please try again.';
                return null;
            } finally {
                this.loading = false;
            }
        },

        async submitStep1() {
            const json = await this.post('/admin/onboarding/step1', this.form1);
            if (json) this.step = 2;
        },

        async submitStep2() {
            const json = await this.post('/admin/onboarding/step2', this.form2);
            if (json) this.step = 3;
        },

        async submitStep3() {
            const json = await this.post('/admin/onboarding/step3', this.form3);
            if (json) this.step = 4;
        },

        async completeOnboarding() {
            const json = await this.post('/admin/onboarding/complete', {});
            if (json?.redirect_url) {
                window.location.href = json.redirect_url;
            }
        },
    }
}
</script>
</body>
</html>
