<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Mail\SubscriptionInvoiceMail;
use App\Models\Billing\SubscriptionInvoice;
use App\Services\Invoice\SubscriptionInvoiceService;
use Illuminate\Http\Request;
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
                  ->orWhereHas('tenant', fn($q) => $q->where('trade_name', 'like', "%{$search}%"));
            });
        }
        
        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        // Stats
        $stats = [
            'total' => SubscriptionInvoice::count(),
            'pending' => SubscriptionInvoice::where('status', 'pending')->count(),
            'paid' => SubscriptionInvoice::where('status', 'paid')->count(),
            'overdue' => SubscriptionInvoice::where('status', 'pending')
                ->where('due_date', '<', now())->count(),
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
        
        if (!$email) {
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
            $validated['reference'] ?? 'MANUAL-' . time(),
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
     */
    public function cancel(SubscriptionInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'لا يمكن إلغاء فاتورة مدفوعة');
        }
        
        $invoice->update(['status' => 'cancelled']);
        
        return back()->with('success', 'تم إلغاء الفاتورة');
    }
}
