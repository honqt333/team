/**
 * Shared formatting helpers used across WorkOrder views, modals, and
 * dashboard widgets. Replaces the per-component `formatDate`,
 * `formatDateTime`, `formatPrice`, `formatFileSize`, `getUserRoleName`,
 * `getNoteDate`, and `getNoteTime` definitions that were duplicated in
 * Show.vue, PaymentsListModal.vue, CustomerImportModal.vue, etc.
 *
 * Use it like:
 *   import { useFormatters } from '@/Composables/useFormatters';
 *   const { formatDate, formatPrice } = useFormatters();
 */
export function useFormatters() {
    /**
     * Format a date for display in the user's locale.
     *
     * Accepts ISO strings (`YYYY-MM-DD` or full datetime) and Date objects.
     * For pure date strings we parse manually to avoid the JS Date
     * timezone shift that turns `2026-06-15` into the previous day in
     * negative-UTC locales.
     *
     * @param {string|Date|null|undefined} date
     * @returns {string} Formatted `DD/MM/YYYY` or '-' when input is empty
     */
    function formatDate(date) {
        if (!date) return '-';

        // Manual parsing for ISO date strings to avoid timezone issues.
        if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(date)) {
            const parts = date.split('T')[0].split('-');
            const day = parts[2];
            const month = parts[1];
            const year = parts[0];
            return `${day}/${month}/${year}`;
        }

        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    }

    /**
     * Format a datetime for display.
     *
     * @param {string|Date|null|undefined} date
     * @returns {string} Formatted `DD/MM/YYYY HH:mm` or '-' when input is empty
     */
    function formatDateTime(date) {
        if (!date) return '-';
        const d = new Date(date);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    /**
     * Convert a byte count into a human-readable size (Bytes / KB / MB).
     *
     * @param {number} bytes
     * @returns {string}
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Map a role name to a human-friendly label.
     *
     * Kept here (not as a single file-scoped helper) because dashboard,
     * notes list, and activity feeds all render role badges the same way.
     * Falls back to the raw role name when unknown, and to "Staff" when
     * the user has no roles at all.
     *
     * @param {{roles?: Array<{name: string}>, is_system_admin?: boolean}|null|undefined} user
     * @returns {string}
     */
    function getUserRoleName(user) {
        if (!user) return '';
        if (user.is_system_admin) return 'System Admin';
        if (user.roles && user.roles.length > 0) {
            const roleName = user.roles[0].name;
            const rolesMap = {
                super_admin: 'Super Admin',
                business_owner: 'Business Owner',
                admin: 'Admin',
                manager: 'Manager',
                technician: 'Technician',
                receptionist: 'Receptionist',
            };
            return rolesMap[roleName] || roleName;
        }
        return 'Staff';
    }

    /**
     * Format a timestamp as `YYYY-MM-DD`. Used by the notes list to show
     * a sortable date in the meta column.
     *
     * @param {string|Date|null|undefined} createdAt
     * @returns {string}
     */
    function getNoteDate(createdAt) {
        if (!createdAt) return '';
        const d = new Date(createdAt);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${year}-${month}-${day}`;
    }

    /**
     * Format a timestamp as `HH:mm:ss`. Companion to `getNoteDate`.
     *
     * @param {string|Date|null|undefined} createdAt
     * @returns {string}
     */
    function getNoteTime(createdAt) {
        if (!createdAt) return '';
        const d = new Date(createdAt);
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        return `${hours}:${minutes}:${seconds}`;
    }

    return {
        formatDate,
        formatDateTime,
        formatFileSize,
        getUserRoleName,
        getNoteDate,
        getNoteTime,
    };
}
