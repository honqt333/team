<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeam
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            // Set the team id for permissions based on the user's tenant
            if ($user->tenant_id) {
                $registrar = app(PermissionRegistrar::class);
                
                // Reset cached permissions to ensure fresh check
                $registrar->forgetCachedPermissions();
                $registrar->setPermissionsTeamId($user->tenant_id);
                
                // Force reload user roles
                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');
                
                // Debug: log after setting
                Log::channel('single')->debug('SetPermissionsTeam', [
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'team_id_set' => $registrar->getPermissionsTeamId(),
                    'roles' => $user->getRoleNames()->toArray(),
                    'can_quotes_view' => $user->can('quotes.view'),
                ]);
            }
        }

        return $next($request);
    }
}
