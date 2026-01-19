<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('inventory.parts.search_label') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="relative">
                        <input type="text" v-model="searchQuery" @input="debouncedSearch"
                            @focus="showSearchResults = true" :placeholder="$t('inventory.parts.search_placeholder')"
                            class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all font-medium"
                            :class="{ 'ring-2 ring-blue-500 bg-white dark:bg-gray-800': showSearchResults }"
                            :disabled="!!item" />
                        <div
                            class="absolute left-0 top-0 h-full w-11 flex items-center justify-center text-gray-400 pointer-events-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <div v-if="searching" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="animate-spin w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div v-if="showSearchResults && searchResults.length > 0"
                        class="absolute z-20 w-full mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 max-h-72 overflow-y-auto transform origin-top transition-all">
                        <div class="p-2 space-y-1">
                            <button v-for="part in searchResults" :key="part.id" type="button" @click="selectPart(part)"
                                class="w-full px-4 py-3 flex items-center justify-between hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg group transition-colors text-start">
                                <div>
                                    <div
                                        class="font-bold text-gray-900 dark:text-white text-sm group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                        {{ part.name_ar }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            class="text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded">{{
                                                part.sku }}</span>
                                        <span class="text-xs text-gray-500">{{ part.unit?.name_ar || part.unit?.name_en
                                            }}</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white" dir="ltr">
                                        {{ formatCurrency(part.cost_price) }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-medium mt-0.5"
                                        :class="{ 'text-red-500': part.stock_qty <= 0, 'text-green-600': part.stock_qty > 0 }">
                                        {{ part.stock_qty }} {{ $t('inventory.stock.in_stock') }}
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Part Card -->
            <div v-if="selectedPart"
                class="bg-blue-50 dark:bg-blue-900/10 rounded-xl p-4 border border-blue-100 dark:border-blue-800/30 flex items-start gap-4">
                <div
                    class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ selectedPart.name_ar }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">{{ selectedPart.sku }}</p>
                </div>
                <div v-if="selectedPart.stock_qty !== undefined"
                    class="text-xs font-medium px-2 py-1 bg-white dark:bg-gray-800 rounded border border-blue-100 dark:border-blue-800 text-blue-600 dark:text-blue-400 whitespace-nowrap">
                    {{ selectedPart.stock_qty }} {{ $t('inventory.stock.in_stock') }}
                </div>
            </div>

            <!-- Form Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('common.quantity') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" v-model.number="form.qty" step="0.01" min="0.01" dir="ltr" lang="en"
                            @input="sanitizeInput"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center font-mono font-bold text-lg" />
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-400 text-xs">{{ selectedPart?.unit?.name_ar ||
                                selectedPart?.unit?.name_en ||
                                '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Unit Cost -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('inventory.parts.cost_price') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" v-model.number="form.unit_cost" step="0.01" min="0" dir="ltr" lang="en"
                            @input="sanitizeInput"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center font-mono font-bold text-lg" />
                    </div>
                </div>

                <!-- Discount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('common.discount') }}
                    </label>
                    <input type="number" v-model.number="form.discount" step="0.01" min="0" dir="ltr" lang="en"
                        @input="sanitizeInput"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center font-mono" />
                </div>

                <!-- Total Display (Readonly field in grid) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('common.total') }}
                    </label>
                    <div class="w-full px-4 py-3 border border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white font-mono font-bold text-lg text-center flex items-center justify-center"
                        dir="ltr">
                        {{ formatCurrency(calculatedTotal) }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <!-- Contains -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('inventory.parts.contains') }}
                    </label>
                    <div
                        class="px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-center text-sm border border-gray-100 dark:border-gray-800">
                        -
                    </div>
                </div>
                <!-- Warehouse Unit -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('inventory.units.warehouse_unit') }}
                    </label>
                    <div
                        class="px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 text-center text-sm border border-gray-100 dark:border-gray-800">
                        -
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
import axios from 'axios';
import { debounce } from 'lodash-es';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    item: Object,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();

const form = ref({
    part_id: '',
    qty: 1,
    unit_cost: 0,
    discount: 0,
});

const { sanitizeInput, formatCurrency: formatCurrencyEn } = useNumberFormat();

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const showSearchResults = ref(false);
const selectedPart = ref(null);

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.item) {
            form.value = { ...props.item };
            selectedPart.value = props.item.part;
            searchQuery.value = props.item.part?.name_ar || '';
        } else {
            resetForm();
        }
    }
});

const resetForm = () => {
    form.value = {
        part_id: '',
        qty: 1,
        unit_cost: 0,
        discount: 0,
    };
    searchQuery.value = '';
    selectedPart.value = null;
    searchResults.value = [];
};

const calculatedTotal = computed(() => {
    const subtotal = (form.value.qty || 0) * (form.value.unit_cost || 0);
    return Math.max(0, subtotal - (form.value.discount || 0));
});

const debouncedSearch = debounce(async () => {
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
        showSearchResults.value = true;
    } catch (error) {
        console.error(error);
    } finally {
        searching.value = false;
    }
}, 300);

const selectPart = (part) => {
    selectedPart.value = part;
    form.value.part_id = part.id;
    form.value.unit_cost = part.cost_price || 0;
    searchQuery.value = part.name_ar;
    showSearchResults.value = false;
};

const formatCurrency = (value) => {
    return formatCurrencyEn(value);
};

const submit = () => {
    if (!selectedPart.value) return;

    emit('saved', {
        ...form.value,
        part: selectedPart.value,
        total: calculatedTotal.value
    });
};
</script>
