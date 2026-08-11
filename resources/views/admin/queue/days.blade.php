@extends('layouts.admin')

@section('title', __('Queue Days'))
@section('subtitle', __('Review and manage queue activity by day'))

@section('content')


        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">إجمالي الأيام</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $days->count() }}</p>
                    </div>
                    <div class="bg-indigo-100 dark:bg-indigo-900 p-3 rounded-full">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">إجمالي في الانتظار</p>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $overallStats['waiting'] ?? 0 }}</p>
                    </div>
                    <div class="bg-amber-100 dark:bg-amber-900 p-3 rounded-full">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">إجمالي يتم خدمتهم</p>
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $overallStats['serving'] ?? 0 }}</p>
                    </div>
                    <div class="bg-indigo-100 dark:bg-indigo-900 p-3 rounded-full">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">إجمالي المكتمل</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $overallStats['completed'] ?? 0 }}</p>
                    </div>
                    <div class="bg-emerald-100 dark:bg-emerald-900 p-3 rounded-full">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Days Table -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
            <div class="mb-6 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">جدول الأيام</h3>
                <a href="{{ route('admin.queue.day', ['date' => now()->toDateString()]) }}" class="bg-indigo-600 dark:bg-indigo-700 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    يوم جديد
                </a>
            </div>

            @if($days->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">التاريخ</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">اليوم</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">إجمالي الأدوار</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">في الانتظار</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">يتم الخدمة</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">مكتمل</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">VIP</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                            @foreach($days as $index => $day)
                                @php
                                    $isToday = $day->date === now()->toDateString();
                                    $dayName = \Carbon\Carbon::parse($day->date)->locale('ar')->translatedFormat('l');
                                    $formattedDate = \Carbon\Carbon::parse($day->date)->format('Y-m-d');
                                    $lastActivityTime = $day->last_activity ? \Carbon\Carbon::parse($day->last_activity)->format('h:i A') : '-';
                                @endphp
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer transition-colors {{ $isToday ? 'bg-indigo-50 dark:bg-indigo-900 border-r-4 border-indigo-500 dark:border-indigo-400' : '' }}" onclick="window.location='{{ route('admin.queue.day', ['date' => $day->date]) }}'">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $formattedDate }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $lastActivityTime }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $dayName }}</span>
                                        @if($isToday)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 mr-2">اليوم</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                                            {{ $day->total }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $day->waiting > 0 ? 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }}">
                                            {{ $day->waiting }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $day->serving > 0 ? 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }}">
                                            {{ $day->serving }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $day->completed > 0 ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }}">
                                            {{ $day->completed }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $day->vip > 0 ? 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }}">
                                            {{ $day->vip }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('admin.queue.day', ['date' => $day->date]) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 p-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900 transition-colors" title="عرض الأدوار">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            @if($day->waiting > 0)
                                                <button onclick="moveToNextDay('{{ $day->date }}', 'waiting')" class="text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300 p-1 rounded hover:bg-amber-50 dark:hover:bg-amber-900 transition-colors" title="نقل المنتظرين لليوم التالي">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                                </button>
                                            @endif
                                            @if($day->serving > 0)
                                                <button onclick="moveToNextDay('{{ $day->date }}', 'serving')" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300 p-1 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900 transition-colors" title="نقل المتخدمين لليوم التالي">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">لا توجد أيام بعد</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">اضغط على "يوم جديد" لبدء يوم جديد في قائمة الانتظار</p>
                    <div class="mt-6">
                        <a href="{{ route('admin.queue.day', ['date' => now()->toDateString()]) }}" class="bg-indigo-600 dark:bg-indigo-700 text-white px-5 py-2.5 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            يوم جديد
                        </a>
                    </div>
                </div>
            @endif
        </div>

@endsection

@push('scripts')
    <script>
        function moveToNextDay(date, status) {
            const statusText = status === 'waiting' ? 'المنتظرين' : 'المتخدمين';

            if (!confirm(`هل تريد نقل ${statusText} من يوم ${date} إلى اليوم التالي؟`)) {
                return;
            }

            fetch(`{{ url('admin/api/queue/move-to-next-day') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    date: date,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        }
    </script>
@endpush
