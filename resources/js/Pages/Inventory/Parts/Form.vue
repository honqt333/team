<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('app.inventory.parts.index')"
                        class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        {{ $t('common.back') }}
                    </Link>
                    <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ part ? $t('inventory.parts.edit') : $t('inventory.parts.add') }}
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Main Form -->
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                <!-- Basic Info Section -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ $t('inventory.parts.basic_info') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- SKU / Barcode Combined -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('inventory.parts.sku_or_barcode') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.sku"
                                type="text"
                                dir="ltr"
                                :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500', form.errors.sku ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                                :placeholder="$t('inventory.parts.sku_or_barcode_placeholder')"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.sku_or_barcode_hint') }}</p>
                            <p v-if="form.errors.sku" class="mt-1 text-sm text-red-500">{{ form.errors.sku }}</p>
                        </div>

                        <!-- Name AR -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('inventory.parts.name_ar') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_ar"
                                type="text"
                                :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500', form.errors.name_ar ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                            />
                            <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-500">{{ form.errors.name_ar }}</p>
                        </div>

                        <!-- Name EN -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('inventory.parts.name_en') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_en"
                                type="text"
                                dir="ltr"
                                :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500', form.errors.name_en ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                            />
                            <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-500">{{ form.errors.name_en }}</p>
                        </div>

                        <!-- Unit -->
                        <div>
                            <SearchableSelect
                                v-model="form.unit_id"
                                :options="unitOptions"
                                :label="$t('inventory.parts.unit')"
                                required
                                :error="form.errors.unit_id"
                            />
                        </div>

                        <!-- Category -->
                        <div>
                            <SearchableSelect
                                v-model="form.category_id"
                                :options="categoryOptions"
                                :label="$t('inventory.parts.category')"
                                :placeholder="$t('inventory.parts.category_placeholder')"
                                :error="form.errors.category_id"
                            />
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('inventory.parts.description') }}
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Warehouse Stock Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('inventory.parts.warehouse_stock') }}</h3>
                        <button
                            v-if="warehouses.length > 1"
                            type="button"
                            @click="addWarehouseRow"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $t('inventory.parts.add_warehouse') }}
                        </button>
                    </div>

                    <!-- Stock Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900/50">
                                    <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.warehouse') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.cost_price') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.sale_price') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.min_sale_price') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.initial_stock') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.min_stock') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.storage_location') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('inventory.parts.allow_price_change') }}
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $t('common.status') }}
                                    </th>
                                    <th class="px-4 py-3 w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="(row, index) in form.warehouse_data" :key="index">
                                    <!-- Warehouse Select -->
                                    <td class="px-4 py-3">
                                        <SearchableSelect
                                            v-model="row.warehouse_id"
                                            :options="availableWarehouses(row.warehouse_id, index)"
                                            :option-label="(wh) => `${wh.name} - ${wh.center_name}`"
                                            option-value="id"
                                            :placeholder="$t('inventory.parts.select_warehouse')"
                                            :disabled="index > 0 && isWarehouseSelected(row.warehouse_id, index)"
                                            compact
                                        />
                                    </td>
                                    <!-- Cost Price -->
                                    <td class="px-4 py-3">
                                        <input
                                            v-model.number="row.cost_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            dir="ltr"
                                            class="w-24 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm text-center focus:ring-2 focus:ring-blue-500"
                                            :placeholder="'0.00'"
                                        />
                                    </td>
                                    <!-- Sale Price -->
                                    <td class="px-4 py-3">
                                        <input
                                            v-model.number="row.sale_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            dir="ltr"
                                            class="w-24 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm text-center focus:ring-2 focus:ring-blue-500"
                                            :placeholder="'0.00'"
                                        />
                                    </td>
                                    <!-- Min Sale Price -->
                                    <td class="px-4 py-3">
                                        <input
                                            v-model.number="row.min_sale_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            dir="ltr"
                                            class="w-24 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm text-center focus:ring-2 focus:ring-blue-500"
                                            :placeholder="'0.00'"
                                        />
                                    </td>
                                    <!-- Initial Stock -->
                                    <td class="px-4 py-3">
                                        <input
                                            v-model.number="row.initial_stock"
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            dir="ltr"
                                            class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm text-center focus:ring-2 focus:ring-blue-500"
                                            :placeholder="'0'"
                                        />
                                    </td>
                                    <!-- Min Stock -->
                                    <td class="px-4 py-3">
                                        <input
                                            v-model.number="row.min_stock"
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            dir="ltr"
                                            class="w-16 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm text-center focus:ring-2 focus:ring-blue-500"
                                            :placeholder="'0'"
                                        />
                                    </td>
                                    <!-- Storage Location -->
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="row.storage_location"
                                            type="text"
                                            dir="ltr"
                                            class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm text-center focus:ring-2 focus:ring-blue-500"
                                            :placeholder="'A1'"
                                        />
                                    </td>
                                    <!-- Allow Price Change -->
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            v-model="row.allow_price_change"
                                            type="checkbox"
                                            class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                                        />
                                    </td>
                                    <!-- Active -->
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            v-model="row.is_active"
                                            type="checkbox"
                                            class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                                        />
                                    </td>
                                    <!-- Delete -->
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            v-if="form.warehouse_data.length > 1"
                                            type="button"
                                            @click="removeWarehouseRow(index)"
                                            class="text-red-500 hover:text-red-700 p-1"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <p v-if="form.errors.warehouse_data" class="mt-2 text-sm text-red-500">{{ form.errors.warehouse_data }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Link
                        :href="route('app.inventory.parts.index')"
                        class="px-4 py-2.5 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        {{ $t('common.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { computed } from 'vue';

const props = defineProps({
    part: Object,
    units: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    existingBalances: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const unitOptions = computed(() => {
    return props.units.map(unit => ({
        value: unit.id,
        label: page.props.auth.user.locale === 'ar' ? unit.name_ar : (unit.name_en || unit.name_ar)
    }));
});

const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        value: cat.id,
        label: page.props.auth.user.locale === 'ar' ? cat.name_ar : (cat.name_en || cat.name_ar)
    }));
});

