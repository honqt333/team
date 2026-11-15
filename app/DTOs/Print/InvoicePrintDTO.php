<?php

declare(strict_types=1);

namespace App\DTOs\Print;

final readonly class InvoicePrintDTO
{
    /**
     * @param  array<string, mixed>  $seller
     * @param  array<string, mixed>  $party
     * @param  array<string, mixed>|null  $vehicle
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $totals
     * @param  array<int, array<string, mixed>>  $payments
     * @param  array<string, mixed>  $signatures
     * @param  array<string, mixed>  $settings
     * @param  array<int, string>  $missingSnapshots
     */
    public function __construct(
        public string $documentType,
        public string $locale,
        public string $direction,
        public string $code,
        public ?string $status,
        public ?string $subtype,
        public ?string $issueDate,
        public ?string $dueDate,
        public ?string $supplyDate,
        public string $currencyCode,
        public array $seller,
        public array $party,
        public ?array $vehicle,
        public array $items,
        public array $totals,
        public array $payments,
        public ?string $qrDataUri,
        public ?string $qrTlv,
        public bool $qrMandatory,
        public ?string $originalInvoiceNumber,
        public ?string $returnReason,
        public ?string $notes,
        public ?string $warrantyText,
        public ?string $termsAndConditions,
        public array $signatures,
        public array $settings,
        public array $missingSnapshots = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'document_type' => $this->documentType,
            'locale' => $this->locale,
            'direction' => $this->direction,
            'code' => $this->code,
            'status' => $this->status,
            'subtype' => $this->subtype,
            'issue_date' => $this->issueDate,
            'due_date' => $this->dueDate,
            'supply_date' => $this->supplyDate,
            'currency_code' => $this->currencyCode,
            'seller' => $this->seller,
            'party' => $this->party,
            'vehicle' => $this->vehicle,
            'items' => $this->items,
            'totals' => $this->totals,
            'payments' => $this->payments,
            'qr_data_uri' => $this->qrDataUri,
            'qr_tlv' => $this->qrTlv,
            'qr_mandatory' => $this->qrMandatory,
            'original_invoice_number' => $this->originalInvoiceNumber,
            'return_reason' => $this->returnReason,
            'notes' => $this->notes,
            'warranty_text' => $this->warrantyText,
            'terms_and_conditions' => $this->termsAndConditions,
            'signatures' => $this->signatures,
            'settings' => $this->settings,
            'missing_snapshots' => $this->missingSnapshots,
        ];
    }
}
