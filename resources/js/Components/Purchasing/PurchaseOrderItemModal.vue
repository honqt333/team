<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl" z-index-class="z-[60]">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                {{ item ? $t('purchasing.items.edit') : $t('purchasing.items.add') }}
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Search Part -->
            <div class="space-y-3">
                <SearchableSelect
                    v-model="form.part_id"
                    :options="searchResults"
                    :asyncSearch="true"
                    @search="debouncedSearch"
                    @change="onPartSelected"
                    option-label="name_ar"
                    option-value="id"
                    :label="`${$t('inventory.parts.search_label')} *`"
                    :placeholder="$t('inventory.parts.search_placeholder')"
                    :disabled="!!item"
                >
                    <template #option="{ option: part }">
                        <div class="w-full flex items-center justify-between group transition-colors">
                            <div class="text-start">
                                <div class="font-bold text-gray-900 dark:text-white text-sm group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                    {{ part.name_ar }}
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded" dir="ltr" lang="en">
                                        {{ part.sku }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ part.unit?.name_ar || part.unit?.name_en }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-sm font-bold text-gray-900 dark:text-white" dir="ltr" lang="en">
                                    {{ formatCurrency(part.cost_price) }}
                                </div>
                                <div class="text-xs font-medium mt-0.5" dir="ltr" lang="en" :class="{ 'text-red-500': part.stock_qty <= 0, 'text-green-600': part.stock_qty > 0 }">
                                    {{ toEnglish(part.stock_qty) }} {{ $t('inventory.stock.in_stock') }}
                                </div>
                            </div>
                        </div>
                    </template>
                </SearchableSelect>
            </div>

            <!-- Selected Part Card -->
            <div v-if="selectedPart"
                class="bg-blue-50 dark:bg-blue-900/10 rounded-xl p-6 border border-blue-100 dark:border-blue-800/30 flex flex-col items-center text-center gap-4">
                <div
                    class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-800 flex items-center justify-center shrink-0 shadow-sm ring-4 ring-white dark:ring-gray-800">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ selectedPart.name_ar }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-mono font-bold tracking-widest" dir="ltr" lang="en">{{ selectedPart.sku }}</p>
                </div>
                <div v-if="selectedPart.stock_qty !== undefined" dir="ltr" lang="en"
                    class="text-xs font-black px-3 py-1.5 bg-white dark:bg-gray-800 rounded-xl border border-blue-100 dark:border-blue-800 text-blue-600 dark:text-blue-400 whitespace-nowrap shadow-sm">
                    {{ toEnglish(selectedPart.stock_qty) }} {{ $t('inventory.stock.in_stock') }}
                </div>
            </div>

            <!-- Form Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 bg-gray-50 dark:bg-gray-800/30 p-5 rounded-2xl border border-gray-100 dark:border-gray-800">
                <!-- Unit Cost -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('inventory.parts.cost_price') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.unit_cost" inputmode="decimal" dir="ltr" lang="en"
                        @input="e => form.unit_cost = toEnglish(e.target.value)"
                        style="font-family: sans-serif !important;"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center font-bold text-lg shadow-sm" />
                </div>

                <!-- Discount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('common.discount') }}
                    </label>
                    <input type="text" v-model="form.discount" inputmode="decimal" dir="ltr" lang="en"
                        @input="e => form.discount = toEnglish(e.target.value)"
                        style="font-family: sans-serif !important;"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center text-lg shadow-sm" />
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('common.quantity') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" v-model="form.qty" inputmode="decimal" dir="ltr" lang="en"
                            @input="e => form.qty = toEnglish(e.target.value)"
                            style="font-family: sans-serif !important;"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center font-bold text-lg shadow-sm" />
                    </div>
                </div>

                <!-- Tax Included Toggle (Moved here) -->
                <div v-if="tenantTaxSettings?.vat_enabled" class="sm:col-span-3">
                    <label class="flex items-center cursor-pointer p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-blue-200 dark:hover:border-blue-800 transition-colors">
                        <div class="relative flex items-center">
                            <input type="checkbox" :checked="taxIncluded" @change="emit('update:taxIncluded', $event.target.checked)" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </div>
                        <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('purchasing.orders.price_includes_tax') }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Unit Conversion Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 bg-blue-50/50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-800/30">
                <!-- Purchase Unit -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('purchasing.items.purchase_unit') }}
                        </label>
                        <button v-if="form.purchase_unit_id" type="button" 
                            @click="isUnitLocked ? isUnitLocked = false : resetUnit()"
                            class="text-xs font-bold flex items-center gap-1 transition-colors"
                            :class="isUnitLocked ? 'text-blue-600 hover:text-blue-700' : 'text-red-600 hover:text-red-700'">
                            <svg v-if="isUnitLocked" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ isUnitLocked ? 'تغيير' : 'إلغاء' }}
                        </button>
                    </div>
                    <SearchableSelect
                        v-model="form.purchase_unit_id"
                        :options="availableUnits"
                        option-label="name_ar"
                        option-value="id"
                        :placeholder="$t('common.choose')"
                        :disabled="isUnitLocked"
                        required="true"
                    />
                </div>

                <!-- Unit Factor -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5" dir="ltr" lang="en" style="font-family: 'Inter', system-ui, sans-serif !important;">
                        <span class="text-xs text-gray-500 font-normal">1 {{ toEnglish(selectedPurchaseUnitName) }} {{ $t('purchasing.items.contains') }}</span>
                    </label>
                    <div class="relative flex items-center">
                        <input type="text" v-model="form.conversion_factor" inputmode="decimal" dir="ltr" lang="en"
                            @input="e => form.conversion_factor = toEnglish(e.target.value)"
                            style="font-family: sans-serif !important;"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center font-bold text-lg shadow-sm" />
                        <span class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400 text-xs font-bold" dir="ltr" lang="en">
                            {{ toEnglish(selectedPart?.unit?.name_ar || selectedPart?.unit?.name_en || '-') }}
                        </span>
                    </div>
                </div>

                <!-- Inventory Qty -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.items.inventory_qty') }}
                    </label>
                    <div dir="ltr" lang="en" class="w-full px-4 py-3 border border-transparent rounded-xl bg-gray-100 dark:bg-gray-900/50 text-gray-900 dark:text-white font-mono font-bold text-lg text-center flex items-center justify-center">
                        <span class="text-xs text-gray-500 mr-2">{{ selectedPart?.unit?.name_ar || '-' }}</span>
                        {{ toEnglish(calculatedInventoryQty) }}
                    </div>
                </div>
            </div>

            <!-- Financials Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 bg-green-50/50 dark:bg-green-900/10 p-5 rounded-2xl border border-green-100 dark:border-green-800/30">
                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.items.amount') }}
                    </label>
                    <div dir="ltr" lang="en" class="w-full px-4 py-3 border border-transparent rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono font-bold text-lg text-center shadow-sm">
                        {{ formatCurrency(calculatedAmount) }}
                    </div>
                </div>

                <!-- VAT -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.items.vat') }}
                        <span v-if="taxIncluded && tenantTaxSettings?.vat_enabled" class="text-xs text-green-600 dark:text-green-400 font-normal ml-1">({{ $t('common.vat_included') }})</span>
                    </label>
                    <div dir="ltr" lang="en" class="w-full px-4 py-3 border border-transparent rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono font-bold text-lg text-center shadow-sm text-red-500 dark:text-red-400">
                        {{ formatCurrency(calculatedVat) }}
                    </div>
                </div>

                <!-- Total -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.items.total') }}
                    </label>
                    <div dir="ltr" lang="en" class="w-full px-4 py-3 border border-transparent rounded-xl bg-green-100 dark:bg-green-800 text-green-900 dark:text-white font-mono font-black text-xl text-center shadow-sm ring-2 ring-green-500/20">
                        {{ formatCurrency(calculatedTotal) }}
                    </div>
                </div>
            </div>


        </form>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div v-if="item" class="text-sm text-gray-500">
                    <!-- Optional: Delete button or other secondary actions -->
                </div>
                <div v-else></div> <!-- Spacer -->

                <div class="flex items-center gap-3">
                    <button type="button" @click="$emit('close')"
                        class="px-5 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors font-medium">
                        {{ $t('common.cancel') }}
                    </button>
                    <button type="button" @click="submit" :disabled="!selectedPart || form.qty <= 0"
                        class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 disabled:opacity-50 transition-all shadow-lg shadow-blue-500/30 font-bold">
                        <svg v-if="!item" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ item ? $t('common.save') : $t('common.add') }}
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';
import { debounce } from 'lodash-es';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    item: Object,
    units: {
        type: Array,
        default: () => []
    },
    taxIncluded: Boolean,
    tenantTaxSettings: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close', 'saved', 'update:taxIncluded']);
