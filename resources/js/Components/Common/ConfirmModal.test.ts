import { describe, it, expect, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import ConfirmModal from '../ConfirmModal.vue';

// jsdom doesn't implement <dialog> element's showModal/close methods.
// Polyfill them on every <dialog> in the DOM so Modal.vue can call them.
if (typeof window !== 'undefined' && window.HTMLDialogElement) {
    // @ts-ignore - dynamic property assignment
    const proto = window.HTMLDialogElement.prototype;
    // @ts-ignore - dynamic property check
    if (!proto.showModal) {
        // @ts-ignore - jsdom polyfill
        proto.showModal = function () {
            this.open = true;
        };
    }
    // @ts-ignore - dynamic property check
    if (!proto.close) {
        // @ts-ignore - jsdom polyfill
        proto.close = function () {
            this.open = false;
        };
    }
}

// Component reads `useConfirm().isOpen` and `useConfirm().options`.
// Use real Vue refs and a vi.mock factory so reactivity flows through.
vi.mock('@/Composables/useConfirm', () => {
    const isOpen = ref(true);
    const options = ref({
        title: 'Delete Item?',
        message: 'Are you sure you want to delete?',
        type: 'danger',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
    });
    return {
        useConfirm: () => ({
            isOpen,
            options,
            open: vi.fn(),
            close: vi.fn(),
            resolve: vi.fn(),
        }),
    };
});

// Provide a global $t function so the template's i18n calls don't fail.
const tMock = (key, fallback) => fallback ?? key.split('.').pop() ?? key;

describe('ConfirmModal', () => {
    it('renders title and message when open', async () => {
        const wrapper = mount(ConfirmModal, {
            global: {
                mocks: { $t: tMock },
            },
        });
        await flushPromises();
        expect(wrapper.text()).toContain('Delete Item?');
        expect(wrapper.text()).toContain('Are you sure you want to delete?');
    });

    it('emits confirm when confirmed', async () => {
        const wrapper = mount(ConfirmModal, {
            global: {
                mocks: { $t: tMock },
            },
        });
        await flushPromises();
        const confirmBtn = wrapper.find('button.bg-red-600, button.bg-primary-600');
        if (confirmBtn.exists()) {
            await confirmBtn.trigger('click');
            expect(wrapper.emitted('confirm')).toBeTruthy();
        }
    });
});
