import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppPagination from './AppPagination.vue';

describe('AppPagination', () => {
    const mockLinks = [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/items?page=1', label: '1', active: true },
        { url: '/items?page=2', label: '2', active: false },
        { url: '/items?page=3', label: '3', active: false },
        { url: '/items?page=2', label: 'Next &raquo;', active: false },
    ];

    it('renders pagination links when links length > 3', () => {
        const wrapper = mount(AppPagination, {
            props: {
                links: mockLinks,
                from: 1,
                to: 15,
                total: 45,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: {
                        template: '<a :href="href" :class="$attrs.class"><slot /></a>',
                        props: ['href'],
                    },
                },
            },
        });

        expect(wrapper.exists()).toBe(true);
        expect(wrapper.text()).toContain('45');
        expect(wrapper.findAll('a').length).toBe(4); // 3 numbered pages + 1 next link
    });

    it('renders pagination when links length is exactly 3 (single page)', () => {
        const wrapper = mount(AppPagination, {
            props: {
                links: [
                    { url: null, label: 'Prev', active: false },
                    { url: '/items?page=1', label: '1', active: true },
                    { url: null, label: 'Next', active: false },
                ],
                total: 10,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: {
                        template: '<a :href="href" :class="$attrs.class"><slot /></a>',
                        props: ['href'],
                    },
                },
            },
        });

        // With >= 3 fix: single page (3 links) DOES render the nav container
        expect(wrapper.find('nav').exists()).toBe(true);
    });

    it('does not render when links array is empty or fewer than 3', () => {
        const wrapper = mount(AppPagination, {
            props: {
                links: [],
                total: 0,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
            },
        });

        expect(wrapper.find('nav').exists()).toBe(false);
    });

    it('marks active link correctly with action-primary', () => {
        const wrapper = mount(AppPagination, {
            props: {
                links: mockLinks,
                total: 45,
            },
            global: {
                mocks: {
                    $t: (key: string, fallback: string) => fallback || key,
                },
                stubs: {
                    Link: {
                        template: '<a :href="href" :class="$attrs.class"><slot /></a>',
                        props: ['href'],
                    },
                },
            },
        });

        const activeLink = wrapper
            .findAll('a')
            .find((el) => el.classes().includes('bg-action-primary'));
        expect(activeLink).toBeDefined();
        expect(activeLink?.classes()).toContain('text-action-on');
    });
});
