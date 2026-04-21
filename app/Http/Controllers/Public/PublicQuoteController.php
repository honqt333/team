<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicQuoteController extends Controller
{
    /**
     * Display a public, unauthenticated view of a quote by its UUID.
     */
    public function show(string $uuid): Response
    {
        $quote = Quote::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->with([
                'center',
                'tenant',
                'customer',
                'vehicle.make',
                'vehicle.model',
                'lines',
                'parts',
            ])
            ->firstOrFail();

        return Inertia::render('Quotes/PublicShow', [
            'quote' => [
                'id'                    => $quote->id,
                'uuid'                  => $quote->uuid,
                'code'                  => $quote->code,
                'status'                => $quote->status,
                'odometer'              => $quote->odometer,
                'notes'                 => $quote->notes,
                'customer_complaint'    => $quote->customer_complaint,
                'initial_assessment'    => $quote->initial_assessment,
                'rejection_reason'      => $quote->rejection_reason,
                'subtotal'              => $quote->subtotal,
                'total_discount'        => $quote->total_discount,
                'total'                 => $quote->total,
                'total_excl_tax'        => $quote->total_excl_tax,
                'total_tax'             => $quote->total_tax,
                'total_incl_tax'        => $quote->total_incl_tax,
                'tax_enabled_snapshot'  => $quote->tax_enabled_snapshot,
                'pricing_mode_snapshot' => $quote->pricing_mode_snapshot,
                'tax_rate_snapshot'     => $quote->tax_rate_snapshot,
                'currency_code'         => $quote->currency_code,
                'created_at'            => $quote->created_at?->toDateString(),
                'can_be_actioned'       => in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT]),
                'center' => $quote->center ? [
                    'id'            => $quote->center->id,
                    'name'          => $quote->center->name,
                    'name_ar'       => $quote->center->name_ar,
                    'name_en'       => $quote->center->name_en,
                    'phone'         => $quote->center->phone,
                    'email'         => $quote->center->email,
                    'logo_light_url' => $quote->center->logo_light_url,
                    'logo_dark_url'  => $quote->center->logo_dark_url,
                ] : null,
                'tenant' => $quote->tenant ? [
                    'quote_title' => $quote->tenant->quote_title,
                    'quote_terms' => $quote->tenant->quote_terms,
                ] : null,
                'customer' => $quote->customer ? [
                    'id'    => $quote->customer->id,
                    'name'  => $quote->customer->name,
                    'phone' => $quote->customer->phone,
                ] : null,
                'vehicle' => $quote->vehicle ? [
                    'id'           => $quote->vehicle->id,
                    'plate_number' => $quote->vehicle->plate_number,
                    'make'         => $quote->vehicle->make?->name,
                    'model'        => $quote->vehicle->model?->name,
                    'year'         => $quote->vehicle->year,
                    'color'        => $quote->vehicle->color,
                ] : null,
                'lines' => $quote->lines->map(fn($line) => [
                    'id'             => $line->id,
                    'title'          => $line->title,
                    'description'    => $line->description,
                    'qty'            => $line->qty,
                    'unit_price'     => $line->unit_price,
                    'discount_amount'=> $line->discount_amount,
                    'tax_amount'     => $line->tax_amount,
                    'line_total'     => $line->line_total,
                ]),
                'parts' => $quote->parts->map(fn($part) => [
                    'id'             => $part->id,
                    'name'           => $part->name,
                    'part_number'    => $part->part_number,
                    'qty'            => $part->qty,
                    'unit_price'     => $part->unit_price,
                    'discount'       => $part->discount,
                    'tax_amount'     => $part->tax_amount,
                    'total'          => $part->total,
                    'total_incl_tax' => $part->total_incl_tax,
                ]),
            ],
        ]);
    }

    /**
     * Approve a quote via public link.
     */
    public function approve(string $uuid): \Illuminate\Http\RedirectResponse
    {
        $quote = Quote::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (!in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT])) {
            return back()->with('error', 'لا يمكن الموافقة على هذا العرض.');
        }

        $quote->update([
            'status'      => Quote::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        // Notify the quote creator
        if ($quote->created_by) {
            NotificationService::notify(
                tenantId: $quote->tenant_id,
                userId: $quote->created_by,
                type: 'quote.approved',
                title: 'وافق العميل على عرض السعر #' . $quote->code,
                body: 'تمت الموافقة من قِبَل العميل عبر رابط المشاركة',
                actionUrl: '/app/quotes/' . $quote->id,
                actorId: null,
                icon: 'check',
            );
        }

        return redirect()->route('public.quotes.show', $quote->uuid)
            ->with('success', 'تمت الموافقة على عرض السعر بنجاح.');
    }

    /**
     * Reject a quote via public link with a reason.
     */
    public function reject(Request $request, string $uuid): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $quote = Quote::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->firstOrFail();

        if (!in_array($quote->status, [Quote::STATUS_DRAFT, Quote::STATUS_SENT])) {
            return back()->with('error', 'لا يمكن رفض هذا العرض.');
        }

        $quote->update([
            'status'           => Quote::STATUS_REJECTED,
            'rejected_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Notify the quote creator
        if ($quote->created_by) {
            NotificationService::notify(
                tenantId: $quote->tenant_id,
                userId: $quote->created_by,
                type: 'quote.rejected',
                title: 'رفض العميل عرض السعر #' . $quote->code,
                body: 'سبب الرفض: ' . $request->rejection_reason,
                actionUrl: '/app/quotes/' . $quote->id,
                actorId: null,
                icon: 'x',
            );
        }

        return redirect()->route('public.quotes.show', $quote->uuid)
            ->with('success', 'تم تسجيل رفضك لعرض السعر.');
    }
}
