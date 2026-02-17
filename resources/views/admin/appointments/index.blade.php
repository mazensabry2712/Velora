<!DOCTYPE html>
@php
    $isArabic = app()->getLocale() === 'ar';
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isArabic ? 'إدارة المواعيد' : 'Manage Appointments' }} - {{ tenant()->name }}</title>
    <!-- Apply dark mode IMMEDIATELY to prevent flash -->
    <script>
        // This runs BEFORE anything renders
        (function() {
            if (localStorage.getItem('darkMode') === 'true' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <style>
        .status-badge { @apply px-2 py-1 text-xs font-semibold rounded-full cursor-pointer transition-all; }
        .status-pending { @apply bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900 dark:text-amber-300; }
        .status-confirmed { @apply bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900 dark:text-emerald-300; }
        .status-cancelled { @apply bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-300; }
        .status-completed { @apply bg-cyan-100 text-cyan-800 hover:bg-cyan-200 dark:bg-cyan-900 dark:text-cyan-300; }
        .stat-card { @apply bg-white rounded-xl shadow-sm border border-slate-100 p-4 hover:shadow-md transition-shadow dark:bg-slate-800 dark:border-slate-700; }
        .filter-input {
            @apply px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100;
        }

        /* Force Dark Mode for Inputs - Override Browser Defaults */
        .dark .filter-input {
            background-color: #334155 !important;
            color: #f1f5f9 !important;
            border-color: #475569 !important;
        }

        /* Placeholders & Options */
        select option {
            background-color: white;
            color: #1e293b;
        }
        .dark select option {
            background-color: #334155;
            color: #e2e8f0;
        }
        .dark select option {
            background-color: #334155;
            color: #e2e8f0;
        }
        input::placeholder,
        textarea::placeholder,
        select::placeholder {
            opacity: 0.6;
            color: #64748b;
        }
        .dark input::placeholder,
        .dark textarea::placeholder,
        .dark select::placeholder {
            opacity: 0.8;
            color: #cbd5e1;
        }

        /* Loading Indicator */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(99, 102, 241, 0.2);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Modal Animations */
        #addModal, #editModal, #statsModal {
            transition: opacity 300ms ease-in-out;
        }
        #addModal .relative, #editModal .relative, #statsModal .relative {
            transition: transform 300ms ease-in-out;
        }
        .scale-95 {
            transform: scale(0.95);
        }
        .scale-100 {
            transform: scale(1);
        }

        /* Stats Modal Grid - Responsive */
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(1, 1fr);
            }
        }
        @media (min-width: 641px) and (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1025px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Table Responsive */
        @media (max-width: 768px) {
            .hide-mobile { display: none; }
            table th, table td { padding: 0.5rem !important; font-size: 0.75rem !important; }
        }

        /* Custom Scrollbar */
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Smooth Focus Rings */
        input:focus, select:focus, textarea:focus {
            outline: none;
        }

        /* Pulse animation for stats button */
        @keyframes pulse-ring {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }
        .pulse-on-update {
            animation: pulse-ring 0.6s ease-in-out;
        }

        /* Back to Top Button */
        #backToTop {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 50;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
        }
        #backToTop.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Swipe Hint for Mobile */
        @keyframes swipe-hint {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(-10px); }
        }
        .swipe-hint {
            animation: swipe-hint 2s ease-in-out infinite;
        }

        /* Skeleton Loading */
        @keyframes skeleton-loading {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #e2e8f0 0px, #f1f5f9 40px, #e2e8f0 80px);
            background-size: 200px;
            animation: skeleton-loading 1.4s ease-in-out infinite;
        }

        /* Sticky Bulk Actions */
        .sticky-bulk-bar {
            position: sticky;
            bottom: 0;
            z-index: 40;
            backdrop-filter: blur(10px);
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Mobile Filters Toggle */
        @media (max-width: 768px) {
            #filtersContainer.collapsed {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }
            #filtersContainer.expanded {
                max-height: 1000px;
                transition: max-height 0.5s ease;
            }
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900">
    @include('partials.admin-nav')

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="spinner"></div>
    </div>

    <!-- Statistics Modal -->
    <div id="statsModal" class="hidden fixed inset-0 bg-slate-900 bg-opacity-50 z-50 overflow-y-auto backdrop-blur-sm" onclick="closeStatsModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-6xl w-full mx-auto overflow-hidden" onclick="event.stopPropagation()">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $isArabic ? 'إحصائيات المواعيد' : 'Appointments Statistics' }}</h3>
                            <p class="text-sm text-indigo-100">{{ $isArabic ? 'ملخص شامل للأداء والإحصائيات' : 'Complete performance overview' }}</p>
                        </div>
                    </div>
                    <button onclick="closeStatsModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6 bg-slate-50 dark:bg-slate-800 max-h-[80vh] overflow-y-auto">
                    <!-- Main Statistics Grid -->
                    <div class="stats-grid grid gap-4 mb-6">
                        <!-- Today's Appointments -->
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900 dark:to-indigo-800 rounded-xl shadow-sm border border-indigo-200 dark:border-indigo-700 p-5 hover:shadow-md transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-indigo-500 rounded-xl flex items-center justify-center shadow-md">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-3xl font-bold text-indigo-900 dark:text-indigo-100">{{ $stats['today'] ?? 0 }}</p>
                                    <p class="text-sm text-indigo-700 dark:text-indigo-300 font-medium">{{ $isArabic ? 'مواعيد اليوم' : "Today's Appointments" }}</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-indigo-200 dark:border-indigo-700 flex items-center justify-between">
                                <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">{{ $isArabic ? 'مؤكد' : 'Confirmed' }}</span>
                                <span class="text-sm font-bold text-indigo-900 dark:text-indigo-100">{{ $stats['today_confirmed'] ?? 0 }}</span>
                            </div>
                        </div>

                        <!-- Pending -->
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900 dark:to-amber-800 rounded-xl shadow-sm border border-amber-200 dark:border-amber-700 p-5 hover:shadow-md transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-amber-500 rounded-xl flex items-center justify-center shadow-md">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ $stats['pending'] ?? 0 }}</p>
                                    <p class="text-sm text-amber-700 dark:text-amber-300 font-medium">{{ $isArabic ? 'قيد الانتظار' : 'Pending' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-amber-200 dark:border-amber-700">
                                <span class="text-xs text-amber-600 dark:text-amber-400">{{ $isArabic ? 'يحتاج تأكيد' : 'needs confirmation' }}</span>
                            </div>
                        </div>

                        <!-- This Week -->
                        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900 dark:to-emerald-800 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-700 p-5 hover:shadow-md transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-emerald-500 rounded-xl flex items-center justify-center shadow-md">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-3xl font-bold text-emerald-900 dark:text-emerald-100">{{ $stats['this_week'] ?? 0 }}</p>
                                    <p class="text-sm text-emerald-700 dark:text-emerald-300 font-medium">{{ $isArabic ? 'هذا الأسبوع' : 'This Week' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-emerald-200 dark:border-emerald-800">
                                <span class="text-xs text-emerald-600 dark:text-emerald-400">{{ $isArabic ? 'موعد' : 'appointments' }}</span>
                            </div>
                        </div>

                        <!-- Cancelled -->
                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900 dark:to-red-800 rounded-xl shadow-sm border border-red-200 dark:border-red-700 p-5 hover:shadow-md transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-red-500 rounded-xl flex items-center justify-center shadow-md">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-3xl font-bold text-red-900 dark:text-red-100">{{ $stats['cancelled_month'] ?? 0 }}</p>
                                    <p class="text-sm text-red-700 dark:text-red-300 font-medium">{{ $isArabic ? 'ملغي' : 'Cancelled' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-red-200 dark:border-red-700">
                                <span class="text-xs text-red-600 dark:text-red-400">{{ $isArabic ? 'هذا الشهر' : 'this month' }}</span>
                            </div>
                        </div>

                        <!-- In Queue -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 rounded-xl shadow-sm border border-purple-200 dark:border-purple-700 p-5 hover:shadow-md transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-purple-500 rounded-xl flex items-center justify-center shadow-md">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ $stats['in_queue'] ?? 0 }}</p>
                                    <p class="text-sm text-purple-700 dark:text-purple-300 font-medium">{{ $isArabic ? 'في الطابور' : 'In Queue' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-purple-200">
                                <span class="text-xs text-purple-600">{{ $isArabic ? 'في الانتظار/الخدمة' : 'waiting/serving' }}</span>
                            </div>
                        </div>

                        <!-- Revenue -->
                        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl shadow-sm border border-emerald-200 p-5 hover:shadow-md transition-all">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-emerald-500 rounded-xl flex items-center justify-center shadow-md">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    @php
                                        $expectedRevenue = $appointments->where('status', '!=', 'cancelled')->sum(function($app) {
                                            return $app->service?->price ?? 0;
                                        });
                                    @endphp
                                    <p class="text-3xl font-bold text-emerald-900">{{ number_format($expectedRevenue) }}</p>
                                    <p class="text-sm text-emerald-700 font-medium">{{ $isArabic ? 'إيرادات متوقعة' : 'Revenue' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-emerald-200">
                                <span class="text-xs text-emerald-600">{{ $isArabic ? 'ج.م' : 'EGP' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Analytics -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-5 mb-4">
                        <h4 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ $isArabic ? 'تحليلات متقدمة' : 'Advanced Analytics' }}
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- No-Show Rate -->
                            <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900 dark:to-red-900 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $isArabic ? 'معدل عدم الحضور' : 'No-Show Rate' }}</span>
                                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['no_show_rate'] ?? 0 }}%</p>
                                <p class="text-xs text-slate-500 dark:text-slate-300 mt-1">{{ $isArabic ? 'من إجمالي المواعيد السابقة' : 'of past appointments' }}</p>
                            </div>

                            <!-- Average Daily -->
                            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900 dark:to-indigo-800 rounded-lg p-4 border border-indigo-200 dark:border-indigo-700">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-slate-600 dark:text-slate-300">{{ $isArabic ? 'متوسط يومي' : 'Daily Average' }}</span>
                                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-800 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['avg_daily'] ?? 0 }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-300 mt-1">{{ $isArabic ? 'مواعيد في اليوم (هذا الشهر)' : 'appointments per day (month)' }}</p>
                            </div>

                            <!-- Top Services -->
                            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900 dark:to-emerald-800 rounded-lg p-4 border border-emerald-200 dark:border-emerald-700">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $isArabic ? 'أكثر الخدمات طلباً' : 'Top Services' }}</span>
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                <div class="space-y-2">
                                    @forelse($stats['top_services'] ?? [] as $service)
                                        <div class="flex items-center justify-between bg-white dark:bg-slate-700 bg-opacity-50 rounded px-2 py-1">
                                            <span class="text-xs text-slate-600 dark:text-slate-300 truncate">{{ $service->service_type }}</span>
                                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 ml-2">{{ $service->total }}</span>
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-400 dark:text-slate-400 text-center py-2">{{ $isArabic ? 'لا توجد بيانات' : 'No data' }}</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex gap-3 justify-end">
                        <a href="{{ route('admin.reports') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{ $isArabic ? 'تقرير تفصيلي' : 'Detailed Report' }}
                        </a>
                        <button onclick="closeStatsModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                            {{ $isArabic ? 'إغلاق' : 'Close' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTop" onclick="scrollToTop()" class="w-12 h-12 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full shadow-lg hover:shadow-xl flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </button>

    <!-- Page Header -->
    <header class="bg-white dark:bg-slate-800 border-b dark:border-slate-700 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $isArabic ? 'إدارة المواعيد' : 'Manage Appointments' }}</h2>
                    <div class="flex items-center gap-4 mt-2 flex-wrap">
                        <p class="text-sm text-slate-500 dark:text-slate-300">{{ $isArabic ? 'عرض وإدارة جميع المواعيد' : 'View and manage all appointments' }}</p>
                        <!-- Quick Stats -->
                        <div class="hidden lg:flex items-center gap-3 text-xs">
                            <span class="px-2 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-lg font-semibold">
                                📅 {{ $isArabic ? 'اليوم' : 'Today' }}: {{ $stats['today'] ?? 0 }}
                            </span>
                            <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 rounded-lg font-semibold">
                                ⏳ {{ $isArabic ? 'معلق' : 'Pending' }}: {{ $stats['pending'] ?? 0 }}
                            </span>
                            <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 rounded-lg font-semibold">
                                📊 {{ $isArabic ? 'الأسبوع' : 'Week' }}: {{ $stats['this_week'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <!-- Statistics Button with Badge -->
                    <button onclick="openStatsModal()" id="statsButton" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 text-sm font-medium shadow-md hover:shadow-lg transition-all relative">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="hidden sm:inline">{{ $isArabic ? 'الإحصائيات' : 'Statistics' }}</span>
                        <span class="inline sm:hidden">📊</span>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">{{ $stats['pending'] ?? 0 }}</span>
                    </button>

                    <button onclick="toggleView('grouped')" id="groupedViewBtn" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        <span class="hidden sm:inline">{{ $isArabic ? 'حسب اليوم' : 'By Day' }}</span>
                    </button>
                    <button onclick="toggleView('calendar')" id="calendarViewBtn" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">{{ $isArabic ? 'تقويم' : 'Calendar' }}</span>
                    </button>
                    <button onclick="openAddModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-sm shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden sm:inline">{{ $isArabic ? 'موعد جديد' : 'New' }}</span>
                        <span class="inline sm:hidden">+</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Calendar View (Initially Hidden) -->
        <div id="calendarView" class="hidden mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-slate-100">{{ $isArabic ? 'عرض التقويم' : 'Calendar View' }}</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-300">{{ $isArabic ? 'قريباً: عرض تقويم تفاعلي للمواعيد' : 'Coming soon: Interactive calendar view of appointments' }}</p>
                    <button onclick="toggleView('list')" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                        </svg>
                        {{ $isArabic ? 'عرض القائمة' : 'List View' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Grouped by Day View (Initially Hidden) -->
        <div id="groupedView" class="hidden mb-6">
            @if($appointmentsByDate && $appointmentsByDate->count() > 0)
                <div class="space-y-4">
                    <!-- Summary Stats Card -->
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 rounded-xl shadow-lg p-6 text-white">
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="text-center">
                                <div class="text-3xl font-bold">{{ $appointmentsByDate->count() }}</div>
                                <div class="text-sm opacity-90 mt-1">{{ $isArabic ? 'يوم' : 'Days' }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold">{{ $appointmentsByDate->sum('total') }}</div>
                                <div class="text-sm opacity-90 mt-1">{{ $isArabic ? 'موعد' : 'Total' }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold">{{ $appointmentsByDate->sum('confirmed') }}</div>
                                <div class="text-sm opacity-90 mt-1">{{ $isArabic ? 'مؤكد' : 'Confirmed' }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold">{{ $appointmentsByDate->sum('pending') }}</div>
                                <div class="text-sm opacity-90 mt-1">{{ $isArabic ? 'معلق' : 'Pending' }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold">{{ number_format($appointmentsByDate->sum('revenue')) }}</div>
                                <div class="text-sm opacity-90 mt-1">{{ $isArabic ? 'ج.م' : 'EGP' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters Indicators -->
                    @if(isset($activeFilters) && count($activeFilters) > 0)
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4">
                            <div class="flex items-center gap-2 flex-wrap">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                <span class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ $isArabic ? 'الفلاتر النشطة:' : 'Active Filters:' }}</span>
                                @foreach($activeFilters as $key => $value)
                                    <span class="px-2 py-1 bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-300 rounded text-xs font-medium">
                                        {{ ucfirst($key) }}: {{ $value }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Controls -->
                    <div class="flex justify-between items-center">
                        <div class="flex gap-2">
                            <button onclick="expandAllDays()" class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                                </svg>
                                {{ $isArabic ? 'فتح الكل' : 'Expand All' }}
                            </button>
                            <button onclick="collapseAllDays()" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-50 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 text-sm transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                {{ $isArabic ? 'إغلاق الكل' : 'Collapse All' }}
                            </button>
                        </div>
                        <button onclick="toggleView('list')" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 text-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            </svg>
                            {{ $isArabic ? 'عرض القائمة' : 'List View' }}
                        </button>
                    </div>

                    @foreach($appointmentsByDate as $dayData)
                        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden {{ $dayData['is_today'] ? 'ring-2 ring-indigo-500 dark:ring-indigo-600' : '' }}">
                            <!-- Day Header (Clickable) -->
                            <button onclick="toggleDay('{{ $dayData['date'] }}')"
                                    class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors {{ $dayData['is_past'] ? 'bg-slate-50 dark:bg-slate-700/30' : ($dayData['is_today'] ? 'bg-indigo-50 dark:bg-indigo-900/30' : '') }}">
                                <div class="flex items-center gap-4 flex-wrap">
                                    <!-- Date Info -->
                                    <div class="{{ $isArabic ? 'text-right' : 'text-left' }}">
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                            {{ $dayData['date_formatted'] }}
                                            @if($dayData['is_today'])
                                                <span class="px-2 py-0.5 bg-indigo-500 dark:bg-indigo-600 text-white text-xs rounded-full font-semibold">
                                                    {{ $isArabic ? 'اليوم' : 'Today' }}
                                                </span>
                                            @elseif($dayData['is_tomorrow'])
                                                <span class="px-2 py-0.5 bg-emerald-500 dark:bg-emerald-600 text-white text-xs rounded-full font-semibold">
                                                    {{ $isArabic ? 'غداً' : 'Tomorrow' }}
                                                </span>
                                            @elseif($dayData['is_past'])
                                                <span class="px-2 py-0.5 bg-slate-500 dark:bg-slate-600 text-white text-xs rounded-full font-semibold">
                                                    {{ $isArabic ? 'سابق' : 'Past' }}
                                                </span>
                                            @endif
                                        </h3>
                                        <p class="text-sm text-slate-500 dark:text-slate-300 mt-1">{{ $dayData['diff_humans'] }}</p>
                                    </div>

                                    <!-- Stats Badges with Percentages -->
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-3 py-1 bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 text-slate-700 dark:text-slate-300 rounded-full text-xs font-semibold flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $dayData['total'] }} {{ $isArabic ? 'موعد' : 'appts' }}
                                        </span>
                                        @if($dayData['confirmed'] > 0)
                                            <span class="px-3 py-1 bg-gradient-to-r from-emerald-100 to-emerald-50 dark:from-emerald-900 dark:to-emerald-800 text-emerald-700 dark:text-emerald-300 rounded-full text-xs font-semibold" title="{{ $dayData['confirmed_percent'] }}%">
                                                ✓ {{ $dayData['confirmed'] }} <span class="opacity-75">({{ $dayData['confirmed_percent'] }}%)</span>
                                            </span>
                                        @endif
                                        @if($dayData['pending'] > 0)
                                            <span class="px-3 py-1 bg-gradient-to-r from-yellow-100 to-yellow-50 text-yellow-700 rounded-full text-xs font-semibold" title="{{ $dayData['pending_percent'] }}%">
                                                ⏱ {{ $dayData['pending'] }} <span class="opacity-75">({{ $dayData['pending_percent'] }}%)</span>
                                            </span>
                                        @endif
                                        @if($dayData['completed'] > 0)
                                            <span class="px-3 py-1 bg-gradient-to-r from-cyan-100 to-cyan-50 dark:from-cyan-900 dark:to-cyan-800 text-cyan-700 dark:text-cyan-300 rounded-full text-xs font-semibold" title="{{ $dayData['completed_percent'] }}%">
                                                ✔ {{ $dayData['completed'] }} <span class="opacity-75">({{ $dayData['completed_percent'] }}%)</span>
                                            </span>
                                        @endif
                                        @if($dayData['cancelled'] > 0)
                                            <span class="px-3 py-1 bg-gradient-to-r from-red-100 to-red-50 text-red-700 rounded-full text-xs font-semibold" title="{{ $dayData['cancelled_percent'] }}%">
                                                ✗ {{ $dayData['cancelled'] }} <span class="opacity-75">({{ $dayData['cancelled_percent'] }}%)</span>
                                            </span>
                                        @endif
                                        @if($dayData['revenue'] > 0)
                                            <span class="px-3 py-1 bg-gradient-to-r from-emerald-100 to-emerald-50 text-emerald-700 rounded-full text-xs font-semibold flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                {{ number_format($dayData['revenue']) }} {{ $isArabic ? 'ج.م' : 'EGP' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Toggle Icon -->
                                <svg id="icon-{{ $dayData['date'] }}" class="w-5 h-5 text-slate-400 dark:text-slate-400 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- Progress Bar -->
                            <div class="px-6 py-2 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-100 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 transition-all duration-500" style="width: {{ $dayData['progress_percent'] }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $dayData['progress_percent'] }}%</span>
                                </div>
                            </div>

                            <!-- Quick Actions Bar -->
                            @if($dayData['pending'] > 0 || $dayData['confirmed'] > 0)
                                <div class="px-6 py-3 bg-gradient-to-r from-slate-50 to-white dark:from-slate-800 dark:to-slate-800 border-t border-slate-100 dark:border-slate-700 flex gap-2">
                                    @if($dayData['pending'] > 0)
                                        <button onclick="bulkDayAction('confirm_all', '{{ $dayData['date'] }}', {{ json_encode($dayData['appointment_ids']) }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 text-white rounded-lg hover:from-emerald-600 hover:to-emerald-700 dark:hover:from-emerald-700 dark:hover:to-emerald-800 text-xs font-medium transition-all shadow-sm hover:shadow">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ $isArabic ? 'تأكيد الكل' : 'Confirm All' }}
                                        </button>
                                    @endif
                                    @if($dayData['confirmed'] > 0 || $dayData['pending'] > 0)
                                        <button onclick="bulkDayAction('complete_all', '{{ $dayData['date'] }}', {{ json_encode($dayData['appointment_ids']) }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-cyan-500 to-cyan-600 dark:from-cyan-600 dark:to-cyan-700 text-white rounded-lg hover:from-cyan-600 hover:to-cyan-700 dark:hover:from-cyan-700 dark:hover:to-cyan-800 text-xs font-medium transition-all shadow-sm hover:shadow">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $isArabic ? 'إكمال الكل' : 'Complete All' }}
                                        </button>
                                    @endif
                                </div>
                            @endif

                            <!-- Day Content (Collapsible with Lazy Loading) -->
                            <div id="content-{{ $dayData['date'] }}" class="hidden border-t border-slate-100 dark:border-slate-700" data-loaded="false">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                        <thead class="bg-slate-50 dark:bg-slate-700">
                                            <tr>
                                                <th class="px-6 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">#</th>
                                                <th class="px-6 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">{{ $isArabic ? 'الوقت' : 'Time' }}</th>
                                                <th class="px-6 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">{{ $isArabic ? 'العميل' : 'Customer' }}</th>
                                                <th class="px-6 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">{{ $isArabic ? 'الموظف' : 'Staff' }}</th>
                                                <th class="px-6 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">{{ $isArabic ? 'الخدمة' : 'Service' }}</th>
                                                <th class="px-6 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">{{ $isArabic ? 'الحالة' : 'Status' }}</th>
                                                <th class="px-6 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">{{ $isArabic ? 'الإجراءات' : 'Actions' }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                                            @foreach($dayData['appointments'] as $appointment)
                                                @php
                                                    $rowClass = 'hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors';
                                                    if($appointment->customer?->is_vip) {
                                                        $rowClass .= ' bg-amber-50 dark:bg-amber-900/20';
                                                    } elseif($appointment->status === 'cancelled') {
                                                        $rowClass .= ' bg-red-50 dark:bg-red-900/20';
                                                    } elseif($appointment->status === 'completed') {
                                                        $rowClass .= ' bg-emerald-50 dark:bg-emerald-900/20';
                                                    } elseif($appointment->date < now() && $appointment->status !== 'completed') {
                                                        $rowClass .= ' bg-orange-50 dark:bg-orange-900/20';
                                                    }
                                                @endphp
                                                <tr class="{{ $rowClass }}">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-300">{{ $loop->iteration }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $appointment->time_slot }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div>
                                                                <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $appointment->customer?->name ?? 'N/A' }}</div>
                                                                <div class="text-xs text-slate-500 dark:text-slate-300">{{ $appointment->customer?->phone ?? 'N/A' }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">{{ $appointment->staff?->name ?? ($isArabic ? 'غير محدد' : 'N/A') }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-300">{{ $appointment->service_type ?? '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-3 py-1.5 inline-flex items-center gap-1.5 text-xs leading-5 font-semibold rounded-full
                                                        @if($appointment->status === 'pending') bg-gradient-to-r from-amber-100 to-amber-50 dark:from-amber-900 dark:to-amber-800 text-amber-800 dark:text-amber-300
                                                        @elseif($appointment->status === 'confirmed') bg-gradient-to-r from-emerald-100 to-emerald-50 dark:from-emerald-900 dark:to-emerald-800 text-emerald-800 dark:text-emerald-300
                                                        @elseif($appointment->status === 'completed') bg-gradient-to-r from-cyan-100 to-cyan-50 dark:from-cyan-900 dark:to-cyan-800 text-cyan-800 dark:text-cyan-300
                                                        @elseif($appointment->status === 'cancelled') bg-gradient-to-r from-red-100 to-red-50 dark:from-red-900 dark:to-red-800 text-red-800 dark:text-red-300
                                                        @else bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-800 text-slate-800 dark:text-slate-300
                                                        @endif">
                                                            <span class="w-1.5 h-1.5 rounded-full
                                                            @if($appointment->status === 'pending') bg-amber-500
                                                            @elseif($appointment->status === 'confirmed') bg-emerald-500
                                                            @elseif($appointment->status === 'completed') bg-cyan-500
                                                            @elseif($appointment->status === 'cancelled') bg-red-500
                                                            @else bg-slate-500 dark:bg-slate-600
                                                            @endif"></span>
                                                            @if($isArabic)
                                                                @if($appointment->status === 'pending') معلق
                                                                @elseif($appointment->status === 'confirmed') مؤكد
                                                                @elseif($appointment->status === 'completed') مكتمل
                                                                @elseif($appointment->status === 'cancelled') ملغي
                                                                @else {{ $appointment->status }}
                                                                @endif
                                                            @else
                                                                {{ ucfirst($appointment->status) }}
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                        <div class="flex items-center gap-2">
                                                            <button onclick="viewAppointment({{ $appointment->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 text-indigo-700 dark:text-indigo-400 rounded-lg hover:from-indigo-100 hover:to-indigo-200 dark:hover:from-indigo-800/40 dark:hover:to-indigo-700/40 text-xs transition-all">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                                </svg>
                                                            </button>
                                                            <button onclick="editAppointment({{ $appointment->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 text-emerald-700 dark:text-emerald-400 rounded-lg hover:from-emerald-100 hover:to-emerald-200 dark:hover:from-emerald-800/40 dark:hover:to-emerald-700/40 text-xs transition-all">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-slate-100">{{ $isArabic ? 'لا توجد مواعيد' : 'No appointments' }}</h3>
                    </div>
                </div>
            @endif
        </div>

        <!-- List View (Default) -->
        <div id="listView">

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6">
            <!-- Mobile Filter Toggle -->
            <div class="lg:hidden px-4 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                <button type="button" onclick="toggleFilters()" class="w-full flex items-center justify-between">
                    <span class="font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        {{ $isArabic ? 'الفلاتر' : 'Filters' }}
                    </span>
                    <svg id="filterToggleIcon" class="w-5 h-5 text-slate-500 dark:text-slate-300 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>

            <div id="filtersContainer" class="expanded p-4">
            <form method="GET" action="{{ route('admin.appointments') }}" id="filterForm">
                <!-- Row 1: Search, Period, Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'بحث' : 'Search' }}</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="{{ $isArabic ? 'اسم أو هاتف...' : 'Name or phone...' }}"
                                   class="filter-input w-full {{ $isArabic ? 'pr-10' : 'pl-10' }}">
                            <svg class="w-4 h-4 text-slate-400 dark:text-slate-400 absolute top-1/2 -translate-y-1/2 {{ $isArabic ? 'right-3' : 'left-3' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'الفترة' : 'Period' }}</label>
                        <select name="date_filter" class="filter-input w-full" onchange="toggleCustomDate(this)">
                            <option value="">{{ $isArabic ? 'الكل' : 'All' }}</option>
                            <option value="today" {{ request('date_filter') === 'today' ? 'selected' : '' }}>{{ $isArabic ? 'اليوم' : 'Today' }}</option>
                            <option value="week" {{ request('date_filter') === 'week' ? 'selected' : '' }}>{{ $isArabic ? 'هذا الأسبوع' : 'This Week' }}</option>
                            <option value="month" {{ request('date_filter') === 'month' ? 'selected' : '' }}>{{ $isArabic ? 'هذا الشهر' : 'This Month' }}</option>
                            <option value="custom" {{ request('date_filter') === 'custom' ? 'selected' : '' }}>{{ $isArabic ? 'تحديد' : 'Custom' }}</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'الحالة' : 'Status' }}</label>
                        <select name="status" class="filter-input w-full">
                            <option value="all">{{ $isArabic ? 'الكل' : 'All' }}</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ $isArabic ? 'قيد الانتظار' : 'Pending' }}</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>{{ $isArabic ? 'مؤكد' : 'Confirmed' }}</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ $isArabic ? 'مكتمل' : 'Completed' }}</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ $isArabic ? 'ملغي' : 'Cancelled' }}</option>
                        </select>
                    </div>

                    <!-- Staff Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'الموظف' : 'Staff' }}</label>
                        <select name="staff_id" id="staff_filter" class="filter-input w-full" onchange="loadStaffServices(this.value)">
                            <option value="">{{ $isArabic ? 'الكل' : 'All' }}</option>
                            @foreach($staffMembers ?? [] as $staff)
                                <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 2: Service, Service Type, Queue Status, Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Service Filter (from Services table) -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'الخدمة' : 'Service' }}</label>
                        <select name="service_name" id="service_filter" class="filter-input w-full">
                            <option value="">{{ $isArabic ? 'الكل' : 'All' }}</option>
                            @foreach($services ?? [] as $service)
                                <option value="{{ $service->name }}" {{ request('service_name') == $service->name ? 'selected' : '' }}>
                                    {{ $isArabic && $service->name_ar ? $service->name_ar : $service->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Service Type Filter (consultation, examination, etc.) -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'نوع الخدمة' : 'Service Type' }}</label>
                        <select name="service_type" class="filter-input w-full">
                            <option value="">{{ $isArabic ? 'الكل' : 'All' }}</option>
                            @foreach($serviceTypes ?? [] as $type)
                                <option value="{{ $type }}" {{ request('service_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Queue Status Filter -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'حالة الطابور' : 'Queue Status' }}</label>
                        <select name="queue_status" class="filter-input w-full">
                            <option value="">{{ $isArabic ? 'الكل' : 'All' }}</option>
                            <option value="in_queue" {{ request('queue_status') === 'in_queue' ? 'selected' : '' }}>{{ $isArabic ? 'في الطابور' : 'In Queue' }}</option>
                            <option value="not_in_queue" {{ request('queue_status') === 'not_in_queue' ? 'selected' : '' }}>{{ $isArabic ? 'غير في الطابور' : 'Not in Queue' }}</option>
                            <option value="waiting" {{ request('queue_status') === 'waiting' ? 'selected' : '' }}>{{ $isArabic ? 'في الانتظار' : 'Waiting' }}</option>
                            <option value="serving" {{ request('queue_status') === 'serving' ? 'selected' : '' }}>{{ $isArabic ? 'جاري الخدمة' : 'Serving' }}</option>
                            <option value="queue_completed" {{ request('queue_status') === 'queue_completed' ? 'selected' : '' }}>{{ $isArabic ? 'اكتمل (طابور)' : 'Completed (Q)' }}</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-sm">
                            {{ $isArabic ? 'بحث' : 'Filter' }}
                        </button>
                        <a href="{{ route('admin.appointments') }}" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 text-sm">
                            {{ $isArabic ? 'مسح' : 'Clear' }}
                        </a>
                    </div>
                </div>

                <!-- Custom Date Range (hidden by default) -->
                <div id="customDateRange" class="mt-4 grid grid-cols-2 gap-4 {{ request('date_filter') === 'custom' ? '' : 'hidden' }}">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'من' : 'From' }}</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-input w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-white mb-1">{{ $isArabic ? 'إلى' : 'To' }}</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="filter-input w-full">
                    </div>
                </div>
            </form>
            </div><!-- End filtersContainer -->
        </div>

        <!-- Appointments Table -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
            <!-- Table Header with Bulk Actions -->
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <h3 class="font-semibold text-slate-900 dark:text-slate-100">
                            {{ $isArabic ? 'قائمة المواعيد' : 'Appointments List' }}
                            <span class="text-sm font-normal text-slate-500 dark:text-slate-300">({{ $appointments->total() }})</span>
                        </h3>
                        <span id="bulkSelectedCount" class="text-sm text-indigo-600 dark:text-indigo-400 font-medium hidden"></span>
                    </div>

                    <!-- Bulk Actions Buttons -->
                    <div id="bulkActionsBar" class="hidden">
                        <div class="flex items-center gap-2">
                        <select id="bulkAction" class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ $isArabic ? 'عملية جماعية...' : 'Bulk Action...' }}</option>
                            <option value="confirm">{{ $isArabic ? 'تأكيد' : 'Confirm' }}</option>
                            <option value="complete">{{ $isArabic ? 'إكمال' : 'Complete' }}</option>
                            <option value="cancel">{{ $isArabic ? 'إلغاء' : 'Cancel' }}</option>
                            <option value="delete">{{ $isArabic ? 'حذف' : 'Delete' }}</option>
                        </select>
                        <button onclick="applyBulkAction()" class="px-4 py-1.5 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-sm">
                            {{ $isArabic ? 'تطبيق' : 'Apply' }}
                        </button>
                        <button onclick="clearBulkSelection()" class="px-4 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 text-sm">
                            {{ $isArabic ? 'إلغاء' : 'Cancel' }}
                        </button>
                        </div>
                    </div>
                </div>
            </div>

            @if($appointments->count() > 0)
                <!-- Top Pagination & Per Page Selector -->
                <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ $isArabic ? 'عرض' : 'Show' }}:</label>
                            <form action="" method="GET" id="perPageForm" class="inline-block">
                                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $val)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <select name="per_page" onchange="this.form.submit()"
                                        class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all">
                                    <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                                    <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="75" {{ request('per_page', 15) == 75 ? 'selected' : '' }}>75</option>
                                    <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </form>
                            <span class="text-xs text-slate-500 dark:text-slate-300">{{ $isArabic ? 'نتيجة' : 'entries' }}</span>
                        </div>

                        <!-- Pagination -->
                        <div class="text-sm">
                            {{ $appointments->appends(request()->except('page'))->links() }}
                        </div>

                        <!-- Results Info -->
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            {{ $isArabic ? 'عرض' : 'Showing' }}
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $appointments->firstItem() ?? 0 }}</span>
                            {{ $isArabic ? 'إلى' : 'to' }}
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $appointments->lastItem() ?? 0 }}</span>
                            {{ $isArabic ? 'من' : 'of' }}
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $appointments->total() }}</span>
                            {{ $isArabic ? 'نتيجة' : 'results' }}
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="appointmentsTable" class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700">
                            <tr>
                                <th class="px-4 py-3 w-12">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                                           class="w-4 h-4 text-indigo-600 bg-slate-100 dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded focus:ring-indigo-500">
                                </th>
                                <th class="px-3 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'customer', 'dir' => request('dir') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        {{ $isArabic ? 'العميل' : 'Customer' }}
                                        @if(request('sort') === 'customer')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="{{ request('dir') === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'date', 'dir' => request('dir') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        {{ $isArabic ? 'التاريخ والوقت' : 'Date & Time' }}
                                        @if(request('sort', 'date') === 'date')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="{{ request('dir', 'desc') === 'asc' ? 'M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z' : 'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' }}" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider hide-mobile">{{ $isArabic ? 'الموظف' : 'Staff' }}</th>
                                <th class="px-3 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider hide-mobile">{{ $isArabic ? 'الخدمة' : 'Service' }}</th>
                                <th class="px-3 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider hide-mobile">{{ $isArabic ? 'السعر' : 'Price' }}</th>
                                <th class="px-3 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ $isArabic ? 'الحالة' : 'Status' }}</th>
                                <th class="px-3 py-3 {{ $isArabic ? 'text-right' : 'text-left' }} text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ $isArabic ? 'الإجراءات' : 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($appointments as $appointment)
                                @php
                                    $rowClass = 'hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors';
                                    if($appointment->customer?->is_vip) {
                                        $rowClass .= ' bg-amber-50 dark:bg-amber-900/20';
                                    } elseif($appointment->status === 'cancelled') {
                                        $rowClass .= ' bg-red-50 dark:bg-red-900/20';
                                    } elseif($appointment->status === 'completed') {
                                        $rowClass .= ' bg-emerald-50 dark:bg-emerald-900/20';
                                    } elseif($appointment->date < now() && $appointment->status !== 'completed') {
                                        $rowClass .= ' bg-orange-50 dark:bg-orange-900/20';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}" id="row-{{ $appointment->id }}" data-appointment-id="{{ $appointment->id }}">
                                    <!-- Checkbox -->
                                    <td class="px-4 py-3">
                                        <input type="checkbox" class="appointment-checkbox w-4 h-4 text-indigo-600 bg-slate-100 dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded focus:ring-indigo-500 dark:focus:ring-indigo-400"
                                               value="{{ $appointment->id }}" onchange="updateBulkSelection()">
                                    </td>

                                    <!-- Customer -->
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900 dark:to-indigo-800 rounded-lg flex items-center justify-center shadow-sm">
                                                <span class="text-indigo-700 dark:text-indigo-300 font-bold text-sm">{{ mb_substr($appointment->customer?->name ?? '?', 0, 1) }}</span>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                                                    {{ $appointment->customer?->name ?? ($isArabic ? 'غير محدد' : 'N/A') }}
                                                    @if($appointment->customer?->is_vip)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 ml-1">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                            </svg>
                                                        </span>
                                                    @endif
                                                </div>
                                                <a href="tel:{{ $appointment->customer?->phone }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">
                                                    {{ $appointment->customer?->phone ?? '-' }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Date & Time -->
                                    <td class="px-3 py-3">
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $appointment->date->format('Y-m-d') }}</div>
                                        <div class="text-xs text-slate-600 dark:text-slate-400 font-medium">{{ $appointment->time_slot }}</div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span class="text-xs text-slate-500 dark:text-slate-300">{{ $appointment->date->translatedFormat($isArabic ? 'l' : 'D') }}</span>
                                            @if($appointment->date < now() && $appointment->status !== 'completed')
                                                <span class="px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full font-bold">!</span>
                                            @elseif($appointment->date->diffInHours(now()) <= 2 && $appointment->date > now())
                                                <span class="px-1.5 py-0.5 bg-orange-500 text-white text-xs rounded-full font-bold animate-pulse">{{ $isArabic ? 'قريب' : 'Soon' }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Staff (hide on mobile) -->
                                    <td class="px-3 py-3 hide-mobile">
                                        <div class="text-sm text-slate-900 dark:text-slate-100 font-medium">{{ $appointment->staff?->name ?? ($isArabic ? 'غير محدد' : 'N/A') }}</div>
                                    </td>

                                    <!-- Service (hide on mobile) -->
                                    <td class="px-3 py-3 hide-mobile">
                                        <div class="text-sm text-slate-700 dark:text-slate-300">{{ $appointment->service_type ?? '-' }}</div>
                                        @if($appointment->queue)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold mt-1 {{ $appointment->queue->status === 'waiting' ? 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-300' : ($appointment->queue->status === 'serving' ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300') }}">
                                                #{{ $appointment->queue->queue_number }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Price (hide on mobile) -->
                                    <td class="px-3 py-3 hide-mobile">
                                        <div class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $appointment->service?->price ?? '-' }}</div>
                                        <div class="text-xs text-slate-600 dark:text-slate-400">{{ $isArabic ? 'ج.م' : 'EGP' }}</div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-3 py-3">
                                        <div class="relative inline-block">
                                            <button onclick="toggleStatusDropdown({{ $appointment->id }})"
                                                    class="px-3 py-2 inline-flex items-center gap-1.5 text-xs leading-5 font-bold rounded-lg transition-all duration-200 cursor-pointer shadow-sm
                                                    @if($appointment->status === 'pending') bg-gradient-to-r from-amber-100 to-amber-50 dark:from-amber-900 dark:to-amber-800 text-amber-800 dark:text-amber-300 hover:from-amber-200 hover:to-amber-100 dark:hover:from-amber-800 dark:hover:to-amber-700 border border-amber-300 dark:border-amber-700
                                                    @elseif($appointment->status === 'confirmed') bg-gradient-to-r from-emerald-100 to-emerald-50 dark:from-emerald-900 dark:to-emerald-800 text-emerald-800 dark:text-emerald-300 hover:from-emerald-200 hover:to-emerald-100 dark:hover:from-emerald-800 dark:hover:to-emerald-700 border border-emerald-300 dark:border-emerald-700
                                                    @elseif($appointment->status === 'completed') bg-gradient-to-r from-cyan-100 to-cyan-50 dark:from-cyan-900 dark:to-cyan-800 text-cyan-800 dark:text-cyan-300 hover:from-cyan-200 hover:to-cyan-100 dark:hover:from-cyan-800 dark:hover:to-cyan-700 border border-cyan-300 dark:border-cyan-700
                                                    @elseif($appointment->status === 'cancelled') bg-gradient-to-r from-red-100 to-red-50 dark:from-red-900 dark:to-red-800 text-red-800 dark:text-red-300 hover:from-red-200 hover:to-red-100 dark:hover:from-red-800 dark:hover:to-red-700 border border-red-300 dark:border-red-700
                                                    @else bg-gradient-to-r from-slate-100 to-slate-50 dark:from-slate-700 dark:to-slate-600 text-slate-800 dark:text-slate-300 hover:from-slate-200 hover:to-slate-100 dark:hover:from-slate-600 dark:hover:to-slate-500 border border-slate-300 dark:border-slate-600
                                                    @endif"
                                                    id="status-btn-{{ $appointment->id }}">
                                                @if($appointment->status === 'pending')
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span class="hidden sm:inline">{{ $isArabic ? 'معلق' : 'Pending' }}</span>
                                                @elseif($appointment->status === 'confirmed')
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span class="hidden sm:inline">{{ $isArabic ? 'مؤكد' : 'OK' }}</span>
                                                @elseif($appointment->status === 'completed')
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span class="hidden sm:inline">{{ $isArabic ? 'تم' : 'Done' }}</span>
                                                @elseif($appointment->status === 'cancelled')
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span class="hidden sm:inline">{{ $isArabic ? 'ملغي' : 'X' }}</span>
                                                @endif
                                                <svg class="w-3 h-3 opacity-60" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>

                                            <!-- Status Dropdown Menu -->
                                            <div id="status-dropdown-{{ $appointment->id }}" class="hidden absolute z-20 {{ $isArabic ? 'right-0' : 'left-0' }} mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-100 dark:border-slate-700 py-2 divide-y divide-slate-100 dark:divide-slate-700">
                                                <div class="px-2 pb-2">
                                                    <button onclick="updateStatus({{ $appointment->id }}, 'pending')" class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2.5 text-sm font-medium text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-all duration-200 flex items-center gap-3 group">
                                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 group-hover:bg-amber-200 dark:group-hover:bg-amber-900/40 transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </span>
                                                        <span>{{ $isArabic ? 'قيد الانتظار' : 'Pending' }}</span>
                                                    </button>
                                                    <button onclick="updateStatus({{ $appointment->id }}, 'confirmed')" class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2.5 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-all duration-200 flex items-center gap-3 group">
                                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/40 transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </span>
                                                        <span>{{ $isArabic ? 'مؤكد' : 'Confirmed' }}</span>
                                                    </button>
                                                </div>
                                                <div class="px-2 pt-2">
                                                    <button onclick="updateStatus({{ $appointment->id }}, 'completed')" class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2.5 text-sm font-medium text-cyan-700 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/30 rounded-lg transition-all duration-200 flex items-center gap-3 group">
                                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-cyan-100 dark:bg-cyan-900/30 group-hover:bg-cyan-200 dark:group-hover:bg-cyan-900/40 transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </span>
                                                        <span>{{ $isArabic ? 'مكتمل' : 'Completed' }}</span>
                                                    </button>
                                                    <button onclick="updateStatus({{ $appointment->id }}, 'cancelled')" class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2.5 text-sm font-medium text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-all duration-200 flex items-center gap-3 group">
                                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 group-hover:bg-red-200 dark:group-hover:bg-red-900/40 transition-colors">
                                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </span>
                                                        <span>{{ $isArabic ? 'ملغي' : 'Cancelled' }}</span>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-1">
                                            <!-- View Button -->
                                            <button onclick="viewAppointment({{ $appointment->id }})"
                                                    class="p-2 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-all duration-200"
                                                    title="{{ $isArabic ? 'عرض' : 'View' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>

                                            <!-- Edit Button -->
                                            <button onclick="openEditModal({{ $appointment->id }})"
                                                    class="p-2 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-all duration-200"
                                                    title="{{ $isArabic ? 'تعديل' : 'Edit' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>

                                            <!-- Dropdown Menu -->
                                            <div class="relative inline-block">
                                                <button onclick="toggleActionsMenu({{ $appointment->id }})"
                                                        class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-all duration-200"
                                                        title="{{ $isArabic ? 'المزيد' : 'More' }}">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Dropdown Menu Items -->
                                                <div id="actions-menu-{{ $appointment->id }}" class="hidden absolute {{ $isArabic ? 'left-0' : 'right-0' }} mt-2 w-44 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-100 dark:border-slate-700 py-1 z-30">
                                                    @if($appointment->status !== 'cancelled' && $appointment->status !== 'completed' && $appointment->date >= now())
                                                        <button onclick="sendReminder({{ $appointment->id}})"
                                                                class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                            </svg>
                                                            {{ $isArabic ? 'إرسال تذكير' : 'Send Reminder' }}
                                                        </button>
                                                    @endif

                                                    <button onclick="showQRCode({{ $appointment->id }})"
                                                            class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                                        <svg class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                                        </svg>
                                                        {{ $isArabic ? 'رمز QR' : 'QR Code' }}
                                                    </button>

                                                    @if($appointment->queue)
                                                        <button onclick="removeFromQueue({{ $appointment->id }})"
                                                                class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                            {{ $isArabic ? 'إزالة من الطابور' : 'Remove from Queue' }}
                                                        </button>
                                                    @else
                                                        <button onclick="addToQueue({{ $appointment->id }})"
                                                                class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2 text-xs text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/30 flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                            </svg>
                                                            {{ $isArabic ? 'إضافة للطابور' : 'Add to Queue' }}
                                                        </button>
                                                    @endif

                                                    <div class="border-t border-slate-100 dark:border-slate-700 my-1"></div>

                                                    <button onclick="deleteAppointment({{ $appointment->id }})"
                                                            class="w-full text-{{ $isArabic ? 'right' : 'left' }} px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center gap-2 font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                        {{ $isArabic ? 'حذف' : 'Delete' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Bottom Pagination -->
                <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ $isArabic ? 'عرض' : 'Show' }}:</label>
                            <form action="" method="GET" class="inline-block">
                                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $val)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $val }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <select name="per_page" onchange="this.form.submit()"
                                        class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all">
                                    <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                                    <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="75" {{ request('per_page', 15) == 75 ? 'selected' : '' }}>75</option>
                                    <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </form>
                            <span class="text-xs text-slate-500 dark:text-slate-300">{{ $isArabic ? 'نتيجة' : 'entries' }}</span>
                        </div>

                        <!-- Pagination -->
                        <div class="text-sm">
                            {{ $appointments->appends(request()->except('page'))->links() }}
                        </div>

                        <!-- Results Info -->
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            {{ $isArabic ? 'عرض' : 'Showing' }}
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $appointments->firstItem() ?? 0 }}</span>
                            {{ $isArabic ? 'إلى' : 'to' }}
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $appointments->lastItem() ?? 0 }}</span>
                            {{ $isArabic ? 'من' : 'of' }}
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $appointments->total() }}</span>
                            {{ $isArabic ? 'نتيجة' : 'results' }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900 dark:text-slate-100">{{ $isArabic ? 'لا توجد مواعيد' : 'No appointments' }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">{{ $isArabic ? 'ابدأ بإضافة موعد جديد' : 'Start by adding a new appointment' }}</p>
                    <button onclick="openAddModal()" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ $isArabic ? 'إضافة موعد' : 'Add Appointment' }}
                    </button>
                </div>
            @endif
        </div>
        </div> <!-- end listView -->
    </main>

    <!-- View Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-full p-4">
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl w-11/12 max-w-lg mx-4">
            <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $isArabic ? 'تفاصيل الموعد' : 'Appointment Details' }}</h3>
                <button onclick="closeViewModal()" class="text-slate-400 dark:text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="appointmentDetails" class="p-4">
                <!-- Details loaded via JS -->
            </div>
        </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="flex items-center justify-center min-h-full p-4">
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden transform transition-all duration-300 scale-95">
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-r from-emerald-600 to-emerald-700 dark:from-emerald-700 dark:to-emerald-800 px-6 py-4 border-b border-emerald-600 dark:border-emerald-700">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 backdrop-blur rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">{{ $isArabic ? 'تعديل الموعد' : 'Edit Appointment' }}</h3>
                        </div>
                        <button onclick="closeEditModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="overflow-y-auto max-h-[calc(90vh-140px)]">
                    <form id="editForm" class="p-6 space-y-5">
                        <input type="hidden" id="edit_id">

                        <!-- Customer Information Section -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ $isArabic ? 'معلومات العميل' : 'Customer Information' }}
                            </h4>

                            <div class="grid grid-cols-1 gap-4">
                                <!-- Full Name -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'اسم العميل' : 'Customer Name' }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" id="edit_name" required
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm">
                                    </div>
                                </div>

                                <!-- Phone & Email -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Phone -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                            {{ $isArabic ? 'الهاتف' : 'Phone' }} <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                            </div>
                                            <input type="tel" id="edit_phone" required
                                                class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm">
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                            {{ $isArabic ? 'البريد الإلكتروني' : 'Email' }}
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <input type="email" id="edit_email"
                                                class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Details Section -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $isArabic ? 'تفاصيل الموعد' : 'Appointment Details' }}
                            </h4>

                            <!-- Service Selection (First) -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                    {{ $isArabic ? 'الخدمة' : 'Service' }} <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    <select id="edit_service" required onchange="loadStaffByService(this.value, 'edit')"
                                        class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm appearance-none">
                                        <option value="">{{ $isArabic ? 'اختر الخدمة' : 'Select Service' }}</option>
                                        @foreach($services ?? [] as $service)
                                            <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-duration="{{ $service->duration }}">
                                                {{ $isArabic && $service->name_ar ? $service->name_ar : $service->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Service Price & Duration Display -->
                            <div id="edit_service_info" class="hidden bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 rounded-lg p-4 border border-emerald-100 dark:border-emerald-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400">{{ $isArabic ? 'السعر' : 'Price' }}</p>
                                            <p id="edit_service_price" class="text-lg font-bold text-emerald-900 dark:text-emerald-100">0 {{ $isArabic ? 'ريال' : 'SAR' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span id="edit_service_duration" class="text-sm text-slate-700 dark:text-slate-300 font-medium">0 {{ $isArabic ? 'دقيقة' : 'min' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Staff (Will be populated based on service) -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'الموظف المتخصص' : 'Specialist Staff' }}
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <select id="edit_staff" disabled
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm appearance-none disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:cursor-not-allowed">
                                            <option value="">{{ $isArabic ? 'اختر الخدمة أولاً' : 'Select service first' }}</option>
                                        </select>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-300 mt-1">{{ $isArabic ? 'الموظفين المتاحين لهذه الخدمة' : 'Available staff for this service' }}</p>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'التاريخ' : 'Date' }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <input type="date" id="edit_date" required
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm"
                                            min="{{ date('Y-m-d') }}">
                                    </div>
                                </div>

                                <!-- Time -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'الوقت' : 'Time' }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <select id="edit_time" disabled
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm appearance-none disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:cursor-not-allowed">
                                            <option value="">{{ $isArabic ? 'اختر الخدمة والموظف والتاريخ أولاً' : 'Select service, staff and date first' }}</option>
                                        </select>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-300 mt-1">{{ $isArabic ? 'الأوقات المتاحة ستظهر بعد اختيار الخدمة والموظف والتاريخ' : 'Available times will appear after selecting service, staff and date' }}</p>
                                </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'الحالة' : 'Status' }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <select id="edit_status" required
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm appearance-none">
                                            <option value="pending">{{ $isArabic ? 'قيد الانتظار' : 'Pending' }}</option>
                                            <option value="confirmed">{{ $isArabic ? 'مؤكد' : 'Confirmed' }}</option>
                                            <option value="completed">{{ $isArabic ? 'مكتمل' : 'Completed' }}</option>
                                            <option value="cancelled">{{ $isArabic ? 'ملغي' : 'Cancelled' }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                    {{ $isArabic ? 'ملاحظات' : 'Notes' }}
                                </label>
                                <div class="relative">
                                    <textarea id="edit_notes" rows="3"
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 dark:focus:border-emerald-400 transition-all text-sm resize-none"
                                        placeholder="{{ $isArabic ? 'أي ملاحظات أو طلبات خاصة...' : 'Any special notes or requests...' }}"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Success/Error Messages -->
                        <div id="editMessage" class="hidden rounded-lg text-sm"></div>
                    </form>
                </div>

                <!-- Footer with Action Buttons -->
                <div class="sticky bottom-0 bg-slate-800 dark:bg-slate-900 px-6 py-4 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('editForm').requestSubmit()"
                            class="flex-1 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 dark:from-emerald-500 dark:to-emerald-600 dark:hover:from-emerald-600 dark:hover:to-emerald-700 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 shadow-lg text-sm">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                {{ $isArabic ? 'حفظ التعديلات' : 'Save Changes' }}
                            </span>
                        </button>
                        <button type="button" onclick="closeEditModal()"
                            class="px-6 py-3 bg-white dark:bg-slate-700 hover:bg-slate-600 dark:hover:bg-slate-600 text-slate-300 dark:text-slate-300 font-medium rounded-lg transition-all duration-200 border border-slate-600 dark:border-slate-600 hover:border-slate-500 dark:hover:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:focus:ring-slate-500 focus:ring-offset-2 text-sm">
                            {{ $isArabic ? 'إلغاء' : 'Cancel' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="flex items-center justify-center min-h-full p-4">
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden transform transition-all duration-300 scale-95">
                <!-- Header -->
                <div class="sticky top-0 bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-700 dark:to-indigo-800 px-6 py-4 border-b border-indigo-600 dark:border-indigo-700">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white bg-opacity-20 backdrop-blur rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white">{{ $isArabic ? 'إضافة موعد جديد' : 'Add New Appointment' }}</h3>
                        </div>
                        <button onclick="closeAddModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-lg p-2 transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="overflow-y-auto max-h-[calc(90vh-140px)]">
                    <form id="addForm" class="p-6 space-y-5">
                        <!-- Customer Information Section -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ $isArabic ? 'معلومات العميل' : 'Customer Information' }}
                            </h4>

                            <div class="grid grid-cols-1 gap-4">
                                <!-- Full Name -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'اسم العميل' : 'Customer Name' }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" id="add_name" required
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm"
                                            placeholder="{{ $isArabic ? 'أدخل اسم العميل الكامل' : 'Enter full customer name' }}">
                                    </div>
                                </div>

                                <!-- Phone & Email -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Phone -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                            {{ $isArabic ? 'الهاتف' : 'Phone' }} <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                            </div>
                                            <input type="tel" id="add_phone" required
                                                class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100"
                                                placeholder="{{ $isArabic ? 'مثال: 0501234567' : 'e.g., 0501234567' }}">
                                        </div>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                            {{ $isArabic ? 'البريد الإلكتروني' : 'Email' }}
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <input type="email" id="add_email"
                                                class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all text-sm"
                                                placeholder="{{ $isArabic ? 'example@domain.com' : 'example@domain.com' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointment Details Section -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2 pb-2 border-b border-slate-200 dark:border-slate-700">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $isArabic ? 'تفاصيل الموعد' : 'Appointment Details' }}
                            </h4>

                            <!-- Service Selection (First) -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                    {{ $isArabic ? 'الخدمة' : 'Service' }} <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    <select id="add_service" required onchange="loadStaffByService(this.value, 'add')"
                                        class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all text-sm appearance-none">
                                        <option value="">{{ $isArabic ? 'اختر الخدمة' : 'Select Service' }}</option>
                                        @foreach($services ?? [] as $service)
                                            <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-duration="{{ $service->duration }}">
                                                {{ $isArabic && $service->name_ar ? $service->name_ar : $service->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Service Price & Duration Display -->
                            <div id="add_service_info" class="hidden bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/30 rounded-lg p-4 border border-indigo-100 dark:border-indigo-800">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-600 dark:text-slate-400">{{ $isArabic ? 'السعر' : 'Price' }}</p>
                                            <p id="add_service_price" class="text-lg font-bold text-indigo-900 dark:text-indigo-100">0 {{ $isArabic ? 'ريال' : 'SAR' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span id="add_service_duration" class="text-sm text-slate-700 dark:text-slate-300 font-medium">0 {{ $isArabic ? 'دقيقة' : 'min' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Staff (Will be populated based on service) -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'الموظف المتخصص' : 'Specialist Staff' }}
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <select id="add_staff" disabled
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all text-sm appearance-none disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:cursor-not-allowed">
                                            <option value="">{{ $isArabic ? 'اختر الخدمة أولاً' : 'Select service first' }}</option>
                                        </select>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-300 mt-1">{{ $isArabic ? 'الموظفين المتاحين لهذه الخدمة' : 'Available staff for this service' }}</p>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'التاريخ' : 'Date' }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <input type="date" id="add_date" required
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all text-sm"
                                            min="{{ date('Y-m-d') }}">
                                    </div>
                                </div>

                                <!-- Time -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                        {{ $isArabic ? 'الوقت' : 'Time' }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 {{ $isArabic ? 'right-0 pr-3' : 'left-0 pl-3' }} flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <select id="add_time" disabled
                                            class="w-full {{ $isArabic ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all text-sm appearance-none disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:cursor-not-allowed">
                                            <option value="">{{ $isArabic ? 'اختر الخدمة والموظف والتاريخ أولاً' : 'Select service, staff and date first' }}</option>
                                        </select>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-300 mt-1">{{ $isArabic ? 'الأوقات المتاحة ستظهر بعد اختيار الخدمة والموظف والتاريخ' : 'Available times will appear after selecting service, staff and date' }}</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-white mb-2">
                                    {{ $isArabic ? 'ملاحظات' : 'Notes' }}
                                </label>
                                <div class="relative">
                                    <textarea id="add_notes" rows="3"
                                        class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 transition-all text-sm resize-none"
                                        placeholder="{{ $isArabic ? 'أي ملاحظات أو طلبات خاصة...' : 'Any special notes or requests...' }}"></textarea>
                                </div>
                            </div>

                            <!-- Add to Queue Option -->
                            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800/50">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input type="checkbox" id="add_to_queue"
                                        class="mt-1 w-5 h-5 text-indigo-600 bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 transition-all">
                                    <div class="flex-1">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors">
                                            {{ $isArabic ? 'إضافة إلى الطابور' : 'Add to Queue' }}
                                        </span>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                                            {{ $isArabic ? 'سيتم إضافة العميل للطابور تلقائياً في تاريخ الموعد المحدد' : 'Customer will be automatically added to queue on the appointment date' }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Success/Error Messages -->
                        <div id="addMessage" class="hidden rounded-lg text-sm"></div>
                    </form>
                </div>

                <!-- Footer with Action Buttons -->
                <div class="sticky bottom-0 bg-slate-800 dark:bg-slate-900 px-6 py-4 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('addForm').requestSubmit()"
                            class="flex-1 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 dark:from-indigo-500 dark:to-indigo-600 dark:hover:from-indigo-600 dark:hover:to-indigo-700 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 shadow-lg text-sm">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                {{ $isArabic ? 'حفظ الموعد' : 'Save Appointment' }}
                            </span>
                        </button>
                        <button type="button" onclick="closeAddModal()"
                            class="px-6 py-3 bg-white dark:bg-slate-700 hover:bg-slate-600 dark:hover:bg-slate-600 text-slate-300 dark:text-slate-300 font-medium rounded-lg transition-all duration-200 border border-slate-600 dark:border-slate-600 hover:border-slate-500 dark:hover:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-400 dark:focus:ring-slate-500 focus:ring-offset-2 text-sm">
                            {{ $isArabic ? 'إلغاء' : 'Cancel' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Appointments script starting...');
        const isArabic = {{ $isArabic ? 'true' : 'false' }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        console.log('Script initialized. isArabic:', isArabic, 'csrfToken:', csrfToken ? 'present' : 'missing');

        // All services data for filtering
        const allServices = @json($services ?? []);
        const allServicesData = @json($services ?? []);

        const texts = {
            confirmDelete: isArabic ? 'هل أنت متأكد من حذف هذا الموعد؟' : 'Are you sure you want to delete this appointment?',
            deleted: isArabic ? 'تم الحذف بنجاح' : 'Deleted successfully',
            saved: isArabic ? 'تم الحفظ بنجاح' : 'Saved successfully',
            error: isArabic ? 'حدث خطأ' : 'An error occurred',
            statusUpdated: isArabic ? 'تم تحديث الحالة' : 'Status updated',
            loading: isArabic ? 'جاري التحميل...' : 'Loading...',
            pending: isArabic ? 'قيد الانتظار' : 'Pending',
            confirmed: isArabic ? 'مؤكد' : 'Confirmed',
            completed: isArabic ? 'مكتمل' : 'Completed',
            cancelled: isArabic ? 'ملغي' : 'Cancelled',
            all: isArabic ? 'الكل' : 'All'
        };

        // Load staff by service selection
        async function loadStaffByService(serviceId, formType = 'add') {
            console.log('🔵 loadStaffByService called:', { serviceId, formType });

            const staffSelect = document.getElementById(`${formType}_staff`);
            const timeSelect = document.getElementById(`${formType}_time`);
            const serviceInfo = document.getElementById(`${formType}_service_info`);
            const servicePrice = document.getElementById(`${formType}_service_price`);
            const serviceDuration = document.getElementById(`${formType}_service_duration`);

            // Check if elements exist
            if (!staffSelect || !timeSelect) {
                console.error('❌ Required form elements not found');
                return;
            }

            // Reset time slots when service changes
            timeSelect.innerHTML = `<option value="">${isArabic ? 'اختر الموظف والتاريخ أولاً' : 'Select staff and date first'}</option>`;
            timeSelect.disabled = true;

            if (!serviceId) {
                staffSelect.disabled = true;
                staffSelect.innerHTML = `<option value="">${isArabic ? 'اختر الخدمة أولاً' : 'Select service first'}</option>`;
                if (serviceInfo) serviceInfo.classList.add('hidden');
                return;
            }

            // Show service price and duration
            const selectedService = allServicesData.find(s => s.id == serviceId);
            if (selectedService && serviceInfo) {
                servicePrice.textContent = `${selectedService.price} ${isArabic ? 'ريال' : 'SAR'}`;
                serviceDuration.textContent = `${selectedService.duration} ${isArabic ? 'دقيقة' : 'min'}`;
                serviceInfo.classList.remove('hidden');
            }

            // Load staff for this service
            try {
                staffSelect.disabled = true;
                staffSelect.innerHTML = `<option value="">${isArabic ? 'جاري التحميل...' : 'Loading...'}</option>`;

                console.log('📡 Fetching staff for service:', serviceId);
                const response = await fetch(`/api/booking/staff/by-service/${serviceId}`);
                const result = await response.json();
                console.log('✅ Staff response:', result);

                if (result.success && result.data.length > 0) {
                    staffSelect.innerHTML = `<option value="">${isArabic ? 'اختر الموظف' : 'Select Staff'}</option>`;
                    result.data.forEach(staff => {
                        staffSelect.innerHTML += `<option value="${staff.id}">${staff.name}</option>`;
                    });
                    staffSelect.disabled = false;
                    console.log(`✅ Loaded ${result.data.length} staff members`);
                } else {
                    console.warn('⚠️ No staff found for this service');
                    staffSelect.innerHTML = `<option value="">${isArabic ? 'لا يوجد موظفين لهذه الخدمة' : 'No staff available for this service'}</option>`;
                    staffSelect.disabled = true;
                }
            } catch (error) {
                console.error('❌ Error loading staff:', error);
                staffSelect.innerHTML = `<option value="">${isArabic ? 'خطأ في التحميل - حاول مرة أخرى' : 'Error loading - try again'}</option>`;
                staffSelect.disabled = true;

                // Show user-friendly error message
                showToast(isArabic ? 'فشل تحميل الموظفين. تحقق من اتصال الإنترنت.' : 'Failed to load staff. Check your internet connection.', 'error');
            }
        }

        // Load staff services when staff is selected (for filters)
        async function loadStaffServices(staffId) {
            const serviceFilter = document.getElementById('service_filter');

            if (!staffId) {
                // Reset to all services
                serviceFilter.innerHTML = `<option value="">${texts.all}</option>`;
                allServices.forEach(service => {
                    const name = isArabic && service.name_ar ? service.name_ar : service.name;
                    serviceFilter.innerHTML += `<option value="${service.name}">${name}</option>`;
                });
                return;
            }

            try {
                const response = await fetch(`/api/booking/staff/${staffId}/services`);
                const result = await response.json();

                if (result.success) {
                    serviceFilter.innerHTML = `<option value="">${texts.all}</option>`;
                    result.data.forEach(service => {
                        const name = isArabic && service.name_ar ? service.name_ar : service.name;
                        serviceFilter.innerHTML += `<option value="${service.name}">${name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error loading staff services:', error);
            }
        }

        // Toggle custom date range
        function toggleCustomDate(select) {
            const customRange = document.getElementById('customDateRange');
            if (select.value === 'custom') {
                customRange.classList.remove('hidden');
            } else {
                customRange.classList.add('hidden');
            }
        }

        // Status dropdown
        let activeDropdown = null;

        function toggleStatusDropdown(id) {
            const dropdown = document.getElementById(`status-dropdown-${id}`);

            // Close any open dropdown
            if (activeDropdown && activeDropdown !== dropdown) {
                activeDropdown.classList.add('hidden');
            }

            dropdown.classList.toggle('hidden');
            activeDropdown = dropdown.classList.contains('hidden') ? null : dropdown;
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (activeDropdown && !e.target.closest('[id^="status-btn-"]') && !e.target.closest('[id^="status-dropdown-"]')) {
                activeDropdown.classList.add('hidden');
                activeDropdown = null;
            }
        });

        // Quick status update
        async function updateStatus(id, status) {
            try {
                const response = await fetch(`/admin/api/appointments/${id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ status })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Update button visually with new design
                    const btn = document.getElementById(`status-btn-${id}`);

                    // Define status configurations
                    const statusConfig = {
                        pending: {
                            classes: 'bg-gradient-to-r from-yellow-100 to-yellow-50 text-yellow-800 hover:from-yellow-200 hover:to-yellow-100 border border-yellow-200',
                            icon: '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>',
                            text: isArabic ? 'قيد الانتظار' : 'Pending'
                        },
                        confirmed: {
                            classes: 'bg-gradient-to-r from-emerald-100 to-emerald-50 dark:from-emerald-900 dark:to-emerald-800 text-emerald-800 dark:text-emerald-300 hover:from-emerald-200 hover:to-emerald-100 dark:hover:from-emerald-800 dark:hover:to-emerald-700 border border-emerald-200 dark:border-emerald-700',
                            icon: '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                            text: isArabic ? 'مؤكد' : 'Confirmed'
                        },
                        completed: {
                            classes: 'bg-gradient-to-r from-cyan-100 to-cyan-50 dark:from-cyan-900 dark:to-cyan-800 text-cyan-800 dark:text-cyan-300 hover:from-cyan-200 hover:to-cyan-100 dark:hover:from-cyan-800 dark:hover:to-cyan-700 border border-cyan-200 dark:border-cyan-700',
                            icon: '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                            text: isArabic ? 'مكتمل' : 'Completed'
                        },
                        cancelled: {
                            classes: 'bg-gradient-to-r from-red-100 to-red-50 text-red-800 hover:from-red-200 hover:to-red-100 border border-red-200',
                            icon: '<svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
                            text: isArabic ? 'ملغي' : 'Cancelled'
                        }
                    };

                    const config = statusConfig[status] || statusConfig.pending;

                    btn.className = `px-3 py-1.5 inline-flex items-center gap-1.5 text-xs leading-5 font-semibold rounded-full transition-all duration-200 cursor-pointer shadow-sm ${config.classes}`;
                    btn.innerHTML = `${config.icon} ${config.text} <svg class="w-3 h-3 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>`;

                    // Update row background color based on status
                    const row = document.getElementById(`row-${id}`);
                    if (row) {
                        // Remove all status-specific backgrounds
                        row.classList.remove('bg-red-50', 'bg-emerald-50', 'bg-orange-50', 'bg-amber-50');

                        // Add new background based on status (keeping VIP amber if exists)
                        const isVip = row.classList.contains('bg-amber-50') || row.querySelector('.text-amber-800');
                        if (!isVip) {
                            if (status === 'cancelled') {
                                row.classList.add('bg-red-50');
                            } else if (status === 'completed') {
                                row.classList.add('bg-emerald-50');
                            } else {
                                // For pending or confirmed, check if overdue
                                const dateCell = row.querySelector('[data-appointment-date]');
                                if (dateCell && status !== 'completed') {
                                    row.classList.add('bg-orange-50');
                                }
                            }
                        }
                    }

                    // Close dropdown
                    document.getElementById(`status-dropdown-${id}`).classList.add('hidden');
                    activeDropdown = null;

                    // Show toast
                    showToast(texts.statusUpdated, 'success');
                } else {
                    showToast(result.message || texts.error, 'error');
                }
            } catch (error) {
                showToast(texts.error, 'error');
            }
        }

        // View appointment
        window.viewAppointment = async function(id) {
            console.log('viewAppointment called with id:', id);
            const modal = document.getElementById('viewModal');
            const details = document.getElementById('appointmentDetails');

            details.innerHTML = `<div class="text-center py-8 text-slate-500 dark:text-slate-400">${texts.loading}</div>`;
            modal.style.display = 'block';

            try {
                const response = await fetch(`/admin/api/appointments/${id}`);
                const result = await response.json();

                if (response.ok && result.success) {
                    const a = result.data;
                    details.innerHTML = `
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 bg-white dark:bg-slate-700 rounded-lg p-3">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">${isArabic ? 'العميل' : 'Customer'}</p>
                                <p class="font-semibold text-slate-900 dark:text-slate-100">${a.customer_name}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400">${a.customer_phone}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">${a.customer_email || '-'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">${isArabic ? 'التاريخ' : 'Date'}</p>
                                <p class="font-medium text-slate-900 dark:text-slate-100">${a.date}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">${isArabic ? 'الوقت' : 'Time'}</p>
                                <p class="font-medium text-slate-900 dark:text-slate-100">${a.time_slot}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">${isArabic ? 'الخدمة' : 'Service'}</p>
                                <p class="font-medium text-slate-900 dark:text-slate-100">${a.service_type || '-'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">${isArabic ? 'الموظف' : 'Staff'}</p>
                                <p class="font-medium text-slate-900 dark:text-slate-100">${a.staff_name || '-'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">${isArabic ? 'الحالة' : 'Status'}</p>
                                <span class="status-badge status-${a.status}">${texts[a.status] || a.status}</span>
                            </div>
                            ${a.notes ? `
                            <div class="col-span-2">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-1">${isArabic ? 'ملاحظات' : 'Notes'}</p>
                                <p class="text-slate-300 dark:text-slate-300">${a.notes}</p>
                            </div>
                            ` : ''}
                        </div>
                        <div class="mt-4 pt-4 border-t flex gap-2">
                            <button onclick="closeViewModal(); editAppointment(${a.id});" class="flex-1 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                                ${isArabic ? 'تعديل' : 'Edit'}
                            </button>
                            <button onclick="closeViewModal();" class="flex-1 py-2 bg-white dark:bg-slate-700 text-slate-300 dark:text-slate-300 rounded-lg hover:bg-slate-600 dark:hover:bg-slate-600 text-sm">
                                ${isArabic ? 'إغلاق' : 'Close'}
                            </button>
                        </div>
                    `;
                }
            } catch (error) {
                details.innerHTML = `<div class="text-center py-8 text-red-500">${texts.error}</div>`;
            }
        }

        window.closeViewModal = function() {
            document.getElementById('viewModal').style.display = 'none';
        }

        // Edit appointment
        window.editAppointment = async function(id) {
            console.log('editAppointment called with id:', id);
            try {
                const response = await fetch(`/admin/api/appointments/${id}`);
                const result = await response.json();

                if (response.ok && result.success) {
                    const a = result.data;
                    document.getElementById('edit_id').value = a.id;
                    document.getElementById('edit_name').value = a.customer_name;
                    document.getElementById('edit_phone').value = a.customer_phone || '';
                    document.getElementById('edit_email').value = a.customer_email || '';
                    document.getElementById('edit_date').value = a.date;
                    document.getElementById('edit_status').value = a.status;
                    document.getElementById('edit_notes').value = a.notes || '';

                    // Find service by name or ID and set service ID
                    const serviceSelect = document.getElementById('edit_service');
                    let serviceOption = null;

                    // Try to find by service_id first
                    if (a.service_id) {
                        serviceOption = Array.from(serviceSelect.options).find(opt => opt.value == a.service_id);
                    }

                    // Fall back to finding by service_type (name)
                    if (!serviceOption && a.service_type) {
                        serviceOption = Array.from(serviceSelect.options).find(opt => opt.text === a.service_type);
                    }

                    if (serviceOption) {
                        serviceSelect.value = serviceOption.value;
                        // Trigger staff loading for this service
                        await loadStaffByService(serviceOption.value, 'edit');

                        // Set staff after loading
                        setTimeout(() => {
                            if (a.staff_id) {
                                document.getElementById('edit_staff').value = a.staff_id;

                                // Load available time slots after staff is set
                                if (a.date) {
                                    loadAvailableTimeSlots(a.date, a.staff_id, 'edit', a.id).then(() => {
                                        // Set the time after loading available slots
                                        setTimeout(() => {
                                            const timeSelect = document.getElementById('edit_time');
                                            if (timeSelect && a.time_slot) {
                                                timeSelect.value = a.time_slot;
                                            }
                                        }, 100);
                                    });
                                }
                            }
                        }, 200);
                    } else {
                        // If no service found, just set staff and time directly
                        document.getElementById('edit_staff').value = a.staff_id || '';
                        const timeSelect = document.getElementById('edit_time');
                        if (timeSelect) {
                            timeSelect.innerHTML = `<option value="${a.time_slot}">${a.time_slot}</option>`;
                            timeSelect.value = a.time_slot;
                        }
                    }

                    const modal = document.getElementById('editModal');
                    modal.style.display = 'block';
                    setTimeout(() => {
                        modal.classList.remove('opacity-0');
                        modal.querySelector('.relative').classList.remove('scale-95');
                        modal.querySelector('.relative').classList.add('scale-100');
                    }, 10);
                }
            } catch (error) {
                console.error('Error in editAppointment:', error);
                showToast(texts.error, 'error');
            }
        }

        // Handle edit form
        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const id = document.getElementById('edit_id').value;
            const messageDiv = document.getElementById('editMessage');
            const submitBtn = document.querySelector('#editModal button[onclick*="requestSubmit"]');
            const originalBtnContent = submitBtn.innerHTML;

            const serviceSelect = document.getElementById('edit_service');
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const serviceName = selectedOption ? selectedOption.text : '';

            const data = {
                customer_name: document.getElementById('edit_name').value,
                customer_phone: document.getElementById('edit_phone').value,
                customer_email: document.getElementById('edit_email').value,
                appointment_date: document.getElementById('edit_date').value,
                appointment_time: document.getElementById('edit_time').value,
                service_type: serviceName,
                service_id: document.getElementById('edit_service').value,
                staff_id: document.getElementById('edit_staff').value,
                status: document.getElementById('edit_status').value,
                notes: document.getElementById('edit_notes').value
            };

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    ${isArabic ? 'جاري الحفظ...' : 'Saving...'}
                </span>
            `;

            try {
                const response = await fetch(`/admin/api/appointments/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    messageDiv.className = 'p-4 rounded-lg text-sm bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-200';
                    messageDiv.innerHTML = `
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-emerald-900">${texts.saved}</p>
                                <p class="text-xs text-emerald-700 mt-1">${isArabic ? 'جاري تحديث الصفحة...' : 'Refreshing page...'}</p>
                            </div>
                        </div>
                    `;
                    messageDiv.classList.remove('hidden');

                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    let errorMsg = result.message || texts.error;
                    let errorsList = '';

                    if (result.errors) {
                        const fieldErrors = Object.entries(result.errors);
                        errorsList = '<ul class="list-disc list-inside mt-2 space-y-1 text-xs">';
                        fieldErrors.forEach(([field, messages]) => {
                            messages.forEach(msg => {
                                errorsList += `<li>${msg}</li>`;
                            });
                        });
                        errorsList += '</ul>';
                    }

                    messageDiv.className = 'p-4 rounded-lg text-sm bg-gradient-to-r from-red-50 to-rose-50 border border-red-200';
                    messageDiv.innerHTML = `
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-red-900">${errorMsg}</p>
                                ${errorsList}
                            </div>
                        </div>
                    `;
                    messageDiv.classList.remove('hidden');

                    // Restore button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
            } catch (error) {
                console.error('Error updating appointment:', error);
                messageDiv.className = 'p-4 rounded-lg text-sm bg-gradient-to-r from-red-50 to-rose-50 border border-red-200';
                messageDiv.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-red-900">${texts.error}</p>
                            <p class="text-xs text-red-700 mt-1">${isArabic ? 'يرجى المحاولة مرة أخرى' : 'Please try again'}</p>
                        </div>
                    </div>
                `;
                messageDiv.classList.remove('hidden');

                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            }
        });

        // Add modal
        window.openAddModal = function() {
            console.log('🔵 Opening Add Modal');
            const modal = document.getElementById('addModal');
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('.relative').classList.remove('scale-95');
                modal.querySelector('.relative').classList.add('scale-100');
            }, 10);

            // Reset and disable time select initially
            const timeSelect = document.getElementById('add_time');
            const staffSelect = document.getElementById('add_staff');
            if (timeSelect) {
                timeSelect.innerHTML = `<option value="">${isArabic ? 'اختر الخدمة والموظف والتاريخ أولاً' : 'Select service, staff and date first'}</option>`;
                timeSelect.disabled = true;
            }
            if (staffSelect) {
                staffSelect.disabled = true;
                staffSelect.innerHTML = `<option value="">${isArabic ? 'اختر الخدمة أولاً' : 'Select service first'}</option>`;
            }
        }

        // Event listeners for date and staff changes to load available times
        const addDateInput = document.getElementById('add_date');
        if (addDateInput) {
            addDateInput.addEventListener('change', function() {
                const date = this.value;
                const staffId = document.getElementById('add_staff')?.value;
                if (date && staffId) {
                    loadAvailableTimeSlots(date, staffId, 'add');
                }
            });
        }

        const addStaffSelect = document.getElementById('add_staff');
        if (addStaffSelect) {
            addStaffSelect.addEventListener('change', function() {
                const staffId = this.value;
                const date = document.getElementById('add_date')?.value;
                if (date && staffId) {
                    loadAvailableTimeSlots(date, staffId, 'add');
                }
            });
        }

        const editDateInput = document.getElementById('edit_date');
        if (editDateInput) {
            editDateInput.addEventListener('change', function() {
                const date = this.value;
                const staffId = document.getElementById('edit_staff')?.value;
                const appointmentId = document.getElementById('edit_id')?.value;
                if (date && staffId) {
                    loadAvailableTimeSlots(date, staffId, 'edit', appointmentId);
                }
            });
        }

        const editStaffSelect = document.getElementById('edit_staff');
        if (editStaffSelect) {
            editStaffSelect.addEventListener('change', function() {
                const staffId = this.value;
                const date = document.getElementById('edit_date')?.value;
                const appointmentId = document.getElementById('edit_id')?.value;
                if (date && staffId) {
                    loadAvailableTimeSlots(date, staffId, 'edit', appointmentId);
                }
            });
        }

        window.closeAddModal = function() {
            const modal = document.getElementById('addModal');
            modal.classList.add('opacity-0');
            modal.querySelector('.relative').classList.remove('scale-100');
            modal.querySelector('.relative').classList.add('scale-95');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('addForm').reset();
                document.getElementById('addMessage').classList.add('hidden');

                // Reset service info visibility
                const serviceInfo = document.getElementById('add_service_info');
                if (serviceInfo) serviceInfo.classList.add('hidden');

                // Reset staff and time selects
                const staffSelect = document.getElementById('add_staff');
                const timeSelect = document.getElementById('add_time');
                if (staffSelect) {
                    staffSelect.disabled = true;
                    staffSelect.innerHTML = `<option value="">${isArabic ? 'اختر الخدمة أولاً' : 'Select service first'}</option>`;
                }
                if (timeSelect) {
                    timeSelect.disabled = true;
                    timeSelect.innerHTML = `<option value="">${isArabic ? 'اختر الموظف والتاريخ أولاً' : 'Select staff and date first'}</option>`;
                }
            }, 300);
        }

        window.closeEditModal = function() {
            const modal = document.getElementById('editModal');
            modal.classList.add('opacity-0');
            modal.querySelector('.relative').classList.remove('scale-100');
            modal.querySelector('.relative').classList.add('scale-95');
            setTimeout(() => {
                modal.style.display = 'none';
                document.getElementById('editForm').reset();
                document.getElementById('editMessage').classList.add('hidden');

                // Reset service info visibility
                const serviceInfo = document.getElementById('edit_service_info');
                if (serviceInfo) serviceInfo.classList.add('hidden');
            }, 300);
        }

        // Load time slots
        async function loadTimeSlots() {
            try {
                const response = await fetch('/api/booking/timeslots');
                const result = await response.json();

                if (result.success) {
                    const select = document.getElementById('add_time');
                    select.innerHTML = `<option value="">${isArabic ? 'اختر الوقت' : 'Select Time'}</option>`;

                    result.data.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.start_time;
                        option.textContent = `${slot.formatted_start_time} - ${slot.formatted_end_time}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading time slots:', error);
            }
        }

        // Load available time slots (filtering booked ones)
        async function loadAvailableTimeSlots(date, staffId, formType = 'add', excludeAppointmentId = null) {
            console.log('🕐 loadAvailableTimeSlots called:', { date, staffId, formType });

            const selectId = formType === 'add' ? 'add_time' : 'edit_time';
            const select = document.getElementById(selectId);

            if (!select) {
                console.error(`❌ Time select element not found: ${selectId}`);
                return;
            }

            if (!date || !staffId) {
                console.warn('⚠️ Date or staff not provided');
                select.innerHTML = `<option value="">${isArabic ? 'اختر التاريخ والموظف أولاً' : 'Select date and staff first'}</option>`;
                select.disabled = true;
                return;
            }

            // Check if date is in the past
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            selectedDate.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                console.error('❌ Selected date is in the past:', date);
                select.innerHTML = `<option value="">${isArabic ? 'لا يمكن الحجز في تاريخ سابق' : 'Cannot book past dates'}</option>`;
                select.disabled = true;
                showToast(isArabic ? 'الرجاء اختيار تاريخ اليوم أو تاريخ مستقبلي' : 'Please select today or a future date', 'error');
                return;
            }

            try {
                select.disabled = true;
                select.innerHTML = `<option value="">${isArabic ? 'جاري تحميل الأوقات...' : 'Loading times...'}</option>`;

                let url = `/api/booking/available-timeslots?date=${date}&staff_id=${staffId}`;
                if (excludeAppointmentId) {
                    url += `&exclude_appointment_id=${excludeAppointmentId}`;
                }

                console.log('📡 Fetching available times from:', url);
                const response = await fetch(url);

                console.log('📥 Response status:', response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                console.log('✅ Available times response:', result);

                if (result.success) {
                    if (result.data.length === 0) {
                        console.warn('⚠️ No available times for this date/staff. Reason:', result.reason);

                        let message = '';
                        let toastMessage = '';

                        if (result.reason === 'past_date') {
                            message = isArabic ? 'لا يمكن الحجز في تاريخ سابق' : 'Cannot book past dates';
                            toastMessage = isArabic ? 'الرجاء اختيار تاريخ اليوم أو تاريخ مستقبلي' : 'Please select today or a future date';
                        } else if (result.reason === 'staff_not_working') {
                            message = isArabic ? 'الموظف لا يعمل في هذا اليوم' : 'Staff doesn\'t work on this day';
                            toastMessage = isArabic ? 'الموظف المختار لا يعمل في هذا اليوم. الرجاء اختيار تاريخ آخر' : 'Selected staff doesn\'t work on this day. Please choose another date';
                        } else {
                            message = isArabic ? 'لا توجد أوقات متاحة في هذا التاريخ' : 'No available times for this date';
                            toastMessage = isArabic ? 'جميع الأوقات محجوزة في هذا التاريخ' : 'All times are booked for this date';
                        }

                        select.innerHTML = `<option value="">${message}</option>`;
                        select.disabled = true;
                        showToast(toastMessage, 'error');
                        return;
                    }

                    select.innerHTML = `<option value="">${isArabic ? 'اختر الوقت' : 'Select Time'}</option>`;
                    result.data.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.start_time;
                        option.textContent = `${slot.formatted_start_time} - ${slot.formatted_end_time}`;
                        select.appendChild(option);
                    });
                    select.disabled = false;
                    console.log(`✅ Loaded ${result.data.length} available time slots`);
                } else {
                    console.error('❌ API returned error:', result);
                    select.innerHTML = `<option value="">${isArabic ? 'خطأ في تحميل الأوقات' : 'Error loading times'}</option>`;
                    select.disabled = true;
                }
            } catch (error) {
                console.error('❌ Error loading available time slots:', error);
                select.innerHTML = `<option value="">${isArabic ? 'حدث خطأ في تحميل الأوقات' : 'Error loading times'}</option>`;
                select.disabled = true;
                showToast(isArabic ? 'فشل تحميل الأوقات المتاحة' : 'Failed to load available times', 'error');
            }
        }

        // Handle add form
        document.getElementById('addForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const messageDiv = document.getElementById('addMessage');
            const submitBtn = document.querySelector('#addModal button[onclick*="requestSubmit"]');
            const originalBtnContent = submitBtn.innerHTML;

            const appointmentDate = document.getElementById('add_date').value;
            const appointmentTime = document.getElementById('add_time').value;
            const serviceId = document.getElementById('add_service').value;
            const staffId = document.getElementById('add_staff').value;

            // Client-side validation
            if (!serviceId) {
                showToast(isArabic ? 'الرجاء اختيار الخدمة' : 'Please select a service', 'error');
                return;
            }
            if (!staffId) {
                showToast(isArabic ? 'الرجاء اختيار الموظف' : 'Please select staff', 'error');
                return;
            }
            if (!appointmentDate) {
                showToast(isArabic ? 'الرجاء اختيار التاريخ' : 'Please select date', 'error');
                return;
            }

            // Check if date is in the past
            const selectedDate = new Date(appointmentDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            selectedDate.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                showToast(isArabic ? 'لا يمكن الحجز في تاريخ سابق. الرجاء اختيار تاريخ اليوم أو تاريخ مستقبلي' : 'Cannot book past dates. Please select today or a future date', 'error');
                return;
            }

            if (!appointmentTime) {
                showToast(isArabic ? 'الرجاء اختيار الوقت' : 'Please select time', 'error');
                return;
            }

            const serviceSelect = document.getElementById('add_service');
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const serviceName = selectedOption ? selectedOption.text : '';

            const data = {
                customer_name: document.getElementById('add_name').value,
                customer_phone: document.getElementById('add_phone').value,
                customer_email: document.getElementById('add_email').value,
                appointment_date: appointmentDate,
                appointment_time: appointmentTime,
                service_type: serviceName,
                service_id: serviceId,
                staff_id: staffId,
                notes: document.getElementById('add_notes').value,
                add_to_queue: document.getElementById('add_to_queue').checked,
                queue_date: appointmentDate  // تاريخ الطابور = تاريخ الموعد
            };

            console.log('📤 Submitting appointment data:', data);

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    ${isArabic ? 'جاري الحفظ...' : 'Saving...'}
                </span>
            `;

            try {
                console.log('📡 Sending POST request to: /admin/api/appointments');
                console.log('📦 Request headers:', {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? 'Present' : 'Missing'
                });

                const response = await fetch('/admin/api/appointments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });

                console.log('📥 Response status:', response.status, response.statusText);
                const result = await response.json();
                console.log('📄 Response data:', result);

                if (response.ok && result.success) {
                    console.log('✅ Appointment saved successfully!', result.data);
                    messageDiv.className = 'p-4 rounded-lg text-sm bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-200';
                    messageDiv.innerHTML = `
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-emerald-900">${result.message}</p>
                                <p class="text-xs text-emerald-700 mt-1">${isArabic ? 'جاري تحديث الصفحة...' : 'Refreshing page...'}</p>
                            </div>
                        </div>
                    `;
                    messageDiv.classList.remove('hidden');

                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    console.error('❌ Failed to save appointment:', result);
                    let errorMsg = result.message || texts.error;
                    let errorsList = '';

                    if (result.errors) {
                        console.error('❌ Validation errors:', result.errors);
                        const fieldErrors = Object.entries(result.errors);
                        errorsList = '<ul class="list-disc list-inside mt-2 space-y-1 text-xs">';
                        fieldErrors.forEach(([field, messages]) => {
                            messages.forEach(msg => {
                                errorsList += `<li>${msg}</li>`;
                            });
                        });
                        errorsList += '</ul>';
                    }

                    messageDiv.className = 'p-4 rounded-lg text-sm bg-gradient-to-r from-red-50 to-rose-50 border border-red-200';
                    messageDiv.innerHTML = `
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-red-900">${errorMsg}</p>
                                ${errorsList}
                            </div>
                        </div>
                    `;
                    messageDiv.classList.remove('hidden');

                    // Restore button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnContent;
                }
            } catch (error) {
                console.error('❌ Network or parsing error:', error);
                console.error('Error name:', error.name);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);

                messageDiv.className = 'p-4 rounded-lg text-sm bg-gradient-to-r from-red-50 to-rose-50 border border-red-200';
                messageDiv.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-red-900">${texts.error}</p>
                            <p class="text-xs text-red-700 mt-1">${isArabic ? 'يرجى المحاولة مرة أخرى' : 'Please try again'}</p>
                        </div>
                    </div>
                `;
                messageDiv.classList.remove('hidden');

                // Restore button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;
            }
        });

        // Delete appointment
        window.deleteAppointment = async function(id) {
            console.log('deleteAppointment called with id:', id);
            if (!confirm(texts.confirmDelete)) return;

            try {
                const response = await fetch(`/admin/api/appointments/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    document.getElementById(`row-${id}`).remove();
                    showToast(texts.deleted, 'success');
                } else {
                    showToast(result.message || texts.error, 'error');
                }
            } catch (error) {
                showToast(texts.error, 'error');
            }
        }

        // Print appointments
        function printAppointments() {
            const printContent = document.getElementById('appointmentsTable');
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
                <!DOCTYPE html>
                <html dir="${isArabic ? 'rtl' : 'ltr'}">
                <head>
                    <meta charset="UTF-8">
                    <title>${isArabic ? 'طباعة المواعيد' : 'Print Appointments'}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { text-align: center; margin-bottom: 20px; font-size: 24px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { border: 1px solid #ddd; padding: 10px; text-align: ${isArabic ? 'right' : 'left'}; font-size: 12px; }
                        th { background-color: #f3f4f6; font-weight: bold; }
                        tr:nth-child(even) { background-color: #f9fafb; }
                        .status-pending { color: #92400e; background: #fef3c7; padding: 2px 8px; border-radius: 12px; }
                        .status-confirmed { color: #166534; background: #dcfce7; padding: 2px 8px; border-radius: 12px; }
                        .status-cancelled { color: #991b1b; background: #fee2e2; padding: 2px 8px; border-radius: 12px; }
                        .status-completed { color: #1e40af; background: #dbeafe; padding: 2px 8px; border-radius: 12px; }
                        .print-header { text-align: center; margin-bottom: 30px; }
                        .print-date { color: #666; font-size: 12px; }
                        @media print {
                            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h1>{{ tenant()->name }}</h1>
                        <p>${isArabic ? 'قائمة المواعيد' : 'Appointments List'}</p>
                        <p class="print-date">${isArabic ? 'تاريخ الطباعة:' : 'Print Date:'} ${new Date().toLocaleDateString(isArabic ? 'ar-EG' : 'en-US')}</p>
                    </div>
                    ${printContent.outerHTML}
                </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.print();
            };
        }

        // Export to Excel
        function exportExcel() {
            const params = new URLSearchParams(window.location.search);
            // Add date filter if set
            const dateFilter = document.querySelector('select[name="date_filter"]')?.value;
            if (dateFilter) {
                params.set('period', dateFilter);
            }
            window.location.href = `/admin/api/appointments/export-excel?${params}`;
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 ${isArabic ? 'left-4' : 'right-4'} px-4 py-3 rounded-lg shadow-lg text-white text-sm z-50 ${type === 'success' ? 'bg-emerald-600' : 'bg-red-600'}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.remove(), 3000);
        }

        // Add to Queue
        async function addToQueue(appointmentId) {
            console.log('🟢 addToQueue called with ID:', appointmentId);
            try {
                const response = await fetch(`/admin/api/appointments/${appointmentId}/add-to-queue`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(result.message, 'success');
                    // Reload page to show updated queue status
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message || texts.error, 'error');
                }
            } catch (error) {
                console.error('Error adding to queue:', error);
                showToast(texts.error, 'error');
            }
        }

        // Remove from Queue
        async function removeFromQueue(appointmentId) {
            console.log('🔴 removeFromQueue called with ID:', appointmentId);
            if (!confirm(isArabic ? 'هل أنت متأكد من إزالة هذا الموعد من الطابور؟' : 'Are you sure you want to remove this appointment from the queue?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/api/appointments/${appointmentId}/remove-from-queue`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(result.message, 'success');
                    // Reload page to show updated queue status
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.message || texts.error, 'error');
                }
            } catch (error) {
                console.error('Error removing from queue:', error);
                showToast(texts.error, 'error');
            }
        }

        // Send Reminder
        async function sendReminder(appointmentId) {
            console.log('📱 sendReminder called with ID:', appointmentId);

            if (!confirm(isArabic ? 'هل تريد إرسال تذكير لهذا العميل؟' : 'Do you want to send a reminder to this customer?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/api/appointments/${appointmentId}/send-reminder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showToast(result.message, 'success');
                } else {
                    showToast(result.message || texts.error, 'error');
                }
            } catch (error) {
                console.error('Error sending reminder:', error);
                showToast(texts.error, 'error');
            }
        }

        // Close modals on outside click
        ['viewModal', 'editModal', 'addModal'].forEach(modalId => {
            document.getElementById(modalId).addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        console.log('All event listeners attached. Functions ready:', {
            viewAppointment: typeof viewAppointment,
            editAppointment: typeof editAppointment,
            deleteAppointment: typeof deleteAppointment,
            addToQueue: typeof addToQueue,
            removeFromQueue: typeof removeFromQueue,
            sendReminder: typeof sendReminder
        });

        // ==================== BULK OPERATIONS ====================

        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.appointment-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateBulkSelection();
        }

        function updateBulkSelection() {
            const checkboxes = document.querySelectorAll('.appointment-checkbox:checked');
            const count = checkboxes.length;
            const countSpan = document.getElementById('bulkSelectedCount');
            const actionsBar = document.getElementById('bulkActionsBar');
            const selectAllCheckbox = document.getElementById('selectAll');

            if (count > 0) {
                countSpan.textContent = `${count} ${isArabic ? 'محدد' : 'selected'}`;
                countSpan.classList.remove('hidden');
                actionsBar.classList.remove('hidden');
            } else {
                countSpan.classList.add('hidden');
                actionsBar.classList.add('hidden');
                selectAllCheckbox.checked = false;
            }
        }

        function clearBulkSelection() {
            document.querySelectorAll('.appointment-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBulkSelection();
        }

        async function applyBulkAction() {
            const action = document.getElementById('bulkAction').value;
            if (!action) {
                showToast(isArabic ? 'اختر عملية أولاً' : 'Select an action first', 'error');
                return;
            }

            const checkboxes = document.querySelectorAll('.appointment-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);

            if (ids.length === 0) {
                showToast(isArabic ? 'لم يتم اختيار أي موعد' : 'No appointments selected', 'error');
                return;
            }

            const actionText = {
                'confirm': isArabic ? 'تأكيد' : 'confirm',
                'complete': isArabic ? 'إكمال' : 'complete',
                'cancel': isArabic ? 'إلغاء' : 'cancel',
                'delete': isArabic ? 'حذف' : 'delete'
            }[action];

            if (!confirm(`${isArabic ? 'هل تريد' : 'Do you want to'} ${actionText} ${ids.length} ${isArabic ? 'موعد؟' : 'appointments?'}`)) {
                return;
            }

            try {
                showToast(isArabic ? 'جاري التطبيق...' : 'Applying...', 'info');

                for (const id of ids) {
                    if (action === 'delete') {
                        await fetch(`/admin/api/appointments/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });
                    } else {
                        const statusMap = {
                            'confirm': 'confirmed',
                            'complete': 'completed',
                            'cancel': 'cancelled'
                        };
                        await fetch(`/admin/api/appointments/${id}/status`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ status: statusMap[action] })
                        });
                    }
                }

                showToast(isArabic ? 'تم التطبيق بنجاح!' : 'Applied successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } catch (error) {
                console.error('Bulk action error:', error);
                showToast(isArabic ? 'حدث خطأ أثناء التطبيق' : 'Error applying action', 'error');
            }
        }

        // QR Code Modal Functions
        function showQRCode(id) {
            const modal = document.createElement('div');
            modal.id = 'qrModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center';
            modal.innerHTML = `
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md mx-4 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-slate-100 dark:text-slate-100">${isArabic ? 'رمز الاستجابة السريعة' : 'QR Code'}</h3>
                        <button onclick="closeQRModal()" class="text-slate-400 dark:text-slate-400 hover:text-slate-300 dark:hover:text-slate-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex justify-center items-center bg-white dark:bg-slate-700 rounded-lg p-4">
                        <img src="/admin/api/appointments/${id}/qrcode" alt="QR Code" class="w-64 h-64">
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="/admin/api/appointments/${id}/qrcode" download="appointment-${id}-qr.png"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            ${isArabic ? 'تحميل' : 'Download'}
                        </a>
                        <button onclick="closeQRModal()" class="flex-1 px-4 py-2 bg-white dark:bg-slate-700 text-slate-300 dark:text-slate-300 rounded-lg hover:bg-slate-600 dark:hover:bg-slate-600 text-sm font-medium">
                            ${isArabic ? 'إغلاق' : 'Close'}
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function closeQRModal() {
            const modal = document.getElementById('qrModal');
            if (modal) modal.remove();
        }

        // View Toggle Functions
        function toggleView(view) {
            const calendarView = document.getElementById('calendarView');
            const groupedView = document.getElementById('groupedView');
            const listView = document.getElementById('listView');
            const calendarBtn = document.getElementById('calendarViewBtn');
            const groupedBtn = document.getElementById('groupedViewBtn');

            // Hide all views
            calendarView.classList.add('hidden');
            groupedView.classList.add('hidden');
            listView.classList.add('hidden');

            // Reset button styles
            calendarBtn.classList.remove('bg-indigo-600', 'text-white');
            calendarBtn.classList.add('bg-slate-100 dark:bg-slate-800', 'text-slate-700 dark:text-slate-300');
            groupedBtn.classList.remove('bg-indigo-600', 'text-white');
            groupedBtn.classList.add('bg-slate-100 dark:bg-slate-800', 'text-slate-700 dark:text-slate-300');

            // Show selected view and highlight button
            if (view === 'calendar') {
                calendarView.classList.remove('hidden');
                calendarBtn.classList.remove('bg-slate-100 dark:bg-slate-800', 'text-slate-700 dark:text-slate-300');
                calendarBtn.classList.add('bg-indigo-600', 'text-white');
            } else if (view === 'grouped') {
                groupedView.classList.remove('hidden');
                groupedBtn.classList.remove('bg-slate-100 dark:bg-slate-800', 'text-slate-700 dark:text-slate-300');
                groupedBtn.classList.add('bg-indigo-600', 'text-white');
            } else {
                listView.classList.remove('hidden');
            }
        }

        // Toggle individual day accordion with lazy loading
        function toggleDay(date) {
            const content = document.getElementById('content-' + date);
            const icon = document.getElementById('icon-' + date);

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';

                // Lazy loading: mark as loaded
                if (content.dataset.loaded === 'false') {
                    content.dataset.loaded = 'true';
                }
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Bulk day action
        async function bulkDayAction(action, date, appointmentIds) {
            if (!confirm('Are you sure you want to ' + action.replace('_', ' ') + ' for this day?')) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.api.appointments.bulkDayAction') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        appointment_ids: appointmentIds
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification(data.message || 'Success!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Error occurred', 'error');
                }
            } catch (error) {
                console.error('Bulk action error:', error);
                showNotification('Network error occurred', 'error');
            }
        }

        // Expand all days
        function expandAllDays() {
            const allContents = document.querySelectorAll('[id^="content-"]');
            const allIcons = document.querySelectorAll('[id^="icon-"]');

            allContents.forEach(content => {
                content.classList.remove('hidden');
                // Mark as loaded for lazy loading
                if (content.dataset.loaded === 'false') {
                    content.dataset.loaded = 'true';
                }
            });

            allIcons.forEach(icon => {
                icon.style.transform = 'rotate(180deg)';
            });
        }

        // Collapse all days
        function collapseAllDays() {
            const allContents = document.querySelectorAll('[id^="content-"]');
            const allIcons = document.querySelectorAll('[id^="icon-"]');

            allContents.forEach(content => {
                content.classList.add('hidden');
            });

            allIcons.forEach(icon => {
                icon.style.transform = 'rotate(0deg)';
            });
        }

        // Auto-expand today's appointments when grouped view is shown
        document.addEventListener('DOMContentLoaded', function() {
            // Find today's date element and expand it
            const todayElements = document.querySelectorAll('[class*="ring-2 ring-blue-500"]');
            if (todayElements.length > 0) {
                todayElements.forEach(el => {
                    const button = el.querySelector('button[onclick^="toggleDay"]');
                    if (button) {
                        const onclick = button.getAttribute('onclick');
                        const dateMatch = onclick.match(/toggleDay\('([^']+)'\)/);
                        if (dateMatch) {
                            const date = dateMatch[1];
                            const content = document.getElementById('content-' + date);
                            const icon = document.getElementById('icon-' + date);
                            if (content && icon) {
                                content.classList.remove('hidden');
                                icon.style.transform = 'rotate(180deg)';
                                // Mark as loaded for lazy loading
                                if (content.dataset.loaded === 'false') {
                                    content.dataset.loaded = 'true';
                                }
                            }
                        }
                    }
                });
            }
        });

        // Statistics Modal Functions
        function openStatsModal() {
            const modal = document.getElementById('statsModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                // Trigger animation
                setTimeout(() => {
                    const content = modal.querySelector('.scale-95');
                    if (content) {
                        content.classList.remove('scale-95', 'opacity-0');
                        content.classList.add('scale-100', 'opacity-100');
                    }
                }, 10);
            }
        }

        function closeStatsModal() {
            const modal = document.getElementById('statsModal');
            if (modal) {
                const content = modal.querySelector('.scale-100');
                if (content) {
                    content.classList.remove('scale-100', 'opacity-100');
                    content.classList.add('scale-95', 'opacity-0');
                }
                // Wait for animation to complete
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 300);
            }
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeStatsModal();
            }
        });

        // Actions Menu Functions
        function toggleActionsMenu(appointmentId) {
            const menu = document.getElementById('actions-menu-' + appointmentId);
            if (!menu) return;

            // Close all other menus first
            document.querySelectorAll('[id^="actions-menu-"]').forEach(m => {
                if (m.id !== 'actions-menu-' + appointmentId) {
                    m.classList.add('hidden');
                }
            });

            // Toggle current menu
            menu.classList.toggle('hidden');
        }

        // Close actions menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[onclick^="toggleActionsMenu"]') && !e.target.closest('[id^="actions-menu-"]')) {
                document.querySelectorAll('[id^="actions-menu-"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // Loading Overlay Functions
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('hidden');
            }
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.add('hidden');
            }
        }

        // Close status dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="status-btn-"]') && !e.target.closest('[id^="status-dropdown-"]')) {
                document.querySelectorAll('[id^="status-dropdown-"]').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });
            }
        });

        // =========================================
        // PERFORMANCE & RESPONSIVE ENHANCEMENTS
        // =========================================

        // Toggle Filters (Mobile)
        function toggleFilters() {
            const container = document.getElementById('filtersContainer');
            const icon = document.getElementById('filterToggleIcon');

            if (container.classList.contains('expanded')) {
                container.classList.remove('expanded');
                container.classList.add('collapsed');
                icon.style.transform = 'rotate(-90deg)';
            } else {
                container.classList.remove('collapsed');
                container.classList.add('expanded');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Back to Top Button
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show/Hide Back to Top Button
        window.addEventListener('scroll', function() {
            const backToTopBtn = document.getElementById('backToTop');
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        // Search Debounce (Auto-submit after typing stops)
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Auto-submit form after 800ms of no typing
                    if (e.target.value.length >= 3 || e.target.value.length === 0) {
                        document.getElementById('filterForm').submit();
                    }
                }, 800);
            });
        }

        // Performance: Cache stats in localStorage (5 minutes)
        function cacheStats() {
            const statsData = {
                today: {{ $stats['today'] ?? 0 }},
                pending: {{ $stats['pending'] ?? 0 }},
                week: {{ $stats['this_week'] ?? 0 }},
                timestamp: Date.now()
            };
            localStorage.setItem('appointments_stats', JSON.stringify(statsData));
        }

        function loadCachedStats() {
            const cached = localStorage.getItem('appointments_stats');
            if (cached) {
                const data = JSON.parse(cached);
                // Cache expires after 5 minutes
                if (Date.now() - data.timestamp < 5 * 60 * 1000) {
                    return data;
                }
            }
            return null;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Cache current stats
            cacheStats();

            // Add mobile swipe hint on first visit
            if (!localStorage.getItem('swipe_hint_shown')) {
                const firstRow = document.querySelector('tbody tr:first-child');
                if (firstRow && window.innerWidth < 768) {
                    setTimeout(() => {
                        firstRow.classList.add('swipe-hint');
                        setTimeout(() => {
                            firstRow.classList.remove('swipe-hint');
                            localStorage.setItem('swipe_hint_shown', 'true');
                        }, 3000);
                    }, 1000);
                }
            }

            // Lazyload images/avatars (if any)
            const lazyImages = document.querySelectorAll('img[data-src]');
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                lazyImages.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for browsers without IntersectionObserver
                lazyImages.forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            }

            // Prefetch next page for better UX
            const nextPageLink = document.querySelector('.pagination a[rel="next"]');
            if (nextPageLink && 'requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = nextPageLink.href;
                    document.head.appendChild(link);
                });
            }
        });

        // Performance: Throttle window resize events
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                // Re-calculate any layout-dependent elements
                console.log('Window resized, recalculating layouts...');
            }, 250);
        });
    </script>
    <script src="/js/dark-mode.js"></script>
</body>
</html>














