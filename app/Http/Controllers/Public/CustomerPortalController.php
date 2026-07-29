<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
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

        // Vehicles — light projection; the portal does not
        // need mileage history, condition reports, or
        // ownership metadata.
        $vehicles = $customer->vehicles()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get(['id', 'plate_number', 'make', 'model', 'year', 'color']);

        // Upcoming + in-progress appointments. The portal
        // surfaces statuses the customer can act on:
        //   - scheduled   (booked, awaiting the day)
        //   - confirmed   (the center acknowledged)
        //   - checked_in  (the customer is physically in
        //                  the workshop — the portal should
        //                  show this so the customer can
        //                  see "yes, we received you")
        // We exclude cancelled / no_show / converted.
        $upcomingAppointments = Appointment::query()
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                Appointment::STATUS_SCHEDULED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_CHECKED_IN,
            ])
            // checked_in is "in the past" by definition, so
            // we filter on scheduled_at OR status = checked_in.
            ->where(function ($q) {
                $q->where('scheduled_at', '>=', now())
                    ->orWhere('status', Appointment::STATUS_CHECKED_IN);
            })
            ->with([
                'center:id,name,name_ar,name_en',
                'serviceBay:id,name',
            ])
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get(['id', 'scheduled_at', 'estimated_end_at', 'status', 'vehicle_id', 'center_id', 'service_bay_id']);

        // Recent work orders — last 5 regardless of status.
        // The portal shows the customer "what we did
        // recently". We surface expected_end_date (not just
        // created_at) so the customer can see WHEN the
        // work is promised to be done.
        $recentWorkOrders = WorkOrder::query()
            ->where('customer_id', $customer->id)
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'code', 'status', 'created_at', 'expected_end_date', 'vehicle_id']);

        return Inertia::render('Public/Portal/Dashboard', [
            'tenant' => [
                'id' => $tenant->id,
                'slug' => $tenant->slug,
                'site_name' => $tenant->website?->display_name ?? $tenant->name,
            ],
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'vehicles' => $vehicles->map(fn ($v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'make' => $v->make,
                'model' => $v->model,
                'year' => $v->year,
                'color' => $v->color,
            ])->values(),
            'upcomingAppointments' => $upcomingAppointments->map(fn (Appointment $a) => [
                'id' => $a->id,
                'scheduled_at' => $a->scheduled_at?->toIso8601String(),
                'estimated_end_at' => $a->estimated_end_at?->toIso8601String(),
                'status' => $a->status,
                'vehicle_id' => $a->vehicle_id,
                'center_id' => $a->center_id,
                'service_bay_id' => $a->service_bay_id,
                // Surface the human-readable names instead
                // of (or in addition to) the IDs. The portal
                // shows "Bay 3 at Center Riyadh-North" not
                // "service_bay_id: 17, center_id: 4".
                'center_name' => $a->center?->display_name,
                'service_bay_name' => $a->serviceBay?->name,
            ])->values(),
            'recentWorkOrders' => $recentWorkOrders->map(fn (WorkOrder $w) => [
                'id' => $w->id,
                'code' => $w->code,
                'status' => $w->status,
                'created_at' => $w->created_at?->toIso8601String(),
                'expected_end_date' => $w->expected_end_date?->toDateString(),
                'vehicle_id' => $w->vehicle_id,
            ])->values(),
        ]);
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
}
