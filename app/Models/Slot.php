<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Slot extends Model
{
    protected $fillable = [
        'x',
        'y',
        'scale',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'scale' => 'float',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
