<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public-facing tenant website. This is the only place
 * in the app where the {tenant} URL slug is honored as a
 * tenant identity. Everything else is gated by auth.
 *
 * Data shown on the page is explicitly a subset of the
 * tenant's domain:
 *   - tenant profile (display name, branding)
 *   - active services (no pricing shown on the public page
 *     — pricing is internal until the customer portal
 *     exists in a later phase)
 *   - active centers (just public-facing fields)
 *
 * What is DELIBERATELY NOT shown here, even though the
 * model columns exist:
 *   - work orders
 *   - appointments
 *   - customers
 *   - employees
 *   - any operational metrics
 *   - financial data
 *
 * Every query is filtered by tenant_id explicitly. The
 * CenterScoped / TenantScoped global scopes are NOT
 * relied on here because the request is unauthenticated
 * — TenancyContext returns null, the scopes are a no-op,
 * and we would silently expose all rows. Instead the
 * controllers in this file add where('tenant_id', ...).
 */
class TenantSiteController extends Controller
{
    /**
     * The public homepage. The {tenant} route param is bound
     * and validated by the ResolveTenantFromUrl middleware;
     * by the time we reach this method, request attributes
     * 'public_tenant' and 'public_tenant_website' are set.
     */
    public function show(Request $request): Response
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        /** @var TenantWebsite $website */
        $website = $request->attributes->get('public_tenant_website');

        // Active services only, scoped to this tenant. We do
        // not show packages here (those are bundled SKUs and
        // would be confusing on a public landing page until
        // a real ordering flow exists).
        $services = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->limit(12)
            ->get(['id', 'name_ar', 'name_en', 'description_ar', 'description_en']);

        // Active centers with the public-facing contact
        // fields. We use a manual query rather than
        // $tenant->centers() because the centers relation
        // would not filter is_active in one go without a
        // scope, and we want to be explicit.
        $centers = Center::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with(['address'])
            ->orderByDesc('is_main')
            ->orderBy('name_ar')
            ->get(['id', 'name', 'name_ar', 'name_en', 'phone', 'email', 'is_main', 'slug']);

        return Inertia::render('Public/Site/Index', [
            'site' => [
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'has_portal' => true,
                'site_name' => $website->display_name,
                'tagline' => $website->tagline,
                'logo_url' => $website->logo_url,
                'cover_url' => $website->cover_url,
                'primary_color' => $website->effective_primary_color,
                'secondary_color' => $website->effective_secondary_color,
                'theme_key' => $website->themeKeyNormalized,
                'contact_phone' => $website->contact_phone,
                'contact_whatsapp' => $website->contact_whatsapp,
                'contact_email' => $website->contact_email,
                'phone_link' => $website->phone_link,
                'whatsapp_link' => $website->whatsapp_link,
                'address_line' => $website->address_line,
                'social_links' => $website->social_links ?? [],
                'seo_title' => $website->seo_title ?: $website->display_name,
                'seo_description' => $website->seo_description ?: $website->tagline,
            ],
            'services' => $services->map(fn (Service $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'description' => $s->description,
            ])->values(),
            'centers' => $centers->map(fn (Center $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'display_name' => $c->display_name,
                'phone' => $c->phone,
                'email' => $c->email,
                'is_main' => (bool) $c->is_main,
                'address' => $c->address ? [
                    'address_line' => $c->address->address_line,
                    'city' => $c->address->city,
                    'district' => $c->address->district,
                ] : null,
            ])->values(),
        ]);
    }
}
