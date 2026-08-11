@extends('layouts.admin')

@section('title', __('Subscription & Billing'))
@section('subtitle', __('Manage your plan, track usage, and view invoices'))

@section('content')

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-sm flex items-center gap-2">
        ✅ {{ session('success') }}
    </div>
    @endif

    @if(session('error') || $errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
        {{ session('error') ?? $errors->first() }}
    </div>
    @endif

    @if($subscriptionInfo)
    @php
        $statusColors = [
            'active'    => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
            'trial'     => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            'grace'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
            'expired'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            'cancelled' => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400',
        ];
        $statusClass = $statusColors[$subscriptionInfo['status']] ?? 'bg-slate-100 text-slate-800';
    @endphp

    {{-- Current Plan Overview --}}
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-2xl shadow-lg p-8 mb-8 text-white">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-3xl font-bold">{{ $subscriptionInfo['plan_name'] }}</h2>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-white/20">
                        {{ ucfirst($subscriptionInfo['status']) }}
                    </span>
                </div>
                <p class="text-white/90 text-sm">
                    @if($subscriptionInfo['status'] === 'trial')
                        {{ __('Trial Period') }} — {{ __(':days days remaining', ['days' => $subscriptionInfo['days_remaining']]) }}
                    @elseif($subscriptionInfo['status'] === 'grace' && $subscriptionInfo['grace_ends_at'])
                        {{ __('Grace period ends') }} {{ \Carbon\Carbon::parse($subscriptionInfo['grace_ends_at'])->format('M d, Y') }}
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

        {{-- Usage Bars --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ __('Users') }}</span>
                    <span class="text-sm">{{ $subscriptionInfo['limits']['users']['current'] }} / {{ $subscriptionInfo['limits']['users']['max'] }}</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3">
                    <div class="bg-white h-3 rounded-full transition-all" style="width: {{ min(100, $subscriptionInfo['limits']['users']['percentage']) }}%"></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ __('Appointments') }} ({{ __('This Month') }})</span>
                    <span class="text-sm">{{ $subscriptionInfo['limits']['appointments']['current'] }} / {{ $subscriptionInfo['limits']['appointments']['max'] }}</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3">
                    <div class="bg-white h-3 rounded-full transition-all" style="width: {{ min(100, $subscriptionInfo['limits']['appointments']['percentage']) }}%"></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ __('Storage') }}</span>
                    <span class="text-sm">{{ $subscriptionInfo['limits']['storage']['max'] }}</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3">
                    <div class="bg-white h-3 rounded-full transition-all" style="width: 5%"></div>
                </div>
            </div>
        </div>

        {{-- Near-limit warning --}}
        @if($subscriptionInfo['limits']['users']['percentage'] > 80 || $subscriptionInfo['limits']['appointments']['percentage'] > 80)
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white/20 backdrop-blur-sm rounded-lg p-4">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ __('You are approaching your plan limits') }}</span>
            </div>
            <a href="{{ route('admin.subscription.upgrade') }}" class="px-6 py-2 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition text-center">
                {{ __('Upgrade Now') }}
            </a>
        </div>
        @endif

        {{-- Payment / trial actions --}}
        @if($subscriptionInfo['stripe_customer_id'] || ($subscriptionInfo['status'] === 'trial' && !$subscriptionInfo['trial_extended']))
        <div class="mt-6 pt-6 border-t border-white/20 flex flex-wrap gap-3">
            @if($subscriptionInfo['stripe_customer_id'])
            <form action="{{ route('billing.portal') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-sm px-4 py-2.5 rounded-xl transition-all border border-white/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    {{ __('Manage Billing Portal') }}
                </button>
            </form>
            @endif

            @if($subscriptionInfo['status'] === 'trial' && !$subscriptionInfo['trial_extended'])
            <form action="{{ route('billing.extend-trial') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-sm px-4 py-2.5 rounded-xl transition-all border border-white/20">
                    {{ __('Extend Trial 7 Days') }}
                </button>
            </form>
            @endif
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Features --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Plan Features') }}</h3>
            <ul class="space-y-3">
                @forelse($subscriptionInfo['features'] as $feature)
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-slate-700 dark:text-slate-300">{{ $feature }}</span>
                </li>
                @empty
                <li class="text-slate-500 dark:text-slate-400 text-sm">{{ __('No feature list configured for this plan.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Usage Stats --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Usage Stats') }}</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('Total Users') }}</span>
                    <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $usage['users'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('Appointments This Month') }}</span>
                    <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $usage['appointments'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ __('Total Appointments') }}</span>
                    <span class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $usage['appointments_total'] }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
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
                <a href="mailto:support@velora.com" class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700 text-slate-600 dark:text-slate-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="font-medium">{{ __('Contact Support') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Available Upgrades (with instant checkout where possible) --}}
    @if(count($availableUpgrades) > 0)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6 mb-8">
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
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $plan['max_users'] == -1 ? __('Unlimited users') : __(':count users', ['count' => $plan['max_users']]) }}
                    </li>
                    <li class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $plan['max_appointments'] == -1 ? __('Unlimited appointments') : __(':count appointments/month', ['count' => $plan['max_appointments']]) }}
                    </li>
                </ul>

                @if(!empty($plan['stripe_price_id']))
                <form action="{{ route('billing.checkout') }}" method="POST">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
                    <button type="submit" class="w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                        {{ __('Subscribe Now') }}
                    </button>
                </form>
                @else
                <a href="{{ route('admin.subscription.upgrade') }}?plan={{ $plan['id'] }}"
                   class="block text-center px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition font-semibold">
                    {{ __('Request This Plan') }}
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent billing history preview --}}
    @if($invoices && $invoices->count() > 0)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('Recent Billing History') }}</h3>
            <a href="{{ route('admin.subscription.billing') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('View all') }} →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Plan') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Amount') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($invoices->take(5) as $inv)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="px-6 py-3.5 font-medium text-slate-900 dark:text-slate-100">{{ $inv->plan_name ?? '—' }}</td>
                        <td class="px-6 py-3.5">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$inv->status] ?? 'bg-slate-100 text-slate-800' }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-900 dark:text-slate-100">${{ number_format($inv->amount_paid ?? 0, 2) }}</td>
                        <td class="px-6 py-3.5 text-slate-600 dark:text-slate-400">{{ \Carbon\Carbon::parse($inv->created_at)->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @else
    {{-- No Subscription --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-12 text-center">
        <svg class="w-16 h-16 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2">{{ __('No Active Subscription') }}</h3>
        <p class="text-slate-600 dark:text-slate-400 mb-6">{{ __('Choose a plan to get started with a free trial.') }}</p>
        <a href="/billing/expired" class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
            {{ __('Choose a Plan') }}
        </a>
    </div>
    @endif
@endsection
