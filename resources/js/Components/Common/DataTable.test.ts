import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import DataTable from './DataTable.vue';

describe('DataTable', () => {
    const mockColumns = [
        { key: 'sku', label: 'SKU', sortable: true },
        { key: 'name', label: 'Item Name', sortable: true },
        { key: 'price', label: 'Price', align: 'end' },
    ];

    const mockData = [
        { id: 1, sku: 'SKU-001', name: 'Oil Filter', price: '25.00' },
        { id: 2, sku: 'SKU-002', name: 'Brake Pad', price: '80.00' },
    ];

    it('renders table columns and rows correctly', () => {
        const wrapper = mount(DataTable, {
            props: {
                columns: mockColumns,
                data: mockData,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: true,
                },
            },
        });

        expect(wrapper.exists()).toBe(true);
        expect(wrapper.findAll('th').length).toBe(3);
        expect(wrapper.findAll('tbody tr').length).toBe(2);
        expect(wrapper.text()).toContain('Oil Filter');
        expect(wrapper.text()).toContain('Brake Pad');
    });

    it('emits sort event when clicking sortable column', async () => {
        const wrapper = mount(DataTable, {
            props: {
                columns: mockColumns,
                data: mockData,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: true,
                },
            },
        });

        const sortableTh = wrapper.get('th:nth-child(1)'); // SKU is sortable
        await sortableTh.trigger('click');

        expect(wrapper.emitted('sort')).toBeTruthy();
        expect(wrapper.emitted('sort')?.[0]).toEqual(['sku']);
    });

    it('does not emit sort event when clicking non-sortable column', async () => {
        const wrapper = mount(DataTable, {
            props: {
                columns: mockColumns,
                data: mockData,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: true,
                },
            },
        });

        const nonSortableTh = wrapper.get('th:nth-child(3)'); // price is not sortable
        await nonSortableTh.trigger('click');

        expect(wrapper.emitted('sort')).toBeFalsy();
    });

    it('renders scoped cell slots properly', () => {
        const wrapper = mount(DataTable, {
            props: {
                columns: mockColumns,
                data: mockData,
            },
            slots: {
                'cell-name': `<template #cell-name="{ value }"><span class="badge">{{ value }}</span></template>`,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: true,
                },
            },
        });

        expect(wrapper.findAll('.badge').length).toBe(2);
        expect(wrapper.find('.badge').text()).toBe('Oil Filter');
    });

    it('renders empty state when data array is empty', () => {
        const wrapper = mount(DataTable, {
            props: {
                columns: mockColumns,
                data: [],
                emptyTitle: 'No records found',
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: true,
                },
            },
        });

        expect(wrapper.text()).toContain('No records found');
    });
});
