<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center shadow-lg shadow-green-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">
                        {{ part ? $t('inventory.parts.edit') : $t('quotes.show.add_part') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ quote.code }}</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Source Selector -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    {{ $t('quotes.parts.source') }}
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <button type="button" @click="form.source = 'warehouse'" :class="[
                        'relative p-4 rounded-xl border-2 text-start transition-all duration-200',
                        form.source === 'warehouse'
                            ? 'border-green-500 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 shadow-lg shadow-green-500/10'
                            : 'border-gray-200 dark:border-gray-700 hover:border-green-300 dark:hover:border-green-600 hover:shadow-md'
                    ]">
                        <div class="flex items-center gap-2 font-bold text-gray-800 dark:text-gray-200 mb-1">
                            <span class="text-xl">🏭</span>
                            <span>{{ $t('quotes.parts.warehouse') }}</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('quotes.parts.warehouse_hint') }}
                        </div>
                        <div v-if="form.source === 'warehouse'"
                            class="absolute top-2 end-2 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <button type="button" @click="form.source = 'external'" :class="[
                        'relative p-4 rounded-xl border-2 text-start transition-all duration-200',
                        form.source === 'external'
                            ? 'border-blue-500 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 shadow-lg shadow-blue-500/10'
                            : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md'
                    ]">
                        <div class="flex items-center gap-2 font-bold text-gray-800 dark:text-gray-200 mb-1">
                            <span class="text-xl">🛒</span>
                            <span>{{ $t('quotes.parts.external') }}</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('quotes.parts.external_hint') }}
                        </div>
                        <div v-if="form.source === 'external'"
                            class="absolute top-2 end-2 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <button type="button" @click="form.source = 'customer'" :class="[
                        'relative p-4 rounded-xl border-2 text-start transition-all duration-200',
                        form.source === 'customer'
                            ? 'border-orange-500 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/30 dark:to-amber-900/30 shadow-lg shadow-orange-500/10'
                            : 'border-gray-200 dark:border-gray-700 hover:border-orange-300 dark:hover:border-orange-600 hover:shadow-md'
                    ]">
                        <div class="flex items-center gap-2 font-bold text-gray-800 dark:text-gray-200 mb-1">
                            <span class="text-xl">👤</span>
                            <span>{{ $t('quotes.parts.customer') }}</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $t('quotes.parts.customer_hint') }}
                        </div>
                        <div v-if="form.source === 'customer'"
                            class="absolute top-2 end-2 w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Linked Service (only shown when opening from Parts tab) -->
            <div v-if="showServiceSelect"
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('quotes.service_modal.service') }}
                    <span class="text-xs font-normal text-gray-400 ms-1">({{ $t('common.optional') || 'اختياري'
                        }})</span>
                </label>
                <SearchableSelect v-model="form.quote_line_id" :options="serviceOptions" option-label="label"
                    option-value="value" :placeholder="$t('common.none') || 'بدون خدمة'" />
            </div>

            <!-- Part Search (for warehouse source) -->
            <div v-if="form.source === 'warehouse'"
                class="bg-green-50/50 dark:bg-green-900/10 rounded-xl p-4 border border-green-200 dark:border-green-800">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('inventory.parts.search_label') }}
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" v-model="partSearch" @input="debouncedSearch"
                        :placeholder="$t('inventory.parts.search')"
                        class="w-full ps-12 pe-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" />
                    <!-- Search Results Dropdown -->
                    <div v-if="searchResults.length > 0"
                        class="absolute z-20 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-2xl max-h-60 overflow-auto">
                        <button v-for="result in searchResults" :key="result.id" type="button"
                            @click="selectPart(result)"
                            class="w-full px-4 py-3 text-start hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <div class="flex items-center justify-between">
                                <div class="font-medium text-gray-900 dark:text-white">{{ getName(result) }}</div>
                                <span :class="[
                                    'text-xs px-2 py-0.5 rounded-full font-medium',
                                    (result.inventory_balances_sum_qty_on_hand || 0) > 0
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                ]">
                                    {{ $t('inventory.stock.available') }}: {{ result.inventory_balances_sum_qty_on_hand
                                        || 0 }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500 flex items-center gap-2" dir="ltr">
                                <span>{{ result.sku }}</span>
                                <span class="text-green-600 font-mono">{{ formatCurrency(result.default_sale_price) }}
                                    {{
                                        $t('common.currency') }}</span>
                            </div>
                        </button>
                    </div>
                </div>
                <div v-if="selectedPart" class="mt-3 space-y-2">
                    <div
                        class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg border border-green-300 dark:border-green-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium text-green-800 dark:text-green-200">{{ getName(selectedPart)
                                    }}</span>
                                <span class="text-xs text-green-600 dark:text-green-400 ms-2">
                                    ({{ $t('inventory.stock.available') }}: {{ availableStock }})
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="clearPartSelection"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Stock Warning -->
                    <div v-if="stockWarning"
                        class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-sm text-red-700 dark:text-red-300">
                            {{ $t('inventory.stock.insufficient_warning') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Manual Fields (for external/customer sources) -->
            <div v-if="form.source !== 'warehouse'" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('inventory.parts.part_number') }}
                    </label>
                    <input type="text" v-model="form.part_number" dir="ltr"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 font-mono transition-all" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('inventory.parts.unit') }} <span class="text-red-500">*</span>
                    </label>
                    <SearchableSelect v-model="form.unit_id" :options="unitOptions" option-label="label"
                        option-value="value" :placeholder="$t('common.choose') || 'اختر'" />
                </div>
            </div>

            <!-- Part Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('inventory.parts.name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" v-model="form.name" required :readonly="form.source === 'warehouse' && selectedPart"
                    :class="[
                        'w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all',
                        form.source === 'warehouse' && selectedPart
                            ? 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 cursor-not-allowed'
                            : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-200 dark:border-gray-700'
                    ]" />
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('inventory.parts.description') }}
                </label>
                <textarea v-model="form.description" rows="2"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none transition-all"></textarea>
            </div>

            <!-- Pricing Row -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wide">
                            {{ $t('inventory.parts.unit_price') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" inputmode="decimal" v-model="form.unit_price" dir="ltr" required
                                @input="form.unit_price = normalizeArabicNumerals($event.target.value)"
                                :readonly="form.source === 'customer' || (showToggles && form.include_in_package)"
                                :class="[
                                    'w-full px-3 py-2.5 border rounded-lg font-mono text-end transition-all',
                                    (form.source === 'customer' || (showToggles && form.include_in_package))
                                        ? 'bg-gray-200 dark:bg-gray-900 text-gray-500 border-gray-300 dark:border-gray-600 cursor-not-allowed'
                                        : priceWarning
                                            ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-red-400 dark:border-red-600 focus:ring-2 focus:ring-red-500'
                                            : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-green-500'
                                ]" />
                            <div v-if="priceWarning" class="text-xs text-red-500 mt-1">
                                {{ $t('inventory.parts.min_price_warning') }}: {{ formatCurrency(minSalePrice) }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wide">
                            {{ $t('quotes.form.discount') }}
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.discount" dir="ltr"
                            @input="form.discount = normalizeArabicNumerals($event.target.value)"
                            :readonly="showToggles && form.include_in_package" :class="[
                                'w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-end focus:ring-2 focus:ring-green-500 transition-all',
                                (showToggles && form.include_in_package)
                                    ? 'bg-gray-200 dark:bg-gray-900 text-gray-500 cursor-not-allowed'
                                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white'
                            ]" />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wide">
                            {{ $t('work_orders.item.qty') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.qty" dir="ltr" required
                            @input="form.qty = normalizeArabicNumerals($event.target.value)"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-end focus:ring-2 focus:ring-green-500 transition-all" />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 uppercase tracking-wide">
                            {{ $t('quotes.show.amount') }}
                        </label>
                        <div class="px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-900 font-mono text-end text-gray-900 dark:text-white font-bold"
                            dir="ltr">
                            {{ formatCurrency(calculatedTotal) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toggles (only when from service form) -->
            <div v-if="showToggles"
                class="flex items-center gap-6 px-4 py-3 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" v-model="form.include_in_package"
                        class="w-5 h-5 text-green-600 bg-white dark:bg-gray-800 border-gray-300 rounded focus:ring-green-500 cursor-pointer" />
                    <span
                        class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                        {{ $t('work_orders.item.include_in_package') }}
                    </span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" v-model="form.hide_on_print"
                        class="w-5 h-5 text-green-600 bg-white dark:bg-gray-800 border-gray-300 rounded focus:ring-green-500 cursor-pointer" />
                    <span
                        class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                        {{ $t('work_orders.item.hide_on_print') }}
                    </span>
                </label>
            </div>

            <!-- Calculated Total -->
            <div
                class="flex items-center justify-between p-5 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl shadow-lg shadow-green-500/20">
                <span class="text-sm font-medium text-white/90">
                    {{ $t('quotes.show.total') }}
                </span>
                <span class="text-2xl font-bold text-white font-mono" dir="ltr">
                    {{ formatCurrency(calculatedTotal) }} {{ $t('common.currency') }}
                </span>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-5 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors font-medium">
                {{ $t('common.cancel') }}
            </button>
            <button type="button" @click="submitForm"
                :disabled="form.processing || !form.name || !form.qty || (form.unit_price === '' || form.unit_price === null || form.unit_price === undefined) || (form.source !== 'warehouse' && !form.unit_id)"
                class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all font-medium shadow-lg shadow-green-500/20 hover:shadow-xl hover:shadow-green-500/30">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';

// Native debounce implementation
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Convert Arabic numerals to English
function normalizeArabicNumerals(value) {
    const arabicToEnglish = {
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
        '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
    };
    return String(value).split('').map(char => arabicToEnglish[char] || char).join('');
}

const props = defineProps({
    show: Boolean,
    quote: Object,
    part: Object, // Editing existing part
    quoteLineId: Number, // When adding from service form
    quoteLines: Array, // All quote lines for service dropdown
    units: { type: Array, default: () => [] },
    showServiceSelect: { type: Boolean, default: true }, // Show service dropdown
    showToggles: { type: Boolean, default: false }, // Show include_in_package & hide_on_print
    pendingMode: { type: Boolean, default: false }, // When true, emit data instead of saving
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();

// State
const partSearch = ref('');
const searchResults = ref([]);
const selectedPart = ref(null);

// Form
const form = useForm({
    source: 'external',
    quote_line_id: null,
    part_id: null,
    name: '',
    part_number: '',
    unit_id: null,
    description: '',
    qty: 1,
    unit_price: 0,
    discount: 0,
    include_in_package: true,
    hide_on_print: false,
});

// Computed
const calculatedTotal = computed(() => {
    const price = parseFloat(form.unit_price) || 0;
    const qty = parseFloat(form.qty) || 0;
    const discount = parseFloat(form.discount) || 0;
    return Math.max(0, (price * qty) - discount);
});

// Options for SearchableSelect components
const serviceOptions = computed(() => {
    return (props.quoteLines || []).map(line => ({
        value: line.id,
        label: line.description || getName(line.service)
    }));
});

const unitOptions = computed(() => {
    return (props.units || []).map(unit => ({
        value: unit.id,
        label: getName(unit)
    }));
});

// Stock tracking for warehouse parts
const availableStock = computed(() => {
    if (!selectedPart.value) return 0;
    return selectedPart.value.inventory_balances_sum_qty_on_hand || 0;
});

const stockWarning = computed(() => {
    if (form.source !== 'warehouse' || !selectedPart.value) return false;
    return (parseFloat(form.qty) || 0) > availableStock.value;
});

// Price validation for warehouse parts
const minSalePrice = computed(() => {
    if (!selectedPart.value) return 0;
    return parseFloat(selectedPart.value.min_sale_price) || 0;
});

// Check if net unit price (after discount) is below minimum
const netUnitPrice = computed(() => {
    const price = parseFloat(form.unit_price) || 0;
    const qty = parseFloat(form.qty) || 1;
    const discount = parseFloat(form.discount) || 0;
    // Calculate per-unit net price (total - discount) / qty
    return (price * qty - discount) / qty;
});

const priceWarning = computed(() => {
    if (form.source !== 'warehouse' || !selectedPart.value || minSalePrice.value === 0) return false;
    return netUnitPrice.value < minSalePrice.value;
});

// Methods
const debouncedSearch = debounce(async () => {
    if (partSearch.value.length < 2) {
        searchResults.value = [];
        return;
    }
    try {
        const response = await axios.get(route('app.inventory.parts.search'), {
            params: { q: partSearch.value }
        });
        searchResults.value = response.data;
    } catch (e) {
        searchResults.value = [];
    }
}, 300);

function selectPart(part) {
    selectedPart.value = part;
    form.part_id = part.id;
    form.name = getName(part);
    form.part_number = part.sku || part.barcode || '';
    form.unit_price = parseFloat(part.default_sale_price) || 0;
    form.unit_id = part.unit_id;
    partSearch.value = '';
    searchResults.value = [];
}

function clearPartSelection() {
    selectedPart.value = null;
    form.part_id = null;
    form.name = '';
    form.part_number = '';
    form.unit_price = 0;
    form.unit_id = null;
}

function submitForm() {
    // Calculate total
    const total = calculateTotal();

    if (props.pendingMode) {
        // Return part data instead of saving (for new service creation)
        const partData = {
            ...form.data(),
            total: total
        };
        emit('saved', partData);
        return;
    }

    if (props.part) {
        // Update existing part
        form.put(route('app.quotes.parts.update', { quote: props.quote.id, quotePart: props.part.id }), {
            onSuccess: () => {
                emit('saved');
            },
        });
    } else {
        // Add new part
        form.post(route('app.quotes.parts.store', { quote: props.quote.id }), {
            onSuccess: () => {
                emit('saved');
            },
        });
    }
}

function calculateTotal() {
    const price = parseFloat(form.unit_price) || 0;
    const qty = parseFloat(form.qty) || 0;
    const discount = parseFloat(form.discount) || 0;
    return Math.max(0, (price * qty) - discount);
}

// Watch source changes
watch(() => form.source, (newSource) => {
    if (newSource === 'customer') {
        form.unit_price = 0;
    }
    if (newSource !== 'warehouse') {
        clearPartSelection();
    }
});

// Watch include_in_package toggle (only when showToggles is enabled)
watch(() => form.include_in_package, (isIncluded) => {
    if (props.showToggles && isIncluded) {
        form.unit_price = 0;
        form.discount = 0;
    }
});

// Reset form when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        partSearch.value = '';
        searchResults.value = [];
        selectedPart.value = null;

        if (props.part) {
            // Editing
            form.source = props.part.source;
            form.quote_line_id = props.part.quote_line_id;
            form.part_id = props.part.part_id;
            form.name = props.part.name;
            form.part_number = props.part.part_number || '';
            form.unit_id = props.part.unit_id;
            form.description = props.part.description || '';
            form.qty = props.part.qty;
            form.unit_price = props.part.unit_price;
            form.discount = props.part.discount;
            form.include_in_package = props.part.include_in_package;
            form.hide_on_print = props.part.hide_on_print;

            if (props.part.part) {
                selectedPart.value = props.part.part;
            }
        } else {
            // Adding new
            form.reset();
            form.source = 'external';
            form.quote_line_id = props.quoteLineId || null;
            form.qty = 1;
            form.include_in_package = true;
            form.hide_on_print = false;
        }
    }
});
</script>
