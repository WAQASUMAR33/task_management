@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', Auth::user()->isSuperAdmin() ? 'Unit Command Overview' : (Auth::user()->isSectionAdmin() ? 'Section Dashboard: ' . ($sectionSummary->name ?? '') : 'My Workstation Dashboard'))

@section('content')

@if(Auth::user()->isSectionAdmin() && $sectionSummary)
<!-- Section Admin Banner -->
<div class="mui-card" style="margin-bottom: 28px; padding: 24px 28px; border-left: 5px solid var(--mui-primary); background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <span class="mui-chip" style="font-size: 0.75rem; font-weight: 700; background: var(--mui-primary-light); color: var(--mui-primary); border-color: rgba(79, 70, 229, 0.2);">
                    {{ $sectionSummary->code }}
                </span>
                <span style="font-size: 0.8125rem; color: var(--mui-on-surface-variant); font-weight: 500;">
                    {{ $sectionSummary->unit->name ?? 'Operational Unit' }}
                </span>
            </div>
            <h2 style="font-size: 1.45rem; font-weight: 800; letter-spacing: -0.02em; color: var(--mui-on-surface); margin: 0;">
                {{ $sectionSummary->name }}
            </h2>
            <p style="font-size: 0.875rem; color: var(--mui-on-surface-variant); margin: 6px 0 0 0;">
                {{ $sectionSummary->description ?: 'Section operational command & team management.' }}
            </p>
        </div>

        <div style="display: flex; gap: 12px;">
            <a href="{{ route('tasks.index') }}" class="mui-btn mui-btn-filled mui-btn-sm">
                <span class="material-symbols-outlined">checklist</span>
                <span>Section Tasks</span>
            </a>
            <a href="{{ route('users.index') }}" class="mui-btn mui-btn-tonal mui-btn-sm">
                <span class="material-symbols-outlined">group</span>
                <span>My Team ({{ $staffWorkloads->count() }})</span>
            </a>
        </div>
    </div>
</div>
@endif

<!-- High-Standard KPI Metric Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 28px;">
    <!-- Metric: Total Tasks -->
    <div class="mui-stat-card">
        <div class="mui-stat-icon-wrapper" style="background: var(--mui-primary-light); color: var(--mui-primary);">
            <span class="material-symbols-outlined" style="font-size: 28px;">assignment</span>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-size: 0.8125rem; color: var(--mui-on-surface-variant); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                {{ Auth::user()->isStaff() ? 'My Total Tasks' : (Auth::user()->isSectionAdmin() ? 'Section Tasks' : 'All Unit Tasks') }}
            </div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--mui-on-surface); line-height: 1.1; margin-top: 4px; letter-spacing: -0.02em;">
                {{ $totalTasks }}
            </div>
            <div style="font-size: 0.75rem; color: var(--mui-primary); font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                <span class="material-symbols-outlined" style="font-size: 14px;">track_changes</span>
                <span>Active deliverables</span>
            </div>
        </div>
    </div>

    <!-- Metric: In Progress -->
    <div class="mui-stat-card">
        <div class="mui-stat-icon-wrapper" style="background: var(--mui-info-light); color: var(--mui-info-text);">
            <span class="material-symbols-outlined" style="font-size: 28px;">pending_actions</span>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-size: 0.8125rem; color: var(--mui-on-surface-variant); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">In Progress</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--mui-on-surface); line-height: 1.1; margin-top: 4px; letter-spacing: -0.02em;">
                {{ $inProgressTasks }}
            </div>
            <div style="font-size: 0.75rem; color: var(--mui-info-text); font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                <span class="material-symbols-outlined" style="font-size: 14px;">sync</span>
                <span>Under execution</span>
            </div>
        </div>
    </div>

    <!-- Metric: Completed -->
    <div class="mui-stat-card">
        <div class="mui-stat-icon-wrapper" style="background: var(--mui-success-light); color: var(--mui-success);">
            <span class="material-symbols-outlined" style="font-size: 28px;">check_circle</span>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-size: 0.8125rem; color: var(--mui-on-surface-variant); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Completed</div>
            <div style="font-size: 2rem; font-weight: 800; color: var(--mui-on-surface); line-height: 1.1; margin-top: 4px; letter-spacing: -0.02em;">
                {{ $completedTasks }}
            </div>
            <div style="font-size: 0.75rem; color: var(--mui-success); font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                <span class="material-symbols-outlined" style="font-size: 14px;">done_all</span>
                <span>Successfully closed</span>
            </div>
        </div>
    </div>

    <!-- Metric: Overdue -->
    <div class="mui-stat-card">
        <div class="mui-stat-icon-wrapper" style="background: var(--mui-error-light); color: var(--mui-error);">
            <span class="material-symbols-outlined" style="font-size: 28px;">warning</span>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-size: 0.8125rem; color: var(--mui-on-surface-variant); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">Overdue Tasks</div>
            <div style="font-size: 2rem; font-weight: 800; color: {{ $overdueTasks > 0 ? 'var(--mui-error)' : 'var(--mui-on-surface)' }}; line-height: 1.1; margin-top: 4px; letter-spacing: -0.02em;">
                {{ $overdueTasks }}
            </div>
            <div style="font-size: 0.75rem; color: {{ $overdueTasks > 0 ? 'var(--mui-error)' : 'var(--mui-on-surface-variant)' }}; font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                <span class="material-symbols-outlined" style="font-size: 14px;">schedule</span>
                <span>{{ $overdueTasks > 0 ? 'Immediate action required' : 'All deadlines on track' }}</span>
            </div>
        </div>
    </div>
