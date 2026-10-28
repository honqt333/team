<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Integration\Integration;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for personal data written to the application log
 * (audit findings #63 and #65).
 *
 * THE BUGS
 *
 * 1. PhoneVerificationController::verifyOtp() logged
 *
 *       Log::info('Phone verification attempt', [
 *           'phone' => $phone,
 *           'otp'   => $request->otp,
 *           'result'=> $result,
 *       ]);
 *
 *    The six-digit code is still VALID at that moment, so anybody who
 *    can read the log (or a log backup) can complete somebody else's
 *    phone verification. The phone number is customer personal data
 *    copied outside its intended store, and `$result` carried the
 *    provider's raw response body.
 *
 * 2. AuthenticaService logged the raw phone number in three places,
 *    and the raw provider body in one of them.
 *
 * 3. EmployeeController::store() logged `$request->all()` — the
 *    employee's name, phone, email and nationality — and it did so
 *    BEFORE the permission check, so even a rejected request produced
 *    a full copy of the personal data in the log.
 *
 * THE FIX
 *
 *   - A non-reversible phone fingerprint (PhoneNumber::fingerprint)
 *     replaces the raw number: an HMAC keyed with the application
 *     key, so it cannot be reversed by brute force over the small
 *     space of real phone numbers.
 *   - The code and the raw provider body are no longer logged.
 *   - The employee log line carries only non-identifying context, and
 *     the authorization check moved ahead of it.
 *
 * NOTE ON THE LOG LISTENER
 *
 * `Log::spy()` cannot be used on the /app routes: the SetPermissionsTeam
 * middleware calls `Log::channel('single')->debug(...)`, and a spied
 * log manager returns null for `channel()`. `Log::listen()` observes
 * the real logger without replacing it, so the middleware keeps
 * working.
 */
class SensitiveDataInLogsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+966501234567';

    private const OTP = '123456';

    /**
     * @var array<int, array{message: string, context: mixed}>
     */
    private array $captured = [];

    protected function setUp(): void
    {
        parent::setUp();

        Integration::create([
            'type' => 'sms',
            'provider' => 'authentica',
            'name' => 'Authentica',
            'name_ar' => 'أوثينتيكا',
            'config' => [
                'api_key' => 'test-api-key',
                'template_id' => 1,
            ],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->captured = [];

        Log::listen(function ($event) {
            $this->captured[] = [
                'message' => $event->message,
                'context' => $event->context,
            ];
        });
    }

    /**
     * Every captured log line whose message matches, as JSON.
     */
    private function linesMatching(string $message): array
    {
        $out = [];

        foreach ($this->captured as $line) {
            if ($line['message'] === $message) {
                $out[] = json_encode($line['context']);
            }
        }

        return $out;
    }

    /**
     * The registration OTP verification flow, exercised over HTTP
     * with the SMS provider faked.
     */
    private function attemptVerification(): void
    {
        Http::fake([
            'api.authentica.sa/*' => Http::response(['status' => false, 'message' => 'Invalid OTP'], 200),
        ]);

        $this->withSession([
            'phone_verification_number' => self::PHONE,
        ])->post('/phone/verify-otp', [
            'otp' => self::OTP,
        ]);
    }

    public function test_the_verification_log_does_not_contain_the_otp_code(): void
    {
        $this->attemptVerification();

        $lines = $this->linesMatching('Phone verification attempt');

        $this->assertNotEmpty($lines, 'The verification attempt must still be logged.');

        foreach ($lines as $json) {
            $this->assertStringNotContainsString(
                self::OTP,
                $json,
                'The OTP code must never be written to the application log.'
            );
        }
    }

    public function test_the_verification_log_does_not_contain_the_phone_number(): void
    {
        $this->attemptVerification();

        $lines = $this->linesMatching('Phone verification attempt');

        $this->assertNotEmpty($lines, 'The verification attempt must still be logged.');

        foreach ($lines as $json) {
            $this->assertStringNotContainsString(
                self::PHONE,
                $json,
                'The phone number must not be written to the application log.'
            );
            $this->assertStringNotContainsString(
                '501234567',
                $json,
                'The phone number must not be written to the application log.'
            );
        }
    }

    public function test_the_verification_log_only_carries_non_identifying_keys(): void
    {
        $this->attemptVerification();

        $lines = $this->linesMatching('Phone verification attempt');

        $this->assertNotEmpty($lines, 'The verification attempt must still be logged.');

        // The line's own keys, plus the global log context that
        // SentryContext::shareContext() adds to every record
        // (correlation_id, route, method) — technical context, not
        // personal data.
        $allowed = [
            'phone_ref',
            'success',
            'message',
            'correlation_id',
            'route',
            'method',
        ];

        foreach ($this->captured as $line) {
            if ($line['message'] !== 'Phone verification attempt') {
                continue;
            }

            $actual = is_array($line['context']) ? array_keys($line['context']) : [];

            foreach ($actual as $key) {
                $this->assertContains(
                    $key,
                    $allowed,
                    "The verification log line carries a forbidden key '{$key}'."
                );
            }
        }
    }

    /**
     * The fingerprint must be stable for the same number (so log lines
     * can still be correlated) and must not contain the number.
     */
    public function test_the_phone_fingerprint_is_stable_and_non_reversible(): void
    {
        $a = PhoneNumber::fingerprint(self::PHONE);
        $b = PhoneNumber::fingerprint(self::PHONE);
        $c = PhoneNumber::fingerprint('+966501234568');

        $this->assertSame($a, $b, 'The fingerprint must be stable for the same number.');
        $this->assertNotSame($a, $c, 'Different numbers must produce different fingerprints.');
        $this->assertStringNotContainsString('501234567', (string) $a);
        $this->assertStringStartsWith('ph_', (string) $a);
    }

    public function test_the_phone_fingerprint_handles_an_empty_number(): void
    {
        $this->assertNull(PhoneNumber::fingerprint(null));
        $this->assertNull(PhoneNumber::fingerprint(''));
    }

    private function employeeUser(bool $withPermission): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        Permission::findOrCreate(Permissions::HR_EMPLOYEES_CREATE, 'web');

        if ($withPermission) {
            $user->givePermissionTo(Permissions::HR_EMPLOYEES_CREATE);
            $user->refresh();
        }

        return $user;
    }

    private function employeePayload(): array
    {
        return [
            'name_ar' => 'سارة السرية',
            'name_en' => 'Confidential Sarah',
            'phone' => '+966509876543',
            'email' => 'confidential.sarah@example.com',
            'gender' => 'female',
            'nationality_id' => 1,
            'job_title_id' => 1,
        ];
    }

    /**
     * The employee create path must not copy the request body — name,
     * phone, email — into the log.
     *
     * The caller here DOES hold the employee-create permission, so the
     * request reaches the logging line. The assertion is that even for
     * an accepted request the personal fields are not copied into the
     * log.
     */
    public function test_employee_create_does_not_log_the_request_body(): void
    {
        $user = $this->employeeUser(withPermission: true);

        $this->actingAs($user)->postJson('/app/hr/employees', $this->employeePayload());

        $lines = $this->linesMatching('Employee store initiated');

        $this->assertNotEmpty($lines, 'The employee creation must still be logged.');

        foreach ($lines as $json) {
            $this->assertStringNotContainsString(
                'سارة السرية',
                $json,
                'The employee name must not be written to the application log.'
            );
            $this->assertStringNotContainsString(
                'Confidential Sarah',
                $json,
                'The employee name must not be written to the application log.'
            );
            $this->assertStringNotContainsString(
                '+966509876543',
                $json,
                'The employee phone must not be written to the application log.'
            );
            $this->assertStringNotContainsString(
                'confidential.sarah@example.com',
                $json,
                'The employee email must not be written to the application log.'
            );
        }
    }

    /**
     * A request that fails the permission check must not leave a copy
     * of the personal data in the log either. The authorization check
     * runs before anything is written, so the log line is never
     * reached for a rejected request.
     */
    public function test_a_rejected_employee_request_logs_no_personal_data(): void
    {
        $user = $this->employeeUser(withPermission: false);

        $response = $this->actingAs($user)->postJson('/app/hr/employees', $this->employeePayload());

        $response->assertForbidden();

        $lines = $this->linesMatching('Employee store initiated');

        $this->assertSame(
            [],
            $lines,
            'A rejected employee request must not produce a log line at all, '
            .'because the authorization check now runs first.'
        );
    }
}
