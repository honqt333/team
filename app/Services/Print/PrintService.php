<?php

declare(strict_types=1);

namespace App\Services\Print;

use App\Models\Billing\SubscriptionInvoice;
use App\Models\Invoice;
use App\Models\PaymentSettings;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\Quote;
use App\Models\WorkOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

/**
 * Server-side PDF generation service for all document types.
 *
 * Uses DomPDF (barryvdh/laravel-dompdf) to render HTML views to PDF.
 * Supports all document types: invoices, quotes, work orders, purchase invoices,
 * subscription invoices, credit notes, etc.
 *
 * The service:
 * - Loads company settings from PaymentSettings for logo/stamp/branding
 * - Embeds images as data: URLs for self-contained PDFs
 * - Stores PDFs in public storage with organized paths
 * - Returns file paths/URLs for download or email attachment
 */
class PrintService
{
    /** @var string Default storage disk for generated PDFs */
    protected string $disk = 'public';

    /** @var array<int, string> Supported document types with their view paths */
    protected array $documentViews = [
        // App invoices
        'invoice' => 'pdf.invoice',
        'proforma_invoice' => 'pdf.proforma-invoice',
        'credit_note' => 'pdf.credit-note',

        // Quotes
        'quotation' => 'pdf.quotation',

        // Work orders
        'work_order' => 'pdf.work-order',
        'condition_report' => 'pdf.condition-report',
        'payments' => 'pdf.payments',

        // Purchasing
        'purchase_invoice' => 'pdf.purchase-invoice',
        'purchase_return' => 'pdf.purchase-return',

        // Billing
        'subscription_invoice' => 'pdf.subscription-invoice',

        // HR
        'payslip' => 'pdf.payslip',
        'payroll' => 'pdf.payroll',
        'attendance' => 'pdf.attendance',
        'employee' => 'pdf.employee',

        // Reports
        'customers' => 'pdf.customers',
        'vehicles' => 'pdf.vehicles',
    ];

    /** @var array<int, string> Paper sizes per document type */
    protected array $paperSizes = [
        'invoice' => 'a4',
        'proforma_invoice' => 'a4',
        'credit_note' => 'a4',
        'quotation' => 'a4',
        'work_order' => 'a4',
        'condition_report' => 'a4',
        'payments' => 'a4',
        'purchase_invoice' => 'a4',
        'purchase_return' => 'a4',
        'subscription_invoice' => 'a4',
        'payslip' => 'a4',
        'payroll' => 'a4',
        'attendance' => 'a4',
        'employee' => 'a4',
        'customers' => 'a4',
        'vehicles' => 'a4',
    ];

    /** @var array<int, string> Paper orientations per document type */
    protected array $orientations = [
        'condition_report' => 'landscape',
        'payroll' => 'landscape',
        'attendance' => 'landscape',
        'employees' => 'landscape',
    ];

