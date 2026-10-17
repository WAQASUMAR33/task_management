<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Section;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        if ($currentUser->isStaff()) {
            abort(403, 'Unauthorized access to team management.');
        }

        $query = User::with(['section'])->withCount([
            'assignedTasks as total_tasks',
            'assignedTasks as completed_tasks' => function ($q) {
                $q->where('status', 'completed');
            },
        ]);

        // Section isolation: Section Admin only sees team members in their section as staff
        if ($currentUser->isSectionAdmin()) {
            $query->where('section_id', $currentUser->section_id)
                  ->where('role', 'staff');
            $sections = $currentUser->section_id 
                ? Section::where('id', $currentUser->section_id)->get() 
                : collect();
        } else {
            // Super Admin
            $sections = Section::orderBy('name')->get();
            if ($request->filled('section_id') && $request->input('section_id') !== 'all') {
                $query->where('section_id', $request->input('section_id'));
            }

            if ($request->filled('role') && $request->input('role') !== 'all') {
                $query->where('role', $request->input('role'));
            }
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('role', 'asc')->orderBy('name', 'asc')->paginate(15)->withQueryString();

        return view('users.index', compact('users', 'sections'));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if ($currentUser->isStaff()) {
            abort(403, 'Unauthorized.');
        }

        $allowedRoles = $currentUser->isSuperAdmin() 
            ? ['super_admin', 'admin', 'staff'] 
            : ['staff'];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($allowedRoles)],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'avatar_color' => ['required', 'string'],
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['section_id'] = ['nullable', 'exists:sections,id'];
        }

        $validated = $request->validate($rules);

        $sectionId = $currentUser->isSuperAdmin() 
            ? ($validated['section_id'] ?? null) 
            : $currentUser->section_id;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'section_id' => $sectionId,
            'department' => $validated['department'] ?? 'General',
            'status' => $validated['status'],
            'avatar_color' => $validated['avatar_color'],
        ]);

        ActivityLog::log($currentUser->id, 'USER_CREATED', "Created new {$user->role_title}: {$user->name} ({$user->email})");

        return back()->with('success', "Account for {$user->name} created successfully.");
    }

    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser->isStaff()) {
            abort(403, 'Unauthorized.');
        }

        // Section Admin can only edit staff in their own section
        if ($currentUser->isSectionAdmin()) {
            if ($user->section_id !== $currentUser->section_id || $user->role !== 'staff') {
                abort(403, 'Section Admins can only modify Team Members within their section.');
            }
        }

        $allowedRoles = $currentUser->isSuperAdmin() 
            ? ['super_admin', 'admin', 'staff'] 
            : ['staff'];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in($allowedRoles)],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'avatar_color' => ['required', 'string'],
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['section_id'] = ['nullable', 'exists:sections,id'];
        }

        $validated = $request->validate($rules);

        if ($currentUser->isSuperAdmin() && array_key_exists('section_id', $validated)) {
            $user->section_id = $validated['section_id'];
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->department = $validated['department'] ?? $user->department;
        $user->status = $validated['status'];
        $user->avatar_color = $validated['avatar_color'];
        $user->save();

        ActivityLog::log($currentUser->id, 'USER_UPDATED', "Updated user details for {$user->name}");

        return back()->with('success', "User {$user->name} updated successfully.");
    }

    public function toggleStatus(User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser->isStaff()) {
            abort(403, 'Unauthorized.');
        }

        if ($user->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot deactivate your own account.']);
        }

        if ($currentUser->isSectionAdmin() && ($user->section_id !== $currentUser->section_id || $user->role !== 'staff')) {
            abort(403, 'Section Admins can only change status for team members in their section.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        ActivityLog::log($currentUser->id, 'USER_STATUS_TOGGLED', "Changed status of {$user->name} to {$newStatus}");

        return back()->with('success', "User account has been set to {$newStatus}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser->isStaff()) {
            abort(403, 'Unauthorized.');
        }

        if ($currentUser->isSectionAdmin() && ($user->section_id !== $currentUser->section_id || $user->role !== 'staff')) {
            abort(403, 'Section Admins can only reset passwords for team members in their section.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::log($currentUser->id, 'USER_PASSWORD_RESET', "Reset password for {$user->name}");

        return back()->with('success', "Password for {$user->name} was reset successfully.");
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'Only Super Admins can delete users.');
        }

        if ($user->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot delete yourself.']);
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::log($currentUser->id, 'USER_DELETED', "Permanently deleted user: {$name}");

        return back()->with('success', "User {$name} deleted successfully.");
    }
}
