@extends('layouts.app')

@section('title', 'Tasks')
@section('page_title', 'Tasks & Deliverables')

@section('content')
<!-- Filter, Search & View Controls Bar -->
<div class="mui-card" style="margin-bottom: 24px; padding: 18px 22px;">
    <form action="{{ route('tasks.index') }}" method="GET" id="taskFilterForm" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <input type="hidden" name="view" id="viewModeInput" value="{{ in_array($viewMode, ['board', 'kanban']) ? 'board' : $viewMode }}">

        <!-- Search Input with icon -->
        <div class="mui-textfield" style="flex: 1; min-width: 240px; margin-bottom: 0;">
            <label class="mui-label">Search Deliverables</label>
            <div class="mui-input-wrapper">
                <span class="material-symbols-outlined mui-leading-icon">search</span>
                <input type="text" name="search" class="mui-input mui-input-with-icon" placeholder="Search by title, description..." value="{{ request('search') }}">
            </div>
        </div>

        <!-- Filter: Status (for Grid & Table view) -->
        @if(!in_array($viewMode, ['board', 'kanban']))
        <div class="mui-textfield" style="width: 150px; margin-bottom: 0;">
            <label class="mui-label">Status</label>
            <select name="status" class="mui-select" onchange="this.form.submit()">
                <option value="all">All Statuses</option>
                <option value="todo" {{ request('status') === 'todo' ? 'selected' : '' }}>To Do</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        @endif

        <!-- Filter: Priority -->
        <div class="mui-textfield" style="width: 150px; margin-bottom: 0;">
            <label class="mui-label">Priority</label>
            <select name="priority" class="mui-select" onchange="this.form.submit()">
                <option value="all">All Priorities</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High Priority</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium Priority</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low Priority</option>
            </select>
        </div>

        @if(Auth::user()->isSuperAdmin() && isset($sections))
        <!-- Filter: Section -->
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

        @if(Auth::user()->isAdmin())
        <!-- Filter: Assignee -->
        <div class="mui-textfield" style="width: 160px; margin-bottom: 0;">
            <label class="mui-label">Assignee</label>
            <select name="assigned_to" class="mui-select" onchange="this.form.submit()">
                <option value="all">All Team</option>
                @foreach($staffMembers as $staff)
                <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected' : '' }}>
                    {{ $staff->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Filter: Overdue Checkbox -->
        <div style="display: flex; align-items: center; height: 44px; padding-bottom: 2px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.8125rem; font-weight: 600; cursor: pointer; user-select: none; color: var(--mui-on-surface);">
                <input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }} onchange="this.form.submit()" style="accent-color: var(--mui-error); width: 16px; height: 16px; cursor: pointer;">
                <span>Overdue Only</span>
            </label>
        </div>

        <!-- View Switcher & Action Buttons -->
        <div style="margin-left: auto; display: flex; align-items: center; gap: 10px; height: 44px;">
            <div style="display: flex; background: var(--mui-surface-container); padding: 3px; border-radius: var(--mui-radius-md); border: 1px solid var(--mui-outline);">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'board']) }}" class="mui-btn mui-btn-sm {{ in_array($viewMode, ['board', 'kanban']) ? 'mui-btn-filled' : 'mui-btn-text' }}" style="height: 30px; padding: 0 10px; border-radius: var(--mui-radius-sm);" title="Kanban Board View">
                    <span class="material-symbols-outlined" style="font-size: 16px;">view_kanban</span>
                    <span>Board</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="mui-btn mui-btn-sm {{ $viewMode === 'grid' ? 'mui-btn-filled' : 'mui-btn-text' }}" style="height: 30px; padding: 0 10px; border-radius: var(--mui-radius-sm);" title="Card Grid View">
                    <span class="material-symbols-outlined" style="font-size: 16px;">grid_view</span>
                    <span>Grid</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="mui-btn mui-btn-sm {{ $viewMode === 'table' ? 'mui-btn-filled' : 'mui-btn-text' }}" style="height: 30px; padding: 0 10px; border-radius: var(--mui-radius-sm);" title="Table List View">
                    <span class="material-symbols-outlined" style="font-size: 16px;">table_rows</span>
                    <span>Table</span>
                </a>
            </div>

            <!-- Export CSV Button -->
            <a href="{{ route('tasks.export', request()->query()) }}" class="mui-btn mui-btn-tonal mui-btn-sm" style="height: 38px;" title="Export filtered deliverables to CSV report">
                <span class="material-symbols-outlined" style="font-size: 18px;">download</span>
                <span>Export CSV</span>
            </a>

            @if(Auth::user()->isAdmin())
            <button type="button" class="mui-btn mui-btn-gradient" onclick="openDialog('createTaskDialog')" style="height: 38px;">
                <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                <span>Create Task</span>
            </button>
            @endif
        </div>
    </form>

    <!-- Quick Filter Chips Row -->
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--mui-outline);">
        <span style="font-size: 0.75rem; font-weight: 700; color: var(--mui-on-surface-variant); text-transform: uppercase; letter-spacing: 0.04em; margin-right: 4px;">Quick Filters:</span>
        
        <a href="{{ route('tasks.index', ['view' => $viewMode]) }}" class="mui-chip" style="text-decoration: none; font-size: 0.78rem; {{ !request('priority') && !request('status') && !request('assigned_to') && !request('overdue') ? 'background: var(--mui-primary); color: #fff; border-color: var(--mui-primary);' : '' }}">
            <span class="material-symbols-outlined" style="font-size: 15px;">apps</span>
            <span>All Tasks</span>
        </a>

        @if(Auth::user()->isAdmin())
        <a href="{{ request()->fullUrlWithQuery(['assigned_to' => Auth::id()]) }}" class="mui-chip" style="text-decoration: none; font-size: 0.78rem; {{ request('assigned_to') == Auth::id() ? 'background: var(--mui-primary); color: #fff; border-color: var(--mui-primary);' : '' }}">
            <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
            <span>My Tasks</span>
        </a>
        @endif

        <a href="{{ request()->fullUrlWithQuery(['priority' => 'high']) }}" class="mui-chip" style="text-decoration: none; font-size: 0.78rem; {{ request('priority') === 'high' ? 'background: var(--mui-error); color: #fff; border-color: var(--mui-error);' : '' }}">
            <span class="material-symbols-outlined" style="font-size: 15px;">priority_high</span>
            <span>High Priority</span>
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status' => 'in_progress']) }}" class="mui-chip" style="text-decoration: none; font-size: 0.78rem; {{ request('status') === 'in_progress' ? 'background: var(--mui-primary); color: #fff; border-color: var(--mui-primary);' : '' }}">
            <span class="material-symbols-outlined" style="font-size: 15px;">sync</span>
            <span>In Progress</span>
        </a>

        <a href="{{ request()->fullUrlWithQuery(['overdue' => '1']) }}" class="mui-chip" style="text-decoration: none; font-size: 0.78rem; {{ request('overdue') ? 'background: var(--mui-error); color: #fff; border-color: var(--mui-error);' : '' }}">
            <span class="material-symbols-outlined" style="font-size: 15px;">warning</span>
            <span>Overdue Only</span>
        </a>

        <span style="margin-left: auto; font-size: 0.78rem; color: var(--mui-on-surface-variant); font-weight: 600;">
            Showing {{ $tasks->total() }} deliverables
        </span>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. KANBAN BOARD VIEW -->
