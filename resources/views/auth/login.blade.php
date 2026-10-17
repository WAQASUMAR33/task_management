<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In &mdash; {{ config('app.name', 'Rapid Track') }}</title>

    <!-- Brand Favicons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Typography & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="{{ asset('css/material.css') }}">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #e0e7ff;
            --accent: #7c3aed;
            --slate-900: #0f172a;
            --slate-800: #1e293b;
            --slate-700: #334155;
            --slate-600: #475569;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-main);
            background-color: #0b0f19;
            color: var(--slate-900);
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 20px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            vertical-align: middle;
        }

        /* Container Layout */
        .auth-viewport {
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            position: relative;
        }

        /* Left Brand Side */
        .brand-panel {
            flex: 1.15;
            position: relative;
            background: linear-gradient(145deg, #090d16 0%, #0f172a 45%, #1e1b4b 100%);
            padding: 60px 72px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            color: #ffffff;
        }

        /* Dynamic Mesh Background Accents */
        .brand-panel::before {
            content: '';
            position: absolute;
            top: -15%;
            left: -10%;
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.38) 0%, rgba(79, 70, 229, 0) 70%);
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
            animation: pulseOrb 12s ease-in-out infinite alternate;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.3) 0%, rgba(124, 58, 237, 0) 70%);
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none;
            animation: pulseOrb 15s ease-in-out infinite alternate-reverse;
        }

        @keyframes pulseOrb {
            0% { transform: scale(1) translate(0, 0); opacity: 0.8; }
            100% { transform: scale(1.15) translate(30px, -20px); opacity: 1; }
        }

        /* Subtle Background Grid Pattern */
        .bg-grid-overlay {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            opacity: 0.6;
        }

        .brand-header {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo-badge {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px -4px rgba(79, 70, 229, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .brand-title {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hero Content */
        .brand-body {
            position: relative;
            z-index: 2;
            margin: auto 0;
            max-width: 540px;
            padding: 40px 0;
        }

        .brand-headline {
            font-size: 3.25rem;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #ffffff;
        }

        .brand-headline span {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-footer {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #64748b;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Right Form Side */
        .form-panel {
            flex: 0.95;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px 64px;
            position: relative;
        }

        .form-container {
            width: 100%;
            max-width: 440px;
        }

        /* Mobile Brand Header */
        .mobile-brand {
            display: none;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .mobile-logo-badge {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 8px 16px -2px rgba(79, 70, 229, 0.4);
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-heading {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.03em;
            margin-bottom: 8px;
        }

        .form-subheading {
            font-size: 0.925rem;
            color: var(--slate-500);
            font-weight: 500;
        }

        /* Alert Banners */
        .alert-banner {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.875rem;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
        }

        .alert-error .material-symbols-outlined {
            color: #dc2626;
            font-size: 20px;
        }

        .alert-info {
            background-color: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1e40af;
        }

        .alert-info .material-symbols-outlined {
            color: #2563eb;
            font-size: 20px;
        }

        /* Modern Input Styles */
        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--slate-700);
            margin-bottom: 8px;
            letter-spacing: -0.01em;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-lead {
            position: absolute;
            left: 16px;
            color: var(--slate-400);
            pointer-events: none;
            transition: color 0.2s ease;
            font-size: 20px;
        }

        .custom-input {
            width: 100%;
            height: 50px;
            padding: 0 16px 0 48px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--slate-900);
            background-color: #ffffff;
            border: 1.5px solid var(--slate-200);
            border-radius: 12px;
            outline: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            font-weight: 500;
        }

        .custom-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .custom-input:hover {
            border-color: #cbd5e1;
        }

        .custom-input:focus {
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        .input-wrapper:focus-within .input-icon-lead {
            color: var(--primary);
        }

        /* Password specific styling */
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            height: 34px;
            width: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            border-radius: 8px;
            color: var(--slate-400);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .password-toggle-btn:hover {
            color: var(--slate-700);
            background-color: var(--slate-100);
        }

        /* Options Row */
        .form-options-row {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
            font-size: 0.875rem;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            color: var(--slate-600);
            font-weight: 500;
        }

        .custom-checkbox {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 1.5px solid var(--slate-300);
            border-radius: 6px;
            outline: none;
            cursor: pointer;
            display: grid;
            place-content: center;
            transition: all 0.15s ease;
            background-color: #ffffff;
        }

        .custom-checkbox::before {
            content: "";
            width: 10px;
            height: 10px;
            transform: scale(0);
            transition: 120ms transform ease-in-out;
            box-shadow: inset 1em 1em #ffffff;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }

        .custom-checkbox:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .custom-checkbox:checked::before {
            transform: scale(1);
        }

        .custom-checkbox:focus-visible {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #7c3aed 100%);
            color: #ffffff;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.45);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.6s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -5px rgba(79, 70, 229, 0.55);
            background: linear-gradient(135deg, #4338ca 0%, #4f46e5 50%, #6d28d9 100%);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 6px 14px -3px rgba(79, 70, 229, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .brand-panel {
                padding: 40px;
            }
            .brand-headline {
                font-size: 2.5rem;
            }
            .form-panel {
                padding: 40px;
            }
        }

        @media (max-width: 860px) {
            .auth-viewport {
                flex-direction: column;
                background-color: #0b0f19;
            }

            .brand-panel {
                display: none;
            }

            .form-panel {
                flex: 1;
                min-height: 100vh;
                padding: 32px 24px;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            }

            .mobile-brand {
                display: flex;
            }

            .form-container {
                max-width: 400px;
                margin: auto;
            }
        }
    </style>
</head>
<body>
    <div class="auth-viewport">
        <!-- Left Brand Experience Panel -->
        <div class="brand-panel">
            <div class="bg-grid-overlay"></div>

            <div class="brand-header">
                <div class="brand-logo-badge">
                    <span class="material-symbols-outlined" style="font-size: 28px; color: #ffffff;">task_alt</span>
                </div>
                <div>
                    <span class="brand-title">{{ config('app.name', 'Rapid Track') }}</span>
                </div>
            </div>

            <div class="brand-body">
                <h1 class="brand-headline">
                    Task Management <span>System</span>
                </h1>
            </div>

            <div class="brand-footer">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'Rapid Track') }}. All rights reserved.</span>
            </div>
        </div>

        <!-- Right Sign In Panel -->
        <div class="form-panel">
            <div class="form-container">
                <!-- Mobile brand icon when left panel is hidden -->
                <div class="mobile-brand">
                    <div class="mobile-logo-badge">
                        <span class="material-symbols-outlined" style="font-size: 24px;">task_alt</span>
                    </div>
                    <div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--slate-900);">{{ config('app.name', 'Rapid Track') }}</div>
                        <div style="font-size: 0.8rem; color: var(--slate-500); font-weight: 500;">Task Management System</div>
                    </div>
                </div>

                <div class="form-header">
                    <h2 class="form-heading">Sign In</h2>
                    <p class="form-subheading">Enter your credentials to access your account</p>
                </div>

                @if($errors->any())
                <div class="alert-banner alert-error" role="alert">
                    <span class="material-symbols-outlined">error</span>
                    <div>
                        <strong>Authentication failed</strong>
                        <div style="margin-top: 2px;">{{ $errors->first() }}</div>
                    </div>
                </div>
                @endif

                @if(session('info'))
                <div class="alert-banner alert-info" role="status">
                    <span class="material-symbols-outlined">info</span>
                    <div>{{ session('info') }}</div>
                </div>
                @endif

                <!-- Authentication Form -->
                <form action="{{ route('login.post') }}" method="POST" id="loginForm" autocomplete="on">
                    @csrf

                    <!-- Work Email: Material Design 3 Outlined Text Field -->
                    <div class="md-outlined-text-field has-leading-icon">
                        <div class="md-field-container">
                            <span class="material-symbols-outlined md-field-leading-icon">mail</span>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="md-field-input"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder=" "
                            >
                            <label class="md-floating-label" for="email">Work Email Address</label>
                        </div>
                    </div>

                    <!-- Password: Material Design 3 Outlined Text Field with Toggle -->
                    <div class="md-outlined-text-field has-leading-icon has-trailing-icon">
                        <div class="md-field-container">
                            <span class="material-symbols-outlined md-field-leading-icon">lock</span>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="md-field-input"
                                required
                                autocomplete="current-password"
                                placeholder=" "
                            >
                            <label class="md-floating-label" for="password">Password</label>
                            <button
                                type="button"
                                class="md-field-trailing-icon"
                                id="passwordToggleBtn"
                                aria-label="Toggle password visibility"
                                onclick="togglePasswordVisibility()"
                            >
                                <span class="material-symbols-outlined" id="togglePasswordIcon" style="font-size: 20px;">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Device: Material Design 3 Checkbox -->
                    <div style="margin-bottom: 24px;">
                        <label class="md-checkbox-wrapper" for="remember">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span class="md-checkbox-label">Keep me signed in</span>
                        </label>
                    </div>

                    <!-- Submit: Material Design 3 Filled Button -->
                    <button type="submit" class="md-btn md-filled-button mui-btn-gradient" id="submitBtn" style="width: 100%; height: 48px; border-radius: var(--md-sys-shape-corner-medium); font-size: 0.95rem;">
                        <span class="material-symbols-outlined" style="font-size: 20px;">login</span>
                        <span id="submitBtnText">Sign In</span>
                    </button>
                </form>

                <!-- Quick Persona Instant Access for Testing / Demo with Material 3 Chips -->
                <div style="margin-top: 28px; padding-top: 24px; border-top: 1px solid var(--md-sys-color-outline-variant); text-align: center;">
                    <div style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--md-sys-color-on-surface-variant); margin-bottom: 12px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 16px; color: var(--md-sys-color-primary);">bolt</span>
                        <span>Instant Persona Access (Quick Demo)</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                        <a href="{{ route('login.quick', ['role' => 'super_admin']) }}" class="md-chip" style="background-color: var(--md-sys-color-tertiary-container); color: var(--md-sys-color-on-tertiary-container); border-color: #ddd6fe; justify-content: center; height: 36px; text-decoration: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px;">shield</span>
                            <span>Super Admin</span>
                        </a>
                        <a href="{{ route('login.quick', ['role' => 'admin']) }}" class="md-chip" style="background-color: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); border-color: #c7d2fe; justify-content: center; height: 36px; text-decoration: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px;">manage_accounts</span>
                            <span>Section Lead</span>
                        </a>
                        <a href="{{ route('login.quick', ['role' => 'staff']) }}" class="md-chip" style="background-color: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); border-color: #bae6fd; justify-content: center; height: 36px; text-decoration: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                            <span>Specialist</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            const btn = document.getElementById('passwordToggleBtn');

            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
                btn.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
                btn.setAttribute('aria-label', 'Show password');
            }
        }

        // Add loading state upon form submission
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnText = document.getElementById('submitBtnText');

        if (form && submitBtn) {
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtnText.textContent = 'Signing in...';
            });
        }
    </script>
</body>
</html>
