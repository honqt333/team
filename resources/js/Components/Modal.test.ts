import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, nextTick, type Component } from 'vue';
import Modal from './Modal.vue';
import BaseModal from './BaseModal.vue';
import DialogModal from './DialogModal.vue';

const wrappers: ReturnType<typeof mount>[] = [];
function open(component: Component = Modal, props = {}, slots = {}) {
    const wrapper = mount(component, {
        attachTo: document.body,
        props: { show: true, ...props },
        slots: { default: '<h2>Details</h2><input autofocus /><button>Last</button>', ...slots },
    });
    wrappers.push(wrapper);
    return wrapper;
}
function key(name: string, options = {}) {
    const event = new KeyboardEvent('keydown', {
        key: name,
        bubbles: true,
        cancelable: true,
        ...options,
    });
    document.dispatchEvent(event);
    return event;
}
afterEach(() => {
    wrappers
        .splice(0)
        .reverse()
        .forEach((wrapper) => wrapper.unmount());
    document.body.innerHTML = '';
    document.body.style.overflow = '';
    vi.useRealTimers();
});

describe('shared modal behavior', () => {
    it('opens when mounted visible, names the dialog and focuses autofocus', async () => {
        const wrapper = open();
        await flushPromises();
        const dialog = wrapper.get('dialog').element as HTMLDialogElement;
        expect(dialog.open).toBe(true);
        expect(dialog.getAttribute('aria-modal')).toBe('true');
        expect(document.getElementById(dialog.getAttribute('aria-labelledby')!)?.textContent).toBe(
            'Details'
        );
        expect(document.activeElement).toBe(wrapper.get('input').element);
        expect(document.body.style.overflow).toBe('hidden');
        key('Escape');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('names a DialogModal from its title slot', async () => {
        const wrapper = open(DialogModal, {}, { title: 'Edit supplier' });
        await flushPromises();
        const labelId = wrapper.get('dialog').attributes('aria-labelledby');
        expect(document.getElementById(labelId ?? '')?.textContent).toBe('Edit supplier');
    });

    it('wraps Tab and Shift+Tab at dialog boundaries', async () => {
        const wrapper = open();
        await flushPromises();
        wrapper.get('button').element.focus();
        expect(key('Tab').defaultPrevented).toBe(true);
        expect(document.activeElement).toBe(wrapper.get('input').element);
        key('Tab', { shiftKey: true });
        expect(document.activeElement).toBe(wrapper.get('button').element);
    });

    it('respects non-closeable dialogs and handled inner Escape events', async () => {
        const wrapper = open(Modal, { closeable: false });
        await flushPromises();
        key('Escape');
        const cancel = new Event('cancel', { cancelable: true });
        wrapper.get('dialog').element.dispatchEvent(cancel);
        expect(cancel.defaultPrevented).toBe(true);
        expect(wrapper.emitted('close')).toBeUndefined();
        await wrapper.setProps({ closeable: true });
        const event = new KeyboardEvent('keydown', { key: 'Escape', cancelable: true });
        event.preventDefault();
        document.dispatchEvent(event);
        expect(wrapper.emitted('close')).toBeUndefined();
    });

    it('only closes the top dialog and retains the parent scroll lock and focus', async () => {
        const opener = document.createElement('button');
        document.body.append(opener);
        opener.focus();
        document.body.style.overflow = 'clip';
        const parent = open();
        await flushPromises();
        const parentInput = parent.get('input').element;
        const child = open(BaseModal);
        await flushPromises();
        key('Escape');
        expect(child.emitted('close')).toHaveLength(1);
        expect(parent.emitted('close')).toBeUndefined();
        child.unmount();
        wrappers.splice(wrappers.indexOf(child), 1);
        expect(document.body.style.overflow).toBe('hidden');
        expect(document.activeElement).toBe(parentInput);
        parent.unmount();
        wrappers.splice(wrappers.indexOf(parent), 1);
        expect(document.body.style.overflow).toBe('clip');
        expect(document.activeElement).toBe(opener);
        key('Escape');
        expect(parent.emitted('close')).toBeUndefined();
    });

    it('opens a child above its ancestor when both are initially visible', async () => {
        const Host = defineComponent({
            components: { BaseModal },
            template: `<BaseModal show title="Parent"><BaseModal show title="Child" /></BaseModal>`,
        });
        const wrapper = mount(Host, { attachTo: document.body });
        wrappers.push(wrapper);
        await flushPromises();
        const modals = wrapper.findAllComponents(BaseModal);
        key('Escape');
        const [parent, child] = modals;
        if (!parent || !child) throw new Error('Both nested dialogs must be mounted');
        expect(parent.emitted('close')).toBeUndefined();
        expect(child.emitted('close')).toHaveLength(1);
    });

    it('restores the outside opener when an ancestor is removed before a child', async () => {
        const opener = document.createElement('button');
        document.body.append(opener);
        opener.focus();
        const parent = open();
        await flushPromises();
        const child = open();
        await flushPromises();
        parent.unmount();
        wrappers.splice(wrappers.indexOf(parent), 1);
        expect(document.body.style.overflow).toBe('hidden');
        child.unmount();
        wrappers.splice(wrappers.indexOf(child), 1);
        expect(document.activeElement).toBe(opener);
        expect(document.body.style.overflow).toBe('');
    });

    it('does not let a delayed close dismiss a rapidly reopened dialog', async () => {
        vi.useFakeTimers();
        const wrapper = open();
        await nextTick();
        await nextTick();
        await wrapper.setProps({ show: false });
        await wrapper.setProps({ show: true });
        await nextTick();
        await vi.advanceTimersByTimeAsync(250);
        expect((wrapper.get('dialog').element as HTMLDialogElement).open).toBe(true);
        expect(wrapper.find('input').exists()).toBe(true);
        expect(document.body.style.overflow).toBe('hidden');
    });

    it('restores scroll and focus after the closing transition', async () => {
        vi.useFakeTimers();
        const opener = document.createElement('button');
        document.body.append(opener);
        opener.focus();
        const wrapper = open();
        await nextTick();
        await nextTick();
        await wrapper.setProps({ show: false });
        await vi.advanceTimersByTimeAsync(200);
        expect((wrapper.get('dialog').element as HTMLDialogElement).open).toBe(false);
        expect(document.body.style.overflow).toBe('');
        expect(document.activeElement).toBe(opener);
    });

    it('keeps BaseModal sizes, slots and overflow behavior', async () => {
        const wrapper = open(
            BaseModal,
            { size: '2xl', overflowVisible: true },
            {
                title: 'Edit item',
                footer: '<button>Save</button>',
            }
        );
        await flushPromises();
        expect(wrapper.find('.max-w-5xl').exists()).toBe(true);
        expect(wrapper.find('.overflow-visible').exists()).toBe(true);
        expect(wrapper.text()).toContain('Edit item');
        expect(wrapper.text()).toContain('Save');
        const close = wrapper.get('button[aria-label]');
        expect(close.attributes('type')).toBe('button');
        await close.trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);
    });
});
