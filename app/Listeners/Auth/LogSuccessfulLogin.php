<?php

namespace App\Listeners\Auth;

use App\Events\Auth\LoginSuccessful;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    public function handle(LoginSuccessful $event): void
    {
        Log::info('auth.login.successful', $event->logContext());

        if (class_exists(\App\Models\AdminActivityLog::class)) {
            \App\Models\AdminActivityLog::create([
                'tenant_id' => $event->user->tenant_id,
                'user_id' => $event->user->id,
                'action' => 'login.successful',
                'description' => 'User logged in successfully',
                'ip_address' => $event->ipAddress,
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([]),
            ]);
        }
    }
}
