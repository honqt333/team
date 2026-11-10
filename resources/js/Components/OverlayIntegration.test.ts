import { afterEach, describe, expect, it } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { nextTick } from 'vue';
import Modal from './Modal.vue';
import Toast from './Toast.vue';
import Tooltip from './Tooltip.vue';
import { useToast } from '@/Composables/useToast';

const wrappers: ReturnType<typeof mount>[] = [];
afterEach(() => {
    wrappers
        .splice(0)
        .reverse()
        .forEach((wrapper) => wrapper.unmount());
    useToast().clear();
    document.body.innerHTML = '';
});
describe('overlays inside native dialogs', () => {
    it('moves notifications to the top dialog and returns them to body on unmount', async () => {
        const toast = mount(Toast, { attachTo: document.body });
        wrappers.push(toast);
        await flushPromises();
        useToast().error('Visible error', { duration: 0 });
        await nextTick();
        const modal = mount(Modal, {
            attachTo: document.body,
            props: { show: true },
            slots: { default: '<h2>Edit</h2>' },
        });
        wrappers.push(modal);
        await flushPromises();
        expect(modal.get('dialog').text()).toContain('Visible error');
        modal.unmount();
        wrappers.splice(wrappers.indexOf(modal), 1);
        await flushPromises();
        expect(document.body.textContent).toContain('Visible error');
        expect(document.querySelector('dialog')).toBeNull();
    });
    it('renders tooltip content in the active dialog', async () => {
        const modal = mount(Modal, {
            attachTo: document.body,
            props: { show: true },
            slots: { default: '<h2>Edit</h2>' },
        });
        wrappers.push(modal);
        await flushPromises();
        const tooltip = mount(Tooltip, {
            attachTo: document.body,
            props: { text: 'Help' },
            slots: { default: '<button>Info</button>' },
        });
        wrappers.push(tooltip);
        await tooltip.get('.inline-flex').trigger('mouseenter');
        await flushPromises();
        expect(modal.get('dialog').text()).toContain('Help');
    });
});
