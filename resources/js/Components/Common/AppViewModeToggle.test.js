import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AppViewModeToggle from './AppViewModeToggle.vue';

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key) => key }),
}));

function mockMatchMedia(initialMatches) {
    let matches = initialMatches;
    const listeners = new Set();

    const mql = {
        get matches() {
            return matches;
        },
        media: '(max-width: 767px)',
        addEventListener: vi.fn((_, cb) => listeners.add(cb)),
        removeEventListener: vi.fn((_, cb) => listeners.delete(cb)),
    };

    vi.stubGlobal(
        'matchMedia',
        vi.fn(() => mql)
    );

    return {
        trigger(nextMatches) {
            matches = nextMatches;
            listeners.forEach((cb) => cb({ matches: nextMatches }));
        },
    };
}

describe('AppViewModeToggle', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.restoreAllMocks();
    });

    it('hides the toggle on mobile via responsive classes', () => {
        mockMatchMedia(true);
        const wrapper = mount(AppViewModeToggle, {
            props: { modelValue: 'grid' },
        });

        expect(wrapper.classes()).toContain('hidden');
        expect(wrapper.classes()).toContain('md:inline-flex');
    });

    it('forces grid mode on mobile even when list is saved in localStorage', () => {
        localStorage.setItem('view_mode_customers', 'list');
        mockMatchMedia(true);

        const wrapper = mount(AppViewModeToggle, {
            props: { modelValue: 'list', storageKey: 'customers' },
        });

        expect(wrapper.emitted('update:modelValue')).toEqual([['grid']]);
        expect(localStorage.getItem('view_mode_customers')).toBe('list');
    });

    it('restores saved list mode on desktop and switches back and forth on resize', async () => {
        localStorage.setItem('view_mode_customers', 'list');
        const mq = mockMatchMedia(false);

        const wrapper = mount(AppViewModeToggle, {
            props: { modelValue: 'grid', storageKey: 'customers' },
        });

        expect(wrapper.emitted('update:modelValue')).toEqual([['list']]);

        await wrapper.setProps({ modelValue: 'list' });
        mq.trigger(true);
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['grid']);

        await wrapper.setProps({ modelValue: 'grid' });
        mq.trigger(false);
        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['list']);
    });
});
