<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ErrorLogController extends Controller
{
    private const MAX_BYTES = 1048576;

    private const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewSystemErrorLog');
        $filters = $request->validate([
            'tail' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'level' => ['nullable', Rule::in(self::LEVELS)],
        ]);
        $tail = (int) ($filters['tail'] ?? 200);
        $level = $filters['level'] ?? null;
        $base = (string) config('logging.channels.daily-detailed.path', storage_path('logs/detailed.log'));
        $files = glob(substr($base, 0, -4).'-*.log') ?: [];
        rsort($files);

        if (is_file($base)) {
            array_unshift($files, $base);
        }
        $path = $files[0] ?? null;
        $entries = [];
        $truncated = false;

        if ($path !== null && ! is_link($path)) {
            $handle = fopen($path, 'rb');

            if ($handle !== false) {
                try {
                    $size = (int) (fstat($handle)['size'] ?? 0);
                    $offset = max(0, $size - self::MAX_BYTES);
                    fseek($handle, $offset);

                    if ($offset > 0) {
                        fgets($handle); // Discard a possibly incomplete first record.
                    }
                    $contents = stream_get_contents($handle, self::MAX_BYTES);
                    $truncated = $offset > 0;

                    foreach (explode("\n", $contents === false ? '' : $contents) as $line) {
                        $record = json_decode($line, true);

                        if (! is_array($record) || ! is_string($record['message'] ?? null)) {
                            continue;
                        }
                        $rawLevel = $record['level_name'] ?? $record['level'] ?? '';

                        if (! is_string($rawLevel)) {
                            continue;
                        }
                        $recordLevel = strtolower($rawLevel);

                        if (! in_array($recordLevel, self::LEVELS, true) || ($level !== null && $level !== $recordLevel)) {
                            continue;
                        }
                        // Allow-list presentation fields; never expose request bodies,
                        // context, exception arguments, credentials, or raw log lines.
                        $entries[] = [
                            'id' => count($entries),
                            'timestamp' => $this->safeText($record['timestamp'] ?? $record['datetime'] ?? ''),
                            'level' => $recordLevel,
                            'message' => $this->safeText($record['message']),
                        ];
                    }
                } finally {
                    fclose($handle);
                }
            }
        }

        return Inertia::render('System/ErrorLog/Index', [
            'entries' => array_reverse(array_slice($entries, -$tail)),
            'meta' => ['tail' => $tail, 'level' => $level, 'truncated' => $truncated],
        ]);
    }

    private function safeText(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }
        $value = preg_replace('/\bBearer\s+[^\s,;]+/i', 'Bearer [redacted]', $value) ?? '';
        $value = preg_replace('/\b(password|token|secret|authorization|api[_-]?key)\s*[:=]\s*(?:"[^"]*"|\x27[^\x27]*\x27|[^\s,;]+)/i', '$1=[redacted]', $value) ?? '';
        $value = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted]', $value) ?? '';

        return mb_substr($value, 0, 4096);
    }
}
