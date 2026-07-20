import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import SortIcon from './SortIcon.vue';

describe('SortIcon', () => {
    it('renders sort icon with direction prop', () => {
        const wrapper = mount(SortIcon, {
            props: { direction: 'asc' },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
