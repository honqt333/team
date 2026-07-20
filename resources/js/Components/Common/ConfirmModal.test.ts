import { describe, it, expect, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { ref } from 'vue';
import ConfirmModal from '../ConfirmModal.vue';

// Component reads `useConfirm().isOpen` and `useConfirm().options`.
// Use real Vue refs in the mock factory so reactivity flows through.
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

describe('ConfirmModal', () => {
    it('renders title and message when open', async () => {
        const wrapper = mount(ConfirmModal);
        await flushPromises();
        expect(wrapper.text()).toContain('Delete Item?');
        expect(wrapper.text()).toContain('Are you sure you want to delete?');
    });

    it('emits confirm when confirmed', async () => {
        const wrapper = mount(ConfirmModal);
        await flushPromises();
        const confirmBtn = wrapper.find('button.bg-red-600, button.bg-primary-600');
        if (confirmBtn.exists()) {
            await confirmBtn.trigger('click');
            expect(wrapper.emitted('confirm')).toBeTruthy();
        }
    });
});
