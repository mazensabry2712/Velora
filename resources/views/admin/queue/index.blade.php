<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Manage Queue') }} - {{ tenant()->name }}</title>

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
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.queue') }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">إدارة قائمة الانتظار</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        {{ \Carbon\Carbon::parse($date)->locale('ar')->translatedFormat('l d M Y') }}
                        @if($date === now()->toDateString())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-300 mr-1">اليوم</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
            <div class="mb-6 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">قائمة الانتظار الحالية</h3>
                <div class="space-x-2 space-x-reverse">
                    <a href="{{ route('admin.queue.print', ['date' => $date]) }}" target="_blank" class="inline-block bg-slate-600 dark:bg-slate-700 text-white px-4 py-2 rounded-lg hover:bg-slate-700 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-5 h-5 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        طباعة
                    </a>
                    <button onclick="exportToExcel()" class="bg-emerald-600 dark:bg-emerald-500 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition-colors">
                        <svg class="w-5 h-5 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Excel
                    </button>
                    <button onclick="callNext()" class="bg-emerald-600 dark:bg-emerald-500 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600">
                        استدعاء التالي
                    </button>
                    <button onclick="openAddModal()" class="bg-indigo-600 dark:bg-indigo-500 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600">
                        إضافة عميل
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg p-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg p-4">
                    {{ session('error') }}
                </div>
            @endif

            @if($queues->count() > 0)
                <!-- Queue Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">رقم الدور</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">اسم العميل</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">رقم الهاتف</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">البريد الإلكتروني</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">الموظف</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">الخدمة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">الأولوية</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">ملاحظات</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">الحالة</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($queues as $queue)
                                <tr class="@if($queue->is_vip) bg-yellow-50 dark:bg-yellow-900/20 @endif hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-slate-900 dark:text-slate-100">
                                        #{{ $queue->queue_number }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-slate-100">
                                        {{ $queue->appointment?->customer?->name ?? 'غير محدد' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                        {{ $queue->appointment?->customer?->phone ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                        {{ $queue->appointment?->customer?->email ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                                        {{ $queue->appointment?->staff?->name ?? '-' }}
                                        @if($queue->appointment?->staff?->specialization)
                                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $queue->appointment?->staff?->specialization }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                        @if($queue->appointment?->service)
                                            {{ app()->getLocale() === 'ar' && $queue->appointment->service->name_ar ? $queue->appointment->service->name_ar : $queue->appointment->service->name }}
                                            <span class="block text-xs text-blue-600 dark:text-blue-400">{{ $queue->appointment->service->duration }} {{ __('min') }}</span>
                                        @elseif($queue->appointment?->service_type)
                                            {{ $queue->appointment->service_type }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        @if($queue->is_vip)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300">
                                                ⭐ VIP
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">عادي</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-400 max-w-xs">
                                        @if($queue->notes)
                                            <span class="block truncate" title="{{ $queue->notes }}">{{ \Illuminate\Support\Str::limit($queue->notes, 30) }}</span>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($queue->status === 'waiting') bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300
                                            @elseif($queue->status === 'serving') bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300
                                            @elseif($queue->status === 'completed') bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300
                                            @elseif($queue->status === 'cancelled') bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300
                                            @else bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300
                                            @endif">
                                            @if($queue->status === 'waiting') في الانتظار
                                            @elseif($queue->status === 'serving') يتم الخدمة
                                            @elseif($queue->status === 'completed') مكتمل
                                            @elseif($queue->status === 'cancelled') تم التخطي
                                            @else {{ $queue->status }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
                                        <button onclick="viewQueue({{ $queue->id }})" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100">عرض</button>
                                        @if($queue->status === 'waiting')
                                            <button onclick="serveQueue({{ $queue->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">خدمة</button>
                                            <button onclick="setPriority({{ $queue->id }}, {{ $queue->is_vip ? 0 : 1 }})" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300">
                                                {{ $queue->is_vip ? 'عادي' : 'VIP' }}
                                            </button>
                                        @endif
                                        @if($queue->status === 'serving')
                                            <button onclick="completeQueue({{ $queue->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">إنهاء</button>
                                            <button onclick="returnToWaiting({{ $queue->id }})" class="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300">إرجاع</button>
                                        @endif
                                        <button onclick="editQueue({{ $queue->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">تعديل</button>
                                        <button onclick="removeQueue({{ $queue->id }})" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">حذف</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">لا يوجد أحد في قائمة الانتظار</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">ابدأ بإضافة عملاء إلى القائمة</p>
                </div>
            @endif
        </div>
    </main>

    <!-- Add to Queue Modal -->
    <div id="addQueueModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border border-slate-200 dark:border-slate-700 w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-lg bg-white dark:bg-slate-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">إضافة عميل للقائمة</h3>
                <button onclick="closeAddModal()" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="addQueueForm" class="space-y-4">
                @csrf

                <!-- بيانات العميل -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">اسم العميل <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">رقم الهاتف <span class="text-red-500">*</span></label>
                        <input type="tel" name="customer_phone" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">البريد الإلكتروني</label>
                    <input type="email" name="customer_email" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                </div>

                <!-- اختيار التخصص والموظف والخدمة -->
                <div class="border-t border-slate-200 dark:border-slate-700 pt-4 mt-4">
                    <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-3">اختيار الموظف والخدمة</h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- التخصص -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">التخصص <span class="text-red-500">*</span></label>
                            <select name="specialization" id="specializationSelect" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                                <option value="">اختر التخصص</option>
                                @php
                                    $specializations = \App\Models\User::whereHas('role', fn($q) => $q->where('name', 'Staff'))
                                        ->whereNotNull('specialization')
                                        ->where('specialization', '!=', '')
                                        ->select('specialization')
                                        ->distinct()
                                        ->get();
                                @endphp
                                @foreach($specializations as $spec)
                                    <option value="{{ $spec->specialization }}">{{ $spec->specialization }}</option>
                                @endforeach
                            </select>
                            @if($specializations->isEmpty())
                                <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">⚠️ لا توجد تخصصات - يرجى إضافة تخصصات للموظفين من <a href="{{ route('admin.staff') }}" class="underline">صفحة الموظفين</a></p>
                            @endif
                        </div>

                        <!-- الموظف -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">الموظف <span class="text-red-500">*</span></label>
                            <select name="staff_id" id="staffSelect" required disabled class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 disabled:bg-slate-100 dark:disabled:bg-slate-800">
                                <option value="">اختر التخصص أولاً</option>
                            </select>
                        </div>

                        <!-- الخدمة -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">الخدمة <span class="text-red-500">*</span></label>
                            <select name="service_id" id="serviceSelect" required disabled class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 disabled:bg-slate-100 dark:disabled:bg-slate-800">
                                <option value="">اختر الموظف أولاً</option>
                            </select>
                        </div>
                    </div>

                    <!-- معلومات الخدمة المختارة -->
                    <div id="serviceInfo" class="hidden mt-3 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <span class="font-medium">مدة الخدمة:</span>
                            <span id="serviceDuration">-</span> دقيقة
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ملاحظات</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400" placeholder="أي ملاحظات إضافية عن العميل..."></textarea>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_priority" value="1" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                        <span class="mr-2 text-sm font-medium text-slate-700 dark:text-slate-300">⭐ عميل له أولوية (VIP)</span>
                    </label>
                </div>

                <div id="addErrorMessage" class="hidden bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg p-3"></div>
                <div id="addSuccessMessage" class="hidden bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg p-3"></div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">
                        إضافة للقائمة
                    </button>
                    <button type="button" onclick="closeAddModal()" class="flex-1 bg-slate-300 dark:bg-slate-600 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg hover:bg-slate-400 dark:hover:bg-slate-500 transition-colors">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Queue Modal -->
    <div id="editQueueModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border border-slate-200 dark:border-slate-700 w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-lg bg-white dark:bg-slate-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">تعديل بيانات العميل</h3>
                <button onclick="closeEditModal()" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="editQueueForm" class="space-y-4">
                @csrf
                <input type="hidden" name="queue_id" id="edit_queue_id">

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">اسم العميل <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" id="edit_customer_name" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">رقم الهاتف <span class="text-red-500">*</span></label>
                    <input type="tel" name="customer_phone" id="edit_customer_phone" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">البريد الإلكتروني</label>
                    <input type="email" name="customer_email" id="edit_customer_email" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ملاحظات</label>
                    <textarea name="notes" id="edit_notes" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400" placeholder="أي ملاحظات إضافية عن العميل..."></textarea>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_vip" id="edit_is_vip" value="1" class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                        <span class="mr-2 text-sm font-medium text-slate-700 dark:text-slate-300">⭐ عميل له أولوية (VIP)</span>
                    </label>
                </div>

                <div id="editErrorMessage" class="hidden bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg p-3"></div>
                <div id="editSuccessMessage" class="hidden bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg p-3"></div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">
                        حفظ التعديلات
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-slate-300 dark:bg-slate-600 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg hover:bg-slate-400 dark:hover:bg-slate-500 transition-colors">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Queue Modal -->
    <div id="viewQueueModal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border border-slate-200 dark:border-slate-700 w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-lg bg-white dark:bg-slate-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">تفاصيل العميل</h3>
                <button onclick="closeViewModal()" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div id="viewQueueContent" class="space-y-4">
                <!-- Queue Number & Status -->
                <div class="flex items-center justify-between bg-slate-50 dark:bg-slate-700 p-4 rounded-lg">
                    <div class="text-center">
                        <span class="text-3xl font-bold text-blue-600 dark:text-blue-400" id="view_queue_number">#1</span>
                        <p class="text-sm text-slate-500 dark:text-slate-400">رقم الدور</p>
                    </div>
                    <div id="view_status_badge"></div>
                </div>

                <!-- Customer Info -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        بيانات العميل
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">الاسم</label>
                            <p class="font-medium text-slate-900 dark:text-slate-100" id="view_customer_name">-</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">رقم الهاتف</label>
                            <p class="font-medium text-slate-900 dark:text-slate-100" id="view_customer_phone">-</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">البريد الإلكتروني</label>
                            <p class="font-medium text-slate-900 dark:text-slate-100" id="view_customer_email">-</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">الأولوية</label>
                            <p id="view_vip_status">-</p>
                        </div>
                    </div>
                    <!-- Notes Section -->
                    <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700" id="view_notes_section">
                        <label class="text-xs text-slate-500 dark:text-slate-400">ملاحظات</label>
                        <p class="font-medium text-slate-900 dark:text-slate-100 whitespace-pre-wrap" id="view_notes">-</p>
                    </div>
                </div>

                <!-- Staff & Service Info -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        الموظف والخدمة
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">الموظف</label>
                            <p class="font-medium text-slate-900 dark:text-slate-100" id="view_staff_name">-</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400" id="view_staff_specialization"></p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">الخدمة</label>
                            <p class="font-medium text-slate-900 dark:text-slate-100" id="view_service_name">-</p>
                            <p class="text-sm text-blue-600 dark:text-blue-400" id="view_service_duration"></p>
                        </div>
                    </div>
                </div>

                <!-- Time Info -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-3 flex items-center">
                        <svg class="w-5 h-5 ml-2 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        معلومات الوقت
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">تاريخ الإضافة</label>
                            <p class="font-medium text-slate-900 dark:text-slate-100" id="view_created_at">-</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400">آخر تحديث</label>
                            <p class="font-medium text-slate-900 dark:text-slate-100" id="view_updated_at">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex gap-3">
                <button onclick="editQueueFromView()" class="flex-1 bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">
                    تعديل
                </button>
                <button onclick="closeViewModal()" class="flex-1 bg-slate-300 dark:bg-slate-600 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg hover:bg-slate-400 dark:hover:bg-slate-500 transition-colors">
                    إغلاق
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentViewQueueId = null;

        function openAddModal() {
            document.getElementById('addQueueModal').classList.remove('hidden');
            resetForm();
        }

        function closeAddModal() {
            document.getElementById('addQueueModal').classList.add('hidden');;
            document.getElementById('addQueueForm').reset();
            document.getElementById('addErrorMessage').classList.add('hidden');
            document.getElementById('addSuccessMessage').classList.add('hidden');
            resetForm();
        }

        function resetForm() {
            const staffSelect = document.getElementById('staffSelect');
            const serviceSelect = document.getElementById('serviceSelect');
            const serviceInfo = document.getElementById('serviceInfo');

            document.getElementById('specializationSelect').value = '';
            staffSelect.innerHTML = '<option value="">اختر التخصص أولاً</option>';
            staffSelect.disabled = true;
            serviceSelect.innerHTML = '<option value="">اختر الموظف أولاً</option>';
            serviceSelect.disabled = true;
            serviceInfo.classList.add('hidden');
        }

        // When specialization changes → Load staff from API
        document.getElementById('specializationSelect').addEventListener('change', async function() {
            const specialization = this.value;
            const staffSelect = document.getElementById('staffSelect');
            const serviceSelect = document.getElementById('serviceSelect');
            const serviceInfo = document.getElementById('serviceInfo');

            // Reset dependent fields
            serviceSelect.innerHTML = '<option value="">اختر الموظف أولاً</option>';
            serviceSelect.disabled = true;
            serviceInfo.classList.add('hidden');

            if (!specialization) {
                staffSelect.innerHTML = '<option value="">اختر التخصص أولاً</option>';
                staffSelect.disabled = true;
                return;
            }

            staffSelect.innerHTML = '<option value="">جاري التحميل...</option>';
            staffSelect.disabled = true;

            try {
                const response = await fetch(`/admin/api/staff/by-specialization/${encodeURIComponent(specialization)}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    staffSelect.innerHTML = '<option value="">اختر الموظف</option>';
                    result.data.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = staff.id;
                        option.textContent = staff.name;
                        staffSelect.appendChild(option);
                    });
                    staffSelect.disabled = false;
                } else {
                    staffSelect.innerHTML = '<option value="">لا يوجد موظفين في هذا التخصص</option>';
                    staffSelect.disabled = true;
                }
            } catch (error) {
                console.error('Error loading staff:', error);
                staffSelect.innerHTML = '<option value="">خطأ في التحميل</option>';
                staffSelect.disabled = true;
            }
        });

        // When staff changes → Load their services from API
        document.getElementById('staffSelect').addEventListener('change', async function() {
            const staffId = this.value;
            const serviceSelect = document.getElementById('serviceSelect');
            const serviceInfo = document.getElementById('serviceInfo');

            if (!staffId) {
                serviceSelect.innerHTML = '<option value="">اختر الموظف أولاً</option>';
                serviceSelect.disabled = true;
                serviceInfo.classList.add('hidden');
                return;
            }

            serviceSelect.innerHTML = '<option value="">جاري التحميل...</option>';
            serviceSelect.disabled = true;

            try {
                const response = await fetch(`/admin/api/staff/${staffId}/services`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    serviceSelect.innerHTML = '<option value="">اختر الخدمة</option>';
                    result.data.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = `${service.name_ar || service.name} (${service.duration} دقيقة)`;
                        option.dataset.duration = service.duration;
                        serviceSelect.appendChild(option);
                    });
                    serviceSelect.disabled = false;
                } else {
                    serviceSelect.innerHTML = '<option value="">لا توجد خدمات لهذا الموظف</option>';
                }
            } catch (error) {
                serviceSelect.innerHTML = '<option value="">خطأ في التحميل</option>';
            }
        });

        // When service changes, show duration
        document.getElementById('serviceSelect').addEventListener('change', function() {
            const serviceInfo = document.getElementById('serviceInfo');
            const selectedOption = this.options[this.selectedIndex];

            if (this.value && selectedOption.dataset.duration) {
                document.getElementById('serviceDuration').textContent = selectedOption.dataset.duration;
                serviceInfo.classList.remove('hidden');
            } else {
                serviceInfo.classList.add('hidden');
            }
        });

        // Add to queue
        document.getElementById('addQueueForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            const errorDiv = document.getElementById('addErrorMessage');
            const successDiv = document.getElementById('addSuccessMessage');

            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');

            try {
                const response = await fetch('/admin/api/queue/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    successDiv.textContent = '✓ تم إضافة العميل للقائمة بنجاح!';
                    successDiv.classList.remove('hidden');

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    errorDiv.textContent = '✕ ' + (result.message || 'حدث خطأ أثناء الحفظ');
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                errorDiv.textContent = '✕ حدث خطأ! حاول مرة أخرى';
                errorDiv.classList.remove('hidden');
            }
        });

        // Call next in queue
        async function callNext() {
            try {
                const response = await fetch('/admin/api/queue/call-next', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert('✓ ' + result.message);
                    window.location.reload();
                } else {
                    alert('✕ ' + (result.message || 'حدث خطأ'));
                }
            } catch (error) {
                alert('✕ حدث خطأ! حاول مرة أخرى');
            }
        }

        // Serve queue item
        async function serveQueue(id) {
            try {
                const response = await fetch(`/admin/api/queue/${id}/serve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    window.location.reload();
                } else {
                    alert('✕ ' + (result.message || 'حدث خطأ'));
                }
            } catch (error) {
                alert('✕ حدث خطأ! حاول مرة أخرى');
            }
        }

        // Complete queue item
        async function completeQueue(id) {
            try {
                const response = await fetch(`/admin/api/queue/${id}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert('✓ تم إنهاء الخدمة بنجاح!');
                    window.location.reload();
                } else {
                    alert('✕ ' + (result.message || 'حدث خطأ'));
                }
            } catch (error) {
                alert('✕ حدث خطأ! حاول مرة أخرى');
            }
        }

        // Return to waiting
        async function returnToWaiting(id) {
            if (!confirm('هل تريد إرجاع هذا العميل لقائمة الانتظار؟')) {
                return;
            }

            try {
                const response = await fetch(`/admin/api/queue/${id}/return-waiting`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert('✓ تم إرجاع العميل لقائمة الانتظار');
                    window.location.reload();
                } else {
                    alert('✕ ' + (result.message || 'حدث خطأ'));
                }
            } catch (error) {
                alert('✕ حدث خطأ! حاول مرة أخرى');
            }
        }

        // Set priority
        async function setPriority(id, priority) {
            try {
                const response = await fetch(`/admin/api/queue/${id}/priority`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ priority: priority })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    window.location.reload();
                } else {
                    alert('✕ ' + (result.message || 'حدث خطأ'));
                }
            } catch (error) {
                alert('✕ حدث خطأ! حاول مرة أخرى');
            }
        }

        // Remove from queue
        async function removeQueue(id) {
            if (!confirm('هل أنت متأكد من حذف هذا العميل من القائمة؟')) {
                return;
            }

            try {
                const response = await fetch(`/admin/api/queue/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    window.location.reload();
                } else {
                    alert('✕ ' + (result.message || 'حدث خطأ'));
                }
            } catch (error) {
                alert('✕ حدث خطأ! حاول مرة أخرى');
            }
        }

        // Close modal on outside click
        document.getElementById('addQueueModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });

        document.getElementById('editQueueModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Edit Queue Functions
        function closeEditModal() {
            document.getElementById('editQueueModal').classList.add('hidden');
            document.getElementById('editQueueForm').reset();
            document.getElementById('editErrorMessage').classList.add('hidden');
            document.getElementById('editSuccessMessage').classList.add('hidden');
        }

        async function editQueue(id) {
            try {
                const response = await fetch(`/admin/api/queue/${id}`);
                const result = await response.json();

                if (result.success) {
                    const queue = result.data;
                    document.getElementById('edit_queue_id').value = queue.id;
                    document.getElementById('edit_customer_name').value = queue.appointment?.customer?.name || '';
                    document.getElementById('edit_customer_phone').value = queue.appointment?.customer?.phone || '';
                    document.getElementById('edit_customer_email').value = queue.appointment?.customer?.email || '';
                    document.getElementById('edit_notes').value = queue.notes || '';
                    document.getElementById('edit_is_vip').checked = queue.is_vip;
                    document.getElementById('editQueueModal').classList.remove('hidden');
                } else {
                    alert('خطأ في تحميل البيانات');
                }
            } catch (error) {
                alert('حدث خطأ في الاتصال');
            }
        }

        document.getElementById('editQueueForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const queueId = document.getElementById('edit_queue_id').value;
            const errorDiv = document.getElementById('editErrorMessage');
            const successDiv = document.getElementById('editSuccessMessage');

            errorDiv.classList.add('hidden');
            successDiv.classList.add('hidden');

            const data = {
                customer_name: document.getElementById('edit_customer_name').value,
                customer_phone: document.getElementById('edit_customer_phone').value,
                customer_email: document.getElementById('edit_customer_email').value,
                notes: document.getElementById('edit_notes').value,
                is_vip: document.getElementById('edit_is_vip').checked ? 1 : 0
            };

            try {
                const response = await fetch(`/admin/api/queue/${queueId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    successDiv.textContent = '✓ تم حفظ التعديلات بنجاح';
                    successDiv.classList.remove('hidden');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    errorDiv.textContent = '✕ ' + (result.message || 'حدث خطأ');
                    errorDiv.classList.remove('hidden');
                }
            } catch (error) {
                errorDiv.textContent = '✕ حدث خطأ في الاتصال';
                errorDiv.classList.remove('hidden');
            }
        });

        // View Queue Functions
        function closeViewModal() {
            document.getElementById('viewQueueModal').classList.add('hidden');
            currentViewQueueId = null;
        }

        document.getElementById('viewQueueModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeViewModal();
            }
        });

        async function viewQueue(id) {
            currentViewQueueId = id;
            try {
                const response = await fetch(`/admin/api/queue/${id}`);
                const result = await response.json();

                if (result.success) {
                    const queue = result.data;

                    // Queue number
                    document.getElementById('view_queue_number').textContent = '#' + queue.queue_number;

                    // Status badge
                    let statusHtml = '';
                    if (queue.status === 'waiting') {
                        statusHtml = '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">في الانتظار</span>';
                    } else if (queue.status === 'serving') {
                        statusHtml = '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">يتم الخدمة</span>';
                    } else if (queue.status === 'completed') {
                        statusHtml = '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">مكتمل</span>';
                    } else {
                        statusHtml = '<span class="px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800">ملغي</span>';
                    }
                    document.getElementById('view_status_badge').innerHTML = statusHtml;

                    // Customer info
                    document.getElementById('view_customer_name').textContent = queue.appointment?.customer?.name || '-';
                    document.getElementById('view_customer_phone').textContent = queue.appointment?.customer?.phone || '-';
                    document.getElementById('view_customer_email').textContent = queue.appointment?.customer?.email || '-';

                    // VIP status
                    if (queue.is_vip) {
                        document.getElementById('view_vip_status').innerHTML = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">⭐ VIP</span>';
                    } else {
                        document.getElementById('view_vip_status').innerHTML = '<span class="text-gray-500">عادي</span>';
                    }

                    // Staff info
                    document.getElementById('view_staff_name').textContent = queue.appointment?.staff?.name || '-';
                    document.getElementById('view_staff_specialization').textContent = queue.appointment?.staff?.specialization_ar || queue.appointment?.staff?.specialization || '';

                    // Service info
                    if (queue.appointment?.service) {
                        document.getElementById('view_service_name').textContent = queue.appointment.service.name_ar || queue.appointment.service.name;
                        document.getElementById('view_service_duration').textContent = queue.appointment.service.duration + ' دقيقة';
                    } else {
                        document.getElementById('view_service_name').textContent = queue.appointment?.service_type || '-';
                        document.getElementById('view_service_duration').textContent = '';
                    }

                    // Time info
                    document.getElementById('view_created_at').textContent = new Date(queue.created_at).toLocaleString('ar-EG');
                    document.getElementById('view_updated_at').textContent = new Date(queue.updated_at).toLocaleString('ar-EG');

                    // Notes
                    const notesSection = document.getElementById('view_notes_section');
                    const notesElement = document.getElementById('view_notes');
                    if (queue.notes && queue.notes.trim() !== '') {
                        notesElement.textContent = queue.notes;
                        notesSection.classList.remove('hidden');
                    } else {
                        notesElement.textContent = 'لا توجد ملاحظات';
                        notesSection.classList.remove('hidden');
                    }

                    document.getElementById('viewQueueModal').classList.remove('hidden');
                } else {
                    alert('خطأ في تحميل البيانات');
                }
            } catch (error) {
                alert('حدث خطأ في الاتصال');
            }
        }

        function editQueueFromView() {
            closeViewModal();
            if (currentViewQueueId) {
                editQueue(currentViewQueueId);
            }
        }

        // Print Queue Function
        function printQueue() {
            const printWindow = window.open('', '_blank');
            const queuesData = @json($queues);

            let tableRows = '';
            queuesData.forEach((queue, index) => {
                const customerName = queue.appointment?.customer?.name || '-';
                const customerPhone = queue.appointment?.customer?.phone || '-';
                const customerEmail = queue.appointment?.customer?.email || '-';
                const staffName = queue.appointment?.staff?.name || '-';
                const serviceName = queue.appointment?.service?.name || '-';
                const priority = queue.is_vip ? 'VIP' : 'عادي';
                const notes = queue.notes || '-';
                const status = queue.status === 'waiting' ? 'في الانتظار' :
                              queue.status === 'serving' ? 'يتم الخدمة' :
                              queue.status === 'completed' ? 'مكتمل' : 'ملغي';

                tableRows += `
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; text-align: center;">${queue.queue_number}</td>
                        <td style="padding: 12px;">${customerName}</td>
                        <td style="padding: 12px;">${customerPhone}</td>
                        <td style="padding: 12px;">${customerEmail}</td>
                        <td style="padding: 12px;">${staffName}</td>
                        <td style="padding: 12px;">${serviceName}</td>
                        <td style="padding: 12px; text-align: center;">${priority}</td>
                        <td style="padding: 12px;">${notes}</td>
                        <td style="padding: 12px; text-align: center;">${status}</td>
                    </tr>
                `;
            });

            const printContent = `
                <!DOCTYPE html>
                <html lang="ar" dir="rtl">
                <head>
                    <meta charset="UTF-8">
                    <title>قائمة الانتظار - طباعة</title>
                    <style>
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            direction: rtl;
                            margin: 20px;
                        }
                        h1 {
                            text-align: center;
                            color: #1f2937;
                            margin-bottom: 10px;
                        }
                        .date {
                            text-align: center;
                            color: #6b7280;
                            margin-bottom: 30px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        th {
                            background-color: #f3f4f6;
                            padding: 12px;
                            text-align: right;
                            font-weight: 600;
                            color: #374151;
                            border: 1px solid #e5e7eb;
                        }
                        td {
                            border: 1px solid #e5e7eb;
                        }
                        @media print {
                            body { margin: 0; }
                            @page { margin: 1cm; }
                        }
                    </style>
                </head>
                <body>
                    <h1>قائمة الانتظار</h1>
                    <div class="date">التاريخ: ${new Date().toLocaleDateString('ar-EG')}</div>
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: center;">رقم الدور</th>
                                <th>اسم العميل</th>
                                <th>رقم الهاتف</th>
                                <th>البريد الإلكتروني</th>
                                <th>الموظف</th>
                                <th>الخدمة</th>
                                <th style="text-align: center;">الأولوية</th>
                                <th>ملاحظات</th>
                                <th style="text-align: center;">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </body>
                </html>
            `;

            printWindow.document.write(printContent);
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
            }, 250);
        }

        // Export to Excel Function (XLSX format)
        function exportToExcel() {
            window.location.href = '{{ route('admin.queue.export.excel') }}';
        }
    </script>
    <script src="/js/dark-mode.js"></script>
</body>
</html>
