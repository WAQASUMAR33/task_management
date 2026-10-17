<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemSettingController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'System settings are accessible by Super Admin only.');
        }

        $settings = SystemSetting::all()->pluck('setting_value', 'setting_key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:128'],
            'company_name' => ['required', 'string', 'max:255'],
            'max_file_size_mb' => ['required', 'numeric', 'min:1', 'max:50'],
            'allow_file_uploads' => ['required', 'in:true,false'],
            'default_user_role' => ['required', 'in:staff,admin'],
            'task_notifications_enabled' => ['required', 'in:true,false'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, (string)$value);
        }

        ActivityLog::log($currentUser->id, 'SETTINGS_UPDATED', "Updated enterprise system settings");

        return back()->with('success', 'System settings saved successfully.');
    }
}
