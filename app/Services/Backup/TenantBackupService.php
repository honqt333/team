<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\Tenant;
use App\Models\TenantBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Tenant backup service.
 *
 * Strategy: structured JSON dump per tenant, no mysqldump dependency.
 * Each table that has a `tenant_id` column is dumped to its own file
 * inside a single tar.gz-like archive, plus a manifest.json that
 * records the schema version, tenant id, and row counts.
 *
 * Why JSON instead of SQL dump:
 *  - No external binaries required (mysqldump/pg_dump), works on sqlite
 *    and mysql identically.
 *  - We can restore individual tables or the whole tenant without
 *    parsing SQL.
 *  - Cross-database safety — schema-specific syntax doesn't leak
 *    between environments.
 *
 * The pre-delete flow calls createBackup(type=pre_delete) BEFORE
 * TenantsController::destroy runs the forceDelete, so the data
 * remains recoverable if the deletion turns out to be a mistake.
 */
class TenantBackupService
{
    /**
     * Tenant-owned tables to dump. Order matters for restore:
     * children first, then parents, so FK violations don't bite during
     * the import phase.
     *
     * Keep this list aligned with the TenantScoped concern. If a new
     * model is added to the tenant scope, add it here as well.
     */
    protected const TABLES = [
        // children
        'work_order_items',
        'work_order_item_parts',
        'work_order_item_notes',
        'work_order_item_technicians',
        'work_order_photos',
        'work_order_attachments',
        'work_order_activities',
        'work_order_inspections',
        'work_order_damage_marks',
        'invoice_lines',
        'payments',
        'inventory_moves',
        'inventory_transfers',
        'inventory_transfer_items',
        'quote_lines',
        'quote_parts',
        'goods_received_notes',
        'grn_items',
        'purchase_invoices',
        'purchase_invoice_lines',
        'purchase_return_invoices',
        'purchase_return_invoice_lines',
        'purchase_orders',
        'purchase_order_items',
        'hr_attendances',
        'hr_employees',
        'hr_payroll_items',
        'hr_payroll_runs',
        'hr_other_payments',
        'hr_employee_documents',
        'hr_employee_contracts',
        'hr_leaves',
        'hr_shifts',
        'hr_employee_shifts',
        'hr_allowances_deductions',
        'company_transactions',
        'vehicle_mileage_logs',
        // parents
        'work_orders',
        'invoices',
        'quotes',
        'vehicles',
        'parts',
        'warehouses',
        'inventory_balances',
        'suppliers',
        'service_items',
        'services',
        'departments',
        'centers',
        'center_addresses',
        'center_working_hours',
        'center_sequences',
        'customers',
        'users',
        'vehicle_makes',
        'vehicle_models',
        'vehicle_colors',
        'nationalities',
        'inspection_templates',
        'inspection_items',
        'income_categories',
        // tenant root
        'tenants',
        'tenant_addresses',
        'tenant_zatca_settings',
        'tenant_tax_settings',
    ];

    /**
     * Run a backup synchronously and return the completed record.
     * Long-running: callers should queue this for big tenants.
     */
    public function createBackup(
        Tenant $tenant,
        string $name,
        string $type = TenantBackup::TYPE_MANUAL,
        ?int $createdByAdminId = null
    ): TenantBackup {
        $record = TenantBackup::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'type' => $type,
            'status' => TenantBackup::STATUS_PENDING,
            'disk' => 'local',
            'created_by_admin_id' => $createdByAdminId,
        ]);

        $record->update([
            'status' => TenantBackup::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            [$path, $manifest, $size, $checksum] = $this->dump($tenant, $record);

            $record->update([
                'status' => TenantBackup::STATUS_COMPLETED,
                'path' => $path,
                'size_bytes' => $size,
                'manifest' => $manifest,
                'checksum' => $checksum,
                'completed_at' => now(),
            ]);

            Log::info('tenant.backup.completed', [
                'tenant_id' => $tenant->id,
                'backup_id' => $record->id,
                'size' => $size,
                'rows' => array_sum(array_column($manifest, 'rows')),
            ]);
        } catch (Throwable $e) {
            $record->update([
                'status' => TenantBackup::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('tenant.backup.failed', [
                'tenant_id' => $tenant->id,
                'backup_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $record->fresh();
    }

    /**
     * Download a backup as a streamed response.
     */
    public function download(TenantBackup $backup): StreamedResponse
    {
        $disk = Storage::disk($backup->disk);
        $path = $backup->path;

        abort_unless($disk->exists($path), 404, 'Backup file not found on disk.');

        $fileName = sprintf(
            '%s-%s-%s.json.gz',
            $backup->tenant?->trade_name ?? 'tenant',
            $backup->tenant_id,
            $backup->created_at?->format('Ymd-His') ?? $backup->id
        );

        return response()->streamDownload(function () use ($disk, $path) {
            $stream = $disk->readStream($path);

            while (! feof($stream)) {
                echo fread($stream, 8192);
                flush();
            }
            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'application/gzip',
        ]);
    }

    /**
     * Delete a backup file from disk + the row.
     */
    public function delete(TenantBackup $backup): void
    {
        if ($backup->path && Storage::disk($backup->disk)->exists($backup->path)) {
            Storage::disk($backup->disk)->delete($backup->path);
        }
        $backup->delete();
    }

    /**
     * Build the JSON dump on disk and return [path, manifest, size, checksum].
     */
    protected function dump(Tenant $tenant, TenantBackup $record): array
    {
        $manifest = [];
        $payload = [
            'tenant' => $tenant->only(['id', 'name', 'trade_name', 'slug', 'status']),
            'created_at' => now()->toIso8601String(),
            'app_version' => config('app.version', 'dev'),
            'tables' => [],
        ];

        foreach (self::TABLES as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }
            $hasTenantId = $this->columnExists($table, 'tenant_id');

            $rows = $this->dumpTable($table, $tenant, $hasTenantId);

            $manifest[] = [
                'table' => $table,
                'rows' => count($rows),
                'has_tenant_id' => $hasTenantId,
            ];
            $payload['tables'][$table] = $rows;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($json === false) {
            throw new RuntimeException('Failed to encode backup JSON: '.json_last_error_msg());
        }

        $compressed = gzencode($json, 6);
        $checksum = hash('sha256', $compressed);
        $path = sprintf('tenants/%d/backups/%d-%s.json.gz', $tenant->id, $record->id, now()->format('Ymd-His'));

        Storage::disk($record->disk)->put($path, $compressed);

        return [$path, $manifest, strlen($compressed), $checksum];
    }

    /**
     * Dump a single table. We disable the global TenantScoped scope
     * because we're already filtering by tenant_id explicitly, and
     * some of these tables also have a center_scoped global scope.
     */
    protected function dumpTable(string $table, Tenant $tenant, bool $hasTenantId): array
    {
        $query = DB::table($table);

        if ($hasTenantId) {
            $query->where('tenant_id', $tenant->id);
        } elseif ($table === 'tenants') {
            $query->where('id', $tenant->id);
        } else {
            // No tenant_id column and not the tenants table — skip.
            // This should not happen for the entries in TABLES, but
            // defends against schema drift.
            return [];
        }

        // Chunk to avoid loading huge tables into memory at once.
        $rows = [];
        $query->orderBy('id')->chunk(500, function ($chunk) use (&$rows) {
            foreach ($chunk as $row) {
                $rows[] = (array) $row;
            }
        });

        return $rows;
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }
}
