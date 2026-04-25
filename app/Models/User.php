<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'avatar',
        'email',
        'account_code',
        'role',
        'phone',
        'balance',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Accessors to append to model array/JSON.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'balance' => 'integer',
            'role' => UserRole::class,
            'password' => 'hashed',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function createdRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'creator_id');
    }

    public function participatedRooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'room_participants', 'participant_id', 'room_id')
            ->withTimestamps();
    }

    public function insights(): HasMany
    {
        return $this->hasMany(Insight::class);
    }

    public function aiMessages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'user_items')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function parentConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'parent_id');
    }

    public function childConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'child_id');
    }

    public function sentControlRequests(): HasMany
    {
        return $this->hasMany(ControlRequest::class, 'sender_id');
    }

    public function receivedControlRequests(): HasMany
    {
        return $this->hasMany(ControlRequest::class, 'receiver_id');
    }

    public function receivedNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'receiver_id');
    }

    public function administratedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'admin_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Генерируем url к аватару пользователя
     */
    public function getAvatarUrlAttribute() : ?string {
        if (!$this->avatar) return null;

        // Если путь уже является полным URL, возвращаем как есть
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        // Генерируем URL из S3
        try {
            return Storage::disk('s3')->url($this->avatar);
        } catch (\Exception $e) {
            \Log::warning('Failed to get avatar URL from S3', [
                'avatar' => $this->avatar,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
