<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Events\Billing\ReceiptReviewed;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Subscription;
use App\Models\User;
use App\Services\System\SystemAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BankTransferReceiptsController extends Controller
{
    public function __construct(
        protected SystemAuditService $audit
    ) {}

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
        $disk = Storage::disk(Storage::disk('local')->exists($receipt->receipt_path ?? '') ? 'local' : 'public');
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

        // The reviewer is a system admin (AdminUser) — `reviewed_by`
        // stores an `admin_users.id`. The model's `reviewer()` relation
        // resolves against that table. We keep a `User` fallback for
        // legacy rows written before the relation was corrected, where
        // the id may point at the `users` table instead.
        $reviewerName = null;

        if ($receipt->reviewer) {
            $reviewerName = $receipt->reviewer->name ?? $receipt->reviewer->email;
        }

        if (! $reviewerName && $receipt->reviewed_by) {
            $legacyUser = User::find($receipt->reviewed_by);
            $reviewerName = $legacyUser?->name ?? $legacyUser?->email;
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
            return back()->with('error', __('messages.system.receipts.already_reviewed'));
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
            return back()->with('error', __('messages.system.receipts.amount_mismatch', [
                'actual' => number_format($actual, 2),
                'expected' => number_format((float) $expected, 2),
                'diff' => sprintf('%+.2f', $diff),
            ]));
        }

        // When the admin uses approved_amount, it must be > 0 and <= what
        // the tenant actually transferred (we never invent money). If
        // approved_amount equals the transfer amount, treat it as a
        // full approval with no credit/shortfall.
        $approvedAmount = isset($validated['approved_amount'])
            ? (float) $validated['approved_amount']
            : $actual;

        if ($approvedAmount <= 0 || $approvedAmount > $actual + 0.01) {
            return back()->with('error', __('messages.system.receipts.approved_amount_out_of_range', [
                'approved' => number_format($approvedAmount, 2),
                'actual' => number_format($actual, 2),
            ]));
        }

        // Initialise outside the transaction so the post-commit
        // ReceiptReviewed event can pass the audit notes too.
        $reviewNotes = $validated['notes'] ?? null;

        // The reviewer identity, resolved ONCE and explicitly.
        //
        // `$request->user()` honours the guard that the
        // `auth:web,admin` middleware activated for this request
        // (Laravel's Authenticate middleware calls `shouldUse($guard)`),
        // so it returns the AdminUser for an admin session and the
        // tenant User for a tenant-session system admin.
        //
        // The bare `auth()` helper resolves the *configured* default
        // guard ('web'), which is NOT the same thing: for a pure
        // admin-guard session it yields null, and for a tenant-session
        // system admin it yields a `users` id — while `reviewed_by`
        // is read back as an `admin_users.id`. Writing a `users` id
        // there made the panel display a different person as the
        // reviewer. We therefore record the id from whichever table
        // the acting identity actually lives in, and the model's
        // `reviewer()` relation resolves the same table.
        $actor = $request->user();
        $reviewerId = $actor?->id;

        DB::transaction(function () use ($receipt, $validated, $diff, $expected, $approvedAmount, $actual, &$reviewNotes, $reviewerId) {
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
                    $reviewerId ?? 0,
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
                    $reviewerId ?? 0,
                    now()->toIso8601String()
                );
                $reviewNotes = $reviewNotes
                    ? $reviewNotes."\n".$partialLine
                    : $partialLine;
            }

            // Mark receipt as approved
            $receipt->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
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
            ? __('messages.system.receipts.partial_approved', ['amount' => number_format($approvedAmount, 2)])
            : 'تم الموافقة على الإيصال وتفعيل الاشتراك';

        // Audit the decision. A payment approval is a financial event —
        // it activates a subscription and can activate a tenant — so
        // "who approved this and for how much" must be answerable.
        $this->audit->log('receipt_approve', BankTransferReceipt::class, $receipt->id, [
            'tenant_id' => $receipt->tenant_id,
            'transfer_amount' => $actual,
            'approved_amount' => $approvedAmount,
            'expected_amount' => $expected,
            'amount_diff' => $diff,
            'partial' => $approvedAmount < $actual - 0.01,
            'force_approved' => (bool) ($validated['force_approve'] ?? false),
        ], $request);

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
            return back()->with('error', __('messages.system.receipts.already_reviewed'));
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        // `$request->user()` honours the guard activated for this
        // request by the `auth:web,admin` middleware, so the recorded
        // id belongs to the table the reviewer relation reads back
        // (`admin_users`). See approve() for the full rationale.
        $reviewerId = $request->user()?->id;

        $receipt->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
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

        $this->audit->log('receipt_reject', BankTransferReceipt::class, $receipt->id, [
            'tenant_id' => $receipt->tenant_id,
            'transfer_amount' => (float) $receipt->transfer_amount,
            'notes' => $validated['notes'],
        ], $request);

        return back()->with('success', __('messages.system.receipts.rejected'));
    }

    /**
     * Download the receipt file.
     */
    public function download(BankTransferReceipt $receipt)
    {
        $disk = Storage::disk(Storage::disk('local')->exists($receipt->receipt_path ?? '') ? 'local' : 'public');

        if (! $receipt->receipt_path || ! $disk->exists($receipt->receipt_path)) {
            abort(404, 'Receipt file not found');
        }

        // The DB schema doesn't have a `receipt_filename` column — it was
        // dropped in the 2026_07_25_140000 migration. Fall back to the
        // basename of the stored path so the user gets a sensible filename
        // when downloading.
        $filename = basename($receipt->receipt_path);

        return $disk->response($receipt->receipt_path, $filename, ['Cache-Control' => 'private, no-store'], 'inline');
    }
}
