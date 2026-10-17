<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'uploaded_by',
        'filename',
        'original_name',
        'mime_type',
        'size',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/') ||
               in_array(strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp']);
    }

    public function getUrlAttribute(): string
    {
        return route('attachments.preview', $this);
    }

    public function getIconAttribute(): string
    {
        if ($this->is_image) return 'image';
        if (str_contains($this->mime_type, 'pdf')) return 'picture_as_pdf';
        if (str_contains($this->mime_type, 'zip') || str_contains($this->mime_type, 'compressed')) return 'folder_zip';
        if (str_contains($this->mime_type, 'word') || str_contains($this->mime_type, 'document')) return 'description';
        if (str_contains($this->mime_type, 'sheet') || str_contains($this->mime_type, 'excel') || str_contains($this->mime_type, 'csv')) return 'table_chart';
        return 'insert_drive_file';
    }
}
