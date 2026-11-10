import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppToggle from './AppToggle.vue';

describe('AppToggle', () => {
    it('renders with label and reflects boolean checked status', async () => {
        const wrapper = mount(AppToggle, {
            props: {
                modelValue: false,
                label: 'تفعيل الإشعار',
            },
        });

        expect(wrapper.text()).toContain('تفعيل الإشعار');
        const button = wrapper.find('button[role="switch"]');
        expect(button.attributes('aria-checked')).toBe('false');

        await button.trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true]);
        expect(wrapper.emitted('update:checked')?.[0]).toEqual([true]);
    });

    it('toggles from true to false', async () => {
        const wrapper = mount(AppToggle, {
            props: {
                modelValue: true,
            },
        });

        const button = wrapper.find('button[role="switch"]');
        expect(button.attributes('aria-checked')).toBe('true');

        await button.trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
    });

    it('handles custom trueValue and falseValue like "1" and "0"', async () => {
        const wrapper = mount(AppToggle, {
            props: {
                modelValue: '0',
                trueValue: '1',
                falseValue: '0',
            },
        });

        const button = wrapper.find('button[role="switch"]');
        expect(button.attributes('aria-checked')).toBe('false');

        await button.trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['1']);
    });

    it('handles array bindings for permissions/groups', async () => {
        const wrapper = mount(AppToggle, {
            props: {
                modelValue: ['view', 'edit'],
                value: 'delete',
            },
        });

        const button = wrapper.find('button[role="switch"]');
        expect(button.attributes('aria-checked')).toBe('false');

        await button.trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([['view', 'edit', 'delete']]);

        // When item is already in array
        const wrapperActive = mount(AppToggle, {
            props: {
                modelValue: ['view', 'edit'],
                value: 'edit',
            },
        });
        const buttonActive = wrapperActive.find('button[role="switch"]');
        expect(buttonActive.attributes('aria-checked')).toBe('true');

        await buttonActive.trigger('click');
        expect(wrapperActive.emitted('update:modelValue')?.[0]).toEqual([['view']]);
    });

    it('does not trigger change when disabled', async () => {
        const wrapper = mount(AppToggle, {
            props: {
                modelValue: false,
                disabled: true,
            },
        });

        const button = wrapper.find('button[role="switch"]');
        expect(button.attributes('disabled')).toBeDefined();

        await button.trigger('click');
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });

    it('applies semantic design token color classes when active', () => {
        const wrapperSuccess = mount(AppToggle, {
            props: { modelValue: true, color: 'success' },
        });
        expect(wrapperSuccess.find('button[role="switch"]').classes()).toContain('bg-success');

        const wrapperDanger = mount(AppToggle, {
            props: { modelValue: true, color: 'danger' },
        });
        expect(wrapperDanger.find('button[role="switch"]').classes()).toContain('bg-danger');

        const wrapperPrimary = mount(AppToggle, {
            props: { modelValue: true, color: 'primary' },
        });
        expect(wrapperPrimary.find('button[role="switch"]').classes()).toContain(
            'bg-action-primary'
        );
    });
});
