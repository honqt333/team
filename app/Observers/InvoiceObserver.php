<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Invoice;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use Illuminate\Validation\ValidationException;

/**
 * Guards the immutability contract of an issued invoice.
 *
 * A sales invoice that has left `draft` is a financial document: once it
 * has been issued it carries a ZATCA invoice number and an ICV that is
 * part of the tenant's hash chain. Editing its amounts, its tax, or the
 * identity of either party after the fact silently rewrites history.
 *
 * The contract is explicit on purpose:
 *
 *  - Status transitions are enumerated. An issued document can never be
 *    pushed back to `draft`.
 *  - Every dirty field of the save is inspected — not just the status.
 *    Changing the status does not exempt the rest of the payload.
 *  - A save that mixes an allowed change with a forbidden one is
 *    rejected in full. There is no partial application.
 *
 * Enforcement raises a ValidationException so that:
 *  - a JSON request receives a 422 with an `errors` bag, and
 *  - an Inertia request receives the usual validation errors in the
 *    session, which the forms already render.
 *
 * Unexpected database or runtime errors are deliberately NOT converted
 * into validation failures — they must keep bubbling up to the normal
 * exception handler so they stay diagnosable in the logs.
 *
 * @see InvoiceService::issueInvoice()  for the issue transition
 * @see Invoice::updatePaymentStatus()     for the settlement path
 * @see CreditNoteService                for credit notes (created directly, then settled)
 */
class InvoiceObserver
{
    /**
     * The status of a sales invoice that has been issued.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_VALID = 'valid';

    public const STATUS_REPORTED = 'reported';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Allowed status transitions, keyed by the CURRENT status.
     *
     * A transition is only permitted when the target status is listed
     * for the current status. Anything not listed here is rejected.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_VALID, self::STATUS_CANCELLED],
        self::STATUS_VALID => [self::STATUS_REPORTED, self::STATUS_CANCELLED],
        self::STATUS_REPORTED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Fields that may be written once the invoice has left `draft`,
     * grouped by the operation that is permitted to write them.
     *
     * `settlement` — the payment path. Only the amount that has actually
     * been paid and its derived status may move. The invoice totals, its
     * tax and the identity of either party must stay exactly as issued.
     *
     * `reporting` — the ZATCA reporting path, which stamps the UUID,
     * the hash chain and the generated XML onto an already issued
     * document without altering its commercial content.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_FIELDS = [
        'settlement' => [
            'total_paid',
            'payment_status',
        ],
        'reporting' => [
            'zatca_uuid',
            'zatca_qr_tlv',
            'zatca_hash',
            'zatca_prev_hash',
            'xml_path',
        ],
    ];

    /**
     * Fields that the issue transition is allowed to set, in the same save.
     *
     * The issuer assigns the document number and the issue date at the
     * moment it issues the document; they are part of the transition
     * itself and must land atomically with the status change.
     *
     * @var list<string>
     */
    private const ISSUE_FIELDS = [
        'status',
        'invoice_number',
        'issue_date',
    ];

    /**
     * Fields that may never be modified after the invoice has been issued.
     *
     * This is the commercial content of the document: what was sold, for
     * how much, with how much tax, and who the parties are.
     *
     * @var list<string>
     */
    private const PROTECTED_FIELDS = [
        'total_excl_tax',
        'total_tax',
        'total_incl_tax',
        'total_taxable_amount',
        'tax_enabled_snapshot',
        'pricing_mode_snapshot',
        'tax_rate_snapshot',
        'currency_code',
        'tax_breakdown',
        'customer_name_snapshot',
        'customer_vat_snapshot',
        'customer_address_snapshot',
        'seller_name_snapshot',
        'seller_name_en_snapshot',
        'seller_vat_snapshot',
        'seller_cr_snapshot',
        'invoice_number',
        'issue_date',
        'supply_date',
        'due_date',
        'customer_id',
        'work_order_id',
        'type',
        'subtype',
        'original_invoice_id',
        'notes',
        'return_reason',
    ];

