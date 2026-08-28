<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Finish setting up') }} — {{ $businessName ?: 'Velora' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<div x-data="onboardingWizard()" class="min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="w-full max-w-2xl">
        <header class="mb-6 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white mb-3">✓</div>
            <h1 class="text-2xl font-bold">{{ __('Finish setting up') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('A few quick details and you are ready to go.') }}</p>
            @if($businessName)
                <div class="mt-3 inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white border border-slate-200 shadow-sm text-sm">
                    <span class="text-slate-400">{{ __('Business') }}:</span>
                    <strong>{{ $businessName }}</strong>
                </div>
            @endif
        </header>

        <div class="mb-6 flex items-center gap-2">
            @foreach([__('Business'), __('Staff'), __('Service'), __('Go Live')] as $index => $label)
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <div :class="step >= {{ $index + 1 }} ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-400'"
                             class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0">
                            <span x-show="step <= {{ $index + 1 }}">{{ $index + 1 }}</span>
                            <span x-show="step > {{ $index + 1 }}">✓</span>
                        </div>
                        <span class="hidden sm:block text-xs font-semibold" :class="step >= {{ $index + 1 }} ? 'text-indigo-600' : 'text-slate-400'">{{ $label }}</span>
                    </div>
                    @if($index < 3)<div class="h-1 mt-2 rounded-full" :class="step > {{ $index + 1 }} ? 'bg-indigo-600' : 'bg-slate-200'"></div>@endif
                </div>
            @endforeach
        </div>

        <div x-show="errorMsg" x-cloak class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm" x-text="errorMsg"></div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">

            <section x-show="step === 1" x-transition>
                <h2 class="text-xl font-bold">{{ __('Business contact & branding') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Your business name is already saved. Add the remaining details your customers need.') }}</p>

                @if($businessName)
                    <div class="mt-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="text-xs text-slate-500">{{ __('Business name from signup') }}</p>
                        <p class="mt-1 font-bold text-slate-900">{{ $businessName }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('You can change it later from Settings.') }}</p>
                    </div>
                @endif

                <form class="mt-5 space-y-4" @submit.prevent="submitStep1">
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('Phone / WhatsApp') }} *</label>
                        <input x-model="form1.phone" type="tel" required maxlength="30" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" placeholder="+20 100 000 0000">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('Address') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                        <input x-model="form1.address" type="text" maxlength="255" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('City, District') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('Logo') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                        <input type="file" accept="image/*" @change="form1.logo = $event.target.files[0] || null" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white">
                    </div>
                    <button type="submit" :disabled="loading" class="w-full mt-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold py-3.5">
                        <span x-show="!loading">{{ __('Continue') }} →</span><span x-show="loading">{{ __('Saving…') }}</span>
                    </button>
                </form>
            </section>

            <section x-show="step === 2" x-cloak x-transition>
                <h2 class="text-xl font-bold">{{ __('Your first team member') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Add the first person who will provide your services.') }}</p>
                <form class="mt-6 space-y-4" @submit.prevent="submitStep2">
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('Name') }} *</label>
                        <input x-model="form2.name" type="text" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('e.g. Ahmed') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('Specialty') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></label>
                        <input x-model="form2.specialty" type="text" maxlength="100" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('e.g. Haircut, Massage, Consultation') }}">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="step=1" class="flex-1 rounded-xl border border-slate-300 text-slate-700 font-semibold py-3">← {{ __('Back') }}</button>
                        <button type="submit" :disabled="loading" class="flex-1 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold py-3">
                            <span x-show="!loading">{{ __('Continue') }} →</span><span x-show="loading">{{ __('Saving…') }}</span>
                        </button>
                    </div>
                </form>
            </section>

            <section x-show="step === 3" x-cloak x-transition>
                <h2 class="text-xl font-bold">{{ __('Your first service') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Start with one service. You can add the rest later.') }}</p>
                <form class="mt-6 space-y-4" @submit.prevent="submitStep3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">{{ __('Service name') }} *</label>
                        <input x-model="form3.name" type="text" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-indigo-500" placeholder="{{ __('e.g. Haircut') }}">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="block text-sm font-semibold mb-1">{{ __('Duration (min)') }} *</label><input x-model="form3.duration" type="number" min="5" max="480" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                        <div><label class="block text-sm font-semibold mb-1">{{ __('Price') }} *</label><input x-model="form3.price" type="number" min="0" step="0.01" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"></div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="step=2" class="flex-1 rounded-xl border border-slate-300 text-slate-700 font-semibold py-3">← {{ __('Back') }}</button>
                        <button type="submit" :disabled="loading" class="flex-1 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold py-3">
                            <span x-show="!loading">{{ __('Continue') }} →</span><span x-show="loading">{{ __('Saving…') }}</span>
                        </button>
                    </div>
                </form>
            </section>

            <section x-show="step === 4" x-cloak x-transition>
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl">✓</div>
                    <h2 class="mt-4 text-2xl font-bold">{{ __('Your workspace is ready') }}</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ __('Your public booking link is ready to share.') }}</p>
                </div>
                <div class="mt-6 rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                    <p class="text-xs font-semibold text-indigo-700 mb-2">{{ __('Booking link') }}</p>
                    <div class="flex gap-2 items-center"><code class="flex-1 min-w-0 truncate rounded-lg bg-white border border-indigo-100 px-3 py-2 text-sm">{{ $bookingUrl }}</code><button type="button" @click="copyLink" class="shrink-0 rounded-lg bg-indigo-600 text-white px-3 py-2 text-xs font-semibold">{{ __('Copy') }}</button></div>
                </div>
                <button @click="completeOnboarding" :disabled="loading" class="w-full mt-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-semibold py-3.5"><span x-show="!loading">{{ __('Go to Dashboard') }} →</span><span x-show="loading">{{ __('Finishing…') }}</span></button>
            </section>
        </div>

        <div class="mt-5 text-center"><a href="{{ route('admin.dashboard') }}" class="text-xs text-slate-400 underline hover:text-slate-600">{{ __('Skip for now') }}</a></div>
    </div>
</div>

<script>
function onboardingWizard() {
    return {
        step: {{ max(1, $currentStep) }},
        loading: false,
        errorMsg: '',
        form1: { phone: '', address: '', logo: null },
        form2: { name: '', specialty: '' },
        form3: { name: '', duration: 30, price: 0 },
        async request(url, options = {}) {
            this.loading = true;
            this.errorMsg = '';
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        ...(options.headers || {}),
                    },
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.success === false) {
                    const errors = data.errors || {};
                    this.errorMsg = Object.values(errors).flat()[0] || data.message || '{{ __('Something went wrong.') }}';
                    return null;
                }
                return data;
            } catch (error) {
                this.errorMsg = '{{ __('Network error. Please try again.') }}';
                return null;
            } finally {
                this.loading = false;
            }
        },
        async submitStep1() {
            const body = new FormData();
            body.append('phone', this.form1.phone);
            body.append('address', this.form1.address || '');
            if (this.form1.logo) body.append('logo', this.form1.logo);
            const data = await this.request('{{ route('admin.onboarding.step1') }}', { method: 'POST', body });
            if (data?.next_step) this.step = data.next_step;
        },
        async submitStep2() {
            const data = await this.request('{{ route('admin.onboarding.step2') }}', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form2),
            });
            if (data?.next_step) this.step = data.next_step;
        },
        async submitStep3() {
            const data = await this.request('{{ route('admin.onboarding.step3') }}', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form3),
            });
            if (data?.next_step) this.step = data.next_step;
        },
        async completeOnboarding() {
            const data = await this.request('{{ route('admin.onboarding.complete') }}', { method: 'POST' });
            if (data?.redirect_url) window.location.href = data.redirect_url;
        },
        async copyLink() {
            try { await navigator.clipboard.writeText(@js($bookingUrl)); } catch (_) {}
        },
    };
}
</script>
</body>
</html>
