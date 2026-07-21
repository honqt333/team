<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\Integration\Integration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Single source of truth for SMTP configuration.
 *
 * Both development and production environments should send email through the
 * SMTP integration configured under `/system/integrations` (an `email` row
 * with `is_default = true` and `is_active = true`). The values from `.env`
 * (MAIL_MAILER, MAIL_HOST, ...) are intentionally ignored so the
 * operations team can change providers without touching deployment.
 *
 * The selected integration is cached in memory for the duration of the
 * request to avoid hitting the database on every email send.
 */
class SmtpConfigService
{
    /** @var array{host:string,port:int,username:string,password:string,encryption:?string,from_address:string,from_name:string}|null */
    private ?array $resolvedConfig = null;

    /**
     * Apply the SMTP configuration from the database to Laravel's mail
     * runtime config. Safe to call multiple times — the lookup is
     * memoized for the rest of the request.
     *
     * If no default email integration is configured we log a warning and
     * keep whatever is currently in config('mail.*'). Callers that need
     * hard-fail behaviour should use {@see assertConfigured()} first.
     */
    public function apply(): void
    {
        $config = $this->resolve();

        if ($config === null) {
            Log::warning('SmtpConfigService: no default email integration configured. Falling back to mail.* config.');

            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'],
            'username' => $config['username'],
            'password' => $config['password'],
            'timeout' => null,
        ]);
        Config::set('mail.from.address', $config['from_address']);
        Config::set('mail.from.name', $config['from_name']);
    }

    /**
     * Returns true when a usable default email integration exists in the
     * database. Use this to gate flows that should not run with the
     * provider's defaults (eg. end-to-end registration tests).
     */
    public function isConfigured(): bool
    {
        return $this->resolve() !== null;
    }

    /**
     * @return array{host:string,port:int,username:string,password:string,encryption:?string,from_address:string,from_name:string}|null
     */
    private function resolve(): ?array
    {
        if ($this->resolvedConfig !== null) {
            return $this->resolvedConfig;
        }

        // The integrations table may not exist yet on a fresh install
        // (pre-migration) or in a unit-test environment that has not
        // loaded the schema. Bail out cleanly so we never throw during
        // boot or a request bootstrap.
        if (! Schema::hasTable('integrations')) {
            return null;
        }

        $integration = Integration::getDefault('email');

        if (! $integration || ! $integration->isConfigured()) {
            return null;
        }

        $raw = $integration->config;

        $this->resolvedConfig = [
            'host' => (string) ($raw['host'] ?? ''),
            'port' => (int) ($raw['port'] ?? 587),
            'username' => (string) ($raw['username'] ?? ''),
            'password' => (string) ($raw['password'] ?? ''),
            'encryption' => $raw['encryption'] ?? null,
            'from_address' => (string) ($raw['from_address'] ?? config('mail.from.address')),
            'from_name' => (string) ($raw['from_name'] ?? config('mail.from.name', config('app.name'))),
        ];

        return $this->resolvedConfig;
    }
}
