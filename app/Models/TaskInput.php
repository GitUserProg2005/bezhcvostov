<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TaskInput extends Model
{
    protected $fillable = [
        'task_id',
        'type',
        'file_path',
        'raw_text',
        'processed_text',
    ];

    protected $appends = [
        'file_url',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if (filter_var($this->file_path, FILTER_VALIDATE_URL)) {
            return $this->file_path;
        }

        try {
            return Storage::disk('s3')->url($this->file_path);
        } catch (\Exception $e) {
            Log::warning('Failed to get task input file URL from S3', [
                'file_path' => $this->file_path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
