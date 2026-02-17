@extends('layouts.admin')

@section('title', __('Assistants'))
@section('subtitle', __('Manage assistants and their permissions'))

@section('header-actions')
<button onclick="openAssistantModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2 text-sm">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
    </svg>
    {{ __('Add Assistant') }}
</button>
@endsection

@section('content')
    <!-- Assistants List -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div id="assistantsList" class="divide-y">
            <!-- Assistants will be loaded here via JavaScript -->
        </div>
    </div>

    <!-- Add/Edit Assistant Modal -->
    <div id="assistantModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">{{ __('Add New Assistant') }}</h3>
                <button onclick="closeAssistantModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="assistantForm" class="space-y-4">
                <input type="hidden" id="assistantId" name="id">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Name') }} *</label>
                    <input type="text" name="name" id="assistantName" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }} *</label>
                    <input type="email" name="email" id="assistantEmail" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone') }}</label>
                    <input type="tel" name="phone" id="assistantPhone"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="+966 5xxxxxxxx">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Password') }} <span id="passwordRequired">*</span></label>
                    <input type="password" name="password" id="assistantPassword" minlength="8"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p id="passwordHint" class="text-xs text-gray-500 mt-1 hidden">{{ __('Leave empty to keep current password') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Permissions') }} *</label>
                    
                    <!-- Selected Permissions Tags -->
                    <div id="selectedPermissions" class="flex flex-wrap gap-2 mb-3 min-h-[32px]">
                        <!-- Selected permissions will appear here as tags -->
                    </div>
                    
                    <!-- Dropdown to select permissions -->
                    <div class="relative">
                        <button type="button" id="permissionDropdownBtn" onclick="togglePermissionDropdown()"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white text-left flex items-center justify-between focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <span class="text-gray-500">{{ __('Select permissions...') }}</span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="permissionDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                            @php
                                $permissions = \App\Http\Controllers\Web\AssistantController::getAvailablePermissions();
                            @endphp
                            @foreach($permissions as $key => $permission)
                            <div id="perm-option-{{ $key }}" 
                                 class="permission-option px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                                 data-key="{{ $key }}"
                                 data-name="{{ $permission['name'] }}"
                                 onclick="selectPermission('{{ $key }}', '{{ $permission['name'] }}')">
                                <span class="text-sm font-medium text-gray-900">{{ $permission['name'] }}</span>
                                <p class="text-xs text-gray-500">{{ $permission['description'] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Hidden inputs for selected permissions -->
                    <div id="permissionInputs"></div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeAssistantModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
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
                container.innerHTML = data.data.map(assistant => `
                    <div class="p-5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                    <span class="text-purple-600 font-bold text-lg">${assistant.name.charAt(0)}</span>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">${assistant.name}</h4>
                                    <p class="text-sm text-gray-500">${assistant.email}</p>
                                    ${assistant.phone ? `<p class="text-sm text-gray-400">${assistant.phone}</p>` : ''}
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    ${(assistant.permissions || []).map(p => `
                                        <span class="px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">${getPermissionLabel(p)}</span>
                                    `).join('')}
                                </div>
                                <div class="flex gap-1">
                                    <button onclick="editAssistant(${assistant.id})" class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="{{ __('Edit') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteAssistant(${assistant.id})" class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="{{ __('Delete') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = `
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">{{ __('No assistants yet') }}</h3>
                        <p class="text-gray-500 mb-4">{{ __('Add your first assistant to help manage the system') }}</p>
                        <button onclick="openAssistantModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 inline-flex items-center gap-2">
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
        }
    }

    const permissionLabels = {
        'manage_appointments': '{{ __("Appointments") }}',
        'manage_queue': '{{ __("Queue") }}',
        'manage_staff': '{{ __("Staff") }}',
        'manage_customers': '{{ __("Customers") }}',
        'view_reports': '{{ __("Reports") }}',
        'manage_settings': '{{ __("Settings") }}',
        'manage_assistants': '{{ __("Assistants") }}',
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
        tag.className = 'inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full';
        tag.innerHTML = `
            ${name}
            <button type="button" onclick="removePermission('${key}')" class="text-blue-500 hover:text-blue-700 focus:outline-none">
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
            btn.querySelector('span').textContent = '{{ __("All permissions selected") }}';
        } else if (selectedPermissions.length > 0) {
            btn.querySelector('span').textContent = '{{ __("Add more permissions...") }}';
        } else {
            btn.querySelector('span').textContent = '{{ __("Select permissions...") }}';
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
        document.getElementById('modalTitle').textContent = '{{ __("Add New Assistant") }}';
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
                document.getElementById('modalTitle').textContent = '{{ __("Edit Assistant") }}';
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
            alert('{{ __("An error occurred") }}');
        }
    }

    async function deleteAssistant(id) {
        if (!confirm('{{ __("Are you sure you want to delete this assistant?") }}')) return;

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
            alert('{{ __("An error occurred") }}');
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
                alert(data.message || '{{ __("An error occurred") }}');
            }
        } catch (error) {
            console.error('Error saving assistant:', error);
            alert('{{ __("An error occurred") }}');
        }
    });

    document.getElementById('assistantModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeAssistantModal();
        }
    });
</script>
@endpush
