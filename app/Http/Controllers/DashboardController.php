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
            $todoTasks = max(0, $totalTasks - $inProgressTasks - $completedTasks);

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

            $chartAnalytics = $this->buildChartAnalytics($query, $user);

            return view('dashboard.index', array_merge(compact(
                'totalTasks',
                'inProgressTasks',
                'completedTasks',
                'todoTasks',
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
            ), $chartAnalytics));
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
            $todoTasks = max(0, $totalTasks - $inProgressTasks - $completedTasks);

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
            $chartAnalytics = $this->buildChartAnalytics($query, $user);

            return view('dashboard.index', array_merge(compact(
                'totalTasks',
                'inProgressTasks',
                'completedTasks',
                'todoTasks',
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
            ), $chartAnalytics));
        }

        // Super Admin: Unit-wide Executive Global View
        $query = Task::query();

        $totalTasks = Task::count();
        $inProgressTasks = Task::where('status', 'in_progress')->count();
        $completedTasks = Task::where('status', 'completed')->count();
        $overdueTasks = Task::where('status', '!=', 'completed')
            ->where('due_date', '<', $today)
            ->count();
        $dueTodayTasks = Task::where('status', '!=', 'completed')
            ->whereDate('due_date', $today)
            ->count();
        $todoTasks = max(0, $totalTasks - $inProgressTasks - $completedTasks);

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
        $chartAnalytics = $this->buildChartAnalytics($query, $user);

        return view('dashboard.index', array_merge(compact(
            'totalTasks',
            'inProgressTasks',
            'completedTasks',
            'todoTasks',
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
        ), $chartAnalytics));
    }

    /**
     * Compile 7-day velocity and sectional chart analytics datasets
     */
    private function buildChartAnalytics($baseQuery, $user): array
    {
        $sevenDaysLabels = [];
        $sevenDaysCreated = [];
        $sevenDaysCompleted = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $sevenDaysLabels[] = $date->format('D, M j');

            $created = (clone $baseQuery)->whereDate('created_at', $dateStr)->count();
            $completed = (clone $baseQuery)->where('status', 'completed')->whereDate('updated_at', $dateStr)->count();

            $sevenDaysCreated[] = $created;
            $sevenDaysCompleted[] = $completed;
        }

        $chartSectionsLabels = [];
        $chartSectionsTotal = [];
        $chartSectionsCompleted = [];

        if ($user->isSuperAdmin()) {
            $sections = Section::withCount([
                'tasks',
                'tasks as completed_tasks' => function ($q) {
                    $q->where('status', 'completed');
                }
            ])->get();

            foreach ($sections as $sec) {
                $chartSectionsLabels[] = $sec->name;
                $chartSectionsTotal[] = $sec->tasks_count;
                $chartSectionsCompleted[] = $sec->completed_tasks;
            }
        } elseif ($user->isSectionAdmin()) {
            $staff = User::where('section_id', $user->section_id)
                ->where('role', 'staff')
                ->with(['assignedTasks' => function ($q) use ($user) {
                    $q->where('section_id', $user->section_id);
                }])->get();

            foreach ($staff as $m) {
                $chartSectionsLabels[] = $m->name;
                $chartSectionsTotal[] = $m->assignedTasks->count();
                $chartSectionsCompleted[] = $m->assignedTasks->where('status', 'completed')->count();
            }
        } else {
            $chartSectionsLabels = ['High Priority', 'Medium Priority', 'Low Priority'];
            $chartSectionsTotal = [
                (clone $baseQuery)->where('priority', 'high')->count(),
                (clone $baseQuery)->where('priority', 'medium')->count(),
                (clone $baseQuery)->where('priority', 'low')->count(),
            ];
            $chartSectionsCompleted = [
                (clone $baseQuery)->where('priority', 'high')->where('status', 'completed')->count(),
                (clone $baseQuery)->where('priority', 'medium')->where('status', 'completed')->count(),
                (clone $baseQuery)->where('priority', 'low')->where('status', 'completed')->count(),
            ];
        }

        return [
            'sevenDaysLabels' => $sevenDaysLabels,
            'sevenDaysCreated' => $sevenDaysCreated,
            'sevenDaysCompleted' => $sevenDaysCompleted,
            'chartSectionsLabels' => $chartSectionsLabels,
            'chartSectionsTotal' => $chartSectionsTotal,
            'chartSectionsCompleted' => $chartSectionsCompleted,
        ];
    }
}
