import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { taskApi, userApi } from '../../api';
import TaskModal from './TaskModal';
import TaskDetailModal from './TaskDetailModal';
import ConfirmModal from '../common/ConfirmModal';
import { 
  Plus, 
  Search, 
  Filter, 
  RotateCcw, 
  Kanban, 
  LayoutGrid, 
  List, 
  Calendar, 
  User, 
  Paperclip, 
  MessageSquare, 
  Edit3, 
  Trash2, 
  CheckCircle2, 
  Clock, 
  AlertCircle,
  MoreVertical,
  ChevronRight,
  ArrowUpDown
} from 'lucide-react';

export default function TaskList() {
  const { user } = useAuth();
  const { addToast } = useToast();

  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [staffUsers, setStaffUsers] = useState([]);

  // Filters state
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [priorityFilter, setPriorityFilter] = useState('');
  const [assignedFilter, setAssignedFilter] = useState('');
  const [overdueOnly, setOverdueOnly] = useState(false);

  // View Mode: 'board' | 'grid' | 'table'
  const [viewMode, setViewMode] = useState('board');

  // Modal Dialogs state
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [editingTask, setEditingTask] = useState(null);
  const [detailTaskId, setDetailTaskId] = useState(null);

  // Delete task confirmation state
  const [deleteTaskId, setDeleteTaskId] = useState(null);
  const [deleteTaskTitle, setDeleteTaskTitle] = useState('');
  const [isDeleting, setIsDeleting] = useState(false);

  const isStaff = user?.role === 'staff';
  const isAdminOrSuper = user?.role === 'admin' || user?.role === 'super_admin';

  const fetchTasks = async () => {
    try {
      setLoading(true);
      const params = {};
      if (search) params.search = search;
      if (statusFilter) params.status = statusFilter;
      if (priorityFilter) params.priority = priorityFilter;
      if (assignedFilter) params.assigned_to = assignedFilter;
      if (overdueOnly) params.overdue = 'true';

      const data = await taskApi.getAll(params);
      setTasks(data);
    } catch (err) {
      console.error('Failed to fetch tasks:', err);
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTasks();
  }, [search, statusFilter, priorityFilter, assignedFilter, overdueOnly]);

  useEffect(() => {
    if (isAdminOrSuper) {
      userApi.getAll({ role: 'staff', status: 'active' })
        .then(setStaffUsers)
        .catch(console.error);
    }
  }, [isAdminOrSuper]);

  const resetFilters = () => {
    setSearch('');
    setStatusFilter('');
    setPriorityFilter('');
    setAssignedFilter('');
    setOverdueOnly(false);
  };

  const handleQuickStatusChange = async (e, task, newStatus) => {
    e.stopPropagation();
    try {
      await taskApi.updateStatus(task.id, newStatus);
      setTasks(prev => prev.map(t => t.id === task.id ? { ...t, status: newStatus } : t));
      addToast(`Status updated to ${newStatus.replace('_', ' ')}`, 'success');
    } catch (err) {
      addToast(err.message, 'error');
    }
  };

  const confirmDeleteTask = async () => {
    if (!deleteTaskId) return;
    try {
      setIsDeleting(true);
      await taskApi.delete(deleteTaskId);
      setTasks(prev => prev.filter(t => t.id !== deleteTaskId));
      addToast('Task deleted successfully.', 'success');
      setDeleteTaskId(null);
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setIsDeleting(false);
    }
  };

  const isTaskOverdue = (task) => {
    if (!task.due_date || task.status === 'completed') return false;
    const due = new Date(task.due_date);
    const today = new Date(new Date().toISOString().split('T')[0]);
    return due < today;
  };

  const kanbanColumns = [
    { id: 'todo', title: 'To Do', dot: 'bg-slate-500' },
    { id: 'in_progress', title: 'In Progress', dot: 'bg-indigo-500' },
    { id: 'completed', title: 'Completed', dot: 'bg-emerald-500' }
  ];

  return (
    <div className="space-y-5 animate-fade-in w-full">
      {/* Top Header & Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
            {isStaff ? 'My Assigned Deliverables' : 'Task Operations & Workflow Hub'}
          </h1>
          <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            {isStaff 
              ? 'Review pending milestones, track deliverable statuses, and upload documentation.'
              : 'Enterprise Kanban, Grid, and Table workflows with RBAC and file attachments.'}
          </p>
        </div>

        {isAdminOrSuper && (
          <button
            onClick={() => {
              setEditingTask(null);
              setIsCreateOpen(true);
            }}
            className="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm shadow transition rounded-none"
          >
            <Plus className="w-4 h-4" />
            <span>Create New Task</span>
          </button>
        )}
      </div>

      {/* Filter and View Mode Toolbar */}
      <div className="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 rounded-none">
        <div className="flex flex-col lg:flex-row gap-3">
          {/* Real-time search */}
          <div className="relative flex-1">
            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search tasks by title, deliverables, or keywords..."
              className="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
            />
            {search && (
              <button
                onClick={() => setSearch('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600"
              >
                Clear
              </button>
            )}
          </div>

          {/* Filter Dropdowns */}
          <div className="flex flex-wrap items-center gap-2">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:border-indigo-600 rounded-none"
            >
              <option value="">All Statuses</option>
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>

            <select
              value={priorityFilter}
              onChange={(e) => setPriorityFilter(e.target.value)}
              className="px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:border-indigo-600 rounded-none"
            >
              <option value="">All Priorities</option>
              <option value="high">High Priority</option>
              <option value="medium">Medium Priority</option>
              <option value="low">Low Priority</option>
            </select>

            {isAdminOrSuper && (
              <select
                value={assignedFilter}
                onChange={(e) => setAssignedFilter(e.target.value)}
                className="px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:border-indigo-600 max-w-[150px] truncate rounded-none"
              >
                <option value="">All Assignees</option>
                {staffUsers.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name}
                  </option>
                ))}
              </select>
            )}

            {/* Overdue toggle */}
            <button
              onClick={() => setOverdueOnly(!overdueOnly)}
              className={`px-3 py-2 border text-xs font-bold transition flex items-center gap-1.5 rounded-none ${
                overdueOnly
                  ? 'bg-rose-50 dark:bg-rose-950/60 border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-300'
                  : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50'
              }`}
            >
              <AlertCircle className="w-3.5 h-3.5 text-rose-500" />
              <span>Overdue</span>
            </button>

            {/* Reset */}
            {(search || statusFilter || priorityFilter || assignedFilter || overdueOnly) && (
              <button
                onClick={resetFilters}
                className="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition rounded-none"
                title="Reset filters"
              >
                <RotateCcw className="w-4 h-4" />
              </button>
            )}

            {/* View Switchers: Kanban Board | Grid Cards | Table */}
            <div className="flex items-center border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-0.5 ml-auto rounded-none">
              <button
                onClick={() => setViewMode('board')}
                className={`flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold transition rounded-none ${
                  viewMode === 'board'
                    ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                }`}
                title="Kanban Board View"
              >
                <Kanban className="w-3.5 h-3.5" />
                <span className="hidden sm:inline">Board</span>
              </button>

              <button
                onClick={() => setViewMode('grid')}
                className={`flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold transition rounded-none ${
                  viewMode === 'grid'
                    ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                }`}
                title="Grid Cards View"
              >
                <LayoutGrid className="w-3.5 h-3.5" />
                <span className="hidden sm:inline">Grid</span>
              </button>

              <button
                onClick={() => setViewMode('table')}
                className={`flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-bold transition rounded-none ${
                  viewMode === 'table'
                    ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                }`}
                title="Table Rows View"
              >
                <List className="w-3.5 h-3.5" />
                <span className="hidden sm:inline">Table</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Task Content */}
      {loading ? (
        <div className="flex flex-col items-center justify-center py-24 gap-3">
          <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent animate-spin"></div>
          <span className="text-xs font-semibold text-slate-500">Querying MySQL tasks...</span>
        </div>
      ) : tasks.length === 0 ? (
        <div className="text-center py-20 px-4 border-2 border-dashed border-slate-300 dark:border-slate-800 bg-white/40 dark:bg-slate-900/40 rounded-none">
          <div className="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-3 border border-indigo-200 dark:border-indigo-800 rounded-none">
            <CheckCircle2 className="w-6 h-6" />
          </div>
          <h3 className="text-base font-bold text-slate-900 dark:text-slate-100">No matching tasks found</h3>
          <p className="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
            {search || statusFilter || priorityFilter || overdueOnly
              ? 'No tasks match your selected filter parameters. Clear or change filters above.'
              : 'There are currently no tasks in your queue.'}
          </p>
          {(search || statusFilter || priorityFilter || overdueOnly) ? (
            <button
              onClick={resetFilters}
              className="mt-4 px-4 py-2 bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 border border-slate-300 dark:border-slate-700 rounded-none"
            >
              Reset Filters
            </button>
          ) : isAdminOrSuper && (
            <button
              onClick={() => {
                setEditingTask(null);
                setIsCreateOpen(true);
              }}
              className="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow transition rounded-none"
            >
              <Plus className="w-4 h-4" />
              <span>Create Your First Task</span>
            </button>
          )}
        </div>
      ) : viewMode === 'board' ? (
        /* KANBAN BOARD VIEW */
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {kanbanColumns.map((col) => {
            const columnTasks = tasks.filter((t) => t.status === col.id);

            return (
              <div
                key={col.id}
                className="flex flex-col bg-slate-100/70 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 p-4 min-h-[500px] rounded-none"
              >
                {/* Column Header */}
                <div className="flex items-center justify-between pb-3 mb-3 border-b border-slate-200 dark:border-slate-800">
                  <div className="flex items-center gap-2">
                    <span className={`w-2.5 h-2.5 ${col.dot}`}></span>
                    <h3 className="font-bold text-xs uppercase tracking-wider text-slate-800 dark:text-slate-200">
                      {col.title}
                    </h3>
                  </div>
                  <span className="text-xs font-bold px-2 py-0.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 rounded-none">
                    {columnTasks.length}
                  </span>
                </div>

                {/* Column Cards */}
                <div className="space-y-3 flex-1 overflow-y-auto pr-1">
                  {columnTasks.length === 0 ? (
                    <div className="py-12 text-center text-xs text-slate-400 italic">
                      No tasks in {col.title.toLowerCase()}
                    </div>
                  ) : (
                    columnTasks.map((task) => {
                      const overdue = isTaskOverdue(task);

                      return (
                        <div
                          key={task.id}
                          onClick={() => setDetailTaskId(task.id)}
                          className="group relative p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-500 dark:hover:border-indigo-400 shadow-sm transition cursor-pointer space-y-3 rounded-none"
                        >
                          <div className="flex items-center justify-between gap-2">
                            <span className={`text-[10px] px-2 py-0.5 font-bold uppercase tracking-wider rounded-none ${
                              task.priority === 'high'
                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300'
                                : task.priority === 'medium'
                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300'
                                : 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300'
                            }`}>
                              {task.priority}
                            </span>

                            {overdue && (
                              <span className="text-[10px] font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1 bg-rose-50 dark:bg-rose-950/50 px-1.5 py-0.2 rounded-none">
                                Overdue
                              </span>
                            )}
                          </div>

                          <h4 className="text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                            {task.title}
                          </h4>

                          {task.description && (
                            <p className="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2">
                              {task.description}
                            </p>
                          )}

                          {/* Footer Details */}
                          <div className="pt-2.5 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-[11px] text-slate-400">
                            {/* Assignee Avatar */}
                            <div className="flex items-center gap-1.5 truncate max-w-[120px]">
                              <div
                                className="w-5 h-5 flex items-center justify-center text-white text-[9px] font-bold shrink-0 rounded-none"
                                style={{ backgroundColor: task.assigned_color || '#64748b' }}
                              >
                                {task.assigned_name ? task.assigned_name.charAt(0).toUpperCase() : '?'}
                              </div>
                              <span className="font-semibold text-slate-700 dark:text-slate-300 truncate">
                                {task.assigned_name || 'Unassigned'}
                              </span>
                            </div>

                            {/* Attachments & Comments counters */}
                            <div className="flex items-center gap-2 shrink-0">
                              <span className="flex items-center gap-0.5">
                                <Paperclip className="w-3 h-3 text-slate-400" />
                                {task.attachment_count || 0}
                              </span>
                              <span className="flex items-center gap-0.5">
                                <MessageSquare className="w-3 h-3 text-slate-400" />
                                {task.comment_count || 0}
                              </span>
                            </div>
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            );
          })}
        </div>
      ) : viewMode === 'grid' ? (
        /* GRID VIEW */
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
          {tasks.map((task) => {
            const overdue = isTaskOverdue(task);

            return (
              <div
                key={task.id}
                onClick={() => setDetailTaskId(task.id)}
                className="group relative flex flex-col justify-between p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-400 shadow-sm transition cursor-pointer rounded-none"
              >
                <div>
                  <div className="flex items-center justify-between gap-2 mb-3">
                    <span className={`text-[10px] px-2 py-0.5 font-bold uppercase tracking-wider rounded-none ${
                      task.priority === 'high'
                        ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                        : task.priority === 'medium'
                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                        : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                    }`}>
                      {task.priority}
                    </span>

                    <div onClick={(e) => e.stopPropagation()}>
                      <select
                        value={task.status}
                        onChange={(e) => handleQuickStatusChange(e, task, e.target.value)}
                        className={`text-[11px] font-semibold px-2 py-1 border transition-colors cursor-pointer rounded-none ${
                          task.status === 'completed'
                            ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'
                            : task.status === 'in_progress'
                            ? 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-300 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300'
                            : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300'
                        }`}
                      >
                        <option value="todo">To Do</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                      </select>
                    </div>
                  </div>

                  <h3 className="font-bold text-base text-slate-900 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                    {task.title}
                  </h3>

                  {task.description && (
                    <p className="mt-2 text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                      {task.description}
                    </p>
                  )}
                </div>

                <div className="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                  <div className="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <div className="flex items-center gap-2 truncate max-w-[140px]">
                      <div
                        className="w-5 h-5 flex items-center justify-center text-white text-[10px] font-bold shrink-0 rounded-none"
                        style={{ backgroundColor: task.assigned_color || '#64748b' }}
                      >
                        {task.assigned_name ? task.assigned_name.charAt(0).toUpperCase() : '?'}
                      </div>
                      <span className="truncate font-semibold text-slate-700 dark:text-slate-300">
                        {task.assigned_name || 'Unassigned'}
                      </span>
                    </div>

                    <div className="flex items-center gap-1">
                      <Calendar className="w-3.5 h-3.5 text-slate-400" />
                      <span className={`text-[11px] font-medium ${overdue ? 'text-rose-600 dark:text-rose-400 font-bold' : ''}`}>
                        {task.due_date ? new Date(task.due_date).toLocaleDateString([], { month: 'short', day: 'numeric' }) : 'No due date'}
                      </span>
                    </div>
                  </div>

                  <div className="flex items-center justify-between text-xs text-slate-400 pt-1">
                    <div className="flex items-center gap-3">
                      <span className="flex items-center gap-1">
                        <Paperclip className="w-3.5 h-3.5" />
                        <span>{task.attachment_count || 0}</span>
                      </span>
                      <span className="flex items-center gap-1">
                        <MessageSquare className="w-3.5 h-3.5" />
                        <span>{task.comment_count || 0}</span>
                      </span>
                    </div>

                    {isAdminOrSuper && (
                      <div className="flex items-center gap-1" onClick={(e) => e.stopPropagation()}>
                        <button
                          onClick={() => {
                            setEditingTask(task);
                            setIsCreateOpen(true);
                          }}
                          className="p-1 hover:text-indigo-600 transition rounded-none"
                          title="Edit Task"
                        >
                          <Edit3 className="w-3.5 h-3.5" />
                        </button>
                        <button
                          onClick={() => {
                            setDeleteTaskId(task.id);
                            setDeleteTaskTitle(task.title);
                          }}
                          className="p-1 hover:text-rose-600 transition rounded-none"
                          title="Delete Task"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      ) : (
        /* TABLE VIEW */
        <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden rounded-none">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                <tr>
                  <th className="px-5 py-3">Task Deliverable</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Priority</th>
                  <th className="px-4 py-3">Assignee</th>
                  <th className="px-4 py-3">Deadline</th>
                  <th className="px-4 py-3 text-center">Files</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {tasks.map((task) => {
                  const overdue = isTaskOverdue(task);

                  return (
                    <tr
                      key={task.id}
                      onClick={() => setDetailTaskId(task.id)}
                      className="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition cursor-pointer"
                    >
                      <td className="px-5 py-3 font-bold text-slate-900 dark:text-slate-100 max-w-xs truncate text-xs sm:text-sm">
                        {task.title}
                      </td>
                      <td className="px-4 py-3" onClick={(e) => e.stopPropagation()}>
                        <select
                          value={task.status}
                          onChange={(e) => handleQuickStatusChange(e, task, e.target.value)}
                          className={`text-[11px] font-semibold px-2 py-1 border cursor-pointer rounded-none ${
                            task.status === 'completed'
                              ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'
                              : task.status === 'in_progress'
                              ? 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-300 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300'
                              : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300'
                          }`}
                        >
                          <option value="todo">To Do</option>
                          <option value="in_progress">In Progress</option>
                          <option value="completed">Completed</option>
                        </select>
                      </td>
                      <td className="px-4 py-3">
                        <span className={`text-[10px] px-2 py-0.5 font-bold uppercase tracking-wider rounded-none ${
                          task.priority === 'high'
                            ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                            : task.priority === 'medium'
                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                            : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                        }`}>
                          {task.priority}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <div
                            className="w-5 h-5 flex items-center justify-center text-white text-[10px] font-bold rounded-none"
                            style={{ backgroundColor: task.assigned_color || '#64748b' }}
                          >
                            {task.assigned_name ? task.assigned_name.charAt(0).toUpperCase() : '?'}
                          </div>
                          <span className="font-semibold text-slate-800 dark:text-slate-200">
                            {task.assigned_name || 'Unassigned'}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <span className={overdue ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-500'}>
                          {task.due_date ? new Date(task.due_date).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) : 'None'}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-center text-slate-500 font-semibold">
                        {task.attachment_count || 0}
                      </td>
                      <td className="px-4 py-3 text-right" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center justify-end gap-1">
                          {isAdminOrSuper && (
                            <>
                              <button
                                onClick={() => {
                                  setEditingTask(task);
                                  setIsCreateOpen(true);
                                }}
                                className="p-1.5 text-slate-400 hover:text-indigo-600 transition rounded-none"
                                title="Edit"
                              >
                                <Edit3 className="w-4 h-4" />
                              </button>
                              <button
                                onClick={() => {
                                  setDeleteTaskId(task.id);
                                  setDeleteTaskTitle(task.title);
                                }}
                                className="p-1.5 text-slate-400 hover:text-rose-600 transition rounded-none"
                                title="Delete"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Create / Edit Modal */}
      <TaskModal
        isOpen={isCreateOpen}
        onClose={() => setIsCreateOpen(false)}
        onTaskSaved={fetchTasks}
        editTask={editingTask}
      />

      {/* Task Details Modal */}
      <TaskDetailModal
        isOpen={Boolean(detailTaskId)}
        taskId={detailTaskId}
        onClose={() => setDetailTaskId(null)}
        onTaskUpdated={fetchTasks}
      />

      {/* Delete Confirmation Modal */}
      <ConfirmModal
        isOpen={Boolean(deleteTaskId)}
        title="Delete Task Deliverable?"
        message={`Are you sure you want to permanently delete task "${deleteTaskTitle}" and all attached files from WAMP MySQL?`}
        confirmText="Delete Task"
        isDestructive={true}
        loading={isDeleting}
        onConfirm={confirmDeleteTask}
        onCancel={() => setDeleteTaskId(null)}
      />
    </div>
  );
}
