<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\Backup\TenantBackupService;
use App\Services\Billing\TenantSuspensionService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class TenantsController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): Response
    {
        $query = Tenant::query()
            ->withCount('centers', 'users')
            ->with(['latestSubscription.plan']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by plan
        if ($request->filled('plan_id')) {
            $query->whereHas('subscriptions', function ($q) use ($request) {
                $q->where('plan_id', $request->plan_id)
                    ->whereIn('status', ['active', 'trial', 'trialing']);
            });
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $tenants = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('System/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['status', 'search', 'plan_id']),
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    /**
     * Display the specified tenant with usage analytics.
     */
    public function show(Tenant $tenant): Response
    {
        $tenant->load([
            'centers',
            'users',
            // Subscriptions with their plan + invoices + payments so the
            // Vue tabs can render the full financial timeline for each
            // subscription without a follow-up request per row. Receipts
            // are loaded separately on the tenant root (bankTransferReceipts
            // prop) because the SubscriptionInvoice model no longer has a
            // `receipts()` relation — receipts live on BankTransferReceipt
            // and are scoped directly to the tenant.
            'subscriptions' => fn ($q) => $q
                ->orderByDesc('created_at')
                ->with([
                    'plan',
                    'invoices' => fn ($q) => $q->orderByDesc('issued_at'),
                    'invoices.payments' => fn ($q) => $q->orderByDesc('created_at'),
                ]),
        ]);

        // Get all center IDs for this tenant
        $centerIds = $tenant->centers->pluck('id');

        // Usage Analytics - bypass only center_scoped, keep SoftDeletes
        $analytics = [
            'customers' => Customer::withoutGlobalScope('center_scoped')->whereIn('center_id', $centerIds)->count(),
            'vehicles' => Vehicle::withoutGlobalScope('center_scoped')->whereIn('center_id', $centerIds)->count(),
            'work_orders' => WorkOrder::withoutGlobalScope('center_scoped')->whereIn('center_id', $centerIds)->count(),
            'invoices' => Invoice::withoutGlobalScope('center_scoped')->whereIn('center_id', $centerIds)->count(),
            'users' => $tenant->users->count(),
            'centers' => $tenant->centers->count(),
        ];

        // Calculate storage size (photos, documents)
        $storagePath = storage_path("app/public/tenants/{$tenant->id}");
        $storageSize = 0;

        if (is_dir($storagePath)) {
            $storageSize = $this->getDirectorySize($storagePath);
        }
        $analytics['storage_mb'] = round($storageSize / 1024 / 1024, 2);

        // Recent activity - bypass only center_scoped
        $recentWorkOrders = WorkOrder::withoutGlobalScope('center_scoped')
            ->whereIn('center_id', $centerIds)
            ->with(['vehicle.customer' => fn ($q) => $q->withoutGlobalScope('center_scoped')])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'code', 'status', 'vehicle_id', 'center_id', 'created_at']);

        // Bank transfer receipts — for the dedicated Receipts tab. Scoped
        // to the tenant so the admin sees the full review history without
        // re-querying on tab switch.
        $bankTransferReceipts = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->with(['subscription.plan', 'payment', 'reviewer'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Health Score — a 0-100 score that summarizes payment health,
        // subscription liveness, and customer loyalty. Returns both the
        // numeric score (drives the badge color) and a flag list (drives
        // the "why" tooltip in the UI).
        $healthScore = $this->computeHealthScore($tenant);

        // Activity log (best-effort) — if the AdminActivityLog model
        // exists, surface the last 25 entries. Wrap in try so a missing
        // table doesn't break the page.
        $activityLog = collect();

        try {
            $activityLog = AdminActivityLog::query()
                ->where('model_type', Tenant::class)
                ->where('model_id', $tenant->id)
                ->orderByDesc('created_at')
                ->limit(25)
                ->get();
        } catch (Throwable $e) {
            // table not migrated yet — log and continue
            Log::debug('AdminActivityLog not available for tenant timeline', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }

        return Inertia::render('System/Tenants/Show', [
            'tenant' => $tenant,
            'analytics' => $analytics,
            'recentWorkOrders' => $recentWorkOrders,
            'bankTransferReceipts' => $bankTransferReceipts,
            'healthScore' => $healthScore,
            'activityLog' => $activityLog,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(['id', 'name_ar', 'name_en', 'price_monthly', 'price_yearly', 'trial_days']),
        ]);
    }

    /**
     * Compute a 0-100 health score for a tenant.
     *
     * Signals (each subtracts from a base of 100):
     *   -20  has at least one overdue invoice
     *   -10  no active subscription
     *   -10  current subscription expires within 30 days
     *   -5   any rejected bank transfer receipt in last 90 days
     *
     * Signals (each adds):
     *   +5   has 3+ approved receipts (loyal, established)
     *   +5   subscription renewed at least once
     *
     * Score is clamped to [0, 100]. The `flags` list is used by the UI
     * to render a "why this score" tooltip — every adjustment records
     * the reason so the admin can act on it.
     */
    private function computeHealthScore(Tenant $tenant): array
    {
        $score = 100;
        $flags = [];

        $subscriptionInvoices = SubscriptionInvoice::where('tenant_id', $tenant->id);
        $overdueExists = (clone $subscriptionInvoices)->where('status', 'overdue')->exists();

        if ($overdueExists) {
            $score -= 20;
            $flags[] = ['type' => 'danger', 'key' => 'overdue_invoice'];
        }

        $activeSub = $tenant->subscriptions()
            ->where('status', 'active')
            ->orderByDesc('ends_at')
            ->first();

        if (! $activeSub) {
            $score -= 10;
            $flags[] = ['type' => 'warning', 'key' => 'no_active_subscription'];
        } elseif ($activeSub->ends_at && $activeSub->ends_at->isFuture() && now()->diffInDays($activeSub->ends_at) <= 29) {
            $score -= 10;
            $flags[] = ['type' => 'warning', 'key' => 'expiring_within_30_days'];
        }

        $rejectedRecently = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->where('status', 'rejected')
            ->where('created_at', '>=', now()->subDays(90))
            ->exists();

        if ($rejectedRecently) {
            $score -= 5;
            $flags[] = ['type' => 'warning', 'key' => 'rejected_receipt_recent'];
        }

        $approvedCount = BankTransferReceipt::where('tenant_id', $tenant->id)
            ->where('status', 'approved')
            ->count();

        if ($approvedCount >= 3) {
            $score += 5;
            $flags[] = ['type' => 'positive', 'key' => 'loyal_customer'];
        }

        $renewedAtLeastOnce = $tenant->subscriptions()->count() > 1;

        if ($renewedAtLeastOnce) {
            $score += 5;
            $flags[] = ['type' => 'positive', 'key' => 'has_renewed'];
        }

        return [
            'score' => max(0, min(100, $score)),
            'flags' => $flags,
        ];
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(Request $request, Tenant $tenant)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $request->reason,
        ]);

        return back()->with('success', 'تم تعليق المستأجر بنجاح');
    }

    /**
     * Activate a tenant.
     */
    public function activate(Tenant $tenant)
    {
        $tenant->update([
            'status' => 'trial',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        // Verify all unverified users' emails of this tenant upon activation
        $tenant->users()->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'تم تفعيل المستأجر وتأكيد البريد الإلكتروني لمستخدميه بنجاح');
    }

    /**
     * Extend trial period.
     */
    public function extendTrial(Request $request, Tenant $tenant)
    {
        $request->validate([
            'days' => 'required|integer|min:-90|max:90',
        ]);

        $newEndDate = $tenant->trial_ends_at
            ? $tenant->trial_ends_at->addDays($request->days)
            : now()->addDays($request->days);

        $tenant->update([
            'trial_ends_at' => $newEndDate,
            'status' => 'trial',
        ]);

        // Sync with the database subscription
        $tenant->subscriptions()
            ->whereIn('status', ['trial', 'trialing', 'expired', 'suspended'])
            ->update([
                'status' => 'trialing',
                'ends_at' => $newEndDate,
                'trial_ends_at' => $newEndDate,
            ]);

        return back()->with('success', "تم تمديد الفترة التجريبية {$request->days} يوم");
    }

    /**
     * Hard delete a tenant and all related data.
     */
    public function destroy(Request $request, Tenant $tenant)
    {
        $request->validate([
            'confirmation' => 'required|string',
        ]);

        // Verify confirmation matches tenant name
        if ($request->confirmation !== $tenant->name) {
            return back()->with('error', 'اسم المستأجر غير متطابق');
        }

        // Pre-delete safety net: capture a backup BEFORE the forceDelete
        // so the data is recoverable if the deletion turns out to be a
        // mistake. We do this outside the destroy transaction because
        // the backup must commit independently of the delete. If the
        // backup fails, we abort the whole operation — better to fail
        // safe than to lose data.
        try {
            $backup = app(TenantBackupService::class)->createBackup(
                $tenant,
                sprintf('Pre-delete backup by %s @ %s', $request->user()?->name ?? 'system', now()->format('Y-m-d H:i')),
                TenantBackup::TYPE_PRE_DELETE,
                $request->user()?->id
            );
        } catch (Throwable $e) {
            Log::error('Pre-delete backup failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'فشل إنشاء نسخة احتياطية قبل الحذف — تم إيقاف العملية. الخطأ: '.$e->getMessage()
            );
        }

        DB::transaction(function () use ($tenant) {
            $centerIds = $tenant->centers()->pluck('id');

            // 1. Delete customers first (restrictOnDelete on center_id and tenant_id)
            Customer::withoutGlobalScopes()
                ->whereIn('center_id', $centerIds)
                ->forceDelete();

            // 2. Nullify user center references before deleting centers
            User::where('tenant_id', $tenant->id)
                ->update(['current_center_id' => null]);

            // 3. Delete centers (cascade handles: addresses, working_hours, departments,
            //    services, quotes, invoices, work_orders, sequences, etc.)
            foreach ($tenant->centers as $center) {
                $center->forceDelete();
            }

            // 4. Delete users (restrictOnDelete on tenant_id)
            $tenant->users()->forceDelete();

            // 5. Finally delete the tenant
            $tenant->forceDelete();
        });

        return redirect()->route('system.tenants.index')->with('success', 'تم حذف المستأجر وجميع بياناته نهائياً');
    }

    /**
     * Calculate directory size recursively.
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Phase 7.6 — admin override for the dunning opt-out.
     * The opt-out is a tenant-controlled flag by default
     * (the tenant can flip it from the billing preferences
     * page). Admins need an escape hatch for: legal disputes
     * where the tenant has asked in writing to be opted back
     * in, account recovery flows, or compliance audits.
     *
     * We log the change so the audit trail captures "who
     * flipped this and when" — important when an admin
     * overrides a privacy-related preference.
     */
    public function updateDunningOptOut(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'dunning_opted_out' => 'required|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $tenant->update(['dunning_opted_out' => $validated['dunning_opted_out']]);

        // Log via the central activity logger (falls back to
        // Log facade if the helper isn't available). We record
        // both the new value and the admin's reason so the
        // audit trail is complete.
        if (function_exists('activity')) {
            activity()
                ->performedOn($tenant)
                ->causedBy($request->user())
                ->withProperties([
                    'dunning_opted_out' => $validated['dunning_opted_out'],
                    'reason' => $validated['reason'] ?? null,
                ])
                ->log('dunning_opted_out_changed');
        } else {
            Log::info('admin.dunning_opted_out_changed', [
                'tenant_id' => $tenant->id,
                'admin_id' => $request->user()?->id,
                'dunning_opted_out' => $validated['dunning_opted_out'],
                'reason' => $validated['reason'] ?? null,
            ]);
        }

        return back()->with('success', 'تم تحديث خيار التذكير للمستأجر');
    }

    /**
     * Phase 7.7 — admin override for an auto-suspension. The
     * dunning system can auto-suspend a tenant when tier_4
     * reminders go unpaid past the grace window. Admins
     * sometimes need to lift that suspension (e.g. the tenant
     * promised payment in 24h, or it's a VIP, or the system
     * mis-classified their case). This endpoint clears BOTH
     * `suspended_at` and `auto_suspended_at` and the reason
     * — the next dunning run treats them as a fresh cycle.
     *
     * We log the action so the audit trail records "admin X
     * lifted auto-suspend for tenant Y at Z" — important when
     * disputes come up later about why the system didn't
     * auto-suspend them again on the next cycle.
     */
    public function liftAutoSuspend(Request $request, Tenant $tenant)
    {
        $wasAutoSuspended = $tenant->auto_suspended_at !== null
            && $tenant->suspension_reason === TenantSuspensionService::REASON;

        if (! $wasAutoSuspended) {
            return back()->with('error', 'الحساب ليس معلّقاً تلقائياً — لا حاجة للإجراء');
        }

        $tenant->forceFill([
            'suspended_at' => null,
            'auto_suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        if (function_exists('activity')) {
            activity()
                ->performedOn($tenant)
                ->causedBy($request->user())
                ->log('auto_suspension_lifted');
        } else {
            Log::info('admin.auto_suspension_lifted', [
                'tenant_id' => $tenant->id,
                'admin_id' => $request->user()?->id,
            ]);
        }

        return back()->with('success', 'تم رفع التعليق التلقائي عن المستأجر');
    }
}
