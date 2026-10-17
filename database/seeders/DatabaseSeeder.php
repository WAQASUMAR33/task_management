<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\Section;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('Password123!');

        // 1. Seed Primary Unit
        $unit = Unit::create([
            'name' => 'Operational Command Unit',
            'code' => 'UNIT-HQ-01',
            'description' => 'Central organizational command unit encompassing all strategic, technical, and operational sections.',
            'status' => 'active',
        ]);

        // 2. Seed Sections
        $secEngineering = Section::create([
            'unit_id' => $unit->id,
            'name' => 'Engineering & Technology',
            'code' => 'SEC-ENG',
            'description' => 'Responsible for software engineering, architecture, and technology development.',
            'status' => 'active',
        ]);

        $secOperations = Section::create([
            'unit_id' => $unit->id,
            'name' => 'Operations & Cloud Logistics',
            'code' => 'SEC-OPS',
            'description' => 'Handles server infrastructure, cloud deployment, and system reliability operations.',
            'status' => 'active',
        ]);

        $secDesign = Section::create([
            'unit_id' => $unit->id,
            'name' => 'Design & Quality Assurance',
            'code' => 'SEC-DQA',
            'description' => 'Focuses on UI/UX product design, usability, and end-to-end quality assurance.',
            'status' => 'active',
        ]);

        // 3. Seed Users
        // Super Admin (Unit-level Head)
        $superAdmin = User::create([
            'name' => 'Alexander Vance',
            'email' => 'superadmin@apextask.com',
            'password' => $password,
            'role' => 'super_admin',
            'section_id' => null, // Super Admin oversees all sections
            'department' => 'Unit Headquarters',
            'status' => 'active',
            'avatar_color' => '#7c3aed',
        ]);

        // Section Admin 1: Engineering
        $adminEng = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'admin@apextask.com',
            'password' => $password,
            'role' => 'admin',
            'section_id' => $secEngineering->id,
            'department' => 'Engineering',
            'status' => 'active',
            'avatar_color' => '#2563eb',
        ]);
        $secEngineering->update(['admin_id' => $adminEng->id]);

        // Section Admin 2: Operations
        $adminOps = User::create([
            'name' => 'Marcus Wright',
            'email' => 'marcus.admin@apextask.com',
            'password' => $password,
            'role' => 'admin',
            'section_id' => $secOperations->id,
            'department' => 'Operations',
            'status' => 'active',
            'avatar_color' => '#059669',
        ]);
        $secOperations->update(['admin_id' => $adminOps->id]);

        // Section Admin 3: Design
        $adminDesign = User::create([
            'name' => 'Emily Chen',
            'email' => 'emily.admin@apextask.com',
            'password' => $password,
            'role' => 'admin',
            'section_id' => $secDesign->id,
            'department' => 'Design',
            'status' => 'active',
            'avatar_color' => '#db2777',
        ]);
        $secDesign->update(['admin_id' => $adminDesign->id]);

        // Team Members under Engineering (Admin: Sarah Jenkins)
        $staffEng1 = User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@apextask.com',
            'password' => $password,
            'role' => 'staff',
            'section_id' => $secEngineering->id,
            'department' => 'Frontend Engineering',
            'status' => 'active',
            'avatar_color' => '#0891b2',
        ]);

        $staffEng2 = User::create([
            'name' => 'David Kim',
            'email' => 'david.kim@apextask.com',
            'password' => $password,
            'role' => 'staff',
            'section_id' => $secEngineering->id,
            'department' => 'Backend Engineering',
            'status' => 'active',
            'avatar_color' => '#6366f1',
        ]);

        // Team Members under Operations (Admin: Marcus Wright)
        $staffOps1 = User::create([
            'name' => 'Elena Rostova',
            'email' => 'elena.ops@apextask.com',
            'password' => $password,
            'role' => 'staff',
            'section_id' => $secOperations->id,
            'department' => 'Cloud Infrastructure',
            'status' => 'active',
            'avatar_color' => '#10b981',
        ]);

        // Team Members under Design & QA (Admin: Emily Chen)
        $staffDesign1 = User::create([
            'name' => 'Priya Patel',
            'email' => 'priya.patel@apextask.com',
            'password' => $password,
            'role' => 'staff',
            'section_id' => $secDesign->id,
            'department' => 'Quality Assurance',
            'status' => 'active',
            'avatar_color' => '#d97706',
        ]);

        $inactiveUser = User::create([
            'name' => 'Robert Miller (Inactive)',
            'email' => 'robert.miller@apextask.com',
            'password' => $password,
            'role' => 'staff',
            'section_id' => $secEngineering->id,
            'department' => 'Data Analytics',
            'status' => 'inactive',
            'avatar_color' => '#64748b',
        ]);

        // 4. Seed System Settings
        SystemSetting::set('app_name', 'Rapid Track');
        SystemSetting::set('company_name', 'Apex Unit Command');
        SystemSetting::set('allow_file_uploads', 'true');
        SystemSetting::set('max_file_size_mb', '10');
        SystemSetting::set('default_user_role', 'staff');
        SystemSetting::set('task_notifications_enabled', 'true');

        // 5. Seed Tasks scoped by Section
        // Engineering Tasks (Managed by Sarah Jenkins)
        $task1 = Task::create([
            'title' => 'Implement RBAC Unit & Section Guard in Laravel',
            'description' => 'Set up route middleware and query scopes for Super Admin, Section Admins, and Section Team Members.',
            'section_id' => $secEngineering->id,
            'assigned_to' => $staffEng1->id,
            'created_by' => $adminEng->id,
            'due_date' => Carbon::now()->addDays(2),
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        $task2 = Task::create([
            'title' => 'Refactor API Endpoints for Section Data Isolation',
            'description' => 'Ensure database queries strictly scope team member records and tasks to their assigned section.',
            'section_id' => $secEngineering->id,
            'assigned_to' => $staffEng2->id,
            'created_by' => $adminEng->id,
            'due_date' => Carbon::now()->addDays(4),
            'priority' => 'medium',
            'status' => 'todo',
        ]);

        // Operations Tasks (Managed by Marcus Wright)
        $task3 = Task::create([
            'title' => 'Configure Production Hostinger Nginx & Apache Symlinks',
            'description' => 'Ensure .htaccess rewrite rules, database pooling, and document root mappings are fully validated.',
            'section_id' => $secOperations->id,
            'assigned_to' => $staffOps1->id,
            'created_by' => $adminOps->id,
            'due_date' => Carbon::now()->subDays(1), // Overdue
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        // Design & QA Tasks (Managed by Emily Chen)
        $task4 = Task::create([
            'title' => 'Run End-to-End Section Workflow Test Suite',
            'description' => 'Verify that Section Admins cannot view or modify tasks belonging to other sections.',
            'section_id' => $secDesign->id,
            'assigned_to' => $staffDesign1->id,
            'created_by' => $adminDesign->id,
            'due_date' => Carbon::now(), // Due today
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        // 6. Seed Attachments
        Storage::disk('public')->makeDirectory('attachments');
        
        $sampleText = "Unit Command & Section Architecture Specification.\nVersion: 4.0.0 (Unit Management Stack)\nValidated: Yes";
        Storage::disk('public')->put('attachments/demo_spec_v3.txt', $sampleText);

        $sampleSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200" viewBox="0 0 400 200"><rect width="400" height="200" fill="#1e293b"/><text x="200" y="100" fill="#38bdf8" font-family="sans-serif" font-size="20" font-weight="bold" text-anchor="middle">Rapid Track Architecture</text><text x="200" y="130" fill="#94a3b8" font-family="sans-serif" font-size="14" text-anchor="middle">Unit & Section Hierarchy</text></svg>';
        Storage::disk('public')->put('attachments/demo_diagram.svg', $sampleSvg);

        TaskAttachment::create([
            'task_id' => $task1->id,
            'uploaded_by' => $adminEng->id,
            'filename' => 'attachments/demo_spec_v3.txt',
            'original_name' => 'Architecture_Spec_v3.txt',
            'mime_type' => 'text/plain',
            'size' => strlen($sampleText),
        ]);

        TaskAttachment::create([
            'task_id' => $task1->id,
            'uploaded_by' => $staffEng1->id,
            'filename' => 'attachments/demo_diagram.svg',
            'original_name' => 'Section_Workflow_Diagram.svg',
            'mime_type' => 'image/svg+xml',
            'size' => strlen($sampleSvg),
        ]);

        // 7. Seed Comments
        TaskComment::create([
            'task_id' => $task1->id,
            'user_id' => $adminEng->id,
            'comment' => 'John, ensure the section filtering applies to both index listing and task detail routes.',
        ]);

        TaskComment::create([
            'task_id' => $task1->id,
            'user_id' => $staffEng1->id,
            'comment' => 'Working on it! Query scopes and controller authorization checks added.',
        ]);

        // 8. Seed Activity Logs
        ActivityLog::log($superAdmin->id, 'SYSTEM_INIT', 'Initialized Unit Command system with Engineering, Operations, and Design sections');
        ActivityLog::log($adminEng->id, 'SECTION_UPDATE', 'Section Admin Sarah Jenkins took leadership of Engineering & Technology section');
        ActivityLog::log($adminEng->id, 'TASK_CREATED', 'Created task #1 in Engineering section: "Implement RBAC Unit & Section Guard"');
        ActivityLog::log($staffEng1->id, 'TASK_STATUS_CHANGED', 'Updated task #1 to In Progress');
    }
}
