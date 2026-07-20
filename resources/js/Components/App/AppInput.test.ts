import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppInput from './AppInput.vue';

describe('AppInput', () => {
    it('renders label when provided', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', label: 'Customer Name' },
        });
        expect(wrapper.text()).toContain('Customer Name');
    });

    it('emits update:modelValue on user input', async () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '' },
        });
        const input = wrapper.find('input');
        if (input.exists()) {
            await input.setValue('John');
            expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        }
    });

    it('displays error message', () => {
        const wrapper = mount(AppInput, {
            props: { modelValue: '', error: 'Field is required' },
        });
        expect(wrapper.text()).toContain('Field is required');
    });
});
