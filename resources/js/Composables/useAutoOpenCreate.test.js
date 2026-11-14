import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, nextTick } from 'vue';
import { useAutoOpenCreate } from './useAutoOpenCreate';

describe('useAutoOpenCreate', () => {
    beforeEach(() => {
        window.history.replaceState({}, '', '/app/customers');
    });

    it('invokes callback and strips create=1 from URL when ?create=1 is present', async () => {
        window.history.replaceState({}, '', '/app/customers?create=1&page=2');
        const onCreate = vi.fn();

        const TestComp = defineComponent({
            setup() {
                useAutoOpenCreate(onCreate);
                return () => null;
            },
        });

        mount(TestComp);
        await nextTick();

        expect(onCreate).toHaveBeenCalledTimes(1);
        expect(window.location.search).toBe('?page=2');
    });

    it('does not invoke callback when ?create=1 is absent', async () => {
        window.history.replaceState({}, '', '/app/customers?page=2');
        const onCreate = vi.fn();

        const TestComp = defineComponent({
            setup() {
                useAutoOpenCreate(onCreate);
                return () => null;
            },
        });

        mount(TestComp);
        await nextTick();

        expect(onCreate).not.toHaveBeenCalled();
        expect(window.location.search).toBe('?page=2');
    });
});
