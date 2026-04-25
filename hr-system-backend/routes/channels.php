<?php

use Illuminate\Support\Facades\Broadcast;

// User-private channel: notifications and any user-specific real-time
// payloads ride this. Subscriber must be the same authenticated user.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Friendlier alias matching the JS naming convention. Echo translates
// `Echo.private('user.123')` to `private-user.123` on the wire.
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
