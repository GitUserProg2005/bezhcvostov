<?php

namespace App\Models;

use App\Enums\MascotEmotion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'user_id',
        'sender',
        'message',
        'output',
        'emotion',
    ];

    protected function casts(): array
    {
        return [
            'output' => 'array',
            'emotion' => MascotEmotion::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
