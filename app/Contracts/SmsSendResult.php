<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Lightweight result envelope for SMS / OTP send
 * operations. Phase 7 uses this to keep the channel
 * contract decoupled from any specific provider SDK.
 */
class SmsSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}

    public static function ok(?string $providerMessageId = null): self
    {
        return new self(true, $providerMessageId, null, null);
    }

    public static function fail(string $code, string $message): self
    {
        return new self(false, null, $code, $message);
    }
}
