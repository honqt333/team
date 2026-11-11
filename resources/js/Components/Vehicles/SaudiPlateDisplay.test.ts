import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import SaudiPlateDisplay from './SaudiPlateDisplay.vue';

describe('SaudiPlateDisplay', () => {
    it('renders each Arabic and English letter in an isolated non-wrapping span to prevent overlap', () => {
        const wrapper = mount(SaudiPlateDisplay, {
            props: {
                plateNumber: 'HXG 1234',
                size: 'sm',
            },
        });

        expect(wrapper.classes()).toContain('shrink-0');
        expect(wrapper.classes()).toContain('min-w-[158px]');

        const arRow = wrapper.find('[dir="rtl"]');
        const enRow = wrapper.find('[dir="ltr"]');

        const arLetters = arRow.findAll('span');
        const enLetters = enRow.findAll('span');

        expect(arLetters.map((s) => s.text())).toEqual(['هـ', 'ص', 'ق']);
        expect(enLetters.map((s) => s.text())).toEqual(['H', 'X', 'G']);

        for (const span of [...arLetters, ...enLetters]) {
            expect(span.classes()).toContain('whitespace-nowrap');
            expect(span.classes()).toContain('shrink-0');
        }
        wrapper.unmount();
    });

    it('supports spaced letter input and xs/md/lg sizes', () => {
        const wrapper = mount(SaudiPlateDisplay, {
            props: {
                plateNumber: 'A B D 9988',
                size: 'xs',
            },
        });

        expect(wrapper.classes()).toContain('min-w-[136px]');
        const arLetters = wrapper.find('[dir="rtl"]').findAll('span');
        expect(arLetters.map((s) => s.text())).toEqual(['أ', 'ب', 'د']);
        wrapper.unmount();
    });

    it('falls back cleanly for non-standard plate strings', () => {
        const wrapper = mount(SaudiPlateDisplay, {
            props: {
                plateNumber: 'DXB-55412',
            },
        });

        expect(wrapper.text()).toBe('DXB-55412');
        wrapper.unmount();
    });
});
