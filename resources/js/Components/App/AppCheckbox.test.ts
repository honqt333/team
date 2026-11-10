import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AppCheckbox from './AppCheckbox.vue';

describe('AppCheckbox models', () => {
    it('keeps Boolean behavior and native required/disabled attributes', async () => {
        const wrapper = mount(AppCheckbox, { props: { modelValue: false, required: true } });
        await wrapper.get('input').setValue(true);
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true]);
        expect(wrapper.get('input').attributes('required')).toBeDefined();
        await wrapper.setProps({ disabled: true });
        expect(wrapper.get('input').attributes('disabled')).toBeDefined();
        wrapper.unmount();
    });
    it('adds and removes permissions without mutating the parent array', async () => {
        const selected = ['read'];
        const wrapper = mount(AppCheckbox, { props: { modelValue: selected, value: 'write' } });
        expect((wrapper.get('input').element as HTMLInputElement).checked).toBe(false);
        await wrapper.get('input').setValue(true);
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([['read', 'write']]);
        expect(selected).toEqual(['read']);
        await wrapper.setProps({ modelValue: ['read', 'write'] });
        await wrapper.get('input').setValue(false);
        expect(wrapper.emitted('update:modelValue')?.[1]).toEqual([['read']]);
        wrapper.unmount();
    });
    it('uses a non-label row when embedded in an existing label', () => {
        const wrapper = mount(AppCheckbox, { props: { modelValue: [] } });
        expect(wrapper.find('label').exists()).toBe(false);
        wrapper.unmount();
    });
});
