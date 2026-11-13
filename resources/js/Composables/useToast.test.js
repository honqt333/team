import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest';
import { useToast } from './useToast';

describe('useToast', () => {
    const {
        toasts,
        success,
        error,
        warning,
        info,
        clear,
        removeToast,
        pauseToast,
        resumeToast,
        markPendingFlashError,
        clearPendingFlashError,
    } = useToast();

    beforeEach(() => {
        clear();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('adds success toast', () => {
        success('Item created');
        expect(toasts.value.length).toBe(1);
        expect(toasts.value[0].type).toBe('success');
        expect(toasts.value[0].message).toBe('Item created');
        expect(toasts.value[0].dismissOnClick).toBe(true);
    });

    it('adds error toast', () => {
        error('Something failed');
        expect(toasts.value.length).toBe(1);
        expect(toasts.value[0].type).toBe('error');
    });

    it('supports pause and resume of toast timers without throwing', () => {
        const id = success('Pausable toast');
        expect(() => {
            pauseToast(id);
            resumeToast(id);
        }).not.toThrow();
    });

    it('removes toast by id', () => {
        const id = success('Test toast');
        expect(toasts.value.length).toBe(1);
        removeToast(id);
        expect(toasts.value.length).toBe(0);
    });

    it('clears all toasts', () => {
        success('One');
        info('Two');
        expect(toasts.value.length).toBe(2);
        clear();
        expect(toasts.value.length).toBe(0);
    });

    it('suppresses same-response flash toast even with different wording, without dropping later distinct actions', () => {
        success('تم حفظ البيانات');
        // Same response tick (<300ms) flash with slightly different wording is deduplicated
        const dupId = success('تم التحديث بنجاح', { fromFlash: true });
        expect(dupId).toBeNull();
        expect(toasts.value.length).toBe(1);

        // Advance past same-response window (500ms) -> distinct later action is allowed
        vi.advanceTimersByTime(500);
        const nextId = success('تم تفعيل القسم بنجاح', { fromFlash: true });
        expect(nextId).not.toBeNull();
        expect(toasts.value.length).toBe(2);
    });

    it('suppresses optimistic success toast when a flash error is pending so users never see success then error', () => {
        markPendingFlashError();
        const suppressedId = success('تم الحذف');
        expect(suppressedId).toBeNull();
        expect(toasts.value.length).toBe(0);

        error('لا يمكن الحذف لوجود بيانات مرتبطة', { fromFlash: true });
        clearPendingFlashError();
        expect(toasts.value.length).toBe(1);
        expect(toasts.value[0].type).toBe('error');

        // Normal success toasts work again afterwards
        const normalId = success('تمت العملية');
        expect(normalId).not.toBeNull();
        expect(toasts.value.length).toBe(2);
    });
});
