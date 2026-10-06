import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { taskApi, userApi } from '../../api';
import ConfirmModal from '../common/ConfirmModal';
import { 
  X, 
  Clock, 
  Calendar, 
  User, 
  CheckCircle2, 
  AlertCircle, 
  Upload, 
  Download, 
  Trash2, 
  FileText, 
  Image as ImageIcon, 
  MessageSquare, 
  Send,
  ExternalLink,
  ShieldCheck,
  FileCheck,
  Eye,
  FileCode,
  FileSpreadsheet
} from 'lucide-react';

export default function TaskDetailModal({ isOpen, taskId, onClose, onTaskUpdated }) {
  const { user } = useAuth();
  const { addToast } = useToast();

  const [task, setTask] = useState(null);
  const [loading, setLoading] = useState(true);
  const [newComment, setNewComment] = useState('');
  const [submittingComment, setSubmittingComment] = useState(false);
  const [uploadingFiles, setUploadingFiles] = useState(false);
  const [staffUsers, setStaffUsers] = useState([]);

  // Image Lightbox preview state
  const [previewImageUrl, setPreviewImageUrl] = useState(null);
  const [previewImageName, setPreviewImageName] = useState('');

  // Attachment delete confirmation state
  const [deleteAttachmentId, setDeleteAttachmentId] = useState(null);
  const [deleteAttachmentName, setDeleteAttachmentName] = useState('');
  const [deletingAttachment, setDeletingAttachment] = useState(false);

  const isStaff = user?.role === 'staff';
  const isAdminOrSuper = user?.role === 'admin' || user?.role === 'super_admin';

  const fetchTaskDetails = async () => {
    if (!taskId) return;
    try {
      setLoading(true);
      const data = await taskApi.getById(taskId);
      setTask(data);
    } catch (err) {
      console.error('Failed to load task details:', err);
      addToast(err.message, 'error');
      onClose();
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isOpen && taskId) {
      fetchTaskDetails();
      if (isAdminOrSuper) {
        userApi.getAll({ role: 'staff', status: 'active' })
          .then(setStaffUsers)
          .catch(console.error);
      }
    }
  }, [isOpen, taskId]);

  if (!isOpen) return null;

  const handleStatusChange = async (newStatus) => {
    try {
      await taskApi.updateStatus(task.id, newStatus);
      setTask(prev => ({ ...prev, status: newStatus }));
      addToast(`Status updated to ${newStatus.replace('_', ' ')}`, 'success');
      onTaskUpdated();
    } catch (err) {
      addToast(err.message, 'error');
    }
  };

  const handleAssigneeChange = async (newAssigneeId) => {
    try {
      const res = await taskApi.updateAssignee(task.id, newAssigneeId || null);
      setTask(prev => ({
        ...prev,
        assigned_to: res.assigned_to,
        assigned_name: res.assigned_name
      }));
      addToast(res.message, 'success');
      onTaskUpdated();
    } catch (err) {
      addToast(err.message, 'error');
    }
  };

  const handleAddComment = async (e) => {
    e.preventDefault();
    if (!newComment.trim()) return;

    try {
      setSubmittingComment(true);
      const added = await taskApi.addComment(task.id, newComment.trim());
      setTask(prev => ({
        ...prev,
        comments: [...(prev.comments || []), added]
      }));
      setNewComment('');
      addToast('Note added to timeline.', 'success');
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setSubmittingComment(false);
    }
  };

  const handleFileUpload = async (e) => {
    const selectedFiles = Array.from(e.target.files);
    if (selectedFiles.length === 0) return;

    const MAX_SIZE = 10 * 1024 * 1024;
    const validFiles = [];

    for (const f of selectedFiles) {
      if (f.size > MAX_SIZE) {
        addToast(`"${f.name}" exceeds 10MB limit.`, 'warning');
        continue;
      }
      validFiles.push(f);
    }

    if (validFiles.length === 0) return;

    try {
      setUploadingFiles(true);
      const formData = new FormData();
      for (const f of validFiles) {
        formData.append('attachments', f);
      }

      const res = await taskApi.uploadAttachments(task.id, formData);
      setTask(prev => ({
        ...prev,
        attachments: [...(prev.attachments || []), ...res.attachments]
      }));
      addToast(`${res.attachments.length} file(s) attached successfully.`, 'success');
      onTaskUpdated();
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setUploadingFiles(false);
      e.target.value = '';
    }
  };

  const confirmDeleteAttachment = async () => {
    if (!deleteAttachmentId) return;

    try {
      setDeletingAttachment(true);
      await taskApi.deleteAttachment(deleteAttachmentId);
      setTask(prev => ({
        ...prev,
        attachments: prev.attachments.filter(a => a.id !== deleteAttachmentId)
      }));
      addToast('File attachment deleted.', 'success');
      setDeleteAttachmentId(null);
      onTaskUpdated();
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setDeletingAttachment(false);
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  };

  const isImageFile = (mime) => mime && mime.startsWith('image/');

  const isOverdue = task?.due_date && new Date(task.due_date) < new Date(new Date().toISOString().split('T')[0]) && task?.status !== 'completed';

  return (
    <>
      <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-4 sm:p-6 flex items-center justify-center">
        {/* Strict max-height modal dialog container with sharp corners */}
        <div 
          className="w-full max-w-4xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-800 shadow-2xl flex flex-col max-h-[88vh] min-h-0 overflow-hidden my-auto rounded-none"
          onClick={(e) => e.stopPropagation()}
        >
          {loading ? (
            <div className="flex flex-col items-center justify-center py-28 gap-3">
              <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
              <span className="text-xs font-semibold text-slate-500">Retrieving task details...</span>
            </div>
          ) : task ? (
            <>
              {/* Header */}
              <div className="flex items-start justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shrink-0">
                <div className="space-y-1.5 max-w-[85%]">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="text-[11px] font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 border border-indigo-200 dark:border-indigo-800 rounded-none">
                      TASK-{task.id}
                    </span>
                    
                    {/* Priority badge */}
                    <span className={`text-[10px] px-2 py-0.5 font-bold uppercase tracking-wider rounded-none ${
                      task.priority === 'high'
                        ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                        : task.priority === 'medium'
                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'
                        : 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                    }`}>
                      {task.priority} Priority
                    </span>

                    {/* Overdue alert */}
                    {isOverdue && (
                      <span className="text-[10px] px-2 py-0.5 font-bold bg-rose-500 text-white rounded-none">
                        Past Deadline
                      </span>
                    )}
                  </div>

                  <h2 className="text-lg sm:text-xl font-bold text-slate-900 dark:text-slate-100 leading-snug">
                    {task.title}
                  </h2>
                </div>

                <button
                  onClick={onClose}
                  className="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-800 transition rounded-none"
                  title="Close modal"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Body: Split View with min-h-0 and overflow-y-auto */}
              <div className="flex-1 min-h-0 overflow-y-auto p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Left 2 Cols: Description, File Attachments, Notes */}
                <div className="lg:col-span-2 space-y-6">
                  {/* Deliverable Specifications Box */}
                  <div>
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                      Deliverable Scope & Specifications
                    </h3>
                    <div className="p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 text-xs sm:text-sm text-slate-700 dark:text-slate-200 whitespace-pre-wrap leading-relaxed rounded-none">
                      {task.description || 'No detailed deliverable specification provided.'}
                    </div>
                  </div>

                  {/* Multi-File Attachments Gallery */}
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                        <FileCheck className="w-4 h-4 text-indigo-500" />
                        Attached Files ({task.attachments?.length || 0})
                      </h3>
                      {/* Upload Button */}
                      <label className="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 font-bold text-xs transition border border-indigo-200 dark:border-indigo-800 rounded-none">
                        <Upload className="w-3.5 h-3.5" />
                        {uploadingFiles ? 'Uploading...' : 'Attach Documents'}
                        <input
                          type="file"
                          multiple
                          onChange={handleFileUpload}
                          disabled={uploadingFiles}
                          className="hidden"
                          accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.pdf,.doc,.docx,.txt,.csv,.xls,.xlsx,.ppt,.pptx,.zip"
                        />
                      </label>
                    </div>

                    {task.attachments && task.attachments.length > 0 ? (
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {task.attachments.map((file) => {
                          const isImg = isImageFile(file.mime_type);
                          const downloadUrl = taskApi.getDownloadUrl(file.id);

                          return (
                            <div
                              key={file.id}
                              className="group relative p-3 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-indigo-400 dark:hover:border-indigo-500 transition shadow-sm flex flex-col justify-between rounded-none"
                            >
                              <div className="flex items-start gap-3">
                                {isImg ? (
                                  <div 
                                    onClick={() => {
                                      setPreviewImageUrl(downloadUrl);
                                      setPreviewImageName(file.original_name);
                                    }}
                                    className="w-12 h-12 bg-slate-100 dark:bg-slate-800 overflow-hidden flex items-center justify-center shrink-0 border border-slate-200 dark:border-slate-700 cursor-pointer relative group/img rounded-none"
                                    title="Click to view full image"
                                  >
                                    <img
                                      src={downloadUrl}
                                      alt={file.original_name}
                                      className="w-full h-full object-cover transition transform group-hover/img:scale-110"
                                      onError={(e) => {
                                        e.target.style.display = 'none';
                                      }}
                                    />
                                    <div className="absolute inset-0 bg-slate-900/40 opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition">
                                      <Eye className="w-4 h-4 text-white" />
                                    </div>
                                  </div>
                                ) : (
                                  <div className="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 border border-indigo-100 dark:border-indigo-900/40 rounded-none">
                                    <FileText className="w-6 h-6" />
                                  </div>
                                )}

                                <div className="overflow-hidden flex-1">
                                  <div className="text-xs font-bold text-slate-900 dark:text-slate-100 truncate" title={file.original_name}>
                                    {file.original_name}
                                  </div>
                                  <div className="text-[10px] text-slate-400 mt-0.5">
                                    {formatFileSize(file.size)} • By {file.uploader_name || 'Staff'}
                                  </div>
                                </div>
                              </div>

                              <div className="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <a
                                  href={downloadUrl}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  download
                                  className="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-[11px]"
                                >
                                  <Download className="w-3.5 h-3.5" />
                                  Download File
                                </a>

                                {(isAdminOrSuper || file.uploaded_by === user?.id) && (
                                  <button
                                    type="button"
                                    onClick={() => {
                                      setDeleteAttachmentId(file.id);
                                      setDeleteAttachmentName(file.original_name);
                                    }}
                                    className="p-1 text-slate-400 hover:text-rose-500 transition rounded-none"
                                    title="Delete File Attachment"
                                  >
                                    <Trash2 className="w-3.5 h-3.5" />
                                  </button>
                                )}
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    ) : (
                      <div className="p-4 border border-dashed border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400 rounded-none">
                        No attached documents. Staff and Admins can upload files up to 10MB each.
                      </div>
                    )}
                  </div>

                  {/* Collaboration Notes / Comments */}
                  <div>
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
                      <MessageSquare className="w-4 h-4 text-indigo-500" />
                      Activity Timeline & Team Notes ({task.comments?.length || 0})
                    </h3>

                    <div className="space-y-2 mb-3 max-h-56 overflow-y-auto pr-1">
                      {task.comments && task.comments.length > 0 ? (
                        task.comments.map((c) => (
                          <div
                            key={c.id}
                            className="p-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 text-xs space-y-1 rounded-none"
                          >
                            <div className="flex items-center justify-between text-[11px]">
                              <div className="flex items-center gap-2">
                                <div
                                  className="w-5 h-5 flex items-center justify-center text-white text-[9px] font-bold rounded-none"
                                  style={{ backgroundColor: c.author_color || '#4f46e5' }}
                                >
                                  {c.author_name ? c.author_name.charAt(0).toUpperCase() : 'U'}
                                </div>
                                <span className="font-bold text-slate-800 dark:text-slate-200">
                                  {c.author_name}
                                </span>
                                <span className="text-[9px] px-1 py-0.2 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold capitalize rounded-none">
                                  {c.author_role?.replace('_', ' ')}
                                </span>
                              </div>
                              <span className="text-[10px] text-slate-400">
                                {new Date(c.created_at).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                              </span>
                            </div>
                            <p className="text-slate-700 dark:text-slate-300 pl-7 leading-relaxed">
                              {c.comment}
                            </p>
                          </div>
                        ))
                      ) : (
                        <div className="p-4 text-center text-xs text-slate-400 border border-slate-200 dark:border-slate-800 rounded-none">
                          No notes on this task yet. Type an update below.
                        </div>
                      )}
                    </div>

                    {/* Add Comment Input */}
                    <form onSubmit={handleAddComment} className="flex gap-2">
                      <input
                        type="text"
                        value={newComment}
                        onChange={(e) => setNewComment(e.target.value)}
                        placeholder="Add a progress update, deliverable link, or blocker..."
                        className="flex-1 px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
                      />
                      <button
                        type="submit"
                        disabled={submittingComment || !newComment.trim()}
                        className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs sm:text-sm font-bold transition flex items-center gap-1.5 shadow rounded-none"
                      >
                        <Send className="w-3.5 h-3.5" />
                        <span>Post Note</span>
                      </button>
                    </form>
                  </div>
                </div>

                {/* Right Col: Quick Status Toggles & Assigned Staff */}
                <div className="space-y-4 lg:border-l lg:border-slate-200 dark:lg:border-slate-800 lg:pl-6">
                  {/* Status Toggle Box */}
                  <div className="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-none">
                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                      Workflow Status
                    </label>
                    <div className="grid grid-cols-1 gap-2">
                      {[
                        { id: 'todo', label: 'To Do', activeBg: 'bg-slate-700 text-white' },
                        { id: 'in_progress', label: 'In Progress', activeBg: 'bg-indigo-600 text-white' },
                        { id: 'completed', label: 'Completed', activeBg: 'bg-emerald-600 text-white' }
                      ].map((s) => {
                        const isSelected = task.status === s.id;
                        return (
                          <button
                            key={s.id}
                            type="button"
                            onClick={() => handleStatusChange(s.id)}
                            className={`w-full py-2 px-3 text-xs font-bold transition flex items-center justify-between rounded-none ${
                              isSelected
                                ? `${s.activeBg} shadow`
                                : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750'
                            }`}
                          >
                            <span>{s.label}</span>
                            {isSelected && <CheckCircle2 className="w-4 h-4" />}
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  {/* Assignee Information / Reassignment */}
                  <div className="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-none">
                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                      Assigned Staff
                    </label>
                    {isAdminOrSuper ? (
                      <div>
                        <select
                          value={task.assigned_to || ''}
                          onChange={(e) => handleAssigneeChange(e.target.value)}
                          className="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-semibold focus:outline-none focus:border-indigo-600 rounded-none"
                        >
                          <option value="">Unassigned</option>
                          {staffUsers.map((u) => (
                            <option key={u.id} value={u.id}>
                              {u.name} ({u.department || 'General'})
                            </option>
                          ))}
                        </select>
                        <p className="text-[10px] text-slate-400 mt-1">
                          Admins can re-allocate tasks across staff members.
                        </p>
                      </div>
                    ) : (
                      <div className="flex items-center gap-3">
                        <div
                          className="w-8 h-8 flex items-center justify-center text-white text-xs font-bold shrink-0 rounded-none"
                          style={{ backgroundColor: task.assigned_color || '#4f46e5' }}
                        >
                          {task.assigned_name ? task.assigned_name.charAt(0).toUpperCase() : '?'}
                        </div>
                        <div>
                          <div className="text-xs font-bold text-slate-900 dark:text-slate-100">
                            {task.assigned_name || 'Unassigned'}
                          </div>
                          <div className="text-[11px] text-slate-400">
                            {task.assigned_department || 'General'}
                          </div>
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Due Date & Creation Details */}
                  <div className="space-y-2.5 text-xs text-slate-500 dark:text-slate-400 p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-none">
                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-1.5 font-medium">
                        <Calendar className="w-3.5 h-3.5 text-slate-400" />
                        Deadline:
                      </span>
                      <span className={`font-bold ${isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200'}`}>
                        {task.due_date ? new Date(task.due_date).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) : 'No Deadline'}
                      </span>
                    </div>

                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-1.5 font-medium">
                        <User className="w-3.5 h-3.5 text-slate-400" />
                        Creator:
                      </span>
                      <span className="font-semibold text-slate-800 dark:text-slate-200">
                        {task.creator_name || 'System Admin'}
                      </span>
                    </div>

                    <div className="flex items-center justify-between">
                      <span className="flex items-center gap-1.5 font-medium">
                        <Clock className="w-3.5 h-3.5 text-slate-400" />
                        Registered:
                      </span>
                      <span>
                        {new Date(task.created_at).toLocaleDateString()}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </>
          ) : null}
        </div>
      </div>

      {/* Image Lightbox Modal */}
      {previewImageUrl && (
        <div 
          onClick={() => setPreviewImageUrl(null)}
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md animate-fade-in cursor-zoom-out"
        >
          <div className="relative max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl border border-slate-700 rounded-none">
            <img 
              src={previewImageUrl} 
              alt={previewImageName} 
              className="max-h-[85vh] w-auto object-contain mx-auto rounded-none" 
            />
            <div className="absolute top-3 right-3 flex items-center gap-2">
              <a 
                href={previewImageUrl} 
                download 
                onClick={(e) => e.stopPropagation()}
                className="p-2 bg-slate-900/90 text-white hover:bg-slate-800 transition shadow border border-slate-700 rounded-none"
                title="Download"
              >
                <Download className="w-4 h-4" />
              </a>
              <button 
                onClick={() => setPreviewImageUrl(null)}
                className="p-2 bg-slate-900/90 text-white hover:bg-slate-800 transition shadow border border-slate-700 rounded-none"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
            <div className="text-center text-xs text-white/80 py-2 bg-slate-900/90 border-t border-slate-800">
              {previewImageName}
            </div>
          </div>
        </div>
      )}

      {/* Delete Attachment Confirmation */}
      <ConfirmModal
        isOpen={Boolean(deleteAttachmentId)}
        title="Delete File Attachment?"
        message={`Are you sure you want to remove "${deleteAttachmentName}"? The file will be permanently deleted from server storage.`}
        confirmText="Delete File"
        isDestructive={true}
        loading={deletingAttachment}
        onConfirm={confirmDeleteAttachment}
        onCancel={() => setDeleteAttachmentId(null)}
      />
    </>
  );
}
