<?php

namespace App\Providers;

use App\Events\Auth\LoginFailed;
use App\Events\Auth\LoginSuccessful;
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
            UpdateInvoiceStatusOnPayment::class,
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
