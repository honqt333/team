import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import SafeHtmlPlugin from './safeHtml';
import SelectionCard from '@/Components/CustomerPortal/SelectionCard.vue';

describe('SafeHtmlPlugin', () => {
    it('registers safe-html directive on app', () => {
        const app = {
            directive: vi.fn(),
        };
        SafeHtmlPlugin.install(app);
        expect(app.directive).toHaveBeenCalledWith('safe-html', expect.any(Object));
    });

    it('sanitizes script tags when mounted', () => {
        const app = { directive: vi.fn() };
        SafeHtmlPlugin.install(app);
        const directive = app.directive.mock.calls[0][1];

        const el = document.createElement('div');
        directive.mounted(el, { value: '<script>alert("xss")</script>Hello' });

        expect(el.innerHTML).not.toContain('<script>');
        expect(el.innerHTML).toContain('Hello');
    });

    it('preserves valid SVG icons while stripping XSS event handlers in SelectionCard', () => {
        const wrapper = mount(SelectionCard, {
            props: {
                title: 'Test Service',
                icon: '<svg class="w-5 h-5" viewBox="0 0 24 24" onload="alert(1)"><path d="M5 13l4 4L19 7"/></svg><img src="x" onerror="alert(2)">',
            },
            global: {
                plugins: [SafeHtmlPlugin],
            },
        });

        expect(wrapper.find('svg').exists()).toBe(true);
        expect(wrapper.find('path').exists()).toBe(true);
        expect(wrapper.html()).not.toContain('onload');
        expect(wrapper.html()).not.toContain('onerror');
    });
});
