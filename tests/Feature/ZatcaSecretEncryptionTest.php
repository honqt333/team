<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantZatcaSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

/**
 * Proves the ZATCA secret material is encrypted at rest and still
 * readable through the model.
 *
 * WHAT IS COVERED
 *
 * tenant_zatca_settings held four pieces of sensitive material in
 * plaintext: csr_content, private_key, compliance_secret and
 * production_secret. The private key is the credential that can sign
 * a tenant's invoices to ZATCA, so a database dump was enough to
 * impersonate that tenant.
 *
 * These are now encrypted with the application key. The
 * `secrets_encrypted` flag keeps the data conversion independent of
 * the code deploy: a row that has not been converted yet still reads
 * as usable plaintext.
 */
class ZatcaSecretEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private const PLAINTEXT_PRIVATE_KEY = "-----BEGIN EC PRIVATE KEY-----\nSECRETMATERIAL\n-----END EC PRIVATE KEY-----\n";

    private const PLAINTEXT_SECRET = 'PROD_SEC_abcdefghijklmnopqrstuvwxyz012345';

    public function test_a_secret_written_through_the_model_is_stored_encrypted(): void
    {
        $tenant = Tenant::factory()->create();

        $setting = TenantZatcaSetting::create([
            'tenant_id' => $tenant->id,
            'environment' => TenantZatcaSetting::ENV_SIMULATION,
            'onboarding_status' => TenantZatcaSetting::STATUS_CSR_READY,
            'egs_serial_number' => 'EGS-1',
            'csr_content' => 'CSR-PLAINTEXT-CONTENT',
            'private_key' => self::PLAINTEXT_PRIVATE_KEY,
        ]);

        // Read the raw column, bypassing the model entirely.
        $raw = DB::table('tenant_zatca_settings')->where('id', $setting->id)->first();

        $this->assertNotSame(
            self::PLAINTEXT_PRIVATE_KEY,
            $raw->private_key,
            'The private key must not be readable in the database.'
        );
        $this->assertStringNotContainsString(
            'SECRETMATERIAL',
            (string) $raw->private_key,
            'No part of the private key may appear in plaintext in the database.'
        );
        $this->assertNotSame('CSR-PLAINTEXT-CONTENT', $raw->csr_content);
        $this->assertTrue((bool) $raw->secrets_encrypted, 'The row must be flagged as encrypted.');
    }

    public function test_an_encrypted_secret_is_read_back_as_the_original_plaintext(): void
    {
        $tenant = Tenant::factory()->create();

        $setting = TenantZatcaSetting::create([
            'tenant_id' => $tenant->id,
            'environment' => TenantZatcaSetting::ENV_PRODUCTION,
            'onboarding_status' => TenantZatcaSetting::STATUS_ACTIVE,
            'csr_content' => 'CSR-PLAINTEXT-CONTENT',
            'private_key' => self::PLAINTEXT_PRIVATE_KEY,
            'compliance_secret' => 'COMP_SEC_123',
            'production_secret' => self::PLAINTEXT_SECRET,
        ]);

        // Re-read from the database, not from the in-memory instance.
        $fresh = TenantZatcaSetting::withoutGlobalScopes()->findOrFail($setting->id);

        $this->assertSame(self::PLAINTEXT_PRIVATE_KEY, $fresh->private_key);
        $this->assertSame('CSR-PLAINTEXT-CONTENT', $fresh->csr_content);
        $this->assertSame('COMP_SEC_123', $fresh->compliance_secret);
        $this->assertSame(self::PLAINTEXT_SECRET, $fresh->production_secret);
    }

    public function test_a_row_that_was_never_converted_still_returns_plaintext(): void
    {
        // This is the state of a row written before the encryption
        // migration ran, or before the code was deployed. The model
        // must keep working on it — that is the whole point of the
        // `secrets_encrypted` flag.
        $tenant = Tenant::factory()->create();

        $id = DB::table('tenant_zatca_settings')->insertGetId([
            'tenant_id' => $tenant->id,
            'environment' => TenantZatcaSetting::ENV_SIMULATION,
            'onboarding_status' => TenantZatcaSetting::STATUS_CSR_READY,
            'csr_content' => 'LEGACY-CSR',
            'private_key' => 'LEGACY-PRIVATE-KEY',
            'secrets_encrypted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = TenantZatcaSetting::withoutGlobalScopes()->findOrFail($id);

        $this->assertFalse($row->hasEncryptedSecrets());
        $this->assertSame('LEGACY-CSR', $row->csr_content);
        $this->assertSame('LEGACY-PRIVATE-KEY', $row->private_key);
    }

    public function test_updating_a_secret_on_an_already_encrypted_row_keeps_it_encrypted(): void
    {
        $tenant = Tenant::factory()->create();

        $setting = TenantZatcaSetting::create([
            'tenant_id' => $tenant->id,
            'private_key' => self::PLAINTEXT_PRIVATE_KEY,
        ]);

        $setting->production_secret = 'ROTATED_SECRET_VALUE';
        $setting->save();

        $raw = DB::table('tenant_zatca_settings')->where('id', $setting->id)->first();

        $this->assertNotSame(
            'ROTATED_SECRET_VALUE',
            $raw->production_secret,
            'A rotated secret must not be written in plaintext.'
        );

        $fresh = TenantZatcaSetting::withoutGlobalScopes()->findOrFail($setting->id);
        $this->assertSame('ROTATED_SECRET_VALUE', $fresh->production_secret);
        $this->assertSame(self::PLAINTEXT_PRIVATE_KEY, $fresh->private_key, 'Unchanged secrets must survive.');
    }

    public function test_the_secret_columns_are_not_exposed_by_the_models_array_form(): void
    {
        $tenant = Tenant::factory()->create();

        $setting = TenantZatcaSetting::create([
            'tenant_id' => $tenant->id,
            'private_key' => self::PLAINTEXT_PRIVATE_KEY,
            'production_secret' => self::PLAINTEXT_SECRET,
        ]);

        $array = $setting->toArray();

        // The accessors decrypt on read, so without an explicit
        // $hidden the array form would carry the plaintext private key
        // and CSID secrets. The secret columns must be absent entirely
        // so a future `Inertia::render(..., ['setting' => $setting])`
        // cannot leak them to the browser.
        foreach (TenantZatcaSetting::SECRET_COLUMNS as $column) {
            $this->assertArrayNotHasKey(
                $column,
                $array,
                "The secret column {$column} must not appear in the model's array form."
            );
        }

        $this->assertStringNotContainsString('SECRETMATERIAL', json_encode($array));
        $this->assertStringNotContainsString('PROD_SEC_abcdefghijklmnopqrstuvwxyz012345', json_encode($array));
    }

    /**
     * The data-conversion migration must be reversible: it encrypts the
     * existing plaintext rows on the way up and puts the plaintext back
     * on the way down.
     */
    public function test_the_conversion_migration_is_reversible(): void
    {
        $tenant = Tenant::factory()->create();

        // A row in the pre-migration state: plaintext, flag = false.
        $id = DB::table('tenant_zatca_settings')->insertGetId([
            'tenant_id' => $tenant->id,
            'environment' => TenantZatcaSetting::ENV_SIMULATION,
            'onboarding_status' => TenantZatcaSetting::STATUS_CSR_READY,
            'csr_content' => 'LEGACY-CSR',
            'private_key' => 'LEGACY-PRIVATE-KEY',
            'secrets_encrypted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path('database/migrations/2026_10_25_190000_encrypt_tenant_zatca_secrets.php');

        // ── up() direction ────────────────────────────────────────────
        $this->callMigrationConversion($migration, 'up');

        $raw = DB::table('tenant_zatca_settings')->find($id);
        $this->assertNotSame('LEGACY-CSR', $raw->csr_content, 'The CSR must be encrypted after the conversion.');
        $this->assertNotSame('LEGACY-PRIVATE-KEY', $raw->private_key);
        $this->assertTrue((bool) $raw->secrets_encrypted, 'The row must be flagged as encrypted.');

        // The model must still return the original plaintext.
        $model = TenantZatcaSetting::withoutGlobalScopes()->findOrFail($id);
        $this->assertSame('LEGACY-CSR', $model->csr_content);
        $this->assertSame('LEGACY-PRIVATE-KEY', $model->private_key);

        // ── down() direction ──────────────────────────────────────────
        $this->callMigrationConversion($migration, 'down');

        $raw = DB::table('tenant_zatca_settings')->find($id);
        $this->assertSame('LEGACY-CSR', $raw->csr_content, 'The down() direction must restore the plaintext.');
        $this->assertSame('LEGACY-PRIVATE-KEY', $raw->private_key);
    }

    /**
     * Run one direction of the conversion inside the migration.
     */
    private function callMigrationConversion(object $migration, string $direction): void
    {
        $method = (new ReflectionClass($migration))->getMethod('convertRows');
        $method->setAccessible(true);

        $method->invoke(
            $migration,
            fn (string $value) => $direction === 'up' ? encrypt($value, false) : decrypt($value, false),
            $direction
        );
    }
}
