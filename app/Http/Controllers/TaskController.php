<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Section;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\TaskSubtask;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Task::with(['section', 'assignee', 'creator', 'attachments', 'comments', 'subtasks']);

        // Data Isolation Hierarchy:
        if ($user->isStaff()) {
            // Team member sees only their assigned tasks within their section
            $query->where('assigned_to', $user->id)
                  ->where('section_id', $user->section_id);
        } elseif ($user->isSectionAdmin()) {
            // Section Admin sees only tasks within their assigned section
            $query->where('section_id', $user->section_id);
        } else {
            // Super Admin can filter by section
            if ($request->filled('section_id') && $request->input('section_id') !== 'all') {
                $query->where('section_id', $request->input('section_id'));
            }
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Priority Filter
        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        // Assignee Filter
        if ($request->filled('assigned_to') && $request->input('assigned_to') !== 'all') {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        // Overdue Filter
        if ($request->boolean('overdue')) {
            $query->where('status', '!=', 'completed')
                  ->where('due_date', '<', Carbon::today());
        }

        $viewMode = $request->input('view', 'grid');
        $perPage = in_array($viewMode, ['board', 'kanban']) ? 50 : 12;
        $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        // Team members available for assignment:
        if ($user->isSuperAdmin()) {
            $sections = Section::where('status', 'active')->orderBy('name')->get();
            $allStaffMembers = User::where('status', 'active')->orderBy('name')->get();
            if ($request->filled('section_id') && $request->input('section_id') !== 'all') {
                $staffMembers = User::where('status', 'active')
                    ->where('section_id', $request->input('section_id'))
                    ->orderBy('name')
                    ->get();
            } else {
                $staffMembers = $allStaffMembers;
            }
        } else {
            $sections = Section::where('id', $user->section_id)->get();
            // Section Admin can only assign tasks to team members in their section
            $staffMembers = User::where('section_id', $user->section_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $allStaffMembers = $staffMembers;
        }
        $staffMembersJson = $allStaffMembers->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'department' => $u->department ?? '',
                'section_id' => $u->section_id,
            ];
        })->values();

        return view('tasks.index', compact('tasks', 'sections', 'staffMembers', 'allStaffMembers', 'staffMembersJson', 'viewMode'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->isStaff()) {
            abort(403, 'Team members are not permitted to create new tasks.');
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:todo,in_progress,completed'],
            'attachments.*' => ['nullable', 'file', 'max:20480'], // 20MB max
        ];

        if ($user->isSuperAdmin()) {
            $rules['section_id'] = ['required', 'exists:sections,id'];
        }

        $validated = $request->validate($rules, [
            'attachments.*.max' => 'Each attachment must not exceed 20MB.',
            'attachments.*.file' => 'One of the uploaded files is invalid.',
        ]);

        $sectionId = $user->isSuperAdmin() ? $validated['section_id'] : $user->section_id;

        // Ensure assigned user belongs to this section
        if (!empty($validated['assigned_to'])) {
            $assignee = User::find($validated['assigned_to']);
            if (!$assignee || ($assignee->section_id != $sectionId && !$assignee->isSuperAdmin())) {
                return back()->withErrors(['assigned_to' => 'The assigned user must belong to this section.'])->withInput();
            }
        }

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'section_id' => $sectionId,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'created_by' => $user->id,
        ]);

        // Process attachments
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('attachments', 'public');
                    TaskAttachment::create([
                        'task_id' => $task->id,
                        'uploaded_by' => $user->id,
                        'filename' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size' => $file->getSize() ?: 0,
                    ]);
                }
            }
        }

        $sectionName = $task->section ? $task->section->name : 'General';
        ActivityLog::log($user->id, 'TASK_CREATED', "Created task #{$task->id} in [{$sectionName}]: \"{$task->title}\"");

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $user = Auth::user();

        // Check Access Isolation
        if ($user->isStaff()) {
            if ($task->assigned_to !== $user->id || $task->section_id !== $user->section_id) {
                abort(403, 'You do not have permission to view tasks outside your assigned section.');
            }
        } elseif ($user->isSectionAdmin()) {
            if ($task->section_id !== $user->section_id) {
                abort(403, 'Section Admins can only view tasks belonging to their section.');
            }
        }

        $task->load(['section', 'assignee', 'creator', 'attachments.uploader', 'comments.user', 'subtasks.creator']);

        // Activity Logs for this deliverable
        $activityLogs = ActivityLog::with('user')
            ->where('details', 'like', "%task #{$task->id}%")
            ->orWhere('details', 'like', "%task #{$task->id}:%")
            ->latest()
            ->take(25)
            ->get();

        // Scope dropdown to section members
        if ($user->isSuperAdmin()) {
            $sections = Section::where('status', 'active')->orderBy('name')->get();
            $allStaffMembers = User::where('status', 'active')->orderBy('name')->get();
            $staffMembers = User::where('section_id', $task->section_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        } else {
            $sections = Section::where('id', $user->section_id)->get();
            $staffMembers = User::where('section_id', $user->section_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            $allStaffMembers = $staffMembers;
        }

        $staffMembersJson = $allStaffMembers->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'department' => $u->department ?? '',
                'section_id' => $u->section_id,
            ];
        })->values();

        return view('tasks.show', compact('task', 'staffMembers', 'sections', 'allStaffMembers', 'staffMembersJson', 'activityLogs'));
    }

    public function update(Request $request, Task $task)
    {
        $user = Auth::user();
        if ($user->isStaff()) {
            abort(403, 'Team members cannot edit task specifications.');
        }

        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'You can only edit tasks within your assigned section.');
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
            'status' => ['required', 'in:todo,in_progress,completed'],
        ];

        if ($user->isSuperAdmin()) {
            $rules['section_id'] = ['nullable', 'exists:sections,id'];
        }

        $validated = $request->validate($rules);

        $targetSectionId = $user->isSuperAdmin() 
            ? ($validated['section_id'] ?? $task->section_id) 
            : $task->section_id;

        if (!empty($validated['assigned_to'])) {
            $assignee = User::find($validated['assigned_to']);
            if (!$assignee || ($assignee->section_id != $targetSectionId && !$assignee->isSuperAdmin())) {
                return back()->withErrors(['assigned_to' => 'The assigned user must belong to this section.'])->withInput();
            }
        }

        $task->update($validated);
        ActivityLog::log($user->id, 'TASK_UPDATED', "Updated task #{$task->id}: \"{$task->title}\"");

        return back()->with('success', 'Task updated successfully.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        $user = Auth::user();

        if ($user->isStaff()) {
            if ($task->assigned_to !== $user->id || $task->section_id !== $user->section_id) {
                abort(403, 'You can only update status for tasks assigned to you in your section.');
            }
        } elseif ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'You can only update tasks within your section.');
        }

        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,completed'],
        ]);

        $oldStatus = $task->status;
        $task->update(['status' => $validated['status']]);

        ActivityLog::log($user->id, 'TASK_STATUS_CHANGED', "Changed task #{$task->id} status from {$oldStatus} to {$validated['status']}");

        if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => "Task status updated to {$task->status_label}.",
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'status_label' => $task->status_label,
                    'status_badge_class' => $task->status_badge_class,
                ]
            ]);
        }

        return back()->with('success', "Task status updated to {$task->status_label}.");
    }

    public function destroy(Task $task)
    {
        $user = Auth::user();
        if ($user->isStaff()) {
            abort(403, 'Team members are not permitted to delete tasks.');
        }

        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'You can only delete tasks belonging to your section.');
        }

        // Delete physical attachment files
        foreach ($task->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->filename);
        }

        $title = $task->title;
        $taskId = $task->id;
        $task->delete();

        ActivityLog::log($user->id, 'TASK_DELETED', "Deleted task #{$taskId}: \"{$title}\"");

        return redirect()->route('tasks.index')->with('success', 'Task has been deleted.');
    }

    public function uploadAttachment(Request $request, Task $task)
    {
        $user = Auth::user();
        if ($user->isStaff() && $task->assigned_to !== $user->id) {
            abort(403, 'You can only attach files to tasks assigned to you.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'attachment' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $file = $request->file('attachment');
        $path = $file->store('attachments', 'public');

        TaskAttachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $user->id,
            'filename' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        ActivityLog::log($user->id, 'ATTACHMENT_UPLOADED', "Uploaded attachment to task #{$task->id}");

        return back()->with('success', 'File uploaded successfully.');
    }

    public function previewAttachment(TaskAttachment $attachment)
    {
        $user = Auth::user();
        $task = $attachment->task;

        // Verify task viewing permissions
        if ($user->isStaff() && ($task->assigned_to !== $user->id || $task->section_id !== $user->section_id)) {
            abort(403, 'Unauthorized to view this attachment.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($attachment->filename)) {
            abort(404, 'Attachment file not found on server.');
        }

        return response()->file($disk->path($attachment->filename), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function downloadAttachment(TaskAttachment $attachment)
    {
        $user = Auth::user();
        $task = $attachment->task;

        if ($user->isStaff() && $task->assigned_to !== $user->id && $task->created_by !== $user->id) {
            abort(403, 'Unauthorized to download this file.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        if (!Storage::disk('public')->exists($attachment->filename)) {
            abort(404, 'File not found on server.');
        }

        return Storage::disk('public')->download($attachment->filename, $attachment->original_name);
    }

    public function deleteAttachment(TaskAttachment $attachment)
    {
        $user = Auth::user();
        $task = $attachment->task;

        if ($user->isStaff() && $attachment->uploaded_by !== $user->id) {
            abort(403, 'You can only delete files that you uploaded.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        Storage::disk('public')->delete($attachment->filename);
        $attachment->delete();

        ActivityLog::log($user->id, 'ATTACHMENT_DELETED', "Removed attachment from task #{$task->id}");

        return back()->with('success', 'Attachment deleted.');
    }

    public function addComment(Request $request, Task $task)
    {
        $user = Auth::user();
        if ($user->isStaff() && $task->assigned_to !== $user->id) {
            abort(403, 'You cannot comment on tasks not assigned to you.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'comment' => $validated['comment'],
        ]);

        ActivityLog::log($user->id, 'COMMENT_ADDED', "Added comment to task #{$task->id}");

        return back()->with('success', 'Comment posted.');
    }

    public function deleteComment(TaskComment $comment)
    {
        $user = Auth::user();
        if ($user->isStaff() && $comment->user_id !== $user->id) {
            abort(403, 'You cannot delete another user\'s comment.');
        }
        if ($user->isSectionAdmin() && $comment->task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        $comment->delete();
        return back()->with('success', 'Comment removed.');
    }

    // =========================================================================
    // SUBTASKS / CHECKLIST FUNCTIONALITY
    // =========================================================================
    public function addSubtask(Request $request, Task $task)
    {
        $user = Auth::user();
        if ($user->isStaff() && ($task->assigned_to !== $user->id || $task->section_id !== $user->section_id)) {
            abort(403, 'Unauthorized.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $subtask = TaskSubtask::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'is_completed' => false,
            'created_by' => $user->id,
            'sort_order' => $task->subtasks()->count() + 1,
        ]);

        ActivityLog::log($user->id, 'SUBTASK_CREATED', "Added checklist item \"{$subtask->title}\" to task #{$task->id}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Checklist item added.',
                'subtask' => $subtask,
            ]);
        }

        return back()->with('success', 'Checklist item added.');
    }

    public function toggleSubtask(Task $task, TaskSubtask $subtask)
    {
        $user = Auth::user();
        if ($user->isStaff() && ($task->assigned_to !== $user->id || $task->section_id !== $user->section_id)) {
            abort(403, 'Unauthorized.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        if ($subtask->task_id !== $task->id) {
            abort(404, 'Checklist item does not belong to this task.');
        }

        $subtask->is_completed = !$subtask->is_completed;
        $subtask->save();

        $actionText = $subtask->is_completed ? 'completed' : 'pending';
        ActivityLog::log($user->id, 'SUBTASK_TOGGLED', "Marked checklist item \"{$subtask->title}\" as {$actionText} on task #{$task->id}");

        $task->refresh();
        $total = $task->subtasks->count();
        $completed = $task->subtasks->where('is_completed', true)->count();
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return response()->json([
            'success' => true,
            'message' => "Checklist item marked as {$actionText}.",
            'is_completed' => $subtask->is_completed,
            'completed_count' => $completed,
            'total_count' => $total,
            'percent' => $percent,
        ]);
    }

    public function deleteSubtask(Task $task, TaskSubtask $subtask)
    {
        $user = Auth::user();
        if ($user->isStaff() && ($task->assigned_to !== $user->id || $task->section_id !== $user->section_id)) {
            abort(403, 'Unauthorized.');
        }
        if ($user->isSectionAdmin() && $task->section_id !== $user->section_id) {
            abort(403, 'Unauthorized.');
        }

        if ($subtask->task_id !== $task->id) {
            abort(404, 'Checklist item does not belong to this task.');
        }

        $title = $subtask->title;
        $subtask->delete();

        ActivityLog::log($user->id, 'SUBTASK_DELETED', "Removed checklist item \"{$title}\" from task #{$task->id}");

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Checklist item removed.',
            ]);
        }

        return back()->with('success', 'Checklist item removed.');
    }

    // =========================================================================
    // QUICK SEARCH API FOR COMMAND PALETTE (CTRL + K)
    // =========================================================================
    public function quickSearch(Request $request)
    {
        $query = trim($request->input('q', ''));
        if (strlen($query) < 1) {
            return response()->json(['tasks' => [], 'sections' => [], 'users' => []]);
        }

        $user = Auth::user();

        // 1. Search Tasks (scoped by access permissions)
        $tasksQuery = Task::with('section')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('id', $query);
            });

        if ($user->isStaff()) {
            $tasksQuery->where('assigned_to', $user->id)->where('section_id', $user->section_id);
        } elseif ($user->isSectionAdmin()) {
            $tasksQuery->where('section_id', $user->section_id);
        }

        $tasks = $tasksQuery->latest()->take(6)->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'status_label' => $t->status_label,
                'priority' => $t->priority,
                'section_code' => $t->section->code ?? '',
                'url' => route('tasks.show', $t),
            ];
        });

        // 2. Search Sections
        $sectionsQuery = Section::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%");
        if (!$user->isSuperAdmin()) {
            $sectionsQuery->where('id', $user->section_id);
        }
        $sections = $sectionsQuery->take(4)->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'url' => route('sections.show', $s),
            ];
        });

        // 3. Search Users (Admin / Super Admin only)
        $users = [];
        if ($user->isAdmin()) {
            $usersQuery = User::where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%")
                      ->orWhere('department', 'like', "%{$query}%");
                });

            if ($user->isSectionAdmin()) {
                $usersQuery->where('section_id', $user->section_id);
            }

            $users = $usersQuery->take(4)->get()->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'department' => $u->department ?? '',
                    'avatar_color' => $u->avatar_color,
                    'role_title' => $u->role_title,
                ];
            });
        }

        return response()->json([
            'tasks' => $tasks,
            'sections' => $sections,
            'users' => $users,
        ]);
    }

    // =========================================================================
    // EXPORT DELIVERABLES TO CSV
    // =========================================================================
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        $query = Task::with(['section', 'assignee', 'creator', 'subtasks']);

        if ($user->isStaff()) {
            $query->where('assigned_to', $user->id)->where('section_id', $user->section_id);
        } elseif ($user->isSectionAdmin()) {
            $query->where('section_id', $user->section_id);
        } elseif ($request->filled('section_id') && $request->input('section_id') !== 'all') {
            $query->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('assigned_to') && $request->input('assigned_to') !== 'all') {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        if ($request->boolean('overdue')) {
            $query->where('status', '!=', 'completed')->where('due_date', '<', Carbon::today());
        }

        $tasks = $query->orderBy('id', 'desc')->get();
        $filename = 'rapid_track_deliverables_' . date('Y-m-d_His') . '.csv';

        ActivityLog::log($user->id, 'TASKS_EXPORTED', "Exported {$tasks->count()} deliverables to CSV");

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($tasks) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'ID',
                'Title',
                'Section Code',
                'Section Name',
                'Assignee',
                'Status',
                'Priority',
                'Due Date',
                'Completed Subtasks',
                'Total Subtasks',
                'Subtask Progress %',
                'Created By',
                'Created Date',
            ]);

            foreach ($tasks as $task) {
                $totalSub = $task->subtasks->count();
                $compSub = $task->subtasks->where('is_completed', true)->count();
                $pct = $totalSub > 0 ? round(($compSub / $totalSub) * 100) : 0;

                fputcsv($file, [
                    $task->id,
                    $task->title,
                    $task->section->code ?? 'N/A',
                    $task->section->name ?? 'N/A',
                    $task->assignee->name ?? 'Unassigned',
                    $task->status_label,
                    ucfirst($task->priority),
                    $task->due_date ? $task->due_date->format('Y-m-d') : 'No Due Date',
                    $compSub,
                    $totalSub,
                    $pct . '%',
                    $task->creator->name ?? 'System',
                    $task->created_at ? $task->created_at->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
