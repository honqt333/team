import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import KpiCard from './KpiCard.vue';

describe('KpiCard trend meaning', () => {
    it.each([
        [12, false, 'emerald'],
        [-12, false, 'rose'],
        [12, true, 'rose'],
        [-12, true, 'emerald'],
    ])('colors trend %s with inversion %s correctly', (trend, trendInverse, color) => {
        const wrapper = mount(KpiCard, {
            props: { label: 'Revenue', value: 100, trend, trendInverse },
        });
        expect(wrapper.find(`.text-${color}-600`).exists()).toBe(true);
        expect(wrapper.text()).toContain('12%');
        wrapper.unmount();
    });
});
