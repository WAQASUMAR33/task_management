<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SystemSettingController;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/login/quick/{role}', [AuthController::class, 'quickLogin'])->name('login.quick');
});

// Authenticated Routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Quick Search & Export
    Route::get('/tasks/export/csv', [TaskController::class, 'exportCsv'])->name('tasks.export');
    Route::get('/quick-search', [TaskController::class, 'quickSearch'])->name('tasks.quick-search');

    // Tasks Management
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Task Subtasks & Checklist
    Route::post('/tasks/{task}/subtasks', [TaskController::class, 'addSubtask'])->name('tasks.subtasks.store');
    Route::patch('/tasks/{task}/subtasks/{subtask}/toggle', [TaskController::class, 'toggleSubtask'])->name('tasks.subtasks.toggle');
    Route::delete('/tasks/{task}/subtasks/{subtask}', [TaskController::class, 'deleteSubtask'])->name('tasks.subtasks.destroy');

    // Task Attachments
    Route::post('/tasks/{task}/attachments', [TaskController::class, 'uploadAttachment'])->name('tasks.attachments.store');
    Route::get('/attachments/{attachment}/preview', [TaskController::class, 'previewAttachment'])->name('attachments.preview');
    Route::get('/attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [TaskController::class, 'deleteAttachment'])->name('attachments.destroy');

    // Task Comments
    Route::post('/tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.comments.store');
    Route::delete('/comments/{comment}', [TaskController::class, 'deleteComment'])->name('comments.destroy');

    // Section Management & Overview
    Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
    Route::get('/sections/{section}', [SectionController::class, 'show'])->name('sections.show');

    // User Profile
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    // Team & User Management (Section Admins & Super Admins)
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Super Admin Only: Manage Sections & System Settings
    Route::middleware('role:super_admin')->group(function () {
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::put('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

        Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
    });
});
