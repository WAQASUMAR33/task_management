import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useToast } from '../../context/ToastContext';
import { useTheme } from '../../context/ThemeContext';
import { 
  Zap, 
  Lock, 
  Mail, 
  ArrowRight, 
  ShieldCheck, 
  Sun, 
  Moon,
  FileCheck,
  FolderKanban,
  CheckCircle2
} from 'lucide-react';

export default function Login() {
  const { login } = useAuth();
  const { addToast } = useToast();
  const { theme, toggleTheme } = useTheme();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email.trim() || !password) {
      setError('Please provide both email and password.');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      await login(email.trim(), password);
      addToast('Authentication successful. Welcome to ApexTask!', 'success');
    } catch (err) {
      console.error('Login error:', err);
      setError(err.message);
      addToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen w-full flex flex-col justify-between bg-slate-900 dark:bg-slate-950 text-slate-100 relative overflow-hidden transition-colors duration-200">
      {/* Top Navbar */}
      <div className="flex items-center justify-between p-6 w-full max-w-7xl mx-auto relative z-10">
        <div className="flex items-center gap-2.5">
          <div className="w-9 h-9 bg-indigo-600 flex items-center justify-center text-white shadow-sm rounded-none">
            <Zap className="w-5 h-5 fill-current" />
          </div>
          <div>
            <span className="font-extrabold text-xl tracking-tight text-white">
              ApexTask
            </span>
            <span className="text-[10px] font-black px-1.5 py-0.2 ml-1.5 bg-indigo-600 text-white rounded-none">
              PRO
            </span>
          </div>
        </div>

        <button
          onClick={toggleTheme}
          className="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition rounded-none"
          title={`Switch to ${theme === 'dark' ? 'Light' : 'Dark'} mode`}
        >
          {theme === 'dark' ? (
            <Sun className="w-4 h-4 text-amber-400" />
          ) : (
            <Moon className="w-4 h-4 text-indigo-400" />
          )}
        </button>
      </div>

      {/* Login Card */}
      <div className="flex-1 flex items-center justify-center p-4 relative z-10 my-6">
        <div className="w-full max-w-md bg-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl rounded-none">
          <div className="text-center mb-6">
            <h1 className="text-2xl font-black tracking-tight text-white">
              Sign In to Workspace
            </h1>
            <p className="text-xs text-slate-400 mt-1">
              Role-Based Access Control • WAMP MySQL Connected
            </p>
          </div>

          {error && (
            <div className="mb-5 p-3 bg-rose-950/60 border border-rose-800 text-rose-300 text-xs flex items-center gap-2 rounded-none">
              <span className="w-2 h-2 bg-rose-500 shrink-0"></span>
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                Work Email Address
              </label>
              <div className="relative">
                <Mail className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="name@company.com"
                  className="w-full pl-10 pr-4 py-2 border border-slate-700 bg-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition rounded-none"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                Password
              </label>
              <div className="relative">
                <Lock className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full pl-10 pr-4 py-2 border border-slate-700 bg-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition rounded-none"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full mt-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow transition flex items-center justify-center gap-2 disabled:opacity-50 rounded-none"
            >
              {loading ? (
                <div className="w-4 h-4 border-2 border-white border-t-transparent animate-spin"></div>
              ) : (
                <>
                  <span>Sign In</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </form>
        </div>
      </div>

      {/* Footer Info */}
      <div className="p-4 border-t border-slate-800 text-center text-xs text-slate-500 relative z-10 flex flex-wrap justify-center gap-6">
        <span className="flex items-center gap-1.5">
          <ShieldCheck className="w-4 h-4 text-indigo-400" />
          Enterprise 3-Tier RBAC
        </span>
        <span className="flex items-center gap-1.5">
          <FileCheck className="w-4 h-4 text-indigo-400" />
          Up to 10MB Multi-File Attachments
        </span>
        <span className="flex items-center gap-1.5">
          <FolderKanban className="w-4 h-4 text-indigo-400" />
          Kanban, Grid & Table Pipelines
        </span>
      </div>
    </div>
  );
}
