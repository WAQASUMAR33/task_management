<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'This user account is currently deactivated.',
                ]);
            }

            $request->session()->regenerate();
            ActivityLog::log($user->id, 'AUTH_LOGIN', "User {$user->name} logged in successfully");

            return redirect()->intended(route('dashboard'))
                ->with('success', "Welcome back, {$user->name}!");
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function quickLogin(Request $request, $role)
    {
        $emailMap = [
            'super_admin' => 'superadmin@apextask.com',
            'admin' => 'admin@apextask.com',
            'staff' => 'john.doe@apextask.com',
            'staff2' => 'emily.chen@apextask.com',
        ];

        $targetEmail = $emailMap[$role] ?? null;
        if (!$targetEmail) {
            return redirect()->route('login')->withErrors(['email' => 'Unknown demo persona.']);
        }

        $user = User::where('email', $targetEmail)->first();
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Demo user account not found.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        ActivityLog::log($user->id, 'AUTH_QUICK_LOGIN', "Demo persona quick login as {$user->name} ({$user->role})");

        return redirect()->route('dashboard')
            ->with('success', "Logged in as {$user->name} ({$user->role_title}) via Quick Demo Persona.");
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            ActivityLog::log($user->id, 'AUTH_LOGOUT', "User {$user->name} logged out");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been safely signed out.');
    }

    public function showProfile()
    {
        $user = Auth::user();
        return view('profile.show', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'avatar_color' => ['required', 'string', 'max:32'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'avatar_color' => $validated['avatar_color'],
        ]);

        ActivityLog::log($user->id, 'PROFILE_UPDATED', "Updated profile details");

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::log($user->id, 'PASSWORD_CHANGED', "Password was changed successfully");

        return back()->with('success', 'Password updated successfully.');
    }
}
