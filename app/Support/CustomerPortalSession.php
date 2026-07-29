<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Customer-portal session key helper.
 *
 * The portal session is a small array of:
 *   - customer_id
 *   - tenant_id
 *   - last_seen_at (ISO 8601)
 *
 * We use a single, well-known session key so the
 * middleware and the auth controller share the same
 * payload shape. Helpers here are the only place this
 * shape is defined.
 */
class CustomerPortalSession
{
    /**
     * The session key. The `cp_` prefix is short for
     * "customer portal" and keeps the key visually
     * distinct from any other session data.
     */
    public const SESSION_KEY = 'cp_session';

    public static function key(): string
    {
        return self::SESSION_KEY;
    }
}
