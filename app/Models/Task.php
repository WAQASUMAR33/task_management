<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'section_id',
        'assigned_to',
        'due_date',
        'priority',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')->orderBy('created_at', 'asc');
    }

    public function subtasks()
    {
        return $this->hasMany(TaskSubtask::class, 'task_id')->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function getSubtasksProgressPercentAttribute(): int
    {
        $total = $this->subtasks->count();
        if ($total === 0) return 0;
        return (int) round(($this->subtasks->where('is_completed', true)->count() / $total) * 100);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match($this->priority) {
            'high' => 'chip-priority-high',
            'medium' => 'chip-priority-medium',
            default => 'chip-priority-low',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'completed' => 'chip-status-completed',
            'in_progress' => 'chip-status-progress',
            default => 'chip-status-todo',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'completed' => 'Completed',
            'in_progress' => 'In Progress',
            default => 'To Do',
        };
    }
}