</div>

@if(Auth::user()->isSuperAdmin() && $allSections->isNotEmpty())
<!-- Super Admin: Sections Performance Matrix -->
<div class="mui-card" style="margin-bottom: 28px;">
    <div class="mui-card-header">
        <div>
            <h3 class="mui-card-title">Unit Sections Overview</h3>
            <div class="mui-card-subtitle">Operational status & workload across all active unit sections</div>
        </div>
        <a href="{{ route('sections.index') }}" class="mui-btn mui-btn-tonal mui-btn-sm">
            <span>Manage All Sections</span>
            <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; margin-top: 10px;">
        @foreach($allSections as $sec)
        @php
            $pct = $sec->tasks_count > 0 ? round(($sec->completed_tasks / $sec->tasks_count) * 100) : 0;
        @endphp
        <div style="padding: 18px; border-radius: var(--mui-radius-md); border: 1px solid var(--mui-outline); background: var(--mui-surface-dim); display: flex; flex-direction: column; justify-content: space-between; transition: all var(--mui-transition-base);" onmouseover="this.style.borderColor='var(--mui-primary)'; this.style.backgroundColor='#ffffff';" onmouseout="this.style.borderColor='var(--mui-outline)'; this.style.backgroundColor='var(--mui-surface-dim)';">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <span class="mui-chip" style="font-size: 0.72rem; font-weight: 700; background: var(--mui-primary-light); color: var(--mui-primary); border-color: rgba(79, 70, 229, 0.2);">
                        {{ $sec->code }}
                    </span>
                    <span style="font-size: 0.75rem; color: var(--mui-on-surface-variant); display: flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-outlined" style="font-size: 15px; color: var(--mui-on-surface-variant);">shield_person</span>
                        Lead: <strong>{{ $sec->admin->name ?? 'Unassigned' }}</strong>
                    </span>
                </div>
                <h4 style="margin: 0 0 6px 0; font-size: 1.05rem; font-weight: 700; color: var(--mui-on-surface);">
                    {{ $sec->name }}
                </h4>
                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8125rem; color: var(--mui-on-surface-variant); margin-bottom: 8px;">
                    <span>{{ $sec->total_members }} Team Members</span>
                    <span style="font-weight: 600; color: var(--mui-on-surface);">{{ $sec->completed_tasks }} / {{ $sec->tasks_count }} Tasks ({{ $pct }}%)</span>
                </div>
                <div style="height: 8px; background: var(--mui-outline-strong); border-radius: var(--mui-radius-full); overflow: hidden; margin-bottom: 14px;">
                    <div style="height: 100%; width: {{ $pct }}%; background: linear-gradient(90deg, var(--mui-primary), #10b981); border-radius: var(--mui-radius-full); transition: width 0.6s ease;"></div>
                </div>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end; padding-top: 10px; border-top: 1px solid var(--mui-outline);">
                <a href="{{ route('sections.show', $sec) }}" class="mui-btn mui-btn-tonal mui-btn-sm" style="height: 30px; font-size: 0.78rem;">
                    <span>Details</span>
                </a>
                <a href="{{ route('tasks.index', ['section_id' => $sec->id]) }}" class="mui-btn mui-btn-text mui-btn-sm" style="height: 30px; font-size: 0.78rem;">
                    <span>View Tasks</span>
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Analytics & Quick Action Controls -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 20px; margin-bottom: 28px;">
    <!-- Completion Rate & Velocity -->
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h3 class="mui-card-title">Completion Rate</h3>
                <div class="mui-card-subtitle">Overall progress across active deliverables</div>
            </div>
            <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-md); background: var(--mui-primary-light); color: var(--mui-primary); display: flex; align-items: center; justify-content: center;">
                <span class="material-symbols-outlined">trending_up</span>
            </div>
        </div>
        <div style="margin: 16px 0;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                <span style="font-size: 2.5rem; font-weight: 800; color: var(--mui-primary); letter-spacing: -0.03em;">{{ $completionRate }}%</span>
                <span class="mui-chip chip-status-completed" style="font-size: 0.78rem;">
                    {{ $completedTasks }} of {{ $totalTasks }} finished
                </span>
            </div>
            <!-- Animated Progress Bar -->
            <div style="height: 10px; background: var(--mui-surface-container); border-radius: var(--mui-radius-full); overflow: hidden; border: 1px solid var(--mui-outline);">
                <div style="height: 100%; width: {{ $completionRate }}%; background: linear-gradient(90deg, #4f46e5 0%, #10b981 100%); border-radius: var(--mui-radius-full); transition: width 0.8s ease;"></div>
            </div>
        </div>
        <p style="font-size: 0.8125rem; color: var(--mui-on-surface-variant); margin-top: 10px;">
            Based on current task assignments and milestone completions.
        </p>
    </div>

    <!-- Priority Distribution -->
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h3 class="mui-card-title">Priority Breakdown</h3>
                <div class="mui-card-subtitle">Deliverables distributed by urgency</div>
            </div>
            <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-md); background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center;">
                <span class="material-symbols-outlined">pie_chart</span>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 16px;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span class="mui-chip chip-priority-high" style="font-size: 0.75rem;">High Priority</span>
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--mui-on-surface);">{{ $highPriority }}</span>
                </div>
                <div style="height: 6px; background: var(--mui-surface-container); border-radius: var(--mui-radius-full); overflow: hidden;">
                    <div style="height: 100%; width: {{ $totalTasks > 0 ? ($highPriority / $totalTasks) * 100 : 0 }}%; background: #ef4444; border-radius: var(--mui-radius-full);"></div>
                </div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span class="mui-chip chip-priority-medium" style="font-size: 0.75rem;">Medium Priority</span>
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--mui-on-surface);">{{ $mediumPriority }}</span>
                </div>
                <div style="height: 6px; background: var(--mui-surface-container); border-radius: var(--mui-radius-full); overflow: hidden;">
                    <div style="height: 100%; width: {{ $totalTasks > 0 ? ($mediumPriority / $totalTasks) * 100 : 0 }}%; background: #f59e0b; border-radius: var(--mui-radius-full);"></div>
                </div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span class="mui-chip chip-priority-low" style="font-size: 0.75rem;">Low Priority</span>
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--mui-on-surface);">{{ $lowPriority }}</span>
                </div>
                <div style="height: 6px; background: var(--mui-surface-container); border-radius: var(--mui-radius-full); overflow: hidden;">
                    <div style="height: 100%; width: {{ $totalTasks > 0 ? ($lowPriority / $totalTasks) * 100 : 0 }}%; background: #94a3b8; border-radius: var(--mui-radius-full);"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Operations Navigation -->
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h3 class="mui-card-title">Quick Actions</h3>
                <div class="mui-card-subtitle">Frequent operational shortcuts</div>
            </div>
            <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-md); background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center;">
                <span class="material-symbols-outlined">bolt</span>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 14px;">
            <a href="{{ route('tasks.index') }}" class="mui-btn mui-btn-tonal" style="width: 100%; justify-content: flex-start; height: 42px; border-radius: var(--mui-radius-md);">
                <span class="material-symbols-outlined" style="color: var(--mui-primary);">checklist</span>
                <span>Browse Task Board</span>
            </a>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('tasks.index', ['create' => '1']) }}" class="mui-btn mui-btn-gradient" style="width: 100%; justify-content: flex-start; height: 42px; border-radius: var(--mui-radius-md);">
                <span class="material-symbols-outlined">add_task</span>
                <span>Create New Task</span>
            </a>
            <a href="{{ route('users.index') }}" class="mui-btn mui-btn-outlined" style="width: 100%; justify-content: flex-start; height: 42px; border-radius: var(--mui-radius-md);">
                <span class="material-symbols-outlined" style="color: var(--mui-on-surface-variant);">group</span>
                <span>{{ Auth::user()->isSuperAdmin() ? 'User Directory' : 'Section Team Roster' }}</span>
            </a>
            @endif
            <a href="{{ route('profile.show') }}" class="mui-btn mui-btn-text" style="width: 100%; justify-content: flex-start; height: 38px;">
                <span class="material-symbols-outlined">person</span>
                <span>My Profile & Preferences</span>
            </a>
        </div>
    </div>
