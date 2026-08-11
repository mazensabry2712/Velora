{{-- Filters --}}
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6 flex flex-wrap gap-3 items-center">
    <input type="text" x-model="search" @input.debounce.400ms="fetchCustomers()"
        placeholder="{{ $isArabic ? 'ابحث بالاسم أو الإيميل أو التليفون...' : 'Search by name, email or phone…' }}"
        class="filter-input flex-1 min-w-48">

    <select x-model="vipFilter" @change="fetchCustomers()" class="filter-input">
        <option value="">{{ $isArabic ? 'الكل' : 'All' }}</option>
        <option value="1">{{ $isArabic ? 'VIP فقط' : 'VIP only' }}</option>
        <option value="0">{{ $isArabic ? 'غير VIP' : 'Non-VIP' }}</option>
    </select>
</div>
