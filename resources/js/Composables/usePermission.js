import { usePage } from '@inertiajs/vue3';

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

    return {
        can,
        canAny,
        canAll,
        hasRole
    };
}

