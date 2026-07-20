# Carag V2 — Event & Listener Architecture

**Status:** Phase 1 stable (zxz82)
**Audience:** Backend developers, future maintainers

This document explains the event/listener pattern that Carag V2 uses to
keep models slim and side effects explicit.

---

## Why events?

Before Phase 1, side effects were scattered between:
- Eloquent model `boot()` methods (e.g. `Payment::boot()`)
- Inline calls in controllers (e.g. `WorkOrderStatusController::complete`)
- Service methods that did too many things

This made the data flow impossible to follow and very hard to test
without booting the entire framework.

Phase 1 refactored the most painful cases into **events** (fired by
observers) and **listeners** (registered in `EventServiceProvider::$listen`).

---

## Core events

| Event | Fired by | Listeners |
|---|---|---|
| `WorkOrderCreated` | `WorkOrderObserver` (when a new WO is created) | `NotifyOwnerOnCreation` |
| `WorkOrderStatusChanged` | `WorkOrderObserver` (when WO status changes) | `LogActivityOnStatusChange` |
| `PaymentRecorded` | `PaymentObserver` (on `created`, `updated`, `deleted`) | `LinkPaymentToInvoice`, `UpdateInvoiceStatusOnPayment`, `HandleAutoInvoiceOnDoneWorkOrder` |
| `InvoiceIssued` | `InvoiceService::issueInvoice()` | (slot — no listeners yet) |
| `CustomerCreated` | `CustomerObserver` | (slot) |
| `StockLow` | `InventoryService` | (slot) |
| `LeaveRequested` / `LeaveApproved` | HR service | (slot) |
| `LoginSuccessful` / `LoginFailed` | `AuthenticatedSessionController` | `LogSuccessfulLogin`, `LogFailedLogin` |

---

## The PaymentRecorded cascade

`PaymentRecorded` is the busiest event. It fires on every payment
mutation, and **three** listeners react:

```mermaid
Payment::create / update / delete
            │
            ▼
    PaymentObserver dispatches
            │
            ▼
   App\Events\Payment\PaymentRecorded
            │
   ┌────────┴─────────────────────┬──────────────────────┐
   ▼                              ▼                      ▼
LinkPaymentToInvoice    UpdateInvoiceStatusOnPayment   HandleAutoInvoiceOnDoneWorkOrder
   │                              │                      │
   ▼                              ▼                      ▼
Attach to WO's          Recalculate $invoice-         If WO is `done` AND balance <= 0
existing invoice        >updatePaymentStatus()        AND no invoice yet → call
(if any)                (unpaid / partial / paid)     InvoiceService::createFromWorkOrder()
```

### Why three listeners instead of one?

- **Single Responsibility**: each listener has exactly one job.
- **Reorderable**: Laravel calls listeners in registration order. If a
  future requirement says "create the invoice before recalculating its
  status", just reorder the array — no other code changes.
- **Testable in isolation**: see
  `tests/Unit/Listeners/Payment/LinkPaymentToInvoiceTest.php` and
  `tests/Unit/Listeners/Payment/HandleAutoInvoiceOnDoneWorkOrderTest.php`.
- **Debuggable**: a single `event:list` shows the full graph.

### Re-entrancy guard

`HandleAutoInvoiceOnDoneWorkOrder` calls
`InvoiceService::createFromWorkOrder()`, which in turn can call
`$workOrder->payments()->update(['invoice_id' => ...])`. That fires
`PaymentObserver::updated()`, which fires `PaymentRecorded`, which calls
this listener again.

To prevent infinite recursion, the listener sets a container-level flag:

```php
if (app()->bound('payment.auto-invoice-in-progress')) {
    return;
}
app()->instance('payment.auto-invoice-in-progress', true);

try {
    // ... create invoice, link payments, notify owner
} finally {
    app()->forgetInstance('payment.auto-invoice-in-progress');
}
```

The flag is removed even if an exception is thrown (via `finally`), so
later payment events (e.g. a real refund) still trigger invoice status
recalculation correctly.

---

## How to add a new listener

1. Create the listener class:
   ```bash
   php artisan make:listener MyNewListener
   ```
2. Implement `handle(MyEvent $event)`.
3. Register in `app/Providers/EventServiceProvider.php`:
   ```php
   MyEvent::class => [
       MyNewListener::class,
   ],
   ```
4. Add a unit test under `tests/Unit/Listeners/`.

That's it. Laravel will auto-discover the listener via the `$listen`
array — no further wiring needed.

---

## How to fire a new event

1. Create the event class:
   ```bash
   php artisan make:event MyEvent
   ```
2. Either dispatch it manually:
   ```php
   event(new MyEvent($payload));
   ```
3. Or wire it to an observer for automatic dispatch on model lifecycle.

---

## Testing listeners

Listeners are pure classes. You can test them without booting the event
dispatcher:

```php
$listener = new LinkPaymentToInvoice();
$listener->handle(new PaymentRecorded($payment));
```

For end-to-end tests, just perform the model action and assert on the
final state — the event system will fire automatically.

To assert that an event was dispatched (without running listeners), use
`Event::fake([MyEvent::class])`. To fake all events, use `Event::fake()`
without arguments. Note: `Event::fake()` swallows ALL events, so
listeners won't run — use it only when testing dispatch, not outcomes.

---

## Files of interest

- `app/Providers/EventServiceProvider.php` — registration of all listeners
- `app/Observers/PaymentObserver.php` — dispatches `PaymentRecorded`
- `app/Listeners/Payment/LinkPaymentToInvoice.php` — auto-link
- `app/Listeners/Payment/HandleAutoInvoiceOnDoneWorkOrder.php` — auto-create
- `app/Listeners/Payment/UpdateInvoiceStatusOnPayment.php` — recalc
- `app/Listeners/WorkOrder/LogActivityOnStatusChange.php` — activity log
- `app/Listeners/WorkOrder/NotifyOwnerOnCreation.php` — notification
- `app/Listeners/Auth/LogSuccessfulLogin.php` / `LogFailedLogin.php` — auth log
