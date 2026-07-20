import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppButton from './AppButton.vue';

describe('AppButton', () => {
    it('renders slot text', () => {
        const wrapper = mount(AppButton, {
            slots: { default: 'Click me' },
        });
        expect(wrapper.text()).toContain('Click me');
    });

    it('emits click event', async () => {
        const wrapper = mount(AppButton, {
            slots: { default: 'Submit' },
        });
        await wrapper.trigger('click');
        expect(wrapper.emitted('click')).toBeTruthy();
    });

    it('disables button when disabled prop is true', () => {
        const wrapper = mount(AppButton, {
            props: { disabled: true },
            slots: { default: 'Disabled' },
        });
        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    });
});
