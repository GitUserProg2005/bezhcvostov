<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subtask extends Model
{
    protected $fillable = [
        'task_id',
        'title',
        'description',
        'order',
        'estimated_minutes',
        'deadline',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'estimated_minutes' => 'integer',
            'deadline' => 'datetime',
            'status' => TaskStatus::class,
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
