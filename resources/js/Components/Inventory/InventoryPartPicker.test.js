import { nextTick } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, expect, it, vi } from 'vitest';
import axios from 'axios';
import InventoryPartPicker from './InventoryPartPicker.vue';

vi.mock('axios', () => ({ default: { get: vi.fn() } }));
vi.mock('@/Composables/useLocalized', () => ({
    useLocalized: () => ({ getName: (item) => item.name }),
}));

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

it('only selects results from the current warehouse when an earlier request finishes late', async () => {
    vi.useFakeTimers();
    vi.stubGlobal('route', (name) => name);
    let finishFirst;
    axios.get.mockImplementationOnce(
        () =>
            new Promise((resolve) => {
                finishFirst = resolve;
            })
    );
    const part = { id: 4, name: 'Filter' };
    axios.get.mockResolvedValueOnce({ data: [part] });
    const wrapper = mount(InventoryPartPicker, {
        props: { warehouseId: 1 },
        global: { stubs: { SearchableSelect: true } },
    });
    await vi.advanceTimersByTimeAsync(1);
    await wrapper.setProps({ warehouseId: 2 });
    await vi.advanceTimersByTimeAsync(1);
    finishFirst({ data: [{ id: 3 }] });
    await flushPromises();
    const select = wrapper.getComponent({ name: 'SearchableSelect' });
    expect(axios.get.mock.lastCall[1].params.warehouse_id).toBe(2);
    expect(select.props('options')).toEqual([part]);
    select.vm.$emit('change', 4);
    await nextTick();
    expect(wrapper.emitted('select')).toEqual([[part]]);
    wrapper.unmount();
});
