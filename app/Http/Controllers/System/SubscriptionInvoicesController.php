<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\BankTransferReceipt;
use App\Models\Billing\SubscriptionInvoice;
use App\Services\Invoice\SubscriptionInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionInvoicesController extends Controller
{
    protected SubscriptionInvoiceService $invoiceService;

    public function __construct(SubscriptionInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * List all subscription invoices.
     */
    public function index(Request $request): Response
    {
        $query = SubscriptionInvoice::with(['tenant', 'subscription.plan']);

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($q) => $q->where('trade_name', 'like', "%{$search}%"));
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
        $today = now()->startOfDay();

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
        $invoice->load(['tenant', 'subscription.plan', 'installments']);

        return Inertia::render('System/Invoices/Show', [
            'invoice' => $invoice,
            'pdfUrl' => $this->invoiceService->getPdfUrl($invoice),
        ]);
    }

    /**
     * Download invoice PDF.
     */
    public function download(SubscriptionInvoice $invoice)
    {
        $pdfPath = $this->invoiceService->getPdfPath($invoice);

        return response()->download($pdfPath, "{$invoice->invoice_number}.pdf");
    }

    /**
     * Send invoice by email.
     */
    public function send(SubscriptionInvoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan']);

        $email = $invoice->tenant->email;

        if (! $email) {
            return back()->with('error', 'لا يوجد بريد إلكتروني للمستأجر');
        }

        Mail::to($email)->send(new SubscriptionInvoiceMail($invoice));

        return back()->with('success', 'تم إرسال الفاتورة بنجاح');
    }

    /**
     * Regenerate PDF.
     */
    public function regeneratePdf(SubscriptionInvoice $invoice)
    {
        $this->invoiceService->generatePdf($invoice);

        return back()->with('success', 'تم إعادة إنشاء ملف PDF');
    }

    /**
     * Mark as paid manually.
     */
    public function markPaid(Request $request, SubscriptionInvoice $invoice)
    {
        $validated = $request->validate([
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $this->invoiceService->markAsPaid(
            $invoice,
            'manual',
            $validated['reference'] ?? 'MANUAL-'.time(),
            ['notes' => $validated['notes'] ?? null, 'marked_by' => auth()->id()]
        );

        // Activate subscription if pending
        if ($invoice->subscription && $invoice->subscription->status !== 'active') {
            $invoice->subscription->update(['status' => 'active']);
            $invoice->tenant->update(['status' => 'active']);
        }

        return back()->with('success', 'تم تأكيد الدفع بنجاح');
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
    public function cancel(SubscriptionInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'لا يمكن إلغاء فاتورة مدفوعة');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'cancelled']);

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

                    // Cancel payments on this invoice
                    $subscription->payments()
                        ->where('subscription_invoice_id', $invoice->id)
                        ->whereIn('status', ['pending', 'pending_payment'])
                        ->update(['status' => 'cancelled']);

                    // Supersede pending bank transfer receipts
                    BankTransferReceipt::where('subscription_id', $subscription->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'superseded',
                            'reviewed_by' => auth('admin')->id(),
                            'reviewed_at' => now(),
                            'review_notes' => 'Invoice #'.$invoice->invoice_number.' cancelled by admin — receipt superseded.',
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

        return back()->with('success', 'تم إلغاء الفاتورة');
    }
}
