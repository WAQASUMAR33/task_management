<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Unit;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();

        $query = Section::with(['unit', 'admin', 'teamMembers'])->withCount(['tasks', 'members']);

        // If Section Admin or Staff, highlight or isolate their section
        if ($currentUser->isSectionAdmin() || $currentUser->isStaff()) {
            $sections = $query->where('id', $currentUser->section_id)->get();
        } else {
            $sections = $query->orderBy('name')->get();
        }

        $units = Unit::where('status', 'active')->get();
        
        // Potential section admins: Users with role admin or super_admin
        $potentialAdmins = User::whereIn('role', ['admin', 'super_admin'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('sections.index', compact('sections', 'units', 'potentialAdmins'));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'Only Super Admins can create new sections.');
        }

        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:sections,code'],
            'description' => ['nullable', 'string'],
            'admin_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $section = Section::create($validated);

        // If an admin is assigned, assign their section_id
        if (!empty($validated['admin_id'])) {
            $adminUser = User::find($validated['admin_id']);
            if ($adminUser) {
                $adminUser->update([
                    'section_id' => $section->id,
                    'role' => $adminUser->role === 'super_admin' ? 'super_admin' : 'admin',
                ]);
            }
        }

        ActivityLog::log($currentUser->id, 'SECTION_CREATED', "Created section [{$section->code}] {$section->name}");

        return back()->with('success', "Section \"{$section->name}\" created successfully.");
    }

    public function show(Section $section)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin() && $currentUser->section_id !== $section->id) {
            abort(403, 'Unauthorized to view this section.');
        }

        $section->load(['unit', 'admin', 'members' => function ($q) {
            $q->withCount('assignedTasks');
        }, 'tasks' => function ($q) {
            $q->with(['assignee'])->latest()->take(10);
        }]);

        return view('sections.show', compact('section'));
    }

    public function update(Request $request, Section $section)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'Only Super Admins can update section configurations.');
        }

        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('sections', 'code')->ignore($section->id)],
            'description' => ['nullable', 'string'],
            'admin_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $oldAdminId = $section->admin_id;
        $section->update($validated);

        // If admin changed, sync section_id
        if ($validated['admin_id'] != $oldAdminId) {
            if (!empty($validated['admin_id'])) {
                $newAdmin = User::find($validated['admin_id']);
                if ($newAdmin) {
                    $newAdmin->update([
                        'section_id' => $section->id,
                        'role' => $newAdmin->role === 'super_admin' ? 'super_admin' : 'admin',
                    ]);
                }
            }
        }

        ActivityLog::log($currentUser->id, 'SECTION_UPDATED', "Updated section details for [{$section->code}]");

        return back()->with('success', "Section \"{$section->name}\" updated successfully.");
    }

    public function destroy(Section $section)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'Only Super Admins can remove sections.');
        }

        $name = $section->name;
        $section->delete();

        ActivityLog::log($currentUser->id, 'SECTION_DELETED', "Deleted section \"{$name}\"");

        return redirect()->route('sections.index')->with('success', "Section \"{$name}\" removed.");
    }
}
