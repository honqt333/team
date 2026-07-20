import { describe, it, expect, vi } from 'vitest';
import SafeHtmlPlugin from './safeHtml';

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
});
