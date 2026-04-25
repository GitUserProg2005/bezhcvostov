<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Video call channel
Broadcast::channel('video-call.{linkId}', function ($user, $linkId) {
    return true;
});

// Task manager channel
Broadcast::channel('task-manager.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('projects.chat.{chatId}', function ($user, $chatId) {
    return Chat::query()
        ->where('id', $chatId)
        ->whereHas('project.users', fn ($query) => $query->where('users.id', $user->id))
        ->exists();
});
