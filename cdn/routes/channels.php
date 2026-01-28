<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    if (!$user) {
        return false;
    }
    return (int) $user->id === (int) $id;
});

// Page channels (public)
Broadcast::channel('page.{pageId}', function ($user, $pageId) {
    return true; // Public channel, anyone can listen
});

// User private channels
Broadcast::channel('user.{userId}', function ($user, $userId) {
    \Log::info('Channel authorization: user.{userId}', [
        'has_user' => $user !== null,
        'user_id' => $user?->id,
        'requested_user_id' => $userId,
    ]);

    if (!$user) {
        \Log::warning('Channel authorization: No user provided');
        return false;
    }

    $authorized = (int) $user->id === (int) $userId;
    \Log::info('Channel authorization result', [
        'authorized' => $authorized,
        'user_id' => $user->id,
        'requested_user_id' => $userId,
    ]);

    return $authorized;
});
