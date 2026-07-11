import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

/**
 * useWorkOrderSuggestions — drives the AI WorkOrder Suggester panel.
 *
 * Contract (§9 of design.md):
 *   - Reads `workOrder.complaint` (or its `customer_complaint` alias) and
 *     POSTs to the suggestions endpoint.
 *   - Fetches once on mount (no debounce), then watches the complaint and
 *     refetches after `debounceMs` of inactivity (default 600ms).
 *   - Returns refs the panel binds to.
 *
 * The HTTP call uses axios (the codebase convention — see
 * resources/js/Pages/WorkOrders/Show.vue and the WorkOrderItemModal
 * family). The X-Requested-With header is required so Laravel's API
 * stack returns JSON instead of redirecting to /login on auth failure.
 *
 * @param {Object} options
 * @param {import('vue').Ref<Object>|Object|Function} options.workOrder
 *   The work-order data (Inertia prop). Accepts a Ref, plain object, or
 *   a getter function (matching the pattern in useWorkOrderNotes.js).
 * @param {string} [options.endpoint] Optional override for the API URL.
 *   When omitted, the composable derives it from `workOrder.id`.
 * @param {number} [options.debounceMs=600] Watch debounce in milliseconds.
 * @returns {{
 *   suggestions: import('vue').Ref<Array<Object>>,
 *   isLoading: import('vue').Ref<boolean>,
 *   error: import('vue').Ref<string|null>,
 *   meta: import('vue').Ref<Object|null>,
 *   refresh: () => Promise<void>,
 *   isStale: import('vue').ComputedRef<boolean>,
 * }}
 */
export function useWorkOrderSuggestions({ workOrder, endpoint, debounceMs = 600 } = {}) {
    const getWorkOrder = () =>
        typeof workOrder === 'function'
            ? workOrder()
            : workOrder?.value !== undefined
              ? workOrder.value
              : workOrder;

    // ─── State ────────────────────────────────────────────────────────────
    const suggestions = ref([]);
    const isLoading = ref(false);
    const error = ref(null);
    const meta = ref(null);

    // Tracks the most recent complaint text the user typed. We compare
    // incoming complaint values against this so identical re-renders
    // (e.g. when the parent re-emits the same prop) don't refire the API.
    let lastFetchedComplaint = null;

    // ─── Internals ────────────────────────────────────────────────────────
    /**
     * Resolve the API URL for this work order.
     * @returns {string|null} Endpoint URL, or null if we can't resolve yet.
     */
    function resolveEndpoint() {
        if (endpoint) return endpoint;
        const wo = getWorkOrder();
        if (!wo || wo.id == null) return null;
        return `/api/v1/work-orders/${wo.id}/suggestions`;
    }

    /**
     * Read the complaint text from the work-order object. The contract
     * uses `workOrder.complaint` (§9) but the existing WorkOrder model
     * exposes it as `customer_complaint`. Support both.
     * @returns {string}
     */
    function readComplaint() {
        const wo = getWorkOrder();
        if (!wo) return '';
        return wo.complaint ?? wo.customer_complaint ?? '';
    }

    /**
     * Fire one suggestions request and update state. Silently no-ops if
     * the complaint is too short (the backend rejects <5 chars anyway).
     */
    async function refresh() {
        const url = resolveEndpoint();
        if (!url) return;

        const complaint = readComplaint().trim();
        if (complaint.length < 5) {
            // Backend would 422 here. Keep panel quiet — empty state covers it.
            suggestions.value = [];
            meta.value = null;
            error.value = null;
            return;
        }

        isLoading.value = true;
        error.value = null;
        try {
            const response = await axios.post(url, buildPayload(complaint), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = response?.data ?? {};
            suggestions.value = Array.isArray(data.suggestions) ? data.suggestions : [];
            meta.value = data.meta ?? null;
            lastFetchedComplaint = complaint;
        } catch (err) {
            // Per contract: surface the server-provided message when present.
            const message =
                err?.response?._data?.message ??
                err?.response?.data?.message ??
                err?.message ??
                'Request failed';
            error.value = message;
            // Clear suggestions on error so the panel doesn't show stale cards.
            suggestions.value = [];
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Build the POST body. Mirrors §3 of the design contract.
     */
    function buildPayload(complaint) {
        const wo = getWorkOrder() || {};
        const vehicle = wo.vehicle || {};
        const payload = { complaint };
        if (vehicle && Object.keys(vehicle).length > 0) {
            payload.vehicle = {
                make: vehicle.make ?? null,
                model: vehicle.model ?? null,
                year: vehicle.year ?? null,
                plate_number: vehicle.plate_number ?? vehicle.plateNumber ?? null,
                odometer: vehicle.odometer ?? null,
            };
        }
        return payload;
    }

    // ─── Computed ─────────────────────────────────────────────────────────
    /**
     * True when the panel is showing data for a complaint other than the
     * one the user is currently typing. The UI uses this to hint "press
     * refresh to update".
     */
    const isStale = computed(() => {
        if (lastFetchedComplaint === null) return false;
        return lastFetchedComplaint !== readComplaint();
    });

    // ─── Lifecycle ────────────────────────────────────────────────────────
    let debounceTimer = null;

    onMounted(() => {
        // Immediate fetch — no debounce on first paint.
        refresh();
    });

    // Drop any pending debounced fetch when the host component unmounts.
    // Without this, the in-flight setTimeout would call refresh() against a
    // torn-down reactive scope and produce noisy "set on unmounted" warnings
    // in vitest (e.g. when the user navigates away mid-debounce).
    onUnmounted(() => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }
    });

    watch(
        () => readComplaint(),
        (next, prev) => {
            // Avoid redundant calls when the value didn't actually change.
            if (next === prev) return;
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                debounceTimer = null;
                refresh();
            }, debounceMs);
        }
    );

    return {
        suggestions,
        isLoading,
        error,
        meta,
        refresh,
        isStale,
    };
}
