<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Services\Backup\TenantBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class BackupController extends Controller
{
    public function __construct(protected TenantBackupService $backups) {}

    /**
     * List all backups across tenants. System admin only.
     */
    public function index(Request $request): Response
    {
        $query = TenantBackup::with(['tenant', 'createdByAdmin'])
            ->latest('created_at');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $backups = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => TenantBackup::count(),
            'completed' => TenantBackup::where('status', TenantBackup::STATUS_COMPLETED)->count(),
            'failed' => TenantBackup::where('status', TenantBackup::STATUS_FAILED)->count(),
            'total_size_mb' => round((float) TenantBackup::where('status', TenantBackup::STATUS_COMPLETED)->sum('size_bytes') / 1024 / 1024, 2),
        ];

        return Inertia::render('System/Backups/Index', [
            'backups' => $backups,
            'stats' => $stats,
            'tenants' => Tenant::orderBy('trade_name')->get(['id', 'name', 'trade_name']),
            'filters' => $request->only(['tenant_id', 'status', 'type']),
        ]);
    }

    /**
     * Trigger a new backup for a tenant.
     */
    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        $adminUser = Auth::guard('admin')->user();
        $adminId = $adminUser?->id;

        $name = $request->name ?: sprintf(
            'Manual backup by %s @ %s',
            $adminUser?->name ?? Auth::user()?->name ?? 'system',
            now()->format('Y-m-d H:i')
        );

        try {
            $backup = $this->backups->createBackup(
                $tenant,
                $name,
                TenantBackup::TYPE_MANUAL,
                $adminId
            );

            if ($adminId) {
                AdminActivityLog::create([
                    'admin_user_id' => $adminId,
                    'action' => 'backup_create',
                    'model_type' => Tenant::class,
                    'model_id' => $tenant->id,
                    'changes' => ['backup_id' => $backup->id, 'backup_name' => $backup->name],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } catch (Throwable $e) {
            return back()->with('error', 'فشل إنشاء النسخة الاحتياطية: '.$e->getMessage());
        }

        return back()->with('success', "تم إنشاء النسخة الاحتياطية ({$backup->humanSize()})");
    }

    /**
     * Download a backup archive.
     */
    public function download(Request $request, TenantBackup $backup)
    {
        if ($adminId = Auth::guard('admin')->id()) {
            AdminActivityLog::create([
                'admin_user_id' => $adminId,
                'action' => 'backup_download',
                'model_type' => Tenant::class,
                'model_id' => $backup->tenant_id,
                'changes' => ['backup_id' => $backup->id, 'backup_name' => $backup->name],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $this->backups->download($backup);
    }

    /**
     * Delete a backup.
     */
    public function destroy(Request $request, TenantBackup $backup): RedirectResponse
    {
        $tenantId = $backup->tenant_id;
        $backupName = $backup->name;

        $this->backups->delete($backup);

        if ($adminId = Auth::guard('admin')->id()) {
            AdminActivityLog::create([
                'admin_user_id' => $adminId,
                'action' => 'backup_delete',
                'model_type' => Tenant::class,
                'model_id' => $tenantId,
                'changes' => ['backup_name' => $backupName],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return back()->with('success', 'تم حذف النسخة الاحتياطية');
    }
}
