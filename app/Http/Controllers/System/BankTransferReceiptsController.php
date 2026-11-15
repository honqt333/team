<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Exceptions\BankTransferException;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Subscription;
use App\Models\User;
use App\Services\Billing\BankTransferService;
use App\Services\Billing\SubscriptionLifecycleService;
use App\Services\System\SystemAuditService;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BankTransferReceiptsController extends Controller
{
    public function __construct(
        protected SystemAuditService $audit,
        protected ?SubscriptionLifecycleService $lifecycle = null,
        protected ?BankTransferService $bankTransferService = null
    ) {
        $this->lifecycle ??= app(SubscriptionLifecycleService::class);
        $this->bankTransferService ??= app(BankTransferService::class);
    }

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
    public function show(Request $request, BankTransferReceipt $receipt): Response
    {
        $receipt->load(['tenant', 'subscription.plan', 'payment.invoice', 'reviewer']);

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
        $expected = $receipt->payment?->invoice
            ? (float) $this->bankTransferService->fromHalalas(
                $this->bankTransferService->getRemainingHalalas($receipt->payment->invoice)
            )
            : ($receipt->payment?->amount !== null ? (float) $receipt->payment->amount : null);
        $receipt->expected_amount = $expected;
        $receipt->amount_diff = $receipt->expected_amount !== null
            ? round((float) $receipt->transfer_amount - $receipt->expected_amount, 2)
            : null;
        $receipt->setAttribute(
            'invoice_paid_amount',
            $receipt->payment?->invoice
                ? (float) $this->bankTransferService->fromHalalas(
                    $this->bankTransferService->getPaidHalalas($receipt->payment->invoice)
                )
                : null
        );

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

        $actor = $request->user();
        $reviewer = $actor instanceof AdminUser
            ? $actor
            : ($actor ? AdminUser::where('user_id', $actor->id)->first() : null);

        return Inertia::render('System/BankTransferReceipts/Show', [
            'receipt' => array_merge($receipt->toArray(), [
                'receipt_url' => $receipt->receipt_url,
                'receipt_filename' => $receipt->receipt_filename,
                'receipt_mime_type' => $receipt->receipt_mime_type,
                'receipt_size_bytes' => $receipt->receipt_size_bytes,
                'reviewer_name' => $reviewerName,
            ]),
            'canOverride' => $reviewer?->hasPermission(Permissions::SYSTEM_RECEIPTS_OVERRIDE) ?? false,
        ]);
    }

    /**
     * Approve receipt — activates or extends the subscription when invoice balance is fully settled.
     */
    public function approve(Request $request, BankTransferReceipt $receipt)
    {
        if ($receipt->status !== BankTransferReceipt::STATUS_PENDING) {
            return back()->with('error', __('messages.system.receipts.already_reviewed'));
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'force_approve' => 'sometimes|boolean',
            'approved_amount' => 'nullable|numeric|min:0.01',
        ]);

        $actor = $request->user();
        $reviewer = $actor instanceof AdminUser
            ? $actor
            : ($actor ? AdminUser::where('user_id', $actor->id)->first() : null);

        try {
            $result = $this->bankTransferService->approveReceipt(
                $receipt,
                $reviewer,
                $validated['notes'] ?? null,
                (bool) ($validated['force_approve'] ?? false),
                $validated['approved_amount'] ?? null
            );
        } catch (BankTransferException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result['already_reviewed']) {
            return back()->with('error', __('messages.system.receipts.already_reviewed'));
        }

        $this->audit->log('receipt_approve', BankTransferReceipt::class, $receipt->id, $result['audit'], $request);

        $approvedAmount = (float) ($result['receipt']->approved_amount ?? $receipt->transfer_amount);
        $message = $result['partial']
            ? __('messages.system.receipts.partial_approved', ['amount' => number_format($approvedAmount, 2)])
            : __('messages.system.receipts.approved');

        return back()->with('success', $message);
    }

    /**
     * Reject receipt — marks pending payment failed while keeping subscription & invoice intact.
     */
    public function reject(Request $request, BankTransferReceipt $receipt)
    {
        if ($receipt->status !== BankTransferReceipt::STATUS_PENDING) {
            return back()->with('error', __('messages.system.receipts.already_reviewed'));
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $actor = $request->user();
        $reviewer = $actor instanceof AdminUser
            ? $actor
            : ($actor ? AdminUser::where('user_id', $actor->id)->first() : null);

        $result = $this->bankTransferService->rejectReceipt($receipt, $reviewer, $validated['notes']);

        if ($result['already_reviewed']) {
            return back()->with('error', __('messages.system.receipts.already_reviewed'));
        }

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
