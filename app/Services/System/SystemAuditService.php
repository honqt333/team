<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Central audit trail for the /system admin panel.
 *
 * ## Why this exists
 *
 * The panel mutates platform-level state: tenant lifecycle, payments,
 * subscriptions, plans, settings, integrations, credit balances. The
 * constitution requires every such change to leave an audit trail, and
 * an operator asking "who changed this, and when?" must be able to get
 * an answer.
 *
 * Several controllers grew their own inline logging, and some had none
 * at all. This service centralises the mechanics so a controller only
 * has to say WHAT happened.
 *
 * ## The two-identity problem
 *
 * `admin_activity_logs.admin_user_id` is NOT NULL and carries a foreign
 * key to `admin_users.id`. But the panel is reachable through two
 * different identities:
 *
 *   - an `AdminUser` on the 'admin' guard (the normal case), and
 *   - a tenant `User` flagged `is_system_admin` on the 'web' guard.
 *
 * For the second kind there is no `admin_users` row, so writing the
 * tenant user's id into `admin_user_id` would either violate the FK or
 * — worse — point the log at an unrelated admin who happens to share
 * the id. We therefore record the tenant-session actor in a dedicated
 * column-free way: the log row keeps a valid `admin_user_id` only when
 * the actor really is an AdminUser, and otherwise records the actor's
 * identity inside the `changes` payload.
 */
class SystemAuditService
{
    /**
     * Record an action performed by the current request's actor.
     *
     * @param string $action A short machine-readable verb, e.g.
     *                       'tenant_suspend', 'receipt_approve'.
     * @param string|null $modelType The FQCN of the affected model.
     * @param mixed|null $modelId The affected model's id.
     * @param array $changes Extra context for the audit trail.
     */
    public function log(
        string $action,
        ?string $modelType = null,
        mixed $modelId = null,
        array $changes = [],
        ?Request $request = null
    ): ?AdminActivityLog {
        $request ??= request();

        $actor = $this->resolveActor($request);

        $payload = $changes;

        // Record WHO did it, in a way that survives both identity
        // kinds. `admin_user_id` only carries a value when the actor is
        // a real AdminUser; the human-readable actor always lands in
        // the changes payload.
        $payload['actor'] = $actor['label'];
        $payload['actor_type'] = $actor['type'];

        try {
            return AdminActivityLog::create([
                'admin_user_id' => $actor['admin_user_id'],
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'changes' => $payload,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable $e) {
            // An audit failure must NEVER break the operation the
            // operator asked for. Log it loudly and carry on.
            Log::error('System audit log write failed', [
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve the acting identity for the current request.
     *
     * `$request->user()` honours the guard that the `auth:web,admin`
     * middleware activated, so it returns the AdminUser for an admin
     * session and the tenant User for a tenant-session system admin.
     *
     * @return array{admin_user_id: int|null, label: string, type: string}
     */
    private function resolveActor(Request $request): array
    {
        $user = $request->user();

        if ($user instanceof AdminUser) {
            return [
                'admin_user_id' => $user->id,
                'label' => $user->name ?: $user->email,
                'type' => 'admin',
            ];
        }

        if ($user instanceof User) {
            // A tenant-session system admin has no `admin_users` row,
            // so `admin_user_id` stays NULL (the column is nullable
            // since the 2026_11_06_012200 migration) and the identity
            // lives in the `changes` payload instead. Writing the
            // tenant user's id here would point the log at an
            // unrelated admin who happens to share the id.
            return [
                'admin_user_id' => null,
                'label' => $user->name ?: $user->email,
                'type' => 'tenant_system_admin',
            ];
        }

        return [
            'admin_user_id' => null,
            'label' => 'unknown',
            'type' => 'unknown',
        ];
    }
}
