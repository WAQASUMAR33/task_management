<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Rapid Track') }} - @yield('title', 'Task Management')</title>

    <!-- Rapid Track Brand Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Modern Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Rapid Track Next-Gen Design System -->
    <link rel="stylesheet" href="{{ asset('css/material.css') }}">
    @stack('styles')
</head>
<body>
    <div class="mui-layout">
        <!-- Modern Navigation Drawer -->
        <aside class="mui-drawer" id="mainDrawer">
            <div class="mui-drawer-header">
                <div class="logo-badge">
                    <span class="material-symbols-outlined" style="font-size: 24px;">task_alt</span>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h2>{{ config('app.name', 'Rapid Track') }}</h2>
                    <p>Enterprise Tasks</p>
                </div>
                <button class="mui-icon-btn mui-icon-btn-sm" id="closeDrawerBtn" style="display: none;" title="Close Menu">
                    <span class="material-symbols-outlined" style="font-size: 20px;">close</span>
                </button>
            </div>

            <nav class="mui-drawer-nav">
                <div class="mui-nav-category">Main Workspace</div>
                <a href="{{ route('dashboard') }}" class="mui-nav-item {{ request()->routeIs('dashboard') || request()->routeIs('home') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('tasks.index') }}" class="mui-nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">checklist</span>
                    <span>Tasks & Projects</span>
                </a>

                <a href="{{ route('sections.index') }}" class="mui-nav-item {{ request()->routeIs('sections.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">domain</span>
                    <span>{{ Auth::user()->isSuperAdmin() ? 'Unit Sections' : 'My Section' }}</span>
                </a>

                @if(Auth::user()->isAdmin())
                <div class="mui-nav-category">Management</div>
                <a href="{{ route('users.index') }}" class="mui-nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">group</span>
                    <span>{{ Auth::user()->isSuperAdmin() ? 'User Directory' : 'Section Team' }}</span>
                </a>
                @endif

                @if(Auth::user()->isSuperAdmin())
                <a href="{{ route('settings.index') }}" class="mui-nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">settings</span>
                    <span>System Settings</span>
                </a>
                @endif

                <div class="mui-nav-category">Preferences</div>
                <a href="{{ route('profile.show') }}" class="mui-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined">person</span>
                    <span>My Profile</span>
                </a>
            </nav>

            <div class="mui-drawer-footer">
                <div class="mui-user-card">
                    <div class="mui-avatar mui-avatar-sm" style="background-color: {{ Auth::user()->avatar_color }};">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 0.85rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--mui-on-surface);">{{ Auth::user()->name }}</div>
                        <div style="font-size: 0.72rem; color: var(--mui-on-surface-variant); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ Auth::user()->role_title }}{{ Auth::user()->section ? ' • ' . Auth::user()->section->name : '' }}
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="mui-icon-btn mui-icon-btn-sm" title="Sign Out" style="color: var(--mui-error);">
                            <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Mobile Drawer Backdrop Overlay -->
        <div class="mui-drawer-backdrop" id="drawerBackdrop" onclick="closeMobileDrawer()"></div>

        <!-- Main Content Viewport -->
        <main class="mui-main-content">
            <!-- Top Header App Bar -->
            <header class="mui-app-bar">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <button class="mui-icon-btn" id="drawerToggle" aria-label="Toggle Menu" style="display: none;">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div>
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--mui-on-surface-variant); font-weight: 500;">
                            <span>Rapid Track</span>
                            <span>/</span>
                            <span style="color: var(--mui-primary); font-weight: 600;">@yield('title', 'Workspace')</span>
                        </div>
                        <h1 style="font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em; color: var(--mui-on-surface); line-height: 1.2;">@yield('page_title', 'Dashboard')</h1>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <!-- Quick Search Trigger -->
                    <button type="button" class="mui-btn mui-btn-outlined mui-btn-sm quick-search-trigger" onclick="openCommandPalette()" style="height: 36px; padding: 0 12px; gap: 8px; border-radius: var(--mui-radius-full); background: #ffffff; color: var(--mui-on-surface-variant); font-weight: 500; font-size: 0.8125rem;">
                        <span class="material-symbols-outlined" style="font-size: 18px; color: var(--mui-on-surface-variant);">search</span>
                        <span class="quick-search-label">Search anything...</span>
                        <kbd style="font-family: inherit; font-size: 0.6875rem; background: var(--mui-surface-container); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--mui-outline); color: var(--mui-on-surface-variant);">Ctrl K</kbd>
                    </button>

                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('tasks.index', ['create' => '1']) }}" class="mui-btn mui-btn-gradient mui-btn-sm" style="height: 36px;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                        <span>New Task</span>
                    </a>
                    @endif

                    <!-- User Role Pill -->
                    <span class="mui-chip {{ Auth::user()->role_badge_class }}" style="display: none; @media(min-width: 768px){display: inline-flex;}">
                        <span class="material-symbols-outlined" style="font-size: 14px;">
                            {{ Auth::user()->isSuperAdmin() ? 'admin_panel_settings' : (Auth::user()->isAdmin() ? 'shield' : 'person') }}
                        </span>
                        {{ Auth::user()->role_title }}
                    </span>

                    <a href="{{ route('profile.show') }}" class="mui-avatar mui-avatar-sm" style="background-color: {{ Auth::user()->avatar_color }}; text-decoration: none;" title="Account Profile">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </a>
                </div>
            </header>

            <!-- Page Body Content -->
            <div class="mui-page-body">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Command Palette / Quick Search Dialog (Ctrl + K) -->
    <div class="command-palette-backdrop" id="commandPalette" onclick="if(event.target === this) closeCommandPalette()">
        <div class="command-palette-modal">
            <div style="display: flex; align-items: center; gap: 12px; padding: 18px 24px; border-bottom: 1px solid var(--mui-outline);">
                <span class="material-symbols-outlined" style="font-size: 24px; color: var(--mui-primary);">search</span>
                <input type="text" id="commandInput" placeholder="Quickly search tasks, sections, or pages..." style="flex: 1; border: none; outline: none; font-size: 1rem; font-family: var(--mui-font); color: var(--mui-on-surface);" oninput="handleCommandSearch(this.value)">
                <span style="font-size: 0.75rem; background: var(--mui-surface-container); padding: 4px 8px; border-radius: 6px; color: var(--mui-on-surface-variant);">ESC</span>
            </div>
            <div id="commandResults" style="max-height: 360px; overflow-y: auto; padding: 12px 16px;">
                <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-on-surface-variant); padding: 6px 12px;">Quick Navigation</div>
                <a href="{{ route('dashboard') }}" class="mui-nav-item" style="border-radius: var(--mui-radius-md);">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('tasks.index') }}" class="mui-nav-item" style="border-radius: var(--mui-radius-md);">
                    <span class="material-symbols-outlined">checklist</span>
                    <span>Tasks & Deliverables</span>
                </a>
                <a href="{{ route('sections.index') }}" class="mui-nav-item" style="border-radius: var(--mui-radius-md);">
                    <span class="material-symbols-outlined">domain</span>
                    <span>Operational Sections</span>
                </a>
                @if(Auth::user()->isAdmin())
                <a href="{{ route('users.index') }}" class="mui-nav-item" style="border-radius: var(--mui-radius-md);">
                    <span class="material-symbols-outlined">group</span>
                    <span>Team Management</span>
                </a>
                @endif
                <a href="{{ route('profile.show') }}" class="mui-nav-item" style="border-radius: var(--mui-radius-md);">
                    <span class="material-symbols-outlined">person</span>
                    <span>Profile Settings</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    @if(session('success'))
    <div class="mui-toast mui-toast-success" id="toastNotification">
        <span class="material-symbols-outlined">check_circle</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error') || $errors->any())
    <div class="mui-toast mui-toast-error" id="toastNotification">
        <span class="material-symbols-outlined">error</span>
        <span>{{ session('error') ?? $errors->first() }}</span>
    </div>
    @endif

    <!-- Global Interactive Scripts -->
    <script>
        // Drawer toggle for mobile
        const drawerToggle = document.getElementById('drawerToggle');
        const mainDrawer = document.getElementById('mainDrawer');
        const closeDrawerBtn = document.getElementById('closeDrawerBtn');

        function checkMobileDrawer() {
            if (window.innerWidth <= 900) {
                if (drawerToggle) drawerToggle.style.display = 'inline-flex';
                if (closeDrawerBtn) closeDrawerBtn.style.display = 'inline-flex';
            } else {
                if (drawerToggle) drawerToggle.style.display = 'none';
                if (closeDrawerBtn) closeDrawerBtn.style.display = 'none';
                closeMobileDrawer();
            }
        }
        window.addEventListener('resize', checkMobileDrawer);
        checkMobileDrawer();

        function openMobileDrawer() {
            if (mainDrawer) mainDrawer.classList.add('open');
            const backdrop = document.getElementById('drawerBackdrop');
            if (backdrop) backdrop.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileDrawer() {
            if (mainDrawer) mainDrawer.classList.remove('open');
            const backdrop = document.getElementById('drawerBackdrop');
            if (backdrop) backdrop.classList.remove('open');
            document.body.style.overflow = '';
        }

        if (drawerToggle) drawerToggle.addEventListener('click', openMobileDrawer);
        if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeMobileDrawer);

        // Dialog Open/Close Helpers
        function openDialog(id) {
            const dialog = document.getElementById(id);
            if (dialog) {
                dialog.classList.add('open');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeDialog(id) {
            const dialog = document.getElementById(id);
            if (dialog) {
                dialog.classList.remove('open');
                document.body.style.overflow = '';
            }
        }

        window.openModal = openDialog;
        window.closeModal = closeDialog;

        // Command Palette (Ctrl + K)
        function openCommandPalette() {
            const cp = document.getElementById('commandPalette');
            if (cp) {
                cp.classList.add('open');
                document.getElementById('commandInput').focus();
            }
        }

        function closeCommandPalette() {
            const cp = document.getElementById('commandPalette');
            if (cp) {
                cp.classList.remove('open');
            }
        }

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openCommandPalette();
            }
            if (e.key === 'Escape') {
                closeCommandPalette();
                document.querySelectorAll('.mui-dialog-backdrop.open').forEach(d => d.classList.remove('open'));
                document.body.style.overflow = '';
            }
        });

        // Dynamic Live Search for Command Palette
        let commandDebounce = null;
        let initialCommandHTML = '';
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('commandResults');
            if (container) initialCommandHTML = container.innerHTML;
        });

        function handleCommandSearch(query) {
            clearTimeout(commandDebounce);
            const container = document.getElementById('commandResults');
            if (!container) return;

            query = query.trim();
            if (query.length === 0) {
                container.innerHTML = initialCommandHTML;
                return;
            }

            container.innerHTML = `
                <div style="padding: 24px; text-align: center; color: var(--mui-on-surface-variant); font-size: 0.85rem;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">sync</span>
                    <div style="margin-top: 6px;">Searching deliverables, sections & team...</div>
                </div>
            `;

            commandDebounce = setTimeout(() => {
                fetch(`/quick-search?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        let html = '';

                        // 1. Deliverables
                        if (data.tasks && data.tasks.length > 0) {
                            html += `<div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-primary); padding: 8px 12px 4px;">Deliverables (${data.tasks.length})</div>`;
                            data.tasks.forEach(t => {
                                html += `
                                    <a href="${t.url}" class="mui-nav-item" style="border-radius: var(--mui-radius-md); padding: 8px 12px; margin-bottom: 4px; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: var(--mui-primary);">assignment</span>
                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.875rem;">${t.title}</span>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                            ${t.section_code ? `<span class="mui-chip" style="font-size: 0.68rem; padding: 1px 6px;">${t.section_code}</span>` : ''}
                                            <span class="mui-chip" style="font-size: 0.68rem; padding: 1px 6px;">${t.status_label}</span>
                                        </div>
                                    </a>
                                `;
                            });
                        }

                        // 2. Sections
                        if (data.sections && data.sections.length > 0) {
                            html += `<div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-primary); padding: 12px 12px 4px;">Unit Sections (${data.sections.length})</div>`;
                            data.sections.forEach(s => {
                                html += `
                                    <a href="${s.url}" class="mui-nav-item" style="border-radius: var(--mui-radius-md); padding: 8px 12px; margin-bottom: 4px; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #0284c7;">domain</span>
                                            <span style="font-size: 0.875rem;">${s.name}</span>
                                        </div>
                                        <span class="mui-chip" style="font-size: 0.68rem; padding: 1px 6px;">${s.code}</span>
                                    </a>
                                `;
                            });
                        }

                        // 3. Team Members
                        if (data.users && data.users.length > 0) {
                            html += `<div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--mui-primary); padding: 12px 12px 4px;">Team Specialists (${data.users.length})</div>`;
                            data.users.forEach(u => {
                                html += `
                                    <a href="{{ route('users.index') }}?search=${encodeURIComponent(u.name)}" class="mui-nav-item" style="border-radius: var(--mui-radius-md); padding: 8px 12px; margin-bottom: 4px;">
                                        <div class="mui-avatar mui-avatar-sm" style="background-color: ${u.avatar_color}; width: 24px; height: 24px; font-size: 0.7rem;">
                                            ${u.name.substring(0, 1).toUpperCase()}
                                        </div>
                                        <div style="min-width: 0;">
                                            <div style="font-size: 0.85rem; font-weight: 600;">${u.name}</div>
                                            <div style="font-size: 0.72rem; color: var(--mui-on-surface-variant);">${u.department || u.role_title}</div>
                                        </div>
                                    </a>
                                `;
                            });
                        }

                        if (!html) {
                            html = `
                                <div style="text-align: center; padding: 30px 16px; color: var(--mui-on-surface-variant); font-size: 0.875rem;">
                                    <span class="material-symbols-outlined" style="font-size: 32px; color: var(--mui-on-surface-muted); margin-bottom: 6px;">search_off</span>
                                    <div>No matches found for "${query}"</div>
                                </div>
                            `;
                        }

                        container.innerHTML = html;
                    })
                    .catch(() => {
                        container.innerHTML = `<div style="text-align: center; padding: 20px; color: var(--mui-error);">Error searching. Try again.</div>`;
                    });
            }, 250);
        }

        // Dynamic Toast Helper
        function showToast(message, type = 'success') {
            const existing = document.getElementById('toastNotification');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.id = 'toastNotification';
            toast.className = `mui-toast mui-toast-${type}`;
            toast.innerHTML = `
                <span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : 'error'}</span>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(20px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // Auto dismiss initial toast
        const initialToast = document.getElementById('toastNotification');
        if (initialToast) {
            setTimeout(() => {
                initialToast.style.opacity = '0';
                initialToast.style.transform = 'translateY(20px)';
                initialToast.style.transition = 'all 0.3s ease';
                setTimeout(() => initialToast.remove(), 300);
            }, 4000);
        }
    </script>
    @stack('scripts')
</body>
</html>
