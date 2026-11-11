<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Models\HR\EmployeeDocument;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\User;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderPhoto;
use App\Services\PurchaseAttachmentService;
use App\Services\TenantStorageService;
use App\Support\TenancyContext;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantAttachmentsService
{
    public const SOURCE_WORK_ORDER_ATTACHMENT = 'work_order_attachment';

    public const SOURCE_WORK_ORDER_PHOTO = 'work_order_photo';

    public const SOURCE_EMPLOYEE_DOCUMENT = 'employee_document';

    public const SOURCE_PURCHASE_INVOICE = 'purchase_invoice';

    public const SOURCE_PURCHASE_RETURN_INVOICE = 'purchase_return_invoice';

    /** @var array<int, string> */
    public const SOURCES = [
        self::SOURCE_WORK_ORDER_ATTACHMENT,
        self::SOURCE_WORK_ORDER_PHOTO,
        self::SOURCE_EMPLOYEE_DOCUMENT,
        self::SOURCE_PURCHASE_INVOICE,
        self::SOURCE_PURCHASE_RETURN_INVOICE,
    ];

    public function __construct(
        private readonly PurchaseAttachmentService $purchaseAttachmentService
    ) {}

    /**
     * Build paginated attachments + category summary scoped strictly to tenant and current center.
     *
     * @param array<string, mixed> $filters
     * @return array{
     *     items: LengthAwarePaginator<int, array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     sources: array<int, string>
     * }
     */
    public function paginateForUser(
        User $user,
        array $filters = [],
        int $perPage = 15,
        int $page = 1,
        string $path = ''
    ): array {
        $tenantId = (int) $user->tenant_id;
        $centerId = $this->resolveCenterId($user);

        $sourceFilter = is_string($filters['source'] ?? null) ? trim($filters['source']) : '';
        $search = is_string($filters['search'] ?? null) ? trim($filters['search']) : '';

        $allItems = $this->collectTenantItems($tenantId, $centerId);

        $bySource = [];

        foreach (self::SOURCES as $src) {
            $bySource[$src] = 0;
        }

        foreach ($allItems as $item) {
            $bySource[$item['source']] = ($bySource[$item['source']] ?? 0) + 1;
        }

        $filtered = $allItems;

        if ($sourceFilter !== '' && in_array($sourceFilter, self::SOURCES, true)) {
            $filtered = $filtered->where('source', $sourceFilter)->values();
        }

        if ($search !== '') {
            $filtered = $filtered->filter(function (array $item) use ($search): bool {
                return mb_stripos((string) $item['file_name'], $search) !== false
                    || mb_stripos((string) $item['reference_label'], $search) !== false
                    || mb_stripos((string) ($item['uploaded_by'] ?? ''), $search) !== false;
            })->values();
        }

        $sorted = $filtered->sortByDesc('created_at_timestamp')->values();

        $total = $sorted->count();
        $totalMatchedBytes = (int) $sorted->sum('file_size');
        $currentPage = max(1, $page);
        $slice = $sorted->forPage($currentPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $path !== '' ? $path : request()->url(),
                'query' => array_filter([
                    'source' => $sourceFilter !== '' ? $sourceFilter : null,
                    'search' => $search !== '' ? $search : null,
                ], static fn ($value) => $value !== null),
            ]
        );

        return [
            'items' => $paginator,
            'summary' => [
                'total_count' => $allItems->count(),
                'matched_count' => $total,
                'matched_bytes' => $totalMatchedBytes,
                'by_source' => $bySource,
            ],
            'sources' => self::SOURCES,
        ];
    }

    /**
     * Safely stream a tenant/center-scoped attachment as a forced download.
     */
    public function downloadForUser(User $user, string $source, int $id): StreamedResponse
    {
        $resolved = $this->resolveScopedAttachment($user, $source, $id);

        if (! $resolved) {
            abort(404, __('system_settings.attachments.not_found'));
        }

        $diskName = $resolved['disk'];
        $storagePath = $resolved['storage_path'];

        if (! $storagePath || ! Storage::disk($diskName)->exists($storagePath)) {
            abort(404, __('system_settings.attachments.file_missing'));
        }

        $filename = $this->sanitizeFilename($resolved['file_name'], $storagePath);

        return Storage::disk($diskName)->download($storagePath, $filename, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Permanently remove the attachment (file + record or attachment column), log activity, and bust storage cache.
     */
    public function deleteForUser(User $user, string $source, int $id): void
    {
        $resolved = $this->resolveScopedAttachment($user, $source, $id);

        if (! $resolved) {
            abort(404, __('system_settings.attachments.not_found'));
        }

        $tenantId = (int) $user->tenant_id;
        $centerId = $this->resolveCenterId($user);
        $model = $resolved['model'];
        $diskName = $resolved['disk'];
        $storagePath = $resolved['storage_path'];
        $fileName = $resolved['file_name'];
        $fileSize = $resolved['file_size'];

        if ($model instanceof PurchaseInvoice || $model instanceof PurchaseReturnInvoice) {
            $this->purchaseAttachmentService->replace($model, null);
        } else {
            if ($storagePath !== null && $storagePath !== '') {
                Storage::disk($diskName)->delete($storagePath);
            }

            $model->delete();
        }

        $user->logActivity('system_attachment.deleted', [
            'source' => $source,
            'record_id' => $id,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'center_id' => $centerId,
        ]);

        TenantStorageService::clearCache($tenantId);
    }

    /**
     * Gather all attachment items scoped to the tenant and current center.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collectTenantItems(int $tenantId, ?int $centerId): Collection
    {
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');
        $items = collect();

        // 1. Work Order Attachments
        $woAttachments = WorkOrderAttachment::query()
            ->with(['workOrder:id,code,center_id', 'user:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereHas('workOrder', function ($query) use ($centerId): void {
                if ($centerId !== null) {
                    $query->where('center_id', $centerId);
                }
            })
            ->get();

        foreach ($woAttachments as $attachment) {
            $path = (string) ($attachment->path ?? '');
            $exists = $path !== '' && $publicDisk->exists($path);
            $size = (int) ($attachment->file_size ?: ($exists ? $publicDisk->size($path) : 0));
            $fileName = (string) ($attachment->file_name ?: basename($path));
            $workOrderCode = $attachment->workOrder->code ?? ('#'.$attachment->work_order_id);

            $items->push($this->formatRow(
                id: (int) $attachment->id,
                source: self::SOURCE_WORK_ORDER_ATTACHMENT,
                fileName: $fileName,
                extension: strtolower(pathinfo($fileName !== '' ? $fileName : $path, PATHINFO_EXTENSION)),
                fileSize: $size,
                exists: $exists,
                referenceLabel: (string) $workOrderCode,
                referenceUrl: $attachment->work_order_id ? route('work-orders.show', $attachment->work_order_id) : null,
                uploadedBy: $attachment->user?->name,
                createdAt: $attachment->created_at
            ));
        }

        // 2. Work Order Photos
        $woPhotos = WorkOrderPhoto::query()
            ->with('workOrder:id,code,center_id')
            ->where('tenant_id', $tenantId)
            ->when($centerId !== null, fn ($query) => $query->where('center_id', $centerId))
            ->get();

        foreach ($woPhotos as $photo) {
            $path = (string) ($photo->path ?? '');
            $exists = $path !== '' && $publicDisk->exists($path);
            $size = $exists ? (int) $publicDisk->size($path) : 0;
            $fileName = basename($path) ?: ($photo->caption ?: ('photo-'.$photo->id));
            $workOrderCode = $photo->workOrder->code ?? ('#'.$photo->work_order_id);

            $items->push($this->formatRow(
                id: (int) $photo->id,
                source: self::SOURCE_WORK_ORDER_PHOTO,
                fileName: (string) $fileName,
                extension: strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                fileSize: $size,
                exists: $exists,
                referenceLabel: (string) $workOrderCode,
                referenceUrl: $photo->work_order_id ? route('work-orders.show', $photo->work_order_id) : null,
                uploadedBy: null,
                createdAt: $photo->created_at
            ));
        }

        // 3. Employee Documents (private local disk with public fallback)
        $employeeDocs = EmployeeDocument::query()
            ->with(['employee:id,center_id,employee_number,name_ar,name_en'])
            ->where('tenant_id', $tenantId)
            ->whereHas('employee', function ($query) use ($centerId): void {
                if ($centerId !== null) {
                    $query->where(function ($sub) use ($centerId): void {
                        $sub->where('center_id', $centerId)->orWhereNull('center_id');
                    });
                }
            })
            ->get();

        foreach ($employeeDocs as $doc) {
            $path = (string) ($doc->file_path ?? '');
            $resolvedDisk = $this->resolveDiskForPath($path, ['local', 'public'], $localDisk, $publicDisk);
            $exists = $resolvedDisk !== null;
            $size = $exists ? (int) Storage::disk($resolvedDisk)->size($path) : 0;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $fileName = $this->buildDisplayFilename((string) ($doc->title ?? ''), $path);
            $employee = $doc->employee;
            $employeeLabel = $employee
                ? trim(($employee->display_name ?: $employee->name_ar ?: $employee->name_en ?: '').' '.($employee->employee_number ? "({$employee->employee_number})" : ''))
                : ('#'.$doc->employee_id);

            $items->push($this->formatRow(
                id: (int) $doc->id,
                source: self::SOURCE_EMPLOYEE_DOCUMENT,
                fileName: $fileName,
                extension: $ext,
                fileSize: $size,
                exists: $exists,
                referenceLabel: $employeeLabel,
                referenceUrl: $doc->employee_id ? route('app.hr.employees.show', $doc->employee_id) : null,
                uploadedBy: null,
                createdAt: $doc->created_at
            ));
        }

        // 4. Purchase Invoices with attachments
        $purchaseInvoices = PurchaseInvoice::query()
            ->select(['id', 'tenant_id', 'center_id', 'code', 'invoice_number', 'attachment_path', 'created_at'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->when($centerId !== null, fn ($query) => $query->where('center_id', $centerId))
            ->get();

        foreach ($purchaseInvoices as $invoice) {
            $path = (string) $invoice->attachment_path;
            $exists = $publicDisk->exists($path);
            $size = $exists ? (int) $publicDisk->size($path) : 0;
            $fileName = basename($path) ?: ($invoice->code ?: ('PI-'.$invoice->id));
            $refLabel = (string) ($invoice->code ?: $invoice->invoice_number ?: ('#'.$invoice->id));

            $items->push($this->formatRow(
                id: (int) $invoice->id,
                source: self::SOURCE_PURCHASE_INVOICE,
                fileName: $fileName,
                extension: strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                fileSize: $size,
                exists: $exists,
                referenceLabel: $refLabel,
                referenceUrl: route('app.invoices.purchases.show', $invoice->id),
                uploadedBy: null,
                createdAt: $invoice->created_at
            ));
        }

        // 5. Purchase Return Invoices with attachments
        $purchaseReturns = PurchaseReturnInvoice::query()
            ->select(['id', 'tenant_id', 'center_id', 'code', 'attachment_path', 'created_at'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '!=', '')
            ->when($centerId !== null, fn ($query) => $query->where('center_id', $centerId))
            ->get();

        foreach ($purchaseReturns as $returnInvoice) {
            $path = (string) $returnInvoice->attachment_path;
            $exists = $publicDisk->exists($path);
            $size = $exists ? (int) $publicDisk->size($path) : 0;
            $fileName = basename($path) ?: ($returnInvoice->code ?: ('PR-'.$returnInvoice->id));
            $refLabel = (string) ($returnInvoice->code ?: ('#'.$returnInvoice->id));

            $items->push($this->formatRow(
                id: (int) $returnInvoice->id,
                source: self::SOURCE_PURCHASE_RETURN_INVOICE,
                fileName: $fileName,
                extension: strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                fileSize: $size,
                exists: $exists,
                referenceLabel: $refLabel,
                referenceUrl: route('app.invoices.purchases.returns.show', $returnInvoice->id),
                uploadedBy: null,
                createdAt: $returnInvoice->created_at
            ));
        }

        return $items;
    }

    /**
     * Resolve a single scoped attachment record along with storage disk details.
     *
     * @return array{
     *     model: WorkOrderAttachment|WorkOrderPhoto|EmployeeDocument|PurchaseInvoice|PurchaseReturnInvoice,
     *     disk: string,
     *     storage_path: ?string,
     *     file_name: string,
     *     file_size: int
     * }|null
     */
    private function resolveScopedAttachment(User $user, string $source, int $id): ?array
    {
        if (! in_array($source, self::SOURCES, true)) {
            return null;
        }

        $tenantId = (int) $user->tenant_id;
        $centerId = $this->resolveCenterId($user);

        switch ($source) {
            case self::SOURCE_WORK_ORDER_ATTACHMENT:
                $model = WorkOrderAttachment::query()
                    ->where('tenant_id', $tenantId)
                    ->whereHas('workOrder', function ($query) use ($centerId): void {
                        if ($centerId !== null) {
                            $query->where('center_id', $centerId);
                        }
                    })
                    ->find($id);

                if (! $model) {
                    return null;
                }

                $path = (string) ($model->path ?? '');
                $disk = 'public';
                $size = (int) ($model->file_size ?: ($path !== '' && Storage::disk($disk)->exists($path) ? Storage::disk($disk)->size($path) : 0));

                return [
                    'model' => $model,
                    'disk' => $disk,
                    'storage_path' => $path !== '' ? $path : null,
                    'file_name' => (string) ($model->file_name ?: basename($path)),
                    'file_size' => $size,
                ];

            case self::SOURCE_WORK_ORDER_PHOTO:
                $model = WorkOrderPhoto::query()
                    ->where('tenant_id', $tenantId)
                    ->when($centerId !== null, fn ($query) => $query->where('center_id', $centerId))
                    ->find($id);

                if (! $model) {
                    return null;
                }

                $path = (string) ($model->path ?? '');
                $disk = 'public';
                $size = $path !== '' && Storage::disk($disk)->exists($path) ? (int) Storage::disk($disk)->size($path) : 0;

                return [
                    'model' => $model,
                    'disk' => $disk,
                    'storage_path' => $path !== '' ? $path : null,
                    'file_name' => basename($path) ?: ('photo-'.$model->id),
                    'file_size' => $size,
                ];

            case self::SOURCE_EMPLOYEE_DOCUMENT:
                $model = EmployeeDocument::query()
                    ->where('tenant_id', $tenantId)
                    ->whereHas('employee', function ($query) use ($centerId): void {
                        if ($centerId !== null) {
                            $query->where(function ($sub) use ($centerId): void {
                                $sub->where('center_id', $centerId)->orWhereNull('center_id');
                            });
                        }
                    })
                    ->find($id);

                if (! $model) {
                    return null;
                }

                $path = (string) ($model->file_path ?? '');
                $disk = $this->resolveDiskForPath($path, ['local', 'public'], Storage::disk('local'), Storage::disk('public')) ?? 'local';
                $size = $path !== '' && Storage::disk($disk)->exists($path) ? (int) Storage::disk($disk)->size($path) : 0;

                return [
                    'model' => $model,
                    'disk' => $disk,
                    'storage_path' => $path !== '' ? $path : null,
                    'file_name' => $this->buildDisplayFilename((string) ($model->title ?? ''), $path),
                    'file_size' => $size,
                ];

            case self::SOURCE_PURCHASE_INVOICE:
                $model = PurchaseInvoice::query()
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull('attachment_path')
                    ->where('attachment_path', '!=', '')
                    ->when($centerId !== null, fn ($query) => $query->where('center_id', $centerId))
                    ->find($id);

                if (! $model) {
                    return null;
                }

                $path = (string) $model->attachment_path;
                $disk = 'public';
                $size = Storage::disk($disk)->exists($path) ? (int) Storage::disk($disk)->size($path) : 0;

                return [
                    'model' => $model,
                    'disk' => $disk,
                    'storage_path' => $path,
                    'file_name' => basename($path),
                    'file_size' => $size,
                ];

            case self::SOURCE_PURCHASE_RETURN_INVOICE:
                $model = PurchaseReturnInvoice::query()
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull('attachment_path')
                    ->where('attachment_path', '!=', '')
                    ->when($centerId !== null, fn ($query) => $query->where('center_id', $centerId))
                    ->find($id);

                if (! $model) {
                    return null;
                }

                $path = (string) $model->attachment_path;
                $disk = 'public';
                $size = Storage::disk($disk)->exists($path) ? (int) Storage::disk($disk)->size($path) : 0;

                return [
                    'model' => $model,
                    'disk' => $disk,
                    'storage_path' => $path,
                    'file_name' => basename($path),
                    'file_size' => $size,
                ];

            default:
                return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRow(
        int $id,
        string $source,
        string $fileName,
        string $extension,
        int $fileSize,
        bool $exists,
        string $referenceLabel,
        ?string $referenceUrl,
        ?string $uploadedBy,
        mixed $createdAt
    ): array {
        $timestamp = $createdAt ? (int) $createdAt->getTimestamp() : 0;

        return [
            'uid' => "{$source}:{$id}",
            'id' => $id,
            'source' => $source,
            'file_name' => $fileName,
            'extension' => $extension,
            'file_size' => $fileSize,
            'exists' => $exists,
            'reference_label' => $referenceLabel,
            'reference_url' => $referenceUrl,
            'uploaded_by' => $uploadedBy,
            'created_at' => $createdAt?->toIso8601String(),
            'created_at_timestamp' => $timestamp,
            'download_url' => route('settings.attachments.download', ['source' => $source, 'id' => $id]),
        ];
    }

    private function resolveCenterId(User $user): ?int
    {
        $centerId = TenancyContext::centerId() ?? $user->current_center_id;

        return $centerId !== null ? (int) $centerId : null;
    }

    /**
     * @param array<int, string> $candidates
     */
    private function resolveDiskForPath(
        string $path,
        array $candidates,
        Filesystem $localDisk,
        Filesystem $publicDisk
    ): ?string {
        if ($path === '') {
            return null;
        }

        foreach ($candidates as $candidate) {
            $disk = $candidate === 'local' ? $localDisk : $publicDisk;

            if ($disk->exists($path)) {
                return $candidate;
            }
        }

        return null;
    }

    private function buildDisplayFilename(string $title, string $path): string
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        if ($title !== '') {
            if ($ext !== '' && ! str_ends_with(strtolower($title), '.'.strtolower($ext))) {
                return $title.'.'.$ext;
            }

            return $title;
        }

        return basename($path) ?: 'document';
    }

    private function sanitizeFilename(string $name, string $fallbackPath): string
    {
        $clean = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\]+/', '_', $name));

        return $clean !== '' ? $clean : (basename($fallbackPath) ?: 'attachment');
    }
}
