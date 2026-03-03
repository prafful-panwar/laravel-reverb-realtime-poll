<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id): bool {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.polls.{userId}', function ($user, $userId): bool {
    return (int) $user->id === (int) $userId;
});
