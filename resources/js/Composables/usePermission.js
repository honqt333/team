import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    /**
     * Check if user has a specific permission
     * @param {string} permission 
     * @returns {boolean}
     */
    function hasRole(role) {
        const user = usePage().props.auth.user;
        if (!user) return false;
        
        // System admin bypass
        if (user.is_system_admin) return true;
        
        const roles = user.roles || [];
        return roles.some(r => {
            const roleName = typeof r === 'object' ? r.name : r;
            return roleName === role;
        });
    }

    function isAnyAdmin() {
        const user = usePage().props.auth.user;
        if (!user) return false;
        
        // Emergency bypass for main admin
        if (user.email === 'admin@khidmh.pro' || user.is_system_admin) return true;
        
        const roles = user.roles || [];
        return roles.some(r => {
            const name = typeof r === 'object' ? r.name : r;
            return ['super_admin', 'admin', 'owner'].includes(name);
        });
    }

    function can(permission) {
        if (isAnyAdmin()) return true;
        const permissions = usePage().props.auth.permissions || [];
        return permissions.includes(permission);
    }

    function canAny(permissions) {
        if (isAnyAdmin()) return true;
        const userPermissions = usePage().props.auth.permissions || [];
        return permissions.some(p => userPermissions.includes(p));
    }

    function canAll(permissions) {
        if (isAnyAdmin()) return true;
        const userPermissions = usePage().props.auth.permissions || [];
        return permissions.every(p => userPermissions.includes(p));
    }

    return {
        can,
        canAny,
        canAll,
        hasRole
    };
}

