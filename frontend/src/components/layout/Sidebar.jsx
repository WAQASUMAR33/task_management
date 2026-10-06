import React from 'react';
import { useAuth } from '../../context/AuthContext';
import { 
  LayoutDashboard, 
  CheckSquare, 
  Users, 
  Settings, 
  Zap, 
  X, 
  ShieldCheck, 
  Database,
  Building2,
  FolderKanban,
  CheckCircle2
} from 'lucide-react';

export default function Sidebar({ isOpen, onClose, activeTab, onSelectTab }) {
  const { user } = useAuth();
  const role = user?.role;

  const isSuperAdmin = role === 'super_admin';
  const isAdmin = role === 'admin';
  const isStaff = role === 'staff';

  const navItems = [
    {
      id: 'dashboard',
      label: 'Dashboard',
      description: 'Metrics, trends & activity',
      icon: LayoutDashboard,
      roles: ['super_admin', 'admin', 'staff']
    },
    {
      id: 'tasks',
      label: isStaff ? 'My Assigned Tasks' : 'All Tasks & Workflows',
      description: isStaff ? 'Your queue & deliverables' : 'Manage & assign workflows',
      icon: CheckSquare,
      roles: ['super_admin', 'admin', 'staff']
    },
    {
      id: 'users',
      label: isSuperAdmin ? 'Staff & Permissions' : 'Staff Directory',
      description: 'Role access & team members',
      icon: Users,
      roles: ['super_admin', 'admin']
    },
    {
      id: 'settings',
      label: 'System Settings',
      description: 'Global policies & quotas',
      icon: Settings,
      roles: ['super_admin']
    }
  ];

  return (
    <>
      {/* Mobile Backdrop */}
      {isOpen && (
        <div
          onClick={onClose}
          className="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden transition-opacity"
        />
      )}

      {/* Sidebar Container */}
      <aside
        className={`fixed top-0 bottom-0 left-0 z-40 w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 rounded-none ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Brand Header */}
        <div className="flex items-center justify-between h-16 px-5 border-b border-slate-200 dark:border-slate-800">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 bg-indigo-600 flex items-center justify-center text-white shadow-sm rounded-none">
              <Zap className="w-5 h-5 fill-current" />
            </div>
            <div>
              <div className="flex items-center gap-1.5">
                <span className="font-extrabold text-base tracking-tight text-slate-900 dark:text-slate-100">
                  ApexTask
                </span>
                <span className="text-[10px] font-black px-1.5 py-0.2 bg-indigo-600 text-white rounded-none">
                  PRO
                </span>
              </div>
              <p className="text-[10px] text-slate-400 font-medium">Enterprise RBAC Edition</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 lg:hidden rounded-none"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Current Active Workspace Card */}
        <div className="px-4 py-3 mx-3 mt-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 shadow-sm rounded-none">
          <div className="flex items-center gap-3">
            <div
              className="w-8 h-8 flex items-center justify-center text-white font-bold text-sm shadow-sm shrink-0 rounded-none"
              style={{ backgroundColor: user?.avatar_color || '#4f46e5' }}
            >
              {user?.name?.charAt(0).toUpperCase()}
            </div>
            <div className="overflow-hidden flex-1">
              <div className="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">
                {user?.name}
              </div>
              <div className="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold capitalize flex items-center gap-1 truncate">
                {isSuperAdmin && <ShieldCheck className="w-3 h-3 text-purple-500 shrink-0" />}
                <span>{role?.replace('_', ' ')}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Navigation Items */}
        <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <div className="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            Workspaces & Modules
          </div>
          {navItems.map((item) => {
            if (!item.roles.includes(role)) return null;
            const Icon = item.icon;
            const isActive = activeTab === item.id;

            return (
              <button
                key={item.id}
                onClick={() => {
                  onSelectTab(item.id);
                  if (window.innerWidth < 1024) onClose();
                }}
                className={`w-full group flex items-center gap-3 px-3.5 py-2.5 font-medium text-xs transition-colors text-left rounded-none ${
                  isActive
                    ? 'bg-indigo-600 text-white font-semibold shadow-sm'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100'
                }`}
              >
                <Icon className={`w-4 h-4 shrink-0 ${
                  isActive ? 'text-white' : 'text-slate-400 dark:text-slate-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400'
                }`} />
                <div className="flex-1 overflow-hidden">
                  <div className="truncate font-semibold">{item.label}</div>
                  <div className={`text-[10px] truncate ${isActive ? 'text-indigo-100' : 'text-slate-400 dark:text-slate-500'}`}>
                    {item.description}
                  </div>
                </div>
              </button>
            );
          })}
        </nav>

        {/* Database & Connection Info Footer */}
        <div className="p-3 mx-3 mb-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 space-y-1.5 rounded-none">
          <div className="flex items-center justify-between">
            <span className="font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <Database className="w-3.5 h-3.5 text-indigo-500" />
              WAMP MySQL
            </span>
            <span className="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
              <span className="w-1.5 h-1.5 bg-emerald-500"></span>
              Live
            </span>
          </div>
          <div className="text-[10px] text-slate-400 truncate">
            DB: <span className="font-mono text-slate-600 dark:text-slate-300">task_management</span>
          </div>
        </div>
      </aside>
    </>
  );
}
