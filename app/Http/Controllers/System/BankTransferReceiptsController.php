<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BankTransferReceiptsController extends Controller
{
    /**
     * List pending bank transfer receipts for review.
     */
    public function index(Request $request): Response
    {
        $query = BankTransferReceipt::with(['tenant', 'subscription.plan', 'payment', 'reviewer']);

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        // Search by tenant name
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%");
            });
        }

        $receipts = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'pending' => BankTransferReceipt::where('status', 'pending')->count(),
            'approved' => BankTransferReceipt::where('status', 'approved')->count(),
            'rejected' => BankTransferReceipt::where('status', 'rejected')->count(),
            'total_amount_pending' => BankTransferReceipt::where('status', 'pending')->sum('transfer_amount'),
        ];

        return Inertia::render('System/BankTransferReceipts/Index', [
            'receipts' => $receipts,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show single receipt detail.
     */
    public function show(BankTransferReceipt $receipt): Response
    {
        $receipt->load(['tenant', 'subscription.plan', 'payment', 'reviewer']);

        return Inertia::render('System/BankTransferReceipts/Show', [
            'receipt' => $receipt,
        ]);
    }

    /**
     * Approve receipt — activates the subscription and marks payment as paid.
     */
    public function approve(Request $request, BankTransferReceipt $receipt)
    {
        if ($receipt->status !== 'pending') {
            return back()->with('error', 'هذا الإيصال تمت مراجعته مسبقاً');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($receipt, $validated) {
            // Mark receipt as approved
            $receipt->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $validated['notes'] ?? null,
            ]);

            // Mark payment as paid
            if ($receipt->payment) {
                $receipt->payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => 'bank_transfer',
                    'gateway_ref' => $receipt->transfer_reference,
                    'failure_reason' => null,
                ]);
                $receipt->payment->markAsPaid([
                    'receipt_id' => $receipt->id,
                    'transfer_amount' => $receipt->transfer_amount,
                    'transfer_date' => $receipt->transfer_date?->toDateString(),
                    'transfer_reference' => $receipt->transfer_reference,
                ]);
            }

            // Activate subscription
            if ($receipt->subscription) {
                $receipt->subscription->update(['status' => 'active']);
            }

            // Mark invoice as paid
            if ($receipt->payment?->invoice) {
                $receipt->payment->invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Activate tenant
            if ($receipt->tenant) {
                $receipt->tenant->update(['status' => 'active']);
            }
        });

        return back()->with('success', 'تم الموافقة على الإيصال وتفعيل الاشتراك');
    }

    /**
     * Reject receipt — keeps subscription in pending_payment.
     */
    public function reject(Request $request, BankTransferReceipt $receipt)
    {
        if ($receipt->status !== 'pending') {
            return back()->with('error', 'هذا الإيصال تمت مراجعته مسبقاً');
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $receipt->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['notes'],
        ]);

        return back()->with('success', 'تم رفض الإيصال');
    }

    /**
     * Download the receipt file.
     */
    public function download(BankTransferReceipt $receipt)
    {
        if (! Storage::disk('public')->exists($receipt->receipt_path)) {
            abort(404, 'Receipt file not found');
        }

        return Storage::disk('public')->download($receipt->receipt_path, $receipt->receipt_filename);
    }
}
