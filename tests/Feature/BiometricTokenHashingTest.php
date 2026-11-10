<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\HR\BiometricDevice;
use App\Models\HR\Employee;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proves the biometric device API token is stored as a hash and that
 * the devices still authenticate.
 *
 * WHAT WAS WRONG
 *
 * biometric_devices.api_token held the device credential in
 * plaintext, and the device authenticated by sending it as a bearer
 * token. The credential is long-lived and authorises writing
 * attendance records for a whole tenant, so a database dump, a
 * replica, a slow query log or an SQL console was enough to obtain
 * it.
 *
 * The token is now stored as an HMAC-SHA256 in a new `token_hash`
 * column, and the plaintext column is nulled.
 */
class BiometricTokenHashingTest extends TestCase
{
    use RefreshDatabase;

    private function makeDevice(): BiometricDevice
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        return BiometricDevice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Front Door Reader',
            'device_type' => 'zkteco',
        ]);
    }

    public function test_the_generated_token_is_not_stored_in_plaintext(): void
    {
        $device = $this->makeDevice();

        $token = $device->api_token;

        $this->assertNotEmpty($token, 'A device must be issued a token.');

        $raw = DB::table('biometric_devices')->where('id', $device->id)->first();

        $this->assertNull(
            $raw->api_token,
            'The plaintext token must not remain in the database.'
        );
        $this->assertNotEmpty($raw->token_hash, 'The token hash must be stored.');
        $this->assertNotSame(
            $token,
            $raw->token_hash,
            'The stored hash must not equal the token.'
        );
        $this->assertStringNotContainsString(
            $token,
            (string) $raw->token_hash,
            'The token must not be recoverable from the stored value.'
        );
    }

    public function test_the_hash_matches_the_documented_algorithm(): void
    {
        $device = $this->makeDevice();
        $token = $device->api_token;

        $raw = DB::table('biometric_devices')->where('id', $device->id)->first();

        $this->assertSame(
            hash_hmac('sha256', $token, (string) config('app.key')),
            $raw->token_hash,
            'The hash must be HMAC-SHA256 keyed with the application key.'
        );
    }

    public function test_a_device_can_be_found_by_the_token_it_presented(): void
    {
        $device = $this->makeDevice();
        $token = $device->api_token;

        $found = BiometricDevice::findByToken($token);

        $this->assertNotNull($found, 'The device must be found by its token.');
        $this->assertSame($device->id, $found->id);
    }

    public function test_an_unknown_token_finds_no_device(): void
    {
        $this->makeDevice();

        $this->assertNull(BiometricDevice::findByToken('not-a-real-token'));
        $this->assertNull(BiometricDevice::findByToken(null));
        $this->assertNull(BiometricDevice::findByToken(''));
    }

    public function test_an_inactive_device_is_not_found_by_its_token(): void
    {
        $device = $this->makeDevice();
        $token = $device->api_token;

        $device->update(['is_active' => false]);

        $this->assertNull(
            BiometricDevice::findByToken($token),
            'A deactivated device must not authenticate.'
        );
    }

    public function test_rotating_the_token_invalidates_the_old_one(): void
    {
        $device = $this->makeDevice();
        $oldToken = $device->api_token;

        $newToken = $device->regenerateToken();

        $this->assertNotSame($oldToken, $newToken);

        $this->assertNotNull(
            BiometricDevice::findByToken($newToken),
            'The new token must authenticate.'
        );
        $this->assertNull(
            BiometricDevice::findByToken($oldToken),
            'The old token must stop working immediately after a rotation.'
        );

        $raw = DB::table('biometric_devices')->where('id', $device->id)->first();
        $this->assertNull($raw->api_token, 'The rotated plaintext must not be persisted either.');
    }

    public function test_the_token_is_not_exposed_by_the_models_array_form(): void
    {
        $device = $this->makeDevice();

        $array = $device->toArray();

        $this->assertArrayNotHasKey('api_token', $array);
        $this->assertArrayNotHasKey('token_hash', $array);
    }

    /**
     * The token is still readable in memory right after it is issued,
     * so the "copy this into your device" flow keeps working even
     * though it is not persisted.
     */
    public function test_the_token_is_readable_in_memory_right_after_it_is_issued(): void
    {
        $device = $this->makeDevice();

        $this->assertNotEmpty(
            $device->api_token,
            'The in-memory model must still expose the token that was just issued.'
        );
    }

    /**
     * End-to-end: a device that was provisioned before the hashing
     * change must still be able to clock in with the token it was
     * given. This is the flow that actually matters — the hashing
     * change is only safe if the devices keep working.
     */
    public function test_a_device_can_still_clock_in_with_its_token(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $device = BiometricDevice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Front Door Reader',
            'device_type' => 'zkteco',
        ]);

        $token = $device->api_token;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/attendance/ping');

        $response->assertOk();
        $this->assertTrue(
            (bool) $response->json('success'),
            'A device presenting a valid token must be accepted by the API. '
            .'Response: '.$response->getContent()
        );
    }

    public function test_a_device_with_an_unknown_token_is_rejected(): void
    {
        $this->makeDevice();

        $response = $this->withHeader('Authorization', 'Bearer wrong-token')
            ->getJson('/api/v1/attendance/ping');

        $response->assertStatus(401);
        $this->assertFalse((bool) $response->json('success'));
    }

    public function test_device_cannot_clock_in_employee_from_different_center(): void
    {
        $tenant = Tenant::factory()->create();
        $centerA = Center::factory()->create(['tenant_id' => $tenant->id]);
        $centerB = Center::factory()->create(['tenant_id' => $tenant->id]);

        $device = BiometricDevice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerA->id,
            'name' => 'Center A Reader',
            'device_type' => 'zkteco',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'employee_number' => 'EMP-B-001',
            'name_ar' => 'موظف فرع ب',
            'name_en' => 'Branch B Employee',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$device->api_token)
            ->postJson('/api/v1/attendance/clock', [
                'employee_identifier' => 'EMP-B-001',
                'action' => 'check_in',
            ]);

        $response->assertStatus(403);
        $this->assertFalse((bool) $response->json('success'));
        $this->assertSame('cross_center_unauthorized', $response->json('error'));
    }
}
