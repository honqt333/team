import { ref, computed, watch, onMounted, onUnmounted, toValue } from 'vue';
import axios from 'axios';

// The restored panel opts into manual requests. Automatic mode remains available
// for existing callers, with cancellation and stale-response protection.
export function useWorkOrderSuggestions({
    workOrder,
    endpoint,
    debounceMs = 600,
    autoFetch = true,
} = {}) {
    const suggestions = ref([]);
    const isLoading = ref(false);
    const error = ref(null);
    const meta = ref(null);
    const fetchedKey = ref(null);
    const order = () => toValue(workOrder) || {};
    const complaint = () => String(order().complaint ?? order().customer_complaint ?? '').trim();
    const key = () => `${order().id ?? ''}:${complaint()}`;
    let generation = 0;
    let timer;
    let controller;
    let disposed = false;

    function invalidate() {
        generation++;
        controller?.abort();
        isLoading.value = false;
    }

    async function refresh() {
        invalidate();
        const current = generation;
        const requestKey = key();
        const text = complaint();
        suggestions.value = [];
        meta.value = null;
        error.value = null;
        const url =
            endpoint || (order().id ? `/api/v1/work-orders/${order().id}/suggestions` : null);
        if (!url || text.length < 5 || disposed) return;
        const vehicle = order().vehicle || {};
        const label = (value) =>
            typeof value === 'object' && value !== null
                ? value.name_en || value.name_ar || value.name || null
                : (value ?? null);
        const payload = { complaint: text };
        if (Object.keys(vehicle).length) {
            payload.vehicle = {
                make: label(vehicle.make),
                model: label(vehicle.model),
                year: vehicle.year ?? null,
                plate_number: vehicle.plate_number ?? null,
                odometer: vehicle.odometer ?? null,
            };
        }
        controller = new AbortController();
        isLoading.value = true;
        try {
            const { data } = await axios.post(url, payload, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (disposed || generation !== current || key() !== requestKey) return;
            suggestions.value = Array.isArray(data?.suggestions) ? data.suggestions : [];
            meta.value = data?.meta ?? null;
            fetchedKey.value = requestKey;
        } catch (err) {
            if (disposed || generation !== current) return;
            error.value =
                err?.response?.data?.message || 'work_orders.suggestions.errors.request_failed';
        } finally {
            if (!disposed && generation === current) isLoading.value = false;
        }
    }

    onMounted(() => {
        if (autoFetch) refresh();
    });
    watch(key, () => {
        invalidate();
        clearTimeout(timer);
        suggestions.value = [];
        meta.value = null;
        error.value = null;
        if (autoFetch) timer = setTimeout(refresh, debounceMs);
    });
    onUnmounted(() => {
        disposed = true;
        clearTimeout(timer);
        invalidate();
    });

    return {
        suggestions,
        isLoading,
        error,
        meta,
        refresh,
        isStale: computed(() => fetchedKey.value !== null && fetchedKey.value !== key()),
    };
}
