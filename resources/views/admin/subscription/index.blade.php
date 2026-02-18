@extends('layouts.admin')

@section('title', __('Subscription & Billing'))
@section('subtitle', __('Manage your subscription plan and billing'))

@section('content')
    @if($subscriptionInfo)
    <!-- Current Plan Overview -->
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-xl shadow-lg p-8 mb-8 text-white">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">{{ $subscriptionInfo['plan_name'] }}</h2>
                <p class="text-white/90">
                    @if($subscriptionInfo['status'] === 'trial')
                        {{ __('Trial Period') }} - {{ __(':days days remaining', ['days' => $subscriptionInfo['days_remaining']]) }}
                    @elseif($subscriptionInfo['days_remaining'] > 0)
                        {{ __(':days days remaining', ['days' => $subscriptionInfo['days_remaining']]) }}
                    @else
                        {{ __('Expired') }}
                    @endif
                </p>
            </div>
            <div class="text-right">
                <p class="text-4xl font-bold">${{ number_format($subscriptionInfo['price'], 2) }}</p>
                <p class="text-white/90">/ {{ $subscriptionInfo['billing_cycle'] }}</p>
            </div>
        </div>

        <!-- Usage Bars -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Users Usage -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ __('Users') }}</span>
                    <span class="text-sm">{{ $subscriptionInfo['limits']['users']['current'] }} / {{ $subscriptionInfo['limits']['users']['max'] }}</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3">
                    <div class="bg-white h-3 rounded-full transition-all"
                         style="width: {{ min(100, $subscriptionInfo['limits']['users']['percentage']) }}%"></div>
                </div>
            </div>

            <!-- Appointments Usage -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ __('Appointments') }} ({{ __('This Month') }})</span>
                    <span class="text-sm">{{ $subscriptionInfo['limits']['appointments']['current'] }} / {{ $subscriptionInfo['limits']['appointments']['max'] }}</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3">
                    <div class="bg-white h-3 rounded-full transition-all"
                         style="width: {{ min(100, $subscriptionInfo['limits']['appointments']['percentage']) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Upgrade Button -->
        @if($subscriptionInfo['limits']['users']['percentage'] > 80 || $subscriptionInfo['limits']['appointments']['percentage'] > 80)
        <div class="mt-6 flex items-center justify-between bg-white/20 backdrop-blur-sm rounded-lg p-4">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ __('You are approaching your plan limits') }}</span>
            </div>
            <a href="{{ route('admin.subscription.upgrade') }}"
               class="px-6 py-2 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition">
                {{ __('Upgrade Now') }}
            </a>
        </div>
        @endif
    </div>

    <!-- Plan Features -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Features Card -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Plan Features') }}</h3>
            <ul class="space-y-3">
                @foreach($subscriptionInfo['features'] as $feature)
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-slate-700 dark:text-slate-300">{{ $feature }}</span>
                </li>
                @endforeach
            </ul>
        </div>

        <!-- Usage Stats -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Usage Stats') }}</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('Total Users') }}</span>
                        <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $usage['users'] }}</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('Appointments This Month') }}</span>
                        <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $usage['appointments'] }}</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('Total Appointments') }}</span>
                        <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $usage['appointments_total'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Quick Actions') }}</h3>
            <div class="space-y-3">
                <a href="{{ route('admin.subscription.upgrade') }}"
                   class="flex items-center gap-3 p-3 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                    <span class="font-medium">{{ __('Upgrade Plan') }}</span>
                </a>
                <a href="{{ route('admin.subscription.billing') }}"
                   class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium">{{ __('Billing History') }}</span>
                </a>
                <button onclick="contactSupport()"
                        class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="font-medium">{{ __('Contact Support') }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Available Upgrades -->
    @if(count($availableUpgrades) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6">{{ __('Available Upgrades') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($availableUpgrades as $plan)
            <div class="border-2 border-slate-200 dark:border-slate-700 rounded-xl p-6 hover:border-indigo-500 transition {{ $plan['is_popular'] ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}">
                @if($plan['is_popular'])
                <span class="inline-block px-3 py-1 bg-indigo-500 text-white text-xs font-semibold rounded-full mb-3">{{ __('Popular') }}</span>
                @endif
                <h4 class="text-2xl font-bold text-slate-900 dark:text-slate-100 mb-2">{{ $plan['name'] }}</h4>
                <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mb-4">
                    ${{ number_format($plan['price'], 2) }}
                    <span class="text-sm text-slate-600 dark:text-slate-400">/ {{ $plan['billing_cycle'] }}</span>
                </p>
                <ul class="space-y-2 mb-6">
                    <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $plan['max_users'] == -1 ? __('Unlimited users') : __(':count users', ['count' => $plan['max_users']]) }}
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $plan['max_appointments'] == -1 ? __('Unlimited appointments') : __(':count appointments/month', ['count' => $plan['max_appointments']]) }}
                    </li>
                </ul>
                <a href="{{ route('admin.subscription.upgrade') }}?plan={{ $plan['id'] }}"
                   class="block text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                    {{ __('Select Plan') }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @else
    <!-- No Subscription -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-12 text-center">
        <svg class="w-16 h-16 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2">{{ __('No Active Subscription') }}</h3>
        <p class="text-slate-600 dark:text-slate-400 mb-6">{{ __('Please contact support to activate your subscription.') }}</p>
        <button onclick="contactSupport()"
                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
            {{ __('Contact Support') }}
        </button>
    </div>
    @endif
@endsection

@push('scripts')
<script>
function contactSupport() {
    alert('{{ __("Please contact: support@example.com") }}');
}
</script>
@endpush
