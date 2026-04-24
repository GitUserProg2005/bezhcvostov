<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    protected $fillable = [
        'slot_id',
        'title',
        'description',
        'price',
        'picture',
    ];

    protected $appends = [
        'picture_url',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_items')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Генерируем URL к картинке предмета.
     */
    public function getPictureUrlAttribute(): ?string
    {
        if (!$this->picture) {
            return null;
        }

        if (filter_var($this->picture, FILTER_VALIDATE_URL)) {
            return $this->picture;
        }

        if (str_starts_with($this->picture, '/img/') || str_starts_with($this->picture, 'img/')) {
            return '/'.ltrim($this->picture, '/');
        }

        try {
            return Storage::disk('s3')->url($this->picture);
        } catch (\Exception $e) {
            \Log::warning('Failed to get item picture URL from S3', [
                'picture' => $this->picture,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
