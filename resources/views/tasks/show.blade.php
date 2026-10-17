@extends('layouts.app')

@section('title', $task->title)
@section('page_title', 'Deliverable #' . $task->id)

@section('content')
<!-- Top Back & Breadcrumb Row -->
<div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <a href="{{ route('tasks.index') }}" class="mui-btn mui-btn-outlined mui-btn-sm" style="border-radius: var(--mui-radius-full);">
        <span class="material-symbols-outlined" style="font-size: 16px;">arrow_back</span>
        <span>Back to All Tasks</span>
    </a>

    <div style="display: flex; align-items: center; gap: 8px;">
        @if(Auth::user()->isAdmin())
        <button type="button" class="mui-btn mui-btn-tonal mui-btn-sm" onclick="openDialog('editTaskDialog')">
            <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
            <span>Edit Specifications</span>
        </button>
        <form action="{{ route('tasks.destroy', $task) }}" method="POST" onsubmit="return confirm('Permanently delete this deliverable?');" style="margin: 0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="mui-btn mui-btn-danger mui-btn-sm" title="Delete Deliverable">
                <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
            </button>
        </form>
        @endif
    </div>
</div>

<div class="deliverable-layout-grid">
    <!-- Main Left Column -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Deliverable Header & Status Stepper Card -->
        <div class="mui-card">
            <!-- Header Badges -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                @if($task->section)
                <span class="mui-chip" style="font-size: 0.72rem; font-weight: 700; background: var(--mui-primary-light); color: var(--mui-primary); border-color: rgba(79, 70, 229, 0.2);">
                    <span class="material-symbols-outlined" style="font-size: 14px;">domain</span>
                    {{ $task->section->name }} ({{ $task->section->code }})
                </span>
                @endif
                <span class="mui-chip {{ $task->status_badge_class }}" id="showPageStatusBadge">
                    {{ $task->status_label }}
                </span>
                <span class="mui-chip {{ $task->priority_badge_class }}">
                    {{ ucfirst($task->priority) }} Priority
                </span>
                @if($task->isOverdue())
                <span class="mui-chip chip-priority-high">
                    <span class="material-symbols-outlined" style="font-size: 14px;">warning</span>
                    <span>Overdue</span>
                </span>
                @endif
            </div>

            <h2 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.025em; color: var(--mui-on-surface); line-height: 1.3; margin-bottom: 16px;">
                {{ $task->title }}
            </h2>

            <!-- Interactive Status Progress Stepper -->
            <div style="background: var(--mui-surface-dim); border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-lg); padding: 16px 20px; margin-bottom: 20px;">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-on-surface-variant); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; color: var(--mui-primary);">sync_alt</span>
                    <span>Execution Status Stepper</span>
                </div>
                <div class="status-stepper-grid">
                    <button type="button" onclick="updateTaskStatus('todo')" class="mui-btn mui-btn-sm {{ $task->status === 'todo' ? 'mui-btn-filled' : 'mui-btn-outlined' }}" style="height: 38px; border-radius: var(--mui-radius-md);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">radio_button_unchecked</span>
                        <span>1. To Do</span>
                    </button>
                    <button type="button" onclick="updateTaskStatus('in_progress')" class="mui-btn mui-btn-sm {{ $task->status === 'in_progress' ? 'mui-btn-filled' : 'mui-btn-outlined' }}" style="height: 38px; border-radius: var(--mui-radius-md);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">pending</span>
                        <span>2. In Progress</span>
                    </button>
                    <button type="button" onclick="updateTaskStatus('completed')" class="mui-btn mui-btn-sm {{ $task->status === 'completed' ? 'mui-btn-filled' : 'mui-btn-outlined' }}" style="height: 38px; border-radius: var(--mui-radius-md);">
                        <span class="material-symbols-outlined" style="font-size: 16px;">check_circle</span>
                        <span>3. Completed</span>
                    </button>
                </div>
            </div>

            <!-- Description Container -->
            <div style="border-top: 1px solid var(--mui-outline); padding-top: 18px;">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-on-surface-variant); margin-bottom: 8px;">
                    Technical Description & Scope
                </div>
                <div style="font-size: 0.95rem; line-height: 1.65; color: var(--mui-on-surface); white-space: pre-line;">
                    {{ $task->description ?? 'No extra technical description provided for this deliverable.' }}
                </div>
            </div>
        </div>

        <!-- Actionable Checklist & Subtasks Card -->
        <div class="mui-card" id="subtaskCard">
            <div class="mui-card-header">
                <div>
                    <h3 class="mui-card-title" style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: var(--mui-primary);">checklist</span>
                        <span>Actionable Checklist & Subtasks</span>
                    </h3>
                    <div class="mui-card-subtitle" id="subtaskSubtitle">
                        <span id="subtaskCountText">{{ $task->subtasks->where('is_completed', true)->count() }} of {{ $task->subtasks->count() }} items completed</span>
                        (<span id="subtaskPercentText">{{ $task->subtasks_progress_percent }}%</span>)
                    </div>
                </div>
                <span class="mui-chip {{ $task->subtasks_progress_percent === 100 ? 'chip-status-completed' : 'chip-status-progress' }}" id="subtaskStatusChip">
                    <span class="material-symbols-outlined" style="font-size: 15px;">{{ $task->subtasks_progress_percent === 100 ? 'check_circle' : 'timelapse' }}</span>
                    <span id="subtaskChipLabel">{{ $task->subtasks_progress_percent === 100 ? 'All Completed' : 'In Progress' }}</span>
                </span>
            </div>

            <!-- Material 3 Linear Progress Bar -->
            <div class="md-linear-progress" style="margin-bottom: 18px; height: 8px; background: var(--mui-surface-container);">
                <div id="subtaskProgressBar" class="md-linear-progress-bar" style="width: {{ $task->subtasks_progress_percent }}%; background: linear-gradient(90deg, #4f46e5 0%, #10b981 100%);"></div>
            </div>

            <!-- Subtask Items List -->
            <div id="subtaskList" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
                @forelse($task->subtasks as $sub)
                <div class="subtask-row" id="subtask-row-{{ $sub->id }}" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: var(--mui-radius-md); border: 1px solid var(--mui-outline); background: {{ $sub->is_completed ? 'var(--mui-surface-dim)' : '#ffffff' }}; transition: all 0.2s ease;">
                    <label class="md-checkbox-wrapper" style="flex: 1; cursor: pointer;">
                        <input type="checkbox" onchange="toggleSubtaskAjax({{ $sub->id }})" {{ $sub->is_completed ? 'checked' : '' }} id="subtask-chk-{{ $sub->id }}">
                        <span class="md-checkbox-label" id="subtask-title-{{ $sub->id }}" style="font-size: 0.875rem; {{ $sub->is_completed ? 'text-decoration: line-through; color: var(--mui-on-surface-variant);' : 'color: var(--mui-on-surface); font-weight: 500;' }}">
                            {{ $sub->title }}
                        </span>
                    </label>
                    <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="deleteSubtaskAjax({{ $sub->id }})" style="color: var(--mui-on-surface-variant); width: 28px; height: 28px;" title="Delete checklist item">
                        <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                    </button>
                </div>
                @empty
                <div id="noSubtasksNotice" style="text-align: center; padding: 20px; color: var(--mui-on-surface-variant); font-size: 0.85rem;">
                    No checklist items defined yet. Add key action items below.
                </div>
                @endforelse
            </div>

            <!-- Quick Add Item Form -->
            <form id="newSubtaskForm" onsubmit="addSubtaskAjax(event)" style="display: flex; gap: 10px; align-items: center; border-top: 1px solid var(--mui-outline); padding-top: 14px;">
                <div class="mui-input-wrapper" style="flex: 1;">
                    <span class="material-symbols-outlined mui-leading-icon" style="font-size: 18px;">add_task</span>
                    <input type="text" id="newSubtaskInput" class="mui-input mui-input-with-icon" placeholder="Add a new checklist step & press Enter..." required style="height: 42px; font-size: 0.875rem;">
                </div>
                <button type="submit" class="mui-btn mui-btn-filled mui-btn-sm" style="height: 42px; padding: 0 16px;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                    <span>Add Item</span>
                </button>
            </form>
        </div>

        <!-- Deliverable Attachments Card -->
        <div class="mui-card">
            <div class="mui-card-header">
                <div>
                    <h3 class="mui-card-title">Attachments & Assets ({{ $task->attachments->count() }})</h3>
                    <div class="mui-card-subtitle">Deliverable files, documentation, and previews</div>
                </div>
                <button type="button" class="mui-btn mui-btn-tonal mui-btn-sm" onclick="document.getElementById('attachmentInput').click()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">upload_file</span>
                    <span>Upload File</span>
                </button>
            </div>

            <!-- Upload Hidden Form -->
            <form action="{{ route('tasks.attachments.store', $task) }}" method="POST" enctype="multipart/form-data" id="attachmentForm" style="display: none;">
                @csrf
                <input type="file" name="attachment" id="attachmentInput" onchange="document.getElementById('attachmentForm').submit()">
            </form>

            @php
                $images = $task->attachments->filter->is_image;
            @endphp

            @if($images->isNotEmpty())
            <!-- Image Previews Grid -->
            <div style="margin-bottom: 20px;">
                <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-on-surface-variant); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; color: var(--mui-primary);">image</span>
                    <span>Image Gallery ({{ $images->count() }})</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
                    @foreach($images as $img)
                    <div class="mui-card" style="padding: 0; overflow: hidden; border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md);">
                        <div style="position: relative; background: #0f172a; height: 180px; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer;" onclick="openLightbox('{{ $img->url }}', '{{ $img->original_name }}')">
                            <img src="{{ $img->url }}" alt="{{ $img->original_name }}" style="width: 100%; height: 100%; object-fit: contain; background: #0b0f19;">
                            <div style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.65); color: #fff; padding: 4px 8px; font-size: 0.72rem; display: flex; align-items: center; gap: 4px; border-radius: var(--mui-radius-full);">
                                <span class="material-symbols-outlined" style="font-size: 14px;">zoom_in</span>
                                <span>Preview</span>
                            </div>
                        </div>
                        <div style="padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 0.8125rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--mui-on-surface);" title="{{ $img->original_name }}">
                                    {{ $img->original_name }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--mui-on-surface-variant); margin-top: 2px;">
                                    {{ $img->formatted_size }} • {{ $img->created_at->format('M d, Y') }}
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <a href="{{ route('attachments.download', $img) }}" class="mui-icon-btn mui-icon-btn-sm" title="Download">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">download</span>
                                </a>
                                @if(Auth::user()->isAdmin() || $img->uploaded_by === Auth::id())
                                <form action="{{ route('attachments.destroy', $img) }}" method="POST" onsubmit="return confirm('Delete this image?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mui-icon-btn mui-icon-btn-sm" style="color: var(--mui-error);" title="Delete">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <hr style="height: 1px; background: var(--mui-outline); border: none; margin-bottom: 16px;">
            @endif

            <!-- File List -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                @forelse($task->attachments as $att)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md); background: var(--mui-surface-dim);">
                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;">
                        <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-sm); background: var(--mui-primary-light); color: var(--mui-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span class="material-symbols-outlined" style="font-size: 20px;">{{ $att->icon }}</span>
                        </div>
                        <div style="min-width: 0; flex: 1;">
                            <div style="font-size: 0.875rem; font-weight: 700; color: var(--mui-on-surface); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $att->original_name }}
                                @if($att->is_image)
                                <span class="mui-chip" style="font-size: 0.68rem; padding: 1px 6px; margin-left: 6px; background: var(--mui-primary-light); color: var(--mui-primary);">Image</span>
                                @endif
                            </div>
                            <div style="font-size: 0.72rem; color: var(--mui-on-surface-variant); margin-top: 2px;">
                                {{ $att->formatted_size }} • Uploaded {{ $att->created_at->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        @if($att->is_image)
                        <button type="button" onclick="openLightbox('{{ $att->url }}', '{{ $att->original_name }}')" class="mui-btn mui-btn-tonal mui-btn-sm" style="height: 30px; font-size: 0.78rem;">
                            <span class="material-symbols-outlined" style="font-size: 15px;">visibility</span>
                            <span>Preview</span>
                        </button>
                        @endif

                        <a href="{{ route('attachments.download', $att) }}" class="mui-btn mui-btn-tonal mui-btn-sm" style="height: 30px; font-size: 0.78rem;">
                            <span class="material-symbols-outlined" style="font-size: 15px;">download</span>
                            <span>Download</span>
                        </a>

                        @if(Auth::user()->isAdmin() || $att->uploaded_by === Auth::id())
                        <form action="{{ route('attachments.destroy', $att) }}" method="POST" onsubmit="return confirm('Delete this file?');" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mui-icon-btn mui-icon-btn-sm" style="color: var(--mui-error); width: 30px; height: 30px;" title="Delete file">
                                <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div style="text-align: center; padding: 28px; color: var(--mui-on-surface-variant); font-size: 0.875rem;">
                    No files attached yet. Click <strong>Upload File</strong> above to attach documentation or assets.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Comments & Discussion Timeline -->
        <div class="mui-card">
            <div class="mui-card-header">
                <div>
                    <h3 class="mui-card-title">Collaboration & Updates</h3>
                    <div class="mui-card-subtitle">Real-time discussion stream</div>
                </div>
                <div style="width: 36px; height: 36px; border-radius: var(--mui-radius-md); background: var(--mui-primary-light); color: var(--mui-primary); display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined">forum</span>
                </div>
            </div>

            <!-- Post New Comment Form -->
            <form action="{{ route('tasks.comments.store', $task) }}" method="POST" style="margin-bottom: 24px;">
                @csrf
                <div class="mui-textfield" style="margin-bottom: 12px;">
                    <textarea name="comment" class="mui-textarea" placeholder="Share a progress update, technical note, or blocker..." style="min-height: 85px;" required></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="mui-btn mui-btn-gradient mui-btn-sm">
                        <span class="material-symbols-outlined" style="font-size: 16px;">send</span>
                        <span>Post Update</span>
                    </button>
                </div>
            </form>

            <!-- Comments List -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @forelse($task->comments as $comment)
                <div style="display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md); background: var(--mui-surface-dim);">
                    <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $comment->user ? $comment->user->avatar_color : '#64748b' }}; margin-top: 2px;">
                        {{ strtoupper(substr($comment->user ? $comment->user->name : 'U', 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 700; font-size: 0.875rem; color: var(--mui-on-surface);">{{ $comment->user ? $comment->user->name : 'Unknown User' }}</span>
                                @if($comment->user)
                                <span class="mui-chip {{ $comment->user->role_badge_class }}" style="font-size: 0.65rem; padding: 1px 6px;">
                                    {{ $comment->user->role_title }}
                                </span>
                                @endif
                            </div>
                            <span style="font-size: 0.72rem; color: var(--mui-on-surface-muted);">
                                {{ $comment->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--mui-on-surface); line-height: 1.55; white-space: pre-line;">
                            {{ $comment->comment }}
                        </div>
                    </div>
                    <div>
                        @if(Auth::user()->isAdmin() || $comment->user_id === Auth::id())
                        <form action="{{ route('comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('Remove comment?');" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mui-icon-btn mui-icon-btn-sm" style="color: var(--mui-error); width: 28px; height: 28px;" title="Delete comment">
                                <span class="material-symbols-outlined" style="font-size: 15px;">delete</span>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @empty
                <div style="text-align: center; color: var(--mui-on-surface-variant); font-size: 0.85rem; padding: 24px;">
                    No discussion updates posted yet. Be the first to add a note above!
                </div>
                @endforelse
            </div>
        </div>

        <!-- Activity Audit Trail Card -->
        <div class="mui-card">
            <div class="mui-card-header">
                <div>
                    <h3 class="mui-card-title" style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="color: var(--mui-primary);">history</span>
                        <span>Activity & Audit Trail</span>
                    </h3>
                    <div class="mui-card-subtitle">Chronological record of status changes, attachments, and actions</div>
                </div>
                <span class="mui-chip" style="font-size: 0.72rem; background: var(--mui-surface-dim); border-color: var(--mui-outline);">
                    {{ $activityLogs->count() }} records
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px; max-height: 380px; overflow-y: auto; padding-right: 4px;">
                @forelse($activityLogs as $log)
                <div style="display: flex; align-items: flex-start; gap: 12px; padding: 10px 14px; background: var(--mui-surface-dim); border-radius: var(--mui-radius-md); border: 1px solid var(--mui-outline);">
                    <div class="mui-avatar mui-avatar-sm" style="width: 28px; height: 28px; font-size: 0.72rem; background-color: {{ $log->user->avatar_color ?? 'var(--mui-primary)' }};">
                        {{ strtoupper(substr($log->user->name ?? 'S', 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                            <span style="font-size: 0.8125rem; font-weight: 700; color: var(--mui-on-surface);">{{ $log->user->name ?? 'System' }}</span>
                            <span style="font-size: 0.72rem; color: var(--mui-on-surface-muted);">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--mui-on-surface-variant); margin-top: 2px;">
                            {{ $log->details }}
                        </div>
                    </div>
                </div>
                @empty
                <div style="text-align: center; color: var(--mui-on-surface-variant); font-size: 0.8125rem; padding: 18px;">
                    No recent activity records found for this deliverable.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Metadata Sidebar Card -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="mui-card">
            <h3 class="mui-card-title" style="margin-bottom: 16px; font-size: 1rem;">Deliverable Specifications</h3>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Section -->
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-on-surface-variant); letter-spacing: 0.04em;">Unit Section</div>
                    <div style="margin-top: 4px; font-weight: 700; color: var(--mui-primary); font-size: 0.9rem;">
                        {{ $task->section ? $task->section->name . ' (' . $task->section->code . ')' : 'General Headquarters' }}
                    </div>
                </div>
                <hr style="height: 1px; background: var(--mui-outline); border: none;">

                <!-- Assignee -->
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-on-surface-variant); letter-spacing: 0.04em;">Assigned Specialist</div>
                    <div style="margin-top: 6px;">
                        @if($task->assignee)
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $task->assignee->avatar_color }};">
                                {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 0.875rem; color: var(--mui-on-surface);">{{ $task->assignee->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--mui-on-surface-variant);">{{ $task->assignee->department }}</div>
                            </div>
                        </div>
                        @else
                        <span style="color: var(--mui-on-surface-muted); font-size: 0.85rem; font-style: italic;">Unassigned deliverable</span>
                        @endif
                    </div>
                </div>
                <hr style="height: 1px; background: var(--mui-outline); border: none;">

                <!-- Due Date -->
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-on-surface-variant); letter-spacing: 0.04em;">Target Deadline</div>
                    <div style="margin-top: 4px; font-size: 0.875rem; font-weight: {{ $task->isOverdue() ? '700' : '600' }}; color: {{ $task->isOverdue() ? 'var(--mui-error)' : 'var(--mui-on-surface)' }};">
                        {{ $task->due_date ? $task->due_date->format('l, F d, Y') : 'No target deadline specified' }}
                    </div>
                    @if($task->due_date && $task->isOverdue())
                    <div style="font-size: 0.72rem; color: var(--mui-error); font-weight: 600; margin-top: 2px;">
                        Deadline has passed
                    </div>
                    @endif
                </div>
                <hr style="height: 1px; background: var(--mui-outline); border: none;">

                <!-- Creator -->
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-on-surface-variant); letter-spacing: 0.04em;">Created By</div>
                    <div style="margin-top: 4px; font-size: 0.85rem; font-weight: 600; color: var(--mui-on-surface);">
                        {{ $task->creator ? $task->creator->name : 'System Administrator' }}
                    </div>
                </div>
                <hr style="height: 1px; background: var(--mui-outline); border: none;">

                <!-- Created Timestamp -->
                <div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-on-surface-variant); letter-spacing: 0.04em;">Logged On</div>
                    <div style="margin-top: 4px; font-size: 0.8125rem; color: var(--mui-on-surface-variant);">
                        {{ $task->created_at->format('M d, Y • h:i A') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div class="lightbox-modal" id="imageLightbox" onclick="if(event.target === this) closeLightbox()">
    <div class="lightbox-content">
        <button type="button" class="mui-icon-btn" onclick="closeLightbox()" style="position: absolute; top: -44px; right: 0; color: #fff; background: rgba(255,255,255,0.15); border-radius: var(--mui-radius-full);">
            <span class="material-symbols-outlined">close</span>
        </button>
        <img src="" id="lightboxImg" alt="Preview">
        <div id="lightboxCaption" style="color: #fff; margin-top: 12px; font-size: 0.875rem; font-weight: 600;"></div>
    </div>
</div>

@if(Auth::user()->isAdmin())
<!-- Edit Task Dialog -->
<div class="mui-dialog-backdrop" id="editTaskDialog">
    <div class="mui-dialog">
        <div class="mui-dialog-header">
            <h2 class="mui-dialog-title">Edit Specifications #{{ $task->id }}</h2>
            <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="closeDialog('editTaskDialog')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('tasks.update', $task) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mui-dialog-body">
                <div class="mui-textfield">
                    <label class="mui-label" for="editTitle">Deliverable Title *</label>
                    <input type="text" name="title" id="editTitle" class="mui-input" value="{{ $task->title }}" required>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="editDescription">Description</label>
                    <textarea name="description" id="editDescription" class="mui-textarea">{{ $task->description }}</textarea>
                </div>

                @if(Auth::user()->isSuperAdmin() && isset($sections))
                <div class="mui-textfield">
                    <label class="mui-label" for="editSection">Target Section *</label>
                    <select name="section_id" id="editSection" class="mui-select" required>
                        @foreach($sections as $sec)
                        <option value="{{ $sec->id }}" {{ $task->section_id == $sec->id ? 'selected' : '' }}>
                            {{ $sec->name }} ({{ $sec->code }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="mui-textfield">
                        <label class="mui-label" for="editAssignee">Assign To</label>
                        <select name="assigned_to" id="editAssignee" class="mui-select">
                            <option value="">-- Unassigned --</option>
                            @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}" {{ $task->assigned_to == $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }} ({{ $staff->department }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="editDueDate">Due Date</label>
                        <input type="date" name="due_date" id="editDueDate" class="mui-input" value="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="mui-textfield">
                        <label class="mui-label" for="editPriority">Priority</label>
                        <select name="priority" id="editPriority" class="mui-select">
                            <option value="low" {{ $task->priority === 'low' ? 'selected' : '' }}>Low Priority</option>
                            <option value="medium" {{ $task->priority === 'medium' ? 'selected' : '' }}>Medium Priority</option>
                            <option value="high" {{ $task->priority === 'high' ? 'selected' : '' }}>High Priority</option>
                        </select>
                    </div>

                    <div class="mui-textfield">
                        <label class="mui-label" for="editStatus">Status</label>
                        <select name="status" id="editStatus" class="mui-select">
                            <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>To Do</option>
                            <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mui-dialog-footer">
                <button type="button" class="mui-btn mui-btn-text" onclick="closeDialog('editTaskDialog')">Cancel</button>
                <button type="submit" class="mui-btn mui-btn-gradient">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    // Lightbox helper
    function openLightbox(url, name) {
        const lb = document.getElementById('imageLightbox');
        document.getElementById('lightboxImg').src = url;
        document.getElementById('lightboxCaption').textContent = name;
        lb.classList.add('open');
    }

    function closeLightbox() {
        document.getElementById('imageLightbox').classList.remove('open');
    }

    // Interactive Status Stepper via AJAX
    function updateTaskStatus(newStatus) {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch("{{ route('tasks.update-status', $task) }}", {
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
            showToast(data.message || 'Status updated', 'success');
            setTimeout(() => window.location.reload(), 400);
        })
        .catch(err => {
            showToast('Failed to update status', 'error');
            console.error(err);
        });
    }

    // Subtask Checklist AJAX Handlers
    function toggleSubtaskAjax(subtaskId) {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const chk = document.getElementById('subtask-chk-' + subtaskId);
        const titleSpan = document.getElementById('subtask-title-' + subtaskId);
        const row = document.getElementById('subtask-row-' + subtaskId);

        fetch(`/tasks/{{ $task->id }}/subtasks/${subtaskId}/toggle`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.is_completed) {
                    titleSpan.style.textDecoration = 'line-through';
                    titleSpan.style.color = 'var(--mui-on-surface-variant)';
                    row.style.backgroundColor = 'var(--mui-surface-dim)';
                } else {
                    titleSpan.style.textDecoration = 'none';
                    titleSpan.style.color = 'var(--mui-on-surface)';
                    row.style.backgroundColor = '#ffffff';
                }

                const bar = document.getElementById('subtaskProgressBar');
                const countText = document.getElementById('subtaskCountText');
                const percentText = document.getElementById('subtaskPercentText');
                const chipLabel = document.getElementById('subtaskChipLabel');
                const statusChip = document.getElementById('subtaskStatusChip');

                if (bar) bar.style.width = data.percent + '%';
                if (countText) countText.textContent = `${data.completed_count} of ${data.total_count} items completed`;
                if (percentText) percentText.textContent = `${data.percent}%`;
                if (chipLabel) chipLabel.textContent = data.percent === 100 ? 'All Completed' : 'In Progress';
                if (statusChip) {
                    statusChip.className = data.percent === 100 ? 'mui-chip chip-status-completed' : 'mui-chip chip-status-progress';
                }

                showToast(data.message, 'success');
            }
        })
        .catch(err => {
            if (chk) chk.checked = !chk.checked;
            showToast('Failed to update checklist item', 'error');
        });
    }

    function addSubtaskAjax(e) {
        e.preventDefault();
        const input = document.getElementById('newSubtaskInput');
        const title = input.value.trim();
        if (!title) return;

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch("{{ route('tasks.subtasks.store', $task) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ title: title })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                showToast('Checklist item added', 'success');
                setTimeout(() => window.location.reload(), 300);
            }
        })
        .catch(err => {
            showToast('Failed to add checklist item', 'error');
        });
    }

    function deleteSubtaskAjax(subtaskId) {
        if (!confirm('Remove this checklist item?')) return;
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/tasks/{{ $task->id }}/subtasks/${subtaskId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('subtask-row-' + subtaskId);
                if (row) row.remove();
                showToast('Checklist item removed', 'success');
                setTimeout(() => window.location.reload(), 300);
            }
        })
        .catch(err => {
            showToast('Failed to remove checklist item', 'error');
        });
    }
</script>
@endpush
@endsection
