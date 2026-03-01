@extends('layouts.app')
@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Subscription & Billing</h1>
            <p class="text-gray-400 text-sm mt-1">Manage your plan, track usage, and view invoices.</p>
        </div>
        @if($subscription && $subscription->stripe_customer_id)
        <form action="/billing/portal" method="POST">
            @csrf
            <button type="submit"
                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-sm px-4 py-2.5 rounded-xl transition-all border border-white/10">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Manage Billing Portal
            </button>
        </form>
        @endif
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-green-500/10 border border-green-500/30 text-green-300 text-sm flex items-center gap-2">
        ✅ {{ session('success') }}
    </div>
    @endif

    {{-- Current Plan Card --}}
    @if($subscription)
    <div class="glass rounded-2xl p-6 border
        {{ $subscription->status === 'trial'   ? 'border-blue-500/30'  : '' }}
        {{ $subscription->status === 'active'  ? 'border-green-500/30' : '' }}
        {{ $subscription->status === 'grace'   ? 'border-orange-500/30': '' }}
        {{ $subscription->status === 'expired' ? 'border-red-500/30'   : '' }}">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-xl font-bold text-white">{{ $subscription->plan_name }}</h2>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full
                        {{ $subscription->status === 'trial'   ? 'bg-blue-500/20 text-blue-300'   : '' }}
                        {{ $subscription->status === 'active'  ? 'bg-green-500/20 text-green-300' : '' }}
                        {{ $subscription->status === 'grace'   ? 'bg-orange-500/20 text-orange-300': '' }}
                        {{ $subscription->status === 'expired' ? 'bg-red-500/20 text-red-300'     : '' }}
                        {{ $subscription->status === 'cancelled'? 'bg-gray-500/20 text-gray-400'  : '' }}">
                        {{ ucfirst($subscription->status) }}
                    </span>
                </div>
                <p class="text-gray-400 text-sm">
                    @if($subscription->status === 'trial' && $subscription->trial_ends_at)
                        Trial ends {{ \Carbon\Carbon::parse($subscription->trial_ends_at)->format('M d, Y') }}
                        ({{ max(0, (int) now()->diffInDays($subscription->trial_ends_at, false)) }} days left)
                    @elseif($subscription->status === 'active' && $subscription->ends_at)
                        Renews {{ \Carbon\Carbon::parse($subscription->ends_at)->format('M d, Y') }}
                    @elseif($subscription->status === 'grace' && $subscription->grace_ends_at)
                        Grace period ends {{ \Carbon\Carbon::parse($subscription->grace_ends_at)->format('M d, Y') }}
                    @endif
                </p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-black text-white">${{ number_format($subscription->price ?? 0, 0) }}</div>
                <div class="text-gray-400 text-xs">{{ $subscription->billing_cycle ?? 'per month' }}</div>
            </div>
        </div>

        {{-- Usage Bars --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 border-t border-white/10">
            @php
                $maxUsers = $subscription->max_users == -1 ? null : $subscription->max_users;
                $maxAppts = $subscription->max_appointments == -1 ? null : $subscription->max_appointments;
                $usersPercent = $maxUsers ? min(100, round(($usersCount / $maxUsers) * 100)) : 0;
                $apptsPercent = $maxAppts ? min(100, round(($appointmentsCount / $maxAppts) * 100)) : 0;
            @endphp

            {{-- Staff --}}
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-400">Staff Members</span>
                    <span class="text-white font-medium">{{ $usersCount }} / {{ $maxUsers ?? '∞' }}</span>
                </div>
                <div class="h-2 rounded-full bg-white/10">
                    <div class="h-full rounded-full {{ $usersPercent > 90 ? 'bg-red-500' : 'bg-brand-500' }} transition-all"
                         style="width: {{ $maxUsers ? $usersPercent : 15 }}%"></div>
                </div>
            </div>

            {{-- Appointments --}}
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-400">Appointments (this month)</span>
                    <span class="text-white font-medium">{{ number_format($appointmentsCount) }} / {{ $maxAppts ? number_format($maxAppts) : '∞' }}</span>
                </div>
                <div class="h-2 rounded-full bg-white/10">
                    <div class="h-full rounded-full {{ $apptsPercent > 90 ? 'bg-red-500' : 'bg-green-500' }} transition-all"
                         style="width: {{ $maxAppts ? $apptsPercent : 10 }}%"></div>
                </div>
            </div>

            {{-- Storage --}}
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-400">Storage</span>
                    <span class="text-white font-medium">{{ $subscription->storage_limit == -1 ? 'Unlimited' : $subscription->storage_limit . ' GB' }}</span>
                </div>
                <div class="h-2 rounded-full bg-white/10">
                    <div class="h-full rounded-full bg-sky-500 transition-all" style="width: 5%"></div>
                </div>
            </div>
        </div>

        {{-- Trial / Expired CTA --}}
        @if(in_array($subscription->status, ['trial', 'grace', 'expired', 'cancelled']))
        <div class="mt-6 pt-6 border-t border-white/10 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
            <div class="text-sm {{ $subscription->status === 'trial' ? 'text-blue-300' : 'text-red-300' }}">
                @if($subscription->status === 'trial')
                    🎉 Enjoying your trial? Upgrade to keep full access after it ends.
                @else
                    ⚠️ Upgrade now to restore full access.
                @endif
            </div>
            <a href="/billing/expired"
               class="btn-primary text-white font-bold text-sm px-6 py-2.5 rounded-xl flex-shrink-0 inline-block transition-all"
               style="background:linear-gradient(135deg,#6C63FF,#8b76ff);box-shadow:0 8px 30px rgba(108,99,255,0.4);">
                Upgrade Plan →
            </a>
        </div>
        @endif
    </div>
    @else
    <div class="glass rounded-2xl p-8 text-center">
        <div class="text-5xl mb-4">📭</div>
        <h2 class="text-xl font-bold text-white mb-2">No subscription found</h2>
        <p class="text-gray-400 text-sm mb-6">Get started with a 14-day free trial.</p>
        <a href="/billing/expired"
           class="inline-block text-white font-bold text-sm px-6 py-3 rounded-xl transition-all"
           style="background:linear-gradient(135deg,#6C63FF,#8b76ff);">
            Choose a Plan
        </a>
    </div>
    @endif

    {{-- Available Upgrades --}}
    @if($plans->isNotEmpty() && $subscription && $subscription->status !== 'active')
    <div>
        <h2 class="text-lg font-bold text-white mb-4">Available Plans</h2>
        <div class="grid grid-cols-1 sm:grid-cols-{{ min($plans->count(), 3) }} gap-4">
            @foreach($plans as $plan)
            <div class="glass rounded-xl p-5 {{ $plan->is_popular ? 'border-2 border-brand-500' : '' }} hover:-translate-y-1 transition-all">
                @if($plan->is_popular)
                <div class="text-xs font-bold text-brand-400 mb-2">⭐ Recommended</div>
                @endif
                <h3 class="font-bold text-white mb-1">{{ $plan->name }}</h3>
                <div class="flex items-baseline gap-1 mb-4">
                    <span class="text-2xl font-black text-white">${{ number_format($plan->price, 0) }}</span>
                    <span class="text-gray-400 text-xs">/month</span>
                </div>
                @if($plan->stripe_price_id)
                <form action="/billing/checkout" method="POST">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}" />
                    <button type="submit"
                            class="w-full text-white font-semibold text-sm px-4 py-2.5 rounded-xl transition-all {{ $plan->is_popular ? '' : 'border border-brand-500/40 hover:border-brand-500' }}"
                            @if($plan->is_popular) style="background:linear-gradient(135deg,#6C63FF,#8b76ff);" @endif>
                        Upgrade Now
                    </button>
                </form>
                @else
                <a href="mailto:support@velora.com?subject=Enterprise Plan Inquiry"
                   class="block w-full text-center border border-white/20 hover:border-white/40 text-gray-300 font-semibold text-sm px-4 py-2.5 rounded-xl transition-all">
                    Contact Sales
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Billing History --}}
    @if($invoices && $invoices->count() > 0)
    <div class="glass rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/10">
            <h2 class="font-bold text-white text-lg">Billing History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/5 text-gray-400 text-xs uppercase tracking-wider">
                        <th class="text-left px-6 py-3">Date</th>
                        <th class="text-left px-6 py-3">Plan</th>
                        <th class="text-left px-6 py-3">Amount</th>
                        <th class="text-left px-6 py-3">Method</th>
                        <th class="text-left px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($invoices as $inv)
                    <tr class="hover:bg-white/[0.02]">
                        <td class="px-6 py-3.5 text-gray-300">
                            {{ \Carbon\Carbon::parse($inv->created_at)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-3.5 text-white font-medium">{{ $inv->plan_name ?? '—' }}</td>
                        <td class="px-6 py-3.5 text-white font-bold">${{ number_format($inv->amount_paid, 2) }}</td>
                        <td class="px-6 py-3.5 text-gray-400 capitalize">{{ $inv->payment_method ?? '—' }}</td>
                        <td class="px-6 py-3.5">
                            <span class="text-xs px-2 py-1 rounded-full font-semibold
                                {{ in_array($inv->status, ['active','trial']) ? 'bg-green-500/20 text-green-300'
                                    : ($inv->status === 'expired' ? 'bg-red-500/20 text-red-300'
                                    : 'bg-gray-500/20 text-gray-400') }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
