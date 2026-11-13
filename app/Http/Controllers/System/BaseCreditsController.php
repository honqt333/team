<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\System\SystemAuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

abstract class BaseCreditsController extends Controller
{
    protected SystemAuditService $audit;

    public function __construct(
        ?SystemAuditService $audit = null
    ) {
        $this->audit = $audit ?? app(SystemAuditService::class);
    }

    abstract protected function channelKey(): string;

    /** @return class-string<Model> */
    abstract protected function packageModel(): string;

    /** @return class-string<Model> */
    abstract protected function balanceModel(): string;

    /** @return class-string<Model> */
    abstract protected function purchaseModel(): string;

    /** @return class-string<Model> */
    abstract protected function usageLogModel(): string;

    abstract protected function inertiaPage(): string;

    protected function extraStats(Collection $packages): array
    {
        return [];
    }

    public function index(Request $request): Response
    {
        $tab = $request->query('tab', 'packages');

        if (! in_array($tab, ['packages', 'balances', 'purchases', 'usage'], true)) {
            $tab = 'packages';
        }

        $packageModel = $this->packageModel();
        $balanceModel = $this->balanceModel();
        $purchaseModel = $this->purchaseModel();
        $usageLogModel = $this->usageLogModel();

        $packages = $packageModel::orderBy('sort_order')->get();

        $balances = ['data' => [], 'total' => 0, 'links' => []];

        if ($tab === 'balances') {
            $balancesQuery = $balanceModel::with('tenant');

            if ($request->search) {
                $search = $request->search;
                $balancesQuery->whereHas('tenant', fn ($q) => $q->where('trade_name', 'like', "%{$search}%"));
            }

            $balances = $balancesQuery->orderByDesc('balance')->paginate(20, ['*'], 'page')->withQueryString();
        }

        $purchases = ['data' => [], 'total' => 0, 'links' => []];

        if ($tab === 'purchases') {
            $purchasesQuery = $purchaseModel::with(['tenant', 'package']);

            if ($request->search) {
                $search = $request->search;
                $purchasesQuery->where(function ($q) use ($search) {
                    $q->whereHas('tenant', fn ($t) => $t->where('trade_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))
                        ->orWhere('payment_reference', 'like', "%{$search}%");
                });
            }

            if ($request->status && $request->status !== 'all') {
                $purchasesQuery->where('status', $request->status);
            }

            if ($request->gateway && $request->gateway !== 'all') {
                $purchasesQuery->where('payment_gateway', $request->gateway);
            }

            $purchases = $purchasesQuery->orderBy('created_at', 'desc')->paginate(20, ['*'], 'page')->withQueryString();
        }

        $logs = ['data' => [], 'total' => 0, 'links' => []];

        if ($tab === 'usage') {
            $usageQuery = $usageLogModel::with(['tenant', 'center']);

            if ($request->status) {
                $usageQuery->where('status', $request->status);
            }

            $logs = $usageQuery->latest()->paginate(50, ['*'], 'page')->withQueryString();
        }

        $stats = array_merge([
            'total_purchased' => $balanceModel::sum('total_purchased'),
            'total_used' => $balanceModel::sum('total_used'),
            'total_balance' => $balanceModel::sum('balance'),
            'total_revenue' => (float) $purchaseModel::where('status', 'paid')->sum('amount'),
            'total_credits_sold' => (int) $purchaseModel::where('status', 'paid')->sum('credits'),
            'delivered' => $usageLogModel::where('status', 'delivered')->count(),
            'failed' => $usageLogModel::where('status', 'failed')->count(),
            'packages_count' => $packages->count(),
        ], $this->extraStats($packages));

        return Inertia::render($this->inertiaPage(), [
            'tab' => $tab,
            'packages' => $packages,
            'balances' => $balances,
            'purchases' => $purchases,
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'gateway']),
        ]);
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $validated = $this->validatePackage($request);
        $packageModel = $this->packageModel();
        $package = $packageModel::create($validated);

        $this->audit->log($this->channelKey().'_package_create', $packageModel, $package->id, [
            'name' => $package->name,
            'credits' => $package->credits,
            'price' => $package->price,
        ], $request);

        return back()->with('success', __('messages.system.credits.created_success'));
    }

    protected function performUpdatePackage(Request $request, Model $package): RedirectResponse
    {
        $validated = $this->validatePackage($request);
        $package->update($validated);

        $this->audit->log($this->channelKey().'_package_update', $package::class, $package->id, [
            'name' => $package->name,
            'credits' => $package->credits,
            'price' => $package->price,
        ], $request);

        return back()->with('success', __('messages.system.credits.updated_success'));
    }

    protected function performDestroyPackage(Model $package): RedirectResponse
    {
        $packageId = $package->id;
        $packageName = $package->name;
        $packageClass = $package::class;

        $package->delete();

        $this->audit->log($this->channelKey().'_package_delete', $packageClass, $packageId, [
            'name' => $packageName,
        ]);

        return back()->with('success', __('messages.system.credits.deleted_success'));
    }

    public function addCredits(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'credits' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $balanceModel = $this->balanceModel();
        $purchaseModel = $this->purchaseModel();

        DB::transaction(function () use ($validated, $balanceModel, $purchaseModel) {
            $balance = $balanceModel::getOrCreate($validated['tenant_id']);
            $balance->addCredits($validated['credits']);

            $purchaseModel::create([
                'tenant_id' => $validated['tenant_id'],
                'credits' => $validated['credits'],
                'amount' => 0,
                'payment_gateway' => 'manual',
                'payment_reference' => 'MANUAL-'.time(),
                'status' => 'paid',
                'payment_details' => ['reason' => $validated['reason'] ?? 'إضافة يدوية'],
            ]);
        });

        $this->audit->log($this->channelKey().'_credits_add_manual', $balanceModel, $validated['tenant_id'], [
            'tenant_id' => $validated['tenant_id'],
            'credits' => $validated['credits'],
            'reason' => $validated['reason'] ?? null,
        ], $request);

        return back()->with('success', __('messages.system.credits.added_success', ['credits' => $validated['credits']]));
    }

    private function validatePackage(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }
}
