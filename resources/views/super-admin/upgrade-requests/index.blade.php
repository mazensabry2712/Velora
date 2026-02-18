<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Requests - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-slate-900">Upgrade Requests</h1>
                    <a href="/super-admin/dashboard" class="text-indigo-600 hover:text-indigo-700 font-medium">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
                    <div class="text-sm text-slate-600 mb-1">Total Requests</div>
                    <div class="text-3xl font-bold text-slate-900">{{ $requests->total() }}</div>
                </div>
                <div class="bg-yellow-50 rounded-lg shadow-sm p-6 border border-yellow-200">
                    <div class="text-sm text-yellow-800 mb-1">Pending</div>
                    <div class="text-3xl font-bold text-yellow-900">
                        {{ $requests->where('status', 'pending')->count() }}
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg shadow-sm p-6 border border-green-200">
                    <div class="text-sm text-green-800 mb-1">Approved</div>
                    <div class="text-3xl font-bold text-green-900">
                        {{ $requests->where('status', 'approved')->count() }}
                    </div>
                </div>
                <div class="bg-red-50 rounded-lg shadow-sm p-6 border border-red-200">
                    <div class="text-sm text-red-800 mb-1">Rejected</div>
                    <div class="text-3xl font-bold text-red-900">
                        {{ $requests->where('status', 'rejected')->count() }}
                    </div>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Tenant
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Current Plan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Requested Plan
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Requested By
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($requests as $request)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900">{{ $request->tenant_id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900">{{ $request->currentPlan->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">${{ $request->currentPlan->price ?? '0' }}/mo</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-indigo-600">{{ $request->requestedPlan->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">${{ $request->requestedPlan->price ?? '0' }}/mo</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-900">{{ $request->requested_by_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $request->requested_by_email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($request->status === 'pending')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    @elseif($request->status === 'approved')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Approved
                                        </span>
                                    @elseif($request->status === 'rejected')
                                        <span class="px-2 py-1 text-xs font-semibibold rounded-full bg-red-100 text-red-800">
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ $request->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('super-admin.upgrade-requests.show', $request->id) }}"
                                       class="text-indigo-600 hover:text-indigo-900 font-medium">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-slate-400">
                                        <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No upgrade requests found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                @if($requests->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $requests->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
