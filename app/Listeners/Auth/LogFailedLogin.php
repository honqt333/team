<?php

namespace App\Listeners\Auth;

use App\Events\Auth\LoginFailed;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    public function handle(LoginFailed $event): void
    {
        Log::warning('auth.login.failed', [
            'email' => $event->email,
            'ip' => $event->ipAddress,
            'reason' => $event->reason,
        ]);
    }
}
