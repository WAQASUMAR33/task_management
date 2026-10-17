@extends('layouts.app')

@section('title', 'My Profile')
@section('page_title', 'Account Settings & Profile')

@section('content')
<div style="max-width: 840px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">
    <!-- Profile Info Card -->
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h2 class="mui-card-title">Profile Identity</h2>
                <div class="mui-card-subtitle">Manage your personal details and custom avatar accent</div>
            </div>
            <span class="mui-chip {{ $user->role_badge_class }}">{{ $user->role_title }}</span>
        </div>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Avatar Customization -->
            <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 24px; padding: 18px 20px; background: var(--mui-surface-dim); border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-lg);">
                <div id="avatarPreview" class="mui-avatar mui-avatar-lg" style="background-color: {{ $user->avatar_color }};">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div style="font-size: 0.875rem; font-weight: 700; color: var(--mui-on-surface);">Custom Avatar Appearance</div>
                    <div style="font-size: 0.75rem; color: var(--mui-on-surface-variant); margin: 2px 0 8px 0;">Select a color signature to stand out in task assignments and team comments.</div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" name="avatar_color" id="avatarColor" value="{{ $user->avatar_color }}" style="height: 38px; width: 80px; border: 1.5px solid var(--mui-outline); border-radius: var(--mui-radius-md); cursor: pointer; padding: 2px;" onchange="document.getElementById('avatarPreview').style.backgroundColor = this.value">
                        <span style="font-size: 0.75rem; color: var(--mui-on-surface-muted);">Click to change</span>
                    </div>
                </div>
            </div>

            <div class="responsive-form-grid">
                <div class="mui-textfield">
                    <label class="mui-label" for="profileName">Full Display Name *</label>
                    <input type="text" name="name" id="profileName" class="mui-input" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="profileEmail">Work Email Address (Managed)</label>
                    <input type="email" id="profileEmail" class="mui-input" value="{{ $user->email }}" disabled style="background: var(--mui-surface-dim); cursor: not-allowed; color: var(--mui-on-surface-variant);">
                </div>
            </div>

            <div class="responsive-form-grid">
                <div class="mui-textfield">
                    <label class="mui-label">Department</label>
                    <input type="text" class="mui-input" value="{{ $user->department ?: 'General Department' }}" disabled style="background: var(--mui-surface-dim); cursor: not-allowed; color: var(--mui-on-surface-variant);">
                </div>

                <div class="mui-textfield">
                    <label class="mui-label">Assigned Unit Section</label>
                    <input type="text" class="mui-input" value="{{ $user->section ? $user->section->name : 'Unit Headquarters' }}" disabled style="background: var(--mui-surface-dim); cursor: not-allowed; color: var(--mui-on-surface-variant);">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; padding-top: 16px; border-top: 1px solid var(--mui-outline);">
                <button type="submit" class="mui-btn mui-btn-gradient">
                    <span class="material-symbols-outlined">save</span>
                    <span>Save Profile</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password Card -->
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h2 class="mui-card-title">Security & Password</h2>
                <div class="mui-card-subtitle">Ensure your workstation account is secured with a strong password</div>
            </div>
            <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-md); background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                <span class="material-symbols-outlined">lock</span>
            </div>
        </div>

        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mui-textfield">
                <label class="mui-label" for="currentPassword">Current Account Password *</label>
                <input type="password" name="current_password" id="currentPassword" class="mui-input" required placeholder="••••••••">
            </div>

            <div class="responsive-form-grid">
                <div class="mui-textfield">
                    <label class="mui-label" for="password">New Password (Min 8 chars) *</label>
                    <input type="password" name="password" id="password" class="mui-input" required placeholder="••••••••">
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="password_confirmation">Confirm New Password *</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="mui-input" required placeholder="••••••••">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; padding-top: 16px; border-top: 1px solid var(--mui-outline);">
                <button type="submit" class="mui-btn mui-btn-filled">
                    <span class="material-symbols-outlined">key</span>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
