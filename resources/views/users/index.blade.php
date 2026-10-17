@extends('layouts.app')

@section('title', Auth::user()->isSuperAdmin() ? 'User Directory' : 'Section Team')
@section('page_title', Auth::user()->isSuperAdmin() ? 'User & Access Directory' : 'Section Team: ' . (Auth::user()->section->name ?? 'My Section'))

@section('content')
<!-- Filter & Action Bar -->
<div class="mui-card" style="margin-bottom: 24px; padding: 18px 22px;">
    <form action="{{ route('users.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <!-- Search Field -->
        <div class="mui-textfield" style="flex: 1; min-width: 240px; margin-bottom: 0;">
            <label class="mui-label">Search Specialists</label>
            <div class="mui-input-wrapper">
                <span class="material-symbols-outlined mui-leading-icon">search</span>
                <input type="text" name="search" class="mui-input mui-input-with-icon" placeholder="Search by name, email, department..." value="{{ request('search') }}">
            </div>
        </div>

        @if(Auth::user()->isSuperAdmin() && isset($sections))
        <!-- Section Select -->
        <div class="mui-textfield" style="width: 170px; margin-bottom: 0;">
            <label class="mui-label">Section</label>
            <select name="section_id" class="mui-select" onchange="this.form.submit()">
                <option value="all">All Sections</option>
                @foreach($sections as $sec)
                <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>
                    {{ $sec->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        @if(Auth::user()->isSuperAdmin())
        <!-- Role Select -->
        <div class="mui-textfield" style="width: 160px; margin-bottom: 0;">
            <label class="mui-label">Role</label>
            <select name="role" class="mui-select" onchange="this.form.submit()">
                <option value="all">All Roles</option>
                <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Section Admin</option>
                <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Team Member</option>
            </select>
        </div>
        @endif

        <!-- Status Select -->
        <div class="mui-textfield" style="width: 140px; margin-bottom: 0;">
            <label class="mui-label">Status</label>
            <select name="status" class="mui-select" onchange="this.form.submit()">
                <option value="all">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div style="margin-left: auto;">
            <button type="button" class="mui-btn mui-btn-gradient" onclick="openDialog('createUserDialog')" style="height: 44px;">
                <span class="material-symbols-outlined">person_add</span>
                <span>Add Team Member</span>
            </button>
        </div>
    </form>
</div>

<!-- Users Table Surface -->
<div class="mui-table-wrapper">
    <table class="mui-table">
        <thead>
            <tr>
                <th>Specialist</th>
                <th>Role Privilege</th>
                <th>Unit Section</th>
                <th>Department</th>
                <th>Active Workload</th>
                <th>Account Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $user->avatar_color }};">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.875rem; color: var(--mui-on-surface);">{{ $user->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--mui-on-surface-variant);">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="mui-chip {{ $user->role_badge_class }}">
                        {{ $user->role_title }}
                    </span>
                </td>
                <td>
                    @if($user->section)
                    <span class="mui-chip" style="font-size: 0.72rem; background: var(--mui-primary-light); color: var(--mui-primary); font-weight: 700;">
                        {{ $user->section->name }}
                    </span>
                    @else
                    <span style="font-size: 0.8rem; color: var(--mui-on-surface-muted); font-style: italic;">
                        Unit Headquarters
                    </span>
                    @endif
                </td>
                <td>
                    <span style="font-size: 0.875rem; font-weight: 500;">{{ $user->department ?: '—' }}</span>
                </td>
                <td>
                    <span style="font-size: 0.875rem; font-weight: 700;">{{ $user->total_tasks }} tasks</span>
                    <span style="font-size: 0.75rem; color: var(--mui-on-surface-variant);">({{ $user->completed_tasks }} done)</span>
                </td>
                <td>
                    <form action="{{ route('users.toggle-status', $user) }}" method="POST" style="margin: 0;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="mui-chip {{ $user->status === 'active' ? 'chip-status-completed' : 'chip-status-todo' }}" style="cursor: pointer; border: 1px solid transparent;" title="Click to toggle active status">
                            <span class="material-symbols-outlined" style="font-size: 14px;">
                                {{ $user->status === 'active' ? 'check_circle' : 'cancel' }}
                            </span>
                            {{ ucfirst($user->status) }}
                        </button>
                    </form>
                </td>
                <td style="text-align: right;">
                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                        <button type="button" class="mui-icon-btn mui-icon-btn-sm" title="Edit User" onclick="openEditDialog({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->role }}', '{{ addslashes($user->department) }}', '{{ $user->avatar_color }}', '{{ $user->status }}', '{{ $user->section_id }}')">
                            <span class="material-symbols-outlined" style="font-size: 17px;">edit</span>
                        </button>

                        <button type="button" class="mui-icon-btn mui-icon-btn-sm" title="Reset Password" onclick="openPasswordDialog({{ $user->id }}, '{{ addslashes($user->name) }}')">
                            <span class="material-symbols-outlined" style="font-size: 17px;">key</span>
                        </button>

                        @if(Auth::user()->isSuperAdmin() && $user->id !== Auth::id())
                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Permanently delete account for {{ addslashes($user->name) }}?');" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mui-icon-btn mui-icon-btn-sm" style="color: var(--mui-error);" title="Delete User">
                                <span class="material-symbols-outlined" style="font-size: 17px;">delete</span>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 48px; color: var(--mui-on-surface-variant);">
                    {{ Auth::user()->isSuperAdmin() ? 'No users match the criteria.' : 'No team members found in your section.' }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 24px;">
    {{ $users->links() }}
</div>

<!-- Dialog: Create User -->
<div class="mui-dialog-backdrop" id="createUserDialog">
    <div class="mui-dialog">
        <div class="mui-dialog-header">
            <h2 class="mui-dialog-title">{{ Auth::user()->isSuperAdmin() ? 'Add New User' : 'Add Team Member to ' . (Auth::user()->section->name ?? 'Section') }}</h2>
            <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="closeDialog('createUserDialog')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="mui-dialog-body">
                <div class="mui-textfield">
                    <label class="mui-label" for="userName">Full Name *</label>
                    <input type="text" name="name" id="userName" class="mui-input" placeholder="e.g. Liam Martinez" required>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="userEmail">Email Address *</label>
                    <input type="email" name="email" id="userEmail" class="mui-input" placeholder="liam.m@rapidtrack.com" required>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="userPassword">Initial Password *</label>
                    <input type="password" name="password" id="userPassword" class="mui-input" value="Password123!" required>
                </div>

                @if(Auth::user()->isSuperAdmin())
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="mui-textfield">
                        <label class="mui-label" for="userRole">Role Privilege *</label>
                        <select name="role" id="userRole" class="mui-select" required>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Section Admin</option>
                            <option value="staff" selected>Team Member</option>
                        </select>
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="userSection">Unit Section</label>
                        <select name="section_id" id="userSection" class="mui-select">
                            <option value="">-- No Section (Unit HQ) --</option>
                            @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @else
                <input type="hidden" name="role" value="staff">
                <div style="padding: 10px 14px; background: var(--mui-primary-light); color: var(--mui-primary); border-radius: var(--mui-radius-md); font-size: 0.85rem; margin-bottom: 16px; font-weight: 500;">
                    <strong>Assigned Section:</strong> {{ Auth::user()->section->name ?? 'My Section' }} (Role: Team Member)
                </div>
                @endif

                <div class="mui-textfield">
                    <label class="mui-label" for="userDepartment">Department</label>
                    <input type="text" name="department" id="userDepartment" class="mui-input" placeholder="e.g. Software Systems">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="mui-textfield">
                        <label class="mui-label" for="userColor">Avatar Accent Color</label>
                        <input type="color" name="avatar_color" id="userColor" value="#4f46e5" style="height: 44px; width: 100%; border: 1.5px solid var(--mui-outline); border-radius: var(--mui-radius-md); cursor: pointer; padding: 2px;">
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="userStatus">Initial Status</label>
                        <select name="status" id="userStatus" class="mui-select">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mui-dialog-footer">
                <button type="button" class="mui-btn mui-btn-text" onclick="closeDialog('createUserDialog')">Cancel</button>
                <button type="submit" class="mui-btn mui-btn-gradient">
                    <span class="material-symbols-outlined">person_add</span>
                    <span>Create Account</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Dialog: Edit User -->
<div class="mui-dialog-backdrop" id="editUserDialog">
    <div class="mui-dialog">
        <div class="mui-dialog-header">
            <h2 class="mui-dialog-title">Edit Specialist Account</h2>
            <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="closeDialog('editUserDialog')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="editUserForm" method="POST">
            @csrf
            @method('PUT')
            <div class="mui-dialog-body">
                <div class="mui-textfield">
                    <label class="mui-label" for="editName">Full Name *</label>
                    <input type="text" name="name" id="editName" class="mui-input" required>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="editEmail">Email Address *</label>
                    <input type="email" name="email" id="editEmail" class="mui-input" required>
                </div>

                @if(Auth::user()->isSuperAdmin())
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="mui-textfield">
                        <label class="mui-label" for="editRole">Role Privilege *</label>
                        <select name="role" id="editRole" class="mui-select" required>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Section Admin</option>
                            <option value="staff">Team Member</option>
                        </select>
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="editSection">Unit Section</label>
                        <select name="section_id" id="editSection" class="mui-select">
                            <option value="">-- No Section (Unit HQ) --</option>
                            @foreach($sections as $sec)
                            <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @else
                <input type="hidden" name="role" id="editRole" value="staff">
                @endif

                <div class="mui-textfield">
                    <label class="mui-label" for="editDepartment">Department</label>
                    <input type="text" name="department" id="editDepartment" class="mui-input">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="mui-textfield">
                        <label class="mui-label" for="editColor">Avatar Color</label>
                        <input type="color" name="avatar_color" id="editColor" style="height: 44px; width: 100%; border: 1.5px solid var(--mui-outline); border-radius: var(--mui-radius-md); cursor: pointer; padding: 2px;">
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="editStatus">Status</label>
                        <select name="status" id="editStatus" class="mui-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mui-dialog-footer">
                <button type="button" class="mui-btn mui-btn-text" onclick="closeDialog('editUserDialog')">Cancel</button>
                <button type="submit" class="mui-btn mui-btn-gradient">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Dialog: Reset Password -->
<div class="mui-dialog-backdrop" id="resetPasswordDialog">
    <div class="mui-dialog" style="max-width: 440px;">
        <div class="mui-dialog-header">
            <h2 class="mui-dialog-title">Reset Password</h2>
            <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="closeDialog('resetPasswordDialog')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="resetPasswordForm" method="POST">
            @csrf
            <div class="mui-dialog-body">
                <p id="resetUserPrompt" style="font-size: 0.85rem; color: var(--mui-on-surface-variant); margin-bottom: 16px;"></p>

                <div class="mui-textfield">
                    <label class="mui-label" for="newPassword">New Password (Min 8 chars) *</label>
                    <input type="password" name="password" id="newPassword" class="mui-input" required>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="confirmPassword">Confirm New Password *</label>
                    <input type="password" name="password_confirmation" id="confirmPassword" class="mui-input" required>
                </div>
            </div>

            <div class="mui-dialog-footer">
                <button type="button" class="mui-btn mui-btn-text" onclick="closeDialog('resetPasswordDialog')">Cancel</button>
                <button type="submit" class="mui-btn mui-btn-gradient">Update Password</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openEditDialog(id, name, email, role, department, color, status, sectionId) {
        document.getElementById('editUserForm').action = `/users/${id}`;
        document.getElementById('editName').value = name;
        document.getElementById('editEmail').value = email;
        const roleEl = document.getElementById('editRole');
        if (roleEl) roleEl.value = role;
        const secEl = document.getElementById('editSection');
        if (secEl) secEl.value = sectionId || '';
        document.getElementById('editDepartment').value = department;
        document.getElementById('editColor').value = color;
        document.getElementById('editStatus').value = status;
        openDialog('editUserDialog');
    }

    function openPasswordDialog(id, name) {
        document.getElementById('resetPasswordForm').action = `/users/${id}/reset-password`;
        document.getElementById('resetUserPrompt').innerText = `Setting new password for ${name}.`;
        openDialog('resetPasswordDialog');
    }
</script>
@endpush
@endsection
