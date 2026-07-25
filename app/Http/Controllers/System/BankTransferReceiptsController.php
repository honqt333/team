<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
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

        // Filter by status. Default is "pending" so the admin sees the
        // review queue first. "all" returns every status. An empty /
        // missing status param behaves the same as the default.
        if ($request->status === 'all') {
            // no status filter — admin wants to see every receipt
        } elseif ($request->status) {
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

        // Augment the receipt with computed fields the Vue page expects
        // (the underlying schema doesn't store them as columns, so we
        // derive them from receipt_path at read-time):
        //   - receipt_url       : public URL to the file (already an
        //                         accessor on the model)
        //   - receipt_filename  : basename of the stored path; the
        //                         original client filename is lost after
        //                         Laravel renames the upload, but this
        //                         gives the user a sensible label.
        //   - receipt_mime_type : looked up from the file on disk (cached
        //                         on the request so we don't stat twice)
        //   - receipt_size_bytes: file size on disk
        $disk = Storage::disk('public');
        $receipt->receipt_filename = $receipt->receipt_path
            ? basename($receipt->receipt_path)
            : null;

        if ($receipt->receipt_path && $disk->exists($receipt->receipt_path)) {
            $receipt->receipt_size_bytes = $disk->size($receipt->receipt_path);
            $receipt->receipt_mime_type = $disk->mimeType($receipt->receipt_path) ?: 'application/octet-stream';
        } else {
            $receipt->receipt_size_bytes = null;
            $receipt->receipt_mime_type = null;
        }

        // The reviewer relation is User (not AdminUser) — but the file
        // is uploaded by tenant staff. If the reviewer is a User we
        // expose name; if it's an AdminUser we add it. Most approvals
        // will be by an admin via the admin guard, so look both up.
        $reviewerName = null;

        if ($receipt->reviewer) {
            $reviewerName = $receipt->reviewer->name ?? null;
        }

        if (! $reviewerName && $receipt->reviewed_by) {
            $admin = AdminUser::find($receipt->reviewed_by);
            $reviewerName = $admin?->name ?? $admin?->email;
        }

        return Inertia::render('System/BankTransferReceipts/Show', [
            'receipt' => array_merge($receipt->toArray(), [
                'receipt_url' => $receipt->receipt_url,
                'receipt_filename' => $receipt->receipt_filename,
                'receipt_mime_type' => $receipt->receipt_mime_type,
                'receipt_size_bytes' => $receipt->receipt_size_bytes,
                'reviewer_name' => $reviewerName,
            ]),
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

            // Mark payment as paid (one source of truth — don't both
            // ->update() and ->markAsPaid() the same record, the second
            // call would silently overwrite payment_method and gateway_ref
            // because markAsPaid doesn't know about them).
            if ($receipt->payment) {
                $receipt->payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => 'bank_transfer',
                    'gateway_ref' => $receipt->transfer_reference,
                    'failure_reason' => null,
                    'gateway_response' => [
                        'receipt_id' => $receipt->id,
                        'transfer_amount' => (float) $receipt->transfer_amount,
                        'transfer_date' => $receipt->transfer_date?->toDateString(),
                        'transfer_reference' => $receipt->transfer_reference,
                    ],
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
        if (! $receipt->receipt_path || ! Storage::disk('public')->exists($receipt->receipt_path)) {
            abort(404, 'Receipt file not found');
        }

        // The DB schema doesn't have a `receipt_filename` column — it was
        // dropped in the 2026_07_25_140000 migration. Fall back to the
        // basename of the stored path so the user gets a sensible filename
        // when downloading.
        $filename = basename($receipt->receipt_path);

        return Storage::disk('public')->download($receipt->receipt_path, $filename);
    }
}
