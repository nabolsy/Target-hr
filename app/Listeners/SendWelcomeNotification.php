<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification
{
    public function handle(UserRegistered $event): void
    {
        Log::info('Welcome notification queued for new user', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'company_id' => $event->company->id,
            'company_name' => $event->company->name,
        ]);

        // TODO: Send welcome email/notification to the registered user.
    }
}
