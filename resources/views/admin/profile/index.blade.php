<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Profile') }} - {{ tenant()->name }}</title>
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
            <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ __('Profile Settings') }}</h2>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('Manage your account settings and preferences') }}</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Success/Error Messages -->
        <div id="alertContainer" class="hidden mb-6">
            <div id="alertMessage" class="rounded-lg p-4"></div>
        </div>

        <div class="space-y-6">
            <!-- Avatar Section -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Profile Picture') }}</h3>

                <div class="flex items-center gap-6">
                    <div class="relative">
                        <div id="avatarPreview" class="w-24 h-24 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center overflow-hidden">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-full h-full object-cover">
                            @else
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold text-3xl">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="cursor-pointer bg-indigo-600 dark:bg-indigo-700 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 inline-block text-center">
                            <input type="file" id="avatarInput" accept="image/*" class="hidden">
                            {{ __('Change Picture') }}
                        </label>
                        @if(auth()->user()->avatar)
                            <button onclick="removeAvatar()" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm">
                                {{ __('Remove Picture') }}
                            </button>
                        @endif
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('JPG, PNG or GIF. Max size 2MB') }}</p>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Profile Information') }}</h3>

                <form id="profileForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Name') }}</label>
                            <input type="text" name="name" value="{{ auth()->user()->name }}" required
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Email') }}</label>
                            <input type="email" name="email" value="{{ auth()->user()->email }}" required
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Phone') }}</label>
                        <input type="text" name="phone" value="{{ auth()->user()->phone }}"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 dark:bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Change Password') }}</h3>

                <form id="passwordForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Current Password') }}</label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('New Password') }}</label>
                            <input type="password" name="password" required minlength="8"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Confirm New Password') }}</label>
                            <input type="password" name="password_confirmation" required minlength="8"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            {{ __('Update Password') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border-2 border-red-200 dark:border-red-800">
                <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">{{ __('Delete Account') }}</h3>
                <p class="text-slate-600 dark:text-slate-400 mb-4">{{ __('Once you delete your account, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}</p>

                <button onclick="openDeleteModal()" class="bg-red-600 dark:bg-red-700 text-white px-6 py-2 rounded-lg hover:bg-red-700 dark:hover:bg-red-600">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </div>
    </main>

    <!-- Delete Account Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black dark:bg-slate-900 bg-opacity-50 dark:bg-opacity-70 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-800 rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">{{ __('Confirm Account Deletion') }}</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-4">{{ __('This action cannot be undone. Please enter your password to confirm.') }}</p>

            <form id="deleteForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Password') }}</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-red-500 dark:focus:ring-red-600 focus:border-red-500 dark:focus:border-red-600">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-600">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-red-600 dark:bg-red-700 text-white px-4 py-2 rounded-lg hover:bg-red-700 dark:hover:bg-red-600">
                        {{ __('Delete Account') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Show alert message
        function showAlert(message, type = 'success') {
            const container = document.getElementById('alertContainer');
            const alert = document.getElementById('alertMessage');

            container.classList.remove('hidden');
            alert.className = 'rounded-lg p-4 ' + (type === 'success' ? 'bg-emerald-50 dark:bg-emerald-900 border border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200' : 'bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200');
            alert.textContent = message;

            setTimeout(() => {
                container.classList.add('hidden');
            }, 5000);
        }

        // Avatar Upload
        document.getElementById('avatarInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const response = await fetch('/admin/profile/avatar', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    // Update preview
                    const preview = document.getElementById('avatarPreview');
                    preview.innerHTML = `<img src="${data.avatar_url}" alt="Avatar" class="w-full h-full object-cover">`;
                    location.reload();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('{{ __("An error occurred") }}', 'error');
            }
        });

        // Remove Avatar
        async function removeAvatar() {
            if (!confirm('{{ __("Are you sure you want to remove your profile picture?") }}')) return;

            try {
                const response = await fetch('/admin/profile/avatar', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    location.reload();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('{{ __("An error occurred") }}', 'error');
            }
        }

        // Profile Form
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('/admin/profile', {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('{{ __("An error occurred") }}', 'error');
            }
        });

        // Password Form
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('/admin/profile/password', {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('{{ __("An error occurred") }}', 'error');
            }
        });

        // Delete Account Modal
        function openDeleteModal() {
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }

        // Delete Account Form
        document.getElementById('deleteForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('/admin/profile', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('{{ __("An error occurred") }}', 'error');
            }
        });

        // Close modal on outside click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
    <script src="/js/dark-mode.js"></script>
</body>
</html>
