<template>
    <DialogModal :show="show" @close="handleClose" max-width="4xl">
        <template #title>
            <div class="flex items-center justify-between">
                <span class="text-xl font-bold">{{ $t('inventory.parts.warehouse_stock') }}</span>
                <span v-if="part" class="text-sm font-normal text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                    {{ part.sku }} - {{ part.name_ar }}
                </span>
            </div>
        </template>

        <template #content>
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Warehouse Selector -->
                <div class="flex items-center gap-3 mb-4">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('inventory.parts.add_warehouse') }}:
                    </label>
                    <div class="flex-1">
                        <SearchableSelect
                            v-model="selectedWarehouseId"
                            :options="availableWarehouses"
                            :option-label="(wh) => `${wh.name} - ${wh.center_name}`"
                            option-value="id"
                            :placeholder="$t('inventory.parts.select_warehouse')"
                            compact
                        />
                    </div>
                    <button
                        type="button"
                        @click="addWarehouse"
                        :disabled="!selectedWarehouseId || isWarehouseAdded(selectedWarehouseId)"
                        class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ $t('common.add') }}
                    </button>
                </div>

                <!-- Stock Table -->
                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2.5 text-start text-gray-500 font-semibold">{{ $t('inventory.parts.warehouse') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.cost_price') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.sale_price') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.min_sale_price') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.min_stock') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.initial_stock') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.storage_location') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.allow_price_change') }}</th>
                                <th class="px-3 py-2.5 text-center text-gray-500 font-semibold">{{ $t('common.status') }}</th>
                                <th class="px-3 py-2.5 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(row, index) in form.warehouse_data" :key="index">
                                <td class="px-3 py-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ getWarehouseName(row.warehouse_id) }}
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ getWarehouseCenter(row.warehouse_id) }}
                                    </div>
                                </td>
                                <!-- Cost Price -->
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="row.cost_price"
                                        type="text"
                                        inputmode="decimal"
                                        dir="ltr"
                                        @input="sanitizeNumber($event, row, 'cost_price')"
                                        class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs text-center font-mono"
                                        placeholder="0.00"
                                    />
                                </td>
                                <!-- Sale Price -->
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="row.sale_price"
                                        type="text"
                                        inputmode="decimal"
                                        dir="ltr"
                                        @input="sanitizeNumber($event, row, 'sale_price')"
                                        class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs text-center font-mono"
                                        placeholder="0.00"
                                    />
                                </td>
                                <!-- Min Sale Price -->
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="row.min_sale_price"
                                        type="text"
                                        inputmode="decimal"
                                        dir="ltr"
                                        @input="sanitizeNumber($event, row, 'min_sale_price')"
                                        class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs text-center font-mono"
                                        placeholder="0.00"
                                    />
                                </td>
                                <!-- Min Stock -->
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="row.min_stock"
                                        type="text"
                                        inputmode="decimal"
                                        dir="ltr"
                                        @input="sanitizeNumber($event, row, 'min_stock')"
                                        class="w-16 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs text-center font-mono"
                                        placeholder="0"
                                    />
                                </td>
                                <!-- Initial Stock -->
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="row.initial_stock"
                                        type="text"
                                        inputmode="decimal"
                                        dir="ltr"
                                        @input="sanitizeNumber($event, row, 'initial_stock')"
                                        class="w-16 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs text-center font-mono"
                                        placeholder="0"
                                    />
                                </td>
                                <!-- Storage Location -->
                                <td class="px-3 py-2">
                                    <input
                                        v-model="row.storage_location"
                                        type="text"
                                        dir="ltr"
                                        class="w-14 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs text-center"
                                        placeholder="A1"
                                    />
                                </td>
                                <!-- Allow Price Change -->
                                <td class="px-3 py-2 text-center">
                                    <input
                                        v-model="row.allow_price_change"
                                        type="checkbox"
                                        class="w-4 h-4 rounded border-gray-300 text-indigo-600"
                                    />
                                </td>
                                <!-- Active -->
                                <td class="px-3 py-2 text-center">
                                    <input
                                        v-model="row.is_active"
                                        type="checkbox"
                                        class="w-4 h-4 rounded border-gray-300 text-indigo-600"
                                    />
                                </td>
                                <!-- Delete -->
                                <td class="px-3 py-2 text-center">
                                    <button
                                        type="button"
                                        @click="removeWarehouseRow(index)"
                                        class="text-red-500 hover:text-red-700"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="form.warehouse_data.length === 0">
                                <td colspan="10" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('common.no_records') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="form.errors.warehouse_data" class="text-sm text-red-500">{{ form.errors.warehouse_data }}</p>
            </form>
        </template>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button
                    @click="handleClose"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </button>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: Boolean,
    part: Object,
    warehouses: {
        type: Array,
        default: () => [],
    },
    existingBalances: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const page = usePage();

