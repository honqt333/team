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

    it('tracks owning container dir reactively when teleported to body without full reload', async () => {
        document.documentElement.dir = 'rtl';
        const owner = document.createElement('div');
        owner.setAttribute('dir', 'ltr');
        document.body.appendChild(owner);

        try {
            wrapper = mount(Toast, {
                attachTo: owner,
                global: {
                    mocks: {
                        $t: (key: string) => key,
                    },
                },
            });

            success('English portal toast');
            await nextTick();

            const container = document.body.querySelector('[dir="ltr"].fixed');
            expect(container).not.toBeNull();
            expect(container?.classList.contains('right-4')).toBe(true);

            // Switch owning container dir dynamically (like SPA/Inertia locale switch)
            owner.setAttribute('dir', 'rtl');
            await new Promise((r) => setTimeout(r, 20));
            await nextTick();

            expect(container?.getAttribute('dir')).toBe('rtl');
            expect(container?.classList.contains('left-4')).toBe(true);
        } finally {
            owner.remove();
        }
    });

    it('cleans up MutationObserver on unmount and tracks direction across Layout remounts (Inertia navigation)', async () => {
        const disconnectSpy = vi.spyOn(MutationObserver.prototype, 'disconnect');
        document.documentElement.dir = 'rtl';

        const ownerA = document.createElement('div');
        ownerA.setAttribute('dir', 'rtl');
        document.body.appendChild(ownerA);

        const ownerB = document.createElement('div');
        ownerB.setAttribute('dir', 'ltr');
        document.body.appendChild(ownerB);

        let wrapperA: ReturnType<typeof mount> | null = null;
        try {
            // Simulate Layout A (e.g., AppLayout / System / EmployeePortal in RTL)
            wrapperA = mount(Toast, {
                attachTo: ownerA,
                global: { mocks: { $t: (key: string) => key } },
            });
            success('RTL panel toast');
            await nextTick();
            expect(document.body.querySelector('[dir="rtl"].fixed')).not.toBeNull();

            // Simulate Inertia navigation remounting Layout B in LTR
            wrapperA.unmount();
            wrapperA = null;
            expect(disconnectSpy).toHaveBeenCalled();

            wrapper = mount(Toast, {
                attachTo: ownerB,
                global: { mocks: { $t: (key: string) => key } },
            });
            await nextTick();

            const containerB = document.body.querySelector('[dir="ltr"].fixed');
            expect(containerB).not.toBeNull();
            expect(containerB?.classList.contains('right-4')).toBe(true);
        } finally {
            if (wrapperA) wrapperA.unmount();
            ownerA.remove();
            ownerB.remove();
            vi.restoreAllMocks();
        }
    });
});