<!-- ========================================================================= -->
@if(in_array($viewMode, ['board', 'kanban']))
    @php
        $todoTasks = $tasks->filter(fn($t) => $t->status === 'todo');
        $inProgressTasks = $tasks->filter(fn($t) => $t->status === 'in_progress');
        $completedTasks = $tasks->filter(fn($t) => $t->status === 'completed');
    @endphp

    <div class="kanban-board">
        <!-- Column 1: To Do -->
        <div class="kanban-column" id="column-todo" data-status="todo" ondragover="allowDrop(event)" ondragleave="dragLeave(event)" ondrop="handleDrop(event, 'todo')">
            <div class="kanban-column-header">
                <div class="kanban-column-title">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #64748b; display: inline-block;"></span>
                    <span>To Do</span>
                </div>
                <span class="kanban-count-badge" id="count-todo">{{ $todoTasks->count() }}</span>
            </div>

            <div class="kanban-cards-container" id="container-todo">
                @forelse($todoTasks as $task)
                    @include('tasks._card', ['task' => $task])
                @empty
                    <div class="kanban-empty-hint" style="text-align: center; padding: 30px 16px; color: var(--mui-on-surface-muted); font-size: 0.8125rem;">
                        No tasks to do
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Column 2: In Progress -->
        <div class="kanban-column" id="column-in_progress" data-status="in_progress" ondragover="allowDrop(event)" ondragleave="dragLeave(event)" ondrop="handleDrop(event, 'in_progress')">
            <div class="kanban-column-header">
                <div class="kanban-column-title">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #0284c7; display: inline-block;"></span>
                    <span>In Progress</span>
                </div>
                <span class="kanban-count-badge" id="count-in_progress">{{ $inProgressTasks->count() }}</span>
            </div>

            <div class="kanban-cards-container" id="container-in_progress">
                @forelse($inProgressTasks as $task)
                    @include('tasks._card', ['task' => $task])
                @empty
                    <div class="kanban-empty-hint" style="text-align: center; padding: 30px 16px; color: var(--mui-on-surface-muted); font-size: 0.8125rem;">
                        No tasks in progress
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Column 3: Completed -->
        <div class="kanban-column" id="column-completed" data-status="completed" ondragover="allowDrop(event)" ondragleave="dragLeave(event)" ondrop="handleDrop(event, 'completed')">
            <div class="kanban-column-header">
                <div class="kanban-column-title">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                    <span>Completed</span>
                </div>
                <span class="kanban-count-badge" id="count-completed">{{ $completedTasks->count() }}</span>
            </div>

            <div class="kanban-cards-container" id="container-completed">
                @forelse($completedTasks as $task)
                    @include('tasks._card', ['task' => $task])
                @empty
                    <div class="kanban-empty-hint" style="text-align: center; padding: 30px 16px; color: var(--mui-on-surface-muted); font-size: 0.8125rem;">
                        No completed tasks
                    </div>
                @endforelse
            </div>
        </div>
    </div>

