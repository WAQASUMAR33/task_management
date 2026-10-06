import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { taskApi, userApi } from '../../api';
import { X, Upload, File, Trash2, Calendar, AlertCircle } from 'lucide-react';

export default function TaskModal({ isOpen, onClose, onTaskSaved, editTask = null }) {
  const { user } = useAuth();
  const { addToast } = useToast();

  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [assignedTo, setAssignedTo] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [priority, setPriority] = useState('medium');
  const [status, setStatus] = useState('todo');
  const [files, setFiles] = useState([]);
  const [staffUsers, setStaffUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (isOpen) {
      userApi.getAll({ role: 'staff', status: 'active' })
        .then(users => setStaffUsers(users))
        .catch(err => console.error('Failed to load staff users:', err));

      if (editTask) {
        setTitle(editTask.title || '');
        setDescription(editTask.description || '');
        setAssignedTo(editTask.assigned_to ? String(editTask.assigned_to) : '');
        setDueDate(editTask.due_date || '');
        setPriority(editTask.priority || 'medium');
        setStatus(editTask.status || 'todo');
      } else {
        setTitle('');
        setDescription('');
        setAssignedTo('');
        setDueDate('');
        setPriority('medium');
        setStatus('todo');
      }
      setFiles([]);
      setError(null);
    }
  }, [isOpen, editTask]);

  if (!isOpen) return null;

  const handleFileChange = (e) => {
    const selectedFiles = Array.from(e.target.files);
    const MAX_SIZE = 10 * 1024 * 1024; // 10MB
    const validFiles = [];

    for (const f of selectedFiles) {
      if (f.size > MAX_SIZE) {
        addToast(`"${f.name}" exceeds the maximum limit of 10MB.`, 'warning');
        continue;
      }
      validFiles.push(f);
    }

    setFiles(prev => [...prev, ...validFiles]);
  };

  const removeFile = (index) => {
    setFiles(prev => prev.filter((_, i) => i !== index));
  };

  const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!title.trim()) {
      setError('Task title is required.');
      return;
    }

    try {
      setLoading(true);
      setError(null);

      const formData = new FormData();
      formData.append('title', title.trim());
      formData.append('description', description.trim());
      if (assignedTo) formData.append('assigned_to', assignedTo);
      if (dueDate) formData.append('due_date', dueDate);
      formData.append('priority', priority);
      formData.append('status', status);

      for (const file of files) {
        formData.append('attachments', file);
      }

      if (editTask) {
        await taskApi.update(editTask.id, formData);
        addToast('Task updated successfully.', 'success');
      } else {
        await taskApi.create(formData);
        addToast('New task created and assigned successfully.', 'success');
      }

      onTaskSaved();
      onClose();
    } catch (err) {
      console.error('Failed to save task:', err);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-4 sm:p-6 flex items-center justify-center">
      {/* Modal Dialog Card - Strict max-height, min-h-0 for proper flex shrink, sharp corners (rounded-none) */}
      <div 
        className="w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 shadow-2xl flex flex-col max-h-[88vh] min-h-0 overflow-hidden my-auto rounded-none"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Fixed Header with Title & Close Button */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shrink-0">
          <div>
            <h2 className="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 tracking-tight">
              {editTask ? 'Edit Task Details' : 'Create New Task'}
            </h2>
            <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {editTask ? 'Update assignment, due dates, and attach additional files' : 'Assign workflows and attach documents to staff'}
            </p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-800 transition rounded-none"
            title="Close dialog"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Scrollable Form Body - min-h-0 allows flexbox scrolling */}
        <form onSubmit={handleSubmit} id="task-form" className="flex-1 min-h-0 overflow-y-auto p-6 space-y-4">
          {error && (
            <div className="p-3 bg-rose-50 dark:bg-rose-950/50 border border-rose-300 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2 rounded-none">
              <AlertCircle className="w-4 h-4 shrink-0" />
              <span>{error}</span>
            </div>
          )}

          {/* Title */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
              Task Title *
            </label>
            <input
              type="text"
              required
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="e.g. Implement user permission matrix"
              className="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-500 text-xs sm:text-sm rounded-none"
            />
          </div>

          {/* Description */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
              Description & Specifications
            </label>
            <textarea
              rows={4}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Describe deliverables, technical scope, and acceptance criteria..."
              className="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-500 text-xs sm:text-sm rounded-none"
            />
          </div>

          {/* Assignee & Due Date Row */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Assign To Staff
              </label>
              <select
                value={assignedTo}
                onChange={(e) => setAssignedTo(e.target.value)}
                className="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-500 text-xs sm:text-sm rounded-none"
              >
                <option value="">Unassigned</option>
                {staffUsers.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name} ({u.department || 'General'})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Due Date
              </label>
              <input
                type="date"
                value={dueDate}
                onChange={(e) => setDueDate(e.target.value)}
                className="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-500 text-xs sm:text-sm rounded-none"
              />
            </div>
          </div>

          {/* Priority & Status Row */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Priority
              </label>
              <select
                value={priority}
                onChange={(e) => setPriority(e.target.value)}
                className="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-500 text-xs sm:text-sm font-semibold rounded-none"
              >
                <option value="low">Low Priority</option>
                <option value="medium">Medium Priority</option>
                <option value="high">High Priority (Urgent)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Status
              </label>
              <select
                value={status}
                onChange={(e) => setStatus(e.target.value)}
                className="w-full px-3.5 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-indigo-600 dark:focus:border-indigo-500 text-xs sm:text-sm font-semibold rounded-none"
              >
                <option value="todo">To Do</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
          </div>

          {/* Multiple File Attachments Dropzone */}
          <div>
            <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
              File Attachments (Images, PDFs, Docs, Max 10MB)
            </label>
            <div className="relative border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-500 dark:hover:border-indigo-400 p-5 text-center transition-colors bg-slate-50/50 dark:bg-slate-800/30 rounded-none">
              <input
                type="file"
                multiple
                onChange={handleFileChange}
                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.pdf,.doc,.docx,.txt,.csv,.xls,.xlsx,.ppt,.pptx,.zip"
              />
              <div className="flex flex-col items-center justify-center pointer-events-none">
                <Upload className="w-6 h-6 text-indigo-500 mb-1.5" />
                <p className="text-xs font-bold text-slate-700 dark:text-slate-200">
                  Click or drag files here to attach
                </p>
                <p className="text-[11px] text-slate-400 mt-0.5">
                  Images, PDFs, Docs, Spreadsheets & ZIP up to 10MB per file
                </p>
              </div>
            </div>

            {/* List of pending files */}
            {files.length > 0 && (
              <div className="mt-3 space-y-1.5">
                <span className="text-[11px] font-bold text-slate-500">
                  Selected Files ({files.length}):
                </span>
                <div className="max-h-28 overflow-y-auto space-y-1 pr-1">
                  {files.map((f, idx) => (
                    <div
                      key={idx}
                      className="flex items-center justify-between p-2 bg-slate-100 dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-none"
                    >
                      <div className="flex items-center gap-2 truncate max-w-[80%]">
                        <File className="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                        <span className="truncate font-medium">{f.name}</span>
                        <span className="text-slate-400 text-[10px]">({formatFileSize(f.size)})</span>
                      </div>
                      <button
                        type="button"
                        onClick={() => removeFile(idx)}
                        className="p-1 text-slate-400 hover:text-rose-500 transition rounded-none"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </form>

        {/* Fixed Footer Buttons */}
        <div className="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-end gap-3 shrink-0">
          <button
            type="button"
            onClick={onClose}
            disabled={loading}
            className="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 transition rounded-none"
          >
            Cancel
          </button>
          <button
            type="submit"
            form="task-form"
            disabled={loading}
            className="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 transition shadow flex items-center gap-2 rounded-none"
          >
            {loading && (
              <svg className="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
            )}
            <span>{editTask ? 'Save Task Changes' : 'Create Task'}</span>
          </button>
        </div>
      </div>
    </div>
  );
}
