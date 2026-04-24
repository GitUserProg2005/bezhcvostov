<?php

namespace App\Models;

use App\Enums\TaskDifficulty;
use App\Enums\TaskSourceType;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'difficulty',
        'estimated_minutes',
        'deadline',
        'end_at',
        'status',
        'source_type',
        'ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => TaskDifficulty::class,
            'status' => TaskStatus::class,
            'source_type' => TaskSourceType::class,
            'estimated_minutes' => 'integer',
            'deadline' => 'datetime',
            'end_at' => 'date',
            'ai_generated' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class)->orderBy('order');
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(TaskInput::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}
