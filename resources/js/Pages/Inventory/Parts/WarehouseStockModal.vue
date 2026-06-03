<template>
    <DialogModal :show="show" @close="handleClose" max-width="md">
        <template #title>
            <span class="text-xl font-bold">
                {{ editRowData ? warehouseName : $t('inventory.parts.add_warehouse') }}
            </span>
        </template>

        <template #content>
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Warehouse -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('inventory.parts.warehouse') }} <span class="text-red-500">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.warehouse_id"
                        :options="warehouseOptions"
                        option-label="label"
                        option-value="value"
                        :label="''"
                        :placeholder="$t('inventory.parts.select_warehouse')"
                        :error="form.errors.warehouse_id"
                        :disabled="!!editRowData"
                    />
                    <p v-if="form.errors.warehouse_id" class="mt-1 text-xs text-red-500">{{ form.errors.warehouse_id }}</p>
                </div>

                <!-- Cost & Sale Price -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.wac_cost') }}
                        </label>
                        <input
                            v-model="form.cost_price"
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            @input="sanitizeNumber($event, 'cost_price')"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="0.00"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.sale_price') }}
                        </label>
                        <input
                            v-model="form.sale_price"
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            @input="sanitizeNumber($event, 'sale_price')"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <!-- Min Sale Price & Initial Stock -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.min_sale_price') }}
                        </label>
                        <input
                            v-model="form.min_sale_price"
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            @input="sanitizeNumber($event, 'min_sale_price')"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="0.00"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.initial_stock') }}
                            <span v-if="editRowData" class="text-xs text-gray-400 font-normal">({{ $t('common.read_only') }})</span>
                        </label>
                        <input
                            v-model="form.initial_stock"
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            @input="sanitizeNumber($event, 'initial_stock')"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                            :placeholder="editRowData ? form.initial_stock : '0'"
                            :disabled="!!editRowData"
                            :readonly="!!editRowData"
                        />
                    </div>
                </div>

                <!-- Min Stock & Storage Location -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.min_stock') }}
                        </label>
                        <input
                            v-model="form.min_stock"
                            type="text"
                            inputmode="decimal"
                            dir="ltr"
                            @input="sanitizeNumber($event, 'min_stock')"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="0"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.storage_location') }}
                        </label>
                        <input
                            v-model="form.storage_location"
                            type="text"
                            dir="ltr"
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            placeholder="A1"
                        />
                    </div>
                </div>

                <!-- Checkboxes -->
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('common.active') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.allow_price_change"
                            type="checkbox"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('inventory.parts.allow_price_change') }}</span>
                    </label>
                </div>
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
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                >
                    {{ editRowData ? $t('common.update') : $t('common.add') }}
                </button>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import { ref, computed, watch } from 'vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: Boolean,
    warehouses: {
        type: Array,
        default: () => [],
    },
    usedWarehouseIds: {
        type: Array,
        default: () => [],
    },
    editRowData: {
        type: Object,
        default: null,
    },
    warehouseName: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'add', 'update']);
const page = usePage();

const warehouseOptions = computed(() => {
    const used = [...props.usedWarehouseIds];
    // When editing, the row's warehouse should still be available
    if (props.editRowData) {
        used = used.filter(id => id !== props.editRowData.warehouse_id);
    }
    return props.warehouses
        .filter(w => !used.includes(w.id))
        .map(w => ({
            value: w.id,
            label: `${w.name} - ${w.center_name}`
        }));
});

function toEnglish(str) {
    if (!str) return '';
    const arabicNums = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫'];
    const englishNums = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'];
    return String(str).replace(/[٠-٩٫]/g, (d) => {
        const idx = arabicNums.indexOf(d);
        return idx !== -1 ? englishNums[idx] : d;
    });
}

function sanitizeNumber(event, field) {
    let value = event.target.value;
    value = toEnglish(value);
    value = value.replace(/[^0-9.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    event.target.value = value;
    form[field] = value ? parseFloat(value) || 0 : 0;
}

const form = useForm({
    warehouse_id: '',
    cost_price: 0,
    sale_price: 0,
    min_sale_price: 0,
    initial_stock: 0,
    min_stock: 0,
    storage_location: '',
    is_active: true,
    allow_price_change: false,
});

// When editRowData changes, pre-fill the form
watch(
    () => props.editRowData,
    (data) => {
        if (data && data.warehouse_id != null) {
            form.warehouse_id = data.warehouse_id ?? '';
            form.cost_price = data.cost_price ?? 0;
            form.sale_price = data.sale_price ?? 0;
            form.min_sale_price = data.min_sale_price ?? 0;
            form.initial_stock = data.initial_stock ?? 0;
            form.min_stock = data.min_stock ?? 0;
            form.storage_location = data.storage_location ?? '';
            form.is_active = data.is_active ?? true;
            form.allow_price_change = data.allow_price_change ?? false;
        } else {
            form.reset();
            form.is_active = true;
            form.allow_price_change = false;
        }
    },
    { immediate: true, deep: true }
);

const handleClose = () => {
    emit('close');
};

const submit = () => {
    const data = {
        warehouse_id: form.warehouse_id,
        cost_price: form.cost_price,
        sale_price: form.sale_price,
        min_sale_price: form.min_sale_price,
        initial_stock: form.initial_stock,
        min_stock: form.min_stock,
        storage_location: form.storage_location,
        is_active: form.is_active,
        allow_price_change: form.allow_price_change,
    };

    if (props.editRowData) {
        emit('update', data);
    } else {
        emit('add', data);
    }
    emit('close');
};
</script>
