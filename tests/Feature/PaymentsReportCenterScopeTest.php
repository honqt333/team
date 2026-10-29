<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the centre scope of the payments report (audit finding #66).
 *
 * The customer filter of the report was loaded with `tenant_id` only, so
 * a user of one branch received the name and the phone number of the
 * customers of every other branch of the same tenant — even though the
 * payments behind that filter were already limited to the centres of
 * the signed-in user.
 *
 * The explicit centre filter was a second way out: it was applied
 * without checking that the user may read that centre, so putting
 * another branch's id in the query returned that branch's payments.
 */
class PaymentsReportCenterScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->tenant = Tenant::factory()->create();
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function userIn(Center $center, bool $superAdmin = false): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        if ($superAdmin) {
            Role::findOrCreate('super_admin', 'web');
            $user->assignRole('super_admin');
        } else {
            // Reading the payments report needs the payments
            // permission (audit finding #51). These tests are about
            // the centre scope, so the user is given the permission
            // and the scope is what is under test.
            Permission::findOrCreate(Permissions::PAYMENTS_VIEW, 'web');
            $user->givePermissionTo(Permissions::PAYMENTS_VIEW);
        }

        $user->refresh();

        return $user;
    }

    private function customerIn(Center $center, string $name): Customer
    {
        return Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => $name,
            'phone' => '+9665'.random_int(1000000, 9999999),
        ]);
    }

    private function paymentIn(Center $center, string $reference): Payment
    {
        return Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $center->id,
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => $reference,
        ]);
    }

    public function test_the_customer_filter_hides_the_customers_of_another_centre(): void
    {
        $mine = $this->customerIn($this->centerA, 'عميل مركز أ');
        $theirs = $this->customerIn($this->centerB, 'عميل مركز ب');

        $response = $this->actingAs($this->userIn($this->centerA))
            ->get('/app/reports/payments?range=all');

        $response->assertStatus(200);

        $customers = $response->viewData('page')['props']['customers'];
        $ids = collect($customers)->pluck('id')->all();

        $this->assertContains($mine->id, $ids, 'The customer of the own centre must stay in the filter.');
        $this->assertNotContains($theirs->id, $ids, 'A customer of another centre leaked into the filter.');
        $this->assertCount(1, $customers);
    }

    public function test_asking_for_another_centres_payments_by_id_shows_no_records_at_all(): void
    {
        $mine = $this->paymentIn($this->centerA, 'PAY-A');
        $theirs = $this->paymentIn($this->centerB, 'PAY-B');

        $response = $this->actingAs($this->userIn($this->centerA))
            ->get('/app/reports/payments?range=all&center_id='.$this->centerB->id);

        $response->assertStatus(200);

        $references = collect($response->viewData('page')['props']['payments']['data'])
            ->pluck('reference')
            ->all();

        $this->assertNotContains($theirs->reference, $references, 'The centre filter was a way around the centre scope.');
        $this->assertNotContains($mine->reference, $references, 'A filter outside the scope must not fall back to the own centre.');
        $this->assertSame([], $references);
    }

    public function test_the_export_of_another_centre_is_empty_for_a_centre_user(): void
    {
        $this->paymentIn($this->centerA, 'PAY-A');
        $theirs = $this->paymentIn($this->centerB, 'PAY-B');

        $response = $this->actingAs($this->userIn($this->centerA))
            ->get('/app/reports/payments/export?range=all&center_id='.$this->centerB->id);

        $response->assertStatus(200);

        $csv = $response->streamedContent();

        $this->assertStringNotContainsString('PAY-B', $csv);
        $this->assertStringNotContainsString('PAY-A', $csv);
    }

    public function test_the_tenant_owner_still_sees_every_customer_of_the_tenant(): void
    {
        $mine = $this->customerIn($this->centerA, 'عميل مركز أ');
        $theirs = $this->customerIn($this->centerB, 'عميل مركز ب');

        $response = $this->actingAs($this->userIn($this->centerA, true))
            ->get('/app/reports/payments?range=all');

        $response->assertStatus(200);

        $ids = collect($response->viewData('page')['props']['customers'])->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertContains($theirs->id, $ids, 'The tenant owner manages every branch of the tenant.');
    }
}
