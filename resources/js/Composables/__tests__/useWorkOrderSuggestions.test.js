/**
 * Vitest coverage for useWorkOrderSuggestions — the four scenarios from
 * design.md §10.
 *
 * We mock axios so no real network is hit, and we drive the composable
 * inside a Vue test-utils `mount` to get a real reactive scope (the
 * composable calls `onMounted` + `watch`, which only run inside a
 * component context).
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent, h, nextTick, ref as vueRef } from 'vue';
import { mount } from '@vue/test-utils';
import axios from 'axios';

vi.mock('axios', () => {
    const post = vi.fn();
    return {
        default: {
            post,
        },
        post,
    };
});

import { useWorkOrderSuggestions } from '@/Composables/useWorkOrderSuggestions';

// Helper: mount a tiny host component that calls the composable and exposes
// its return value via `expose`. Returns the wrapper + the live refs.
function mountHost(workOrder, opts = {}) {
    let api;
    const Host = defineComponent({
        setup() {
            api = useWorkOrderSuggestions({
                workOrder,
                endpoint: opts.endpoint,
                debounceMs: opts.debounceMs ?? 50, // tight debounce for tests
            });
            return () => h('div');
        },
    });
    const wrapper = mount(Host);
    return { wrapper, api };
}

const SAMPLE_RESPONSE = {
    suggestions: [
        {
            department_id: 12,
            department_name: 'فرامل',
            item_type: 'service',
            item_id: 45,
            name: 'فحص نظام الفرامل',
            name_en: 'Brake system inspection',
            qty: 1,
            estimated_unit_price_cents: 15000,
            currency: 'SAR',
            confidence: 0.92,
            reason: 'شكوى العميل من صوت — فحص وقائي',
            is_active: true,
        },
        {
            department_id: 12,
            department_name: 'فرامل',
            item_type: 'part',
            item_id: 302,
            name: 'تيل فرامل أمامي',
            qty: 1,
            estimated_unit_price_cents: 18000,
            currency: 'SAR',
            confidence: 0.55,
            reason: 'تيل متآكل محتمل',
            is_active: true,
        },
    ],
    meta: {
        provider: 'mock',
        model: 'gpt-4o-mini',
        work_order_id: 123,
        tenant_id: 7,
        center_id: 3,
        total_candidates: 18,
        returned: 2,
    },
};

describe('useWorkOrderSuggestions', () => {
    beforeEach(() => {
        axios.post.mockReset();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    // 1) Fetches on mount, sets isLoading=true then false
    it('fetches on mount and toggles isLoading around the request', async () => {
        let resolvePost;
        axios.post.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    resolvePost = resolve;
                })
        );

        const { api } = mountHost({ id: 123, complaint: 'صوت طقطقة في الفرامل' });

        // onMounted triggers refresh; isLoading should be true synchronously
        // after the awaited microtask.
        await nextTick();
        expect(api.isLoading.value).toBe(true);
        expect(axios.post).toHaveBeenCalledTimes(1);

        // Resolve and let the catch-up microtasks run.
        resolvePost({ data: SAMPLE_RESPONSE });
        await nextTick();
        await nextTick();

        expect(api.isLoading.value).toBe(false);
        expect(api.error.value).toBeNull();
        expect(api.suggestions.value).toHaveLength(2);
    });

    // 2) Debounces when workOrder.complaint changes
    it('debounces when the complaint changes', async () => {
        axios.post.mockResolvedValue({ data: SAMPLE_RESPONSE });

        // Mount with a reactive ref so we can drive the watch by changing
        // the inner value. A plain JS object would not be tracked by Vue.
        const workOrderRef = vueRef({ id: 123, complaint: 'صوت طقطقة في الفرامل' });
        const { api } = mountHost(() => workOrderRef.value, { debounceMs: 80 });

        // Wait past the initial mount fetch.
        await nextTick();
        await new Promise((r) => setTimeout(r, 30));
        const callsAfterMount = axios.post.mock.calls.length;
        expect(callsAfterMount).toBeGreaterThan(0);

        // Change the complaint — must NOT fire immediately (debounce).
        workOrderRef.value = { id: 123, complaint: 'تيل الفرامل مهترئ جداً' };
        await nextTick();
        // Still within the debounce window — no new call yet.
        expect(axios.post.mock.calls.length).toBe(callsAfterMount);

        // Wait past the debounce window — the second call should fire.
        await new Promise((r) => setTimeout(r, 150));
        expect(axios.post.mock.calls.length).toBe(callsAfterMount + 1);
        expect(api.isLoading.value).toBe(false);
    });

    // 3) On 4xx/5xx, sets error and clears suggestions
    it('sets error and clears suggestions on a failed request', async () => {
        axios.post.mockRejectedValueOnce({
            response: { data: { message: 'AI service unavailable.' }, status: 502 },
        });

        const { api } = mountHost({ id: 123, complaint: 'صوت طقطقة في الفرامل' });

        await nextTick();
        await nextTick();

        expect(api.isLoading.value).toBe(false);
        expect(api.error.value).toBe('AI service unavailable.');
        expect(api.suggestions.value).toEqual([]);
    });

    // 4) On 200, exposes suggestions + meta
    it('exposes suggestions and meta on a successful 200 response', async () => {
        axios.post.mockResolvedValueOnce({ data: SAMPLE_RESPONSE });

        const { api } = mountHost({ id: 123, complaint: 'صوت طقطقة في الفرامل' });

        await nextTick();
        await nextTick();

        expect(api.isLoading.value).toBe(false);
        expect(api.error.value).toBeNull();

        // suggestions shape
        expect(api.suggestions.value).toHaveLength(2);
        expect(api.suggestions.value[0]).toMatchObject({
            item_type: 'service',
            item_id: 45,
            name: 'فحص نظام الفرامل',
            confidence: 0.92,
        });

        // meta shape
        expect(api.meta.value).toMatchObject({
            provider: 'mock',
            work_order_id: 123,
            tenant_id: 7,
            center_id: 3,
            total_candidates: 18,
            returned: 2,
        });

        // Contract conformance: post URL + payload.
        expect(axios.post).toHaveBeenCalledWith(
            '/api/v1/work-orders/123/suggestions',
            expect.objectContaining({ complaint: expect.any(String) }),
            expect.objectContaining({
                headers: expect.objectContaining({ 'X-Requested-With': 'XMLHttpRequest' }),
            })
        );
    });

    // 5) Bonus coverage: isStale flips when the user types a new complaint
    //    that the last fetch didn't cover. Important for the panel's
    //    "press refresh to update" hint.
    it('flips isStale to true when the user types a new complaint', async () => {
        axios.post.mockResolvedValue({ data: SAMPLE_RESPONSE });

        const workOrderRef = vueRef({
            id: 123,
            complaint: 'صوت طقطقة في الفرامل',
        });
        const { api } = mountHost(() => workOrderRef.value);

        // Wait past the on-mount fetch.
        await nextTick();
        await nextTick();

        // After a successful fetch, the panel is "fresh".
        expect(api.isStale.value).toBe(false);

        // The user edits the complaint — the panel is now showing data for
        // an older complaint than what's in the work order.
        workOrderRef.value = {
            id: 123,
            complaint: 'تيل الفرامل مهترئ جداً',
        };
        await nextTick();
        expect(api.isStale.value).toBe(true);
    });
});
