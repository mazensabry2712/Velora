@extends('super-admin.layout')

@section('title', __('super-admin.tenants_title'))
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">{{ __('super-admin.tenants_title') }}</span>@endsection

@section('content')
<div x-data="tenantsManager()" x-init="loadTenants()">

    <!-- Header -->
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-center">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                {{ __('super-admin.tenants_title') }}
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">{{ __('super-admin.tenants_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button x-show="!loading && tenants.length > 0" @click="showDeleteAllModal = true"
                    :disabled="submitting"
                    class="flex items-center gap-2 text-red-600 dark:text-red-400 hover:text-white border border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-900/20 hover:bg-red-600 dark:hover:bg-red-700 font-semibold px-4 py-2.5 rounded-xl transition-all duration-200 disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span x-text="__tTenants.locale === 'ar' ? 'حذف الجميع' : 'Delete All'"></span>
            </button>
            <button @click="openTrashModal()"
                    class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 border border-slate-200 dark:border-white/[0.1] hover:border-red-300 dark:hover:border-red-800 bg-white dark:bg-white/[0.03] hover:bg-red-50 dark:hover:bg-red-900/20 font-semibold px-4 py-2.5 rounded-xl transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span x-text="__tTenants.locale === 'ar' ? 'سلة المحذوفات' : 'Trash'"></span>
                <span x-show="trashedCount > 0"
                      x-text="trashedCount"
                      class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold bg-red-500 text-white rounded-full"></span>
            </button>
            <button @click="openAddModal()"
                    class="flex items-center gap-2 bg-gradient-to-r from-[#5b4ff7] to-[#6C63FF] hover:from-[#4d3de3] hover:to-[#5b4ff7] text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-[#6C63FF]/30 dark:shadow-[#6C63FF]/20 transition-all duration-200 hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('super-admin.tenant_add') }}
            </button>
        </div>
    </div>

    <!-- Skeleton Loading State -->
    <div x-show="loading" class="space-y-4">
        <div class="bg-white dark:bg-[#0d0c1a] rounded-2xl shadow-sm border border-slate-200 dark:border-white/[0.07] overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-white/[0.07]">
                <div class="h-5 bg-slate-200 dark:bg-white/[0.07] rounded-lg w-32 skeleton"></div>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-white/[0.05]">
                <template x-for="i in 6" :key="i">
                    <div class="px-6 py-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-white/[0.07] skeleton flex-shrink-0"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-slate-200 dark:bg-white/[0.07] rounded w-1/3 skeleton"></div>
                            <div class="h-3 bg-slate-200 dark:bg-white/[0.05] rounded w-1/2 skeleton"></div>
                        </div>
                        <div class="h-6 w-16 bg-slate-200 dark:bg-white/[0.07] rounded-full skeleton"></div>
                        <div class="flex gap-2">
                            <div class="h-8 w-8 bg-slate-200 dark:bg-white/[0.07] rounded-lg skeleton"></div>
                            <div class="h-8 w-8 bg-slate-200 dark:bg-white/[0.07] rounded-lg skeleton"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Tenants List -->
    <div x-show="!loading" x-cloak class="bg-white dark:bg-[#0d0c1a] rounded-2xl shadow-sm border border-slate-200 dark:border-white/[0.07]">
        <!-- Table header with search -->
        <div class="p-5 border-b border-slate-200 dark:border-white/[0.07] flex flex-wrap gap-3 items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenants_list') }}</h2>
                <span class="text-xs bg-slate-100 dark:bg-white/[0.07] text-slate-600 dark:text-slate-300 px-2.5 py-1 rounded-full font-medium"
                      x-text="(filteredTenants.length !== tenants.length ? filteredTenants.length + ' {{ __('super-admin.tenant_of') }} ' : '') + tenants.length + ' {{ __('super-admin.tenant_company_label') }}'" ></span>
            </div>
            <div class="relative">
                <input type="text" x-model="searchQuery" @input="filterTenants()"
                       placeholder="{{ __('super-admin.tenant_search') }}"
                       class="w-56 pr-9 pl-4 py-2 text-sm border border-slate-200 dark:border-white/[0.1] rounded-xl bg-slate-50 dark:bg-white/[0.05] text-slate-900 dark:text-white focus:ring-2 focus:ring-[#6C63FF] focus:border-transparent transition">
                <svg class="w-4 h-4 text-slate-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-[#0c0b18]/70">
                    <tr>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_company') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_domain') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_email') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_status') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_created_at') }}</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/[0.05]">
                    <template x-for="tenant in pagedTenants" :key="tenant.id">
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-white/[0.03] transition-colors duration-150 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm flex-shrink-0 shadow-sm"
                                         :style="`background: hsl(${(tenant.name.charCodeAt(0) * 37) % 360}, 65%, 50%)`"
                                         x-text="tenant.name.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="tenant.name"></p>
                                        <p class="text-xs text-slate-400" x-text="'#' + tenant.id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="text-xs bg-slate-100 dark:bg-white/[0.07] text-slate-700 dark:text-slate-300 px-2 py-1 rounded-md font-mono" x-text="tenant.domain"></code>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400" x-text="tenant.email || '-'"></td>
                            <td class="px-6 py-4"> {{-- Active status --}}
                                <button @click="toggleStatus(tenant.id, tenant.active)"
                                        :class="tenant.active
                                            ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 ring-1 ring-emerald-200 dark:ring-emerald-800'
                                            : 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 ring-1 ring-amber-200 dark:ring-amber-800'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full hover:opacity-80 transition">
                                    <span :class="tenant.active ? 'bg-emerald-500' : 'bg-amber-500'" class="w-1.5 h-1.5 rounded-full"></span>
                                    <span x-text="tenant.active ? '{{ __('super-admin.tenant_active') }}' : '{{ __('super-admin.tenant_inactive') }}'"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400" x-text="formatDate(tenant.created_at)"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="viewTenant(tenant)"
                                            class="p-1.5 text-slate-400 hover:text-[#6C63FF] dark:hover:text-[#8b76ff] hover:bg-[#6C63FF]/10 rounded-lg transition tooltip" data-tip="{{ __('super-admin.tenant_view_details') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button @click="openEditModal(tenant)"
                                            class="p-1.5 text-slate-400 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-900/20 rounded-lg transition tooltip" data-tip="{{ __('super-admin.tenant_edit') ?? 'تعديل' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="confirmReset(tenant.id, tenant.name)"
                                            class="p-1.5 text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition tooltip" data-tip="{{ __('super-admin.tenant_reset_password') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                    </button>
                                    <button @click="confirmDelete(tenant.id, tenant.name)"
                                            class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition tooltip" data-tip="{{ __('super-admin.tenant_delete_company') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <!-- Empty state -->
                    <tr x-show="filteredTenants.length === 0 && !loading">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                            </svg>
                            <p class="text-slate-500 dark:text-slate-400 font-medium">{{ __('super-admin.tenant_no_results') }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('super-admin.tenant_empty_hint') }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="filteredTenants.length > 0" class="px-5 py-4 border-t border-slate-200 dark:border-white/[0.07] flex flex-wrap gap-3 items-center justify-between">

            <!-- Per-page + info -->
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <span>{{ __('super-admin.pagination_show') }}</span>
                <select x-model.number="perPage" @change="currentPage = 1; paginate()"
                        class="border border-slate-200 dark:border-white/[0.1] rounded-lg px-2 py-1 text-sm bg-white dark:bg-[#0c0b18] text-slate-900 dark:text-white focus:ring-2 focus:ring-[#6C63FF] outline-none">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>{{ __('super-admin.pagination_per_page') }}</span>
                <span class="hidden sm:inline text-slate-300 dark:text-slate-600 mx-1">|</span>
                <span class="hidden sm:inline" x-text="`${((currentPage-1)*perPage)+1}–${Math.min(currentPage*perPage, filteredTenants.length)} ${__tTenants.of_word} ${filteredTenants.length}`"></span>
            </div>

            <!-- Page buttons -->
            <div class="flex items-center gap-1">
                <!-- First page -->
                <button @click="goToPage(1)" :disabled="currentPage === 1"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
                <!-- Prev -->
                <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <!-- Page numbers -->
                <template x-for="page in totalPages" :key="page">
                    <span x-show="page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1">
                        <!-- Ellipsis before -->
                        <span x-show="page === currentPage - 1 && currentPage - 2 > 1"
                              class="px-1 text-slate-400 dark:text-slate-500 text-sm select-none">…</span>
                        <button @click="goToPage(page)"
                                :class="currentPage === page
                                    ? 'bg-[#6C63FF] text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.08]'"
                                class="min-w-[32px] h-8 px-1.5 rounded-lg text-sm font-medium transition"
                                x-text="page"></button>
                        <!-- Ellipsis after -->
                        <span x-show="page === currentPage + 1 && currentPage + 2 < totalPages"
                              class="px-1 text-slate-400 dark:text-slate-500 text-sm select-none">…</span>
                    </span>
                </template>

                <!-- Next -->
                <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <!-- Last page -->
                <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Add Tenant Modal -->
    <div x-show="showAddModal" x-cloak
         @keydown.escape.window="showAddModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAddModal = false"></div>
        <div x-show="showAddModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-lg">

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-white/[0.07]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenant_add') }}</h3>
                </div>
                <button @click="showAddModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/[0.08] rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="addTenant()">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('super-admin.tenant_form_name_label') }} <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newTenant.name" required
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-white/[0.1] rounded-xl focus:ring-2 focus:ring-[#6C63FF] focus:border-transparent bg-slate-50 dark:bg-white/[0.05] text-slate-900 dark:text-white transition"
                               placeholder="{{ __('super-admin.tenant_form_name_ph') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('super-admin.tenant_domain') }} <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newTenant.domain" required
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-white/[0.1] rounded-xl focus:ring-2 focus:ring-[#6C63FF] focus:border-transparent bg-slate-50 dark:bg-white/[0.05] text-slate-900 dark:text-white transition font-mono text-sm"
                               placeholder="company.booking-saas.test">
                        <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.tenant_form_domain_help') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('super-admin.tenant_email') }} <span class="text-slate-400 font-normal">({{ __('super-admin.tenant_form_optional') }})</span></label>
                        <input type="email" x-model="newTenant.email"
                           class="w-full px-4 py-2.5 border border-slate-200 dark:border-white/[0.1] rounded-xl focus:ring-2 focus:ring-[#6C63FF] focus:border-transparent bg-slate-50 dark:bg-white/[0.05] text-slate-900 dark:text-white transition"
                               placeholder="admin@company.com">
                        <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.tenant_form_email_info') }}</p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end p-6 pt-0">
                    <button type="button" @click="showAddModal = false"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                        {{ __('super-admin.common_cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-[#5b4ff7] to-[#6C63FF] hover:from-[#4d3de3] hover:to-[#5b4ff7] rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-[#6C63FF]/30">
                        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitting ? '{{ __('super-admin.tenant_submitting') }}' : '{{ __('super-admin.tenant_add') }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Credentials Modal -->
    <div x-show="showCredentialsModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showCredentialsModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showCredentialsModal"
                 @click="showCredentialsModal = false"
                 class="fixed inset-0 transition-opacity bg-black/60 backdrop-blur-sm"></div>

            <div x-show="showCredentialsModal"
                 class="inline-block bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-xl text-right overflow-hidden shadow-2xl transform transition-all sm:max-w-lg sm:w-full">
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenant_creds_title') }}</h3>
                        <button @click="showCredentialsModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg p-4 mb-4">
                        <p class="text-sm text-emerald-800 dark:text-emerald-300 mb-2">✅ {{ __('super-admin.tenant_creds_created_ok') }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('super-admin.tenant_creds_save_hint') }}</p>
                    </div>

                    <div class="space-y-3">
                        <div class="bg-slate-100 dark:bg-white/[0.05] dark:border dark:border-white/[0.07] rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_email') }}</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white" x-text="credentials.email"></p>
                        </div>

                        <div class="bg-slate-100 dark:bg-white/[0.05] dark:border dark:border-white/[0.07] rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_creds_password') }}</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white" x-text="credentials.password"></p>
                        </div>

                        <div class="bg-slate-100 dark:bg-white/[0.05] dark:border dark:border-white/[0.07] rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_creds_login_url') }}</p>
                            <a :href="credentials.login_url"
                               target="_blank"
                               class="text-sm font-mono text-indigo-600 dark:text-indigo-400 hover:underline"
                               x-text="credentials.login_url"></a>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-[#0c0b18]/60 px-6 py-4 flex justify-end">
                    <button @click="showCredentialsModal = false"
                            class="bg-gradient-to-r from-[#5b4ff7] to-[#6C63FF] hover:from-[#4d3de3] hover:to-[#5b4ff7] text-white font-semibold px-6 py-2 rounded-lg transition shadow-lg shadow-[#6C63FF]/30">
                        {{ __('super-admin.common_done') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Tenant Modal -->
    <div x-show="showEditModal" x-cloak
         @keydown.escape.window="showEditModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showEditModal = false"></div>
        <div x-show="showEditModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-lg">

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-white/[0.07]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-sky-100 dark:bg-sky-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenant_edit') ?? 'تعديل الشركة' }}</h3>
                </div>
                <button @click="showEditModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/[0.08] rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="updateTenant()">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('super-admin.tenant_form_name_label') }} <span class="text-red-500">*</span></label>
                        <input type="text" x-model="editForm.name" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-white/[0.1] rounded-xl focus:ring-2 focus:ring-[#6C63FF] focus:border-transparent bg-slate-50 dark:bg-white/[0.05] text-slate-900 dark:text-white transition"
                               placeholder="{{ __('super-admin.tenant_form_name_ph') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('super-admin.tenant_domain') }} <span class="text-red-500">*</span></label>
                        <input type="text" x-model="editForm.domain" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-white/[0.1] rounded-xl focus:ring-2 focus:ring-[#6C63FF] focus:border-transparent bg-slate-50 dark:bg-white/[0.05] text-slate-900 dark:text-white transition font-mono text-sm"
                               placeholder="company.velora.test">
                        <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.tenant_form_domain_help') }}</p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end p-6 pt-0">
                    <button type="button" @click="showEditModal = false"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                        {{ __('super-admin.common_cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-sky-500/25">
                        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitting ? '{{ __('super-admin.tenant_submitting') }}' : '{{ __('super-admin.tenant_save') ?? 'حفظ التغييرات' }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Password Confirmation Modal -->
    <div x-show="showResetModal" x-cloak
         @keydown.escape.window="showResetModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showResetModal = false"></div>
        <div x-show="showResetModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ __('super-admin.tenant_reset_password') }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_reset_pw_confirm_msg') ?? 'سيتم إنشاء كلمة مرور جديدة لـ' }}</p>
            <p class="font-bold text-slate-900 dark:text-white mb-5" x-text="resetTargetName"></p>
            <div class="flex gap-3">
                <button @click="showResetModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="doResetPassword()"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 rounded-xl transition shadow-lg shadow-amber-500/25">
                    {{ __('super-admin.tenant_reset_password') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <!-- Delete ALL Confirmation Modal -->
    <div x-show="showDeleteAllModal" x-cloak
         @keydown.escape.window="showDeleteAllModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showDeleteAllModal = false"></div>
        <div x-show="showDeleteAllModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-red-900/40 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-2" x-text="__tTenants.locale === 'ar' ? 'حذف جميع الشركات' : 'Delete All Companies'"></h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1" x-text="__tTenants.locale === 'ar' ? 'سيتم نقل جميع الشركات إلى سلة المحذوفات:' : 'All companies will be moved to trash:'"></p>
            <p class="text-2xl font-black text-red-600 dark:text-red-400 my-3" x-text="tenants.length"></p>
            <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold mb-6" x-text="__tTenants.locale === 'ar' ? 'يمكنك الاسترداد لاحقاً من سلة المحذوفات.' : 'You can restore them later from the trash.'"></p>
            <div class="flex gap-3">
                <button @click="showDeleteAllModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="doDeleteAll()"
                        :disabled="submitting"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition shadow-lg shadow-red-500/25 disabled:opacity-50">
                    <span x-text="__tTenants.locale === 'ar' ? 'احذف الجميع' : 'Delete All'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Single Tenant Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak
         @keydown.escape.window="showDeleteModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div x-show="showDeleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ __('super-admin.tenant_delete_title') }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_delete_confirm2') }}</p>
            <p class="font-bold text-slate-900 dark:text-white mb-2" x-text="deleteTargetName"></p>
            <p class="text-xs text-red-500 mb-6">{{ __('super-admin.tenant_delete_warning') }}</p>
            <div class="flex gap-3">
                <button @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="deleteTenant(deleteTargetId)"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition shadow-lg shadow-red-500/25">
                    {{ __('super-admin.tenant_delete_permanent') }}
                </button>
            </div>
        </div>
    </div>

    <!-- View Tenant Modal -->
    <div x-show="showViewModal" x-cloak
         @keydown.escape.window="showViewModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showViewModal = false"></div>
        <div x-show="showViewModal"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

            <!-- Gradient Header -->
            <div class="relative h-28 flex items-end px-6 pb-0 overflow-hidden"
                 :style="selectedTenant ? `background: linear-gradient(135deg, hsl(${(selectedTenant.name.charCodeAt(0)*37)%360},65%,45%), hsl(${(selectedTenant.name.charCodeAt(0)*37+40)%360},65%,35%))` : 'background:#6C63FF'">
                <!-- Decorative circles -->
                <div class="absolute top-[-20px] right-[-20px] w-32 h-32 rounded-full bg-white/10"></div>
                <div class="absolute top-4 right-16 w-16 h-16 rounded-full bg-white/10"></div>
                <!-- Close button -->
                <button @click="showViewModal = false"
                        class="absolute top-3 left-3 p-1.5 rounded-lg bg-white/20 hover:bg-white/30 text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <!-- Avatar -->
                <div class="relative mb-[-28px] flex items-end gap-4">
                    <div class="w-14 h-14 rounded-2xl border-4 border-white dark:border-[#12101f] flex items-center justify-center text-white text-xl font-black shadow-lg flex-shrink-0"
                         :style="selectedTenant ? `background: hsl(${(selectedTenant.name.charCodeAt(0)*37)%360},65%,38%)` : ''">
                        <span x-text="selectedTenant ? selectedTenant.name.charAt(0).toUpperCase() : ''"></span>
                    </div>
                    <div class="pb-1">
                        <p class="text-white font-bold text-base leading-tight drop-shadow" x-text="selectedTenant?.name"></p>
                        <span :class="selectedTenant?.active
                                ? 'bg-emerald-400/90 text-white'
                                : 'bg-amber-400/90 text-white'"
                              class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-white opacity-90 inline-block"></span>
                            <span x-text="selectedTenant?.active ? '{{ __('super-admin.tenant_active') }}' : '{{ __('super-admin.tenant_inactive') }}'"></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="pt-10 px-6 pb-6" x-show="selectedTenant">

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <!-- Domain -->
                    <div class="col-span-2 bg-slate-50 dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-xl px-4 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-slate-400 uppercase font-semibold tracking-wide">{{ __('super-admin.tenant_domain') }}</p>
                            <p class="text-sm font-mono font-semibold text-slate-800 dark:text-white truncate" x-text="selectedTenant?.domain || '-'"></p>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="bg-slate-50 dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-xl px-4 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-sky-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-slate-400 uppercase font-semibold tracking-wide">{{ __('super-admin.tenant_email') }}</p>
                            <p class="text-sm text-slate-800 dark:text-white truncate" x-text="selectedTenant?.email || '-'"></p>
                        </div>
                    </div>
                    <!-- Created At -->
                    <div class="bg-slate-50 dark:bg-white/[0.04] dark:border dark:border-white/[0.06] rounded-xl px-4 py-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] text-slate-400 uppercase font-semibold tracking-wide">{{ __('super-admin.tenant_created_at') }}</p>
                            <p class="text-sm text-slate-800 dark:text-white" x-text="selectedTenant ? formatDate(selectedTenant.created_at) : '-'"></p>
                        </div>
                    </div>
                </div>

                <!-- Stats Panel -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <!-- Users -->
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 dark:border dark:border-indigo-700/30 rounded-xl p-3 text-center">
                        <div x-show="tenantStatsLoading" class="h-7 flex items-center justify-center">
                            <svg class="w-4 h-4 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <p x-show="!tenantStatsLoading" class="text-xl font-black text-indigo-700 dark:text-indigo-300" x-text="tenantStats?.total_users ?? '—'"></p>
                        <p class="text-[10px] text-indigo-500 dark:text-indigo-400 font-semibold uppercase tracking-wide mt-0.5" x-text="__tTenants.locale === 'ar' ? 'مستخدمون' : 'Users'"></p>
                    </div>
                    <!-- Appointments -->
                    <div class="bg-violet-50 dark:bg-violet-900/20 dark:border dark:border-violet-700/30 rounded-xl p-3 text-center">
                        <div x-show="tenantStatsLoading" class="h-7 flex items-center justify-center">
                            <svg class="w-4 h-4 animate-spin text-violet-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <p x-show="!tenantStatsLoading" class="text-xl font-black text-violet-700 dark:text-violet-300" x-text="tenantStats?.total_appointments ?? '—'"></p>
                        <p class="text-[10px] text-violet-500 dark:text-violet-400 font-semibold uppercase tracking-wide mt-0.5" x-text="__tTenants.locale === 'ar' ? 'مواعيد' : 'Appts'"></p>
                    </div>
                    <!-- Invoices -->
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 dark:border dark:border-emerald-700/30 rounded-xl p-3 text-center">
                        <div x-show="tenantStatsLoading" class="h-7 flex items-center justify-center">
                            <svg class="w-4 h-4 animate-spin text-emerald-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        <p x-show="!tenantStatsLoading" class="text-xl font-black text-emerald-700 dark:text-emerald-300" x-text="tenantStats?.total_invoices ?? '—'"></p>
                        <p class="text-[10px] text-emerald-500 dark:text-emerald-400 font-semibold uppercase tracking-wide mt-0.5" x-text="__tTenants.locale === 'ar' ? 'فواتير' : 'Invoices'"></p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2.5 pt-1">
                    <button @click="openEditModal(selectedTenant); showViewModal = false"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 dark:bg-sky-900/20 dark:text-sky-400 dark:border-sky-800 dark:hover:bg-sky-900/40 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('super-admin.tenant_edit') }}
                    </button>
                    <button @click="toggleStatus(selectedTenant.id, selectedTenant.active); showViewModal = false"
                            :class="selectedTenant?.active
                                ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800 dark:hover:bg-amber-900/40'
                                : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800 dark:hover:bg-emerald-900/40'"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                        </svg>
                        <span x-text="selectedTenant?.active ? '{{ __('super-admin.tenant_deactivate') }}' : '{{ __('super-admin.tenant_activate') }}'"></span>
                    </button>
                    <button @click="confirmDelete(selectedTenant.id, selectedTenant.name); showViewModal = false"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-semibold bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 dark:hover:bg-red-900/40 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ __('super-admin.tenant_delete_company') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Trash Modal -->
    <div x-show="showTrashModal" x-cloak
         @keydown.escape.window="showTrashModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showTrashModal = false"></div>
        <div x-show="showTrashModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-white/[0.07] flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white" x-text="__tTenants.locale === 'ar' ? 'سلة المحذوفات' : 'Trash'"></h3>
                        <p class="text-xs text-slate-400" x-text="__tTenants.locale === 'ar' ? 'الشركات المحذوفة مؤقتاً' : 'Soft-deleted companies'"></p>
                    </div>
                </div>
                <button @click="showTrashModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-white/[0.08] rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Footer: Restore All + Delete All buttons -->
            <div x-show="!trashLoading && trashedTenants.length > 0"
                 class="flex items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-white/[0.07] flex-shrink-0 gap-3">
                <p class="text-xs text-slate-400 flex-shrink-0" x-text="(__tTenants.locale === 'ar' ? trashedTenants.length + ' شركة في السلة' : trashedTenants.length + ' companies in trash')"></p>
                <div class="flex items-center gap-2">
                    <button @click="showRestoreAllModal = true"
                            :disabled="submitting"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 rounded-xl transition shadow-md shadow-emerald-500/20 disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <span x-text="__tTenants.locale === 'ar' ? 'استعادة الكل' : 'Restore All'"></span>
                    </button>
                    <button @click="showForceDeleteAllModal = true"
                            :disabled="submitting"
                            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition shadow-md shadow-red-500/20 disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-text="__tTenants.locale === 'ar' ? 'حذف الجميع نهائياً' : 'Delete All Forever'"></span>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="overflow-y-auto flex-1 p-6">

                <!-- Loading -->
                <div x-show="trashLoading" class="flex items-center justify-center py-12">
                    <svg class="w-8 h-8 animate-spin text-red-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>

                <!-- Empty State -->
                <div x-show="!trashLoading && trashedTenants.length === 0" class="text-center py-12">
                    <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <p class="text-slate-500 dark:text-slate-400 font-medium" x-text="__tTenants.locale === 'ar' ? 'سلة المحذوفات فارغة' : 'Trash is empty'"></p>
                </div>

                <!-- Trashed list -->
                <div x-show="!trashLoading && trashedTenants.length > 0" class="space-y-3">
                    <!-- Warning banner -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 rounded-xl px-4 py-3 flex items-start gap-3 mb-4">
                        <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <p class="text-xs text-amber-700 dark:text-amber-400" x-text="__tTenants.locale === 'ar' ? 'الحذف النهائي لا يمكن التراجع عنه.' : 'Permanent deletion cannot be undone.'"></p>
                    </div>

                    <template x-for="t in trashedTenants" :key="t.id">
                        <div class="flex items-center gap-4 bg-slate-50 dark:bg-white/[0.03] dark:border dark:border-white/[0.06] rounded-xl px-4 py-3">
                            <!-- Avatar -->
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-black flex-shrink-0"
                                 :style="`background: hsl(${(t.name.charCodeAt(0)*37)%360},55%,45%)`">
                                <span x-text="t.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 dark:text-white truncate" x-text="t.name"></p>
                                <p class="text-xs text-slate-400 font-mono truncate" x-text="t.domain"></p>
                            </div>
                            <!-- Deleted at -->
                            <p class="text-xs text-slate-400 flex-shrink-0" x-text="formatDate(t.deleted_at)"></p>
                            <!-- Actions -->
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button @click="restoreTenant(t.id, t.name)"
                                        :disabled="submitting"
                                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-800 rounded-lg transition disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                    </svg>
                                    <span x-text="__tTenants.locale === 'ar' ? 'استعادة' : 'Restore'"></span>
                                </button>
                                <button @click="confirmForceDelete(t.id, t.name)"
                                        :disabled="submitting"
                                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-800 rounded-lg transition disabled:opacity-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span x-text="__tTenants.locale === 'ar' ? 'حذف نهائي' : 'Delete forever'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore ALL Confirmation Modal -->
    <div x-show="showRestoreAllModal" x-cloak
         @keydown.escape.window="showRestoreAllModal = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showRestoreAllModal = false"></div>
        <div x-show="showRestoreAllModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-emerald-900/40 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mb-2" x-text="__tTenants.locale === 'ar' ? 'استعادة الكل' : 'Restore All'"></h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1" x-text="__tTenants.locale === 'ar' ? 'سيتم استعادة جميع الشركات الموجودة في السلة:' : 'This will restore all trashed companies:'"></p>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 my-3" x-text="trashedTenants.length"></p>
            <p class="text-xs text-slate-400 dark:text-slate-500 font-medium mb-6" x-text="__tTenants.locale === 'ar' ? 'ستعود الشركات للعمل بشكل طبيعي.' : 'Companies will be fully restored and active again.'"></p>
            <div class="flex gap-3">
                <button @click="showRestoreAllModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="doRestoreAll()"
                        :disabled="submitting"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 rounded-xl transition shadow-lg shadow-emerald-500/25 disabled:opacity-50">
                    <span x-text="__tTenants.locale === 'ar' ? 'استعادة الجميع' : 'Restore All'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Force Delete ALL Confirmation Modal -->
    <div x-show="showForceDeleteAllModal" x-cloak
         @keydown.escape.window="showForceDeleteAllModal = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showForceDeleteAllModal = false"></div>
        <div x-show="showForceDeleteAllModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-red-900/40 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/40 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-2" x-text="__tTenants.locale === 'ar' ? 'حذف الجميع نهائياً' : 'Delete All Forever'"></h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1" x-text="__tTenants.locale === 'ar' ? 'سيتم حذف جميع الشركات الموجودة في السلة:' : 'This will permanently delete all trashed companies:'"></p>
            <p class="text-2xl font-black text-red-600 dark:text-red-400 my-3" x-text="trashedTenants.length"></p>
            <p class="text-xs text-red-500 dark:text-red-400 font-semibold mb-6" x-text="__tTenants.locale === 'ar' ? 'لا يمكن التراجع عن هذا الإجراء نهائياً.' : 'This action is completely irreversible.'"></p>
            <div class="flex gap-3">
                <button @click="showForceDeleteAllModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="doForceDeleteAll()"
                        :disabled="submitting"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition shadow-lg shadow-red-500/25 disabled:opacity-50">
                    <span x-text="__tTenants.locale === 'ar' ? 'احذف الجميع' : 'Delete All'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Force Delete Confirmation Modal -->
    <div x-show="showForceDeleteModal" x-cloak
         @keydown.escape.window="showForceDeleteModal = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showForceDeleteModal = false"></div>
        <div x-show="showForceDeleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-[#12101f] dark:border dark:border-white/[0.08] rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2" x-text="__tTenants.locale === 'ar' ? 'حذف نهائي' : 'Delete Forever'"></h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-1" x-text="__tTenants.locale === 'ar' ? 'سيتم حذف هذه الشركة بشكل دائم:' : 'This will permanently delete:'"></p>
            <p class="font-bold text-slate-900 dark:text-white mb-2" x-text="forceDeleteTargetName"></p>
            <p class="text-xs text-red-500 mb-6" x-text="__tTenants.locale === 'ar' ? 'لا يمكن التراجع عن هذا الإجراء.' : 'This action is irreversible.'"></p>
            <div class="flex gap-3">
                <button @click="showForceDeleteModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-white/[0.06] hover:bg-slate-200 dark:hover:bg-white/[0.1] rounded-xl transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="doForceDelete()"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition shadow-lg shadow-red-500/25">
                    <span x-text="__tTenants.locale === 'ar' ? 'احذف نهائياً' : 'Delete Forever'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@php
$__tTenants = [
    'load_fail'      => __('super-admin.toast_tenant_load_fail'),
    'add_success'    => __('super-admin.toast_tenant_add_success'),
    'add_error'      => __('super-admin.toast_tenant_add_error'),
    'status_updated' => __('super-admin.toast_status_updated'),
    'status_fail'    => __('super-admin.toast_status_fail'),
    'delete_success' => __('super-admin.toast_delete_success'),
    'delete_fail'    => __('super-admin.toast_delete_fail'),
    'delete_error'   => __('super-admin.toast_delete_error2'),
    'reset_success'  => __('super-admin.toast_reset_pw_success'),
    'reset_fail'     => __('super-admin.toast_reset_pw_fail'),
    'reset_error'    => __('super-admin.toast_reset_pw_error'),
    'reset_confirm'  => __('super-admin.tenant_reset_pw_confirm'),
    'of_word'        => __('super-admin.common_of'),
    'locale'         => app()->getLocale(),
];
@endphp
<script>
const __tTenants = @json($__tTenants);
function tenantsManager() {
    return {
        loading: true,
        submitting: false,
        tenants: [],
        filteredTenants: [],
        pagedTenants: [],
        searchQuery: '',
        perPage: 5,
        currentPage: 1,
        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredTenants.length / this.perPage));
        },
        showAddModal: false,
        showEditModal: false,
        showCredentialsModal: false,
        showDeleteModal: false,
        showResetModal: false,
        showViewModal: false,
        showTrashModal: false,
        showForceDeleteModal: false,
        showForceDeleteAllModal: false,
        showDeleteAllModal: false,
        trashLoading: false,
        trashedTenants: [],
        trashedCount: 0,
        forceDeleteTargetId: null,
        forceDeleteTargetName: '',
        showRestoreAllModal: false,
        deleteTargetId: null,
        deleteTargetName: '',
        resetTargetId: null,
        resetTargetName: '',
        editingTenantId: null,
        selectedTenant: null,
        tenantStats: null,
        tenantStatsLoading: false,
        credentials: {},
        newTenant: {
            name: '',
            domain: '',
            email: ''
        },
        editForm: {
            name: '',
            domain: ''
        },

        async loadTenants() {
            try {
                const response = await fetch('/api/super-admin/tenants', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.tenants = data.data;
                    this.filterTenants();
                    this.loadTrashCount();

                    // Auto-open view modal if redirected from dashboard with ?view= or ?edit=
                    const params = new URLSearchParams(window.location.search);
                    const targetId = params.get('view') || params.get('edit');
                    if (targetId) {
                        const tenant = this.tenants.find(t => t.id === targetId);
                        if (tenant) this.viewTenant(tenant);
                        // Clean up the URL without reload
                        window.history.replaceState({}, '', window.location.pathname);
                    }
                }
            } catch (error) {
                console.error('Error loading tenants:', error);
                showToast(__tTenants.load_fail, 'error');
            } finally {
                this.loading = false;
            }
        },

        openAddModal() {
            this.newTenant = { name: '', domain: '', email: '' };
            this.showAddModal = true;
        },

        async addTenant() {
            this.submitting = true;
            try {
                const response = await fetch('/api/super-admin/tenants', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(this.newTenant)
                });

                const data = await response.json();

                if (data.success) {
                    this.credentials = data.data.credentials;
                    this.showAddModal = false;
                    this.showCredentialsModal = true;
                    await this.loadTenants();
                    this.filterTenants();
                    showToast(__tTenants.add_success, 'success');
                } else {
                    const errMsg = data.errors
                        ? Object.values(data.errors).flat().join(' \u2022 ')
                        : (data.message || __tTenants.add_error);
                    showToast(errMsg, 'error');
                }
            } catch (error) {
                console.error('Error adding tenant:', error);
                showToast(__tTenants.add_error, 'error');
            } finally {
                this.submitting = false;
            }
        },

        async toggleStatus(id, currentStatus) {
            try {
                const response = await fetch(`/api/super-admin/tenants/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadTenants();
                    showToast(__tTenants.status_updated, 'success');
                } else {
                    showToast(data.message || __tTenants.status_fail, 'error');
                }
            } catch (error) {
                console.error('Error toggling status:', error);
            }
        },

        confirmDelete(id, name) {
            this.deleteTargetId = id;
            this.deleteTargetName = name;
            this.showDeleteModal = true;
        },

        async doDeleteAll() {
            this.showDeleteAllModal = false;
            this.submitting = true;
            const count = this.tenants.length;
            try {
                const r = await fetch('/api/super-admin/tenants/delete-all', {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include'
                });
                const d = await r.json();
                if (d.success) {
                    await this.loadTenants();
                    showToast(
                        __tTenants.locale === 'ar'
                            ? `تم نقل ${count} شركة إلى سلة المحذوفات`
                            : `${count} companies moved to trash`,
                        'success'
                    );
                } else {
                    showToast(d.message || 'Error', 'error');
                }
            } catch (_) { showToast('Error', 'error'); }
            finally { this.submitting = false; }
        },

        async deleteTenant(id) {
            this.showDeleteModal = false;
            try {
                const response = await fetch(`/api/super-admin/tenants/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadTenants();
                    this.filterTenants();
                    showToast(__tTenants.delete_success, 'success');
                } else {
                    showToast(data.message || __tTenants.delete_fail, 'error');
                }
            } catch (error) {
                console.error('Error deleting tenant:', error);
                showToast(__tTenants.delete_error, 'error');
            }
        },

        openEditModal(tenant) {
            this.editingTenantId = tenant.id;
            this.editForm = {
                name: tenant.name,
                domain: tenant.domain
            };
            this.showEditModal = true;
        },

        async updateTenant() {
            this.submitting = true;
            try {
                const response = await fetch(`/api/super-admin/tenants/${this.editingTenantId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(this.editForm)
                });

                const data = await response.json();

                if (data.success) {
                    this.showEditModal = false;
                    await this.loadTenants();
                    showToast(__tTenants.update_success ?? 'تم تحديث بيانات الشركة', 'success');
                } else {
                    const errMsg = data.errors
                        ? Object.values(data.errors).flat().join(' \u2022 ')
                        : (data.message || 'حدث خطأ');
                    showToast(errMsg, 'error');
                }
            } catch (error) {
                console.error('Error updating tenant:', error);
                showToast('حدث خطأ أثناء التحديث', 'error');
            } finally {
                this.submitting = false;
            }
        },

        confirmReset(id, name) {
            this.resetTargetId = id;
            this.resetTargetName = name;
            this.showResetModal = true;
        },

        async doResetPassword() {
            this.showResetModal = false;
            const id = this.resetTargetId;
            const name = this.resetTargetName;
            try {
                const response = await fetch(`/api/super-admin/tenants/${id}/reset-admin-password`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    this.credentials = {
                        email: data.data.email,
                        password: data.data.password,
                        login_url: 'https://' + (this.tenants.find(t => t.id === id)?.domain || id)
                    };
                    this.showCredentialsModal = true;
                    showToast(__tTenants.reset_success, 'success');
                } else {
                    showToast(data.message || __tTenants.reset_fail, 'error');
                }
            } catch (error) {
                showToast(__tTenants.reset_error, 'error');
            }
        },

        viewTenant(tenant) {
            this.selectedTenant = tenant;
            this.tenantStats = null;
            this.showViewModal = true;
            this.loadTenantStats(tenant.id);
        },

        async loadTenantStats(id) {
            this.tenantStatsLoading = true;
            try {
                const response = await fetch(`/api/super-admin/tenants/${id}/statistics`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include'
                });
                const data = await response.json();
                if (data.success) this.tenantStats = data.data;
            } catch (e) {
                // stats unavailable — non-critical
            } finally {
                this.tenantStatsLoading = false;
            }
        },

        paginate() {
            const start = (this.currentPage - 1) * this.perPage;
            this.pagedTenants = this.filteredTenants.slice(start, start + this.perPage);
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            this.paginate();
        },

        filterTenants() {
            if (!this.searchQuery) {
                this.filteredTenants = this.tenants;
            } else {
                const q = this.searchQuery.toLowerCase();
                this.filteredTenants = this.tenants.filter(t =>
                    t.name.toLowerCase().includes(q) ||
                    t.domain.toLowerCase().includes(q) ||
                    (t.email || '').toLowerCase().includes(q)
                );
            }
            this.currentPage = 1;
            this.paginate();
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString(__tTenants.locale);
        },

        async loadTrashCount() {
            try {
                const r = await fetch('/api/super-admin/tenants/trash', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include'
                });
                const d = await r.json();
                if (d.success) this.trashedCount = d.data.length;
            } catch (_) {}
        },

        async openTrashModal() {
            this.showTrashModal = true;
            this.trashLoading = true;
            try {
                const r = await fetch('/api/super-admin/tenants/trash', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include'
                });
                const d = await r.json();
                if (d.success) {
                    this.trashedTenants = d.data;
                    this.trashedCount = d.data.length;
                }
            } catch (_) {}
            finally { this.trashLoading = false; }
        },

        async restoreTenant(id, name) {
            this.submitting = true;
            try {
                const r = await fetch(`/api/super-admin/tenants/${id}/restore`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include'
                });
                const d = await r.json();
                if (d.success) {
                    this.trashedTenants = this.trashedTenants.filter(t => t.id !== id);
                    this.trashedCount = this.trashedTenants.length;
                    await this.loadTenants();
                    showToast(__tTenants.locale === 'ar' ? `تمت استعادة ${name}` : `${name} restored`, 'success');
                } else {
                    showToast(d.message || 'Error', 'error');
                }
            } catch (_) { showToast('Error', 'error'); }
            finally { this.submitting = false; }
        },

        confirmForceDelete(id, name) {
            this.forceDeleteTargetId = id;
            this.forceDeleteTargetName = name;
            this.showForceDeleteModal = true;
        },

        async doForceDelete() {
            this.showForceDeleteModal = false;
            this.submitting = true;
            const id   = this.forceDeleteTargetId;
            const name = this.forceDeleteTargetName;
            try {
                const r = await fetch(`/api/super-admin/tenants/${id}/force-delete`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include'
                });
                const d = await r.json();
                if (d.success) {
                    this.trashedTenants = this.trashedTenants.filter(t => t.id !== id);
                    this.trashedCount = this.trashedTenants.length;
                    showToast(__tTenants.locale === 'ar' ? `تم حذف ${name} نهائياً` : `${name} permanently deleted`, 'error');
                } else {
                    showToast(d.message || 'Error', 'error');
                }
            } catch (_) { showToast('Error', 'error'); }
            finally { this.submitting = false; }
        },

        async doForceDeleteAll() {
            this.showForceDeleteAllModal = false;
            this.submitting = true;
            try {
                const r = await fetch('/api/super-admin/tenants/force-delete-all', {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include'
                });
                const d = await r.json();
                if (d.success) {
                    const count = this.trashedTenants.length;
                    this.trashedTenants = [];
                    this.trashedCount = 0;
                    showToast(
                        __tTenants.locale === 'ar'
                            ? `تم حذف ${count} شركة نهائياً`
                            : `${count} companies permanently deleted`,
                        'error'
                    );
                } else {
                    showToast(d.message || 'Error', 'error');
                }
            } catch (_) { showToast('Error', 'error'); }
            finally { this.submitting = false; }
        },

        async doRestoreAll() {
            this.showRestoreAllModal = false;
            this.submitting = true;
            const count = this.trashedTenants.length;
            try {
                const r = await fetch('/api/super-admin/tenants/restore-all', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include'
                });
                const d = await r.json();
                if (d.success) {
                    this.trashedTenants = [];
                    this.trashedCount = 0;
                    await this.loadTenants();
                    showToast(
                        __tTenants.locale === 'ar'
                            ? `تمت استعادة ${count} شركة`
                            : `${count} companies restored`,
                        'success'
                    );
                } else {
                    showToast(d.message || 'Error', 'error');
                }
            } catch (_) { showToast('Error', 'error'); }
            finally { this.submitting = false; }
        },
    }
}
</script>
@endpush
