<?php

declare(strict_types=1);

namespace App\Events\Auth;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoginSuccessful
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public string $ipAddress,
    ) {}

    public function logContext(): array
    {
        return [
            'event' => 'login.successful',
            'user_id' => $this->user->id,
            'tenant_id' => $this->user->tenant_id,
            'ip' => $this->ipAddress,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
