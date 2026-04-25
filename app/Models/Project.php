<?php

namespace App\Models;

use App\Enums\ProjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'title',
        'admin_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')->withTimestamps();
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
