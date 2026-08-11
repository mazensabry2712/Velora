@extends('layouts.admin')

@php
    // Was previously referenced throughout this view without ever being
    // passed by the controller or a shared view composer — every
    // "$isArabic ? ... : ..." ternary silently evaluated as falsy,
    // so the page always rendered in English regardless of locale.
    $isArabic = app()->getLocale() === 'ar';
@endphp

@section('title', __('Customers'))
@section('subtitle', __('View and manage all registered customers'))

@push('styles')
<style>
    .stat-card { @apply bg-white rounded-xl shadow-sm border border-slate-100 p-4 hover:shadow-md transition-shadow dark:bg-slate-800 dark:border-slate-700; }
    .filter-input { @apply px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100; }
    .dark .filter-input { background-color: #334155 !important; color: #f1f5f9 !important; border-color: #475569 !important; }
</style>
@endpush

@section('content')
<div class="p-6 max-w-7xl mx-auto" x-data="customersApp()" x-init="init()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ $isArabic ? 'إدارة العملاء' : 'Customer Management' }}
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                {{ $isArabic ? 'عرض وإدارة جميع العملاء المسجلين' : 'View and manage all registered customers' }}
            </p>
        </div>
    </div>

    @include('admin.customers.partials.filters')
    @include('admin.customers.partials.table')
</div>

@include('admin.customers.partials.detail-modal')
@endsection

@push('scripts')
<script>
    function customersApp() {
        return {
            customers: [],
            loading: false,
            search: '',
            vipFilter: '',
            page: 1,
            totalPages: 1,
            showModal: false,
            selectedCustomer: null,
            customerStats: null,
            customerAppointments: [],

            init() {
                this.fetchCustomers();
            },

            async fetchCustomers() {
                this.loading = true;
                const params = new URLSearchParams({
                    page: this.page,
                    per_page: 20,
                    ...(this.search && { search: this.search }),
                    ...(this.vipFilter !== '' && { is_vip: this.vipFilter }),
                });
                try {
                    const res = await fetch(`{{ route('admin.api.customers.index') }}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.customers   = data.data;
                        this.totalPages  = data.pages ?? 1;
                    }
                } catch(e) { console.error(e); }
                this.loading = false;
            },

            async toggleVip(customer) {
                try {
                    const res = await fetch(`/admin/api/customers/${customer.id}/vip`, {
                        method: 'PUT',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    const data = await res.json();
                    if (data.success) customer.is_vip = data.is_vip;
                } catch(e) { console.error(e); }
            },

            async viewCustomer(id) {
                this.selectedCustomer = null;
                this.customerStats = null;
                this.customerAppointments = [];
                this.showModal = true;
                try {
                    const [profileRes, aptsRes] = await Promise.all([
                        fetch(`/admin/api/customers/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
                        fetch(`/admin/api/customers/${id}/appointments?per_page=10`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
                    ]);
                    const profile = await profileRes.json();
                    const apts    = await aptsRes.json();
                    if (profile.success) {
                        this.selectedCustomer = profile.data;
                        this.customerStats    = profile.stats;
                    }
                    if (apts.success) this.customerAppointments = apts.data;
                } catch(e) { console.error(e); }
            },

            async deleteCustomer(customer) {
                const confirmMsg = `{{ $isArabic ? 'هل أنت متأكد من حذف العميل' : 'Are you sure you want to delete' }} ${customer.name}?`;
                if (!confirm(confirmMsg)) return;
                try {
                    const res = await fetch(`/admin/api/customers/${customer.id}`, {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    const data = await res.json();
                    if (data.success) this.customers = this.customers.filter(c => c.id !== customer.id);
                } catch(e) { console.error(e); }
            },
        };
    }
</script>
@endpush
