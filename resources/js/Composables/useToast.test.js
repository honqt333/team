import { describe, it, expect, beforeEach } from 'vitest';
import { useToast } from './useToast';

describe('useToast', () => {
    const { toasts, success, error, warning, info, clear, removeToast } = useToast();

    beforeEach(() => {
        clear();
    });

    it('adds success toast', () => {
        success('Item created');
        expect(toasts.value.length).toBe(1);
        expect(toasts.value[0].type).toBe('success');
        expect(toasts.value[0].message).toBe('Item created');
    });

    it('adds error toast', () => {
        error('Something failed');
        expect(toasts.value.length).toBe(1);
        expect(toasts.value[0].type).toBe('error');
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
});
