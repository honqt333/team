import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import Tooltip from './Tooltip.vue';
import { registerModal, unregisterModal } from '@/Support/modalStack';

describe('Tooltip.vue', () => {
    let wrapper: ReturnType<typeof mount> | null = null;
    let owner: HTMLElement | null = null;

    beforeEach(() => {
        document.documentElement.dir = 'rtl';
    });

    afterEach(() => {
        if (wrapper) {
            wrapper.unmount();
            wrapper = null;
        }
        if (owner) {
            owner.remove();
            owner = null;
        }
        vi.restoreAllMocks();
    });

    it('teleports to body and inherits RTL and LTR from owning container', async () => {
        owner = document.createElement('div');
        owner.setAttribute('dir', 'ltr');
        document.body.appendChild(owner);

        wrapper = mount(Tooltip, {
            attachTo: owner,
            props: { text: 'LTR tooltip' },
            slots: { default: '<button type="button">Hover</button>' },
        });

        await wrapper.find('.inline-flex').trigger('mouseenter');
        await nextTick();

        const tooltipEl = document.body.querySelector('.fixed.z-\\[9999\\]') as HTMLElement | null;
        expect(tooltipEl).not.toBeNull();
        expect(tooltipEl?.textContent).toContain('LTR tooltip');
        expect(tooltipEl?.getAttribute('dir')).toBe('ltr');

        // Change owning container dir while mounted
        owner.setAttribute('dir', 'rtl');
        await new Promise((r) => setTimeout(r, 20));
        await nextTick();

        expect(tooltipEl?.getAttribute('dir')).toBe('rtl');
    });

    it('teleports inside active Modal and cleans up MutationObserver on unmount', async () => {
        const disconnectSpy = vi.spyOn(MutationObserver.prototype, 'disconnect');

        const modalEl = document.createElement('dialog');
        modalEl.setAttribute('dir', 'ltr');
        document.body.appendChild(modalEl);
        registerModal(modalEl, null);

        try {
            owner = document.createElement('div');
            owner.setAttribute('dir', 'rtl');
            document.body.appendChild(owner);

            wrapper = mount(Tooltip, {
                attachTo: owner,
                props: { text: 'Modal tooltip' },
                slots: { default: '<button type="button">Info</button>' },
            });

            await wrapper.find('.inline-flex').trigger('mouseenter');
            await nextTick();

            const inModal = modalEl.querySelector('[dir="ltr"].fixed');
            expect(inModal).not.toBeNull();
            expect(inModal?.textContent).toContain('Modal tooltip');

            wrapper.unmount();
            wrapper = null;
            expect(disconnectSpy).toHaveBeenCalled();
        } finally {
            unregisterModal(modalEl);
            modalEl.remove();
        }
    });
});
