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
        <button @click="openAddModal()"
                class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900 transition-all duration-200 hover:-translate-y-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('super-admin.tenant_add') }}
        </button>
    </div>

    <!-- Skeleton Loading State -->
    <div x-show="loading" class="space-y-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded-lg w-32 skeleton"></div>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <template x-for="i in 6" :key="i">
                    <div class="px-6 py-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 skeleton flex-shrink-0"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/3 skeleton"></div>
                            <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/2 skeleton"></div>
                        </div>
                        <div class="h-6 w-16 bg-slate-200 dark:bg-slate-700 rounded-full skeleton"></div>
                        <div class="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded skeleton"></div>
                        <div class="flex gap-2">
                            <div class="h-8 w-8 bg-slate-200 dark:bg-slate-700 rounded-lg skeleton"></div>
                            <div class="h-8 w-8 bg-slate-200 dark:bg-slate-700 rounded-lg skeleton"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Tenants List -->
    <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <!-- Table header with search -->
        <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h2 class="font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenants_list') }}</h2>
                <span class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2.5 py-1 rounded-full font-medium"
                      x-text="(filteredTenants.length !== tenants.length ? filteredTenants.length + ' {{ __('super-admin.tenant_of') }} ' : '') + tenants.length + ' {{ __('super-admin.tenant_company_label') }}'" ></span>
            </div>
            <div class="relative">
                <input type="text" x-model="searchQuery" @input="filterTenants()"
                       placeholder="{{ __('super-admin.tenant_search') }}"
                       class="w-56 pr-9 pl-4 py-2 text-sm border border-slate-200 dark:border-slate-600 rounded-xl bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                <svg class="w-4 h-4 text-slate-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_company') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_domain') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_email') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_status') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_plan') }}</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_created_at') }}</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.tenant_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    <template x-for="tenant in pagedTenants" :key="tenant.id">
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30 transition-colors duration-150 group">
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
                                <code class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-2 py-1 rounded-md font-mono" x-text="tenant.domain"></code>
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
                            <td class="px-6 py-4"> {{-- Plan --}}
                                <template x-if="tenant.current_subscription && tenant.current_subscription.plan">
                                    <span :class="tenant.current_subscription.status === 'active'
                                        ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 ring-1 ring-blue-200'
                                        : 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 ring-1 ring-purple-200'"
                                          class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full">
                                        <span x-text="tenant.current_subscription.plan.name"></span>
                                        <span x-show="tenant.current_subscription.status === 'trial'" class="text-xs opacity-70">({{ __('super-admin.tenant_trial') }})</span>
                                    </span>
                                </template>
                                <template x-if="!tenant.current_subscription || !tenant.current_subscription.plan">
                                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('super-admin.tenant_no_sub') }}</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400" x-text="formatDate(tenant.created_at)"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="viewTenant(tenant)"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition tooltip" data-tip="{{ __('super-admin.tenant_view_details') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                    <button @click="resetPassword(tenant.id, tenant.name)"
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
                        <td colspan="7" class="px-6 py-16 text-center">
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
        <div x-show="filteredTenants.length > 0" class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between">

            <!-- Per-page + info -->
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <span>{{ __('super-admin.pagination_show') }}</span>
                <select x-model.number="perPage" @change="currentPage = 1; paginate()"
                        class="border border-slate-200 dark:border-slate-600 rounded-lg px-2 py-1 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>{{ __('super-admin.pagination_per_page') }}</span>
                <span class="hidden sm:inline text-slate-300 dark:text-slate-600 mx-1">|</span>
                <span class="hidden sm:inline" x-text="`${((currentPage-1)*perPage)+1}–${Math.min(currentPage*perPage, filteredTenants.length)} من ${filteredTenants.length}`"></span>
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
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
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
             class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg">

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenant_add') }}</h3>
                </div>
                <button @click="showAddModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
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
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition"
                               placeholder="{{ __('super-admin.tenant_form_name_ph') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('super-admin.tenant_domain') }} <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newTenant.domain" required
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition font-mono text-sm"
                               placeholder="company.booking-saas.test">
                        <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.tenant_form_domain_help') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('super-admin.tenant_email') }} <span class="text-slate-400 font-normal">({{ __('super-admin.tenant_form_optional') }})</span></label>
                        <input type="email" x-model="newTenant.email"
                               class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition"
                               placeholder="admin@company.com">
                        <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.tenant_form_email_info') }}</p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end p-6 pt-0">
                    <button type="button" @click="showAddModal = false"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition">
                        {{ __('super-admin.common_cancel') }}
                    </button>
                    <button type="submit" :disabled="submitting"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed">
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
                 class="fixed inset-0 transition-opacity bg-slate-900 bg-opacity-75"></div>

            <div x-show="showCredentialsModal"
                 class="inline-block bg-white dark:bg-slate-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenant_creds_title') }}</h3>
                        <button @click="showCredentialsModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 mb-4">
                        <p class="text-sm text-emerald-800 dark:text-emerald-300 mb-2">✅ {{ __('super-admin.tenant_creds_created_ok') }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ __('super-admin.tenant_creds_save_hint') }}</p>
                    </div>

                    <div class="space-y-3">
                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_email') }}</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white" x-text="credentials.email"></p>
                        </div>

                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_creds_password') }}</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white" x-text="credentials.password"></p>
                        </div>

                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">{{ __('super-admin.tenant_creds_login_url') }}</p>
                            <a :href="credentials.login_url"
                               target="_blank"
                               class="text-sm font-mono text-indigo-600 dark:text-indigo-400 hover:underline"
                               x-text="credentials.login_url"></a>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end">
                    <button @click="showCredentialsModal = false"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                        {{ __('super-admin.common_done') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
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
             class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
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
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="deleteTenant(deleteTargetId)"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl transition">
                    {{ __('super-admin.tenant_delete_permanent') }}
                </button>
            </div>
        </div>
    </div>

    <!-- View Tenant Modal -->
    <div x-show="showViewModal" x-cloak
         @keydown.escape.window="showViewModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showViewModal = false"></div>
        <div x-show="showViewModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.tenant_view_title') }}</h3>
                <button @click="showViewModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6" x-show="selectedTenant">
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100 dark:border-slate-700">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl font-black shadow-lg"
                         :style="selectedTenant ? `background: hsl(${(selectedTenant.name.charCodeAt(0) * 37) % 360}, 65%, 50%)` : ''"
                         x-text="selectedTenant ? selectedTenant.name.charAt(0).toUpperCase() : ''"></div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white" x-text="selectedTenant?.name"></h4>
                        <span :class="selectedTenant?.active ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                              class="text-xs font-semibold px-2.5 py-1 rounded-full"
                              x-text="selectedTenant?.active ? '{{ __('super-admin.tenant_active') }}' : '{{ __('super-admin.tenant_inactive') }}'"></span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-sm text-slate-500">{{ __('super-admin.tenant_plan') }}</span>
                        <span :class="selectedTenant?.current_subscription?.status === 'active' ? 'text-blue-600 font-semibold' : 'text-purple-600 font-semibold'"
                              class="text-sm" x-text="selectedTenant?.current_subscription?.plan?.name || '{{ __('super-admin.tenant_no_sub') }}'"></span>
                    </div>
                    <div class="flex items-center justify-between py-2.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-sm text-slate-500">{{ __('super-admin.tenant_domain') }}</span>
                        <code class="text-sm bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-lg font-mono" x-text="selectedTenant?.domain"></code>
                    </div>
                    <div class="flex items-center justify-between py-2.5 border-b border-slate-100 dark:border-slate-700">
                        <span class="text-sm text-slate-500">{{ __('super-admin.tenant_email') }}</span>
                        <span class="text-sm text-slate-900 dark:text-white" x-text="selectedTenant?.email || '-'"></span>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-sm text-slate-500">{{ __('super-admin.tenant_created_at') }}</span>
                        <span class="text-sm text-slate-900 dark:text-white" x-text="selectedTenant ? formatDate(selectedTenant.created_at) : '-'"></span>
                    </div>
                </div>
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
        showCredentialsModal: false,
        showDeleteModal: false,
        showViewModal: false,
        deleteTargetId: null,
        deleteTargetName: '',
        selectedTenant: null,
        credentials: {},
        newTenant: {
            name: '',
            domain: '',
            email: ''
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
                    this.tenants = data.data.data;
                    this.filterTenants();
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

        async resetPassword(id, name) {
            if (!confirm(__tTenants.reset_confirm.replace(':name', name))) return;
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
            this.showViewModal = true;
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
        }
    }
}
</script>
@endpush
