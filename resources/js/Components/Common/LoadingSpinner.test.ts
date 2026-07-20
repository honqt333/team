import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import LoadingSpinner from './LoadingSpinner.vue';

describe('LoadingSpinner', () => {
    it('renders spinner element', () => {
        const wrapper = mount(LoadingSpinner);
        expect(wrapper.find('svg, div').exists()).toBe(true);
    });
});
