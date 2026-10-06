import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { authApi } from '../../api';
import { X, User, Lock, Building, Check, ShieldCheck, UserCheck, KeyRound } from 'lucide-react';

const AVATAR_PALETTE = [
  '#4f46e5', '#6366f1', '#2563eb', '#0891b2', 
  '#0d9488', '#059669', '#16a34a', '#d97706', 
  '#dc2626', '#e11d48', '#7c3aed', '#9333ea', '#db2777'
];

export default function ProfileModal({ isOpen, onClose }) {
  const { user, updateProfile } = useAuth();
  const { addToast } = useToast();

  const [activeTab, setActiveTab] = useState('profile'); // 'profile' | 'password'

  // Profile Form State
  const [name, setName] = useState(user?.name || '');
  const [department, setDepartment] = useState(user?.department || '');
  const [avatarColor, setAvatarColor] = useState(user?.avatar_color || '#4f46e5');
  const [savingProfile, setSavingProfile] = useState(false);

  // Password Form State
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [savingPassword, setSavingPassword] = useState(false);

  if (!isOpen || !user) return null;

  const handleUpdateProfile = async (e) => {
    e.preventDefault();
    if (!name.trim()) {
      addToast('Name is required.', 'warning');
      return;
    }
    try {
      setSavingProfile(true);
      const res = await authApi.updateProfile({
        name: name.trim(),
        department: department.trim(),
        avatar_color: avatarColor
      });
      updateProfile(res.user);
      addToast('Profile updated successfully!', 'success');
      onClose();
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setSavingProfile(false);
    }
  };

  const handleChangePassword = async (e) => {
    e.preventDefault();
    if (!currentPassword || !newPassword) {
      addToast('Please fill out all password fields.', 'warning');
      return;
    }
    if (newPassword.length < 6) {
      addToast('New password must be at least 6 characters.', 'warning');
      return;
    }
    if (newPassword !== confirmPassword) {
      addToast('New passwords do not match.', 'warning');
      return;
    }

    try {
      setSavingPassword(true);
      const res = await authApi.changePassword({ currentPassword, newPassword });
      addToast(res.message, 'success');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      onClose();
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setSavingPassword(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm p-4 sm:p-6 flex items-center justify-center">
      <div 
        className="w-full max-w-lg bg-white dark:bg-slate-900 shadow-2xl border border-slate-300 dark:border-slate-800 overflow-hidden my-auto rounded-none"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shrink-0">
          <div className="flex items-center gap-3">
            <div 
              className="w-8 h-8 flex items-center justify-center text-white font-bold text-sm shadow-sm rounded-none"
              style={{ backgroundColor: avatarColor }}
            >
              {name ? name.charAt(0).toUpperCase() : user.name.charAt(0).toUpperCase()}
            </div>
            <div>
              <h2 className="text-base font-bold text-slate-900 dark:text-slate-100">
                Account Settings
              </h2>
              <p className="text-xs text-slate-500 dark:text-slate-400">
                {user.email} • <span className="capitalize font-semibold text-indigo-600 dark:text-indigo-400">{user.role.replace('_', ' ')}</span>
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-none"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Tab Switcher */}
        <div className="flex border-b border-slate-200 dark:border-slate-800 px-6 pt-3 gap-6 bg-slate-50 dark:bg-slate-900 text-xs font-semibold">
          <button
            onClick={() => setActiveTab('profile')}
            className={`pb-3 border-b-2 transition flex items-center gap-1.5 rounded-none ${
              activeTab === 'profile'
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            }`}
          >
            <User className="w-4 h-4" />
            Profile Details
          </button>
          <button
            onClick={() => setActiveTab('password')}
            className={`pb-3 border-b-2 transition flex items-center gap-1.5 rounded-none ${
              activeTab === 'password'
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            }`}
          >
            <KeyRound className="w-4 h-4" />
            Security & Password
          </button>
        </div>

        {/* Profile Tab Content */}
        {activeTab === 'profile' && (
          <form onSubmit={handleUpdateProfile} className="p-6 space-y-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Full Name *
              </label>
              <input
                type="text"
                required
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
              />
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Department / Team
              </label>
              <input
                type="text"
                value={department}
                onChange={(e) => setDepartment(e.target.value)}
                placeholder="e.g. Frontend Engineering"
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
              />
            </div>

            {/* Avatar Color Picker */}
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Avatar Accent Color
              </label>
              <div className="flex flex-wrap gap-2">
                {AVATAR_PALETTE.map((color) => (
                  <button
                    key={color}
                    type="button"
                    onClick={() => setAvatarColor(color)}
                    className="w-7 h-7 flex items-center justify-center text-white transition border border-black/10 rounded-none"
                    style={{ backgroundColor: color }}
                  >
                    {avatarColor === color && <Check className="w-4 h-4 stroke-[3]" />}
                  </button>
                ))}
              </div>
            </div>

            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
              <button
                type="button"
                onClick={onClose}
                disabled={savingProfile}
                className="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-none"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={savingProfile}
                className="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 shadow transition flex items-center gap-2 rounded-none"
              >
                {savingProfile ? 'Saving...' : 'Save Changes'}
              </button>
            </div>
          </form>
        )}

        {/* Password Tab Content */}
        {activeTab === 'password' && (
          <form onSubmit={handleChangePassword} className="p-6 space-y-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Current Password *
              </label>
              <input
                type="password"
                required
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
              />
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                New Password *
              </label>
              <input
                type="password"
                required
                minLength={6}
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                placeholder="Minimum 6 characters"
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
              />
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Confirm New Password *
              </label>
              <input
                type="password"
                required
                minLength={6}
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="Re-enter new password"
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 rounded-none"
              />
            </div>

            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
              <button
                type="button"
                onClick={onClose}
                disabled={savingPassword}
                className="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-none"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={savingPassword}
                className="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 shadow transition flex items-center gap-2 rounded-none"
              >
                {savingPassword ? 'Updating Password...' : 'Update Password'}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}
