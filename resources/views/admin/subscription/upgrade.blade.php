@extends('layouts.admin')

@section('title', __('Upgrade Plan'))
@section('subtitle', __('Choose the perfect plan for your business'))

@section('content')
    <!-- Current Plan -->
    @if($currentPlan)
    <div class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">{{ __('Current Plan') }}</p>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $currentPlan['plan_name'] }}</h3>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold text-slate-900 dark:text-slate-100">${{ number_format($currentPlan['price'], 2) }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-400">/ {{ $currentPlan['billing_cycle'] }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Available Plans -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($availablePlans as $plan)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border-2 {{ $plan['is_popular'] ? 'border-indigo-500' : 'border-slate-200 dark:border-slate-700' }} overflow-hidden hover:shadow-2xl transition-all">
            <!-- Popular Badge -->
            @if($plan['is_popular'])
            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-center py-2 text-sm font-semibold">
                ⭐ {{ __('Most Popular') }}
            </div>
            @endif

            <div class="p-8">
                <!-- Plan Name -->
                <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-2">{{ $plan['name'] }}</h3>

                <!-- Price -->
                <div class="mb-6">
                    <span class="text-5xl font-bold text-slate-900 dark:text-slate-100">${{ number_format($plan['price'], 0) }}</span>
                    <span class="text-slate-600 dark:text-slate-400 text-lg">/ {{ $plan['billing_cycle'] }}</span>
                </div>

                <!-- Features -->
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">
                                {{ $plan['max_users'] == -1 ? __('Unlimited Users') : __(':count Users', ['count' => $plan['max_users']]) }}
                            </p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Create staff and assistant accounts') }}</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">
                                {{ $plan['max_appointments'] == -1 ? __('Unlimited Appointments') : __(':count Appointments/month', ['count' => $plan['max_appointments']]) }}
                            </p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Monthly appointment bookings') }}</p>
                        </div>
                    </li>
                    @foreach($plan['features'] as $feature)
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-slate-700 dark:text-slate-300">{{ $feature }}</p>
                    </li>
                    @endforeach
                </ul>

                <!-- Upgrade Button -->
                <button onclick="openUpgradeModal({{ json_encode($plan) }})"
                        class="w-full py-3 px-6 rounded-lg font-semibold transition {{ $plan['is_popular'] ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white hover:from-indigo-600 hover:to-purple-600' : 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                    {{ __('Select This Plan') }}
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Upgrade Modal -->
    <div id="upgradeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
            <form action="{{ route('admin.subscription.requestUpgrade') }}" method="POST">
                @csrf
                <input type="hidden" name="plan_id" id="selectedPlanId">

                <!-- Header -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ __('Upgrade Request') }}</h3>
                        <button type="button" onclick="closeUpgradeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <p class="font-semibold text-indigo-900 dark:text-indigo-100 mb-1">{{ __('Upgrading to:') }} <span id="modalPlanName"></span></p>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300">{{ __('Our team will contact you to complete the upgrade process.') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            {{ __('Message (Optional)') }}
                        </label>
                        <textarea name="message" rows="4"
                                  class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-slate-700 dark:text-slate-100"
                                  placeholder="{{ __('Any special requirements or questions?') }}"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-slate-200 dark:border-slate-700 flex gap-3">
                    <button type="button" onclick="closeUpgradeModal()"
                            class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                        {{ __('Submit Request') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
const modal = document.getElementById('upgradeModal');

function openUpgradeModal(plan) {
    document.getElementById('selectedPlanId').value = plan.id;
    document.getElementById('modalPlanName').textContent = plan.name;
    modal.classList.remove('hidden');
}

function closeUpgradeModal() {
    modal.classList.add('hidden');
}

// Close modal on backdrop click
modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        closeUpgradeModal();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeUpgradeModal();
    }
});
</script>
@endpush
