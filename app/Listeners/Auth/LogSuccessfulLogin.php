<?php

namespace App\Listeners\Auth;

use App\Events\Auth\LoginSuccessful;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    public function handle(LoginSuccessful $event): void
    {
        Log::info('auth.login.successful', $event->logContext());
    }
}
