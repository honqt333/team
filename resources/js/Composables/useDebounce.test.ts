import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { debounce, useDebounce, useDebouncedRef } from './useDebounce';
import { ref } from 'vue';

describe('useDebounce', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('delays function execution by specified time', () => {
        const spy = vi.fn();
        const debounced = debounce(spy, 300);

        debounced('first');
        debounced('second');
        debounced('third');

        expect(spy).not.toHaveBeenCalled();

        vi.advanceTimersByTime(299);
        expect(spy).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);
        expect(spy).toHaveBeenCalledTimes(1);
        expect(spy).toHaveBeenCalledWith('third');
    });

    it('cancels pending execution when cancel is called', () => {
        const spy = vi.fn();
        const debounced = debounce(spy, 300);

        debounced('hello');
        expect(spy).not.toHaveBeenCalled();

        debounced.cancel();
        vi.advanceTimersByTime(400);

        expect(spy).not.toHaveBeenCalled();
    });

    it('flushes pending execution immediately when flush is called', () => {
        const spy = vi.fn();
        const debounced = debounce(spy, 300);

        debounced('immediate');
        expect(spy).not.toHaveBeenCalled();

        debounced.flush();
        expect(spy).toHaveBeenCalledTimes(1);
        expect(spy).toHaveBeenCalledWith('immediate');

        // Should not trigger again when timer elapses
        vi.advanceTimersByTime(300);
        expect(spy).toHaveBeenCalledTimes(1);
    });

    it('updates debounced ref only after delay', async () => {
        const source = ref('initial');
        const debounced = useDebouncedRef(source, 200);

        expect(debounced.value).toBe('initial');

        source.value = 'updated';
        await vi.advanceTimersByTimeAsync(100);
        expect(debounced.value).toBe('initial');

        await vi.advanceTimersByTimeAsync(100);
        expect(debounced.value).toBe('updated');
    });
});
