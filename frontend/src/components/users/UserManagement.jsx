import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { userApi } from '../../api';
import ConfirmModal from '../common/ConfirmModal';
import { 
  UserPlus, 
  Search, 
  ShieldCheck, 
  ShieldAlert, 
  UserCheck, 
  KeyRound, 
  UserX, 
  RotateCcw,
  Building,
  Mail,
  MoreVertical,
  Edit2,
  Trash2,
  X,
  Lock,
  CheckCircle,
  AlertCircle
} from 'lucide-react';

export default function UserManagement() {
  const { user: currentUser } = useAuth();
  const { addToast } = useToast();

  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);

  // Filters
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');

  // Create/Edit User Modal
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    role: 'staff',
    department: 'Engineering',
    status: 'active'
  });
  const [savingUser, setSavingUser] = useState(false);

  // Reset Password Modal
  const [resetModalUser, setResetModalUser] = useState(null);
  const [newPassword, setNewPassword] = useState('');
  const [savingPassword, setSavingPassword] = useState(false);

  // Delete User Confirmation
  const [deleteModalUser, setDeleteModalUser] = useState(null);
  const [deletingUser, setDeletingUser] = useState(false);

  const isSuperAdmin = currentUser?.role === 'super_admin';

  const fetchUsers = async () => {
    try {
      setLoading(true);
      const params = {};
      if (search) params.search = search;
      if (roleFilter) params.role = roleFilter;
      if (statusFilter) params.status = statusFilter;

      const data = await userApi.getAll(params);
      setUsers(data);
    } catch (err) {
      console.error('Failed to load users:', err);
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, [search, roleFilter, statusFilter]);

  const handleOpenCreate = () => {
    setEditingUser(null);
    setFormData({
      name: '',
      email: '',
      password: '',
      role: 'staff',
      department: 'Engineering',
      status: 'active'
    });
    setIsModalOpen(true);
  };

  const handleOpenEdit = (user) => {
    setEditingUser(user);
    setFormData({
      name: user.name,
      email: user.email,
      password: '',
      role: user.role,
      department: user.department || 'Engineering',
      status: user.status
    });
    setIsModalOpen(true);
  };

  const handleSaveUser = async (e) => {
    e.preventDefault();
    if (!formData.name || !formData.email) {
      addToast('Name and email are required.', 'warning');
      return;
    }
    if (!editingUser && !formData.password) {
      addToast('Password is required for new accounts.', 'warning');
      return;
    }

    try {
      setSavingUser(true);
      if (editingUser) {
        await userApi.update(editingUser.id, {
          name: formData.name,
          email: formData.email,
          role: formData.role,
          department: formData.department,
          status: formData.status
        });
        addToast('User account updated successfully.', 'success');
      } else {
        await userApi.create(formData);
        addToast('New user account created successfully.', 'success');
      }
      setIsModalOpen(false);
      fetchUsers();
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setSavingUser(false);
    }
  };

  const handleStatusToggle = async (targetUser) => {
    const nextStatus = targetUser.status === 'active' ? 'inactive' : 'active';
    try {
      await userApi.toggleStatus(targetUser.id, nextStatus);
      setUsers(prev => prev.map(u => u.id === targetUser.id ? { ...u, status: nextStatus } : u));
      addToast(`User marked as ${nextStatus}.`, 'success');
    } catch (err) {
      addToast(err.message, 'error');
    }
  };

  const handleResetPassword = async (e) => {
    e.preventDefault();
    if (!newPassword || newPassword.length < 6) {
      addToast('Password must be at least 6 characters.', 'warning');
      return;
    }

    try {
      setSavingPassword(true);
      await userApi.resetPassword(resetModalUser.id, newPassword);
      addToast('Password has been reset successfully.', 'success');
      setResetModalUser(null);
      setNewPassword('');
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setSavingPassword(false);
    }
  };

  const confirmDeleteUser = async () => {
    if (!deleteModalUser) return;
    try {
      setDeletingUser(true);
      await userApi.delete(deleteModalUser.id);
      setUsers(prev => prev.filter(u => u.id !== deleteModalUser.id));
      addToast('User deleted from MySQL database.', 'success');
      setDeleteModalUser(null);
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setDeletingUser(false);
    }
  };

  return (
    <div className="space-y-5 animate-fade-in w-full">
      {/* Top Header & Actions */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
            {isSuperAdmin ? 'Staff & Access Governance' : 'Staff Directory & Roster'}
          </h1>
          <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            {isSuperAdmin 
              ? 'Manage corporate permissions, onboard Administrators & Staff, and enforce security policies.'
              : 'View team members, roles, departments, and active user statuses.'}
          </p>
        </div>

        <button
          onClick={handleOpenCreate}
          className="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm shadow transition rounded-none"
        >
          <UserPlus className="w-4 h-4" />
          <span>Add New Staff Member</span>
        </button>
      </div>

      {/* Stats Summary Row */}
      <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div className="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-none">
          <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Accounts</span>
          <div className="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">
            {users.length}
          </div>
        </div>

        <div className="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-none">
          <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Super Admins</span>
          <div className="text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-1">
            {users.filter(u => u.role === 'super_admin').length}
          </div>
        </div>

        <div className="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-none">
          <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Administrators</span>
          <div className="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">
            {users.filter(u => u.role === 'admin').length}
          </div>
        </div>

        <div className="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-none">
          <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Staff Members</span>
          <div className="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
            {users.filter(u => u.role === 'staff').length}
          </div>
        </div>
      </div>

      {/* Filter and Search Toolbar */}
      <div className="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row gap-3 rounded-none">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search users by name, email, or department..."
            className="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
          />
        </div>

        <div className="flex items-center gap-2">
          <select
            value={roleFilter}
            onChange={(e) => setRoleFilter(e.target.value)}
            className="px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:border-indigo-600 rounded-none"
          >
            <option value="">All Roles</option>
            {isSuperAdmin && <option value="super_admin">Super Admin</option>}
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
          </select>

          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold focus:outline-none focus:border-indigo-600 rounded-none"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>

          {(search || roleFilter || statusFilter) && (
            <button
              onClick={() => {
                setSearch('');
                setRoleFilter('');
                setStatusFilter('');
              }}
              className="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition rounded-none"
              title="Reset filters"
            >
              <RotateCcw className="w-4 h-4" />
            </button>
          )}
        </div>
      </div>

      {/* Users Data Table */}
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden rounded-none">
        {loading ? (
          <div className="flex flex-col items-center justify-center py-24 gap-3">
            <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent animate-spin"></div>
            <span className="text-xs font-semibold text-slate-500">Querying users from MySQL...</span>
          </div>
        ) : users.length === 0 ? (
          <div className="py-16 text-center text-xs text-slate-400">
            No user accounts found matching your filter parameters.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                <tr>
                  <th className="px-5 py-3">Member</th>
                  <th className="px-4 py-3">Role</th>
                  <th className="px-4 py-3">Department</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Last Active</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {users.map((target) => {
                  const isSelf = target.id === currentUser?.id;
                  const canManage = isSuperAdmin || (currentUser?.role === 'admin' && target.role === 'staff');

                  return (
                    <tr key={target.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                      {/* Name & Email */}
                      <td className="px-5 py-3.5">
                        <div className="flex items-center gap-3">
                          <div
                            className="w-8 h-8 flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm rounded-none"
                            style={{ backgroundColor: target.avatar_color || '#4f46e5' }}
                          >
                            {target.name.charAt(0).toUpperCase()}
                          </div>
                          <div>
                            <div className="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                              <span>{target.name}</span>
                              {isSelf && (
                                <span className="text-[10px] px-1.5 py-0.2 bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold rounded-none">
                                  You
                                </span>
                              )}
                            </div>
                            <div className="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                              <Mail className="w-3 h-3" />
                              <span>{target.email}</span>
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* Role */}
                      <td className="px-4 py-3.5">
                        {target.role === 'super_admin' ? (
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[11px] font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800 rounded-none">
                            <ShieldAlert className="w-3 h-3 text-purple-500" />
                            Super Admin
                          </span>
                        ) : target.role === 'admin' ? (
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-none">
                            <ShieldCheck className="w-3 h-3 text-blue-500" />
                            Admin
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-none">
                            <UserCheck className="w-3 h-3 text-emerald-500" />
                            Staff Member
                          </span>
                        )}
                      </td>

                      {/* Department */}
                      <td className="px-4 py-3.5">
                        <span className="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-none">
                          {target.department || 'General Operations'}
                        </span>
                      </td>

                      {/* Status */}
                      <td className="px-4 py-3.5">
                        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 font-bold rounded-none ${
                          target.status === 'active'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
                        }`}>
                          <span className={`w-1.5 h-1.5 ${target.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'}`}></span>
                          <span className="capitalize">{target.status}</span>
                        </span>
                      </td>

                      {/* Last Active */}
                      <td className="px-4 py-3.5 text-slate-400">
                        {new Date(target.created_at).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })}
                      </td>

                      {/* Action Buttons */}
                      <td className="px-4 py-3.5 text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canManage && (
                            <>
                              <button
                                onClick={() => handleOpenEdit(target)}
                                className="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition rounded-none"
                                title="Edit user profile"
                              >
                                <Edit2 className="w-3.5 h-3.5" />
                              </button>

                              {isSuperAdmin && (
                                <button
                                  onClick={() => {
                                    setResetModalUser(target);
                                    setNewPassword('');
                                  }}
                                  className="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition rounded-none"
                                  title="Reset password"
                                >
                                  <KeyRound className="w-3.5 h-3.5" />
                                </button>
                              )}

                              {!isSelf && (
                                <button
                                  onClick={() => handleStatusToggle(target)}
                                  className={`p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 transition rounded-none ${
                                    target.status === 'active'
                                      ? 'text-slate-400 hover:text-amber-600'
                                      : 'text-slate-400 hover:text-emerald-600'
                                  }`}
                                  title={target.status === 'active' ? 'Deactivate account' : 'Activate account'}
                                >
                                  <UserX className="w-3.5 h-3.5" />
                                </button>
                              )}

                              {!isSelf && isSuperAdmin && (
                                <button
                                  onClick={() => setDeleteModalUser(target)}
                                  className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition rounded-none"
                                  title="Delete user"
                                >
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              )}
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
        )}
      </div>

      {/* Create / Edit User Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-4 sm:p-6 flex items-center justify-center">
          <div 
            className="w-full max-w-lg bg-white dark:bg-slate-900 shadow-2xl border border-slate-300 dark:border-slate-800 overflow-hidden my-auto rounded-none"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shrink-0">
              <h2 className="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100">
                {editingUser ? 'Edit Staff Member' : 'Add New Staff Member'}
              </h2>
              <button
                onClick={() => setIsModalOpen(false)}
                className="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition rounded-none"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleSaveUser} className="p-6 space-y-4">
              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  Full Name *
                </label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="e.g. Sarah Connor"
                  className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
                />
              </div>

              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  Email Address *
                </label>
                <input
                  type="email"
                  required
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  placeholder="e.g. sarah.connor@apextask.com"
                  className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
                />
              </div>

              {!editingUser && (
                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Initial Password *
                  </label>
                  <input
                    type="password"
                    required
                    value={formData.password}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    placeholder="Min. 6 characters"
                    className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
                  />
                </div>
              )}

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Assigned Role
                  </label>
                  <select
                    value={formData.role}
                    onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 font-semibold rounded-none"
                  >
                    <option value="staff">Staff Member</option>
                    <option value="admin">Administrator</option>
                    {isSuperAdmin && <option value="super_admin">Super Admin</option>}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Department
                  </label>
                  <select
                    value={formData.department}
                    onChange={(e) => setFormData({ ...formData, department: e.target.value })}
                    className="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
                  >
                    <option value="Engineering">Engineering</option>
                    <option value="Design">Design</option>
                    <option value="Quality Assurance">Quality Assurance</option>
                    <option value="Operations">Operations</option>
                    <option value="Product">Product</option>
                    <option value="Security">Security</option>
                  </select>
                </div>
              </div>

              <div className="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-none"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={savingUser}
                  className="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 shadow transition rounded-none"
                >
                  {savingUser ? 'Saving...' : editingUser ? 'Update Member' : 'Create Account'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Reset Password Modal */}
      {resetModalUser && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-4 sm:p-6 flex items-center justify-center">
          <div 
            className="w-full max-w-md bg-white dark:bg-slate-900 shadow-2xl border border-slate-300 dark:border-slate-800 overflow-hidden my-auto rounded-none"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shrink-0">
              <div className="flex items-center gap-2">
                <KeyRound className="w-5 h-5 text-amber-500" />
                <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                  Reset User Password
                </h2>
              </div>
              <button
                onClick={() => setResetModalUser(null)}
                className="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-none"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleResetPassword} className="p-6 space-y-4">
              <p className="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                Enter a new temporary or permanent password for <strong>{resetModalUser.name}</strong> ({resetModalUser.email}).
              </p>

              <div>
                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                  New Password *
                </label>
                <input
                  type="password"
                  required
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  placeholder="Min. 6 characters"
                  className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
                />
              </div>

              <div className="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <button
                  type="button"
                  onClick={() => setResetModalUser(null)}
                  className="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-none"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={savingPassword}
                  className="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 disabled:bg-amber-400 shadow transition rounded-none"
                >
                  {savingPassword ? 'Resetting...' : 'Set New Password'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Delete User Confirmation Modal */}
      <ConfirmModal
        isOpen={Boolean(deleteModalUser)}
        title="Delete User Account?"
        message={`Are you sure you want to permanently delete "${deleteModalUser?.name}" (${deleteModalUser?.email})? All system associations will be unassigned.`}
        confirmText="Delete Account"
        isDestructive={true}
        loading={deletingUser}
        onConfirm={confirmDeleteUser}
        onCancel={() => setDeleteModalUser(null)}
      />
    </div>
  );
}