</div>

<!-- Split: Recent Tasks & Real-time Activity Trail -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px; margin-bottom: 28px;">
    <!-- Recent Tasks List -->
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h3 class="mui-card-title">{{ Auth::user()->isStaff() ? 'My Active Workload' : 'Recent Tasks' }}</h3>
                <div class="mui-card-subtitle">Recent deliverables and current progress</div>
            </div>
            <a href="{{ route('tasks.index') }}" class="mui-btn mui-btn-text mui-btn-sm">
                <span>View Board</span>
                <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
            </a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
            @forelse($recentTasks as $task)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: var(--mui-radius-md); border: 1px solid var(--mui-outline); background: var(--mui-surface); transition: all var(--mui-transition-fast); cursor: pointer;" onclick="window.location='{{ route('tasks.show', $task) }}'" onmouseover="this.style.borderColor='var(--mui-primary)'; this.style.backgroundColor='var(--mui-surface-dim)';" onmouseout="this.style.borderColor='var(--mui-outline)'; this.style.backgroundColor='var(--mui-surface)';">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                    <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-sm); display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: {{ $task->status === 'completed' ? '#ecfdf5' : ($task->status === 'in_progress' ? '#eff6ff' : '#f1f5f9') }}; color: {{ $task->status === 'completed' ? '#065f46' : ($task->status === 'in_progress' ? '#1d4ed8' : '#475569') }};">
                        <span class="material-symbols-outlined" style="font-size: 18px;">
                            {{ $task->status === 'completed' ? 'check_circle' : ($task->status === 'in_progress' ? 'pending' : 'radio_button_unchecked') }}
                        </span>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="font-size: 0.9rem; font-weight: 700; color: var(--mui-on-surface); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $task->title }}</div>
                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 3px; flex-wrap: wrap;">
                            @if($task->section)
                            <span class="mui-chip" style="font-size: 0.68rem; padding: 1px 7px; background: var(--mui-primary-light); color: var(--mui-primary);">{{ $task->section->code }}</span>
                            @endif
                            <span class="mui-chip {{ $task->status_badge_class }}" style="font-size: 0.68rem; padding: 1px 7px;">{{ $task->status_label }}</span>
                            <span class="mui-chip {{ $task->priority_badge_class }}" style="font-size: 0.68rem; padding: 1px 7px;">{{ ucfirst($task->priority) }}</span>
                            @if($task->due_date)
                            <span style="font-size: 0.72rem; color: {{ $task->isOverdue() ? 'var(--mui-error)' : 'var(--mui-on-surface-variant)' }}; font-weight: 500;">
                                • Due {{ $task->due_date->format('M d') }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--mui-on-surface-variant); font-size: 20px;">chevron_right</span>
                </div>
            </div>
            @empty
            <div style="text-align: center; color: var(--mui-on-surface-variant); padding: 32px; font-size: 0.875rem;">
                No active tasks found.
            </div>
            @endforelse
        </div>
    </div>

    <!-- Live Activity Trail -->
    <div class="mui-card">
        <div class="mui-card-header">
            <div>
                <h3 class="mui-card-title">Live Activity Trail</h3>
                <div class="mui-card-subtitle">Real-time action history across the workspace</div>
            </div>
            <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-md); background: var(--mui-surface-container); color: var(--mui-on-surface-variant); display: flex; align-items: center; justify-content: center;">
                <span class="material-symbols-outlined">history</span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
            @forelse($recentLogs as $log)
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--mui-outline-variant);">
                <div style="width: 32px; height: 32px; border-radius: var(--mui-radius-full); background: var(--mui-primary-light); color: var(--mui-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">notifications</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.875rem; color: var(--mui-on-surface);">
                        <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                        <span style="color: var(--mui-on-surface-variant);">{{ $log->details }}</span>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--mui-on-surface-muted); margin-top: 3px;">{{ $log->created_at->diffForHumans() }}</div>
                </div>
            </div>
            @empty
            <div style="text-align: center; color: var(--mui-on-surface-variant); padding: 32px; font-size: 0.875rem;">
                No activity logged yet.
            </div>
            @endforelse
        </div>
    </div>
