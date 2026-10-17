<div class="kanban-card" id="kanban-card-{{ $task->id }}" draggable="true" ondragstart="handleDragStart(event, {{ $task->id }})" ondragend="handleDragEnd(event)">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        @if($task->section)
        <span class="mui-chip" style="font-size: 0.68rem; font-weight: 700; background: var(--mui-primary-light); color: var(--mui-primary); padding: 1px 7px;">
            {{ $task->section->code }}
        </span>
        @else
        <span></span>
        @endif

        <span class="mui-chip {{ $task->priority_badge_class }}" style="font-size: 0.68rem; padding: 1px 7px;">
            {{ ucfirst($task->priority) }}
        </span>
    </div>

    <a href="{{ route('tasks.show', $task) }}" style="text-decoration: none; color: inherit;">
        <h4 style="font-size: 0.95rem; font-weight: 700; line-height: 1.35; margin-bottom: 6px; color: var(--mui-on-surface); transition: color 0.15s ease;" onmouseover="this.style.color='var(--mui-primary)'" onmouseout="this.style.color='var(--mui-on-surface)'">
            {{ $task->title }}
        </h4>
    </a>

    @if($task->description)
    <p style="font-size: 0.78rem; color: var(--mui-on-surface-variant); margin-bottom: 12px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
        {{ $task->description }}
    </p>
    @endif

    <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 10px; border-top: 1px solid var(--mui-outline); margin-top: 6px;">
        <div style="display: flex; align-items: center; gap: 6px;">
            @if($task->assignee)
            <div class="mui-avatar mui-avatar-sm" style="width: 26px; height: 26px; font-size: 0.7rem; background-color: {{ $task->assignee->avatar_color }};" title="{{ $task->assignee->name }}">
                {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
            </div>
            <span style="font-size: 0.75rem; font-weight: 600; color: var(--mui-on-surface); max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $task->assignee->name }}
            </span>
            @else
            <span style="font-size: 0.72rem; color: var(--mui-on-surface-muted); font-style: italic;">Unassigned</span>
            @endif
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            @if($task->subtasks->count() > 0)
            <span style="display: flex; align-items: center; gap: 3px; font-size: 0.72rem; color: var(--mui-on-surface-variant);" title="{{ $task->subtasks->where('is_completed', true)->count() }} of {{ $task->subtasks->count() }} subtasks completed">
                <span class="material-symbols-outlined" style="font-size: 14px; color: var(--mui-primary);">checklist</span>
                <span>{{ $task->subtasks->where('is_completed', true)->count() }}/{{ $task->subtasks->count() }}</span>
            </span>
            @endif

            @if($task->attachments->count() > 0)
            <span style="display: flex; align-items: center; gap: 2px; font-size: 0.72rem; color: var(--mui-on-surface-variant);" title="{{ $task->attachments->count() }} attachments">
                <span class="material-symbols-outlined" style="font-size: 14px;">attach_file</span>
                {{ $task->attachments->count() }}
            </span>
            @endif

            @if($task->due_date)
            <span style="font-size: 0.72rem; font-weight: {{ $task->isOverdue() ? '700' : '500' }}; color: {{ $task->isOverdue() ? 'var(--mui-error)' : 'var(--mui-on-surface-variant)' }}; background: {{ $task->isOverdue() ? 'var(--mui-error-light)' : 'transparent' }}; padding: {{ $task->isOverdue() ? '1px 5px' : '0' }}; border-radius: 4px;">
                {{ $task->due_date->format('M d') }}
            </span>
            @endif
        </div>
    </div>

    <!-- Quick Move Dropdown for Touch / Mobile / Accessibility -->
    <div style="margin-top: 8px; display: flex; justify-content: flex-end;">
        <select onchange="updateTaskStatusViaAjax({{ $task->id }}, this.value, document.getElementById('kanban-card-{{ $task->id }}'))" class="mui-select" style="height: 24px; font-size: 0.68rem; padding: 0 18px 0 6px; width: auto; border-radius: var(--mui-radius-sm); border-color: var(--mui-outline); background-position: right 6px center; background-size: 14px;">
            <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>Move to To Do</option>
            <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>Move to In Progress</option>
            <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Move to Completed</option>
        </select>
    </div>
</div>
