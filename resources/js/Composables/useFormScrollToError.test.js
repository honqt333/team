import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useFormScrollToError } from './useFormScrollToError';

describe('useFormScrollToError', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        vi.useFakeTimers();
    });

    it('returns false when errors object is empty or null', () => {
        const { scrollToError } = useFormScrollToError();
        expect(scrollToError(null)).toBe(false);
        expect(scrollToError({})).toBe(false);
    });

    it('calls onSwitchTab if field belongs to a specific tab in tabMap', () => {
        const { scrollToError } = useFormScrollToError();
        const onSwitchTab = vi.fn();
        const tabMap = { email: 'account', phone: 'contact' };

        const handled = scrollToError({ phone: 'رقم الهاتف غير صحيح' }, { tabMap, onSwitchTab });

        expect(handled).toBe(true);
        expect(onSwitchTab).toHaveBeenCalledWith('contact');
    });

    it('scrolls into view and focuses the element matching name or id', () => {
        const { scrollToError } = useFormScrollToError();

        const input = document.createElement('input');
        input.name = 'name_ar';
        input.scrollIntoView = vi.fn();
        input.focus = vi.fn();
        document.body.appendChild(input);

        scrollToError({ name_ar: 'الاسم مطلوب' });

        vi.advanceTimersByTime(150);

        expect(input.scrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'center' });
        expect(input.focus).toHaveBeenCalled();
        expect(input.classList.contains('ring-red-500')).toBe(true);

        vi.advanceTimersByTime(3100);
        expect(input.classList.contains('ring-red-500')).toBe(false);
    });
});
