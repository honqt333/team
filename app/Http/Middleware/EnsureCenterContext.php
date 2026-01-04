<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCenterContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'User not authenticated');
        }

        // Get current center ID from user
        $currentCenterId = $user->current_center_id;

        if (!$currentCenterId) {
            abort(403, 'No active center selected');
        }

        // Load center with soft delete check
        $center = \App\Models\Center::find($currentCenterId);

        // Validate center exists and not soft-deleted
        if (!$center || $center->trashed()) {
            abort(403, 'Center not found or inactive');
        }

        // Validate center is active
        if (!$center->is_active) {
            abort(403, 'Center is not active');
        }

        // Validate center belongs to user's tenant
        if ($center->tenant_id !== $user->tenant_id) {
            abort(403, 'Center does not belong to user tenant');
        }

        // Validate user is assigned to this center via pivot
        // Super Admin bypass: Allow access to any center in the tenant
        $isAssigned = $user->centers()->where('center_id', $currentCenterId)->exists();
        
        if (!$isAssigned && !$user->hasRole('super_admin')) {
            abort(403, 'User is not assigned to this center');
        }

        return $next($request);
    }
}
