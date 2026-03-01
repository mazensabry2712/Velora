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
                Back to home
            </a>

            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2">
                Create your free account
            </h1>
            <p class="text-gray-400">
                Start your <span class="text-brand-400 font-semibold">{{ $maxTrialDays }}-day free trial</span> — no credit card required.
            </p>
        </div>

        {{-- Error Alert --}}
        @if($errors->has('general'))
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            {{ $errors->first('general') }}
        </div>
        @endif

        <form id="signupForm" action="{{ route('signup.store') }}" method="POST" class="space-y-5" novalidate>
            @csrf
            <input type="hidden" name="plan_id" value="{{ request('plan', old('plan_id', $plans->first()?->id)) }}">

            {{-- Plan Selection --}}
            @if($plans->count() > 1)
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Choose Your Plan</label>
                <div class="grid grid-cols-1 sm:grid-cols-{{ min($plans->count(), 3) }} gap-2.5">
                    @foreach($plans as $plan)
                    @php $isSelected = request('plan', old('plan_id', $plans->first()?->id)) == $plan->id; @endphp
                    <label class="relative cursor-pointer">
                        <input type="radio" name="plan_id" value="{{ $plan->id }}"
                               {{ $isSelected ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="border rounded-xl px-3 py-2.5 text-center transition-all
                                    border-white/10 bg-white/5 hover:border-brand-500/50
                                    peer-checked:border-brand-500 peer-checked:bg-brand-500/10">
                            <div class="font-bold text-white text-sm">{{ $plan->name }}</div>
                            <div class="text-brand-400 font-black text-lg">${{ number_format($plan->price, 0) }}</div>
                            <div class="text-xs text-gray-400">{{ $plan->billing_cycle === 'yearly' ? '/yr' : '/mo' }}</div>
                            @if($plan->trial_days > 0)
                            <div class="mt-1 text-xs text-green-400">{{ $plan->trial_days }}-day trial</div>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Business Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">
                    Business Name <span class="text-red-400">*</span>
                </label>
                <input
                    type="text"
                    name="business_name"
                    value="{{ old('business_name') }}"
                    placeholder="e.g. My Beauty Salon"
                    required
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-sm"
                />
                @error('business_name')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Subdomain --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">
                    Your Booking URL <span class="text-red-400">*</span>
                </label>
                <div class="flex rounded-xl overflow-hidden border border-white/10 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500 transition-all">
                    <input
                        type="text"
                        name="subdomain"
                        id="subdomain"
                        value="{{ old('subdomain') }}"
                        placeholder="mysalon"
                        required
                        maxlength="32"
                        pattern="[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]"
                        class="flex-1 bg-white/5 px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none text-sm font-mono"
                    />
                    <span class="bg-white/10 px-4 py-3 text-gray-400 text-sm font-mono border-l border-white/10 flex-shrink-0">
                        .velora.com
                    </span>
                </div>
                <div id="subdomainStatus" class="mt-1.5 text-xs hidden"></div>
                <p class="mt-1 text-xs text-gray-600">Lowercase letters, numbers, and hyphens only.</p>
                @error('subdomain')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">
                    Email Address <span class="text-red-400">*</span>
                </label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    required
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-sm"
                />
                @error('email')
                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">
                        Password <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="Min 8 characters"
                            required
                            minlength="8"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 pr-10 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-sm"
                        />
                        <button type="button" onclick="togglePassword('password')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">
                        Confirm Password <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="password"
                        name="password_confirmation"
                        placeholder="Repeat password"
                        required
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder:text-gray-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all text-sm"
                    />
                </div>
            </div>

            {{-- Country + Language Row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Country</label>
                    <select name="country"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-gray-300 focus:outline-none focus:border-brand-500 transition-all text-sm appearance-none">
                        <option value="">Select country...</option>
                        <option value="SA" {{ old('country') == 'SA' ? 'selected' : '' }}>🇸🇦 Saudi Arabia</option>
                        <option value="AE" {{ old('country') == 'AE' ? 'selected' : '' }}>🇦🇪 UAE</option>
                        <option value="EG" {{ old('country') == 'EG' ? 'selected' : '' }}>🇪🇬 Egypt</option>
                        <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>🇺🇸 United States</option>
                        <option value="GB" {{ old('country') == 'GB' ? 'selected' : '' }}>🇬🇧 United Kingdom</option>
                        <option value="FR" {{ old('country') == 'FR' ? 'selected' : '' }}>🇫🇷 France</option>
                        <option value="DE" {{ old('country') == 'DE' ? 'selected' : '' }}>🇩🇪 Germany</option>
                        <option value="ES" {{ old('country') == 'ES' ? 'selected' : '' }}>🇪🇸 Spain</option>
                        <option value="BR" {{ old('country') == 'BR' ? 'selected' : '' }}>🇧🇷 Brazil</option>
                        <option value="JP" {{ old('country') == 'JP' ? 'selected' : '' }}>🇯🇵 Japan</option>
                        <option value="CN" {{ old('country') == 'CN' ? 'selected' : '' }}>🇨🇳 China</option>
                        <option value="RU" {{ old('country') == 'RU' ? 'selected' : '' }}>🇷🇺 Russia</option>
                        <option value="IT" {{ old('country') == 'IT' ? 'selected' : '' }}>🇮🇹 Italy</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Dashboard Language</label>
                    <select name="language"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-gray-300 focus:outline-none focus:border-brand-500 transition-all text-sm appearance-none">
                        <option value="en" {{ old('language','en') == 'en' ? 'selected' : '' }}>🇺🇸 English</option>
                        <option value="ar" {{ old('language') == 'ar' ? 'selected' : '' }}>🇸🇦 Arabic</option>
                        <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>🇫🇷 French</option>
                        <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>🇪🇸 Spanish</option>
                        <option value="de" {{ old('language') == 'de' ? 'selected' : '' }}>🇩🇪 German</option>
                        <option value="it" {{ old('language') == 'it' ? 'selected' : '' }}>🇮🇹 Italian</option>
                        <option value="pt" {{ old('language') == 'pt' ? 'selected' : '' }}>🇧🇷 Portuguese</option>
                        <option value="ru" {{ old('language') == 'ru' ? 'selected' : '' }}>🇷🇺 Russian</option>
                        <option value="zh" {{ old('language') == 'zh' ? 'selected' : '' }}>🇨🇳 Chinese</option>
                        <option value="ja" {{ old('language') == 'ja' ? 'selected' : '' }}>🇯🇵 Japanese</option>
                    </select>
                </div>
            </div>

            {{-- Terms --}}
            <div class="flex items-start gap-3">
                <input
                    type="checkbox"
                    name="terms"
                    id="terms"
                    value="1"
                    required
                    class="mt-0.5 w-4 h-4 rounded border-white/20 bg-white/5 text-brand-500 focus:ring-brand-500 focus:ring-offset-0 cursor-pointer"
                />
                <label for="terms" class="text-sm text-gray-400 leading-relaxed cursor-pointer">
                    I agree to the
                    <a href="#" class="text-brand-400 hover:text-brand-300 underline">Terms of Service</a>
                    and
                    <a href="#" class="text-brand-400 hover:text-brand-300 underline">Privacy Policy</a>
                </label>
            </div>
            @error('terms')
            <p class="text-xs text-red-400">{{ $message }}</p>
            @enderror

            {{-- Submit --}}
            <button
                type="submit"
                id="submitBtn"
                class="btn-primary w-full text-white font-bold text-base px-6 py-4 rounded-xl flex items-center justify-center gap-3 transition-all"
            >
                <span id="btnText">Create My Free Account</span>
                <svg id="btnSpinner" class="w-5 h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </button>

            {{-- Login redirect --}}
            <p class="text-center text-sm text-gray-500">
                Already have an account?
                <a href="{{ route('super-admin.login') }}" class="text-brand-400 hover:text-brand-300 font-medium">Sign in</a>
            </p>
        </form>
    </div>

    {{-- ── Right Panel: Benefits ──────────────────────────────────────── --}}
    <div class="hidden lg:flex flex-1 flex-col justify-center px-16 py-16 bg-white/[0.02] border-l border-white/5 relative overflow-hidden">

        <div class="absolute inset-0 bg-gradient-radial from-brand-500/10 via-transparent to-transparent pointer-events-none"></div>

        <div class="relative z-10 max-w-md">
            <div class="glass rounded-2xl p-8 mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl btn-primary flex items-center justify-center text-xl">🎉</div>
                    <div>
                        <div class="font-bold text-white">{{ $maxTrialDays }}-Day Free Trial</div>
                        <div class="text-xs text-gray-400">No credit card required</div>
                    </div>
                </div>
                <p class="text-gray-300 text-sm leading-relaxed">
                    Get full access to all features for {{ $maxTrialDays }} days. If you love it (you will),
                    choose a plan that fits your business. If not, no hard feelings.
                </p>
            </div>

            <div class="space-y-4">
                @foreach([
                    ['✅', 'Your own subdomain (yourname.velora.com)', null],
                    ['✅', 'Full appointment & queue management', null],
                    ['✅', 'Unlimited bookings during trial', null],
                    ['✅', 'Staff management & scheduling', null],
                    ['✅', 'Customer-facing booking page', null],
                    ['✅', 'Analytics dashboard', null],
                    ['✅', 'Email reminders', null],
                    ['✅', 'Multi-language support (10 languages)', null],
                ] as [$icon, $text, $_])
                <div class="flex items-center gap-3 text-sm text-gray-300">
                    <span class="text-base flex-shrink-0">{{ $icon }}</span>
                    {{ $text }}
                </div>
                @endforeach
            </div>

            <div class="mt-8 p-4 glass rounded-xl border border-brand-500/20">
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Your data is <strong class="text-white">100% isolated</strong> in a dedicated database.
                    Enterprise-grade security from day one.
                </div>
            </div>

            {{-- Fake social proof widget --}}
            <div class="mt-6 flex items-center gap-3">
                <div class="flex -space-x-2">
                    @foreach(['S','M','A','J','L'] as $l)
                    <div class="w-8 h-8 rounded-full btn-primary flex items-center justify-center text-xs font-bold text-white border-2 border-surface">{{ $l }}</div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400">
                    <span class="text-white font-semibold">500+</span> businesses signed up this month
                </p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Subdomain live check
let subdomainTimer;
const subdomainInput = document.getElementById('subdomain');
const statusEl       = document.getElementById('subdomainStatus');

subdomainInput?.addEventListener('input', function () {
    const val = this.value.toLowerCase().replace(/[^a-z0-9\-]/g, '');
    this.value = val;

    clearTimeout(subdomainTimer);
    if (val.length < 3) {
        statusEl.className = 'mt-1.5 text-xs hidden';
        return;
    }

    statusEl.className = 'mt-1.5 text-xs text-gray-400';
    statusEl.textContent = 'Checking availability...';

    subdomainTimer = setTimeout(() => {
        fetch(`/signup/check-subdomain?subdomain=${encodeURIComponent(val)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        })
        .then(r => r.json())
        .then(data => {
            statusEl.className = `mt-1.5 text-xs ${data.available ? 'text-green-400' : 'text-red-400'}`;
            statusEl.textContent = (data.available ? '✅ ' : '❌ ') + data.message;
        })
        .catch(() => { statusEl.className = 'mt-1.5 text-xs hidden'; });
    }, 500);
});

// Auto-fill subdomain from business name
document.querySelector('[name="business_name"]')?.addEventListener('input', function () {
    if (subdomainInput && !subdomainInput.dataset.touched) {
        const slug = this.value.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9\-]/g, '')
            .replace(/^-|-$/g, '')
            .substring(0, 32);
        subdomainInput.value = slug;
        subdomainInput.dispatchEvent(new Event('input'));
    }
});

subdomainInput?.addEventListener('input', () => { subdomainInput.dataset.touched = '1'; });
subdomainInput?.addEventListener('focus',  () => { subdomainInput.dataset.touched = '1'; });

// Password toggle
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type  = input.type === 'password' ? 'text' : 'password';
}

// Loading state
document.getElementById('signupForm')?.addEventListener('submit', function (e) {
    const btn       = document.getElementById('submitBtn');
    const btnText   = document.getElementById('btnText');
    const spinner   = document.getElementById('btnSpinner');
    btn.disabled    = true;
    btnText.textContent = 'Creating your account...';
    spinner.classList.remove('hidden');
});
</script>
@endpush
