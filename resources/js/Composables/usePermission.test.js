import { describe, it, expect, vi } from 'vitest';
import { usePermission } from './usePermission';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            auth: {
                permissions: ['customers.view', 'customers.create'],
            },
        },
    }),
}));

describe('usePermission', () => {
    const { can, canAny, canAll } = usePermission();

    it('can() returns true for granted permission', () => {
        expect(can('customers.view')).toBe(true);
        expect(can('customers.delete')).toBe(false);
    });

    it('canAny() returns true if any granted', () => {
        expect(canAny(['customers.view', 'customers.delete'])).toBe(true);
        expect(canAny(['customers.delete', 'customers.export'])).toBe(false);
    });

    it('canAll() returns true if all granted', () => {
        expect(canAll(['customers.view', 'customers.create'])).toBe(true);
        expect(canAll(['customers.view', 'customers.delete'])).toBe(false);
    });
});
