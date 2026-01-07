<?php

namespace App\Http\Middleware;

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
        $isTenantUser = $user instanceof \App\Models\User;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? ($isTenantUser ? $user->load('roles:id,name,label_ar,label_en') : $user) : null,
                'permissions' => $user ? ($isTenantUser ? $user->getAllPermissions()->pluck('name') : ($user->permissions ?? [])) : [],
                'available_centers' => ($user && $isTenantUser) ? $user->centers()->get(['centers.id', 'centers.name_ar', 'centers.name_en']) : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->trade_name ?: $tenant->legal_name ?: $tenant->name,
                'legal_name' => $tenant->legal_name,
                'trade_name' => $tenant->trade_name,
                'owner_name' => $tenant->owner_name,
                'phone' => $tenant->phone,
                'email' => $tenant->email,
                'cr_number' => $tenant->cr_number,
                'logo_url' => $tenant->logo_path ? Storage::url($tenant->logo_path) : asset('images/logo.png'),
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
                ? \App\Models\Tenant::find($request->session()->get('impersonating_tenant'))?->trade_name 
                : null,
        ];
    }
}
