<?php

declare(strict_types=1);

namespace App\Logging;

use DateTimeInterface;
use DateTimeZone;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;
use Throwable;

/**
 * Structured JSON log formatter.
 *
 * Always emits ISO 8601 timestamps (UTC) and normalises the top-level
 * payload to a fixed schema:
 *
 *  {
 *      "timestamp": "2026-07-09T14:30:00.123456Z",
 *      "level":     "info",
 *      "message":   "WorkOrder created",
 *      "channel":   "structured",
 *      "tenant_id": "42",
 *      "user_id":   "7",
 *      "correlation_id": "0d4c2f3e-...",
 *      "route":     "POST app/work-orders",
 *      "method":    "POST",
 *      "context":   { ... arbitrary per-call data ... },
 *      "extra":     { ... arbitrary per-call extras ... }
 *  }
 *
 * The correlation_id / tenant_id / user_id / route fields are auto-populated
 * from Laravel's log context — which is populated by
 * App\Http\Middleware\SentryContext — so callers don't need to repeat the
 * boilerplate on every Log::info(...) call.
 *
 * Used by:
 *  - config/logging.php channel "structured"
 *  - Application::configureMonologUsing() for non-local envs
 *
 * If the Sentry SDK is loaded, it handles its own JSON encoding; this
 * formatter is for our own stdout / file streams.
 */
class JsonFormatter extends NormalizerFormatter
{
    public function __construct()
    {
        // ISO 8601 with microseconds, UTC.
        parent::__construct(DateTimeInterface::RFC3339_EXTENDED);
    }

    public function format(LogRecord $record): string
    {
        $context = $this->normalizeRecord($record);
        $contextData = $context['context'] ?? [];
        $extraData = $context['extra'] ?? [];

        $payload = [
            // ISO 8601 / RFC 3339 with microseconds, forced to UTC.
            'timestamp' => $record->datetime->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.u\Z'),
            'level' => strtolower($record->level->getName()),
            'message' => $record->message,
            'channel' => $record->channel,
        ];

        // First-class fields come from log context (SentryContext middleware).
        // We strip them out of the nested `context` block so they don't appear twice.
        foreach (['correlation_id', 'tenant_id', 'user_id', 'route', 'method'] as $key) {
            if (array_key_exists($key, $contextData)) {
                $payload[$key] = $contextData[$key];
                unset($contextData[$key]);
            }
        }

        if (! empty($contextData)) {
            $payload['context'] = $contextData;
        }

        if (! empty($extraData)) {
            $payload['extra'] = $extraData;
        }

        return $this->toJson($payload, true)."\n";
    }

    /**
     * @param Throwable|mixed $data
     */
    protected function normalize($data, int $depth = 0): mixed
    {
        if ($data instanceof Throwable) {
            return [
                'class' => $data::class,
                'message' => $data->getMessage(),
                'file' => $data->getFile().':'.$data->getLine(),
                'trace' => $data->getTraceAsString(),
            ];
        }

        return parent::normalize($data, $depth);
    }
}
