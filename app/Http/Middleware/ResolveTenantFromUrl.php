<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantWebsite;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves a tenant from the public URL slug and binds the
 * published TenantWebsite onto the request for downstream
 * controllers.
 *
 * The middleware is mounted on a route group with the
 * `{tenant}` route parameter. The contract:
 *
 *   1. The {tenant} param is treated as a tenant SLUG (the
 *      unique key on the tenants table). It is NOT trusted
 *      as a tenant_id — a user with role X in tenant A
 *      cannot pass tenant B's id through the URL.
 *
 *   2. If the tenant does not exist, is soft-deleted, or has
 *      no published TenantWebsite row, the request 404s with
 *      a generic message. We deliberately do NOT distinguish
 *      "tenant not found" from "website not published" — the
 *      distinction leaks existence to attackers.
 *
 *   3. The Tenant and TenantWebsite are attached to the
 *      request attributes:
 *          $request->attributes->get('public_tenant')
 *          $request->attributes->get('public_tenant_website')
 *
 *      Controllers read from there, never re-query the DB.
 *
 * Design choice: ResolveTenantFromUrl is the ONLY place in
 * the app that turns an arbitrary URL slug into a Tenant.
 * Anything else that needs to identify a tenant (admin
 * forms, webhooks) must come from a signed, authenticated
 * source.
 */
class ResolveTenantFromUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('tenant', '');

        if ($slug === '') {
            abort(404);
        }

        $tenant = Tenant::where('slug', $slug)->first();

        if (! $tenant || $tenant->trashed()) {
            // Same 404 as the "not published" case. The
            // message is intentionally vague.
            abort(404, __('tenant_site.errors.not_found'));
        }

        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();

        // We do NOT 404 when the website row simply does not
        // exist yet — that would block every brand-new tenant
        // from ever showing a site. Instead we treat missing =
        // unpublished. The owner can only make the site
        // visible by creating a row AND setting status =
        // 'published'. This is the same behavior an admin
        // would expect from a "draft by default" workflow.
        if (! $website || ! $website->is_published) {
            abort(404, __('tenant_site.errors.not_found'));
        }

        $request->attributes->set('public_tenant', $tenant);
        $request->attributes->set('public_tenant_website', $website);

        return $next($request);
    }
}
