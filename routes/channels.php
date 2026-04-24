<?php

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
