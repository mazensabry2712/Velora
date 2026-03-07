@extends('super-admin.layout')

@section('title', 'إدارة الدول')
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">إدارة الدول</span>@endsection

@section('content')
<div x-data="countriesManager()" x-init="init()">
    <!-- Header -->
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-start">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                إدارة الدول
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">تحديد اللغة والعملة الافتراضية لكل دولة</p>
        </div>
        <a href="{{ route('super-admin.countries.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900 transition-all duration-200 hover:-translate-y-0.5 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إضافة دولة
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl text-sm">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Skeleton -->
    <div x-show="loading" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700">
            <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded-lg w-36 skeleton"></div>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <template x-for="i in 8" :key="i">
                <div class="px-6 py-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 skeleton flex-shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/4 skeleton"></div>
                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/6 skeleton"></div>
                    </div>
                    <div class="h-6 w-12 bg-slate-200 dark:bg-slate-700 rounded-full skeleton"></div>
                    <div class="h-6 w-14 bg-slate-200 dark:bg-slate-700 rounded skeleton"></div>
                    <div class="h-6 w-10 bg-slate-200 dark:bg-slate-700 rounded-full skeleton"></div>
                    <div class="flex gap-2">
                        <div class="h-7 w-7 bg-slate-200 dark:bg-slate-700 rounded-lg skeleton"></div>
                        <div class="h-7 w-7 bg-slate-200 dark:bg-slate-700 rounded-lg skeleton"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Main Table Card -->
    <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

        <!-- Card Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="font-bold text-slate-900 dark:text-white">قائمة الدول</h2>
                <span class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2.5 py-1 rounded-full font-medium"
                      x-text="(filtered.length !== countries.length ? filtered.length + ' من ' : '') + countries.length + ' دولة'"></span>
            </div>
            <!-- Search + filter -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Active filter toggle -->
                <button @click="showActiveOnly = !showActiveOnly; applyFilter()"
                        :class="showActiveOnly ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all">
                    نشط فقط
                </button>
                <!-- Search -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" @input="applyFilter()"
                           placeholder="بحث بالاسم، الرمز، اللغة..."
                           class="w-52 pr-9 pl-4 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <svg class="w-4 h-4 text-slate-400 absolute right-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">الدولة</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">اللغة</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">العملة</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">الضريبة</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">الحالة</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700/50">
                    <template x-for="country in paged" :key="country.id">
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30 transition-colors duration-150">

                            <!-- Country -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-xl flex-shrink-0 shadow-sm"
                                         x-text="countryFlag(country.country_code)"></div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="country.country_name"></p>
                                        <code class="text-xs text-slate-400 dark:text-slate-500 font-mono" x-text="country.country_code"></code>
                                    </div>
                                </div>
                            </td>

                            <!-- Language -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                      :class="langClass(country.default_language)"
                                      x-text="country.default_language.toUpperCase()"></span>
                            </td>

                            <!-- Currency -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200" x-text="country.default_currency"></span>
                            </td>

                            <!-- Tax -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-if="country.tax_percentage > 0">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 ring-1 ring-purple-200 dark:ring-purple-800"
                                          x-text="parseFloat(country.tax_percentage).toFixed(0) + '% ' + (country.tax_name || '')"></span>
                                </template>
                                <template x-if="!country.tax_percentage || country.tax_percentage == 0">
                                    <span class="text-xs text-slate-400 dark:text-slate-500">—</span>
                                </template>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                      :class="country.is_active
                                          ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-800'
                                          : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 ring-1 ring-slate-200 dark:ring-slate-600'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                          :class="country.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    <span x-text="country.is_active ? 'نشط' : 'معطّل'"></span>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a :href="country.edit_url"
                                       class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition" title="تعديل">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button @click="confirmDelete(country)"
                                            class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="حذف">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty state -->
                    <tr x-show="filtered.length === 0 && !loading">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-slate-500 dark:text-slate-400 font-medium" x-text="searchQuery ? 'لا توجد نتائج للبحث' : 'لا توجد دول مضافة بعد'"></p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1" x-show="!searchQuery">اضغط على "إضافة دولة" لإضافة أول دولة</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="filtered.length > 0" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between">
            <!-- Per-page + info -->
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <span>عرض</span>
                <select x-model.number="perPage" @change="currentPage = 1; paginate()"
                        class="border border-slate-200 dark:border-slate-600 rounded-lg px-2 py-1 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>في الصفحة</span>
                <span class="hidden sm:inline text-slate-300 dark:text-slate-600 mx-1">|</span>
                <span class="hidden sm:inline" x-text="`${((currentPage-1)*perPage)+1}–${Math.min(currentPage*perPage, filtered.length)} من ${filtered.length}`"></span>
            </div>
            <!-- Page buttons -->
            <div class="flex items-center gap-1">
                <button @click="goToPage(1)" :disabled="currentPage === 1"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-for="page in visiblePages" :key="page">
                    <button @click="goToPage(page)"
                            :class="page === currentPage
                                ? 'bg-indigo-600 text-white'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="w-8 h-8 rounded-lg text-sm font-medium transition"
                            x-text="page"></button>
                </template>
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete confirm modal -->
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="showDeleteModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 w-full max-w-sm border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white">تأكيد الحذف</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">هذا الإجراء لا يمكن التراجع عنه</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
                هل أنت متأكد من حذف دولة <strong class="text-slate-900 dark:text-white" x-text="deleteTarget?.country_name"></strong>؟
                سيتم حذف بيانات الضريبة المرتبطة بها أيضاً.
            </p>
            <div class="flex gap-3">
                <form :action="deleteTarget?.delete_url" method="POST" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2.5 rounded-xl transition-colors text-sm">
                        حذف
                    </button>
                </form>
                <button @click="showDeleteModal = false"
                        class="flex-1 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium px-4 py-2.5 rounded-xl transition-colors text-sm">
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function countriesManager() {
    const LANG_COLORS = {
        ar: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 ring-1 ring-amber-200 dark:ring-amber-800',
        en: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-800',
        fr: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-200 dark:ring-indigo-800',
        de: 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-slate-600',
        es: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800',
        it: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 ring-1 ring-green-200 dark:ring-green-800',
        pt: 'bg-lime-100 dark:bg-lime-900/30 text-lime-700 dark:text-lime-300 ring-1 ring-lime-200 dark:ring-lime-800',
        ru: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 ring-1 ring-cyan-200 dark:ring-cyan-800',
        zh: 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 ring-1 ring-rose-200 dark:ring-rose-800',
        ja: 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 ring-1 ring-pink-200 dark:ring-pink-800',
        tr: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 ring-1 ring-orange-200 dark:ring-orange-800',
        hi: 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 ring-1 ring-violet-200 dark:ring-violet-800',
        ko: 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 ring-1 ring-teal-200 dark:ring-teal-800',
        nl: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 ring-1 ring-purple-200 dark:ring-purple-800',
        id: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800',
    };

    return {
        loading: true,
        countries: [],
        filtered: [],
        paged: [],
        searchQuery: '',
        showActiveOnly: false,
        perPage: 5,
        currentPage: 1,
        showDeleteModal: false,
        deleteTarget: null,

        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },
        get visiblePages() {
            const pages = [];
            const start = Math.max(1, this.currentPage - 2);
            const end   = Math.min(this.totalPages, this.currentPage + 2);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        init() {
            this.countries = {!! $countriesJson !!};
            this.filtered  = this.countries;
            this.paginate();
            this.loading = false;
        },

        applyFilter() {
            const q = this.searchQuery.toLowerCase().trim();
            this.filtered = this.countries.filter(c => {
                const matchSearch = !q ||
                    c.country_name.toLowerCase().includes(q) ||
                    c.country_code.toLowerCase().includes(q) ||
                    c.default_language.toLowerCase().includes(q) ||
                    c.default_currency.toLowerCase().includes(q);
                const matchActive = !this.showActiveOnly || c.is_active;
                return matchSearch && matchActive;
            });
            this.currentPage = 1;
            this.paginate();
        },

        paginate() {
            const start = (this.currentPage - 1) * this.perPage;
            this.paged  = this.filtered.slice(start, start + this.perPage);
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            this.paginate();
        },

        countryFlag(code) {
            if (!code || code.length !== 2) return '🏳️';
            return String.fromCodePoint(...[...code.toUpperCase()].map(c => 0x1F1E6 - 65 + c.charCodeAt(0)));
        },

        langClass(lang) {
            return LANG_COLORS[lang] || 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 ring-1 ring-slate-200';
        },

        confirmDelete(country) {
            this.deleteTarget    = country;
            this.showDeleteModal = true;
        },
    };
}
</script>
@endsection
