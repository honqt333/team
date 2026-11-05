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
            'auth' => [
                'user' => $user ? ($isTenantUser ? $user->load('roles:id,name,label_ar,label_en') : $user) : null,
                'is_super_admin' => $isSuperAdmin,
                'permissions' => $user ? ($isTenantUser ? $user->getAllPermissions()->pluck('name') : ($user->permissions ?? [])) : [],
                'available_centers' => ($user && $isTenantUser) ? $user->centers()->get(['centers.id', 'centers.name_ar', 'centers.name_en']) : [],
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
                'vat_number' => $tenant->vat_number,
                'logo_url' => $tenant->logo_path ? Storage::url($tenant->logo_path) : asset('images/logo.png'),
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
            ] : null,
            'center' => $user?->currentCenter ? [
                'id' => $user->currentCenter->id,
                'name' => $user->currentCenter->name, // Accessor handles lang
                'name_ar' => $user->currentCenter->name_ar,
                'name_en' => $user->currentCenter->name_en,
                'type' => $user->currentCenter->center_type,
                'phone' => $user->currentCenter->phone,
                'email' => $user->currentCenter->email,
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
