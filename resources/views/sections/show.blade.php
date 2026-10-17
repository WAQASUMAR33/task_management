@extends('layouts.app')

@section('title', $section->name)
@section('page_title', 'Section: ' . $section->name)

@section('content')
<!-- Header Row -->
<div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
            <a href="{{ route('sections.index') }}" class="mui-btn mui-btn-outlined mui-btn-sm" style="border-radius: var(--mui-radius-full); height: 28px; padding: 0 10px; font-size: 0.75rem;">
                <span class="material-symbols-outlined" style="font-size: 14px;">arrow_back</span>
                <span>All Sections</span>
            </a>
            <span class="mui-chip" style="font-size: 0.72rem; font-weight: 700; background: var(--mui-primary-light); color: var(--mui-primary); border-color: rgba(79, 70, 229, 0.2);">
                {{ $section->code }}
            </span>
            <span class="mui-chip {{ $section->status === 'active' ? 'chip-status-completed' : 'chip-status-todo' }}" style="font-size: 0.72rem;">
                {{ ucfirst($section->status) }}
            </span>
        </div>
        <h2 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.025em; color: var(--mui-on-surface); margin: 0;">
            {{ $section->name }}
        </h2>
        <p style="color: var(--mui-on-surface-variant); font-size: 0.875rem; margin: 4px 0 0 0;">
            Part of {{ $section->unit->name ?? 'Primary Operational Unit' }}
        </p>
    </div>

    <div style="display: flex; gap: 10px;">
        @if(Auth::user()->isAdmin())
        <a href="{{ route('users.index', ['section_id' => $section->id]) }}" class="mui-btn mui-btn-tonal mui-btn-sm">
            <span class="material-symbols-outlined" style="font-size: 16px;">group</span>
            <span>Manage Team</span>
        </a>
        @endif
        <a href="{{ route('tasks.index', ['section_id' => $section->id]) }}" class="mui-btn mui-btn-gradient mui-btn-sm">
            <span class="material-symbols-outlined" style="font-size: 16px;">checklist</span>
            <span>View Tasks ({{ $section->tasks->count() }})</span>
        </a>
    </div>
</div>

