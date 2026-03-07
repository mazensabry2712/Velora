@extends('super-admin.layout')

@section('title', __('super-admin.upgrade_requests_title'))
@section('breadcrumb')
    <a href="{{ route('super-admin.upgrade-requests') }}" class="text-slate-700 dark:text-slate-200 font-medium">
        {{ __('super-admin.upgrade_requests_title') }}
    </a>
@endsection

@section('content')
<div>

    <!-- Page Header -->
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-start">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                {{ __('super-admin.upgrade_requests_title') }}
                @if($counts['pending'] > 0)
                    <span class="text-sm font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 px-2.5 py-0.5 rounded-full">
                        {{ $counts['pending'] }} {{ __('super-admin.upgrade_pending_badge') }}
                    </span>
                @endif
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">{{ __('super-admin.upgrade_requests_subtitle') }}</p>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Total -->
        <a href="{{ route('super-admin.upgrade-requests') }}"
           class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-md p-5 border border-slate-200 dark:border-slate-700 transition-all hover:-translate-y-0.5
                  {{ $statusFilter === 'all' ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_stat_total') }}</p>
            <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ $counts['total'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.upgrade_stat_all_time') }}</p>
        </a>

        <!-- Pending -->
        <a href="{{ route('super-admin.upgrade-requests', ['status' => 'pending']) }}"
           class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-md p-5 border border-slate-200 dark:border-slate-700 transition-all hover:-translate-y-0.5
                  {{ $statusFilter === 'pending' ? 'ring-2 ring-amber-500' : '' }}">
            <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1">
                @if($counts['pending'] > 0)
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse inline-block"></span>
                @endif
                {{ __('super-admin.upgrade_stat_pending') }}
            </p>
            <p class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $counts['pending'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.upgrade_stat_awaiting') }}</p>
        </a>

        <!-- Approved -->
        <a href="{{ route('super-admin.upgrade-requests', ['status' => 'approved']) }}"
           class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-md p-5 border border-slate-200 dark:border-slate-700 transition-all hover:-translate-y-0.5
                  {{ $statusFilter === 'approved' ? 'ring-2 ring-emerald-500' : '' }}">
            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ __('super-admin.upgrade_stat_approved') }}</p>
            <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $counts['approved'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.upgrade_stat_completed') }}</p>
        </a>

        <!-- Rejected -->
        <a href="{{ route('super-admin.upgrade-requests', ['status' => 'rejected']) }}"
           class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-md p-5 border border-slate-200 dark:border-slate-700 transition-all hover:-translate-y-0.5
                  {{ $statusFilter === 'rejected' ? 'ring-2 ring-red-500' : '' }}">
            <p class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider">{{ __('super-admin.upgrade_stat_rejected') }}</p>
            <p class="text-3xl font-black text-red-600 dark:text-red-400 mt-1">{{ $counts['rejected'] }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.upgrade_stat_declined') }}</p>
        </a>
    </div>

    <!-- Table Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

        <!-- Table Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between">
            <h2 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                {{ __('super-admin.upgrade_all_requests') }}
                <span class="text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-full">
                    {{ $requests->total() }}
                </span>
            </h2>

            <!-- Status filter pills -->
            <div class="flex flex-wrap gap-2">
                @foreach([
                    'all'      => __('super-admin.upgrade_filter_all'),
                    'pending'  => __('super-admin.upgrade_filter_pending'),
                    'approved' => __('super-admin.upgrade_filter_approved'),
                    'rejected' => __('super-admin.upgrade_filter_rejected'),
                ] as $val => $label)
                    <a href="{{ route('super-admin.upgrade-requests', $val !== 'all' ? ['status' => $val] : []) }}"
                       class="px-3 py-1 rounded-full text-xs font-semibold transition-all
                              {{ $statusFilter === $val
                                  ? ($val === 'pending'  ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'
                                  : ($val === 'approved' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300'
                                  : ($val === 'rejected' ? 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300'
                                                         : 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300')))
                                  : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/40">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_col_tenant') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_col_current_plan') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_col_requested_plan') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_col_requested_by') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_col_status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_col_date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.upgrade_col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($requests as $req)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                            <!-- Tenant -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                        <span class="text-white text-xs font-bold">{{ strtoupper(substr($req->tenant_id, 0, 1)) }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $req->tenant_id }}</span>
                                </div>
                            </td>

                            <!-- Current Plan -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    {{ $req->currentPlan->name ?? 'N/A' }}
                                </span>
                                @if($req->currentPlan)
                                    <div class="text-xs text-slate-400 mt-1">${{ number_format($req->currentPlan->price, 2) }}/mo</div>
                                @endif
                            </td>

                            <!-- Requested Plan -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    </svg>
                                    {{ $req->requestedPlan->name ?? 'N/A' }}
                                </span>
                                @if($req->requestedPlan)
                                    <div class="text-xs text-indigo-400 mt-1">${{ number_format($req->requestedPlan->price, 2) }}/mo</div>
                                @endif
                            </td>

                            <!-- Requested By -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $req->requested_by_name }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ $req->requested_by_email }}</div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($req->status === 'pending')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                                        <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                                        {{ __('super-admin.upgrade_status_pending') }}
                                    </span>
                                @elseif($req->status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ __('super-admin.upgrade_status_approved') }}
                                    </span>
                                @elseif($req->status === 'rejected')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        {{ __('super-admin.upgrade_status_rejected') }}
                                    </span>
                                @endif
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900 dark:text-white">{{ $req->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-400">{{ $req->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('super-admin.upgrade-requests.show', $req->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all
                                          {{ $req->status === 'pending'
                                              ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                                              : 'bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300' }}">
                                    @if($req->status === 'pending')
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ __('super-admin.upgrade_action_review') }}
                                    @else
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        {{ __('super-admin.upgrade_action_view') }}
                                    @endif
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-slate-400 dark:text-slate-500">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('super-admin.upgrade_empty_title') }}</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('super-admin.upgrade_empty_sub') }}</p>
                                    </div>
                                    @if($statusFilter !== 'all')
                                        <a href="{{ route('super-admin.upgrade-requests') }}"
                                           class="mt-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                            {{ __('super-admin.upgrade_view_all') }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($requests->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
