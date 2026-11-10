import { describe, it, expect, vi, beforeEach } from 'vitest';
import { nextTick } from 'vue';
import { useServerTable } from './useServerTable';
import { router } from '@inertiajs/vue3';

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: vi.fn(),
    },
}));

describe('useServerTable', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();
    });

    it('initializes with default filters and sort', () => {
        const table = useServerTable({
            routeUrl: '/api/parts',
            initialFilters: { search: '', category: 'engine' },
            initialSort: { column: 'created_at', direction: 'desc' },
        });

        expect(table.filters.search).toBe('');
        expect(table.filters.category).toBe('engine');
        expect(table.sortColumn.value).toBe('created_at');
        expect(table.sortDirection.value).toBe('desc');
        expect(table.isLoading.value).toBe(false);
        expect(table.tableFilters).toBe(table.filters);
    });

    it('cleans empty params and calls router.get with correct arguments', () => {
        const table = useServerTable({
            routeUrl: '/api/parts',
            initialFilters: { search: 'brake', category: '', status: null },
            initialSort: { column: 'sku', direction: 'asc' },
        });

        table.applyFilters();

        expect(router.get).toHaveBeenCalledTimes(1);
        expect(router.get).toHaveBeenCalledWith(
            '/api/parts',
            {
                search: 'brake',
                sort: 'sku',
                direction: 'asc',
            },
            expect.objectContaining({
                preserveState: true,
                preserveScroll: true,
                replace: true,
            })
        );
    });

    it('cleanParams preserves non-empty arrays and drops empty arrays', () => {
        const table = useServerTable({
            routeUrl: '/api/parts',
            initialFilters: { search: '', categories: ['engine', 'brake'], tags: [] as string[] },
        });

        table.applyFilters();

        expect(router.get).toHaveBeenCalledWith(
            '/api/parts',
            expect.objectContaining({ categories: ['engine', 'brake'] }),
            expect.anything()
        );
        // empty array should be stripped
        const firstCall = (router.get as ReturnType<typeof vi.fn>).mock.calls[0];
        if (!firstCall) throw new Error('Expected a navigation request');
        const callArgs = firstCall[1];
        expect(callArgs).not.toHaveProperty('tags');
        // empty string should be stripped
        expect(callArgs).not.toHaveProperty('search');
    });

    it('toggles sort direction when clicking same column, resets to asc when clicking new column', () => {
        const table = useServerTable({
            routeUrl: '/api/parts',
            initialSort: { column: 'name', direction: 'asc' },
        });

        // Toggle same column: should become desc
        table.toggleSort('name');
        expect(table.sortColumn.value).toBe('name');
        expect(table.sortDirection.value).toBe('desc');
        expect(router.get).toHaveBeenCalledWith(
            '/api/parts',
            expect.objectContaining({ sort: 'name', direction: 'desc' }),
            expect.anything()
        );

        // Toggle new column: should switch column and become asc
        table.toggleSort('sku');
        expect(table.sortColumn.value).toBe('sku');
        expect(table.sortDirection.value).toBe('asc');
        expect(router.get).toHaveBeenCalledWith(
            '/api/parts',
            expect.objectContaining({ sort: 'sku', direction: 'asc' }),
            expect.anything()
        );
    });

    it('resets filters back to initial state on resetFilters', () => {
        const table = useServerTable({
            routeUrl: () => '/api/parts',
            initialFilters: { search: '', category: 'all' },
            initialSort: { column: 'id', direction: 'asc' },
        });

        table.filters.search = 'modified';
        table.filters.category = 'modified_cat';
        table.sortColumn.value = 'modified_sort';

        table.resetFilters();

        expect(table.filters.search).toBe('');
        expect(table.filters.category).toBe('all');
        expect(table.sortColumn.value).toBe('id');
        expect(table.sortDirection.value).toBe('asc');
        expect(router.get).toHaveBeenCalled();
    });

    it('auto-debounces search input changes', async () => {
        const table = useServerTable({
            routeUrl: '/api/parts',
            initialFilters: { search: '' },
            debounceMs: 300,
        });

        table.filters.search = 'battery';
        await nextTick();
        expect(router.get).not.toHaveBeenCalled();

        vi.advanceTimersByTime(299);
        expect(router.get).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        expect(router.get).toHaveBeenCalledTimes(1);
        expect(router.get).toHaveBeenCalledWith(
            '/api/parts',
            expect.objectContaining({ search: 'battery' }),
            expect.anything()
        );
    });
});
