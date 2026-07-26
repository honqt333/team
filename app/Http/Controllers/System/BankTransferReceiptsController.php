<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Events\Billing\ReceiptReviewed;
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

        // Augment every receipt with expected_amount + amount_diff so the
        // Index.vue can render the mismatch badge without a second request.
        // The receipt's payment relation is already eager-loaded above.
        $receipts->getCollection()->transform(function ($r) {
            $expected = $r->payment?->amount;
            $r->expected_amount = $expected !== null ? (float) $expected : null;
            $r->amount_diff = $r->expected_amount !== null
                ? round((float) $r->transfer_amount - $r->expected_amount, 2)
                : null;

            return $r;
        });

        $stats = [
            'pending' => BankTransferReceipt::where('status', 'pending')->count(),
            'approved' => BankTransferReceipt::where('status', 'approved')->count(),
            'rejected' => BankTransferReceipt::where('status', 'rejected')->count(),
            'total_amount_pending' => BankTransferReceipt::where('status', 'pending')->sum('transfer_amount'),
            'mismatched_pending' => BankTransferReceipt::where('status', 'pending')
                ->whereHas('payment')
                ->get()
                ->filter(fn ($r) => $r->payment
                    && abs((float) $r->transfer_amount - (float) $r->payment->amount) > 0.01)
                ->count(),
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

        // Amount comparison: how much was due vs. how much the tenant
        // actually transferred. Null when there's no payment linked
        // (e.g. legacy receipts that lost their payment FK) — the
        // Vue page renders those as "no comparison available".
        $expected = $receipt->payment?->amount;
        $receipt->expected_amount = $expected !== null ? (float) $expected : null;
        $receipt->amount_diff = $receipt->expected_amount !== null
            ? round((float) $receipt->transfer_amount - $receipt->expected_amount, 2)
            : null;

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
            'force_approve' => 'sometimes|boolean',
            // Optional override for partial approval. When set, the
            // payment is marked as paid for THIS amount (which may be
            // less than the invoice total). The remaining balance stays
            // on the invoice and the tenant's wallet/credit balance is
            // NOT auto-created here — the admin is responsible for
            // issuing a credit note or carrying the balance forward.
            // The receipt itself is still marked 'approved'.
            'approved_amount' => 'nullable|numeric|min:0.01',
        ]);

        // Amount mismatch check — protect against the admin rubber-stamping
        // a receipt that doesn't match the invoice. The admin can override
        // with force_approve, but the mismatch is then logged in review_notes
        // for the audit trail.
        $expected = $receipt->payment?->amount;
        $actual = (float) $receipt->transfer_amount;
        $diff = $expected !== null ? round($actual - (float) $expected, 2) : null;

        if ($diff !== null && abs($diff) > 0.01 && ! ($validated['force_approve'] ?? false)) {
            return back()->with('error', sprintf(
                'المبلغ المُحوّل (%.2f) يختلف عن المطلوب (%.2f). فرق: %+.2f. للتأكيد على الرغم من الاختلاف، أعد الإرسال مع force_approve=true.',
                $actual,
                (float) $expected,
                $diff
            ));
        }

        // When the admin uses approved_amount, it must be > 0 and <= what
        // the tenant actually transferred (we never invent money). If
        // approved_amount equals the transfer amount, treat it as a
        // full approval with no credit/shortfall.
        $approvedAmount = isset($validated['approved_amount'])
            ? (float) $validated['approved_amount']
            : $actual;

        if ($approvedAmount <= 0 || $approvedAmount > $actual + 0.01) {
            return back()->with('error', sprintf(
                'المبلغ المعتمد (%.2f) يجب أن يكون بين 0.01 و %.2f (المبلغ المُحوّل).',
                $approvedAmount,
                $actual
            ));
        }

        // Initialise outside the transaction so the post-commit
        // ReceiptReviewed event can pass the audit notes too.
        $reviewNotes = $validated['notes'] ?? null;

        DB::transaction(function () use ($receipt, $validated, $diff, $expected, $approvedAmount, $actual, &$reviewNotes) {
            $isPartial = abs($approvedAmount - $actual) > 0.01;
            $isShortVsExpected = $expected !== null && abs($approvedAmount - (float) $expected) > 0.01;

            // When force-approving with a mismatch, append an audit line so
            // we can investigate later who overrode what.
            if ($diff !== null && abs($diff) > 0.01 && ($validated['force_approve'] ?? false)) {
                $auditLine = sprintf(
                    '[FORCE-APPROVED] amount mismatch — expected=%.2f, actual=%.2f, diff=%+.2f, by admin #%d at %s',
                    (float) $expected,
                    (float) $receipt->transfer_amount,
                    $diff,
                    auth()->id(),
                    now()->toIso8601String()
                );
                $reviewNotes = $reviewNotes
                    ? $reviewNotes."\n".$auditLine
                    : $auditLine;
            }

            // When a partial approval was used, log the effective amount
            // so finance can reconcile later.
            if ($isPartial) {
                $partialLine = sprintf(
                    '[PARTIAL-APPROVAL] approved=%.2f, received=%.2f, shortfall=%.2f, by admin #%d at %s',
                    $approvedAmount,
                    $actual,
                    round($actual - $approvedAmount, 2),
                    auth()->id(),
                    now()->toIso8601String()
                );
                $reviewNotes = $reviewNotes
                    ? $reviewNotes."\n".$partialLine
                    : $partialLine;
            }

            // Mark receipt as approved
            $receipt->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);

            // Mark payment as paid (one source of truth — don't both
            // ->update() and ->markAsPaid() the same record, the second
            // call would silently overwrite payment_method and gateway_ref
            // because markAsPaid doesn't know about them).
            if ($receipt->payment) {
                $paymentUpdates = [
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_method' => 'bank_transfer',
                    'gateway_ref' => $receipt->transfer_reference,
                    'failure_reason' => null,
                    'gateway_response' => [
                        'receipt_id' => $receipt->id,
                        'transfer_amount' => (float) $receipt->transfer_amount,
                        'approved_amount' => $approvedAmount,
                        'transfer_date' => $receipt->transfer_date?->toDateString(),
                        'transfer_reference' => $receipt->transfer_reference,
                        'expected_amount' => $expected !== null ? (float) $expected : null,
                        'amount_diff' => $diff,
                        'force_approved' => (bool) ($validated['force_approve'] ?? false),
                        'partial_approval' => $isPartial,
                    ],
                ];

                // For a partial approval, the payment's recorded amount
                // is the approved figure (not the full transfer). This
                // makes reports line up with what was actually settled.
                if ($isPartial) {
                    $paymentUpdates['amount'] = $approvedAmount;
                }
                $receipt->payment->update($paymentUpdates);
            }

            // Activate subscription
            if ($receipt->subscription) {
                $receipt->subscription->update(['status' => 'active']);
            }

            // Mark invoice as paid ONLY if the approved amount covers
            // the full invoice total. For partial approvals, the invoice
            // stays open with the remaining balance so the tenant gets
            // a clear "what's left to pay" view.
            if ($receipt->payment?->invoice) {
                if (! $isShortVsExpected) {
                    $receipt->payment->invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
                // For partial, the invoice stays pending — admin must
                // decide whether to issue a credit or chase the rest.
                // We surface a session message so the UI can show the
                // remaining balance on the next page.
            }

            // Activate tenant
            if ($receipt->tenant) {
                $receipt->tenant->update(['status' => 'active']);
            }
        });

        $message = $approvedAmount < $actual - 0.01
            ? sprintf('تمت الموافقة الجزئية بمبلغ %.2f. الفاتورة تبقى مفتوحة للمبلغ المتبقي.', $approvedAmount)
            : 'تم الموافقة على الإيصال وتفعيل الاشتراك';

        // Notify the tenant of the decision AFTER the transaction
        // commits so the notification carries the canonical review
        // state. The listener (NotifyTenantOfReceiptDecision) creates
        // one row per user attached to the tenant.
        event(new ReceiptReviewed(
            $receipt->fresh(['tenant', 'payment']),
            'approved',
            $reviewNotes,
        ));

        return back()->with('success', $message);
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

        // Notify the tenant with the rejection reason so they know
        // what to fix before resubmitting.
        event(new ReceiptReviewed(
            $receipt->fresh(['tenant']),
            'rejected',
            $validated['notes'],
        ));

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
