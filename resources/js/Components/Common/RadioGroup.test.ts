import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import RadioGroup from './RadioGroup.vue';

describe('RadioGroup', () => {
    const options = [
        { value: 'cash', label: 'نقداً', description: 'دفع نقدي فوري' },
        { value: 'card', label: 'بطاقة ائتمانية', description: 'نقاط بيع' },
    ];

    it('renders options with labels and emits update:modelValue on click', async () => {
        const wrapper = mount(RadioGroup, {
            props: {
                modelValue: 'cash',
                options,
            },
        });

        expect(wrapper.text()).toContain('نقداً');
        expect(wrapper.text()).toContain('بطاقة ائتمانية');

        await wrapper.get('label:nth-child(2)').trigger('click');

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['card']);
    });
});
