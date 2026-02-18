@extends('layouts.admin')

@section('title', __('Billing History'))
@section('subtitle', __('View your subscription and payment history'))

@section('content')
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
        <div class="p-6 border-b border-slate-100 dark:border-slate-700">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ __('Subscription History') }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ __('Plan') }}
                        </th>
                        <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ __('Amount') }}
                        </th>
                        <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ __('Start Date') }}
                        </th>
                        <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ __('End Date') }}
                        </th>
                        <th class="px-6 py-3 text-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ __('Payment Method') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ $subscription->plan_name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'trial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'expired' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    'cancelled' => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-400'
                                ];
                                $statusClass = $statusColors[$subscription->status] ?? 'bg-slate-100 text-slate-800';
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-900 dark:text-slate-100">
                            ${{ number_format($subscription->amount_paid ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ \Carbon\Carbon::parse($subscription->starts_at)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $subscription->ends_at ? \Carbon\Carbon::parse($subscription->ends_at)->format('M d, Y') : __('N/A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                            {{ $subscription->payment_method ?? __('N/A') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-slate-500 dark:text-slate-400">{{ __('No billing history found') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
