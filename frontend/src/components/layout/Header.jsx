import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useTheme } from '../../context/ThemeContext';
import ProfileModal from '../profile/ProfileModal';
import { 
  Menu, 
  Sun, 
  Moon, 
  ShieldCheck, 
  UserCheck, 
  User, 
  LogOut, 
  Plus, 
  Search, 
  Bell,
  Sparkles,
  ChevronDown
} from 'lucide-react';

export default function Header({ onToggleSidebar, activeTab, onOpenCreateTask }) {
  const { user, logout } = useAuth();
  const { theme, toggleTheme } = useTheme();
  const [profileOpen, setProfileOpen] = useState(false);

  const isSuperAdmin = user?.role === 'super_admin';
  const isAdmin = user?.role === 'admin';
  const isAdminOrSuper = isSuperAdmin || isAdmin;

  const getRoleBadge = (role) => {
    switch (role) {
      case 'super_admin':
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800 rounded-none">
            <ShieldCheck className="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
            Super Admin
          </span>
        );
      case 'admin':
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-none">
            <UserCheck className="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
            Admin
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-none">
            <User className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
            Staff Member
          </span>
        );
    }
  };

  const getTabTitle = (tab) => {
    switch (tab) {
      case 'dashboard': return 'Operational Analytics';
      case 'tasks': return 'Task Workflows & Deliverables';
      case 'users': return 'Staff & Access Management';
      case 'settings': return 'Enterprise System Settings';
      default: return 'Workspace Overview';
    }
  };

  return (
    <>
      <header className="sticky top-0 z-30 flex items-center justify-between h-16 px-4 md:px-8 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 transition-colors">
        {/* Left Side: Toggle & Title */}
        <div className="flex items-center gap-3">
          <button
            onClick={onToggleSidebar}
            className="p-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition rounded-none"
            title="Toggle Navigation Menu"
          >
            <Menu className="w-5 h-5" />
          </button>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 tracking-tight leading-none">
                {getTabTitle(activeTab)}
              </h1>
            </div>
            <p className="text-[11px] text-slate-400 dark:text-slate-500 hidden sm:block mt-0.5">
              {user?.department ? `${user.department} Workspace` : 'ApexTask Pro'} • WAMP MySQL
            </p>
          </div>
        </div>

        {/* Right Side: Quick Action, Role, Theme & Profile */}
        <div className="flex items-center gap-2 sm:gap-3">
          {/* Quick Create Task button for Admins / Super Admins */}
          {isAdminOrSuper && onOpenCreateTask && (
            <button
              onClick={onOpenCreateTask}
              className="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow transition rounded-none"
            >
              <Plus className="w-4 h-4" />
              <span>New Task</span>
            </button>
          )}

          {/* Role Badge */}
          {user && getRoleBadge(user.role)}

          {/* Theme Toggle */}
          <button
            onClick={toggleTheme}
            className="p-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition border border-slate-200 dark:border-slate-700 rounded-none"
            title={`Switch to ${theme === 'dark' ? 'Light' : 'Dark'} Mode`}
          >
            {theme === 'dark' ? (
              <Sun className="w-4 h-4 text-amber-400" />
            ) : (
              <Moon className="w-4 h-4 text-indigo-600" />
            )}
          </button>

          {/* User Profile Avatar & Dropdown Trigger */}
          {user && (
            <div className="flex items-center gap-2 pl-2 border-l border-slate-200 dark:border-slate-800">
              <button
                onClick={() => setProfileOpen(true)}
                className="flex items-center gap-2 p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-left group rounded-none"
                title="Account & Security Settings"
              >
                <div
                  className="w-7 h-7 flex items-center justify-center text-white font-bold text-xs shrink-0 rounded-none"
                  style={{ backgroundColor: user.avatar_color || '#4f46e5' }}
                >
                  {user.name.charAt(0).toUpperCase()}
                </div>
                <div className="hidden lg:block">
                  <div className="text-xs font-bold text-slate-900 dark:text-slate-100 leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition truncate max-w-[120px]">
                    {user.name}
                  </div>
                  <div className="text-[10px] text-slate-400 truncate max-w-[120px]">
                    {user.department || 'Settings'}
                  </div>
                </div>
                <ChevronDown className="w-3.5 h-3.5 text-slate-400 hidden lg:block" />
              </button>

              {/* Sign Out */}
              <button
                onClick={logout}
                className="p-2 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition rounded-none"
                title="Sign Out"
              >
                <LogOut className="w-4 h-4" />
              </button>
            </div>
          )}
        </div>
      </header>

      {/* Profile & Security Modal */}
      <ProfileModal
        isOpen={profileOpen}
        onClose={() => setProfileOpen(false)}
      />
    </>
  );
}
