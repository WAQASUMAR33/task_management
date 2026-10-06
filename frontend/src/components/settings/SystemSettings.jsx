import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { settingsApi } from '../../api';
import { 
  Settings, 
  Save, 
  Shield, 
  HardDrive, 
  Bell, 
  Database, 
  CheckCircle2, 
  Server, 
  Cpu, 
  Lock, 
  Layers 
} from 'lucide-react';

export default function SystemSettings() {
  const { user } = useAuth();
  const { addToast } = useToast();

  const [settings, setSettings] = useState({
    app_name: 'ApexTask Pro',
    company_name: 'Apex Global Enterprise',
    allow_file_uploads: 'true',
    max_file_size_mb: '10',
    default_user_role: 'staff',
    task_notifications_enabled: 'true'
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    settingsApi.get()
      .then(data => {
        setSettings(prev => ({ ...prev, ...data }));
      })
      .catch(err => {
        console.error('Failed to load settings:', err);
      })
      .finally(() => setLoading(false));
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);
      await settingsApi.update(settings);
      addToast('Global system parameters saved to WAMP MySQL.', 'success');
    } catch (err) {
      addToast(err.message, 'error');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center py-24 gap-3">
        <div className="w-10 h-10 border-4 border-indigo-600 border-t-transparent animate-spin"></div>
        <span className="text-xs font-semibold text-slate-500">Loading system parameters...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in w-full">
      {/* Header */}
      <div>
        <h2 className="text-2xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">
          Super Admin Global Configuration
        </h2>
        <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
          Full system authority for global application policies, storage quotas, role constraints, and WAMP MySQL engine settings.
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Workspace Identity */}
        <div className="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 rounded-none">
          <div className="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div className="p-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-none">
              <Layers className="w-4 h-4" />
            </div>
            <div>
              <h3 className="font-bold text-sm sm:text-base text-slate-900 dark:text-slate-100">
                Workspace Identity & Display
              </h3>
              <p className="text-[11px] text-slate-400">Branding shown across employee portals</p>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Application Display Title
              </label>
              <input
                type="text"
                value={settings.app_name}
                onChange={(e) => setSettings({ ...settings, app_name: e.target.value })}
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 font-semibold rounded-none"
              />
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Organization Entity Name
              </label>
              <input
                type="text"
                value={settings.company_name}
                onChange={(e) => setSettings({ ...settings, company_name: e.target.value })}
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 font-semibold rounded-none"
              />
            </div>
          </div>
        </div>

        {/* File Attachments & Upload Limits */}
        <div className="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 rounded-none">
          <div className="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div className="p-2 bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 rounded-none">
              <HardDrive className="w-4 h-4" />
            </div>
            <div>
              <h3 className="font-bold text-sm sm:text-base text-slate-900 dark:text-slate-100">
                File Storage & Upload Quota Policies
              </h3>
              <p className="text-[11px] text-slate-400">Controls for deliverables, documents, and disk constraints</p>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                File Upload Submissions
              </label>
              <select
                value={settings.allow_file_uploads}
                onChange={(e) => setSettings({ ...settings, allow_file_uploads: e.target.value })}
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 font-semibold rounded-none"
              >
                <option value="true">Enabled (Allow All Staff to Attach Files)</option>
                <option value="false">Disabled (Read-Only Mode)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                Maximum File Limit (Megabytes)
              </label>
              <input
                type="number"
                min="1"
                max="50"
                value={settings.max_file_size_mb}
                onChange={(e) => setSettings({ ...settings, max_file_size_mb: e.target.value })}
                className="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:outline-none focus:border-indigo-600 font-semibold rounded-none"
              />
              <span className="text-[10px] text-slate-400 mt-1 block">Default: 10MB per file</span>
            </div>
          </div>
        </div>

        {/* Database & Infrastructure Diagnostics Card */}
        <div className="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 rounded-none">
          <div className="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div className="p-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-none">
              <Database className="w-4 h-4" />
            </div>
            <div>
              <h3 className="font-bold text-sm sm:text-base text-slate-900 dark:text-slate-100">
                Remote MySQL Database Diagnostics
              </h3>
              <p className="text-[11px] text-slate-400">Underlying relational engine specifications</p>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div className="p-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-none">
              <span className="text-slate-400 text-[10px] uppercase font-bold block mb-1">Server Host</span>
              <span className="font-mono font-bold text-slate-900 dark:text-slate-100">195.35.59.84:3306</span>
            </div>

            <div className="p-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-none">
              <span className="text-slate-400 text-[10px] uppercase font-bold block mb-1">Database Name</span>
              <span className="font-mono font-bold text-indigo-600 dark:text-indigo-400">u889453186_task_mngmnt</span>
            </div>

            <div className="p-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-none">
              <span className="text-slate-400 text-[10px] uppercase font-bold block mb-1">Driver Pool</span>
              <span className="font-mono font-bold text-slate-900 dark:text-slate-100">mysql2 / 15 conns</span>
            </div>

            <div className="p-3.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-none">
              <span className="text-slate-400 text-[10px] uppercase font-bold block mb-1">Status</span>
              <span className="inline-flex items-center gap-1 font-bold text-emerald-600 dark:text-emerald-400">
                <span className="w-1.5 h-1.5 bg-emerald-500"></span>
                Connected
              </span>
            </div>
          </div>
        </div>

        {/* Submit Button */}
        <div className="flex items-center justify-end">
          <button
            type="submit"
            disabled={saving}
            className="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm shadow transition rounded-none"
          >
            <Save className="w-4 h-4" />
            <span>{saving ? 'Saving System Changes...' : 'Save Configuration'}</span>
          </button>
        </div>
      </form>
    </div>
  );
}
