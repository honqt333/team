<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Exceptions\InvalidQuoteSignatureException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\PortalLocale;
use App\Models\Appointment;
use App\Models\CenterAddress;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuotePart;
use App\Models\Tenant;
use App\Models\TenantAddress;
use App\Models\TenantWebsite;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\NotificationService;
use App\Services\QuoteSignatureStorage;
use App\Support\CountryCurrencyRegistry;
use BackedEnum;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Authenticated customer-portal pages. Every method
 * assumes the EnsureCustomerPortalSession middleware has
 * already:
 *   - resolved the {tenant} slug
 *   - validated the session is alive and matches the
 *     tenant
 *   - loaded the Customer row onto `portal_customer`
 *
 * The controller NEVER trusts a customer_id from the
 * request body or query — it always reads the customer
 * from $request->attributes->get('portal_customer').
 *
 * Data isolation
 * --------------
 * Every query below filters by `customer_id =
 * $customer->id` AND `tenant_id = $customer->tenant_id`.
 * The second filter is defense-in-depth: even if a bug
 * let a customer_id from another tenant slip in, the
 * tenant_id check would still prevent the leak.
 */
class CustomerPortalController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        // Vehicles — light projection with make/model relations
        // and Saudi plate data.
        $vehicles = Vehicle::query()
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->with([
                'make:id,name_ar,name_en,logo_path',
                'model:id,name_ar,name_en',
            ])
            ->orderByDesc('created_at')
            ->get(['id', 'plate_number', 'make_id', 'model_id', 'make_other', 'model_other', 'year', 'color', 'odometer']);

        // Upcoming + in-progress appointments.
        $upcomingAppointments = Appointment::query()
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
            ])
            ->where(function ($q) {
                $q->where('scheduled_at', '>=', now())
                    ->orWhere('status', Appointment::STATUS_CHECKED_IN);
            })
            ->with([
                'center:id,name,name_ar,name_en',
                'serviceBay:id,name',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'services:id,name_ar,name_en',
            ])
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get(['id', 'scheduled_at', 'estimated_end_at', 'status', 'vehicle_id', 'center_id', 'service_bay_id', 'notes']);

        // Recent work orders — last 5 regardless of status.
        $recentWorkOrders = WorkOrder::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->with([
                'center:id,name,name_ar,name_en',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'items:id,work_order_id,status',
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'code', 'status', 'created_at', 'entry_date', 'expected_end_date', 'vehicle_id', 'center_id', 'total_incl_tax', 'currency_code']);

        // Pending quotes awaiting customer decision.
        $pendingQuotes = Quote::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT])
            ->with([
                'center:id,name,name_ar,name_en',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'lines:id,quote_id',
                'parts:id,quote_id',
            ])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->filter(fn (Quote $q) => ! $q->isExpired() && ! $this->isQuoteEmpty($q))
            ->take(3)
            ->values();

        // Recent valid/reported invoices (never expose drafts or cancelled).
        $recentInvoices = Invoice::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with([
                'center:id,name,name_ar,name_en',
                'workOrder:id,code,vehicle_id',
                'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                'workOrder.vehicle.make:id,name_ar,name_en',
                'workOrder.vehicle.model:id,name_ar,name_en',
            ])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $badges = $this->portalBadges($customer, $tenant, $currency);

        return Inertia::render('Public/Portal/Dashboard', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone ?: $customer->whatsapp,
            ],
            'summary' => $badges,
            'portalCapabilities' => $this->portalCapabilities(),
            'vehicles' => $vehicles->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'make' => $v->display_make ?? $v->make?->name,
                'model' => $v->display_model ?? $v->model?->name,
                'year' => $v->year,
                'color' => $v->color,
                'odometer' => $v->odometer,
                'logo_url' => $v->make?->logo_url,
            ])->values(),
            'upcomingAppointments' => $upcomingAppointments->map(fn (Appointment $a) => [
                'id' => $a->id,
                'scheduled_at' => $a->scheduled_at?->toIso8601String(),
                'estimated_end_at' => $a->estimated_end_at?->toIso8601String(),
                'status' => $a->status,
                'notes' => $a->notes,
                'vehicle_id' => $a->vehicle_id,
                'vehicle_plate' => $a->vehicle?->plate_number,
                'vehicle_label' => $this->vehicleLabel($a->vehicle),
                'center_id' => $a->center_id,
                'service_bay_id' => $a->service_bay_id,
                'center_name' => $a->center?->display_name,
                'service_bay_name' => $a->serviceBay?->name,
                'services' => $a->services->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'name' => (string) $s->name,
                ])->values()->all(),
            ])->values(),
            'recentWorkOrders' => $recentWorkOrders->map(fn (WorkOrder $w) => $this->mapWorkOrderSummary($w, $currency))->values(),
            'pendingQuotes' => $pendingQuotes->map(fn (Quote $q) => $this->mapQuoteSummary($q, $currency))->values(),
            'recentInvoices' => $recentInvoices->map(fn (Invoice $inv) => $this->mapInvoiceSummary($inv, $currency))->values(),
        ]);
    }

    /**
     * Vehicles & Work Orders index page.
     */
    public function workOrders(Request $request): Response
    {
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        $vehicles = Vehicle::query()
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->with([
                'make:id,name_ar,name_en,logo_path',
                'model:id,name_ar,name_en',
            ])
            ->orderByDesc('created_at')
            ->get(['id', 'plate_number', 'make_id', 'model_id', 'make_other', 'model_other', 'year', 'color', 'odometer']);

        $workOrders = WorkOrder::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->where('status', '!=', WorkOrder::STATUS_DRAFT)
            ->with([
                'center:id,name,name_ar,name_en',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,color',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'items:id,work_order_id,title,status',
            ])
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $badges = $this->portalBadges($customer, $tenant, $currency);

        return Inertia::render('Public/Portal/WorkOrders/Index', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'vehicles' => $vehicles->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'make' => $v->display_make ?? $v->make?->name,
                'model' => $v->display_model ?? $v->model?->name,
                'year' => $v->year,
                'color' => $v->color,
                'odometer' => $v->odometer,
                'logo_url' => $v->make?->logo_url,
                'label' => $this->vehicleLabel($v),
            ])->values(),
            'workOrders' => $workOrders->map(fn (WorkOrder $w) => $this->mapWorkOrderSummary($w, $currency))->values(),
        ]);
    }

    /**
     * Detailed live work order tracking page.
     */
    public function showWorkOrder(Request $request): Response
    {
        $id = (string) $request->route('id');
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        $workOrder = WorkOrder::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereKey($id)
            ->with([
                'center:id,name,name_ar,name_en,phone',
                'center.address:id,center_id,address_line,district,city',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,color,odometer',
                'vehicle.make:id,name_ar,name_en,logo_path',
                'vehicle.model:id,name_ar,name_en',
                'items',
                'parts',
            ])
            ->first();

        if ($workOrder === null) {
            abort(404);
        }

        $activeItems = [];

        foreach ($workOrder->items as $item) {
            if ($item instanceof WorkOrderItem && $item->status !== WorkOrderItem::STATUS_CANCELLED) {
                $activeItems[] = $item;
            }
        }

        $activeItemIds = array_map(static fn (WorkOrderItem $item): int => (int) $item->id, $activeItems);

        $visibleParts = [];

        foreach ($workOrder->parts as $part) {
            if (! $part instanceof WorkOrderItemPart) {
                continue;
            }

            if (in_array($part->status, [WorkOrderItemPart::STATUS_CANCELLED, WorkOrderItemPart::STATUS_REVERSED], true)) {
                continue;
            }

            if ($part->hide_on_print) {
                continue;
            }

            if ($part->work_order_item_id !== null && ! in_array($part->work_order_item_id, $activeItemIds, true)) {
                continue;
            }

            $visibleParts[] = $part;
        }

        $linkedInvoice = Invoice::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->where('work_order_id', $workOrder->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->latest('id')
            ->first();

        $badges = $this->portalBadges($customer, $tenant, $currency);
        $summary = $this->mapWorkOrderSummary($workOrder, $currency);

        return Inertia::render('Public/Portal/WorkOrders/Show', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'workOrder' => array_merge($summary, [
                'odometer' => $workOrder->odometer ?? $workOrder->mileage ?? $workOrder->vehicle?->odometer,
                'fuel_level' => $workOrder->fuel_level,
                'customer_complaint' => $workOrder->customer_complaint,
                'initial_assessment' => $workOrder->initial_assessment,
                'notes' => $workOrder->notes,
                'hold_reason' => $workOrder->hold_reason,
                'opened_at' => $workOrder->opened_at?->toIso8601String() ?? $workOrder->created_at?->toIso8601String(),
                'closed_at' => $workOrder->closed_at?->toIso8601String(),
                'total_excl_tax' => (float) ($workOrder->total_excl_tax ?? 0),
                'total_tax' => (float) ($workOrder->total_tax ?? 0),
                'total_incl_tax' => (float) ($workOrder->total_incl_tax ?: $workOrder->total),
                'tax_enabled' => (bool) ($workOrder->tax_enabled_snapshot ?? false),
                'center_phone' => $workOrder->center?->phone,
                'center_address' => $this->formatAddress($workOrder->center?->address),
                'items' => array_map(static fn (WorkOrderItem $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'qty' => (float) $item->qty,
                    'unit_price' => (float) ($item->final_unit_price ?: $item->unit_price),
                    'total' => (float) ($item->getAttribute('line_total_incl_tax') ?: $item->line_total ?: $item->total),
                    'status' => $item->status,
                    'is_warranty' => (bool) $item->is_warranty,
                    'completed_at' => $item->completed_at?->toIso8601String(),
                ], $activeItems),
                'parts' => array_map(static fn (WorkOrderItemPart $part): array => [
                    'id' => $part->id,
                    'name' => $part->name,
                    'part_number' => $part->part_number,
                    'qty' => (float) $part->qty,
                    'unit_price' => (float) $part->unit_price,
                    'total' => (float) ($part->grand_total ?: $part->total),
                    'source' => $part->source,
                ], $visibleParts),
                'linked_invoice' => $linkedInvoice ? [
                    'id' => $linkedInvoice->id,
                    'invoice_number' => $linkedInvoice->invoice_number,
                    'payment_status' => $linkedInvoice->payment_status,
                    'total_incl_tax' => (float) $linkedInvoice->total_incl_tax,
                    'balance' => (float) $linkedInvoice->balance,
                ] : null,
            ]),
        ]);
    }

    /**
     * Quotes list page inside the portal.
     */
    public function quotes(Request $request): Response
    {
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        $quotes = Quote::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->with([
                'center:id,name,name_ar,name_en',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,color',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'lines:id,quote_id',
                'parts:id,quote_id',
            ])
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $badges = $this->portalBadges($customer, $tenant, $currency);

        return Inertia::render('Public/Portal/Quotes/Index', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'quotes' => $quotes->map(fn (Quote $q) => $this->mapQuoteSummary($q, $currency))->values(),
        ]);
    }

    /**
     * Detailed quote review & approval page inside the portal.
     */
    public function showQuote(Request $request): Response
    {
        $id = (string) $request->route('id');
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        $quote = Quote::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereKey($id)
            ->with([
                'center:id,name,name_ar,name_en,phone,vat_number',
                'center.address:id,center_id,address_line,district,city',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,color',
                'vehicle.make:id,name_ar,name_en,logo_path',
                'vehicle.model:id,name_ar,name_en',
                'lines',
                'parts',
            ])
            ->first();

        if ($quote === null) {
            abort(404);
        }

        $badges = $this->portalBadges($customer, $tenant, $currency);
        $summary = $this->mapQuoteSummary($quote, $currency);

        return Inertia::render('Public/Portal/Quotes/Show', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'quote' => array_merge($summary, [
                'odometer' => $quote->odometer,
                'notes' => $quote->notes,
                'customer_complaint' => $quote->customer_complaint,
                'initial_assessment' => $quote->initial_assessment,
                'rejection_reason' => $quote->rejection_reason,
                'subtotal' => (float) ($quote->subtotal ?? 0),
                'total_discount' => (float) ($quote->total_discount ?? 0),
                'total_excl_tax' => (float) ($quote->total_excl_tax ?? 0),
                'total_tax' => (float) ($quote->total_tax ?? 0),
                'total_incl_tax' => (float) ($quote->total_incl_tax ?: $quote->total ?: $quote->subtotal),
                'tax_enabled_snapshot' => (bool) ($quote->tax_enabled_snapshot ?? false),
                'tax_rate_snapshot' => (float) ($quote->tax_rate_snapshot ?? 15),
                'signature_url' => $quote->signature_url,
                'quote_terms' => $tenant->quote_terms,
                'center_phone' => $quote->center?->phone,
                'center_address' => $this->formatAddress($quote->center?->address),
                'lines' => array_values(array_map(static function ($line): array {
                    /** @var QuoteLine $line */
                    return [
                        'id' => $line->id,
                        'title' => $line->title ?: $line->description,
                        'description' => $line->description,
                        'qty' => (float) $line->qty,
                        'unit_price' => (float) $line->unit_price,
                        'discount_amount' => (float) ($line->discount_amount ?? 0),
                        'tax_amount' => (float) ($line->tax_amount ?? 0),
                        'line_total' => (float) ($line->line_total ?: ((float) $line->unit_price * (float) $line->qty)),
                    ];
                }, $quote->lines->all())),
                'parts' => array_values(array_map(static function ($part): array {
                    /** @var QuotePart $part */
                    return [
                        'id' => $part->id,
                        'name' => $part->name,
                        'part_number' => $part->part_number,
                        'qty' => (float) $part->qty,
                        'unit_price' => (float) $part->unit_price,
                        'discount' => (float) ($part->discount ?? 0),
                        'tax_amount' => (float) ($part->tax_amount ?? 0),
                        'total' => (float) ($part->total_incl_tax ?: $part->total ?: ((float) $part->unit_price * (float) $part->qty)),
                    ];
                }, $quote->parts->all())),
            ]),
        ]);
    }

    /**
     * Approve a quote from inside the customer portal.
     */
    public function approveQuote(Request $request): RedirectResponse
    {
        $id = (string) $request->route('id');
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);

        return DB::transaction(function () use ($request, $id, $customer, $tenant) {
            $quote = Quote::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('customer_id', $customer->id)
                ->where('tenant_id', $tenant->id)
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($quote === null) {
                abort(404);
            }

            if (! in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT], true)) {
                return back()->with('error', __('quotes.public_cannot_approve'));
            }

            if ($quote->isExpired()) {
                return back()->with('error', __('quotes.public_expired'));
            }

            if ($this->isQuoteEmpty($quote)) {
                return back()->with('error', __('quotes.empty_decision'));
            }

            $signaturePath = null;

            if ($request->filled('signature')) {
                try {
                    $signaturePath = app(QuoteSignatureStorage::class)
                        ->store((string) $request->input('signature'), $quote);
                } catch (InvalidQuoteSignatureException) {
                    return back()
                        ->withInput()
                        ->withErrors(['signature' => __('quotes.signature_invalid')]);
                }
            }

            $quote->update([
                'status' => Quote::STATUS_APPROVED,
                'approved_at' => now(),
                'signature_path' => $signaturePath ?? $quote->signature_path,
            ]);

            $quote->logActivity('approve', ['channel' => 'customer_portal']);

            if ($quote->created_by) {
                NotificationService::notify(
                    tenantId: $quote->tenant_id,
                    userId: $quote->created_by,
                    type: 'quote.approved',
                    title: __('quotes.approved_title', ['code' => $quote->code]),
                    body: __('quotes.approved_body'),
                    actionUrl: '/app/quotes/'.$quote->id,
                    actorId: null,
                    icon: 'check',
                );
            }

            return redirect()
                ->to('/'.$tenant->slug.'/portal/quote/'.$quote->id)
                ->with('success', __('quotes.public_approved'));
        });
    }

    /**
     * Reject a quote from inside the customer portal.
     */
    public function rejectQuote(Request $request): RedirectResponse
    {
        $id = (string) $request->route('id');
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);

        return DB::transaction(function () use ($request, $id, $customer, $tenant) {
            $request->validate([
                'rejection_reason' => ['required', 'string', 'max:1000'],
            ]);

            $quote = Quote::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('customer_id', $customer->id)
                ->where('tenant_id', $tenant->id)
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($quote === null) {
                abort(404);
            }

            if (! in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT], true)) {
                return back()->with('error', __('quotes.public_cannot_reject'));
            }

            if ($quote->isExpired()) {
                return back()->with('error', __('quotes.public_expired'));
            }

            if ($this->isQuoteEmpty($quote)) {
                return back()->with('error', __('quotes.empty_decision'));
            }

            $quote->update([
                'status' => Quote::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => trim((string) $request->input('rejection_reason')),
            ]);

            $quote->logActivity('reject', ['channel' => 'customer_portal']);

            if ($quote->created_by) {
                NotificationService::notify(
                    tenantId: $quote->tenant_id,
                    userId: $quote->created_by,
                    type: 'quote.rejected',
                    title: __('quotes.rejected_title', ['code' => $quote->code]),
                    body: __('quotes.rejected_body'),
                    actionUrl: '/app/quotes/'.$quote->id,
                    actorId: null,
                    icon: 'x',
                );
            }

            return redirect()
                ->to('/'.$tenant->slug.'/portal/quote/'.$quote->id)
                ->with('success', __('quotes.public_rejected'));
        });
    }

    /**
     * Invoices & payments statement page.
     */
    public function invoices(Request $request): Response
    {
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        $invoices = Invoice::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with([
                'center:id,name,name_ar,name_en',
                'workOrder:id,code,vehicle_id',
                'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                'workOrder.vehicle.make:id,name_ar,name_en',
                'workOrder.vehicle.model:id,name_ar,name_en',
            ])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $totalInvoiced = (float) $invoices->sum(fn (Invoice $inv) => (float) $inv->total_incl_tax);
        $totalPaid = (float) $invoices->sum(fn (Invoice $inv) => (float) $inv->total_paid);
        $totalBalance = (float) $invoices->sum(fn (Invoice $inv) => max(0.0, (float) $inv->balance));

        $badges = $this->portalBadges($customer, $tenant, $currency);

        return Inertia::render('Public/Portal/Invoices/Index', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'financialSummary' => [
                'total_invoiced' => round($totalInvoiced, 2),
                'total_paid' => round($totalPaid, 2),
                'total_balance' => round($totalBalance, 2),
                'unpaid_count' => $invoices->filter(fn (Invoice $inv) => (float) $inv->balance > 0.009)->count(),
                'currency' => $currency,
            ],
            'invoices' => $invoices->map(fn (Invoice $inv) => $this->mapInvoiceSummary($inv, $currency))->values(),
        ]);
    }

    /**
     * Detailed invoice view & print page.
     */
    public function showInvoice(Request $request): Response
    {
        $id = (string) $request->route('id');
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        $invoice = Invoice::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereKey($id)
            ->with([
                'center:id,name,name_ar,name_en,phone,vat_number',
                'center.address:id,center_id,address_line,district,city',
                'workOrder:id,code,vehicle_id,odometer,mileage',
                'workOrder.vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,color',
                'workOrder.vehicle.make:id,name_ar,name_en,logo_path',
                'workOrder.vehicle.model:id,name_ar,name_en',
                'lines',
                'payments',
            ])
            ->first();

        if ($invoice === null) {
            abort(404);
        }

        $tenant->loadMissing('address');
        $badges = $this->portalBadges($customer, $tenant, $currency);
        $summary = $this->mapInvoiceSummary($invoice, $currency);

        return Inertia::render('Public/Portal/Invoices/Show', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'invoice' => array_merge($summary, [
                'supply_date' => $invoice->supply_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'notes' => $invoice->notes,
                'seller_name' => $invoice->seller_name_snapshot ?: ($invoice->center !== null ? $invoice->center->display_name : $tenant->name),
                'seller_vat' => $invoice->seller_vat_snapshot ?: ($invoice->center !== null ? $invoice->center->vat_number : $tenant->vat_number),
                'seller_cr' => $invoice->seller_cr_snapshot ?: $tenant->cr_number,
                'center_phone' => $invoice->center !== null ? $invoice->center->phone : $tenant->phone,
                'center_address' => $this->formatAddress($invoice->center?->address) ?? $this->formatAddress($tenant->address),
                'customer_name' => $invoice->customer_name_snapshot ?: $customer->name,
                'customer_vat' => $invoice->customer_vat_snapshot ?: $customer->tax_number,
                'tax_enabled' => (bool) ($invoice->tax_enabled_snapshot ?? false),
                'tax_rate' => (float) ($invoice->tax_rate_snapshot ?? 15),
                'total_excl_tax' => (float) $invoice->total_excl_tax,
                'total_tax' => (float) $invoice->total_tax,
                'zatca_qr_tlv' => $invoice->tax_enabled_snapshot ? $invoice->zatca_qr_tlv : null,
                'zatca_qr_svg' => $invoice->tax_enabled_snapshot ? $this->zatcaQrDataUri($invoice->zatca_qr_tlv) : null,
                'lines' => array_values(array_map(static function ($line): array {
                    /** @var InvoiceLine $line */
                    return [
                        'id' => $line->id,
                        'description' => $line->description,
                        'is_part' => (bool) $line->is_part,
                        'is_warranty' => (bool) $line->is_warranty,
                        'qty' => (float) $line->qty,
                        'unit_price' => (float) $line->unit_price,
                        'discount_amount' => (float) ($line->discount_amount ?? 0),
                        'tax_amount' => (float) ($line->tax_amount ?? 0),
                        'total' => (float) ($line->line_total_incl_tax ?: $line->line_total_excl_tax),
                    ];
                }, $invoice->lines->all())),
                'payments' => array_values(array_map(static function ($p): array {
                    /** @var Payment $p */
                    return [
                        'id' => $p->id,
                        'amount' => (float) $p->amount,
                        'payment_date' => $p->payment_date?->toIso8601String(),
                        'payment_method' => $p->payment_method,
                        'reference' => $p->reference,
                        'type' => $p->type !== null ? $p->type->value : 'payment',
                    ];
                }, $invoice->payments->all())),
            ]),
        ]);
    }

    /**
     * Dedicated Appointments hub (upcoming + history).
     */
    public function appointments(Request $request): Response
    {
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);
        $currency = CountryCurrencyRegistry::tenantCurrency($tenant);

        $allAppointments = Appointment::query()
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->with([
                'center:id,name,name_ar,name_en,phone',
                'center.address:id,center_id,address_line,district,city',
                'serviceBay:id,name',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year,color',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'services:id,name_ar,name_en',
            ])
            ->orderByDesc('scheduled_at')
            ->limit(30)
            ->get();

        $upcoming = $allAppointments->filter(function (Appointment $a) {
            if (! in_array($a->status, [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
            ], true)) {
                return false;
            }

            return $a->status === Appointment::STATUS_CHECKED_IN
                || ($a->scheduled_at !== null && $a->scheduled_at->gte(now()));
        })->sortBy('scheduled_at')->values();

        $upcomingIds = $upcoming->pluck('id')->all();
        $history = $allAppointments
            ->reject(fn (Appointment $a) => in_array($a->id, $upcomingIds, true))
            ->values();

        $badges = $this->portalBadges($customer, $tenant, $currency);

        $mapAppointment = fn (Appointment $a): array => [
            'id' => $a->id,
            'scheduled_at' => $a->scheduled_at?->toIso8601String(),
            'estimated_end_at' => $a->estimated_end_at?->toIso8601String(),
            'status' => $a->status,
            'notes' => $a->notes,
            'work_order_id' => $a->work_order_id,
            'center_id' => $a->center_id,
            'center_name' => $a->center?->display_name,
            'center_address' => $this->formatAddress($a->center?->address),
            'service_bay_name' => $a->serviceBay?->name,
            'vehicle_id' => $a->vehicle_id,
            'vehicle_plate' => $a->vehicle?->plate_number,
            'vehicle_label' => $this->vehicleLabel($a->vehicle),
            'can_manage' => in_array($a->status, [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED], true)
                && $a->scheduled_at !== null
                && $a->scheduled_at->gt(now()),
            'services' => array_values(array_map(static fn ($s): array => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
            ], $a->services->all())),
        ];

        $upcomingList = [];

        foreach ($upcoming->all() as $appt) {
            $upcomingList[] = $mapAppointment($appt);
        }

        $pastList = [];

        foreach ($history->all() as $appt) {
            $pastList[] = $mapAppointment($appt);
        }

        return Inertia::render('Public/Portal/Appointments/Index', [
            'tenant' => $this->tenantContext($request, $badges),
            'locale' => $this->localeContext($request),
            'upcomingAppointments' => $upcomingList,
            'pastAppointments' => $pastList,
        ]);
    }

    private function mapWorkOrderSummary(WorkOrder $w, string $defaultCurrency): array
    {
        $activeItemsCount = 0;
        $completedItemsCount = 0;

        if ($w->relationLoaded('items')) {
            foreach ($w->items as $item) {
                if (! $item instanceof WorkOrderItem || $item->status === WorkOrderItem::STATUS_CANCELLED) {
                    continue;
                }

                $activeItemsCount++;

                if (in_array($item->status, [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_READY_FOR_QC], true)) {
                    $completedItemsCount++;
                }
            }
        }

        $itemsCount = $activeItemsCount;

        $progress = match ($w->status) {
            WorkOrder::STATUS_DONE => 100,
            WorkOrder::STATUS_READY_FOR_QC => 85,
            WorkOrder::STATUS_IN_PROGRESS => $itemsCount > 0
                ? max(35, min(80, (int) round(($completedItemsCount / $itemsCount) * 80)))
                : 55,
            WorkOrder::STATUS_ON_HOLD => 45,
            WorkOrder::STATUS_OPEN => 20,
            default => 10,
        };

        $stageIndex = match ($w->status) {
            WorkOrder::STATUS_OPEN, WorkOrder::STATUS_DRAFT => 1,
            WorkOrder::STATUS_IN_PROGRESS, WorkOrder::STATUS_ON_HOLD => 2,
            WorkOrder::STATUS_READY_FOR_QC => 3,
            WorkOrder::STATUS_DONE => 4,
            default => 1,
        };

        return [
            'id' => $w->id,
            'code' => $w->code,
            'status' => $w->status,
            'created_at' => $w->created_at?->toIso8601String(),
            'entry_date' => $w->entry_date?->toDateString(),
            'expected_end_date' => $w->expected_end_date?->toDateString(),
            'vehicle_id' => $w->vehicle_id,
            'vehicle_plate' => $w->vehicle?->plate_number,
            'vehicle_label' => $this->vehicleLabel($w->vehicle),
            'center_name' => $w->center?->display_name,
            'total_incl_tax' => (float) ($w->total_incl_tax ?: 0),
            'currency_code' => $defaultCurrency,
            'items_count' => $itemsCount,
            'completed_items_count' => $completedItemsCount,
            'progress' => $progress,
            'stage_index' => $stageIndex,
        ];
    }

    private function mapQuoteSummary(Quote $q, string $defaultCurrency): array
    {
        $isExpired = $q->isExpired();
        $isEmpty = $this->isQuoteEmpty($q);
        $canBeActioned = in_array($q->status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT], true)
            && ! $isExpired
            && ! $isEmpty;

        return [
            'id' => $q->id,
            'uuid' => $q->uuid,
            'code' => $q->code,
            'status' => $q->status,
            'created_at' => $q->created_at?->toDateString(),
            'valid_until' => $q->valid_until?->toDateString(),
            'is_expired' => $isExpired,
            'can_be_actioned' => $canBeActioned,
            'total_incl_tax' => (float) ($q->total_incl_tax ?: $q->total ?: $q->subtotal ?: 0),
            'currency_code' => $defaultCurrency,
            'center_name' => $q->center?->display_name,
            'vehicle_id' => $q->vehicle_id,
            'vehicle_plate' => $q->vehicle?->plate_number,
            'vehicle_label' => $this->vehicleLabel($q->vehicle),
            'items_count' => ($q->relationLoaded('lines') ? $q->lines->count() : 0)
                + ($q->relationLoaded('parts') ? $q->parts->count() : 0),
        ];
    }

    private function mapInvoiceSummary(Invoice $inv, string $defaultCurrency): array
    {
        $vehicle = $inv->workOrder?->vehicle;
        $typeVal = $inv->type instanceof BackedEnum ? $inv->type->value : (string) ($inv->type ?? 'invoice');

        return [
            'id' => $inv->id,
            'invoice_number' => $inv->invoice_number,
            'type' => $typeVal,
            'status' => $inv->status,
            'payment_status' => $inv->payment_status,
            'issue_date' => $inv->issue_date?->toDateString(),
            'total_incl_tax' => (float) $inv->total_incl_tax,
            'total_paid' => (float) $inv->total_paid,
            'balance' => max(0.0, (float) $inv->balance),
            'currency_code' => $inv->currency_code ?: $defaultCurrency,
            'center_name' => $inv->center?->display_name,
            'work_order_id' => $inv->work_order_id,
            'work_order_code' => $inv->workOrder?->code,
            'vehicle_plate' => $vehicle?->plate_number,
            'vehicle_label' => $this->vehicleLabel($vehicle),
        ];
    }

    private function portalBadges(Customer $customer, Tenant $tenant, string $currency): array
    {
        $activeWorkOrdersCount = WorkOrder::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                WorkOrder::STATUS_OPEN,
                WorkOrder::STATUS_IN_PROGRESS,
                WorkOrder::STATUS_ON_HOLD,
                WorkOrder::STATUS_READY_FOR_QC,
            ])
            ->count();

        $candidateQuotes = Quote::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT])
            ->with(['lines:id,quote_id', 'parts:id,quote_id'])
            ->get();

        $pendingQuotesCount = $candidateQuotes
            ->filter(fn (Quote $q) => ! $q->isExpired() && ! $this->isQuoteEmpty($q))
            ->count();

        $validInvoices = Invoice::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->get(['id', 'total_incl_tax', 'total_paid', 'payment_status', 'type']);

        $unpaidInvoices = $validInvoices->filter(fn (Invoice $inv) => (float) $inv->balance > 0.009);
        $unpaidBalance = round((float) $unpaidInvoices->sum(fn (Invoice $inv) => (float) $inv->balance), 2);

        $upcomingAppointmentsCount = Appointment::query()
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
            ])
            ->where(function ($q) {
                $q->where('scheduled_at', '>=', now())
                    ->orWhere('status', Appointment::STATUS_CHECKED_IN);
            })
            ->count();

        return [
            'active_work_orders_count' => $activeWorkOrdersCount,
            'pending_quotes_count' => $pendingQuotesCount,
            'unpaid_invoices_count' => $unpaidInvoices->count(),
            'unpaid_balance' => $unpaidBalance,
            'upcoming_appointments_count' => $upcomingAppointmentsCount,
            'currency' => $currency,
        ];
    }

    /**
     * Extensible feature capabilities map for the portal.
     * Future modules (customer satisfaction surveys, loyalty points,
     * maintenance reminders, etc.) can be toggled here cleanly.
     */
    private function portalCapabilities(): array
    {
        return [
            'appointments' => true,
            'work_orders' => true,
            'quotes' => true,
            'invoices' => true,
            'satisfaction_survey' => false,
            'loyalty_program' => false,
        ];
    }

    /**
     * Render the ZATCA TLV payload as an inline SVG data URI on the
     * server, so the customer's tax data is never sent to a
     * third-party QR rendering service.
     */
    private function zatcaQrDataUri(?string $tlv): ?string
    {
        if ($tlv === null || $tlv === '') {
            return null;
        }

        $writer = new Writer(new ImageRenderer(new RendererStyle(180, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($tlv));
    }

    private function formatAddress(CenterAddress|TenantAddress|null $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $parts = array_filter([
            $address->address_line,
            $address->district,
            $address->city,
        ]);

        return $parts !== [] ? implode('، ', $parts) : null;
    }

    private function vehicleLabel(?Vehicle $v): string
    {
        if ($v === null) {
            return '';
        }

        return trim(implode(' · ', array_filter([
            $v->display_make ?? $v->make?->name,
            $v->display_model ?? $v->model?->name,
            $v->year,
            $v->plate_number,
        ])));
    }

    private function isQuoteEmpty(Quote $quote): bool
    {
        $hasLines = $quote->relationLoaded('lines')
            ? $quote->lines->isNotEmpty()
            : $quote->lines()->exists();

        $hasParts = $quote->relationLoaded('parts')
            ? $quote->parts->isNotEmpty()
            : $quote->parts()->exists();

        return (float) $quote->subtotal <= 0
            && (float) $quote->total <= 0
            && ! $hasLines
            && ! $hasParts;
    }

    private function customer(Request $request): Customer
    {
        $customer = $request->attributes->get('portal_customer');

        if (! $customer instanceof Customer) {
            abort(401);
        }

        return $customer;
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('public_tenant');

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        return $tenant;
    }

    private function tenantContext(Request $request, array $badges = []): array
    {
        $tenant = $this->tenant($request);
        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();
        $logoUrl = $website?->logo_url;

        return [
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'country' => CountryCurrencyRegistry::normalizeCountry($tenant->country),
            'currency' => CountryCurrencyRegistry::tenantCurrency($tenant),
            'site_name' => $website?->display_name ?? $tenant->name,
            'tagline' => $website?->tagline,
            'logo_url' => $logoUrl,
            'has_logo' => $logoUrl !== null && $logoUrl !== '',
            'primary_color' => ($website !== null ? $website->effective_primary_color : null) ?? '#2563eb',
            'secondary_color' => ($website !== null ? $website->effective_secondary_color : null) ?? '#0ea5e9',
            'badges' => $badges,
        ];
    }

    private function localeContext(Request $request): array
    {
        $current = $request->attributes->get('portal_locale') ?? PortalLocale::DEFAULT_LOCALE;

        return [
            'current' => $current,
            'is_rtl' => $current === 'ar',
        ];
    }
}