// Initialize warehouse data
const getInitialWarehouseData = () => {
    if (props.part && props.existingBalances.length > 0) {
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
    return [{
        warehouse_id: currentWarehouse?.id || '',
        cost_price: 0,
        sale_price: 0,
        min_sale_price: 0,
        initial_stock: 0,
        min_stock: 0,
        storage_location: '',
        allow_price_change: false,
        is_active: true,
    }];
};

const form = useForm({
    sku: props.part?.sku || props.part?.barcode || '',
    name_ar: props.part?.name_ar || '',
    name_en: props.part?.name_en || '',
    unit_id: props.part?.unit_id || '',
    category_id: props.part?.category_id || '',
    description: props.part?.description || '',
    warehouse_data: getInitialWarehouseData(),
});

const availableWarehouses = (selectedId, currentIndex) => {
    const usedIds = form.warehouse_data
        .map((row, idx) => idx !== currentIndex ? row.warehouse_id : null)
        .filter(Boolean);
    
    return props.warehouses.filter(w => !usedIds.includes(w.id) || w.id === selectedId);
};

const isWarehouseSelected = (warehouseId, currentIndex) => {
    if (!warehouseId) return false;
    return form.warehouse_data.some((row, idx) => idx !== currentIndex && row.warehouse_id === warehouseId);
};

const addWarehouseRow = () => {
    const usedIds = form.warehouse_data.map(r => r.warehouse_id).filter(Boolean);
    const availableWarehouse = props.warehouses.find(w => !usedIds.includes(w.id));
    
    form.warehouse_data.push({
        warehouse_id: availableWarehouse?.id || '',
        cost_price: form.warehouse_data[0]?.cost_price || 0,
        sale_price: form.warehouse_data[0]?.sale_price || 0,
        min_sale_price: form.warehouse_data[0]?.min_sale_price || 0,
        initial_stock: 0,
        min_stock: form.warehouse_data[0]?.min_stock || 0,
        storage_location: '',
        allow_price_change: form.warehouse_data[0]?.allow_price_change || false,
        is_active: true,
    });
};

const removeWarehouseRow = (index) => {
    if (form.warehouse_data.length > 1) {
        form.warehouse_data.splice(index, 1);
    }
};

const submit = () => {
    if (props.part) {
        form.put(route('app.inventory.parts.update', props.part.id));
    } else {
        form.post(route('app.inventory.parts.store'));
    }
};
</script>