    public function updating(Invoice $invoice): void
    {
        $dirty = array_keys($invoice->getDirty());

        // Nothing changed — nothing to police.
        if ($dirty === []) {
            return;
        }

        $originalStatus = (string) $invoice->getOriginal('status');

        // A draft is still a working document. The contract only binds
        // once the invoice has actually been issued.
        if ($originalStatus === self::STATUS_DRAFT && ! $invoice->isDirty('status')) {
            return;
        }

        if ($invoice->isDirty('status')) {
            $this->assertTransitionAllowed($invoice, $originalStatus);

            // A transition must not smuggle unrelated changes through the
            // same save. The issue transition legitimately sets the
            // document number and the issue date; nothing else.
            //
            // The transition has already been validated as a whole, so
            // the settlement allowlist does not apply to it — the issue
            // path is the one place that is allowed to write the
            // document number and the issue date.
            $this->assertOnlyFields($invoice, $dirty, self::ISSUE_FIELDS, 'status');

            return;
        }

        // Everything that is left must be a field the settlement or the
        // reporting path is allowed to write. This is what stops a save
        // that mixes an allowed change with a forbidden one: the mixed
        // save still carries the forbidden field in its dirty set.
        $this->assertSettlableFieldsOnly($invoice, $dirty);
    }

    public function deleting(Invoice $invoice): void
    {
        if ((string) $invoice->status !== self::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => __('validation.invoice_immutable_delete', [
                    'status' => $invoice->status,
                ]),
            ]);
        }
    }

    public function forceDeleting(Invoice $invoice): void
    {
        $this->deleting($invoice);
    }

    /**
     * The target status of a transition is the value being written, not
     * the original one. Eloquent keeps the incoming value on the model
     * and the persisted one in the original attributes.
     */
    private function assertTransitionAllowed(Invoice $invoice, string $originalStatus): void
    {
        $target = (string) $invoice->status;

        if ($target === $originalStatus) {
            return;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$originalStatus] ?? [];

        if (! in_array($target, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('validation.invoice_immutable_transition', [
                    'from' => $originalStatus,
                    'to' => $target,
                ]),
            ]);
        }
    }

    /**
     * @param list<string> $dirty
     */
    private function assertOnlyFields(Invoice $invoice, array $dirty, array $allowed, string $context): void
    {
        $unexpected = array_values(array_diff($dirty, $allowed));

        if ($unexpected !== []) {
            throw ValidationException::withMessages([
                'status' => __('validation.invoice_immutable_transition_fields', [
                    'fields' => implode(', ', $unexpected),
                ]),
            ]);
        }
    }

    /**
     * @param list<string> $dirty
     */
    private function assertSettlableFieldsOnly(Invoice $invoice, array $dirty): void
    {
        $settleable = self::allowedFieldsAfterIssue();

        // Fields that carry the commercial content of the document get a
        // field-specific error key; anything else gets the generic one.
        $forbidden = array_values(array_diff($dirty, $settleable));

        if ($forbidden === []) {
            return;
        }

        $protected = array_values(array_intersect($forbidden, self::PROTECTED_FIELDS));

        if ($protected !== []) {
            throw ValidationException::withMessages([
                $protected[0] => __('validation.invoice_immutable_field', [
                    'field' => $protected[0],
                ]),
            ]);
        }

        throw ValidationException::withMessages([
            'invoice' => __('validation.invoice_immutable_field', [
                'field' => $forbidden[0],
            ]),
        ]);
    }

    /**
     * Fields the settlement and reporting paths are allowed to touch.
     *
     * Exposed for documentation and for the acceptance tests, which
     * assert that these are the ONLY fields that may move after issue.
     *
     * @return list<string>
     */
    public static function allowedFieldsAfterIssue(): array
    {
        return array_values(array_unique(array_merge(
            self::ALLOWED_FIELDS['settlement'],
            self::ALLOWED_FIELDS['reporting'],
        )));
    }
}
