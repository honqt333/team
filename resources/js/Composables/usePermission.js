import { usePage } from '@inertiajs/vue3';
// Force sync comment


export function usePermission() {
    /**
     * Check if user has a specific permission
     * @param {string} permission 
     * @returns {boolean}
     */
    function can(permission) {
        const permissions = usePage().props.auth.permissions || [];
        return permissions.includes(permission);
    }

    /**
     * Check if user has ANY of the given permissions
     * @param {string[]} permissions 
     * @returns {boolean}
     */
    function canAny(permissions) {
        const userPermissions = usePage().props.auth.permissions || [];
        return permissions.some(p => userPermissions.includes(p));
    }

    /**
     * Check if user has ALL of the given permissions
     * @param {string[]} permissions 
     * @returns {boolean}
     */
    function canAll(permissions) {
        const userPermissions = usePage().props.auth.permissions || [];
        return permissions.every(p => userPermissions.includes(p));
    }

    /**
     * Check if user has a specific role
     * @param {string} role 
     * @returns {boolean}
     */
    function hasRole(role) {
        const roles = usePage().props.auth.user?.roles || [];
        return roles.some(r => r.name === role || r === role);
    }

    /**
     * Check if the user can reach the /system/* management panel.
     *
     * This MUST mirror the server-side check in
     * `App\Http\Middleware\EnsureSystemAdmin` — otherwise a tenant
     * owner (with the `super_admin` role) would see a button that
     * leads to a 403. Only the following identities can see the
     * button:
     *   - User with is_system_admin = true
     *
     * The tenant-scope `super_admin` role grants full permissions
     * within that tenant; it does NOT grant cross-tenant / system
     * access.
     */
    function isAnyAdmin() {
        const user = usePage().props.auth.user;
        return Boolean(user?.is_system_admin);
    }

    return {
        can,
        canAny,
        canAll,
        hasRole,
        isAnyAdmin
    };
}

