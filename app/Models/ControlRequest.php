<?php

namespace App\Models;

use App\Enums\ControlRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlRequest extends Model
{
    protected $table = 'control_request';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ControlRequestStatus::class,
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
