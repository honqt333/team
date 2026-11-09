<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Services\Media\OpenGraphImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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
    public function __construct(
        private readonly OpenGraphImage $openGraphImage,
    ) {}

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

        $settings = $website->effective_settings;
        $banners = array_values(array_filter($website->effective_banners, fn ($b) => ! empty($b['is_active'])));

        // Services query scoped to tenant and configured display mode
        $servicesQuery = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL);

        if (($settings['services_mode'] ?? 'all') === 'selected' && ! empty($settings['selected_service_ids'])) {
            $servicesQuery->whereIn('id', (array) $settings['selected_service_ids']);
        }

        $featuredIds = (array) ($settings['featured_service_ids'] ?? []);

        $services = $servicesQuery
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->limit(24)
            ->get(['id', 'name_ar', 'name_en', 'description_ar', 'description_en', 'base_price', 'min_price']);

        $pricingMode = $settings['pricing_mode'] ?? 'upon_inspection';
        $showPrices = ($pricingMode !== 'hidden') && (bool) ($settings['show_prices'] ?? true);

        // Active centers with the public-facing contact fields
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
                'has_portal' => (bool) ($settings['enable_portal_lookup'] ?? true),
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
                /*
                 * The meta description.
                 *
                 * It used to fall back to the tagline and then to an EMPTY
                 * string, which fails the `meta-description` audit and
                 * leaves the page with no search snippet at all.
                 *
                 * The fallback is built from what the tenant actually has:
                 * their name, their tagline, the services they offer and
                 * where they are. It is only used when the operator has not
                 * written one.
                 */
                'seo_description' => $this->buildSeoDescription($website, $services, $centers),
                'banners' => $banners,
                'settings' => $settings,
                /*
                 * Canonical URL.
                 *
                 * Built from the request rather than from the tenant slug,
                 * so it stays correct when the site is served on a custom
                 * domain. Search engines need one canonical URL per page;
                 * without it the same content is reachable under several
                 * addresses.
                 */
                'canonical_url' => $request->url(),
                'alternate_ar_url' => $request->url(),
                'alternate_en_url' => $request->url(),
                /*
                 * The image used for the social share preview.
                 *
                 * A logo is a square and makes a poor share card, so a
                 * banner is preferred when there is one.
                 */
                'share_image_url' => $this->shareImageUrl($banners, $website),
                /*
                 * Structured data.
                 *
                 * `AutoRepair` is the schema.org type for a car workshop. It
                 * is what makes the workshop eligible for the local
                 * business / service panels in search results.
                 *
                 * No `aggregateRating` is emitted: a rating that is not
                 * backed by real reviews is a fabricated claim, and search
                 * engines treat unverified review markup as a spam signal.
                 */
                'json_ld' => $this->buildJsonLd($tenant, $website, $services, $centers, $request),
            ],
            'services' => $services->map(function (Service $s) use ($pricingMode, $showPrices, $featuredIds) {
                $base = (float) ($s->base_price ?? 0);
                $min = (float) ($s->min_price ?? 0);

                $priceDisplay = null;

                if ($pricingMode === 'exact' && $showPrices && $base > 0) {
                    $priceDisplay = number_format($base, 2).' '.__('tenant_site.pricing.sar');
                } elseif ($pricingMode === 'starting_from' && $showPrices) {
                    $val = ($min > 0) ? $min : $base;

                    if ($val > 0) {
                        $priceDisplay = __('tenant_site.pricing.starting_from', ['price' => number_format($val, 2)]);
                    }
                } elseif ($pricingMode === 'upon_inspection') {
                    $priceDisplay = __('tenant_site.pricing.upon_inspection');
                }

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'description' => $s->description,
                    'price' => $base,
                    'price_display' => $priceDisplay,
                    'is_featured' => in_array($s->id, $featuredIds, false),
                ];
            })->values(),
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

    /**
     * Build the meta description for the tenant's public page.
     *
     * @param Collection<int, Service> $services
     * @param Collection<int, Center> $centers
     */
    private function buildSeoDescription(TenantWebsite $website, $services, $centers): string
    {
        $explicit = trim((string) $website->seo_description);

        if ($explicit !== '') {
            return $explicit;
        }

        $isArabic = app()->getLocale() === 'ar';

        $name = trim($website->display_name);
        $tagline = trim((string) $website->tagline);

        $parts = [];

        if ($name !== '' && $tagline !== '') {
            $parts[] = $name.' — '.$tagline;
        } else {
            $parts[] = $name !== '' ? $name : $tagline;
        }

        $serviceNames = $services
            ->take(4)
            ->map(fn (Service $s) => $s->name)
            ->filter()
            ->all();

        if ($serviceNames !== []) {
            $parts[] = ($isArabic ? 'خدماتنا: ' : 'Services: ').implode('، ', $serviceNames);
        }

        $mainCenter = $centers->firstWhere('is_main', true) ?? $centers->first();
        $city = $mainCenter?->address?->city;

        if ($city) {
            $parts[] = ($isArabic ? 'في ' : 'Located in ').$city;
        }

        $description = implode('. ', array_filter($parts));

        // Nothing usable was configured — say what the site IS rather than
        // emitting an empty description.
        return trim($description) !== '' ? $description : ($isArabic
            ? 'مركز صيانة سيارات — خدمات فحص وصيانة وفوترة إلكترونية.'
            : 'Car workshop — inspection, repair and e-invoicing services.');
    }

    /**
     * Build the schema.org JSON-LD graph for the workshop.
     *
     * @param Collection<int, Service> $services
     * @param Collection<int, Center> $centers
     * @return array<string, mixed>
     */
    private function buildJsonLd(
        Tenant $tenant,
        TenantWebsite $website,
        $services,
        $centers,
        Request $request
    ): array {
        $name = $website->display_name;

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'AutoRepair',
            'name' => $name,
            'url' => $request->url(),
        ];

        if ($website->tagline) {
            $data['description'] = $website->tagline;
        }

        if ($website->cover_url) {
            $data['image'] = $website->cover_url;
        } elseif ($website->logo_url) {
            $data['image'] = $website->logo_url;
        }

        if ($website->contact_phone) {
            $data['telephone'] = $website->contact_phone;
        }

        if ($website->address_line) {
            $mainCenter = $centers->firstWhere('is_main', true) ?? $centers->first();

            $address = [
                '@type' => 'PostalAddress',
                'streetAddress' => $website->address_line,
            ];

            if ($mainCenter?->address?->city) {
                $address['addressLocality'] = $mainCenter->address->city;
            }

            if ($mainCenter?->address?->district) {
                $address['addressRegion'] = $mainCenter->address->district;
            }

            $data['address'] = $address;
        }

        $offerItems = $services
            ->take(20)
            ->map(fn (Service $s) => [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $s->name,
                ],
            ])
            ->values()
            ->all();

        if ($offerItems !== []) {
            $data['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => __('tenant_site.json_ld.service_catalog'),
                'itemListElement' => $offerItems,
            ];
        }

        if ($centers->isNotEmpty()) {
            $data['location'] = $centers->map(fn (Center $c) => [
                '@type' => 'Place',
                'name' => $c->display_name ?: $c->name,
                'telephone' => $c->phone,
            ])->values()->all();
        }

        return $data;
    }

    /**
     * The image used for the social share preview.
     *
     * It points at the generated share card, NOT at the banner file:
     *
     *  - the banner is stored as WebP after the upload is optimised, and
     *    the Open Graph crawlers do not render WebP — the card would come
     *    out empty;
     *  - a banner is 1920×960 and a logo is square, while the card ratio
     *    every platform expects is 1.91:1.
     *
     * The URL is absolute: a relative `og:image` is ignored by every
     * platform and the share falls back to a bare text link.
     *
     * @param array<int, array<string, mixed>> $banners
     */
    private function shareImageUrl(array $banners, TenantWebsite $website): ?string
    {
        $tenant = $website->tenant;

        if (! $tenant) {
            return null;
        }

        return url("/{$tenant->slug}/og-image.jpg");
    }

    /**
     * The 1200×630 share card of this workshop.
     *
     * Served as a JPEG on purpose: the Open Graph crawlers do not render
     * WebP, and the optimised banner is exactly that.
     */
    public function ogImage(Request $request): HttpResponse
    {
        /** @var TenantWebsite $website */
        $website = $request->attributes->get('public_tenant_website');

        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('public_tenant');

        $bytes = $this->openGraphImage->forTenant($website, $tenant->slug);

        if ($bytes === '') {
            abort(404);
        }

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => (string) strlen($bytes),
            // The file name carries a fingerprint of the source image, so a
            // new banner produces a new URL and the old one can be cached
            // forever.
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }
}
