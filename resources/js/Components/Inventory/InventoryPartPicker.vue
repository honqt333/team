<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({ warehouseId: [String, Number], showCurrency: Boolean });
const partId = defineModel();
const emit = defineEmits(['select']);
const { getName } = useLocalized();
const { formatCurrency, formatQuantity, toEnglish } = useNumberFormat();
const options = ref([]);
const query = ref('');
function setQuery(value) {
    query.value = value;
}

watch(
    [query, () => props.warehouseId],
    ([search, warehouseId], _, onCleanup) => {
        const controller = new AbortController();
        options.value = [];
        const timer = setTimeout(
            async () => {
                try {
                    const response = await axios.get(route('app.inventory.parts.search'), {
                        params: {
                            q: search,
                            warehouse_id: warehouseId || undefined,
                            hide_out_of_stock: true,
                        },
                        signal: controller.signal,
                    });
                    if (!controller.signal.aborted) options.value = response.data;
                } catch {
                    if (!controller.signal.aborted) options.value = [];
                }
            },
            search ? 300 : 0
        );
        onCleanup(() => {
            clearTimeout(timer);
            controller.abort();
        });
    },
    { immediate: true, flush: 'sync' }
);

function selectPart(id) {
    const part = options.value.find((option) => option.id === id);
    if (part) emit('select', part);
}
</script>

<template>
    <SearchableSelect
        v-model="partId"
        name="part_id"
        data-field="part_id"
        :options="options"
        :async-search="true"
        @search="setQuery"
        @change="selectPart"
        :placeholder="$t('inventory.parts.search')"
        class="w-full"
    >
        <template #option="{ option }">
            <div class="flex items-center justify-between w-full">
                <div>
                    <div class="font-bold text-gray-900 dark:text-white text-xs">
                        {{ toEnglish(getName(option)) }}
                    </div>
                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5">
                        {{ formatCurrency(option.default_sale_price) }}
                        <span v-if="showCurrency">{{ $t('common.currency') }}</span>
                    </div>
                </div>
                <div class="text-right flex flex-col items-end">
                    <span class="text-[10px] font-mono text-gray-500">
                        {{ toEnglish(option.sku || option.barcode || '---') }}
                    </span>
                    <span
                        class="text-[10px] font-bold mt-0.5"
                        :class="
                            Number(option.inventory_balances_sum_qty_on_hand) > 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-red-500 dark:text-red-400'
                        "
                    >
                        {{ $t('inventory.stock.available') }}:
                        {{ formatQuantity(option.inventory_balances_sum_qty_on_hand || 0) }}
                    </span>
                </div>
            </div>
        </template>
    </SearchableSelect>
</template>
