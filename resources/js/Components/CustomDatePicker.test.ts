import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { describe, expect, it } from 'vitest';
import CustomDatePicker from './CustomDatePicker.vue';

describe('CustomDatePicker', () => {
    it('keeps the allowed booking range and emits the selected local calendar day', async () => {
        const wrapper = mount(CustomDatePicker, {
            props: { modelValue: '2026-11-10', minDate: '2026-11-10', maxDate: '2026-11-12' },
            global: { plugins: [createI18n({ legacy: false, locale: 'en' })] },
        });
        await wrapper.get('input').trigger('click');
        const day = (value: string) =>
            wrapper.findAll('button').find((button) => button.text() === value)!;
        expect(day('9').element.disabled).toBe(true);
        expect(day('13').element.disabled).toBe(true);
        await day('12').trigger('click');
        expect(wrapper.emitted('change')).toEqual([['2026-11-12']]);
        wrapper.unmount();
    });
});
