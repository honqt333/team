import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppTextarea from './AppTextarea.vue';

describe('AppTextarea', () => {
    it('renders textarea with label', () => {
        const wrapper = mount(AppTextarea, {
            props: { modelValue: '', label: 'Notes' },
        });
        expect(wrapper.text()).toContain('Notes');
    });
});
