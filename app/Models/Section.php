<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'name',
        'code',
        'description',
        'admin_id',
        'status',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function members()
    {
        return $this->hasMany(User::class, 'section_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(User::class, 'section_id')->where('role', 'staff');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'section_id');
    }
}
