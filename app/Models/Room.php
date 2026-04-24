<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $fillable = [
        'title',
        'creator_id',
        'link_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            if (! $room->link_id) {
                $room->link_id = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_participants', 'room_id', 'participant_id')
            ->withTimestamps();
    }

    public function insights(): HasMany
    {
        return $this->hasMany(Insight::class);
    }
}
