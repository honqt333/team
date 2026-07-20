import { describe, it, expect, vi } from 'vitest';
import { ArabicNumeralsPlugin } from './arabicNumerals';

describe('ArabicNumeralsPlugin', () => {
    it('installs event listener on window', () => {
        const spy = vi.spyOn(window, 'addEventListener');
        ArabicNumeralsPlugin.install({});
        expect(spy).toHaveBeenCalledWith('input', expect.any(Function), true);
    });
});
