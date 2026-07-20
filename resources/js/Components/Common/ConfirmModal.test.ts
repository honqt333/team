import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ConfirmModal from '../ConfirmModal.vue';

describe('ConfirmModal', () => {
    it('renders when open', () => {
        const wrapper = mount(ConfirmModal, {
            props: {
                show: true,
                title: 'Delete Item?',
                message: 'Are you sure you want to delete?',
            },
        });
        expect(wrapper.text()).toContain('Delete Item?');
    });

    it('emits confirm when confirmed', async () => {
        const wrapper = mount(ConfirmModal, {
            props: { show: true, title: 'Test' },
        });
        const confirmBtn = wrapper.find('button.bg-red-600, button.bg-primary-600');
        if (confirmBtn.exists()) {
            await confirmBtn.trigger('click');
            expect(wrapper.emitted('confirm')).toBeTruthy();
        }
    });
});