const { t } = useI18n();

const form = ref({
    part_id: '',
    qty: 1,
    unit_cost: 0,
    discount: 0,
    purchase_unit_id: '',
    conversion_factor: 1,
});

const { sanitizeInput, formatCurrency: formatCurrencyEn, toEnglish } = useNumberFormat();

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const selectedPart = ref(null);
const isUnitLocked = ref(true);
const defaultUnitId = ref(null);

const availableUnits = computed(() => {
    if (isUnitLocked.value) {
        return props.units.filter(u => u.id === form.value.purchase_unit_id);
    }
    // When unlocked, show all units to allow selection and maintain visibility
    return props.units;
});

const resetForm = () => {
    form.value = {
        part_id: '',
        qty: 1,
        unit_cost: 0,
        discount: 0,
        purchase_unit_id: '',
        conversion_factor: 1,
    };
    searchQuery.value = '';
    selectedPart.value = null;
    searchResults.value = [];
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.item) {
            form.value = { ...props.item };
            selectedPart.value = props.item.part;
            searchResults.value = props.item.part ? [props.item.part] : [];
            searchQuery.value = props.item.part?.name_ar || '';
            
            // Set locking state for editing
            isUnitLocked.value = true;
            defaultUnitId.value = props.item.purchase_unit_id;
        } else {
            resetForm();
            isUnitLocked.value = true;
        }
    }
}, { immediate: true });



