import React, { useState } from 'react';
import { AuthProvider, useAuth } from './context/AuthContext';
import { ThemeProvider } from './context/ThemeContext';
import { ToastProvider } from './context/ToastContext';
import Header from './components/layout/Header';
import Sidebar from './components/layout/Sidebar';
import ToastContainer from './components/common/ToastContainer';
import Login from './components/auth/Login';
import Dashboard from './components/dashboard/Dashboard';
import TaskList from './components/tasks/TaskList';
import UserManagement from './components/users/UserManagement';
import SystemSettings from './components/settings/SystemSettings';

function AppContent() {
  const { user, loading } = useAuth();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [createTaskTrigger, setCreateTaskTrigger] = useState(false);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
        <div className="flex flex-col items-center gap-3">
          <div className="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
          <span className="text-xs font-semibold text-slate-600 dark:text-slate-300">
            Initializing ApexTask Workspace...
          </span>
        </div>
      </div>
    );
  }

  if (!user) {
    return <Login />;
  }

  const isSuperAdmin = user.role === 'super_admin';
  const isAdmin = user.role === 'admin';

  let currentTab = activeTab;
  if (currentTab === 'settings' && !isSuperAdmin) {
    currentTab = 'dashboard';
  }
  if (currentTab === 'users' && !isSuperAdmin && !isAdmin) {
    currentTab = 'dashboard';
  }

  const handleOpenCreateTask = () => {
    setActiveTab('tasks');
    setCreateTaskTrigger(true);
  };

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex transition-colors duration-200 w-full overflow-x-hidden">
      {/* Collapsible Sidebar */}
      <Sidebar
        isOpen={sidebarOpen}
        onClose={() => setSidebarOpen(false)}
        activeTab={currentTab}
        onSelectTab={(tab) => setActiveTab(tab)}
      />

      {/* Main Content Area - Full width fluid layout */}
      <div className="flex-1 flex flex-col min-w-0 lg:pl-64 w-full">
        {/* Sticky Header */}
        <Header
          onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
          activeTab={currentTab}
          onOpenCreateTask={handleOpenCreateTask}
        />

        {/* Full-width Responsive Body */}
        <main className="flex-1 p-4 sm:p-6 lg:p-8 w-full min-w-0">
          {currentTab === 'dashboard' && (
            <Dashboard onNavigateToTasks={() => setActiveTab('tasks')} />
          )}
          {currentTab === 'tasks' && (
            <TaskList
              openCreateTrigger={createTaskTrigger}
              onResetCreateTrigger={() => setCreateTaskTrigger(false)}
            />
          )}
          {currentTab === 'users' && <UserManagement />}
          {currentTab === 'settings' && <SystemSettings />}
        </main>
      </div>

      {/* Global Toast Alerts */}
      <ToastContainer />
    </div>
  );
}

export default function App() {
  return (
    <ThemeProvider>
      <ToastProvider>
        <AuthProvider>
          <AppContent />
        </AuthProvider>
      </ToastProvider>
    </ThemeProvider>
  );
}
