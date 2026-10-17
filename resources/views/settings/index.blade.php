@extends('layouts.app')

@section('title', 'System Settings')
@section('page_title', 'Enterprise System Configuration')

@section('content')
<div style="max-width: 840px; margin: 0 auto;">
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h2 class="mui-card-title">Global Platform Configuration</h2>
                <div class="mui-card-subtitle">Manage company identity, file upload limits, and security defaults</div>
            </div>
            <span class="mui-chip badge-super-admin">Super Admin Privilege</span>
        </div>

        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <!-- Brand & Platform Identity -->
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-primary); padding-bottom: 4px;">
                    1. Brand & Organization Identity
                </div>
                <div class="responsive-form-grid">
                    <div class="mui-textfield">
                        <label class="mui-label" for="appName">Platform Application Name *</label>
                        <input type="text" name="app_name" id="appName" class="mui-input" value="{{ $settings['app_name'] ?? 'Rapid Track' }}" required>
                        <div class="mui-helper-text">Displayed on navigation drawer and browser tab titles.</div>
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="companyName">Enterprise Organization *</label>
                        <input type="text" name="company_name" id="companyName" class="mui-input" value="{{ $settings['company_name'] ?? 'Apex Global Enterprise' }}" required>
                        <div class="mui-helper-text">Appears on reports, notifications, and export headers.</div>
                    </div>
                </div>

                <hr style="height: 1px; background: var(--mui-outline); border: none; margin: 8px 0;">

                <!-- File Attachments & Storage -->
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-primary); padding-bottom: 4px;">
                    2. Attachments & Asset Management
                </div>
                <div class="responsive-form-grid">
                    <div class="mui-textfield">
                        <label class="mui-label" for="maxFileSize">Max Attachment Size (MB) *</label>
                        <input type="number" name="max_file_size_mb" id="maxFileSize" class="mui-input" min="1" max="50" value="{{ $settings['max_file_size_mb'] ?? '10' }}" required>
                        <div class="mui-helper-text">Recommended default: 10 MB per file. Max allowed: 50 MB.</div>
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="allowUploads">Allow File Uploads</label>
                        <select name="allow_file_uploads" id="allowUploads" class="mui-select">
                            <option value="true" {{ ($settings['allow_file_uploads'] ?? 'true') === 'true' ? 'selected' : '' }}>Enabled (Recommended)</option>
                            <option value="false" {{ ($settings['allow_file_uploads'] ?? 'true') === 'false' ? 'selected' : '' }}>Disabled (Disallow uploads)</option>
                        </select>
                        <div class="mui-helper-text">Control whether specialists can attach files to deliverables.</div>
                    </div>
                </div>

                <hr style="height: 1px; background: var(--mui-outline); border: none; margin: 8px 0;">

                <!-- Access & Notification Defaults -->
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-primary); padding-bottom: 4px;">
                    3. User Defaults & Alerts
                </div>
                <div class="responsive-form-grid">
                    <div class="mui-textfield">
                        <label class="mui-label" for="defaultRole">Default New User Privilege</label>
                        <select name="default_user_role" id="defaultRole" class="mui-select">
                            <option value="staff" {{ ($settings['default_user_role'] ?? 'staff') === 'staff' ? 'selected' : '' }}>Staff Member (Deliverable Specialist)</option>
                            <option value="admin" {{ ($settings['default_user_role'] ?? 'staff') === 'admin' ? 'selected' : '' }}>Section Admin (Section Lead)</option>
                        </select>
                        <div class="mui-helper-text">Initial role pre-selected when creating new user accounts.</div>
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="taskNotifications">Task Notifications</label>
                        <select name="task_notifications_enabled" id="taskNotifications" class="mui-select">
                            <option value="true" {{ ($settings['task_notifications_enabled'] ?? 'true') === 'true' ? 'selected' : '' }}>Active (Broadcast alerts enabled)</option>
                            <option value="false" {{ ($settings['task_notifications_enabled'] ?? 'true') === 'false' ? 'selected' : '' }}>Muted (Silent mode)</option>
                        </select>
                        <div class="mui-helper-text">Toggle in-app notification toasts and activity logs.</div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; padding-top: 18px; border-top: 1px solid var(--mui-outline); margin-top: 8px;">
                    <button type="submit" class="mui-btn mui-btn-gradient">
                        <span class="material-symbols-outlined">save</span>
                        <span>Save Configuration</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
