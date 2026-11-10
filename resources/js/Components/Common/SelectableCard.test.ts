import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import SelectableCard from './SelectableCard.vue';

describe('SelectableCard', () => {
    it('handles boolean selection toggle', async () => {
        const wrapper = mount(SelectableCard, {
            props: {
                modelValue: false,
                title: 'الباقة المتقدمة',
                description: 'تشمل الفحص الكامل',
            },
        });

        expect(wrapper.text()).toContain('الباقة المتقدمة');
        expect(wrapper.text()).toContain('تشمل الفحص الكامل');

        await wrapper.trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true]);
    });

    it('handles radio selection', async () => {
        const wrapper = mount(SelectableCard, {
            props: {
                modelValue: 'plan_b',
                value: 'plan_a',
                type: 'radio',
                title: 'باقة أ',
            },
        });

        expect(wrapper.attributes('aria-pressed')).toBe('false');

        await wrapper.trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['plan_a']);
    });

    it('does not emit toggle when disabled', async () => {
        const wrapper = mount(SelectableCard, {
            props: {
                modelValue: false,
                disabled: true,
                title: 'باقة ملغاة',
            },
        });

        await wrapper.trigger('click');
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });

    it('handles array modelValue with number and string values', async () => {
        const wrapper = mount(SelectableCard, {
            props: {
                modelValue: [1, 2],
                value: 2,
                type: 'checkbox',
                title: 'الفرع الرئيسي',
            },
        });

        expect(wrapper.attributes('aria-pressed')).toBe('true');

        // Clicking selected item removes it
        await wrapper.trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([[1]]);

        // String value matching numeric modelValue
        const wrapperStr = mount(SelectableCard, {
            props: {
                modelValue: [1, 2],
                value: '2',
                type: 'checkbox',
                title: 'الفرع الرئيسي',
            },
        });
        expect(wrapperStr.attributes('aria-pressed')).toBe('true');
    });
});
