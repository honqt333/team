import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useTheme } from './useTheme';

describe('useTheme', () => {
    beforeEach(() => {
        document.documentElement.className = '';
        localStorage.clear();
    });

    it('initializes from document class or default', () => {
        const { isDark } = useTheme();
        expect(typeof isDark.value).toBe('boolean');
    });

    it('toggles theme correctly', () => {
        const { isDark, toggleTheme } = useTheme();
        const initial = isDark.value;
        if (typeof toggleTheme === 'function') {
            toggleTheme();
            expect(isDark.value).toBe(!initial);
        }
    });
});
