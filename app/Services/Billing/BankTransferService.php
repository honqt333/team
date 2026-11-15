<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Events\Billing\ReceiptReviewed;
use App\Events\Billing\ReceiptSubmitted;
use App\Exceptions\BankTransferException;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Support\Permissions;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

/**
 * Centralized domain service for bank transfer validation, exact halala accounting,
 * idempotent receipt submission, and subscription invoice operation-type resolution.
 */
class BankTransferService
{
    public function __construct(
        protected SubscriptionLifecycleService $lifecycle,
        protected PromoCodeValidator $promoValidator
    ) {}

    /**
     * Convert a decimal money value into exact integer halalas without float arithmetic.
     */
    public function toHalalas(int|float|string $amount): int
    {
        $normalized = trim((string) $amount);

        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new RuntimeException('Invalid monetary amount.');
        }

        // Round half-up to 2 decimal places using bcmath string arithmetic before scaling.
        $sign = str_starts_with($normalized, '-') ? '-' : '';
        $unsigned = ltrim($normalized, '+-');
        $scaled = bcmul($unsigned, '100', 4);
        $parts = explode('.', $scaled, 2);
        $intPart = $parts[0];
        $fracPart = $parts[1] ?? '0000';

        if ((int) ($fracPart[0] ?? '0') >= 5) {
            $intPart = bcadd($intPart, '1', 0);
        }

        $result = (int) $intPart;

