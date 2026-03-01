@extends('super-admin.layout')

@section('title', 'Upgrade Request #' . $request->id)

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Upgrade Request #{{ $request->id }}</h1>
        <a href="{{ route('super-admin.upgrade-requests') }}" class="text-indigo-600 hover:text-indigo-700 font-medium dark:text-indigo-400">
            ← Back to Requests
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Request Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Status Banner -->
                    @if($request->status === 'pending')
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-yellow-800">Pending Review</h3>
                                    <p class="text-sm text-yellow-700 mt-1">This upgrade request is awaiting your approval.</p>
                                </div>
                            </div>
                        </div>
                    @elseif($request->status === 'approved')
                        <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-green-800">Approved</h3>
                                    <p class="text-sm text-green-700 mt-1">Processed on {{ $request->processed_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    @elseif($request->status === 'rejected')
                        <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-red-800">Rejected</h3>
                                    <p class="text-sm text-red-700 mt-1">Processed on {{ $request->processed_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Tenant Information -->
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Tenant Information</h2>
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Tenant ID</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $request->tenant_id }}</dd>
                            </div>
                            @if($tenant)
                                <div>
                                    <dt class="text-sm font-medium text-slate-600">Domain</dt>
                                    <dd class="mt-1 text-sm text-slate-900">{{ $tenant->id }}.{{ config('app.domain', 'booking-saas.test') }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Requested By</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    <div>{{ $request->requested_by_name }}</div>
                                    <div class="text-slate-500">{{ $request->requested_by_email }}</div>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-600">Request Date</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $request->created_at->format('M d, Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Plan Comparison -->
                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Plan Comparison</h2>
                        <div class="grid grid-cols-2 gap-6">
                            <!-- Current Plan -->
                            <div>
                                <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-3">Current Plan</h3>
                                <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4 dark:bg-slate-900">
                                    <div class="text-lg font-semibold text-slate-900 dark:text-white mb-2">{{ $request->currentPlan->name ?? 'N/A' }}</div>
                                    <div class="text-2xl font-bold text-slate-900 dark:text-white mb-4">${{ $request->currentPlan->price ?? '0' }}<span class="text-sm font-normal text-slate-500 dark:text-slate-400">/month</span></div>
                                    @if($request->currentPlan)
                                        <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                                            <li class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $request->currentPlan->max_users }} Users
                                            </li>
                                            <li class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $request->currentPlan->max_appointments }} Appointments/month
                                            </li>
                                            <li class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $request->currentPlan->storage_limit }}GB Storage
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            </div>

                            <!-- Requested Plan -->
                            <div>
                                <h3 class="text-sm font-medium text-slate-600 mb-3">Requested Plan</h3>
                                <div class="border-2 border-indigo-500 rounded-lg p-4 bg-indigo-50">
                                    <div class="text-lg font-semibold text-indigo-900 mb-2">{{ $request->requestedPlan->name ?? 'N/A' }}</div>
                                    <div class="text-2xl font-bold text-indigo-900 mb-4">${{ $request->requestedPlan->price ?? '0' }}<span class="text-sm font-normal text-indigo-600">/month</span></div>
                                    @if($request->requestedPlan)
                                        <ul class="space-y-2 text-sm text-indigo-800">
                                            <li class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $request->requestedPlan->max_users }} Users
                                            </li>
                                            <li class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $request->requestedPlan->max_appointments }} Appointments/month
                                            </li>
                                            <li class="flex items-center">
                                                <svg class="h-4 w-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {{ $request->requestedPlan->storage_limit }}GB Storage
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Request Message -->
                    @if($request->message)
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <h2 class="text-lg font-semibold text-slate-900 mb-4">Request Message</h2>
                            <p class="text-slate-700 whitespace-pre-wrap">{{ $request->message }}</p>
                        </div>
                    @endif

                    <!-- Admin Notes -->
                    @if($request->admin_notes)
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <h2 class="text-lg font-semibold text-slate-900 mb-4">Admin Notes</h2>
                            <p class="text-slate-700 whitespace-pre-wrap">{{ $request->admin_notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Right Column: Actions & Usage -->
                <div class="space-y-6">
                    <!-- Current Usage -->
                    @if($usage)
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <h2 class="text-lg font-semibold text-slate-900 mb-4">Current Usage</h2>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-slate-600">Total Users</dt>
                                    <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $usage['total_users'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-slate-600">Appointments This Month</dt>
                                    <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $usage['appointments_this_month'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-slate-600">Total Appointments</dt>
                                    <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $usage['total_appointments'] }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    <!-- Actions -->
                    @if($request->status === 'pending')
                        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                            <h2 class="text-lg font-semibold text-slate-900 mb-4">Actions</h2>

                            <!-- Approve Form -->
                            <form action="{{ route('super-admin.upgrade-requests.approve', $request->id) }}" method="POST" class="mb-4">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Start Date (Optional)</label>
                                    <input type="date" name="start_date" value="{{ date('Y-m-d') }}"
                                           class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Admin Notes (Optional)</label>
                                    <textarea name="admin_notes" rows="3"
                                              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                              placeholder="Add notes about this approval..."></textarea>
                                </div>
                                <button type="submit"
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                                    <svg class="inline-block h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Approve Upgrade
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form action="{{ route('super-admin.upgrade-requests.reject', $request->id) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to reject this upgrade request?');">
                                @csrf
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-2">Rejection Reason *</label>
                                    <textarea name="admin_notes" rows="3" required
                                              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                              placeholder="Explain why this request is being rejected..."></textarea>
                                </div>
                                <button type="submit"
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                                    <svg class="inline-block h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Reject Request
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