    /**
     * Generate PDF for a document.
     *
     * @param string $documentType One of the keys in $documentViews
     * @param mixed $model The Eloquent model or data object
     * @param array<string, mixed> $extraData Additional data to pass to the view
     * @return string Storage path of the generated PDF
     */
    public function generate(string $documentType, $model, array $extraData = []): string
    {
        $view = $this->resolveView($documentType);

        if (! $view) {
            throw new InvalidArgumentException("Unsupported document type: {$documentType}");
        }

        // Load company info once
        $companyInfo = $this->buildCompanyInfo();

        // Prepare view data
        $viewData = array_merge([
            'company' => $companyInfo,
            'document_type' => $documentType,
        ], $this->prepareModelData($documentType, $model), $extraData);

        // Generate PDF
        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->loadView($view, $viewData);

        // Set paper size and orientation
        $paper = $this->paperSizes[$documentType] ?? 'a4';
        $orientation = $this->orientations[$documentType] ?? 'portrait';
        $pdf->setPaper($paper, $orientation);

        // Store PDF
        $filename = $this->generateFilename($documentType, $model);
        $disk = Storage::disk($this->disk);
        $disk->put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Generate PDF and return raw bytes (for streaming/email attachment).
     */
    public function generateRaw(string $documentType, $model, array $extraData = []): string
    {
        $view = $this->resolveView($documentType);

        if (! $view) {
            throw new InvalidArgumentException("Unsupported document type: {$documentType}");
        }

        $companyInfo = $this->buildCompanyInfo();
        $viewData = array_merge([
            'company' => $companyInfo,
            'document_type' => $documentType,
        ], $this->prepareModelData($documentType, $model), $extraData);

        $pdf = app('dompdf.wrapper');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->loadView($view, $viewData);

        $paper = $this->paperSizes[$documentType] ?? 'a4';
        $orientation = $this->orientations[$documentType] ?? 'portrait';
        $pdf->setPaper($paper, $orientation);

        return $pdf->output();
    }

    /**
     * Generate PDF and attach to a mail message.
     */
    public function attachToMail(
        Message $message,
        string $documentType,
        $model,
        ?string $filename = null,
        array $extraData = []
    ): void {
        $pdfContent = $this->generateRaw($documentType, $model, $extraData);
        $attachFilename = $filename ?? $this->generateFilename($documentType, $model, false);

        $message->attachData($pdfContent, $attachFilename, [
            'mime' => 'application/pdf',
        ]);
    }

    /**
     * Get storage path for an already-generated PDF.
     */
    public function getPath(string $documentType, $model): ?string
    {
        $filename = $this->generateFilename($documentType, $model, false);

        if (Storage::disk($this->disk)->exists($filename)) {
            return $filename;
        }

        return null;
    }

    /**
     * Get public URL for a generated PDF.
     */
    public function getUrl(string $documentType, $model): ?string
    {
        $path = $this->getPath($documentType, $model);

        if ($path) {
            return Storage::disk($this->disk)->url($path);
        }

        return null;
    }

    /**
     * Regenerate PDF (overwrite existing).
     */
    public function regenerate(string $documentType, $model, array $extraData = []): string
    {
        $filename = $this->generateFilename($documentType, $model, false);
        Storage::disk($this->disk)->delete($filename);

        return $this->generate($documentType, $model, $extraData);
    }

    /**
     * Delete generated PDF.
     */
    public function delete(string $documentType, $model): bool
    {
        $filename = $this->generateFilename($documentType, $model, false);

        return Storage::disk($this->disk)->delete($filename);
    }

    /**
     * Resolve view name for document type.
     */
    protected function resolveView(string $documentType): ?string
    {
        return $this->documentViews[$documentType] ?? null;
    }

    /**
     * Prepare model data for view.
     */
    protected function prepareModelData(string $documentType, $model): array
    {
        return match ($documentType) {
            'invoice', 'proforma_invoice', 'credit_note' => $this->prepareInvoiceData($model),
            'quotation' => $this->prepareQuoteData($model),
            'work_order', 'condition_report', 'payments' => $this->prepareWorkOrderData($model),
            'purchase_invoice', 'purchase_return' => $this->preparePurchaseInvoiceData($model),
            'subscription_invoice' => $this->prepareSubscriptionInvoiceData($model),
            'payslip', 'payroll', 'attendance', 'employee' => $this->prepareHrData($model, $documentType),
            'customers', 'vehicles' => $this->prepareReportData($model, $documentType),
            default => ['model' => $model],
        };
    }

    /**
     * Prepare invoice data for PDF view.
     */
    protected function prepareInvoiceData(Invoice $invoice): array
    {
        $invoice->loadMissing([
            'customer',
            'workOrder.customer',
            'workOrder.vehicle.make',
            'workOrder.vehicle.model',
            'lines.service.department',
            'lines.technicians.employee.jobTitle',
            'center',
            'tenant',
            'payments.receivedBy',
            'companyTransaction',
        ]);

        return [
            'invoice' => $invoice,
            'lines' => $invoice->lines->filter(fn ($l) => $l->status !== 'cancelled'),
            'payments' => $invoice->payments,
            'center' => $invoice->center,
            'tenant' => $invoice->tenant,
        ];
    }

    /**
     * Prepare quote data for PDF view.
     */
    protected function prepareQuoteData(Quote $quote): array
    {
        $quote->loadMissing([
            'center.address',
            'customer',
            'vehicle.make',
            'vehicle.customer',
            'vehicle.model',
            'lines.service.department',
            'parts.part.inventoryBalances',
            'parts.quoteLine',
            'departments',
            'createdByUser',
        ]);

        $quote->append('signature_url');

        return [
            'quote' => $quote,
            'lines' => $quote->lines->filter(fn ($l) => $l->status !== 'cancelled'),
            'parts' => $quote->parts,
        ];
    }

    /**
     * Prepare work order data for PDF view.
     */
    protected function prepareWorkOrderData(WorkOrder $workOrder): array
    {
        $workOrder->loadMissing([
            'customer',
            'vehicle.make',
            'vehicle.model',
            'items.service.department',
            'items.technicians.employee.jobTitle',
            'items.parts.part',
            'items.parts.warehouse',
            'parts.part',
            'parts.warehouse',
            'center',
            'tenant',
            'damageMarks',
            'photos',
        ]);

        return [
            'workOrder' => $workOrder,
            'items' => $workOrder->items->filter(fn ($i) => $i->status !== 'cancelled'),
            'parts' => $workOrder->parts,
        ];
    }

    /**
     * Prepare purchase invoice data for PDF view.
     */
    protected function preparePurchaseInvoiceData(PurchaseInvoice|PurchaseReturnInvoice $purchaseInvoice): array
    {
        $purchaseInvoice->loadMissing([
            'supplier',
            'warehouse',
            'center',
            'lines.part',
            'lines.unit',
            'createdBy',
        ]);

        return [
            'purchaseInvoice' => $purchaseInvoice,
            'lines' => $purchaseInvoice->lines,
        ];
    }

    /**
     * Prepare subscription invoice data for PDF view.
     */
    protected function prepareSubscriptionInvoiceData(SubscriptionInvoice $invoice): array
    {
        $invoice->loadMissing([
            'subscription.plan',
            'subscription.tenant',
            'tenant',
        ]);

        $paymentSettings = PaymentSettings::first();
        $vatRate = (float) ($paymentSettings?->subscription_vat_rate ?? 15);

        return [
            'invoice' => $invoice,
            'subscription' => $invoice->subscription,
            'tenant' => $invoice->tenant,
            'plan' => $invoice->subscription->plan,
            'vat_rate' => $vatRate,
        ];
    }

    /**
     * Prepare HR data for PDF view.
     */
    protected function prepareHrData($model, string $documentType): array
    {
        return match ($documentType) {
            'payslip' => ['payslip' => $model],
            'payroll' => ['payroll' => $model],
            'attendance' => ['attendance' => $model],
            'employee' => ['employee' => $model],
            default => ['model' => $model],
        };
    }

    /**
     * Prepare report data for PDF view.
     */
    protected function prepareReportData($model, string $documentType): array
    {
        return match ($documentType) {
            'customers' => ['customers' => $model, 'reportDate' => now()],
            'vehicles' => ['vehicles' => $model, 'reportDate' => now()],
            default => ['model' => $model],
        };
    }

    /**
     * Build company info from PaymentSettings for PDF header/footer.
     */
    protected function buildCompanyInfo(): array
    {
        $settings = PaymentSettings::first();
        $publicDisk = Storage::disk('public');

        $logoDataUrl = null;

        if ($settings?->company_logo_path && $publicDisk->exists($settings->company_logo_path)) {
            $logoDataUrl = $this->fileToDataUrl($publicDisk->path($settings->company_logo_path));
        }

        $stampDataUrl = null;

        if ($settings?->company_stamp_path && $publicDisk->exists($settings->company_stamp_path)) {
            $stampDataUrl = $this->fileToDataUrl($publicDisk->path($settings->company_stamp_path));
        }

        $name = $settings?->company_legal_name
            ?: ($settings?->company_legal_name_en ?: 'Khidma Pro');
        $nameEn = $settings?->company_legal_name_en
            ?: ($settings?->company_legal_name ?: 'Khidma Pro');

        return [
            'name' => $name,
            'name_en' => $nameEn,
            'vat_number' => $settings?->company_vat_number,
            'cr_number' => $settings?->company_cr_number,
            'phone' => $settings?->company_phone,
            'email' => $settings?->company_email,
            'website' => $settings?->company_website,
            'center_name' => $settings?->company_center_name,
            'logo_url' => $logoDataUrl,
            'stamp_url' => $stampDataUrl,
        ];
    }

    /**
     * Convert a local file to a data: URL for self-contained PDFs.
     */
    protected function fileToDataUrl(string $absolutePath): ?string
    {
        if (! is_readable($absolutePath)) {
            return null;
        }

        $bytes = file_get_contents($absolutePath);

        if ($bytes === false) {
            return null;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    /**
     * Generate a consistent filename for the PDF.
     */
    protected function generateFilename(string $documentType, $model, bool $includeExtension = true): string
    {
        $code = $this->extractCode($model);
        $extension = $includeExtension ? '.pdf' : '';
        $folder = match ($documentType) {
            'invoice', 'proforma_invoice', 'credit_note' => 'invoices',
            'quotation' => 'quotes',
            'work_order', 'condition_report', 'payments' => 'work-orders',
            'purchase_invoice', 'purchase_return' => 'purchases',
            'subscription_invoice' => 'subscriptions',
            'payslip', 'payroll', 'attendance', 'employee' => 'hr',
            'customers', 'vehicles' => 'reports',
            default => 'documents',
        };

        return "{$folder}/{$documentType}/{$code}{$extension}";
    }

    /**
     * Extract code/identifier from model for filename.
     */
    protected function extractCode($model): string
    {
        // Try common code fields
        foreach (['invoice_number', 'code', 'invoice_number', 'number', 'payslip_number', 'invoice_number'] as $field) {
            if (isset($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        // Fallback to ID
        return 'id-'.($model->id ?? 'unknown');
    }

    /**
     * Generate multiple PDFs in batch (for bulk operations).
     *
     * @param array<array{document_type: string, model: mixed, extra_data?: array}> $items
     * @return array<string, string> Keyed by document type + code
     */
    public function generateBatch(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            try {
                $path = $this->generate(
                    $item['document_type'],
                    $item['model'],
                    $item['extra_data'] ?? []
                );
                $key = $item['document_type'].'_'.$this->extractCode($item['model']);
                $results[$key] = $path;
            } catch (Throwable $e) {
                Log::error('Batch PDF generation failed', [
                    'document_type' => $item['document_type'],
                    'model_id' => $item['model']->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Register a custom document type with its view and options.
     */
    public function registerDocumentType(
        string $type,
        string $view,
        string $paper = 'a4',
        string $orientation = 'portrait'
    ): void {
        $this->documentViews[$type] = $view;
        $this->paperSizes[$type] = $paper;
        $this->orientations[$type] = $orientation;
    }
}
