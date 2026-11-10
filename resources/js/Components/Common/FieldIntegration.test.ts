import { describe, expect, it } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent } from 'vue';
import FormField from './FormField.vue';
import AppInput from '../App/AppInput.vue';
import BaseModal from '../BaseModal.vue';

describe('FormField and AppInput integration', () => {
    it('links one label and one error to the actual input', async () => {
        const Host = defineComponent({
            components: { FormField, AppInput },
            props: { error: String },
            template: `<FormField v-slot="field" label="Name" :error="error" hint="Your name">
                <AppInput :id="field.id" :aria-describedby="field.describedBy" :invalid="field.hasError" />
            </FormField>`,
        });
        const wrapper = mount(Host, { props: { error: 'Required' } });
        const input = wrapper.get('input');
        expect(wrapper.findAll('label')).toHaveLength(1);
        expect(wrapper.findAll('[role="alert"]')).toHaveLength(1);
        expect(wrapper.get('label').attributes('for')).toBe(input.attributes('id'));
        expect(input.attributes('aria-describedby')).toBe(
            wrapper.get('[role="alert"]').attributes('id')
        );
        expect(input.attributes('aria-invalid')).toBe('true');
        await wrapper.setProps({ error: '' });
        expect(input.attributes('aria-invalid')).toBeUndefined();
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
        expect(wrapper.get(`#${input.attributes('aria-describedby')}`).text()).toBe('Your name');
        wrapper.unmount();
    });
    it('keeps autofocus when a field is mounted inside a native modal', async () => {
        const Host = defineComponent({
            components: { BaseModal, AppInput },
            template: '<BaseModal show title="Edit"><AppInput autofocus /></BaseModal>',
        });
        const wrapper = mount(Host, { attachTo: document.body });
        await flushPromises();
        expect(wrapper.get('input').attributes('autofocus')).toBeDefined();
        expect(document.activeElement).toBe(wrapper.get('input').element);
        wrapper.unmount();
    });

    it('preserves numeric and empty values from legacy number inputs', async () => {
        const wrapper = mount(AppInput, { props: { type: 'number', modelValue: 0 } });
        await wrapper.get('input').setValue('12.5');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([12.5]);
        await wrapper.get('input').setValue('');
        expect(wrapper.emitted('update:modelValue')?.[1]).toEqual(['']);
        wrapper.unmount();
    });
    it('keeps the exposed focus method and focuses on mount when requested', () => {
        const wrapper = mount(AppInput, { attachTo: document.body, props: { autofocus: true } });
        expect(document.activeElement).toBe(wrapper.get('input').element);
        (wrapper.get('input').element as HTMLInputElement).blur();
        wrapper.vm.focus();
        expect(document.activeElement).toBe(wrapper.get('input').element);
        wrapper.unmount();
    });
});
