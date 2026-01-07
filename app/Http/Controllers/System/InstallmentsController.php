<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\Installment;
use App\Models\Billing\SubscriptionInvoice;
use App\Services\Billing\InstallmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstallmentsController extends Controller
{
    protected InstallmentService $installmentService;

    public function __construct(InstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    /**
     * List all installments.
     */
    public function index(Request $request): Response
    {
        $query = Installment::with(['invoice.tenant', 'invoice.subscription.plan']);
        
        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('invoice.tenant', fn($q) => $q->where('trade_name', 'like', "%{$search}%"));
        }
        
        // Order by due date (upcoming first)
        $installments = $query->orderBy('due_date', 'asc')
            ->paginate(20)
            ->withQueryString();
        
        // Stats
        $stats = [
            'total' => Installment::count(),
            'pending' => Installment::where('status', 'pending')->count(),
            'overdue' => Installment::where('status', 'overdue')->count(),
            'paid' => Installment::where('status', 'paid')->count(),
            'pending_amount' => Installment::whereIn('status', ['pending', 'overdue'])->sum('amount'),
        ];
        
        return Inertia::render('System/Installments/Index', [
            'installments' => $installments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show installment details (with invoice context).
     */
    public function show(SubscriptionInvoice $invoice): Response
    {
        $invoice->load(['tenant', 'subscription.plan', 'installments']);
        
        $summary = $this->installmentService->getInstallmentSummary($invoice);
        
        return Inertia::render('System/Installments/Show', [
            'invoice' => $invoice,
            'summary' => $summary,
        ]);
    }

    /**
     * Mark installment as paid.
     */
    public function markPaid(Request $request, Installment $installment)
    {
        $validated = $request->validate([
            'reference' => 'nullable|string|max:255',
            'gateway' => 'nullable|string|max:50',
        ]);
        
        $this->installmentService->markInstallmentPaid(
            $installment,
            $validated['gateway'] ?? 'manual',
            $validated['reference']
        );
        
        return back()->with('success', 'تم تأكيد دفع القسط بنجاح');
    }

    /**
     * Update overdue installments (can be called via scheduler).
     */
    public function updateOverdue()
    {
        $count = $this->installmentService->updateOverdueInstallments();
        
        return back()->with('success', "تم تحديث {$count} قسط متأخر");
    }
}
