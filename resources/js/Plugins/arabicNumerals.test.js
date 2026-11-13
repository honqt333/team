import { describe, it, expect, vi } from 'vitest';
import { ArabicNumeralsPlugin } from './arabicNumerals';

describe('ArabicNumeralsPlugin', () => {
    it('installs event listeners on window', () => {
        const spy = vi.spyOn(window, 'addEventListener');
        ArabicNumeralsPlugin.install({});
        expect(spy).toHaveBeenCalledWith('beforeinput', expect.any(Function), true);
        expect(spy).toHaveBeenCalledWith('paste', expect.any(Function), true);
        expect(spy).toHaveBeenCalledWith('focusin', expect.any(Function), true);
        expect(spy).toHaveBeenCalledWith('input', expect.any(Function), true);
    });

    it('converts Arabic numerals on focusin and dispatches input event', () => {
        ArabicNumeralsPlugin.install({});
        const input = document.createElement('input');
        input.value = '٠٥٠١٢٣٤٥٦٧';
        document.body.appendChild(input);

        let inputFired = false;
        input.addEventListener('input', () => {
            inputFired = true;
        });

        input.dispatchEvent(new Event('focusin', { bubbles: true }));

        expect(input.value).toBe('0501234567');
        expect(inputFired).toBe(true);
        input.remove();
    });

    it('converts Arabic numerals on input event', () => {
        ArabicNumeralsPlugin.install({});
        const input = document.createElement('input');
        document.body.appendChild(input);

        input.value = '١٢٣٫٤٥';
        input.dispatchEvent(new Event('input', { bubbles: true }));

        expect(input.value).toBe('123.45');
        input.remove();
    });
});
