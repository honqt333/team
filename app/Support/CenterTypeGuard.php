<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Center;
use Illuminate\Support\Facades\DB;

/**
 * Centralized business rules for `center_type` and the `is_main` flag.
 *
 * Rule: Each tenant can have AT MOST ONE main center.
 * A center is "main" when EITHER `is_main = true` OR `center_type = 'main'`.
 *
 * When a user designates a center as main (via `is_main` flag or
 * `center_type = 'main'`), the previously designated main center
 * — if any — has its `is_main` flag unset and `center_type` reset
 * to 'branch' automatically.
 */
class CenterTypeGuard
{
    /**
     * Apply the "single main center" rule for a given center.
     *
     * If the incoming payload marks the center as main (either via
     * `is_main = true` or `center_type = 'main'`), this method will:
     *   1. Promote the given center to main (is_main=true, center_type=main)
     *   2. Locate the previous main center for the same tenant and demote
     *      it (is_main=false, center_type='branch')
     *
     * If the payload does NOT mark the center as main, this method
     * does nothing — the caller is responsible for deciding what
     * `center_type` and `is_main` should be for that case.
     *
     * Idempotent — safe to call on a center that is already main.
     *
     * @param Center $center The center being saved.
     * @param array $payload The validated request payload (may contain
     *                       `center_type` and/or `is_main`).
     * @return array Mutated payload (with normalized `is_main` and
     *               `center_type` if the center was promoted).
     */
    public static function applyMainRule(Center $center, array $payload): array
    {
        $wantsMain = ($payload['is_main'] ?? $center->is_main) === true
            || ($payload['center_type'] ?? null) === 'main';

        if (! $wantsMain) {
            return $payload;
        }

        // Normalize: if this center becomes main, force the flag on
        $payload['is_main'] = true;
        $payload['center_type'] = 'main';

        DB::transaction(function () use ($center) {
            // Demote any other main center for the same tenant
            Center::where('tenant_id', $center->tenant_id)
                ->where('id', '!=', $center->id)
                ->where(function ($q) {
                    $q->where('is_main', true)
                        ->orWhere('center_type', 'main');
                })
                ->update([
                    'is_main' => false,
                    'center_type' => 'branch',
                ]);

            // Promote the current center if it isn't already
            // (using a direct DB update to keep the in-memory model
            // consistent after the transaction)
            $center->refresh();

            if (! $center->is_main || $center->center_type !== 'main') {
                $center->forceFill([
                    'is_main' => true,
                    'center_type' => 'main',
                ])->save();
            }
        });

        return $payload;
    }
}