<div class="deliverable-layout-grid" style="align-items: start;">
    <!-- Main Left Column: Team & Deliverables -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Section Team Members -->
        <div class="mui-card">
            <div class="mui-card-header">
                <div>
                    <h3 class="mui-card-title">Section Team Members ({{ $section->members->count() }})</h3>
                    <div class="mui-card-subtitle">Active specialists assigned to this section</div>
                </div>
                @if(Auth::user()->isAdmin())
                <a href="{{ route('users.index') }}" class="mui-btn mui-btn-tonal mui-btn-sm">
                    <span class="material-symbols-outlined" style="font-size: 16px;">person_add</span>
                    <span>Add Member</span>
                </a>
                @endif
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px;">
                @forelse($section->members as $member)
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md); background: var(--mui-surface-dim); transition: all var(--mui-transition-fast);" onmouseover="this.style.borderColor='var(--mui-primary)';" onmouseout="this.style.borderColor='var(--mui-outline)';">
                    <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $member->avatar_color }};">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 0.875rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--mui-on-surface);">
                            {{ $member->name }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--mui-on-surface-variant); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $member->email }}
                        </div>
                        <div style="margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            <span class="mui-chip {{ $member->role_badge_class }}" style="font-size: 0.65rem; padding: 1px 6px;">
                                {{ $member->role_title }}
                            </span>
                            <span style="font-size: 0.72rem; color: var(--mui-on-surface-variant); font-weight: 500;">
                                {{ $member->assigned_tasks_count ?? 0 }} Tasks
                            </span>
                        </div>
                    </div>
                </div>
                @empty
                <div style="grid-column: 1 / -1; padding: 32px; text-align: center; color: var(--mui-on-surface-variant); font-size: 0.875rem;">
                    No team members currently assigned to this section.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Section Tasks -->
        <div class="mui-card">
            <div class="mui-card-header">
                <div>
                    <h3 class="mui-card-title">Section Deliverables</h3>
                    <div class="mui-card-subtitle">Active and completed tasks in this unit</div>
                </div>
                <a href="{{ route('tasks.index', ['section_id' => $section->id]) }}" class="mui-btn mui-btn-text mui-btn-sm">
                    <span>View All</span>
                    <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                </a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                @forelse($section->tasks as $tsk)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md); background: #ffffff; transition: all var(--mui-transition-fast);" onmouseover="this.style.borderColor='var(--mui-primary)';" onmouseout="this.style.borderColor='var(--mui-outline)';">
                    <div style="min-width: 0; flex: 1;">
                        <a href="{{ route('tasks.show', $tsk) }}" style="text-decoration: none; font-size: 0.9rem; font-weight: 700; color: var(--mui-on-surface); transition: color 0.15s ease;" onmouseover="this.style.color='var(--mui-primary)'" onmouseout="this.style.color='var(--mui-on-surface)'">
                            {{ $tsk->title }}
                        </a>
                        <div style="font-size: 0.75rem; color: var(--mui-on-surface-variant); margin-top: 4px; display: flex; align-items: center; gap: 12px;">
                            <span>Assigned: <strong>{{ $tsk->assignee->name ?? 'Unassigned' }}</strong></span>
                            @if($tsk->due_date)
                            <span style="color: {{ $tsk->isOverdue() ? 'var(--mui-error)' : 'inherit' }}; font-weight: {{ $tsk->isOverdue() ? '700' : '500' }};">
                                Due: {{ $tsk->due_date->format('M d, Y') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="mui-chip {{ $tsk->priority_badge_class }}" style="font-size: 0.72rem;">
                            {{ ucfirst($tsk->priority) }}
                        </span>
                        <span class="mui-chip {{ $tsk->status_badge_class }}" style="font-size: 0.72rem;">
                            {{ $tsk->status_label }}
                        </span>
                    </div>
                </div>
                @empty
                <div style="padding: 32px; text-align: center; color: var(--mui-on-surface-variant); font-size: 0.875rem;">
                    No tasks assigned to this section yet.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Sidebar Column: Leadership & Section Details -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Leadership Card -->
        <div class="mui-card" style="border-top: 4px solid var(--mui-primary);">
            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-on-surface-variant); margin-bottom: 12px;">
                Section Leadership
            </div>
            @if($section->admin)
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <div class="mui-avatar mui-avatar-lg" style="background-color: {{ $section->admin->avatar_color }};">
                    {{ strtoupper(substr($section->admin->name, 0, 1)) }}
                </div>
                <div style="min-width: 0;">
                    <div style="font-size: 1rem; font-weight: 700; color: var(--mui-on-surface);">{{ $section->admin->name }}</div>
                    <div style="font-size: 0.8rem; color: var(--mui-on-surface-variant);">{{ $section->admin->email }}</div>
                    <div style="margin-top: 4px;">
                        <span class="mui-chip badge-admin" style="font-size: 0.68rem; padding: 1px 8px;">Section Admin</span>
                    </div>
                </div>
            </div>
            @else
            <div style="padding: 14px 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--mui-radius-md); color: #92400e; font-size: 0.8125rem; margin-bottom: 16px;">
                No Section Admin is assigned to lead this section. Super Admin can assign a Section Admin from the sections overview.
            </div>
            @endif

            <hr style="height: 1px; background: var(--mui-outline); border: none; margin-bottom: 14px;">

            <div style="font-size: 0.85rem; line-height: 1.6; color: var(--mui-on-surface-variant);">
                <div style="margin-bottom: 8px;">
                    <strong style="color: var(--mui-on-surface);">Section Code:</strong> {{ $section->code }}
                </div>
                <div style="margin-bottom: 8px;">
                    <strong style="color: var(--mui-on-surface);">Primary Unit:</strong> {{ $section->unit->name ?? 'Main Unit' }}
                </div>
                <div>
                    <strong style="color: var(--mui-on-surface);">Description:</strong><br>
                    {{ $section->description ?: 'No operational description available.' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
