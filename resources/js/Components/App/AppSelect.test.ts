import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppSelect from './AppSelect.vue';

describe('AppSelect', () => {
    it('renders label and options', () => {
        const wrapper = mount(AppSelect, {
            props: {
                modelValue: '',
                label: 'Choose Type',
                options: [
                    { value: 'cash', label: 'Cash' },
                    { value: 'mada', label: 'Mada' },
                ],
            },
        });
        expect(wrapper.text()).toContain('Choose Type');
    });
});
