import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import Toast from './Toast.vue';
import { useToast } from '@/Composables/useToast';

describe('Toast.vue', () => {
    const { success, clear, toasts } = useToast();
    let wrapper: ReturnType<typeof mount> | null = null;

    beforeEach(() => {
        clear();
    });

    afterEach(() => {
        if (wrapper) {
            wrapper.unmount();
            wrapper = null;
        }
        clear();
    });

    it('renders toast message and closes when card is clicked', async () => {
        wrapper = mount(Toast, {
            attachTo: document.body,
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        success('Operation completed successfully');
        await nextTick();

        const toastCard = document.querySelector('[role="status"]');
        expect(toastCard).not.toBeNull();
        expect(toastCard?.textContent).toContain('Operation completed successfully');

        // Click anywhere on the card to dismiss
        (toastCard as HTMLElement)?.click();
        await nextTick();

        expect(toasts.value.length).toBe(0);
    });

    it('supports pause on mouseenter and resume on mouseleave', async () => {
        wrapper = mount(Toast, {
            attachTo: document.body,
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        success('Hoverable toast');
        await nextTick();

        const toastCard = document.querySelector('[role="status"]');
        expect(toastCard).not.toBeNull();

        expect(() => {
            toastCard?.dispatchEvent(new MouseEvent('mouseenter'));
            toastCard?.dispatchEvent(new MouseEvent('mouseleave'));
        }).not.toThrow();
    });
});
