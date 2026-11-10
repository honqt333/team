<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\PaymentExceedsBalanceException;
use App\Models\Center;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PurchasePaymentSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function createPurchaseFixture(float $total = 115.00): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'مورد قطع غيار تجريبي',
            'code' => 'SUP-TEST-'.uniqid(),
            'is_active' => true,
        ]);

        $purchase = PurchaseInvoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-TEST-'.uniqid(),
            'invoice_number' => 'INV-TEST-001',
            'issue_date' => '2026-01-15',
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => round($total / 1.15, 2),
            'tax_amount' => round($total - round($total / 1.15, 2), 2),
            'total' => $total,
            'balance' => $total,
        ]);

        return [$tenant, $center, $supplier, $purchase];
    }

    public function test_purchase_payment_lifecycle_full_payment_and_deletion(): void
    {
        [, , , $purchase] = $this->createPurchaseFixture(115.00);
        $service = app(PaymentService::class);

        // 1. Pay in full
        $payment = $service->recordPurchasePayment($purchase, [
            'amount' => 115.00,
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(0.0, (float) $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_PAID, $purchase->fresh()->status);

        // 2. Delete payment -> balance restored, status reopened
        $service->deletePayment($payment);

        $this->assertEquals(115.0, (float) $purchase->fresh()->balance, 'Deleting payment must restore purchase invoice balance');
        $this->assertSame(PurchaseInvoice::STATUS_OPEN, $purchase->fresh()->status, 'Deleting payment must reopen purchase invoice status');
        $this->assertCount(0, Payment::where('purchase_invoice_id', $purchase->id)->get());
    }

    public function test_purchase_payment_partial_payments_and_incremental_deletion(): void
    {
        [, , , $purchase] = $this->createPurchaseFixture(100.00);
        $service = app(PaymentService::class);

        // Pay 40
        $pay1 = $service->recordPurchasePayment($purchase, ['amount' => 40.00]);
        $this->assertEquals(60.0, (float) $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_OPEN, $purchase->fresh()->status);

        // Pay 60 -> completes invoice
        $pay2 = $service->recordPurchasePayment($purchase, ['amount' => 60.00]);
        $this->assertEquals(0.0, (float) $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_PAID, $purchase->fresh()->status);

        // Delete second payment (60) -> balance becomes 60, status opens
        $service->deletePayment($pay2);
        $this->assertEquals(60.0, (float) $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_OPEN, $purchase->fresh()->status);

        // Delete first payment (40) -> balance becomes 100
        $service->deletePayment($pay1);
        $this->assertEquals(100.0, (float) $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_OPEN, $purchase->fresh()->status);
    }

    public function test_purchase_payment_rejects_overpayment_beyond_balance(): void
    {
        [, , , $purchase] = $this->createPurchaseFixture(100.00);
        $service = app(PaymentService::class);

        $this->expectException(PaymentExceedsBalanceException::class);
        $service->recordPurchasePayment($purchase, ['amount' => 150.00]);
    }

    public function test_purchase_payment_rejects_negative_or_zero_amount(): void
    {
        [, , , $purchase] = $this->createPurchaseFixture(100.00);
        $service = app(PaymentService::class);

        $this->expectException(InvalidArgumentException::class);
        $service->recordPurchasePayment($purchase, ['amount' => 0.00]);
    }

    public function test_purchase_payment_deletion_via_eloquent_triggers_recalculation(): void
    {
        [, , , $purchase] = $this->createPurchaseFixture(200.00);
        $service = app(PaymentService::class);

        $payment = $service->recordPurchasePayment($purchase, ['amount' => 200.00]);
        $this->assertEquals(0.0, (float) $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_PAID, $purchase->fresh()->status);

        // Deleting via Eloquent model triggers PaymentObserver which notifies listener
        $payment->delete();

        $this->assertEquals(200.0, (float) $purchase->fresh()->balance);
        $this->assertSame(PurchaseInvoice::STATUS_OPEN, $purchase->fresh()->status);
    }
}
