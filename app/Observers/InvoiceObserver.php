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
     * The fields each SPECIFIC transition is allowed to write.
     *
     * The allowlist is keyed by the transition — `"from>to"` — and not
     * by the operation in general. That distinction is the point of the
     * contract:
     *
     *  - `draft>valid` is the ISSUE transition. The issuer assigns the
     *    document number and the issue date at the moment it issues the
     *    document, so those two fields are part of the transition
     *    itself and must land atomically with the status change. It may
     *    also fill in `currency_code`, which the issuer resolves from
     *    the tenant when the draft did not carry one — the issue save
     *    writes `$invoice->currency_code ?: tenantCurrency($tenant)`,
     *    so a draft with no currency of its own gets the tenant's here,
     *    in the same save as the status change.
     *
     *  - `valid>reported` is the REPORTING transition. It stamps the
     *    ZATCA chain onto a document that has already been issued. The
     *    document number and the issue date were fixed at issue time
     *    and are NOT part of this transition, so they may not move here.
     *
     *  - `valid>cancelled` and `draft>cancelled` are CANCELLATIONS.
     *    They only change the state. The document number and the issue
     *    date are fixed as soon as the document is issued, and a
     *    cancellation must never rewrite them.
     *
     * Every field not listed for the transition being performed is
     * rejected, whether or not it is in the list for another transition.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITION_FIELDS = [
        // The issue transition: the number and the date are assigned here.
        self::STATUS_DRAFT.'>'.self::STATUS_VALID => [
            'status',
            'invoice_number',
            'issue_date',
            'currency_code',
        ],

        // The reporting transition: the ZATCA chain only.
        self::STATUS_VALID.'>'.self::STATUS_REPORTED => [
            'status',
            'zatca_uuid',
            'zatca_qr_tlv',
            'zatca_hash',
            'zatca_prev_hash',
            'xml_path',
        ],

        // Cancellations: the state only. The number and the date were
        // fixed when the document was issued.
        self::STATUS_DRAFT.'>'.self::STATUS_CANCELLED => [
            'status',
        ],
        self::STATUS_VALID.'>'.self::STATUS_CANCELLED => [
            'status',
        ],
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

            // The allowlist is the one for THIS transition. The issue
            // transition may set the document number and the issue date;
            // the reporting transition may stamp the ZATCA chain; a
            // cancellation may do neither.
            //
            // A transition must not smuggle unrelated changes through the
            // same save. If the request combines an allowed change with a
            // forbidden one, the forbidden field is still in the dirty
            // set, so the whole operation is rejected.
            $this->assertOnlyFields(
                $invoice,
                $dirty,
                $this->allowedFieldsForTransition($originalStatus, (string) $invoice->status),
                'status'
            );

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
     * The fields the given transition is allowed to write.
     *
     * A transition that is not listed here has NO fields it may write
     * beyond the status itself — which means an unknown transition is
     * rejected rather than silently allowed through with the issue
     * transition's permissions.
     *
     * @return list<string>
     */
    private function allowedFieldsForTransition(string $from, string $to): array
    {
        if ($from === $to) {
            // Not a transition at all — the status did not move. This is
            // the settlement / reporting path, which has its own
            // allowlist.
            return [];
        }

        $allowed = self::TRANSITION_FIELDS[$from.'>'.$to] ?? [];

        // The status itself is always part of the transition, and it is
        // listed explicitly in every entry — but stating it here makes
        // the contract independent of that bookkeeping.
        if (! in_array('status', $allowed, true)) {
            $allowed[] = 'status';
        }

        return $allowed;
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