        return $sign === '-' ? -$result : $result;
    }

    /**
     * Format integer halalas back into a 2-decimal string.
     */
    public function fromHalalas(int $halalas): string
    {
        return bcdiv((string) $halalas, '100', 2);
    }

    /**
     * Read normalized bank transfer policy settings from PaymentSettings.
     *
     * @return array{min_amount_halalas: int, max_days: int, require_receipt: bool}
     */
    public function getPolicyConfig(?PaymentSettings $settings = null): array
    {
        $settings ??= PaymentSettings::first();

        $minAmountHalalas = $settings && $settings->bank_transfer_min_amount !== null
            ? max(0, $this->toHalalas($settings->bank_transfer_min_amount))
            : 0;

        $maxDays = $settings && $settings->bank_transfer_max_days !== null
            ? max(1, (int) $settings->bank_transfer_max_days)
            : 7;

        $requireReceipt = $settings
            ? (bool) ($settings->bank_transfer_require_receipt ?? true)
            : true;

        return [
            'min_amount_halalas' => $minAmountHalalas,
            'max_days' => $maxDays,
            'require_receipt' => $requireReceipt,
        ];
    }

    /**
     * Build unified Laravel validation rules for bank transfer receipt submissions.
     *
     * @return array<string, mixed>
     */
    public function buildValidationRules(
        int $tenantId,
        bool $requirePlan,
        bool $requireReceipt,
        ?string $idempotencyKey = null
    ): array {
        $normalizedKey = $idempotencyKey !== null && trim($idempotencyKey) !== ''
            ? mb_substr(trim($idempotencyKey), 0, 100)
            : null;

        return [
            'plan_id' => $requirePlan ? ['required', 'exists:plans,id'] : ['nullable', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'in:yearly'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'transfer_amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bank_transfer_receipts', 'transfer_reference')
                    ->where(function ($query) use ($tenantId, $normalizedKey): void {
                        $query->where('tenant_id', $tenantId)
                            ->where('status', BankTransferReceipt::STATUS_PENDING);

                        if ($normalizedKey !== null) {
                            $query->where(function ($q) use ($normalizedKey): void {
                                $q->whereNull('idempotency_key')
                                    ->orWhere('idempotency_key', '!=', $normalizedKey);
                            });
                        }
                    }),
            ],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'receipt' => [
                $requireReceipt ? 'required' : 'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'subscription_invoice_id' => [
                'nullable',
                Rule::exists('subscription_invoices', 'id')
                    ->where('tenant_id', $tenantId),
            ],
        ];
    }

    /**
     * Deterministic SHA-256 hash of canonical request payload for Idempotency-Key verification.
     *
     * @param array<string, mixed> $validated
     */
    public function computeRequestHash(array $validated, ?int $explicitSubscriptionId = null): string
    {
        $canonical = [
            'plan_id' => isset($validated['plan_id']) ? (int) $validated['plan_id'] : null,
            'billing_cycle' => $validated['billing_cycle'] ?? 'yearly',
            'promo_code' => $validated['promo_code'] ?? null,
            'subscription_invoice_id' => isset($validated['subscription_invoice_id'])
                ? (int) $validated['subscription_invoice_id']
                : null,
            'explicit_subscription_id' => $explicitSubscriptionId,
            'transfer_amount_halalas' => $this->toHalalas($validated['transfer_amount']),
            'transfer_reference' => trim((string) $validated['transfer_reference']),
            'transfer_date' => (string) $validated['transfer_date'],
            'bank_name' => trim((string) $validated['bank_name']),
            'account_name' => trim((string) $validated['account_name']),
            'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
        ];

        return hash('sha256', (string) json_encode($canonical, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Calculate exact paid halalas for an invoice from settled, non-refunded payments.
     */
    public function getPaidHalalas(SubscriptionInvoice $invoice): int
    {
        $amounts = SubscriptionPayment::query()
            ->where('subscription_invoice_id', $invoice->id)
            ->where('status', 'paid')
            ->whereNull('refunded_at')
            ->pluck('amount');

        $totalHalalas = 0;

        foreach ($amounts as $amount) {
            $totalHalalas += $this->toHalalas($amount);
        }

        return $totalHalalas;
    }

    /**
     * Calculate remaining due halalas on an invoice (never negative).
     */
    public function getRemainingHalalas(SubscriptionInvoice $invoice): int
    {
        $invoiceTotalHalalas = $this->toHalalas($invoice->total);
        $paidHalalas = $this->getPaidHalalas($invoice);

        return max(0, $invoiceTotalHalalas - $paidHalalas);
    }

    /**
     * Resolve the operation type (new, renewal, upgrade) from a reliable source.
     * Halts with RuntimeException if legacy data is ambiguous rather than guessing from status alone.
     */
    public function resolveOperationType(SubscriptionInvoice $invoice): string
    {
        $explicit = $invoice->operation_type;

        if (in_array($explicit, [
            SubscriptionInvoice::OPERATION_NEW,
            SubscriptionInvoice::OPERATION_RENEWAL,
            SubscriptionInvoice::OPERATION_UPGRADE,
        ], true)) {
            return $explicit;
        }

        $subscription = $invoice->subscription()->first();

        if (! $subscription) {
            throw new RuntimeException("Cannot resolve operation type for invoice #{$invoice->id}: missing subscription.");
        }

        // Check reliable historical evidence: prior paid invoices on the same subscription indicate renewal.
        $hasPriorPaidInvoice = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('id', '!=', $invoice->id)
            ->where('status', 'paid')
            ->exists();

        if ($hasPriorPaidInvoice) {
            return SubscriptionInvoice::OPERATION_RENEWAL;
        }

        // Unactivated subscription with no prior paid invoices is a new/upgrade initial invoice.
        if (in_array($subscription->status, ['pending', 'pending_payment', 'failed'], true)) {
            $hasActiveOther = DB::table('subscriptions')
                ->where('tenant_id', $subscription->tenant_id)
                ->where('id', '!=', $subscription->id)
                ->where('status', 'active')
                ->exists();

            return $hasActiveOther
                ? SubscriptionInvoice::OPERATION_UPGRADE
                : SubscriptionInvoice::OPERATION_NEW;
        }

        throw new RuntimeException(
            "Ambiguous operation type for invoice #{$invoice->id} (subscription #{$subscription->id}, status={$subscription->status})."
        );
    }

    /**
     * Submit a bank transfer receipt either for an existing open invoice, an explicit subscription,
     * or a brand-new subscription/upgrade — with tenant locking, policy enforcement, and Idempotency-Key support.
     *
     * @param array<string, mixed> $validated
     * @return array{
     *     idempotent_replay: bool,
     *     subscription: Subscription|null,
     *     invoice: SubscriptionInvoice|null,
     *     payment: SubscriptionPayment|null,
     *     receipt: BankTransferReceipt
     * }
     */
    public function submitReceipt(
        Tenant $tenant,
        array $validated,
        ?UploadedFile $receiptFile,
        ?string $idempotencyKey = null,
        ?Subscription $explicitSubscription = null
    ): array {
        $paymentSettings = PaymentSettings::first();
        $policy = $this->getPolicyConfig($paymentSettings);

        if ($paymentSettings && ! $paymentSettings->bank_transfer_enabled) {
            throw new BankTransferException(
                __('company_profile.subscription.bank_transfer_disabled', [
                    'default' => 'خدمة التحويل البنكي غير متاحة حالياً.',
                ]),
                422
            );
        }

        $transferHalalas = $this->toHalalas($validated['transfer_amount']);

        if ($transferHalalas <= 0) {
            throw new BankTransferException(__('validation.min.numeric', [
                'attribute' => 'transfer_amount',
                'min' => 0.01,
            ]), 422);
        }

        if ($policy['min_amount_halalas'] > 0 && $transferHalalas < $policy['min_amount_halalas']) {
            throw new BankTransferException(
                __('company_profile.subscription.bank_transfer_min_amount_error', [
                    'min' => $this->fromHalalas($policy['min_amount_halalas']),
                    'default' => 'الحد الأدنى لمبلغ التحويل البنكي هو :min.',
                ]),
                422
            );
        }

        if ($policy['require_receipt'] && ! $receiptFile) {
            throw new BankTransferException(
                __('company_profile.subscription.receipt_required', [
                    'default' => 'إرفاق صورة أو ملف إيصال التحويل البنكي مطلوب.',
                ]),
                422
            );
        }

        $normalizedKey = $idempotencyKey !== null && trim($idempotencyKey) !== ''
            ? mb_substr(trim($idempotencyKey), 0, 100)
            : null;
        $requestHash = $this->computeRequestHash($validated, $explicitSubscription?->id);

        if ($normalizedKey !== null) {
            $existing = $this->findIdempotentReceipt($tenant->id, $normalizedKey, $requestHash);

            if ($existing !== null) {
                return $existing;
            }
        }

        $storedPath = null;

        try {
            $result = DB::transaction(function () use (
                $tenant,
                $validated,
                $receiptFile,
                $normalizedKey,
                $requestHash,
                $explicitSubscription,
                $policy,
                &$storedPath
            ): array {
                // Canonical lock order: Tenant -> Subscription -> SubscriptionInvoice -> BankTransferReceipt -> SubscriptionPayment
                $lockedTenant = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();

                if ($normalizedKey !== null) {
                    $existing = $this->findIdempotentReceipt($lockedTenant->id, $normalizedKey, $requestHash);

                    if ($existing !== null) {
                        return $existing;
                    }
                }

                // Path A: Pay an existing open invoice explicitly selected by the tenant
                if (! empty($validated['subscription_invoice_id'])) {
                    return $this->attachReceiptToExistingInvoice(
                        $lockedTenant,
                        (int) $validated['subscription_invoice_id'],
                        $validated,
                        $receiptFile,
                        $normalizedKey,
                        $requestHash,
                        $storedPath
                    );
                }

                // Path B: Legacy route attaching receipt to an explicit existing subscription
                if ($explicitSubscription !== null) {
                    return $this->attachReceiptToExplicitSubscription(
                        $lockedTenant,
                        $explicitSubscription,
                        $validated,
                        $receiptFile,
                        $normalizedKey,
                        $requestHash,
                        $policy,
                        $storedPath
                    );
                }

                // Path C: New subscription / upgrade (or re-submitting after a rejected receipt on the pending subscription)
                return $this->createOrReusePendingSubscriptionReceipt(
                    $lockedTenant,
                    $validated,
                    $receiptFile,
                    $normalizedKey,
                    $requestHash,
                    $policy,
                    $storedPath
                );
            });
        } catch (Throwable $e) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $e;
        }

        if (! $result['idempotent_replay']) {
            event(new ReceiptSubmitted($result['receipt']));
        }

        return $result;
    }

    /**
     * Check for an existing receipt matching (tenant_id, idempotency_key) and verify request payload parity.
     *
     * @return array{
     *     idempotent_replay: bool,
     *     subscription: Subscription|null,
     *     invoice: SubscriptionInvoice|null,
     *     payment: SubscriptionPayment|null,
     *     receipt: BankTransferReceipt
     * }|null
     */
    protected function findIdempotentReceipt(int $tenantId, string $idempotencyKey, string $requestHash): ?array
    {
        $existing = BankTransferReceipt::query()
            ->where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if (! $existing) {
            return null;
        }

        if ($existing->request_hash !== null && ! hash_equals($existing->request_hash, $requestHash)) {
            throw new BankTransferException(
                __('company_profile.subscription.idempotency_conflict', [
                    'default' => 'مفتاح عدم التكرار مستخدم مسبقاً لطلب ببيانات مختلفة.',
                ]),
                409
            );
        }

        /** @var SubscriptionPayment|null $payment */
        $payment = $existing->payment;
        /** @var Subscription|null $subscription */
        $subscription = $existing->subscription?->load('plan');
        /** @var SubscriptionInvoice|null $invoice */
        $invoice = $payment?->invoice;

        return [
            'idempotent_replay' => true,
            'subscription' => $subscription,
            'invoice' => $invoice,
            'payment' => $payment,
            'receipt' => $existing,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function attachReceiptToExistingInvoice(
        Tenant $tenant,
        int $invoiceId,
        array $validated,
        ?UploadedFile $receiptFile,
        ?string $idempotencyKey,
        string $requestHash,
        ?string &$storedPath
    ): array {
        // Canonical lock order: Subscription -> SubscriptionInvoice
        $preResolvedSubId = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($invoiceId)
            ->value('subscription_id');

        $subscription = $preResolvedSubId !== null
            ? Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($preResolvedSubId)
                ->lockForUpdate()
                ->first()
            : null;

        $invoice = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->lockForUpdate()
            ->find($invoiceId);

        if (! $invoice) {
            throw new BankTransferException(
                __('company_profile.subscription.invoice_not_found', [
                    'default' => 'الفاتورة المحددة غير موجودة أو لا تتبع هذا الحساب.',
                ]),
                404
            );
        }

        if ($invoice->subscription_id !== null && (int) $invoice->subscription_id !== ($subscription ? (int) $subscription->id : 0)) {
            $subscription = Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($invoice->subscription_id)
                ->lockForUpdate()
                ->first();
        }

        if (
            $invoice->status === 'paid'
            || ! in_array($invoice->status, ['pending', 'pending_payment', 'overdue'], true)
        ) {
            throw new BankTransferException(
                __('company_profile.subscription.invoice_not_payable', [
                    'default' => 'لا يمكن رفع إيصال لهذه الفاتورة لأنها مدفوعة أو غير مفتوحة للسداد.',
                ]),
                422
            );
        }

        $remainingHalalas = $this->getRemainingHalalas($invoice);

        if ($remainingHalalas <= 0) {
            throw new BankTransferException(
                __('company_profile.subscription.invoice_already_settled', [
                    'default' => 'تم سداد كامل رصيد هذه الفاتورة بالفعل.',
                ]),
                422
            );
        }

        $this->guardNoPendingReceiptOnInvoice($invoice);

        $storedPath = $this->storeReceiptFile($tenant->id, $receiptFile);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $invoice->subscription_id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => $this->fromHalalas($remainingHalalas),
            'currency' => PaymentSettings::getPlatformCurrency(),
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $invoice->subscription_id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => $this->fromHalalas($this->toHalalas($validated['transfer_amount'])),
            'transfer_reference' => $validated['transfer_reference'],
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'transfer_date' => $validated['transfer_date'],
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'receipt_path' => $storedPath,
            'status' => BankTransferReceipt::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);

        return [
            'idempotent_replay' => false,
            'subscription' => $subscription?->fresh()->load('plan'),
            'invoice' => $invoice->fresh(),
            'payment' => $payment,
            'receipt' => $receipt,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @param array{min_amount_halalas: int, max_days: int, require_receipt: bool} $policy
     */
    protected function attachReceiptToExplicitSubscription(
        Tenant $tenant,
        Subscription $subscription,
        array $validated,
        ?UploadedFile $receiptFile,
        ?string $idempotencyKey,
        string $requestHash,
        array $policy,
        ?string &$storedPath
    ): array {
        $lockedSub = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->lockForUpdate()
            ->findOrFail($subscription->id);

        $invoice = SubscriptionInvoice::query()
            ->where('subscription_id', $lockedSub->id)
            ->whereIn('status', ['pending', 'pending_payment', 'overdue'])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (! $invoice) {
            $hasPaidInvoice = SubscriptionInvoice::query()
                ->where('subscription_id', $lockedSub->id)
                ->where('status', 'paid')
                ->exists();

            $operationType = $hasPaidInvoice
                ? SubscriptionInvoice::OPERATION_RENEWAL
                : $this->detectInitialOperationType($tenant->id, $lockedSub->id);

            $invoice = SubscriptionInvoice::create([
                'subscription_id' => $lockedSub->id,
                'tenant_id' => $tenant->id,
                'invoice_number' => SubscriptionInvoice::generateNumber(),
                'operation_type' => $operationType,
                'subtotal' => $lockedSub->price ?? 0,
                'vat' => 0,
                'discount' => $lockedSub->discount_amount ?? 0,
                'total' => max(0, ($lockedSub->price ?? 0) - ($lockedSub->discount_amount ?? 0)),
                'status' => 'pending',
                'issued_at' => now(),
                'due_at' => now()->addDays($policy['max_days']),
            ]);
        }

        $this->guardNoPendingReceiptOnInvoice($invoice);

        $storedPath = $this->storeReceiptFile($tenant->id, $receiptFile);
        $remainingHalalas = $this->getRemainingHalalas($invoice);
        $paymentAmount = $remainingHalalas > 0
            ? $this->fromHalalas($remainingHalalas)
            : $this->fromHalalas($this->toHalalas($validated['transfer_amount']));

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $lockedSub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => $paymentAmount,
            'currency' => PaymentSettings::getPlatformCurrency(),
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $lockedSub->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => $this->fromHalalas($this->toHalalas($validated['transfer_amount'])),
            'transfer_reference' => $validated['transfer_reference'],
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'transfer_date' => $validated['transfer_date'],
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'receipt_path' => $storedPath,
            'status' => BankTransferReceipt::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (! in_array($lockedSub->status, ['active', 'pending_payment'], true)) {
            $lockedSub->update(['status' => 'pending_payment']);
        }

        return [
            'idempotent_replay' => false,
            'subscription' => $lockedSub->fresh()->load('plan'),
            'invoice' => $invoice->fresh(),
            'payment' => $payment,
            'receipt' => $receipt,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @param array{min_amount_halalas: int, max_days: int, require_receipt: bool} $policy
     */
    protected function createOrReusePendingSubscriptionReceipt(
        Tenant $tenant,
        array $validated,
        ?UploadedFile $receiptFile,
        ?string $idempotencyKey,
        string $requestHash,
        array $policy,
        ?string &$storedPath
    ): array {
        $existingPending = $tenant->subscriptions()
            ->whereIn('status', ['pending', 'pending_payment'])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($existingPending) {
            $hasPendingReceipt = BankTransferReceipt::query()
                ->where('subscription_id', $existingPending->id)
                ->where('status', BankTransferReceipt::STATUS_PENDING)
                ->exists();

            if ($hasPendingReceipt || $existingPending->status === 'pending') {
                $plan = $existingPending->plan;
                $planName = $plan ? ($plan->name_ar ?: $plan->name_en) : '';
                $message = $hasPendingReceipt
                    ? __('company_profile.subscription.receipt_under_review', [
                        'plan' => $planName,
                        'default' => 'إيصال تحويل بنكي لـ ":plan" قيد المراجعة من الإدارة. انتظر الموافقة أو ألغِ الطلب الحالي.',
                    ])
                    : __('company_profile.subscription.payment_in_progress', [
                        'plan' => $planName,
                        'default' => 'لديك طلب دفع جارٍ لـ ":plan" — أكمل الدفع أو ألغِه قبل رفع إيصال جديد.',
                    ]);

                throw new BankTransferException($message, 409, [
                    'pending_status' => $existingPending->status,
                    'pending_subscription_id' => $existingPending->id,
                ]);
            }

            // Re-submit on the same pending_payment subscription & its open invoice after rejection (no duplicate records).
            $openInvoice = SubscriptionInvoice::query()
                ->where('subscription_id', $existingPending->id)
                ->whereIn('status', ['pending', 'pending_payment', 'overdue'])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($openInvoice) {
                $storedPath = $this->storeReceiptFile($tenant->id, $receiptFile);
                $remainingHalalas = $this->getRemainingHalalas($openInvoice);
                $paymentAmount = $remainingHalalas > 0
                    ? $this->fromHalalas($remainingHalalas)
                    : $this->fromHalalas($this->toHalalas($openInvoice->total));

                $payment = SubscriptionPayment::create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $existingPending->id,
                    'subscription_invoice_id' => $openInvoice->id,
                    'amount' => $paymentAmount,
                    'currency' => PaymentSettings::getPlatformCurrency(),
                    'gateway' => 'bank_transfer',
                    'status' => 'pending',
                ]);

                $receipt = BankTransferReceipt::create([
                    'tenant_id' => $tenant->id,
                    'subscription_id' => $existingPending->id,
                    'subscription_payment_id' => $payment->id,
                    'transfer_amount' => $this->fromHalalas($this->toHalalas($validated['transfer_amount'])),
                    'transfer_reference' => $validated['transfer_reference'],
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $requestHash,
                    'transfer_date' => $validated['transfer_date'],
                    'bank_name' => $validated['bank_name'],
                    'account_name' => $validated['account_name'],
                    'receipt_path' => $storedPath,
                    'status' => BankTransferReceipt::STATUS_PENDING,
                    'notes' => $validated['notes'] ?? null,
                ]);

                return [
                    'idempotent_replay' => false,
                    'subscription' => $existingPending->fresh()->load('plan'),
                    'invoice' => $openInvoice->fresh(),
                    'payment' => $payment,
                    'receipt' => $receipt,
                ];
            }
        }

        $plan = Plan::findOrFail($validated['plan_id']);
        $baseHalalas = $this->toHalalas($plan->price_yearly);
        $discountHalalas = 0;
        $promo = null;

        if (! empty($validated['promo_code'])) {
            $check = $this->promoValidator->check((string) $validated['promo_code'], $tenant->id);
            $promo = $check->promo;
            $discountHalalas = $this->toHalalas(
                $this->promoValidator->discountFor($check, (float) $this->fromHalalas($baseHalalas))
            );
        }

        $subtotalHalalas = max(0, $baseHalalas - $discountHalalas);
        $paymentSettings = PaymentSettings::first();
        $vatCalc = $paymentSettings?->calculateVat((float) $this->fromHalalas($subtotalHalalas)) ?? [
            'exclusive' => (float) $this->fromHalalas($subtotalHalalas),
            'vat' => 0,
            'total' => (float) $this->fromHalalas($subtotalHalalas),
        ];
        $totalHalalas = $this->toHalalas($vatCalc['total']);
        $vatHalalas = max(0, $totalHalalas - $subtotalHalalas);

        if ($policy['min_amount_halalas'] > 0 && $totalHalalas < $policy['min_amount_halalas']) {
            throw new BankTransferException(
                __('company_profile.subscription.bank_transfer_min_amount_error', [
                    'min' => $this->fromHalalas($policy['min_amount_halalas']),
                    'default' => 'الحد الأدنى لمبلغ التحويل البنكي هو :min.',
                ]),
                422
            );
        }

        $dates = $this->lifecycle->calculateRenewalDates($tenant, 'yearly');
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'starts_at' => $dates['starts_at'],
            'ends_at' => $dates['ends_at'],
            'price' => $this->fromHalalas($baseHalalas),
            'discount_amount' => $this->fromHalalas($discountHalalas),
            'promo_code' => $validated['promo_code'] ?? null,
            'auto_renew' => true,
        ]);

        if ($promo) {
            $this->promoValidator->recordUsage(
                $promo,
                $tenant->id,
                $subscription->id,
                (float) $this->fromHalalas($discountHalalas)
            );
        }

        $operationType = $this->detectInitialOperationType($tenant->id, $subscription->id);
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'SUB-'.str_pad((string) $subscription->id, 6, '0', STR_PAD_LEFT).'-'.now()->format('Ymd'),
            'operation_type' => $operationType,
            'subtotal' => $this->fromHalalas($subtotalHalalas),
            'vat' => $this->fromHalalas($vatHalalas),
            'discount' => $this->fromHalalas($discountHalalas),
            'total' => $this->fromHalalas($totalHalalas),
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays($policy['max_days']),
        ]);

        $storedPath = $this->storeReceiptFile($tenant->id, $receiptFile);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => $this->fromHalalas($totalHalalas),
            'currency' => PaymentSettings::getPlatformCurrency(),
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => $this->fromHalalas($this->toHalalas($validated['transfer_amount'])),
            'transfer_reference' => $validated['transfer_reference'],
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'transfer_date' => $validated['transfer_date'],
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'receipt_path' => $storedPath,
            'status' => BankTransferReceipt::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);

        return [
            'idempotent_replay' => false,
            'subscription' => $subscription->fresh()->load('plan'),
            'invoice' => $invoice,
            'payment' => $payment,
            'receipt' => $receipt,
        ];
    }

    protected function detectInitialOperationType(int $tenantId, int $subscriptionId): string
    {
        $hasActiveOther = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $subscriptionId)
            ->where('status', 'active')
            ->exists();

        return $hasActiveOther
            ? SubscriptionInvoice::OPERATION_UPGRADE
            : SubscriptionInvoice::OPERATION_NEW;
    }

    protected function guardNoPendingReceiptOnInvoice(SubscriptionInvoice $invoice): void
    {
        $paymentIds = SubscriptionPayment::query()
            ->where('subscription_invoice_id', $invoice->id)
            ->pluck('id');

        if ($paymentIds->isEmpty()) {
            return;
        }

        $hasPending = BankTransferReceipt::query()
            ->whereIn('subscription_payment_id', $paymentIds)
            ->where('status', BankTransferReceipt::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            throw new BankTransferException(
                __('company_profile.subscription.invoice_receipt_pending', [
                    'default' => 'يوجد إيصال تحويل بنكي قيد المراجعة لهذه الفاتورة بالفعل.',
                ]),
                409
            );
        }
    }

    protected function storeReceiptFile(int $tenantId, ?UploadedFile $receiptFile): ?string
    {
        if (! $receiptFile) {
            return null;
        }

        $path = $receiptFile->store("bank-transfer-receipts/{$tenantId}", 'local');
        throw_unless($path, RuntimeException::class, 'Receipt storage failed.');

        return $path;
    }

    /**
     * Approve a pending bank transfer receipt inside a canonical lock order,
     * preserving both transfer_amount and approved_amount, enforcing exact halala
     * limits, and executing calendar activation/extension only upon full settlement.
     *
     * @return array{
     *     already_reviewed: bool,
     *     receipt: BankTransferReceipt,
     *     partial: bool,
     *     completed_invoice: bool,
     *     operation_type: string|null,
     *     audit: array<string, mixed>
     * }
     */
    public function approveReceipt(
        BankTransferReceipt $receipt,
        ?AdminUser $reviewer,
        ?string $notes = null,
        bool $forceApprove = false,
        int|float|string|null $approvedAmountInput = null
    ): array {
        $receipt->loadMissing(['payment.invoice']);
        $paymentModel = $receipt->payment;
        $invoiceSubId = ($paymentModel !== null && $paymentModel->invoice !== null)
            ? $paymentModel->invoice->subscription_id
            : null;
        $subscriptionId = $receipt->subscription_id
            ?? ($paymentModel !== null ? $paymentModel->subscription_id : null)
            ?? $invoiceSubId;
        $invoiceId = $paymentModel?->subscription_invoice_id;
        $paymentId = $receipt->subscription_payment_id;
        $reviewerId = $reviewer?->id;

        $result = DB::transaction(function () use (
            $receipt,
            $subscriptionId,
            $invoiceId,
            $paymentId,
            $reviewer,
            $reviewerId,
            $notes,
            $forceApprove,
            $approvedAmountInput
        ): array {
            // Canonical lock order: Subscription -> SubscriptionInvoice -> BankTransferReceipt -> SubscriptionPayment
            $lockedSub = $subscriptionId !== null
                ? Subscription::query()->whereKey($subscriptionId)->lockForUpdate()->first()
                : null;

            $lockedInvoice = $invoiceId !== null
                ? SubscriptionInvoice::query()->whereKey($invoiceId)->lockForUpdate()->first()
                : null;

            $lockedReceipt = BankTransferReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            $lockedPayment = $paymentId !== null
                ? SubscriptionPayment::query()->whereKey($paymentId)->lockForUpdate()->first()
                : null;

            if ($lockedReceipt->status !== BankTransferReceipt::STATUS_PENDING) {
                return [
                    'already_reviewed' => true,
                    'receipt' => $lockedReceipt,
                    'partial' => false,
                    'completed_invoice' => false,
                    'operation_type' => null,
                    'audit' => [],
                ];
            }

            if ($lockedInvoice === null && $lockedPayment?->subscription_invoice_id !== null) {
                $lockedInvoice = SubscriptionInvoice::query()
                    ->whereKey($lockedPayment->subscription_invoice_id)
                    ->lockForUpdate()
                    ->first();
            }

            if ($lockedSub === null && $lockedInvoice?->subscription_id !== null) {
                $lockedSub = Subscription::query()
                    ->whereKey($lockedInvoice->subscription_id)
                    ->lockForUpdate()
                    ->first();
            }

            $transferredHalalas = $this->toHalalas($lockedReceipt->transfer_amount);
            $remainingHalalas = $lockedInvoice
                ? $this->getRemainingHalalas($lockedInvoice)
                : ($lockedPayment ? $this->toHalalas($lockedPayment->amount) : null);

            if ($lockedInvoice && $remainingHalalas <= 0) {
                throw new BankTransferException(
                    __('messages.system.receipts.invoice_already_settled', [
                        'default' => 'تم سداد كامل قيمة هذه الفاتورة بالفعل ولا يمكن اعتماد إيصال إضافي.',
                    ]),
                    422
                );
            }

            $expectedHalalas = $remainingHalalas;
            $diffHalalas = $expectedHalalas !== null ? ($transferredHalalas - $expectedHalalas) : null;
            $hasMismatch = $diffHalalas !== null && abs($diffHalalas) > 1;

            if ($hasMismatch && ! $forceApprove) {
                throw new BankTransferException(
                    __('messages.system.receipts.amount_mismatch', [
                        'actual' => $this->fromHalalas($transferredHalalas),
                        'expected' => $this->fromHalalas($expectedHalalas),
                        'diff' => ($diffHalalas >= 0 ? '+' : '').$this->fromHalalas($diffHalalas),
                    ]),
                    422
                );
            }

            $trimmedNotes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

            if ($hasMismatch && $forceApprove) {
                if (! $reviewer || ! $reviewer->hasPermission(Permissions::SYSTEM_RECEIPTS_OVERRIDE)) {
                    throw new BankTransferException(
                        __('messages.system.receipts.override_permission_required', [
                            'default' => 'ليست لديك صلاحية تجاوز اختلاف مبلغ التحويل البنكي.',
                        ]),
                        403
                    );
                }

                if ($trimmedNotes === null) {
                    throw new BankTransferException(
                        __('messages.system.receipts.override_reason_required', [
                            'default' => 'يجب إدخال سبب التجاوز عند اعتماد إيصال بمبلغ مختلف.',
                        ]),
                        422
                    );
                }
            }

            $approvedHalalas = $approvedAmountInput !== null
                ? $this->toHalalas($approvedAmountInput)
                : $transferredHalalas;

            if ($approvedHalalas <= 0 || $approvedHalalas > $transferredHalalas) {
                throw new BankTransferException(
                    __('messages.system.receipts.approved_amount_out_of_range', [
                        'approved' => $this->fromHalalas($approvedHalalas),
                        'actual' => $this->fromHalalas($transferredHalalas),
                    ]),
                    422
                );
            }

            // Even with force_approve, approved_amount can NEVER exceed remaining invoice balance.
            if ($remainingHalalas !== null && $approvedHalalas > $remainingHalalas) {
                throw new BankTransferException(
                    __('messages.system.receipts.approved_exceeds_remaining', [
                        'approved' => $this->fromHalalas($approvedHalalas),
                        'remaining' => $this->fromHalalas($remainingHalalas),
                        'default' => 'لا يمكن اعتماد مبلغ (:approved) يتجاوز الرصيد المتبقي للفاتورة (:remaining).',
                    ]),
                    422
                );
            }

            $willCompleteInvoice = $lockedInvoice !== null && ($remainingHalalas - $approvedHalalas) === 0;
            $operationType = null;

            // Resolve operation type BEFORE mutating financial state; halt safely if ambiguous.
            if ($willCompleteInvoice && $lockedInvoice) {
                $operationType = $this->resolveOperationType($lockedInvoice);
            }

            $isPartial = $approvedHalalas < $transferredHalalas;
            $reviewNotes = $trimmedNotes;

            if ($hasMismatch && $forceApprove) {
                $auditLine = sprintf(
                    '[FORCE-APPROVED] amount mismatch — expected=%s, actual=%s, approved=%s, diff=%s%s, by admin #%d at %s, reason=%s',
                    $this->fromHalalas($expectedHalalas),
                    $this->fromHalalas($transferredHalalas),
                    $this->fromHalalas($approvedHalalas),
                    $diffHalalas >= 0 ? '+' : '',
                    $this->fromHalalas($diffHalalas),
                    $reviewerId ?? 0,
                    now()->toIso8601String(),
                    $trimmedNotes
                );
                $reviewNotes = $reviewNotes ? $reviewNotes."\n".$auditLine : $auditLine;
            }

            if ($isPartial) {
                $partialLine = sprintf(
                    '[PARTIAL-APPROVAL] approved=%s, received=%s, shortfall=%s, by admin #%d at %s',
                    $this->fromHalalas($approvedHalalas),
                    $this->fromHalalas($transferredHalalas),
                    $this->fromHalalas($transferredHalalas - $approvedHalalas),
                    $reviewerId ?? 0,
                    now()->toIso8601String()
                );
                $reviewNotes = $reviewNotes ? $reviewNotes."\n".$partialLine : $partialLine;
            }

            $approvedDecimal = $this->fromHalalas($approvedHalalas);

            // Preserve original transfer_amount and store approved_amount independently.
            $lockedReceipt->update([
                'status' => BankTransferReceipt::STATUS_APPROVED,
                'approved_amount' => $approvedDecimal,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);

            if ($lockedPayment) {
                $remainingAfterHalalas = $remainingHalalas !== null
                    ? max(0, $remainingHalalas - $approvedHalalas)
                    : 0;

                $lockedPayment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'amount' => $approvedDecimal,
                    'payment_method' => 'bank_transfer',
                    'gateway_ref' => $lockedReceipt->transfer_reference,
                    'failure_reason' => null,
                    'gateway_response' => [
                        'receipt_id' => $lockedReceipt->id,
                        'transfer_amount' => (float) $lockedReceipt->transfer_amount,
                        'approved_amount' => (float) $approvedDecimal,
                        'transfer_date' => $lockedReceipt->transfer_date?->toDateString(),
                        'transfer_reference' => $lockedReceipt->transfer_reference,
                        'expected_amount' => $expectedHalalas !== null ? (float) $this->fromHalalas($expectedHalalas) : null,
                        'remaining_before' => $remainingHalalas !== null ? (float) $this->fromHalalas($remainingHalalas) : null,
                        'remaining_after' => (float) $this->fromHalalas($remainingAfterHalalas),
                        'amount_diff' => $diffHalalas !== null ? (float) $this->fromHalalas($diffHalalas) : null,
                        'force_approved' => $hasMismatch && $forceApprove,
                        'override_by' => ($hasMismatch && $forceApprove) ? $reviewerId : null,
                        'override_reason' => ($hasMismatch && $forceApprove) ? $trimmedNotes : null,
                        'partial_approval' => $isPartial,
                    ],
                ]);
            }

            $completedInvoice = false;
            $remainingAfterCheck = $lockedInvoice ? $this->getRemainingHalalas($lockedInvoice) : 0;

            if ($lockedInvoice && $remainingAfterCheck === 0 && $lockedInvoice->status !== 'paid') {
                $lockedInvoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $completedInvoice = true;

                if ($lockedSub) {
                    if ($operationType === SubscriptionInvoice::OPERATION_RENEWAL) {
                        $this->lifecycle->extendSubscriptionByCycle($lockedSub, $lockedSub->billing_cycle);
                    } else {
                        $this->lifecycle->activateSubscription($lockedSub);
                    }
                } elseif ($lockedReceipt->tenant) {
                    $lockedReceipt->tenant->update([
                        'status' => 'active',
                        'suspended_at' => null,
                        'auto_suspended_at' => null,
                        'suspension_reason' => null,
                    ]);
                }
            } elseif (! $lockedInvoice && $lockedReceipt->tenant) {
                $lockedReceipt->tenant->update([
                    'status' => 'active',
                    'suspended_at' => null,
                    'auto_suspended_at' => null,
                    'suspension_reason' => null,
                ]);
            }

            return [
                'already_reviewed' => false,
                'receipt' => $lockedReceipt->fresh(['tenant', 'payment']),
                'partial' => $isPartial || ($lockedInvoice !== null && $remainingAfterCheck > 0),
                'completed_invoice' => $completedInvoice,
                'operation_type' => $operationType,
                'review_notes' => $reviewNotes,
                'audit' => [
                    'tenant_id' => $lockedReceipt->tenant_id,
                    'transfer_amount' => $this->fromHalalas($transferredHalalas),
                    'approved_amount' => $approvedDecimal,
                    'expected_amount' => $expectedHalalas !== null ? $this->fromHalalas($expectedHalalas) : null,
                    'amount_diff' => $diffHalalas !== null ? $this->fromHalalas($diffHalalas) : null,
                    'remaining_after' => $this->fromHalalas($remainingAfterCheck),
                    'partial' => $isPartial,
                    'force_approved' => $hasMismatch && $forceApprove,
                    'override_by' => ($hasMismatch && $forceApprove) ? $reviewerId : null,
                    'override_reason' => ($hasMismatch && $forceApprove) ? $trimmedNotes : null,
                    'operation_type' => $operationType,
                ],
            ];
        });

        if (! $result['already_reviewed']) {
            event(new ReceiptReviewed(
                $result['receipt'],
                'approved',
                $result['review_notes'] ?? null,
            ));
        }

        return $result;
    }

    /**
     * Reject a pending receipt, marking the linked pending payment as failed
     * while preserving the subscription, invoice, and historical financial records.
     *
     * @return array{already_reviewed: bool, receipt: BankTransferReceipt}
     */
    public function rejectReceipt(
        BankTransferReceipt $receipt,
        ?AdminUser $reviewer,
        string $reason
    ): array {
        $receipt->loadMissing(['payment.invoice']);
        $paymentModel = $receipt->payment;
        $invoiceSubId = ($paymentModel !== null && $paymentModel->invoice !== null)
            ? $paymentModel->invoice->subscription_id
            : null;
        $subscriptionId = $receipt->subscription_id
            ?? ($paymentModel !== null ? $paymentModel->subscription_id : null)
            ?? $invoiceSubId;
        $invoiceId = $paymentModel?->subscription_invoice_id;
        $paymentId = $receipt->subscription_payment_id;
        $reviewerId = $reviewer?->id;

        $result = DB::transaction(function () use (
            $receipt,
            $subscriptionId,
            $invoiceId,
            $paymentId,
            $reviewerId,
            $reason
        ): array {
            if ($subscriptionId !== null) {
                Subscription::query()->whereKey($subscriptionId)->lockForUpdate()->first();
            }

            if ($invoiceId !== null) {
                SubscriptionInvoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
            }

            $lockedReceipt = BankTransferReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $lockedPayment = $paymentId !== null
                ? SubscriptionPayment::query()->whereKey($paymentId)->lockForUpdate()->first()
                : null;

            if ($lockedReceipt->status !== BankTransferReceipt::STATUS_PENDING) {
                return [
                    'already_reviewed' => true,
                    'receipt' => $lockedReceipt,
                ];
            }

            $lockedReceipt->update([
                'status' => BankTransferReceipt::STATUS_REJECTED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $reason,
            ]);

            if ($lockedPayment && $lockedPayment->status === 'pending') {
                $lockedPayment->update([
                    'status' => 'failed',
                    'failure_reason' => $reason,
                ]);
            }

            return [
                'already_reviewed' => false,
                'receipt' => $lockedReceipt->fresh(['tenant', 'payment']),
            ];
        });

        if (! $result['already_reviewed']) {
            event(new ReceiptReviewed(
                $result['receipt'],
                'rejected',
                $reason,
            ));
        }

        return $result;
    }

    /**
     * Single server-side source of truth for a tenant's in-flight subscription,
     * open invoices, receipt review status, and allowed next actions.
     *
     * @return array<string, mixed>
     */
    public function getTenantBillingState(Tenant $tenant): array
    {
        $pendingSub = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'pending_payment'])
            ->with('plan')
            ->latest()
            ->first();

        $pendingReceipt = BankTransferReceipt::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', BankTransferReceipt::STATUS_PENDING)
            ->with(['subscription.plan', 'payment.invoice'])
            ->latest()
            ->first();

        $openInvoicesCollection = SubscriptionInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'pending_payment', 'overdue'])
            ->with(['subscription.plan'])
            ->orderBy('due_at')
            ->get();

        $openInvoices = [];
        $hasApprovedPayments = false;

        foreach ($openInvoicesCollection as $inv) {
            $paidHalalas = $this->getPaidHalalas($inv);
            $remainingHalalas = $this->getRemainingHalalas($inv);
            $hasApproved = $paidHalalas > 0;

            if ($hasApproved) {
                $hasApprovedPayments = true;
            }

            $openInvoices[] = [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'subscription_id' => $inv->subscription_id,
                'plan_id' => $inv->subscription?->plan_id,
                'plan_name_ar' => $inv->subscription?->plan?->name_ar,
                'plan_name_en' => $inv->subscription?->plan?->name_en,
                'amount' => (float) $inv->total,
                'paid_amount' => (float) $this->fromHalalas($paidHalalas),
                'remaining_amount' => (float) $this->fromHalalas($remainingHalalas),
                'has_approved_payments' => $hasApproved,
                'due_date' => $inv->due_at?->toDateString(),
                'status' => $inv->status,
            ];
        }

        $primaryOpenInvoice = $openInvoices[0] ?? null;

        if ($pendingReceipt !== null) {
            $pendingInvoiceId = $pendingReceipt->payment?->subscription_invoice_id;

            if ($pendingInvoiceId !== null) {
                foreach ($openInvoices as $openInv) {
                    if ($openInv['id'] === $pendingInvoiceId) {
                        $primaryOpenInvoice = $openInv;
                        break;
                    }
                }
            }
        } elseif ($hasApprovedPayments) {
            foreach ($openInvoices as $openInv) {
                if ($openInv['has_approved_payments']) {
                    $primaryOpenInvoice = $openInv;
                    break;
                }
            }
        }

        $openInvoiceIds = $openInvoicesCollection->pluck('id')->all();
        $latestRejectedReceipt = null;

        if (! $pendingReceipt && $openInvoiceIds !== []) {
            $rejectedModel = BankTransferReceipt::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', BankTransferReceipt::STATUS_REJECTED)
                ->whereHas('payment', static fn ($q) => $q->whereIn('subscription_invoice_id', $openInvoiceIds))
                ->with('payment')
                ->latest()
                ->first();

            if ($rejectedModel) {
                $rejectedInvoiceId = $rejectedModel->payment?->subscription_invoice_id;

                if ($rejectedInvoiceId !== null && ! $hasApprovedPayments) {
                    foreach ($openInvoices as $openInv) {
                        if ($openInv['id'] === $rejectedInvoiceId) {
                            $primaryOpenInvoice = $openInv;
                            break;
                        }
                    }
                }

                $reviewedAt = $rejectedModel->reviewed_at;
                $latestRejectedReceipt = [
                    'id' => $rejectedModel->id,
                    'subscription_id' => $rejectedModel->subscription_id,
                    'subscription_invoice_id' => $rejectedInvoiceId,
                    'transfer_reference' => $rejectedModel->transfer_reference,
                    'transfer_amount' => (float) $rejectedModel->transfer_amount,
                    'review_notes' => $rejectedModel->review_notes,
                    'reviewed_at' => $reviewedAt instanceof DateTimeInterface
                        ? $reviewedAt->toIso8601String()
                        : ($reviewedAt ?: null),
                ];
            }
        }

        $hasPendingReceipt = $pendingReceipt !== null;
        $hasRejectedReceipt = ! $hasPendingReceipt && $latestRejectedReceipt !== null;

        $displayStatus = 'none';

        if ($hasPendingReceipt) {
            $displayStatus = 'under_review';
        } elseif ($hasApprovedPayments) {
            $displayStatus = 'partially_paid';
        } elseif ($hasRejectedReceipt) {
            $displayStatus = 'receipt_rejected';
        } elseif ($pendingSub !== null || $primaryOpenInvoice !== null) {
            $displayStatus = 'awaiting_payment';
        }

        return [
            'display_status' => $displayStatus,
            'has_pending_subscription' => $pendingSub !== null,
            'pending_subscription_id' => $pendingSub?->id,
            'pending_subscription_status' => $pendingSub?->status,
            'pending_plan_id' => $pendingSub?->plan_id,
            'pending_plan_name_ar' => $pendingSub?->plan?->name_ar,
            'pending_plan_name_en' => $pendingSub?->plan?->name_en,
            'has_pending_receipt' => $hasPendingReceipt,
            'has_rejected_receipt' => $hasRejectedReceipt,
            'has_approved_payments' => $hasApprovedPayments,
            'latest_rejected_receipt' => $latestRejectedReceipt,
            'open_invoice' => $primaryOpenInvoice,
            'open_invoices' => $openInvoices,
            'open_invoices_count' => count($openInvoices),
            'has_multiple_open_invoices' => count($openInvoices) > 1,
            'allowed_actions' => [
                'can_upload_receipt' => ! $hasPendingReceipt && $primaryOpenInvoice !== null,
                'can_cancel_pending' => $pendingSub !== null && ! $hasPendingReceipt && ! $hasApprovedPayments,
                'can_change_plan_or_gateway' => ! $hasPendingReceipt && ! $hasApprovedPayments,
            ],
        ];
    }

    /**
     * Safely cancel an unpaid in-flight subscription when switching plans/gateways
     * or cancelling a rejected request, while preserving historical financial rows.
     */
    public function cancelUnpaidPendingSubscription(Subscription $pendingSub, string $reason): void
    {
        DB::transaction(function () use ($pendingSub, $reason): void {
            $lockedSub = Subscription::query()->whereKey($pendingSub->id)->lockForUpdate()->first();

            if (! $lockedSub) {
                return;
            }

            // Canonical lock order: Subscription -> SubscriptionInvoice -> BankTransferReceipt
            $invoices = SubscriptionInvoice::query()
                ->where('subscription_id', $lockedSub->id)
                ->lockForUpdate()
                ->get();

            $hasPendingReceipt = BankTransferReceipt::query()
                ->where('subscription_id', $lockedSub->id)
                ->where('status', BankTransferReceipt::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            if ($hasPendingReceipt) {
                throw new BankTransferException(
                    __('company_profile.subscription.cannot_cancel_receipt_under_review', [
                        'default' => 'لا يمكن إلغاء الطلب أو تغييره لوجود إيصال تحويل بنكي قيد المراجعة الإدارية حالياً.',
                    ]),
                    409
                );
            }

            foreach ($invoices as $invoice) {
                if ($this->getPaidHalalas($invoice) > 0) {
                    throw new BankTransferException(
                        __('company_profile.subscription.cannot_cancel_partially_paid_invoice', [
                            'default' => 'لا يمكن إلغاء الفاتورة أو تغيير الباقة لوجود مبالغ معتمدة مسددة عليها جزئياً.',
                        ]),
                        409
                    );
                }
            }

            $hasBankTransferHistory = BankTransferReceipt::where('subscription_id', $lockedSub->id)->exists();

            if ($hasBankTransferHistory) {
                SubscriptionPayment::where('subscription_id', $lockedSub->id)
                    ->whereIn('status', ['pending', 'pending_payment', 'initiated'])
                    ->update([
                        'status' => 'cancelled',
                        'failure_reason' => $reason,
                    ]);

                SubscriptionInvoice::where('subscription_id', $lockedSub->id)
                    ->whereIn('status', ['pending', 'pending_payment', 'overdue'])
                    ->update(['status' => 'cancelled']);

                $lockedSub->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                return;
            }

            // Online/abandoned checkout attempt with zero bank transfer receipts:
            // preserve detached payment log while cleaning up provisional subscription & invoice
            SubscriptionPayment::where('subscription_id', $lockedSub->id)
                ->update([
                    'status' => 'cancelled',
                    'failure_reason' => $reason,
                    'subscription_id' => null,
                    'subscription_invoice_id' => null,
                ]);

            SubscriptionInvoice::where('subscription_id', $lockedSub->id)->delete();
            $lockedSub->delete();
        });
    }
}
