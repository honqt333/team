<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\Invoice\SubscriptionInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SubscriptionInvoicesController extends Controller
{
    protected SubscriptionInvoiceService $invoiceService;

    protected SubscriptionLifecycleService $lifecycle;

    public function __construct(
        SubscriptionInvoiceService $invoiceService,
        ?SubscriptionLifecycleService $lifecycle = null
    ) {
        $this->invoiceService = $invoiceService;
        $this->lifecycle = $lifecycle ?? app(SubscriptionLifecycleService::class);
    }

    /**
     * List all subscription invoices.
     */
    public function index(Request $request): Response
    {
        $query = SubscriptionInvoice::with(['tenant', 'subscription.plan']);

        // Filters
        $today = now()->startOfDay();

        if ($request->status) {
            if ($request->status === 'overdue') {
                $query->where('status', 'pending')
                    ->where('due_at', '<', $today);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tq) use ($search) {
                        $tq->where('trade_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Stats — single query for all counts to avoid N+1 / multiple
        // round-trips. The 2026_07_25_140000 migration renamed due_date
        // to due_at, so the overdue filter was previously a silent no-op
        // (column didn't exist in SQLite, no error in MySQL but 0 rows
        // matched because the column literally wasn't there).
        $stats = [
            'total' => SubscriptionInvoice::count(),
            'pending' => SubscriptionInvoice::where('status', 'pending')->count(),
            'paid' => SubscriptionInvoice::where('status', 'paid')->count(),
            'overdue' => SubscriptionInvoice::where('status', 'pending')
                ->where('due_at', '<', $today)
                ->count(),
            'total_revenue' => SubscriptionInvoice::where('status', 'paid')->sum('total'),
        ];

        return Inertia::render('System/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show invoice details.
     */
    public function show(SubscriptionInvoice $invoice): Response
    {
        $invoice->load([
            'tenant',
            'subscription.plan',
            'payments' => fn ($q) => $q->latest('id'),
        ]);

        return Inertia::render('System/Invoices/Show', [
            'invoice' => $invoice,
            'pdfUrl' => $this->invoiceService->getPdfUrl($invoice),
        ]);
    }

    /**
     * Download invoice PDF.
     */
    public function download(Request $request, SubscriptionInvoice $invoice)
    {
        $pdfPath = $this->invoiceService->getPdfPath($invoice);

        if ($request->boolean('inline')) {
            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$invoice->invoice_number}.pdf\"",
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return response()->download($pdfPath, "{$invoice->invoice_number}.pdf");
    }

    /**
     * Send invoice by email.
     */
    public function send(SubscriptionInvoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan']);

        $email = $invoice->tenant?->email;

        if (! $email) {
            return back()->with('error', __('messages.system.tenants.no_email'));
        }

        Mail::to($email)->send(new SubscriptionInvoiceMail($invoice));

        return back()->with('success', __('messages.system.invoices.sent'));
    }

    /**
     * Regenerate PDF.
     */
    public function regeneratePdf(SubscriptionInvoice $invoice)
    {
        $this->invoiceService->refreshPdf($invoice);

        return back()->with('success', __('messages.system.invoices.pdf_regenerated'));
    }

    /**
     * Mark as paid manually (cash at office, POS, bank transfer, or manual settlement).
     */
    public function markPaid(Request $request, SubscriptionInvoice $invoice)
    {
        $validated = $request->validate([
            'payment_method' => 'nullable|in:cash,bank_transfer,pos,manual',
            'reference' => 'nullable|string|max:255',
            'paid_amount' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($invoice->status === 'paid') {
            return back()->with('success', __('messages.system.invoices.paid'));
        }

        $actor = $request->user();
        $markedBy = $actor
            ? $actor::class.'#'.$actor->id
            : 'unknown';

        $method = $validated['payment_method'] ?? 'manual';
        $reference = ! empty($validated['reference']) ? $validated['reference'] : strtoupper($method).'-'.time();
        $paidAmount = isset($validated['paid_amount'])
            ? (float) $validated['paid_amount']
            : (float) $invoice->total;

        DB::transaction(function () use ($invoice, $method, $reference, $paidAmount, $validated, $markedBy) {
            $this->invoiceService->markAsPaid(
                $invoice,
                $method,
                $reference,
                ['notes' => $validated['notes'] ?? null, 'marked_by' => $markedBy]
            );

            // Record an actual SubscriptionPayment row so analytics, receipt/payment
            // history, and subscription resume checks recognize the settlement.
            $existingPendingPayment = $invoice->payments()
                ->whereIn('status', ['pending', 'pending_payment', 'initiated'])
                ->latest('id')
                ->first();

            if ($existingPendingPayment) {
                $existingPendingPayment->update([
                    'amount' => $paidAmount,
                    'gateway' => $method,
                    'payment_method' => $method,
                    'gateway_ref' => $reference,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'failure_reason' => null,
                    'gateway_response' => [
                        'recorded_by' => $markedBy,
                        'notes' => $validated['notes'] ?? null,
                    ],
                ]);
            } else {
                SubscriptionPayment::create([
                    'tenant_id' => $invoice->tenant_id,
                    'subscription_id' => $invoice->subscription_id,
                    'subscription_invoice_id' => $invoice->id,
                    'amount' => $paidAmount,
                    'currency' => PaymentSettings::getPlatformCurrency(),
                    'gateway' => $method,
                    'payment_method' => $method,
                    'gateway_ref' => $reference,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => [
                        'recorded_by' => $markedBy,
                        'notes' => $validated['notes'] ?? null,
                    ],
                ]);
            }

            // Activate subscription if pending or inactive
            if ($invoice->subscription && $invoice->subscription->status !== 'active') {
                $this->lifecycle->activateSubscription($invoice->subscription);
            }
        });

        try {
            $this->invoiceService->refreshPdf($invoice);
        } catch (Throwable) {
            // Paid transaction is already committed; invalidate stale PDF so next view regenerates.
            $this->invoiceService->invalidatePdf($invoice);
        }

        return back()->with('success', __('messages.system.invoices.paid'));
    }

    /**
     * Cancel invoice.
     *
     * Cascades to:
     *   - The parent subscription (mark as cancelled so it doesn't
     *     show as the tenant's "current" subscription any more — they
     *     would otherwise see a pending sub on the dashboard forever)
     *   - Any payments on this invoice (mark as cancelled so the
     *     payment history doesn't show a "pending" payment that was
     *     really cancelled)
     *   - Any pending bank transfer receipts linked to those payments
     *     (mark as superseded so the admin queue doesn't hold them and
     *     so a late approval can't re-activate a cancelled sub)
     *
     * Without these cascades, cancelling just the invoice left the
     * tenant's subscription page in a stale "pending" state — the
     * dashboard said "current subscription = pending, pending
     * payment, receipt under review" even though the admin had
     * clearly cancelled the whole thing.
     */
    public function cancel(Request $request, SubscriptionInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', __('messages.system.invoices.cannot_cancel_paid'));
        }

        $actor = $request->user();
        $reviewerAdminId = $actor instanceof AdminUser ? $actor->id : null;
        $actorNote = $actor ? ($actor->name ?? 'admin').' ('.$actor::class.'#'.$actor->id.')' : 'admin';

        DB::transaction(function () use ($invoice, $reviewerAdminId, $actorNote) {
            $invoice->update(['status' => 'cancelled']);

            // Cancel payments linked directly to this invoice
            SubscriptionPayment::where('subscription_invoice_id', $invoice->id)
                ->whereIn('status', ['pending', 'pending_payment', 'initiated'])
                ->update(['status' => 'cancelled']);

            // Cancel the parent subscription (if it has no other open
            // invoices). If it has another open invoice, leave the
            // subscription in pending_payment so the tenant can pay
            // the other one.
            $subscription = $invoice->subscription;

            if ($subscription) {
                $hasOtherOpenInvoice = $subscription->invoices()
                    ->where('id', '!=', $invoice->id)
                    ->whereIn('status', ['pending', 'pending_payment'])
                    ->exists();

                if (! $hasOtherOpenInvoice) {
                    $subscription->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);

                    // Supersede pending bank transfer receipts
                    BankTransferReceipt::where('subscription_id', $subscription->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'superseded',
                            'reviewed_by' => $reviewerAdminId,
                            'reviewed_at' => now(),
                            'review_notes' => 'Invoice #'.$invoice->invoice_number.' cancelled by '.$actorNote.' — receipt superseded.',
                        ]);

                    // NOTE: We deliberately do NOT change tenant.status
                    // here. Cancelling an invoice is not the same as
                    // suspending the tenant — they may have other
                    // subscriptions, or the admin may have clicked
                    // cancel by mistake. Tenant suspension is a
                    // separate, explicit action in the tenant UI.
                }
            }
        });

        try {
            $this->invoiceService->refreshPdf($invoice);
        } catch (Throwable) {
            $this->invoiceService->invalidatePdf($invoice);
        }

        return back()->with('success', __('messages.system.invoices.cancelled'));
    }
}