<!-- ========================================================================= -->
<!-- 2. CARDS GRID VIEW -->
<!-- ========================================================================= -->
@elseif($viewMode === 'grid')
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        @forelse($tasks as $task)
        <div class="mui-card" id="task-card-{{ $task->id }}" style="display: flex; flex-direction: column; justify-content: space-between; border-radius: var(--mui-radius-lg);">
            <div>
                <!-- Top Chips -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; gap: 8px;">
                    <span class="mui-chip {{ $task->status_badge_class }}" id="badge-status-{{ $task->id }}">
                        <span class="material-symbols-outlined" style="font-size: 14px;">
                            {{ $task->status === 'completed' ? 'check_circle' : ($task->status === 'in_progress' ? 'pending' : 'radio_button_unchecked') }}
                        </span>
                        <span>{{ $task->status_label }}</span>
                    </span>

                    <span class="mui-chip {{ $task->priority_badge_class }}">
                        {{ ucfirst($task->priority) }}
                    </span>
                </div>

                @if($task->section)
                <div style="margin-bottom: 8px;">
                    <span class="mui-chip" style="font-size: 0.7rem; font-weight: 700; background: var(--mui-primary-light); color: var(--mui-primary); padding: 2px 8px;">
                        <span class="material-symbols-outlined" style="font-size: 13px;">domain</span>
                        {{ $task->section->name }}
                    </span>
                </div>
                @endif

                <a href="{{ route('tasks.show', $task) }}" style="text-decoration: none; color: inherit;">
                    <h3 style="font-size: 1.05rem; font-weight: 700; line-height: 1.35; margin-bottom: 6px; color: var(--mui-on-surface); transition: color 0.15s ease;" onmouseover="this.style.color='var(--mui-primary)'" onmouseout="this.style.color='var(--mui-on-surface)'">
                        {{ $task->title }}
                    </h3>
                </a>

                <p style="font-size: 0.8125rem; color: var(--mui-on-surface-variant); margin-bottom: 14px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    {{ $task->description ?? 'No extra technical details specified.' }}
                </p>
            </div>

            <div>
                <!-- Assignee & Due Date Row -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid var(--mui-outline); margin-bottom: 12px;">
                    @if($task->assignee)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $task->assignee->avatar_color }};">
                            {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                        </div>
                        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--mui-on-surface);">{{ $task->assignee->name }}</span>
                    </div>
                    @else
                    <span style="font-size: 0.78rem; color: var(--mui-on-surface-muted); font-style: italic;">Unassigned</span>
                    @endif

                    @if($task->due_date)
                    <div style="font-size: 0.78rem; display: flex; align-items: center; gap: 4px; color: {{ $task->isOverdue() ? 'var(--mui-error)' : 'var(--mui-on-surface-variant)' }}; font-weight: {{ $task->isOverdue() ? '700' : '500' }};">
                        <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                        <span>{{ $task->due_date->format('M d') }}</span>
                        @if($task->isOverdue())
                        <span style="color: var(--mui-error); font-size: 0.7rem; background: var(--mui-error-light); padding: 1px 5px; border-radius: 4px;">Overdue</span>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Footer: Counters & Quick Status Select -->
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.78rem; color: var(--mui-on-surface-variant);">
                        <span style="display: flex; align-items: center; gap: 3px;" title="Attachments">
                            <span class="material-symbols-outlined" style="font-size: 15px;">attach_file</span>
                            {{ $task->attachments->count() }}
                        </span>
                        <span style="display: flex; align-items: center; gap: 3px;" title="Comments">
                            <span class="material-symbols-outlined" style="font-size: 15px;">chat_bubble_outline</span>
                            {{ $task->comments->count() }}
                        </span>
                    </div>

                    <!-- Instant Status Dropdown with AJAX -->
                    <select onchange="updateTaskStatusViaAjax({{ $task->id }}, this.value)" class="mui-select" style="height: 30px; padding: 0 24px 0 10px; font-size: 0.75rem; width: auto; border-radius: var(--mui-radius-sm);">
                        <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>To Do</option>
                        <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
            </div>
        </div>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #ffffff; border-radius: var(--mui-radius-lg); border: 1.5px dashed var(--mui-outline-strong);">
            <div style="width: 56px; height: 56px; border-radius: var(--mui-radius-full); background: var(--mui-surface-container); color: var(--mui-on-surface-variant); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px;">
                <span class="material-symbols-outlined" style="font-size: 28px;">assignment_late</span>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--mui-on-surface);">No Tasks Found</h3>
            <p style="color: var(--mui-on-surface-variant); font-size: 0.875rem; margin-top: 4px;">
                Adjust your filters or create a new task to get started.
            </p>
        </div>
        @endforelse
    </div>

