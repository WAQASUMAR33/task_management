import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { dashboardApi } from '../../api';
import { 
  FolderKanban, 
  CheckCircle2, 
  Clock, 
  AlertTriangle, 
  Users, 
  TrendingUp, 
  Activity, 
  FileText, 
  Plus, 
  Calendar,
  CheckCircle,
  Briefcase,
  ChevronRight,
  ArrowRight,
  Shield,
  Layers,
  ArrowUpRight
} from 'lucide-react';

export default function Dashboard({ onNavigateToTasks, onNavigateToUsers }) {
  const { user } = useAuth();
  const { addToast } = useToast();

  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  const isStaff = user?.role === 'staff';
  const isSuperAdmin = user?.role === 'super_admin';
  const isAdmin = user?.role === 'admin';

  const fetchStats = async () => {
    try {
      setLoading(true);
      const data = await dashboardApi.getStats();
      setStats(data);
    } catch (err) {
      console.error('Failed to load dashboard metrics:', err);
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStats();
  }, []);

  const getActivityIcon = (action) => {
    switch (action) {
      case 'CREATE_TASK':
        return <Plus className="w-3.5 h-3.5 text-indigo-500" />;
      case 'UPDATE_STATUS':
        return <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />;
      case 'UPLOAD_ATTACHMENT':
        return <FileText className="w-3.5 h-3.5 text-blue-500" />;
      case 'DELETE_TASK':
        return <AlertTriangle className="w-3.5 h-3.5 text-rose-500" />;
      default:
        return <Activity className="w-3.5 h-3.5 text-slate-400" />;
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-32 gap-3">
        <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent animate-spin"></div>
        <span className="text-xs font-semibold text-slate-500">Aggregating live metrics from MySQL...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in w-full">
      {/* Hero Welcome Banner */}
      <div className="relative overflow-hidden bg-gradient-to-r from-indigo-700 via-indigo-600 to-purple-700 text-white p-6 sm:p-8 shadow border border-indigo-800 rounded-none">
        <div className="relative z-10 max-w-3xl">
          <div className="inline-flex items-center gap-2 px-3 py-1 bg-white/15 text-xs font-semibold uppercase tracking-wider mb-3 rounded-none">
            <span className="w-2 h-2 bg-emerald-400"></span>
            <span>{isStaff ? 'Staff Productivity Center' : isSuperAdmin ? 'Super Admin Command Console' : 'Administrator Operational View'}</span>
          </div>
          <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
            Welcome back, {user?.name}!
          </h2>
          <p className="mt-2 text-indigo-100 text-xs sm:text-sm leading-relaxed max-w-2xl">
            {isStaff
              ? (stats?.totalTasks > 0 
                  ? `You currently have ${stats.totalTasks} active assignment${stats.totalTasks === 1 ? '' : 's'}. You can review deliverables, post notes, and upload attachments.`
                  : 'You have no assigned tasks currently. New assignments will appear here once allocated by an administrator.')
              : (stats?.totalTasks > 0
                  ? `Overall system status is active with ${stats.totalTasks} total task${stats.totalTasks === 1 ? '' : 's'} executing across all departments at ${stats?.completionRate || 0}% completion velocity.`
                  : 'Database is connected with 0 tasks currently registered. Click "+ New Task" above to create your first deliverable.')}
          </p>

          <div className="mt-5 flex flex-wrap items-center gap-3">
            <button
              onClick={() => onNavigateToTasks()}
              className="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-indigo-700 hover:bg-indigo-50 font-bold text-xs sm:text-sm transition shadow rounded-none"
            >
              <FolderKanban className="w-4 h-4" />
              <span>{isStaff ? 'Open My Task List' : 'Inspect Task Operations'}</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      {/* KPI Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        {/* Total Tasks Card */}
        <div className="p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm transition group rounded-none">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              {isStaff ? 'My Assigned Tasks' : 'Total System Tasks'}
            </span>
            <div className="p-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-none">
              <FolderKanban className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className="text-3xl font-extrabold text-slate-900 dark:text-slate-100">
              {stats?.totalTasks || 0}
            </span>
            <span className="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-none">
              Active Items
            </span>
          </div>
          <div className="mt-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2.5 border-t border-slate-100 dark:border-slate-800">
            <span>To Do: <strong className="text-slate-700 dark:text-slate-200">{stats?.statusCounts?.todo || 0}</strong></span>
            <span>In Progress: <strong className="text-slate-700 dark:text-slate-200">{stats?.statusCounts?.in_progress || 0}</strong></span>
          </div>
        </div>

        {/* Completion Velocity Card */}
        <div className="p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm transition group rounded-none">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Completed Tasks
            </span>
            <div className="p-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-none">
              <CheckCircle2 className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className="text-3xl font-extrabold text-slate-900 dark:text-slate-100">
              {stats?.statusCounts?.completed || 0}
            </span>
            <span className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded-none">
              {stats?.completionRate || 0}% velocity
            </span>
          </div>
          {/* Progress Bar */}
          <div className="mt-3 w-full bg-slate-100 dark:bg-slate-800 h-2 overflow-hidden rounded-none">
            <div
              className="bg-emerald-500 h-full transition-all duration-500 rounded-none"
              style={{ width: `${stats?.completionRate || 0}%` }}
            ></div>
          </div>
        </div>

        {/* Overdue Tasks Card */}
        <div className="p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm transition group rounded-none">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Overdue Tasks
            </span>
            <div className={`p-2 transition rounded-none ${
              stats?.overdueCount > 0 
                ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400' 
                : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
            }`}>
              <AlertTriangle className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className={`text-3xl font-extrabold ${stats?.overdueCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-slate-100'}`}>
              {stats?.overdueCount || 0}
            </span>
            {stats?.overdueCount > 0 ? (
              <span className="text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 px-2 py-0.5 rounded-none">
                Needs attention
              </span>
            ) : (
              <span className="text-xs font-medium text-emerald-500">On schedule</span>
            )}
          </div>
          <div className="mt-3 text-xs text-slate-500 dark:text-slate-400 pt-2.5 border-t border-slate-100 dark:border-slate-800">
            {stats?.overdueCount > 0 ? 'Past agreed deliverable date' : 'Zero overdue work'}
          </div>
        </div>

        {/* Due Today Card */}
        <div className="p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm transition group rounded-none">
          <div className="flex items-center justify-between">
            <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              Due Today
            </span>
            <div className="p-2 bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 rounded-none">
              <Calendar className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline gap-2">
            <span className="text-3xl font-extrabold text-slate-900 dark:text-slate-100">
              {stats?.dueTodayCount || 0}
            </span>
            <span className="text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-none">
              Today's targets
            </span>
          </div>
          <div className="mt-3 text-xs text-slate-500 dark:text-slate-400 pt-2.5 border-t border-slate-100 dark:border-slate-800">
            High priority deliverables for today
          </div>
        </div>
      </div>

      {/* Priority Distribution & Staff Workload Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Priority Matrix */}
        <div className="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-none">
          <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <h3 className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 text-sm sm:text-base">
              <TrendingUp className="w-4 h-4 text-indigo-600" />
              Priority Matrix
            </h3>
            <span className="text-[11px] text-slate-400">Relative allocation</span>
          </div>

          <div className="mt-5 space-y-4">
            {/* High Priority */}
            <div>
              <div className="flex items-center justify-between text-xs mb-1.5">
                <span className="flex items-center gap-2 font-semibold text-rose-600 dark:text-rose-400">
                  <span className="w-2 h-2 bg-rose-500"></span>
                  High Priority (Urgent)
                </span>
                <span className="font-bold text-slate-900 dark:text-slate-100">
                  {stats?.priorityCounts?.high || 0}
                </span>
              </div>
              <div className="w-full bg-slate-100 dark:bg-slate-800 h-2.5 overflow-hidden rounded-none">
                <div
                  className="bg-rose-500 h-full transition-all duration-500 rounded-none"
                  style={{
                    width: `${stats?.totalTasks > 0 ? ((stats.priorityCounts?.high || 0) / stats.totalTasks) * 100 : 0}%`
                  }}
                ></div>
              </div>
            </div>

            {/* Medium Priority */}
            <div>
              <div className="flex items-center justify-between text-xs mb-1.5">
                <span className="flex items-center gap-2 font-semibold text-amber-600 dark:text-amber-400">
                  <span className="w-2 h-2 bg-amber-500"></span>
                  Medium Priority
                </span>
                <span className="font-bold text-slate-900 dark:text-slate-100">
                  {stats?.priorityCounts?.medium || 0}
                </span>
              </div>
              <div className="w-full bg-slate-100 dark:bg-slate-800 h-2.5 overflow-hidden rounded-none">
                <div
                  className="bg-amber-500 h-full transition-all duration-500 rounded-none"
                  style={{
                    width: `${stats?.totalTasks > 0 ? ((stats.priorityCounts?.medium || 0) / stats.totalTasks) * 100 : 0}%`
                  }}
                ></div>
              </div>
            </div>

            {/* Low Priority */}
            <div>
              <div className="flex items-center justify-between text-xs mb-1.5">
                <span className="flex items-center gap-2 font-semibold text-blue-600 dark:text-blue-400">
                  <span className="w-2 h-2 bg-blue-500"></span>
                  Low Priority
                </span>
                <span className="font-bold text-slate-900 dark:text-slate-100">
                  {stats?.priorityCounts?.low || 0}
                </span>
              </div>
              <div className="w-full bg-slate-100 dark:bg-slate-800 h-2.5 overflow-hidden rounded-none">
                <div
                  className="bg-blue-500 h-full transition-all duration-500 rounded-none"
                  style={{
                    width: `${stats?.totalTasks > 0 ? ((stats.priorityCounts?.low || 0) / stats.totalTasks) * 100 : 0}%`
                  }}
                ></div>
              </div>
            </div>
          </div>

          {/* Department Breakdown Mini Gauge */}
          {stats?.departmentStats && stats.departmentStats.length > 0 && (
            <div className="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
              <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-3">
                Tasks by Department
              </span>
              <div className="space-y-2">
                {stats.departmentStats.map((dept, i) => (
                  <div key={i} className="flex items-center justify-between text-xs">
                    <span className="text-slate-600 dark:text-slate-300 truncate max-w-[150px]">
                      {dept.department}
                    </span>
                    <span className="font-semibold text-slate-900 dark:text-slate-100">
                      {dept.task_count} tasks
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Staff Workload (Admin/Super Admin only) */}
        {!isStaff ? (
          <div className="lg:col-span-2 p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between rounded-none">
            <div>
              <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 text-sm sm:text-base">
                  <Users className="w-4 h-4 text-indigo-600" />
                  Staff Workload & Task Allocation
                </h3>
                <span className="text-[11px] text-slate-400">Team workload matrix</span>
              </div>

              <div className="mt-4 overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead>
                    <tr className="uppercase text-[10px] tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-800">
                      <th className="pb-3 font-bold">Staff Member</th>
                      <th className="pb-3 font-bold">Department</th>
                      <th className="pb-3 font-bold text-center">Assigned</th>
                      <th className="pb-3 font-bold text-center">Completed</th>
                      <th className="pb-3 font-bold text-center">Overdue</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                    {stats?.staffWorkload && stats.staffWorkload.length > 0 ? (
                      stats.staffWorkload.map((staff) => (
                        <tr key={staff.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                          <td className="py-3">
                            <div className="flex items-center gap-2.5">
                              <div
                                className="w-6 h-6 flex items-center justify-center text-white text-[10px] font-bold shrink-0 rounded-none"
                                style={{ backgroundColor: staff.avatar_color || '#4f46e5' }}
                              >
                                {staff.name.charAt(0).toUpperCase()}
                              </div>
                              <span className="font-bold text-slate-900 dark:text-slate-100">
                                {staff.name}
                              </span>
                            </div>
                          </td>
                          <td className="py-3 text-slate-500 dark:text-slate-400">
                            {staff.department}
                          </td>
                          <td className="py-3 text-center">
                            <span className="px-2 py-0.5 font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-none">
                              {staff.total_tasks}
                            </span>
                          </td>
                          <td className="py-3 text-center font-bold text-emerald-600 dark:text-emerald-400">
                            {staff.completed_tasks}
                          </td>
                          <td className="py-3 text-center">
                            {staff.overdue_tasks > 0 ? (
                              <span className="px-2 py-0.5 font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 rounded-none">
                                {staff.overdue_tasks}
                              </span>
                            ) : (
                              <span className="text-slate-400 font-medium">0</span>
                            )}
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="5" className="py-6 text-center text-slate-400 text-xs">
                          No active staff assignments.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            <div className="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
              <span>Audited through WAMP MySQL relational engine</span>
              <button
                onClick={() => onNavigateToTasks()}
                className="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline font-semibold"
              >
                <span>View Full Task Pipeline</span>
                <ArrowUpRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        ) : (
          /* Staff Personal Center Card */
          <div className="lg:col-span-2 p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between rounded-none">
            <div>
              <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 text-sm sm:text-base">
                  <Briefcase className="w-4 h-4 text-indigo-600" />
                  Staff Role Capabilities
                </h3>
                <span className="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 font-bold rounded-none">
                  Staff Access Active
                </span>
              </div>

              <div className="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div className="p-3.5 bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-none">
                  <span className="font-bold text-indigo-900 dark:text-indigo-300 block mb-1">Status Updates</span>
                  <p className="text-slate-500 dark:text-slate-400">
                    Switch tasks from To Do to In Progress or Completed directly with one click.
                  </p>
                </div>
                <div className="p-3.5 bg-purple-50/50 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40 rounded-none">
                  <span className="font-bold text-purple-900 dark:text-purple-300 block mb-1">File Uploads</span>
                  <p className="text-slate-500 dark:text-slate-400">
                    Attach deliverables, images, PDFs, and ZIP archives up to 10MB each.
                  </p>
                </div>
                <div className="p-3.5 bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 rounded-none">
                  <span className="font-bold text-emerald-900 dark:text-emerald-300 block mb-1">Collaboration</span>
                  <p className="text-slate-500 dark:text-slate-400">
                    Post notes and updates directly to the task conversation timeline.
                  </p>
                </div>
              </div>
            </div>

            <div className="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
              <span className="text-xs text-slate-400">Restricted from reassigning or deleting tasks.</span>
              <button
                onClick={() => onNavigateToTasks()}
                className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow rounded-none"
              >
                Go to My Assigned Tasks
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Live Operational Activity Log */}
      <div className="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-none">
        <div className="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
          <h3 className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2 text-sm sm:text-base">
            <Activity className="w-4 h-4 text-indigo-600" />
            Live Audit Stream & Events
          </h3>
          <span className="text-[11px] text-slate-400">Real-time WAMP MySQL event logging</span>
        </div>

        <div className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
          {stats?.recentActivity && stats.recentActivity.length > 0 ? (
            stats.recentActivity.map((log) => (
              <div key={log.id} className="py-3 flex items-start justify-between gap-4">
                <div className="flex items-start gap-3">
                  <div className="w-7 h-7 bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 mt-0.5 rounded-none">
                    {getActivityIcon(log.action)}
                  </div>
                  <div>
                    <div className="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100">
                      {log.details}
                    </div>
                    <div className="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                      <span className="font-semibold text-slate-700 dark:text-slate-300">{log.user_name || 'System'}</span>
                      <span>•</span>
                      <span className="capitalize">{log.action.replace(/_/g, ' ').toLowerCase()}</span>
                    </div>
                  </div>
                </div>
                <div className="text-[11px] text-slate-400 whitespace-nowrap">
                  {new Date(log.created_at).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' })}
                </div>
              </div>
            ))
          ) : (
            <div className="py-8 text-center text-xs text-slate-400">
              No recent activity recorded yet.
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
