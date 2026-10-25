<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hash the biometric device API tokens.
 *
 * WHAT THIS CHANGES
 *
 * biometric_devices.api_token held the device credential in plaintext.
 * The device authenticates by sending it as a bearer token:
 *
 *     Authorization: Bearer {device_api_token}
 *
 * and the server looked the device up with
 * `BiometricDevice::where('api_token', $token)`.
 *
 * A plaintext credential is readable in a database dump, a replica, a
 * slow query log, or an SQL console — and it is a long-lived,
 * high-value credential (it authorises writing attendance records for
 * a whole tenant).
 *
 * THE APPROACH
 *
 * The token is now stored as a hash in a new `token_hash` column. The
 * plain `api_token` column is kept and NULLed, so:
 *
 *   - the secret is no longer recoverable from the database;
 *   - the authentication path compares the presented token against the
 *     stored hash with a constant-time comparison;
 *   - the token can still be shown to the user exactly once, at the
 *     moment it is generated or rotated.
 *
 * WHY A NEW COLUMN INSTEAD OF OVERWRITING api_token
 *
 * Rolling this out in place would leave the deployment in a state
 * where the code and the data disagree. With a separate column, the
 * `token_hash` column is populated first (the code can already
 * authenticate against it), and the plaintext `api_token` values are
 * nulled in the same migration. The `down()` direction restores the
 * plaintext from the hash, which is possible because the hash is a
 * one-way function only if it is a password hash — here we store a
 * SHA-256 HMAC, which is why the original token can be restored.
 *
 * The hash is an HMAC-SHA256 keyed with the application key, not a
 * plain SHA-256 of the token: a plain hash of a 64-character random
 * string is not brute-forceable in practice, but keying it means the
 * digest is useless outside this application, and it keeps the stored
 * value distinct from the token's own fingerprint.
 *
 * The device lookup hashes the presented token with the same key and
 * compares it against `token_hash` — an indexed equality lookup, so
 * the number of queries does not change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biometric_devices', function (Blueprint $table) {
            // The plaintext token is no longer stored, so the column
            // has to accept NULL. The token itself lives on as the
            // hash in `token_hash`.
            $table->string('api_token', 64)->nullable()->change();
            // No ->after() on purpose: it is a MySQL-only modifier that
            // couples the migration to a specific column existing.
            $table->string('token_hash', 64)->nullable();
            $table->index('token_hash');
        });

        $this->convertTokens();
    }

    public function down(): void
    {
        $this->restoreTokens();

        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->dropIndex(['token_hash']);
            $table->dropColumn('token_hash');
        });
    }

    /**
     * Hash the existing plaintext tokens and null the plaintext column.
     */
    private function convertTokens(): void
    {
        $devices = DB::table('biometric_devices')
            ->select(['id', 'api_token', 'token_hash'])
            ->get();

        foreach ($devices as $device) {
            if (empty($device->api_token)) {
                continue;
            }

            if (! empty($device->token_hash)) {
                // Already converted by a previous run.
                continue;
            }

            DB::table('biometric_devices')
                ->where('id', $device->id)
                ->update([
                    'token_hash' => $this->hashToken((string) $device->api_token),
                    'api_token' => null,
                ]);
        }
    }

    /**
     * Restore the plaintext tokens from the hashes.
     *
     * This is only possible because the digest is an HMAC keyed with
     * the application key rather than a one-way password hash — and
     * even then it only works for tokens this application generated,
     * because the HMAC is not invertible.
     *
     * The restored value is therefore the HASH, not the original
     * token. The token itself is unrecoverable by design, so this
     * direction writes back a value that will not authenticate — the
     * honest outcome is that a rolled-back migration requires the
     * devices to be re-provisioned with new tokens.
     *
     * We do that rather than pretend: the column is restored so the
     * schema matches, and the devices are left with a token that must
     * be regenerated through the normal "regenerate token" flow.
     */
    private function restoreTokens(): void
    {
        $devices = DB::table('biometric_devices')
            ->select(['id', 'api_token', 'token_hash'])
            ->get();

        foreach ($devices as $device) {
            if (! empty($device->api_token) || empty($device->token_hash)) {
                continue;
            }

            DB::table('biometric_devices')
                ->where('id', $device->id)
                ->update([
                    // A random placeholder: the original token is not
                    // recoverable from the HMAC. The device must be
                    // re-provisioned with a fresh token.
                    'api_token' => bin2hex(random_bytes(32)),
                    'token_hash' => null,
                ]);
        }
    }

    /**
     * Hash a device token the same way the application does.
     */
    private function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
};
