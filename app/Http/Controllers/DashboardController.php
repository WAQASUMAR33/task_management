<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Section;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();

        if ($user->isStaff()) {
            // Team Member: Isolated to tasks assigned to them in their section
            $query = Task::where('assigned_to', $user->id)
                ->where('section_id', $user->section_id);

            $totalTasks = (clone $query)->count();
            $inProgressTasks = (clone $query)->where('status', 'in_progress')->count();
            $completedTasks = (clone $query)->where('status', 'completed')->count();
            $overdueTasks = (clone $query)->where('status', '!=', 'completed')->where('due_date', '<', $today)->count();
            $dueTodayTasks = (clone $query)->where('status', '!=', 'completed')->whereDate('due_date', $today)->count();

            $highPriority = (clone $query)->where('priority', 'high')->count();
            $mediumPriority = (clone $query)->where('priority', 'medium')->count();
            $lowPriority = (clone $query)->where('priority', 'low')->count();

            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            $recentTasks = (clone $query)
                ->with(['attachments', 'comments'])
                ->orderBy('due_date', 'asc')
                ->take(5)
                ->get();

            $recentLogs = ActivityLog::where('user_id', $user->id)
                ->latest()
                ->take(8)
                ->get();

            $staffWorkloads = collect();
            $sectionSummary = $user->section;
            $allSections = collect();

            return view('dashboard.index', compact(
                'totalTasks',
                'inProgressTasks',
                'completedTasks',
                'overdueTasks',
                'dueTodayTasks',
                'highPriority',
                'mediumPriority',
                'lowPriority',
                'completionRate',
                'recentTasks',
                'recentLogs',
                'staffWorkloads',
                'sectionSummary',
                'allSections'
            ));
        }

        if ($user->isSectionAdmin()) {
            // Section Admin: Isolated strictly to their assigned Section
            $sectionId = $user->section_id;
            $sectionSummary = Section::with(['admin'])->find($sectionId);

            $query = Task::where('section_id', $sectionId);

            $totalTasks = (clone $query)->count();
            $inProgressTasks = (clone $query)->where('status', 'in_progress')->count();
            $completedTasks = (clone $query)->where('status', 'completed')->count();
            $overdueTasks = (clone $query)->where('status', '!=', 'completed')->where('due_date', '<', $today)->count();
            $dueTodayTasks = (clone $query)->where('status', '!=', 'completed')->whereDate('due_date', $today)->count();

            $highPriority = (clone $query)->where('priority', 'high')->count();
            $mediumPriority = (clone $query)->where('priority', 'medium')->count();
            $lowPriority = (clone $query)->where('priority', 'low')->count();

            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            $recentTasks = (clone $query)
                ->with(['assignee', 'attachments', 'comments'])
                ->latest()
                ->take(6)
                ->get();

            // Section Team Members Workload Matrix
            $teamMembers = User::where('section_id', $sectionId)
                ->where('role', 'staff')
                ->with(['assignedTasks' => function ($q) use ($sectionId) {
                    $q->where('section_id', $sectionId);
                }])
                ->get();

            $staffWorkloads = $teamMembers->map(function ($staff) use ($today) {
                $tasks = $staff->assignedTasks;
                $total = $tasks->count();
                $completed = $tasks->where('status', 'completed')->count();
                $inProgress = $tasks->where('status', 'in_progress')->count();
                $overdue = $tasks->where('status', '!=', 'completed')->filter(function ($t) use ($today) {
                    return $t->due_date && $t->due_date->isBefore($today);
                })->count();

                return [
                    'user' => $staff,
                    'total' => $total,
                    'completed' => $completed,
                    'in_progress' => $inProgress,
                    'overdue' => $overdue,
                    'completion_pct' => $total > 0 ? round(($completed / $total) * 100) : 0,
                ];
            });

            // Activity logs of team members in this section
            $memberIds = User::where('section_id', $sectionId)->pluck('id');
            $recentLogs = ActivityLog::whereIn('user_id', $memberIds)
                ->with('user')
                ->latest()
                ->take(10)
                ->get();

            $allSections = collect();

            return view('dashboard.index', compact(
                'totalTasks',
                'inProgressTasks',
                'completedTasks',
                'overdueTasks',
                'dueTodayTasks',
                'highPriority',
                'mediumPriority',
                'lowPriority',
                'completionRate',
                'recentTasks',
                'recentLogs',
                'staffWorkloads',
                'sectionSummary',
                'allSections'
            ));
        }

        // Super Admin: Unit-wide Executive Global View
        $totalTasks = Task::count();
        $inProgressTasks = Task::where('status', 'in_progress')->count();
        $completedTasks = Task::where('status', 'completed')->count();
        $overdueTasks = Task::where('status', '!=', 'completed')
            ->where('due_date', '<', $today)
            ->count();
        $dueTodayTasks = Task::where('status', '!=', 'completed')
            ->whereDate('due_date', $today)
            ->count();

        $highPriority = Task::where('priority', 'high')->count();
        $mediumPriority = Task::where('priority', 'medium')->count();
        $lowPriority = Task::where('priority', 'low')->count();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $recentTasks = Task::with(['section', 'assignee', 'attachments', 'comments'])
            ->latest()
            ->take(6)
            ->get();

        $recentLogs = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        // Breakdown of all sections
        $allSections = Section::with(['admin'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks' => function ($q) {
                    $q->where('status', 'completed');
                },
                'members as total_members',
            ])
            ->get();

        // Global Staff Workload Allocation Matrix
        $staffMembers = User::where('role', 'staff')
            ->with(['section', 'assignedTasks'])
            ->get();

        $staffWorkloads = $staffMembers->map(function ($staff) use ($today) {
            $tasks = $staff->assignedTasks;
            $total = $tasks->count();
            $completed = $tasks->where('status', 'completed')->count();
            $inProgress = $tasks->where('status', 'in_progress')->count();
            $overdue = $tasks->where('status', '!=', 'completed')->filter(function ($t) use ($today) {
                return $t->due_date && $t->due_date->isBefore($today);
            })->count();

            return [
                'user' => $staff,
                'total' => $total,
                'completed' => $completed,
                'in_progress' => $inProgress,
                'overdue' => $overdue,
                'completion_pct' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ];
        });

        $sectionSummary = null;

        return view('dashboard.index', compact(
            'totalTasks',
            'inProgressTasks',
            'completedTasks',
            'overdueTasks',
            'dueTodayTasks',
            'highPriority',
            'mediumPriority',
            'lowPriority',
            'completionRate',
            'recentTasks',
            'recentLogs',
            'staffWorkloads',
            'sectionSummary',
            'allSections'
        ));
    }
}