const selectedWarehouseId = ref('');

// Convert Arabic numerals to English
function toEnglish(str) {
    if (!str) return '';
    const arabicNums = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫'];
    const englishNums = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];
    
    return String(str).replace(/[٠-٩٫]/g, (d) => {
        const idx = arabicNums.indexOf(d);
        return idx !== -1 ? englishNums[idx] : d;
    });
}

// Sanitize number input - keep only English digits and decimal
function sanitizeNumber(event, row, field) {
    let value = event.target.value;
    value = toEnglish(value);
    // Keep only numbers and one decimal point
    value = value.replace(/[^0-9.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    event.target.value = value;
    row[field] = value ? parseFloat(value) || 0 : 0;
}

const getInitialWarehouseData = () => {
    if (props.existingBalances && props.existingBalances.length > 0) {
        return props.existingBalances.map(balance => ({
            warehouse_id: balance.warehouse_id,
            cost_price: balance.wac_cost || 0,
            sale_price: balance.sale_price || 0,
            min_sale_price: balance.min_sale_price || 0,
            initial_stock: balance.qty_on_hand || 0,
            min_stock: balance.min_stock || 0,
            storage_location: balance.storage_location || '',
            allow_price_change: balance.allow_price_change || false,
            is_active: balance.is_active ?? true,
        }));
    }
    
    // Default: one row with current center's warehouse
    const currentWarehouse = props.warehouses.find(w => w.is_default) || props.warehouses[0];
    if (currentWarehouse) {
        return [{
            warehouse_id: currentWarehouse.id,
            cost_price: 0,
            sale_price: 0,
            min_sale_price: 0,
            initial_stock: 0,
            min_stock: 0,
            storage_location: '',
            allow_price_change: false,
            is_active: true,
        }];
    }
    
    return [];
};

const form = useForm({
    part_id: null,
    warehouse_data: getInitialWarehouseData(),
});

watch(() => props.part, (part) => {
    if (part) {
        form.part_id = part.id;
        form.warehouse_data = getInitialWarehouseData();
    }
}, { immediate: true, deep: true });

const availableWarehouses = computed(() => {
    const usedIds = form.warehouse_data.map(r => r.warehouse_id);
    return props.warehouses.filter(w => !usedIds.includes(w.id));
});

const isWarehouseAdded = (warehouseId) => {
    return form.warehouse_data.some(r => r.warehouse_id === warehouseId);
};

const getWarehouseName = (warehouseId) => {
    const warehouse = props.warehouses.find(w => w.id === warehouseId);
    return warehouse?.name || '';
};

const getWarehouseCenter = (warehouseId) => {
    const warehouse = props.warehouses.find(w => w.id === warehouseId);
    return warehouse?.center_name || '';
};

const addWarehouse = () => {
    if (!selectedWarehouseId.value || isWarehouseAdded(selectedWarehouseId.value)) return;
    
    const warehouse = props.warehouses.find(w => w.id === selectedWarehouseId.value);
    const defaultRow = form.warehouse_data[0];
    
    form.warehouse_data.push({
        warehouse_id: selectedWarehouseId.value,
        cost_price: defaultRow?.cost_price || 0,
        sale_price: defaultRow?.sale_price || 0,
        min_sale_price: defaultRow?.min_sale_price || 0,
        initial_stock: 0,
        min_stock: defaultRow?.min_stock || 0,
        storage_location: '',
        allow_price_change: defaultRow?.allow_price_change || false,
        is_active: true,
    });
    
    selectedWarehouseId.value = '';
};

const removeWarehouseRow = (index) => {
    if (form.warehouse_data.length > 1) {
        form.warehouse_data.splice(index, 1);
    }
};

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
    selectedWarehouseId.value = '';
};

const handleClose = () => {
    close();
};

const submit = () => {
    form.post(route('app.inventory.parts.stock.update', form.part_id), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
};
</script>