</div>

@if(Auth::user()->isAdmin() && $staffWorkloads->isNotEmpty())
<!-- Team Workload Allocation Matrix -->
<div class="mui-card">
    <div class="mui-card-header">
        <div>
            <h3 class="mui-card-title">{{ Auth::user()->isSuperAdmin() ? 'Global Team Workload Allocation' : 'Section Team Workload Allocation' }}</h3>
            <div class="mui-card-subtitle">Active member capacity and completion velocity</div>
        </div>
        <span class="material-symbols-outlined" style="color: var(--mui-primary);">badge</span>
    </div>

    <div class="mui-table-wrapper" style="margin-top: 14px;">
        <table class="mui-table">
            <thead>
                <tr>
                    <th>Team Member</th>
                    @if(Auth::user()->isSuperAdmin())
                    <th>Section</th>
                    @endif
                    <th>Total</th>
                    <th>In Progress</th>
                    <th>Completed</th>
                    <th>Overdue</th>
                    <th style="min-width: 140px;">Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffWorkloads as $wl)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $wl['user']->avatar_color }};">
                                {{ strtoupper(substr($wl['user']->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 0.875rem; color: var(--mui-on-surface);">{{ $wl['user']->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--mui-on-surface-variant);">{{ $wl['user']->department }}</div>
                            </div>
                        </div>
                    </td>
                    @if(Auth::user()->isSuperAdmin())
                    <td>
                        <span class="mui-chip" style="font-size: 0.72rem; background: var(--mui-primary-light); color: var(--mui-primary);">
                            {{ $wl['user']->section->name ?? 'Headquarters' }}
                        </span>
                    </td>
                    @endif
                    <td style="font-weight: 700;">{{ $wl['total'] }}</td>
                    <td style="color: #0284c7; font-weight: 700;">{{ $wl['in_progress'] }}</td>
                    <td style="color: #10b981; font-weight: 700;">{{ $wl['completed'] }}</td>
                    <td style="color: {{ $wl['overdue'] > 0 ? '#ef4444' : 'var(--mui-on-surface-variant)' }}; font-weight: 700;">
                        {{ $wl['overdue'] }}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="flex: 1; height: 8px; background: var(--mui-surface-container); border-radius: var(--mui-radius-full); overflow: hidden; border: 1px solid var(--mui-outline);">
                                <div style="height: 100%; width: {{ $wl['completion_pct'] }}%; background: #10b981; border-radius: var(--mui-radius-full);"></div>
                            </div>
                            <span style="font-size: 0.8125rem; font-weight: 700; color: var(--mui-on-surface);">{{ $wl['completion_pct'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
