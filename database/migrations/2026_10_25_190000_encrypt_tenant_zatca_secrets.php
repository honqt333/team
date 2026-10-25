<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypt the ZATCA secret material at rest.
 *
 * WHAT THIS CHANGES
 *
 * tenant_zatca_settings held four pieces of sensitive material in
 * plaintext:
 *
 *   - csr_content        the CSR submitted to ZATCA
 *   - private_key        the EC private key generated for the tenant's
 *                        EGS (this is the credential that can sign
 *                        invoices — the most sensitive value here)
 *   - compliance_secret  the compliance CSID secret
 *   - production_secret  the production CSID secret
 *
 * A database backup, a replica, or a read-only SQL console would
 * expose all of them. These are now encrypted with the application
 * key (Laravel's `encrypted` cast), so a dump of the table alone is
 * no longer enough to impersonate a tenant to ZATCA.
 *
 * HOW IT WORKS
 *
 * 1. `secrets_encrypted` (boolean, default false) marks whether a
 *    row's secret columns hold ciphertext or plaintext. The model
 *    reads that flag to decide which cast to apply, so a row that
 *    has not been migrated yet still returns usable plaintext.
 *
 * 2. This migration encrypts every existing row in place.
 *
 * 3. The `down()` method decrypts every row back to plaintext and
 *    removes the flag, so the migration is fully reversible.
 *
 * WHY NOT ENCRYPT EVERYTHING IN ONE STEP
 *
 * The `secrets_encrypted` flag is what makes this safe to deploy
 * against a live database. Without it, a deploy that adds the
 * `encrypted` cast before the data has been converted would make
 * every existing secret undecryptable — and with it, every tenant's
 * ZATCA onboarding would break irrecoverably. With the flag, the
 * model keeps working on both plaintext and ciphertext rows, so the
 * data conversion and the code deploy are independent.
 */
return new class extends Migration
{
    /**
     * The secret columns to encrypt.
     *
     * @var list<string>
     */
    private const SECRET_COLUMNS = [
        'csr_content',
        'private_key',
        'compliance_secret',
        'production_secret',
    ];

    public function up(): void
    {
        Schema::table('tenant_zatca_settings', function (Blueprint $table) {
            // No ->after() here on purpose. `after()` is a MySQL-only
            // modifier that makes the migration depend on a specific
            // column existing, which breaks on any database where that
            // column is absent (a fresh install built from a partial
            // migration set, or a table recreated by a test harness).
            // The column order has no functional meaning.
            $table->boolean('secrets_encrypted')->default(false);
        });

        $this->convertRows(fn (string $plain) => $this->encrypt($plain), 'up');
    }

    public function down(): void
    {
        $this->convertRows(fn (string $cipher) => $this->decrypt($cipher), 'down');

        Schema::table('tenant_zatca_settings', function (Blueprint $table) {
            $table->dropColumn('secrets_encrypted');
        });
    }

    /**
     * Encrypt (up) or decrypt (down) the secret columns of every row
     * that has not been converted yet.
     */
    private function convertRows(callable $transform, string $direction): void
    {
        $rows = DB::table('tenant_zatca_settings')
            ->select(['id', ...self::SECRET_COLUMNS, 'secrets_encrypted'])
            ->get();

        foreach ($rows as $row) {
            // Skip rows that are already in the target state.
            $alreadyConverted = (bool) $row->secrets_encrypted;

            if (($direction === 'up' && $alreadyConverted)
                || ($direction === 'down' && ! $alreadyConverted)) {
                continue;
            }

            $updates = [];

            foreach (self::SECRET_COLUMNS as $column) {
                $value = $row->{$column};

                if ($value === null || $value === '') {
                    continue;
                }

                $converted = $transform((string) $value);

                // If a value fails to convert (e.g. it was already
                // ciphertext and we are decrypting), leave it alone
                // rather than corrupting it further.
                if ($converted === null) {
                    continue;
                }

                $updates[$column] = $converted;
            }

            $updates['secrets_encrypted'] = $direction === 'up';

            if ($updates !== ['secrets_encrypted' => $direction === 'up']) {
                DB::table('tenant_zatca_settings')
                    ->where('id', $row->id)
                    ->update($updates);
            }
        }
    }

    /**
     * Encrypt a value with the application key.
     *
     * @return string|null null when the value could not be encrypted
     */
    private function encrypt(string $value): ?string
    {
        try {
            return encrypt($value, false);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Decrypt a value with the application key.
     *
     * @return string|null null when the value was not valid ciphertext
     */
    private function decrypt(string $value): ?string
    {
        try {
            return decrypt($value, false);
        } catch (Throwable) {
            return null;
        }
    }
};
