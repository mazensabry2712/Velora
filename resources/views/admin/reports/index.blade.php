<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Reports') }} - {{ tenant()->name }}</title>

    <!-- Prevent Flash of White Content -->
    <script>
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
</head>
<body class="bg-slate-50 dark:bg-slate-900">
    @include('partials.admin-nav')

    <!-- Page Header -->
    <header class="bg-white dark:bg-slate-800 shadow-sm">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">التقارير والإحصائيات</h2>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-500 dark:bg-indigo-600 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">إجمالي الحجوزات</p>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['total_appointments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-emerald-500 dark:bg-emerald-600 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">حجوزات مؤكدة</p>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['confirmed_appointments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-amber-500 dark:bg-amber-600 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">في الانتظار</p>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['pending_appointments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 dark:bg-purple-600 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="mr-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">إجمالي العملاء</p>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $stats['total_customers'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Appointments Report -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">تقرير الحجوزات</h3>
                @if($appointmentsByStatus->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($appointmentsByStatus as $item)
                            <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                                <div class="flex items-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if($item->status === 'confirmed') bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200
                                        @elseif($item->status === 'pending') bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200
                                        @elseif($item->status === 'cancelled') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                        @else bg-slate-100 dark:bg-slate-600 text-slate-800 dark:text-slate-200
                                        @endif">
                                        @if($item->status === 'confirmed') مؤكد
                                        @elseif($item->status === 'pending') قيد الانتظار
                                        @elseif($item->status === 'cancelled') ملغي
                                        @else {{ $item->status }}
                                        @endif
                                    </span>
                                </div>
                                <div class="text-left">
                                    <span class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $item->count }}</span>
                                    <span class="text-sm text-slate-500 dark:text-slate-400">حجز</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-slate-500 dark:text-slate-400">لا توجد بيانات كافية لعرض التقرير</p>
                    </div>
                @endif
            </div>

            <!-- Queue Statistics -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">إحصائيات قائمة الانتظار</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-indigo-50 dark:bg-indigo-900 rounded">
                        <span class="text-slate-700 dark:text-slate-200">في الانتظار</span>
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $queueStats['waiting'] }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-emerald-50 dark:bg-emerald-900 rounded">
                        <span class="text-slate-700 dark:text-slate-200">يتم الخدمة</span>
                        <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $queueStats['serving'] }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                        <span class="text-slate-700 dark:text-slate-200">مكتمل</span>
                        <span class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ $queueStats['completed'] }}</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-amber-50 dark:bg-amber-900 rounded">
                        <span class="text-slate-700 dark:text-slate-200">عملاء ذوي أولوية</span>
                        <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $queueStats['priority'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Staff Performance -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">أداء الموظفين</h3>
                @if($staffPerformance->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($staffPerformance as $staff)
                            <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-slate-100">{{ $staff->name }}</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $staff->role?->name ?? 'موظف' }}</p>
                                </div>
                                <div class="text-left">
                                    <span class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $staff->appointments_count }}</span>
                                    <span class="text-sm text-slate-500 dark:text-slate-400">حجز</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-slate-500 dark:text-slate-400">لا توجد بيانات كافية لعرض التقرير</p>
                    </div>
                @endif
            </div>

            <!-- Service Types Report -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">تقرير الخدمات</h3>
                @if($serviceTypes->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($serviceTypes as $service)
                            <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                                <span class="text-slate-700 dark:text-slate-200">{{ $service->service_type ?? 'غير محدد' }}</span>
                                <div class="text-left">
                                    <span class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $service->count }}</span>
                                    <span class="text-sm text-slate-500 dark:text-slate-400">حجز</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-slate-500 dark:text-slate-400">لا توجد بيانات كافية لعرض التقرير</p>
                    </div>
                @endif
            </div>
        </div>
    </main>
    <script src="/js/dark-mode.js"></script>
</body>
</html>
