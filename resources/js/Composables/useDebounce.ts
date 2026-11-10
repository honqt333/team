import { getCurrentInstance, onUnmounted, ref, watch, type Ref } from 'vue';

export interface DebouncedFunction<T extends (...args: any[]) => any> {
    (...args: Parameters<T>): void;
    cancel: () => void;
    flush: () => void;
}

/**
 * Standard debounce utility function.
 * Wraps a function to delay its execution until `delay` milliseconds have elapsed
 * since the last time it was invoked.
 */
export function debounce<T extends (...args: any[]) => any>(
    fn: T,
    delay: number = 300
): DebouncedFunction<T> {
    let timer: ReturnType<typeof setTimeout> | null = null;
    let lastArgs: Parameters<T> | null = null;
    let lastThis: any = null;

    const debounced = function (this: any, ...args: Parameters<T>) {
        lastArgs = args;
        lastThis = this;

        if (timer !== null) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            if (lastArgs !== null) {
                fn.apply(lastThis, lastArgs);
                timer = null;
                lastArgs = null;
                lastThis = null;
            }
        }, delay);
    } as DebouncedFunction<T>;

    debounced.cancel = () => {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
            lastArgs = null;
            lastThis = null;
        }
    };

    debounced.flush = () => {
        if (timer !== null && lastArgs !== null) {
            clearTimeout(timer);
            fn.apply(lastThis, lastArgs);
            timer = null;
            lastArgs = null;
            lastThis = null;
        }
    };

    return debounced;
}

/**
 * Composable useDebounce for Vue 3 components.
 * Automatically cancels any pending execution when the component unmounts.
 */
export function useDebounce<T extends (...args: any[]) => any>(
    fn: T,
    delay: number = 300
): DebouncedFunction<T> {
    const debounced = debounce(fn, delay);

    // If invoked inside a Vue component lifecycle, cancel automatically on unmount
    if (getCurrentInstance()) {
        onUnmounted(() => {
            debounced.cancel();
        });
    }

    return debounced;
}

/**
 * Creates a debounced ref that updates only after `delay` ms of inactivity.
 */
export function useDebouncedRef<T>(source: Ref<T>, delay: number = 300): Ref<T> {
    const debouncedValue = ref(source.value) as Ref<T>;

    const update = debounce((newVal: T) => {
        debouncedValue.value = newVal;
    }, delay);

    watch(source, (newVal) => {
        update(newVal);
    });

    if (getCurrentInstance()) {
        onUnmounted(() => {
            update.cancel();
        });
    }

    return debouncedValue;
}
