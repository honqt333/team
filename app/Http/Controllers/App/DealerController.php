<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DealerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Dealer::class);

        $user = $request->user();
        $tenantId = $user->tenant_id;

        $query = Dealer::query()
            ->where('tenant_id', $tenantId)
            ->search($request->input('search'))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->input('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->withCount(['vehicles', 'workOrders'])
            ->select('dealers.*')
            ->selectSub(
                Invoice::query()
                    ->withoutGlobalScope('center_scoped')
                    ->whereColumn('invoices.dealer_id', 'dealers.id')
                    ->where('invoices.is_paid_by_dealer', true)
                    ->whereNotIn('status', ['cancelled'])
                    ->where('type', 'invoice')
                    ->selectRaw('COALESCE(SUM(total_incl_tax - total_paid), 0)'),
                'outstanding_balance'
            );

        $sort = (string) $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sort, ['name', 'type', 'is_active', 'created_at', 'id'], true)) {
            $query->orderBy("dealers.{$sort}", $direction);
        } else {
            $query->latest('dealers.id');
        }

        $dealers = $query->paginate(20)->withQueryString();

        return Inertia::render('Dealers/Index', [
            'dealers' => $dealers,
            'types' => Dealer::TYPES,
            'filters' => array_merge(
                $request->only(['search', 'type', 'status']),
                ['sort' => $sort ?: null, 'direction' => $sort ? $direction : null]
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Dealer::class);

        $user = $request->user();
        $validated = $this->validateDealer($request);
        $validated['tenant_id'] = $user->tenant_id;
        $validated['center_id'] = $user->current_center_id;

        Dealer::create($validated);

        return redirect()->back()->with('success', __('messages.dealers.created'));
    }

    public function show(Dealer $dealer): Response
    {
        $this->authorize('view', $dealer);

        $dealer->load([
            'vehicles' => fn ($q) => $q->withoutGlobalScope('center_scoped')
                ->with(['customer:id,name,phone', 'make:id,name_ar,name_en', 'model:id,name_ar,name_en'])
                ->latest('id')
                ->limit(50),
            'workOrders' => fn ($q) => $q->withoutGlobalScope('center_scoped')
                ->with(['customer:id,name,phone', 'vehicle:id,plate_number'])
                ->latest('id')
                ->limit(50),
            'invoices' => fn ($q) => $q->withoutGlobalScope('center_scoped')
                ->with(['workOrder:id,code', 'customer:id,name'])
                ->latest('id')
                ->limit(50),
        ]);

        $dealer->workOrders->each->append(['total_paid', 'balance', 'bad_debt']);

        return Inertia::render('Dealers/Show', [
            'dealer' => $dealer,
            'types' => Dealer::TYPES,
            'outstandingBalance' => $dealer->getOutstandingBalance(),
        ]);
    }

    public function update(Request $request, Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $validated = $this->validateDealer($request);
        $dealer->update($validated);

        return redirect()->back()->with('success', __('messages.dealers.updated'));
    }

    public function toggleActive(Dealer $dealer): RedirectResponse
    {
        $this->authorize('update', $dealer);

        $dealer->update(['is_active' => ! $dealer->is_active]);

        return redirect()->back()->with('success', $dealer->is_active
            ? __('messages.dealers.activated')
            : __('messages.dealers.deactivated'));
    }

    public function destroy(Dealer $dealer): RedirectResponse
    {
        $this->authorize('delete', $dealer);

        $hasRelations = $dealer->workOrders()->exists() || $dealer->invoices()->exists();

        if ($hasRelations) {
            return redirect()->back()->with('error', __('messages.dealers.cannot_delete_has_relations'));
        }

        $dealer->delete();

        return redirect()->route('app.dealers.index')->with('success', __('messages.dealers.deleted'));
    }

    /**
     * Validate dealer payload uniformly across store and update.
     */
    protected function validateDealer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(Dealer::TYPES)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'building_number' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
