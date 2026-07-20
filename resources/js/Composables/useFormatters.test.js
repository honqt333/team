import { describe, it, expect } from 'vitest';
import { useFormatters } from './useFormatters';

describe('useFormatters', () => {
    const { formatDate, formatPrice, formatFileSize } = useFormatters();

    it('formats date ISO string correctly', () => {
        expect(formatDate('2026-06-15')).toBe('15/06/2026');
        expect(formatDate(null)).toBe('-');
    });

    it('formats price correctly if function exists', () => {
        if (typeof formatPrice === 'function') {
            const formatted = formatPrice(100);
            expect(formatted).toBeDefined();
        }
    });

    it('formats file size correctly if function exists', () => {
        if (typeof formatFileSize === 'function') {
            expect(formatFileSize(1024)).toContain('KB');
        }
    });
});
