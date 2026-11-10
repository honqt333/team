import { ref, reactive, watch, toRaw, getCurrentInstance, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from './useDebounce';

export interface ServerTableOptions<T extends Record<string, any> = Record<string, any>> {
    /** Target URL or function returning URL (e.g. route('users.index')) */
    routeUrl: string | (() => string);
    /** Initial filters dictionary */
    initialFilters?: T;
    /** Initial sort settings */
    initialSort?: {
        column?: string;
        direction?: 'asc' | 'desc';
    };
    /** Debounce milliseconds for automatic search watching (0 to disable auto search watch) */
    debounceMs?: number;
    /** Inertia partial reloads keys (optional) */
    only?: string[];
    /** Preserve scroll position on reload */
    preserveScroll?: boolean;
    /** Preserve component local state on reload */
    preserveState?: boolean;
}

export function useServerTable<T extends Record<string, any> = Record<string, any>>(
    options: ServerTableOptions<T>
) {
    const {
        routeUrl,
        initialFilters = {} as T,
        initialSort = { column: '', direction: 'asc' },
        debounceMs = 350,
        only,
        preserveScroll = true,
        preserveState = true,
    } = options;

    // Filters state
    const filters = reactive<T>({ ...initialFilters } as T);

    // Sorting state
    const sortColumn = ref<string>(initialSort.column || '');
    const sortDirection = ref<'asc' | 'desc'>(initialSort.direction || 'asc');

    // Loading indicator
    const isLoading = ref<boolean>(false);

    /**
     * Resolve the target route URL as a relative pathname to prevent domain/port mismatches
     */
    const getTargetUrl = (): string => {
        const raw = typeof routeUrl === 'function' ? routeUrl() : routeUrl;
        if (typeof raw === 'string' && (raw.startsWith('http://') || raw.startsWith('https://'))) {
            try {
                const parsed = new URL(raw);
                return parsed.pathname;
            } catch {
                return raw;
            }
        }
        return raw;
    };

    /**
     * Clean parameters: remove empty strings, null, undefined scalars.
     * Arrays are kept as-is when non-empty (e.g. filters.category[]).
     */
    const cleanParams = (params: Record<string, any>): Record<string, any> => {
        const cleaned: Record<string, any> = {};
        for (const [key, val] of Object.entries(params)) {
            if (Array.isArray(val)) {
                if (val.length > 0) cleaned[key] = val; // keep non-empty arrays
            } else if (val !== '' && val !== null && val !== undefined) {
                cleaned[key] = val;
            }
        }
        return cleaned;
    };

    /**
     * Apply filters and fetch fresh data via Inertia
     */
    const applyFilters = (overrides?: unknown) => {
        const safeOverrides =
            typeof overrides === 'object' &&
            overrides !== null &&
            !Array.isArray(overrides) &&
            !(typeof Event !== 'undefined' && overrides instanceof Event)
                ? (overrides as Record<string, any>)
                : {};

        const rawFilters = toRaw(filters);
        const queryParams: Record<string, any> = {
            ...rawFilters,
            ...safeOverrides,
        };

        if (sortColumn.value) {
            queryParams.sort = sortColumn.value;
            queryParams.direction = sortDirection.value;
        }

        const cleaned = cleanParams(queryParams);

        const visitOptions: Record<string, any> = {
            preserveState,
            preserveScroll,
            replace: true,
            onStart: () => {
                isLoading.value = true;
            },
            onFinish: () => {
                isLoading.value = false;
            },
            onError: () => {
                isLoading.value = false;
            },
            onCancel: () => {
                isLoading.value = false;
            },
        };

        if (Array.isArray(only) && only.length > 0) {
            visitOptions.only = only;
        }

        router.get(getTargetUrl(), cleaned, visitOptions);
    };

    /**
     * Toggle sort column and direction
     */
    const toggleSort = (column: string) => {
        if (sortColumn.value === column) {
            sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn.value = column;
            sortDirection.value = 'asc';
        }
        applyFilters();
    };

    /**
     * Reset filters to initial state
     */
    const resetFilters = () => {
        Object.keys(filters).forEach((key) => {
            const initialVal = (initialFilters as Record<string, any>)[key];
            (filters as Record<string, any>)[key] = initialVal !== undefined ? initialVal : '';
        });

        sortColumn.value = initialSort.column || '';
        sortDirection.value = initialSort.direction || 'asc';

        applyFilters();
    };

    // Auto-debounce search if filters.search exists and debounceMs > 0
    let debouncedSearchApply: ReturnType<typeof debounce> | null = null;

    if (debounceMs > 0) {
        debouncedSearchApply = debounce(() => {
            applyFilters();
        }, debounceMs);

        watch(
            () => (filters as any).search,
            (newVal, oldVal) => {
                if (newVal !== oldVal && debouncedSearchApply) {
                    debouncedSearchApply();
                }
            }
        );

        if (getCurrentInstance()) {
            onUnmounted(() => {
                debouncedSearchApply?.cancel?.();
            });
        }
    }

    return {
        filters,
        /** Alias for filters to prevent collision with props.filters */
        tableFilters: filters,
        sortColumn,
        sortDirection,
        isLoading,
        applyFilters,
        toggleSort,
        resetFilters,
        /** Cancel any pending debounced search call */
        cancelDebouncedSearch: () => {
            debouncedSearchApply?.cancel?.();
        },
    };
}
