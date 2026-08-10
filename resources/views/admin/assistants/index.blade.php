@extends('layouts.admin')

@section('title', __('Assistants'))
@section('subtitle', __('Manage assistants and their permissions'))

@section('header-actions')
    <button onclick="openAssistantModal()"
        class="w-full sm:w-auto justify-center bg-indigo-600 dark:bg-indigo-700 text-white px-4 py-2.5 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 flex items-center gap-2 text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        {{ __('Add Assistant') }}
    </button>
@endsection

@section('content')
    <!-- Assistants Grid -->
    <div id="assistantsList" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <!-- Skeleton loaders shown until JS replaces this -->
        @for ($i = 0; $i < 3; $i++)
            <div
                class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-5 animate-pulse">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-700 flex-shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3.5 w-2/3 bg-slate-200 dark:bg-slate-700 rounded"></div>
                        <div class="h-3 w-1/2 bg-slate-200 dark:bg-slate-700 rounded"></div>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <div class="h-5 w-16 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                    <div class="h-5 w-20 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Add/Edit Assistant Modal -->
    <div id="assistantModal" class="fixed inset-0 bg-black/50 dark:bg-black/70 hidden items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-5 sm:p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    {{ __('Add New Assistant') }}</h3>
                <button onclick="closeAssistantModal()"
                    class="text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <form id="assistantForm" class="space-y-4">
                <input type="hidden" id="assistantId" name="id">

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Name') }}
                        *</label>
                    <input type="text" name="name" id="assistantName" required
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Email') }}
                        *</label>
                    <input type="email" name="email" id="assistantEmail" required
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Phone') }}</label>
                    <input type="tel" name="phone" id="assistantPhone"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                        placeholder="+966 5xxxxxxxx">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('Password') }}
                        <span id="passwordRequired">*</span></label>
                    <input type="password" name="password" id="assistantPassword" minlength="8"
                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                    <p id="passwordHint" class="text-xs text-slate-500 dark:text-slate-400 mt-1 hidden">
                        {{ __('Leave empty to keep current password') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Permissions') }}
                        *</label>

                    <!-- Selected Permissions Tags -->
                    <div id="selectedPermissions" class="flex flex-wrap gap-2 mb-3 min-h-[32px]">
                        <!-- Selected permissions will appear here as tags -->
                    </div>

                    <!-- Dropdown to select permissions -->
                    <div class="relative">
                        <button type="button" id="permissionDropdownBtn" onclick="togglePermissionDropdown()"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 rounded-lg text-start flex items-center justify-between focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Select permissions...') }}</span>
                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="permissionDropdown"
                            class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                            @php
                                $permissions = \App\Http\Controllers\Web\AssistantController::getAvailablePermissions();
                            @endphp
                            @foreach ($permissions as $key => $permission)
                                <div id="perm-option-{{ $key }}"
                                    class="permission-option px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer border-b border-slate-100 dark:border-slate-700 last:border-b-0"
                                    data-key="{{ $key }}" data-name="{{ $permission['name'] }}"
                                    onclick="selectPermission('{{ $key }}', '{{ $permission['name'] }}')">
                                    <span
                                        class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $permission['name'] }}</span>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $permission['description'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Hidden inputs for selected permissions -->
                    <div id="permissionInputs"></div>
                </div>

                <div
                    class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" onclick="closeAssistantModal()"
                        class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="bg-indigo-600 dark:bg-indigo-700 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors">
                        {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let editingAssistantId = null;

        // Load assistants on page load
        document.addEventListener('DOMContentLoaded', loadAssistants);

        async function loadAssistants() {
            try {
                const response = await fetch('/admin/api/assistants', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();

                const container = document.getElementById('assistantsList');

                if (data.data && data.data.length > 0) {
                    container.className = 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4';
                    container.innerHTML = data.data.map(assistant => `
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-5 hover:shadow-md dark:hover:border-slate-600 transition-all">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/40 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-purple-600 dark:text-purple-400 font-bold text-lg">${assistant.name.charAt(0).toUpperCase()}</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-slate-900 dark:text-slate-100 truncate">${assistant.name}</h4>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 truncate">${assistant.email}</p>
                                    ${assistant.phone ? `<p class="text-sm text-slate-400 dark:text-slate-500 truncate">${assistant.phone}</p>` : ''}
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button onclick="editAssistant(${assistant.id})" class="p-2 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors" title="{{ __('Edit') }}">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="deleteAssistant(${assistant.id})" class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="{{ __('Delete') }}">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                            ${(assistant.permissions || []).length > 0
                                ? (assistant.permissions || []).map(p => `
                                        <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-xs font-medium rounded-full">${getPermissionLabel(p)}</span>
                                    `).join('')
                                : `<span class="text-xs text-slate-400 dark:text-slate-500">{{ __('No permissions assigned') }}</span>`
                            }
                        </div>
                    </div>
                `).join('');
                } else {
                    container.className = '';
                    container.innerHTML = `
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-1">{{ __('No assistants yet') }}</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-4">{{ __('Add your first assistant to help manage the system') }}</p>
                        <button onclick="openAssistantModal()" class="bg-indigo-600 dark:bg-indigo-700 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 inline-flex items-center gap-2 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            {{ __('Add Assistant') }}
                        </button>
                    </div>
                `;
                }
            } catch (error) {
                console.error('Error loading assistants:', error);
                const container = document.getElementById('assistantsList');
                container.className = '';
                container.innerHTML = `
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-red-200 dark:border-red-800 p-8 text-center">
                    <p class="text-red-600 dark:text-red-400">{{ __('Failed to load assistants. Please refresh the page.') }}</p>
                </div>
            `;
            }
        }

        const permissionLabels = {
            'manage_appointments': '{{ __('Appointments') }}',
            'manage_queue': '{{ __('Queue') }}',
            'manage_staff': '{{ __('Staff') }}',
            'manage_customers': '{{ __('Customers') }}',
            'view_reports': '{{ __('Reports') }}',
            'manage_settings': '{{ __('Settings') }}',
            'manage_assistants': '{{ __('Assistants') }}',
        };

        function getPermissionLabel(permission) {
            return permissionLabels[permission] || permission;
        }

        // Selected permissions tracking
        let selectedPermissions = [];

        function togglePermissionDropdown() {
            const dropdown = document.getElementById('permissionDropdown');
            dropdown.classList.toggle('hidden');
        }

        function selectPermission(key, name) {
            if (selectedPermissions.includes(key)) return;

            selectedPermissions.push(key);

            const option = document.getElementById('perm-option-' + key);
            if (option) option.classList.add('hidden');

            const tagsContainer = document.getElementById('selectedPermissions');
            const tag = document.createElement('span');
            tag.id = 'perm-tag-' + key;
            tag.className =
                'inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-sm font-medium rounded-full';
            tag.innerHTML = `
            ${name}
            <button type="button" onclick="removePermission('${key}')" class="text-indigo-500 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
            tagsContainer.appendChild(tag);

            const inputsContainer = document.getElementById('permissionInputs');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'permissions[]';
            input.value = key;
            input.id = 'perm-input-' + key;
            inputsContainer.appendChild(input);

            document.getElementById('permissionDropdown').classList.add('hidden');
            updateDropdownButton();
        }

        function removePermission(key) {
            selectedPermissions = selectedPermissions.filter(p => p !== key);

            const option = document.getElementById('perm-option-' + key);
            if (option) option.classList.remove('hidden');

            const tag = document.getElementById('perm-tag-' + key);
            if (tag) tag.remove();

            const input = document.getElementById('perm-input-' + key);
            if (input) input.remove();

            updateDropdownButton();
        }

        function updateDropdownButton() {
            const btn = document.getElementById('permissionDropdownBtn');
            const allOptions = document.querySelectorAll('.permission-option');
            const hiddenOptions = document.querySelectorAll('.permission-option.hidden');

            if (hiddenOptions.length === allOptions.length) {
                btn.querySelector('span').textContent = '{{ __('All permissions selected') }}';
            } else if (selectedPermissions.length > 0) {
                btn.querySelector('span').textContent = '{{ __('Add more permissions...') }}';
            } else {
                btn.querySelector('span').textContent = '{{ __('Select permissions...') }}';
            }
        }

        function resetPermissions() {
            selectedPermissions = [];
            document.querySelectorAll('.permission-option').forEach(opt => opt.classList.remove('hidden'));
            document.getElementById('selectedPermissions').innerHTML = '';
            document.getElementById('permissionInputs').innerHTML = '';
            updateDropdownButton();
        }

        function openAssistantModal() {
            editingAssistantId = null;
            document.getElementById('modalTitle').textContent = '{{ __('Add New Assistant') }}';
            document.getElementById('assistantForm').reset();
            document.getElementById('assistantId').value = '';
            document.getElementById('assistantPassword').required = true;
            document.getElementById('passwordRequired').textContent = '*';
            document.getElementById('passwordHint').classList.add('hidden');
            resetPermissions();
            document.getElementById('assistantModal').classList.remove('hidden');
            document.getElementById('assistantModal').classList.add('flex');
        }

        function closeAssistantModal() {
            document.getElementById('assistantModal').classList.add('hidden');
            document.getElementById('assistantModal').classList.remove('flex');
            resetPermissions();
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('permissionDropdown');
            const btn = document.getElementById('permissionDropdownBtn');
            if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        async function editAssistant(id) {
            try {
                const response = await fetch(`/admin/api/assistants/${id}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();

                if (data.success) {
                    editingAssistantId = id;
                    document.getElementById('modalTitle').textContent = '{{ __('Edit Assistant') }}';
                    document.getElementById('assistantId').value = id;
                    document.getElementById('assistantName').value = data.data.name;
                    document.getElementById('assistantEmail').value = data.data.email;
                    document.getElementById('assistantPhone').value = data.data.phone || '';
                    document.getElementById('assistantPassword').value = '';
                    document.getElementById('assistantPassword').required = false;
                    document.getElementById('passwordRequired').textContent = '';
                    document.getElementById('passwordHint').classList.remove('hidden');

                    resetPermissions();
                    (data.data.permissions || []).forEach(permission => {
                        const label = permissionLabels[permission] || permission;
                        selectPermission(permission, label);
                    });

                    document.getElementById('assistantModal').classList.remove('hidden');
                    document.getElementById('assistantModal').classList.add('flex');
                }
            } catch (error) {
                console.error('Error loading assistant:', error);
                alert('{{ __('An error occurred') }}');
            }
        }

        async function deleteAssistant(id) {
            if (!confirm('{{ __('Are you sure you want to delete this assistant?') }}')) return;

            try {
                const response = await fetch(`/admin/api/assistants/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const data = await response.json();

                if (data.success) {
                    loadAssistants();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Error deleting assistant:', error);
                alert('{{ __('An error occurred') }}');
            }
        }

        document.getElementById('assistantForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const permissions = [];
            formData.getAll('permissions[]').forEach(p => permissions.push(p));

            const payload = {
                name: formData.get('name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                permissions: permissions,
            };

            if (formData.get('password')) {
                payload.password = formData.get('password');
            }

            const isEditing = editingAssistantId !== null;
            const url = isEditing ? `/admin/api/assistants/${editingAssistantId}` : '/admin/api/assistants';
            const method = isEditing ? 'PUT' : 'POST';

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    closeAssistantModal();
                    loadAssistants();
                } else {
                    alert(data.message || '{{ __('An error occurred') }}');
                }
            } catch (error) {
                console.error('Error saving assistant:', error);
                alert('{{ __('An error occurred') }}');
            }
        });

        document.getElementById('assistantModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAssistantModal();
            }
        });
    </script>
@endpush