<!-- ========================================================================= -->
<!-- 3. DATA TABLE VIEW -->
<!-- ========================================================================= -->
@else
    <div class="mui-table-wrapper">
        <table class="mui-table">
            <thead>
                <tr>
                    <th>Deliverable Details</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assignee</th>
                    <th>Due Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                <tr id="task-row-{{ $task->id }}">
                    <td style="max-width: 320px;">
                        <a href="{{ route('tasks.show', $task) }}" style="font-weight: 700; color: var(--mui-on-surface); text-decoration: none;" onmouseover="this.style.color='var(--mui-primary)'" onmouseout="this.style.color='var(--mui-on-surface)'">
                            {{ $task->title }}
                        </a>
                        <div style="font-size: 0.78rem; color: var(--mui-on-surface-variant); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">
                            {{ $task->description ?? 'No extra description' }}
                        </div>
                    </td>
                    <td>
                        <span class="mui-chip {{ $task->status_badge_class }}">{{ $task->status_label }}</span>
                    </td>
                    <td>
                        <span class="mui-chip {{ $task->priority_badge_class }}">{{ ucfirst($task->priority) }}</span>
                    </td>
                    <td>
                        @if($task->assignee)
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $task->assignee->avatar_color }};">
                                {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                            </div>
                            <span style="font-size: 0.875rem; font-weight: 600;">{{ $task->assignee->name }}</span>
                        </div>
                        @else
                        <span style="color: var(--mui-on-surface-muted); font-size: 0.8125rem;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($task->due_date)
                        <span style="font-size: 0.8125rem; color: {{ $task->isOverdue() ? 'var(--mui-error)' : 'var(--mui-on-surface)' }}; font-weight: {{ $task->isOverdue() ? '700' : '500' }};">
                            {{ $task->due_date->format('M d, Y') }}
                        </span>
                        @else
                        <span style="color: var(--mui-on-surface-muted); font-size: 0.8125rem;">—</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <a href="{{ route('tasks.show', $task) }}" class="mui-btn mui-btn-tonal mui-btn-sm" title="View Task Details">
                                <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                            </a>
                            @if(Auth::user()->isAdmin())
                            <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Permanently delete this task?');" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mui-btn mui-btn-danger mui-btn-sm" title="Delete Task">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 48px; color: var(--mui-on-surface-variant);">
                        No tasks match the filter conditions.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

<!-- Pagination -->
<div style="margin-top: 24px;">
    {{ $tasks->links() }}
</div>

@if(Auth::user()->isAdmin())
<!-- Modern Create Task Dialog -->
<div class="mui-dialog-backdrop" id="createTaskDialog">
    <div class="mui-dialog">
        <div class="mui-dialog-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-md); background: var(--mui-primary-light); color: var(--mui-primary); display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">add_task</span>
                </div>
                <h2 class="mui-dialog-title">Create New Deliverable</h2>
            </div>
            <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="closeDialog('createTaskDialog')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('tasks.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mui-dialog-body">
                @if($errors->any())
                <div style="background: var(--mui-error-light); border-left: 4px solid var(--mui-error); border-radius: var(--mui-radius-sm); padding: 12px 16px; margin-bottom: 16px; color: var(--mui-error-text); font-size: 0.875rem;">
                    <strong>Please check the following errors:</strong>
                    <ul style="margin: 6px 0 0 16px; padding: 0;">
                        @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Title -->
                <div class="mui-textfield">
                    <label class="mui-label" for="taskTitle">Task Title *</label>
                    <input type="text" name="title" id="taskTitle" class="mui-input" value="{{ old('title') }}" placeholder="e.g. Implement modern responsive UI/UX architecture" required>
                </div>

                <!-- Description -->
                <div class="mui-textfield">
                    <label class="mui-label" for="taskDescription">Technical Description & Acceptance Criteria</label>
                    <textarea name="description" id="taskDescription" class="mui-textarea" placeholder="Detail the requirements, milestones, and deliverable expectations...">{{ old('description') }}</textarea>
                </div>

                @if(Auth::user()->isSuperAdmin() && isset($sections))
                <!-- Section -->
                <div class="mui-textfield">
                    <label class="mui-label" for="taskSection">Target Section *</label>
                    <select name="section_id" id="taskSection" class="mui-select" required>
                        <option value="">-- Select Target Section --</option>
                        @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" {{ old('section_id', request('section_id')) == $sec->id ? 'selected' : '' }}>
                            {{ $sec->name }} ({{ $sec->code }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Assignee -->
                    <div class="mui-textfield">
                        <label class="mui-label" for="taskAssignee">Assign To</label>
                        <select name="assigned_to" id="taskAssignee" class="mui-select">
                            <option value="">-- Unassigned --</option>
                            @foreach($allStaffMembers ?? $staffMembers as $staff)
                            <option value="{{ $staff->id }}" 
                                    data-section="{{ $staff->section_id }}"
                                    {{ old('assigned_to') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }} ({{ $staff->department }})
                            </option>
                            @endforeach
                        </select>
                        <div id="assigneeHelperText" class="mui-helper-text" style="display: none;"></div>
                    </div>

                    <!-- Due Date -->
                    <div class="mui-textfield">
                        <label class="mui-label" for="taskDueDate">Due Date</label>
                        <input type="date" name="due_date" id="taskDueDate" class="mui-input" value="{{ old('due_date') }}">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Priority -->
                    <div class="mui-textfield">
                        <label class="mui-label" for="taskPriority">Priority Level</label>
                        <select name="priority" id="taskPriority" class="mui-select">
                            <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low Priority</option>
                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Medium Priority</option>
                            <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High Priority</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="mui-textfield">
                        <label class="mui-label" for="taskStatus">Initial Status</label>
                        <select name="status" id="taskStatus" class="mui-select">
                            <option value="todo" {{ old('status', 'todo') == 'todo' ? 'selected' : '' }}>To Do</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                </div>

                <!-- Multi-File Attachments Dropzone -->
                <div class="mui-textfield">
                    <label class="mui-label" for="taskAttachments">
                        <span class="material-symbols-outlined" style="font-size: 16px;">attach_file</span>
                        <span>Deliverable Attachments</span>
                    </label>
                    <input type="file" name="attachments[]" id="taskAttachments" class="mui-input" multiple style="padding: 10px; cursor: pointer;">
                    <div id="fileListPreview" style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;"></div>
                    <div class="mui-helper-text">Supported: PNG, JPG, PDF, Word, Excel, ZIP, TXT (Up to 20MB per file).</div>
                </div>
            </div>

            <div class="mui-dialog-footer">
                <button type="button" class="mui-btn mui-btn-text" onclick="closeDialog('createTaskDialog')">Cancel</button>
                <button type="submit" class="mui-btn mui-btn-gradient">
                    <span class="material-symbols-outlined">add_task</span>
                    <span>Create Deliverable</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    // Open create dialog if requested via hash or param or error
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('create') === '1' || window.location.hash === '#create' || {{ $errors->any() ? 'true' : 'false' }}) {
        openDialog('createTaskDialog');
    }

    // Dynamic Assignee filtering based on Target Section
    const allStaffMembers = {!! json_encode($staffMembersJson ?? []) !!};
    const taskSectionSelect = document.getElementById('taskSection');
    const taskAssigneeSelect = document.getElementById('taskAssignee');
    const assigneeHelper = document.getElementById('assigneeHelperText');
    const initialAssigneeId = "{{ old('assigned_to', '') }}";

    function filterAssigneesBySection(sectionId, preselectedId = '') {
        if (!taskAssigneeSelect) return;
        taskAssigneeSelect.innerHTML = '';

        if (!sectionId) {
            const promptOpt = document.createElement('option');
            promptOpt.value = '';
            promptOpt.textContent = '-- Select Target Section First --';
            taskAssigneeSelect.appendChild(promptOpt);
            taskAssigneeSelect.disabled = true;
            if (assigneeHelper) assigneeHelper.style.display = 'none';
            return;
        }

        taskAssigneeSelect.disabled = false;
        const unassignedOpt = document.createElement('option');
        unassignedOpt.value = '';
        unassignedOpt.textContent = '-- Unassigned --';
        taskAssigneeSelect.appendChild(unassignedOpt);

        const filteredMembers = allStaffMembers.filter(m => String(m.section_id) === String(sectionId));

        if (filteredMembers.length === 0) {
            const noMembersOpt = document.createElement('option');
            noMembersOpt.value = '';
            noMembersOpt.disabled = true;
            noMembersOpt.textContent = 'No team members in this section';
            taskAssigneeSelect.appendChild(noMembersOpt);
            if (assigneeHelper) {
                assigneeHelper.textContent = 'No team members currently assigned to this section.';
                assigneeHelper.style.display = 'block';
            }
        } else {
            filteredMembers.forEach(member => {
                const opt = document.createElement('option');
                opt.value = member.id;
                opt.textContent = member.name + (member.department ? ' (' + member.department + ')' : '');
                if (String(member.id) === String(preselectedId)) {
                    opt.selected = true;
                }
                taskAssigneeSelect.appendChild(opt);
            });
            if (assigneeHelper) {
                assigneeHelper.textContent = `Showing ${filteredMembers.length} team member(s) in this section.`;
                assigneeHelper.style.display = 'block';
            }
        }
    }

    if (taskSectionSelect) {
        taskSectionSelect.addEventListener('change', function() {
            filterAssigneesBySection(this.value);
        });
        filterAssigneesBySection(taskSectionSelect.value, initialAssigneeId);
    }

    // Attachments preview list
    const fileInput = document.getElementById('taskAttachments');
    const previewContainer = document.getElementById('fileListPreview');
    if (fileInput && previewContainer) {
        fileInput.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            if (this.files.length > 0) {
                Array.from(this.files).forEach(f => {
                    const sizeStr = f.size > 1048576 ? (f.size / 1048576).toFixed(2) + ' MB' : (f.size / 1024).toFixed(1) + ' KB';
                    const item = document.createElement('div');
                    item.style.cssText = 'font-size: 0.8rem; display: flex; align-items: center; gap: 8px; padding: 6px 12px; background: var(--mui-surface-container); border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-sm); color: var(--mui-on-surface);';
                    item.innerHTML = '<span class="material-symbols-outlined" style="font-size: 16px; color: var(--mui-primary);">description</span><span style="font-weight: 600;">' + f.name + '</span> <span style="color: var(--mui-on-surface-variant); margin-left: auto;">(' + sizeStr + ')</span>';
                    previewContainer.appendChild(item);
                });
            }
        });
    }

    // AJAX Task Status Update (Single source for Grid & Kanban)
    function updateTaskStatusViaAjax(taskId, newStatus, cardElement = null) {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/tasks/${taskId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => {
            if (!response.ok) throw new Error('Status update failed');
            return response.json();
        })
        .then(data => {
            showToast(data.message || 'Status updated successfully', 'success');

            // If in Kanban mode, move card to proper container and update column counts
            if (cardElement) {
                const targetContainer = document.getElementById(`container-${newStatus}`);
                if (targetContainer) {
                    targetContainer.appendChild(cardElement);
                    updateKanbanCounts();
                }
            }
        })
        .catch(err => {
            showToast('Failed to update status. Please try again.', 'error');
            console.error(err);
        });
    }

    // Kanban Drag & Drop Handlers
    let draggedTaskId = null;
    let draggedCardElement = null;

    function handleDragStart(e, taskId) {
        draggedTaskId = taskId;
        draggedCardElement = document.getElementById(`kanban-card-${taskId}`);
        if (draggedCardElement) {
            draggedCardElement.classList.add('dragging');
        }
        e.dataTransfer.setData('text/plain', taskId);
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragEnd(e) {
        if (draggedCardElement) {
            draggedCardElement.classList.remove('dragging');
        }
        document.querySelectorAll('.kanban-column').forEach(col => col.classList.remove('drag-over'));
    }

    function allowDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
    }

    function dragLeave(e) {
        e.currentTarget.classList.remove('drag-over');
    }

    function handleDrop(e, targetStatus) {
        e.preventDefault();
        e.currentTarget.classList.remove('drag-over');
        if (!draggedTaskId || !draggedCardElement) return;

        updateTaskStatusViaAjax(draggedTaskId, targetStatus, draggedCardElement);
    }

    function updateKanbanCounts() {
        ['todo', 'in_progress', 'completed'].forEach(st => {
            const container = document.getElementById(`container-${st}`);
            const badge = document.getElementById(`count-${st}`);
            if (container && badge) {
                const count = container.querySelectorAll('.kanban-card').length;
                badge.textContent = count;
            }
        });
    }
</script>
@endpush
@endsection
