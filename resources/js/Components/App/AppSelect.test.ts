import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppSelect from './AppSelect.vue';

describe('AppSelect static options', () => {
    it('renders slotted options and emits the selected value', async () => {
        const wrapper = mount(AppSelect, {
            props: { modelValue: 'cash', label: 'Choose Type' },
            slots: {
                default: '<option value="cash">Cash</option><option value="mada">Mada</option>',
            },
        });
        expect(wrapper.get('label').text()).toBe('Choose Type');
        expect(wrapper.findAll('option')).toHaveLength(2);
        expect((wrapper.get('select').element as HTMLSelectElement).value).toBe('cash');
        await wrapper.get('select').setValue('mada');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['mada']);
        wrapper.unmount();
    });
});