const selectedPurchaseUnitName = computed(() => {
    if (!form.value.purchase_unit_id) return 'وحدة';
    const unit = props.units.find(u => u.id === form.value.purchase_unit_id);
    return unit ? (unit.name_ar || unit.name_en) : 'وحدة';
});

const calculatedInventoryQty = computed(() => {
    return (form.value.qty || 0) * (form.value.conversion_factor || 1);
});

const taxRate = computed(() => {
    return props.tenantTaxSettings?.vat_enabled ? (props.tenantTaxSettings?.parts_vat_rate || 15) : 0;
});

const calculatedAmount = computed(() => {
    const subtotal = (form.value.qty || 0) * (form.value.unit_cost || 0);
    return Math.max(0, subtotal - (form.value.discount || 0));
});

const calculatedVat = computed(() => {
    if (!props.tenantTaxSettings?.vat_enabled) return 0;
    
    const rate = taxRate.value / 100;
    
    if (props.taxIncluded) {
        return calculatedAmount.value - (calculatedAmount.value / (1 + rate));
    } else {
        return calculatedAmount.value * rate;
    }
});

const calculatedTotal = computed(() => {
    if (props.taxIncluded) {
        return calculatedAmount.value;
    } else {
        return calculatedAmount.value + calculatedVat.value;
    }
});

const debouncedSearch = debounce(async (query) => {
    if (query && query.length >= 2) {
        searchQuery.value = query;
    }
    
    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    searching.value = true;
    try {
        const response = await axios.get(route('app.inventory.parts.search'), {
            params: { q: searchQuery.value }
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        searching.value = false;
    }
}, 300);

const onPartSelected = (id) => {
    if (!id) {
        selectedPart.value = null;
        form.value.unit_cost = 0;
        return;
    }
    const part = searchResults.value.find(p => p.id === id);
    if (part) {
        selectPart(part);
    }
};

const selectPart = (part) => {
    selectedPart.value = part;
    form.value.part_id = part.id;
    form.value.unit_cost = part.cost_price || 0;
    
    // Inherit unit from part and lock it
    form.value.purchase_unit_id = part.unit_id;
    defaultUnitId.value = part.unit_id;
    isUnitLocked.value = true;
    
    // Set conversion factor if available on part
    if (part.purchase_conversion) {
        form.value.conversion_factor = part.purchase_conversion;
    } else {
        form.value.conversion_factor = 1;
    }
    
    searchQuery.value = part.name_ar;
};

const resetUnit = () => {
    if (selectedPart.value) {
        form.value.purchase_unit_id = defaultUnitId.value;
        if (selectedPart.value.purchase_conversion) {
            form.value.conversion_factor = selectedPart.value.purchase_conversion;
        } else {
            form.value.conversion_factor = 1;
        }
    }
    isUnitLocked.value = true;
};

const formatCurrency = (value) => {
    return formatCurrencyEn(value);
};

const submit = () => {
    if (!selectedPart.value) return;

    emit('saved', {
        ...form.value,
        part: selectedPart.value,
        total: calculatedTotal.value,
        tax_amount: calculatedVat.value,
        inventory_qty: calculatedInventoryQty.value
    });
};
</script>
