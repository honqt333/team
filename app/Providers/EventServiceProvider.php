<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Auth\LoginFailed;
use App\Events\Auth\LoginSuccessful;
use App\Events\Billing\ReceiptReviewed;
use App\Events\Billing\ReceiptSubmitted;
use App\Events\Customer\CustomerCreated;
use App\Events\HR\LeaveApproved;
use App\Events\HR\LeaveRequested;
use App\Events\Invoice\InvoiceIssued;
use App\Events\Part\StockLow;
use App\Events\Payment\PaymentRecorded;
use App\Events\WorkOrder\WorkOrderCreated;
use App\Events\WorkOrder\WorkOrderStatusChanged;
use App\Listeners\Auth\LogFailedLogin;
use App\Listeners\Auth\LogSuccessfulLogin;
use App\Listeners\Billing\NotifyAdminsOfReceipt;
use App\Listeners\Billing\NotifyTenantOfReceiptDecision;
use App\Listeners\Payment\HandleAutoInvoiceOnDoneWorkOrder;
use App\Listeners\Payment\LinkPaymentToInvoice;
use App\Listeners\Payment\UpdateInvoiceStatusOnPayment;
use App\Listeners\WorkOrder\LogActivityOnStatusChange;
use App\Listeners\WorkOrder\NotifyOwnerOnCreation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        WorkOrderCreated::class => [
            NotifyOwnerOnCreation::class,
        ],
        WorkOrderStatusChanged::class => [
            LogActivityOnStatusChange::class,
        ],
        PaymentRecorded::class => [
            LinkPaymentToInvoice::class,
            UpdateInvoiceStatusOnPayment::class,
            HandleAutoInvoiceOnDoneWorkOrder::class,
        ],
        // Phase 4 — Bank transfer receipt notifications. Tenant uploads
        // a receipt → admins get notified in their bell. Admin reviews
        // → tenant gets notified of the decision.
        ReceiptSubmitted::class => [
            NotifyAdminsOfReceipt::class,
        ],
        ReceiptReviewed::class => [
            NotifyTenantOfReceiptDecision::class,
        ],
        InvoiceIssued::class => [
            // (listeners can be attached here)
        ],
        CustomerCreated::class => [
            // (listeners can be attached here)
        ],
        StockLow::class => [
            // (listeners can be attached here)
        ],
        LeaveRequested::class => [
            // (listeners can be attached here)
        ],
        LeaveApproved::class => [
            // (listeners can be attached here)
        ],
        LoginSuccessful::class => [
            LogSuccessfulLogin::class,
        ],
        LoginFailed::class => [
            LogFailedLogin::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
