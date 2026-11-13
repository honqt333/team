<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\ContactMessage;
use App\Models\InternalNotification;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\SystemAnnouncement;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CountryCurrencyRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $tenant = $user?->tenant;

        // Check if user is a regular tenant User
        $isTenantUser = $user instanceof User;
        $isSystemAdmin = $user instanceof AdminUser;

        // Expose super_admin flag for nav-group filtering in the layout.
        // Server-side EnsureSuperAdmin middleware still enforces the
        // actual route protection; this is purely for hiding links
        // the user can't open.
        $isSuperAdmin = $isSystemAdmin
            ? $user->isSuperAdmin()
            : ($isTenantUser ? (bool) ($user->is_system_admin ?? false) : false);

        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(), // Exposed for fetch() calls that
            // can't use Inertia's automatic
            // CSRF (e.g. multipart file
            // uploads). Always fresh per
            // request so the token doesn't
            // get stale like the meta tag.
            'locale' => app()->getLocale(),
            'app_name' => app()->getLocale() === 'ar'
                ? config('app.name_ar', 'خدمة برو')
                : config('app.name', 'Khidmh Pro'),
            'app_logo' => Setting::get('system.brand_logo') ?: (Setting::get('website.logo') ?: null),
            'app_favicon' => Setting::get('system.brand_favicon') ?: (Setting::get('website.favicon') ?: null),
            'auth' => [
                'user' => $user ? ($isTenantUser ? $user->load('roles:id,name,label_ar,label_en') : $user) : null,
                'is_super_admin' => $isSuperAdmin,
                'has_tenant_context' => $isTenantUser && $tenant !== null && ! $tenant->trashed(),
                // For a system admin the panel resolves abilities from the
                // role and the account's own list, so the EFFECTIVE set is
                // what the front end needs — not the raw column, which is
                // empty for every account that relies on its role default.
                'permissions' => $user ? ($isTenantUser
                    ? $user->getAllPermissions()->pluck('name')
                    : $user->effectivePermissions()) : [],
                'available_centers' => ($user && $isTenantUser)
                    ? $user->centers()
                        ->orderByDesc('is_main')
                        ->orderBy('centers.id')
                        ->get(['centers.id', 'centers.name', 'centers.name_ar', 'centers.name_en', 'centers.is_main'])
                        ->map(fn ($c) => [
                            'id' => $c->id,
                            'name' => $c->name_ar ?: ($c->name_en ?: ($c->name ?: '')),
                            'name_ar' => $c->name_ar ?: ($c->name ?: ($c->name_en ?: '')),
                            'name_en' => $c->name_en ?: ($c->name ?: ($c->name_ar ?: '')),
                            'display_name' => $c->display_name,
                            'is_main' => (bool) $c->is_main,
                        ])
                        ->values()
                        ->all()
                    : [],
                'unread_notifications_count' => ($user && $isTenantUser)
                    ? InternalNotification::where('user_id', $user->id)->where('tenant_id', $user->tenant_id)->whereNull('read_at')->count()
                    + ($tenant ? SystemAnnouncement::forTenant($tenant->id)->whereDoesntHave('reads', fn ($q) => $q->where('tenant_id', $tenant->id))->count() : 0)
                    : 0,
                'approved_quotes_count' => ($user && $isTenantUser)
                    ? Quote::where('status', Quote::STATUS_APPROVED)->count()
                    : 0,
                'unread_contact_messages_count' => (! $isTenantUser && $user)
                    ? ContactMessage::where('is_read', false)->count()
                    : 0,
            ],
            'preferences' => ($user && $isTenantUser && $user->tenant_id)
                ? rescue(function () use ($user) {
                    $raw = Setting::forTenant($user->tenant_id, 'user_preferences.'.$user->id);

                    return $raw ? json_decode((string) $raw, true) : null;
                })
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'status' => fn () => $request->session()->get('status'),
                'message' => fn () => $request->session()->get('message'),
                'new_service_id' => fn () => $request->session()->get('new_service_id'),
                // The biometric device token is issued once and is
                // never stored in recoverable form (it is hashed at
                // rest), so it is flashed to the page that asked for
                // it and nowhere else.
                'new_device_token' => fn () => $request->session()->get('new_device_token'),
            ],
            'customer' => fn () => $request->session()->get('customer'),
            'vehicle' => fn () => $request->session()->get('vehicle'),
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->trade_name ?: $tenant->legal_name ?: $tenant->name,
                'legal_name' => $tenant->legal_name,
                'trade_name' => $tenant->trade_name,
                'owner_name' => $tenant->owner_name,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
                'cr_number' => $tenant->cr_number,
                'vat_number' => $tenant->taxSettings?->tax_number ?? $tenant->vat_number ?? '',
                'iban' => $tenant->iban,
                'country' => CountryCurrencyRegistry::tenantCountry($tenant),
                'currency' => CountryCurrencyRegistry::tenantCurrency($tenant),
                'currency_meta' => CountryCurrencyRegistry::currencyMeta(CountryCurrencyRegistry::tenantCurrency($tenant)),
                'country_meta' => CountryCurrencyRegistry::countryMeta(CountryCurrencyRegistry::tenantCountry($tenant)),
                'timezone' => $tenant->timezone ?? CountryCurrencyRegistry::countryMeta(CountryCurrencyRegistry::tenantCountry($tenant))['timezone'],
                'logo_url' => $tenant->logo_path ? Storage::url($tenant->logo_path) : asset('images/brand/logo-256.png'),
                'dunning_opted_out' => (bool) $tenant->dunning_opted_out,
                'dunning_language' => $tenant->dunning_language,
                'quote_title' => $tenant->quote_title,
                'work_order_title' => $tenant->work_order_title,
                'invoice_title' => $tenant->invoice_title,
                'quote_terms' => $tenant->quote_terms,
                'work_order_terms' => $tenant->work_order_terms,
                'invoice_terms' => $tenant->invoice_terms,
                'print_settings' => $tenant->print_settings,
                'tax_settings' => $tenant->taxSettings,
                'has_paid_subscription' => $tenant->hasPaidSubscription(),
                'status' => $tenant->status,
                'trial_ends_at' => $tenant->trial_ends_at ? $tenant->trial_ends_at->toIso8601String() : null,
                'subscription_ends_at' => rescue(fn () => $tenant->subscriptions()->whereIn('status', ['active', 'trial', 'trialing'])->latest()->first()?->ends_at?->toIso8601String()),
                'subscription_lifecycle' => rescue(function () use ($tenant) {
                    $lifecycle = $tenant->getSubscriptionLifecycle();

                    return [
                        'state' => $lifecycle['state'],
                        'can_operate' => $lifecycle['can_operate'],
                        'is_on_trial' => $lifecycle['is_on_trial'],
                        'is_in_grace_period' => $lifecycle['is_in_grace_period'],
                        'grace_period_days' => $lifecycle['grace_period_days'],
                        'grace_days_remaining' => $lifecycle['grace_days_remaining'],
                        'days_remaining' => $lifecycle['days_remaining'],
                        'ends_at' => $lifecycle['ends_at']?->toIso8601String(),
                        'grace_ends_at' => $lifecycle['grace_ends_at']?->toIso8601String(),
                    ];
                }),
            ] : null,
            'center' => $user?->currentCenter ? [
                'id' => $user->currentCenter->id,
                'name' => $user->currentCenter->name,
                'name_ar' => $user->currentCenter->name_ar ?: ($user->currentCenter->name ?: ($user->currentCenter->name_en ?: '')),
                'name_en' => $user->currentCenter->name_en ?: ($user->currentCenter->name ?: ($user->currentCenter->name_ar ?: '')),
                'display_name' => $user->currentCenter->display_name,
                'is_main' => (bool) $user->currentCenter->is_main,
                'type' => $user->currentCenter->center_type,
                'phone' => $user->currentCenter->phone,
                'email' => $user->currentCenter->email,
                'vat_number' => $user->currentCenter->vat_number,
                'logo_invoice_url' => $user->currentCenter->logo_invoice_url,
                'logo_light_url' => $user->currentCenter->logo_light_url,
                'stamp_url' => $user->currentCenter->stamp_url,
            ] : null,
            // Impersonation state
            'impersonating' => $request->session()->has('impersonating_from'),
            'impersonating_tenant_name' => $request->session()->has('impersonating_tenant')
                ? Tenant::find($request->session()->get('impersonating_tenant'))?->trade_name
                : null,
            // System announcements for tenant users
            'system_announcements' => ($user && $isTenantUser && $tenant)
                ? SystemAnnouncement::forTenant($tenant->id)
                    ->whereDoesntHave('reads', fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->select('id', 'title', 'content', 'type', 'published_at')
                    ->latest('published_at')
                    ->limit(5)
                    ->get()
                : [],
        ];
    }
}
