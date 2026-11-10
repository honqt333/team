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

    it('applies action-primary when active and muted token when inactive', () => {
        const wrapper = mount(SortIcon, {
            props: { active: true, direction: 'asc' },
        });
        const svgs = [wrapper.get('svg:nth-child(1)'), wrapper.get('svg:nth-child(2)')] as const;
        expect(svgs[0].classes()).toContain('text-action-primary');
        expect(svgs[1].classes()).toContain('text-text-muted/40');
    });
});
