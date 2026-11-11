import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';
import VehicleOdometerField from './VehicleOdometerField.vue';
import { isLowerOdometer } from '@/Utils/odometer';

it('normalizes Arabic numerals and distinguishes a zero reading from a missing reading', async () => {
    const wrapper = mount(VehicleOdometerField, {
        props: { modelValue: '', lastVehicleOdometer: 10 },
    });
    await wrapper.get('input').setValue('١٢x');
    expect(wrapper.emitted('update:modelValue')).toEqual([['12']]);
    expect(wrapper.get('input').element.value).toBe('12');
    expect(isLowerOdometer(0, 10)).toBe(true);
    expect(isLowerOdometer('', 10)).toBe(false);
    expect(isLowerOdometer('10', 10)).toBe(false);
    wrapper.unmount();
});
