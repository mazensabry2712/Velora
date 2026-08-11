@extends('layouts.admin')

@section('title', __('Settings'))
@section('subtitle', __('Manage your business information and social media links'))

@push('styles')
<style>
    [dir="rtl"] input, [dir="rtl"] textarea {
        text-align: right;
    }
    [dir="rtl"] input[type="url"], [dir="rtl"] input[type="email"], [dir="rtl"] input[type="tel"] {
        text-align: left;
        direction: ltr;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if(session('success'))
    <div class="bg-emerald-50 dark:bg-emerald-900 border border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 rounded-lg p-4">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 rounded-lg p-4">
        {{ session('error') }}
    </div>
    @endif

    <form id="settingsForm" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.settings.partials.business-info')
        @include('admin.settings.partials.languages')
        @include('admin.settings.partials.logo')
        @include('admin.settings.partials.social-media')

        <div class="flex {{ app()->getLocale() === 'ar' ? 'justify-start' : 'justify-end' }} gap-4">
            <button type="submit" id="saveBtn" class="px-6 py-3 bg-indigo-600 dark:bg-indigo-700 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ __('Save Settings') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Preview logo before upload
    function previewLogo(input) {
        const preview = document.getElementById('logoPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview" class="w-full h-full object-contain">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Handle form submit
    document.getElementById('settingsForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('saveBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> {{ __("Saving...") }}';
        btn.disabled = true;

        try {
            const formData = new FormData(e.target);

            const response = await fetch('/admin/api/settings', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                const alert = document.createElement('div');
                alert.className = 'fixed top-4 {{ app()->getLocale() === "ar" ? "left-4" : "right-4" }} bg-emerald-600 dark:bg-emerald-700 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                alert.innerHTML = '✓ {{ __("Settings saved successfully!") }}';
                document.body.appendChild(alert);
                setTimeout(() => alert.remove(), 3000);
            } else {
                alert(result.message || '{{ __("Error occurred") }}');
            }
        } catch (error) {
            alert('{{ __("Error occurred") }}');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
</script>
@endpush
