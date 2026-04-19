<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <!-- Modern Header -->
        <template #title>
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center shadow-lg shadow-gray-900/20 transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        {{ part ? $t('inventory.parts.edit') : $t('quotes.show.add_part') }}
                    </h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="inline-flex w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 capitalize">{{ $t(`quotes.parts.${form.source}`) }}</p>
                    </div>
                </div>
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Part Source (Keep it compact but functional) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button type="button" @click="form.source = 'warehouse'" :class="[
                    'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all font-bold text-xs',
                    form.source === 'warehouse' ? 'border-emerald-500 bg-emerald-50/30 text-emerald-700 dark:text-emerald-400' : 'border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
                ]">
                    🏭 {{ $t('quotes.parts.warehouse') }}
                </button>
                <button type="button" @click="form.source = 'external'" :class="[
                    'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all font-bold text-xs',
                    form.source === 'external' ? 'border-blue-500 bg-blue-50/30 text-blue-700 dark:text-blue-400' : 'border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
                ]">
                    🛒 {{ $t('quotes.parts.external') }}
                </button>
                <button type="button" @click="form.source = 'customer'" :class="[
                    'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all font-bold text-xs',
                    form.source === 'customer' ? 'border-amber-500 bg-amber-50/30 text-amber-700 dark:text-amber-400' : 'border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
                ]">
                    👤 {{ $t('quotes.parts.customer') }}
                </button>
            </div>

            <!-- Content Container (Linear Vertical Stack) -->
            <div class="bg-gray-50 dark:bg-gray-900/40 rounded-3xl p-6 space-y-5 border border-gray-100 dark:border-gray-800">
                
                <!-- Warehouse Search -->
                <div v-if="form.source === 'warehouse' && !selectedPart" class="relative" ref="searchWrapper">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 ms-1">
                        {{ $t('inventory.parts.search_label') }}
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="partSearch" @input="debouncedSearch"
                            :placeholder="$t('inventory.parts.search')"
                            class="w-full ps-12 pe-4 py-3.5 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm" />
                        
                        <!-- Search Results -->
                        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                            <div v-if="searchResults.length > 0"
                                class="absolute z-30 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl max-h-64 overflow-auto backdrop-blur-xl">
                                <button v-for="result in searchResults" :key="result.id" type="button"
                                    @click="selectPart(result)"
                                    class="w-full px-5 py-3 text-start hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors border-b border-gray-50 dark:border-gray-700 last:border-0 group/item">
                                    <div class="flex items-center justify-between">
                                        <div class="font-bold text-gray-900 dark:text-white group-hover/item:text-emerald-600 transition-colors">{{ getName(result) }}</div>
                                        <span class="text-[10px] font-mono text-gray-500">{{ result.sku }}</span>
                                    </div>
                                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5">{{ formatCurrency(result.default_sale_price) }}</div>
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>

                <!-- Selected Part Banner (Small) -->
                <div v-if="selectedPart" class="flex items-center justify-between p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs">📦</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ getName(selectedPart) }}</p>
                            <p class="text-[10px] text-emerald-600 font-bold uppercase">{{ $t('inventory.stock.available') }}: {{ availableStock }}</p>
                        </div>
                    </div>
                    <button type="button" @click="clearPartSelection" class="p-1.5 hover:bg-emerald-500/20 rounded-lg text-emerald-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Row 1: Part Number & Unit -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('inventory.parts.part_number') }} / {{ $t('inventory.parts.barcode') }}
                        </label>
                        <input type="text" v-model="form.part_number" dir="ltr"
                            class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 font-mono transition-all text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('inventory.parts.unit') }}
                        </label>
                        <SearchableSelect v-model="form.unit_id" :options="unitOptions" option-label="label"
                            option-value="value" :placeholder="$t('common.choose')" />
                    </div>
                </div>

                <!-- Row 2: Name -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                        {{ $t('inventory.parts.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name" required
                        class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all text-sm font-bold" />
                </div>

                <!-- Row 3: Description -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                        {{ $t('inventory.parts.description') }}
                    </label>
                    <textarea v-model="form.description" rows="2"
                        class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 resize-none transition-all text-sm"
                        :placeholder="$t('inventory.parts.description_placeholder')"></textarea>
                </div>

                <!-- Row 4: Pricing Grid [Price | Discount | Qty] -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('inventory.parts.unit_price') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.unit_price" dir="ltr"
                            @input="form.unit_price = normalizeArabicNumerals($event.target.value)"
                            :readonly="form.source === 'customer' || (form.include_in_package && form.quote_line_id)"
                            :class="[
                                'w-full px-4 py-3 border-2 rounded-2xl text-center font-mono focus:ring-4 transition-all text-sm font-bold',
                                (form.include_in_package && form.quote_line_id) || form.source === 'customer'
                                    ? 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-70'
                                    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-emerald-500/10 focus:border-emerald-500'
                            ]" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('quotes.show.discount') }}
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.discount" dir="ltr"
                            @input="form.discount = normalizeArabicNumerals($event.target.value)"
                            :readonly="form.include_in_package && form.quote_line_id"
                            :class="[
                                'w-full px-4 py-3 border-2 rounded-2xl text-center font-mono focus:ring-4 transition-all text-sm font-bold',
                                (form.include_in_package && form.quote_line_id)
                                    ? 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-red-500/50 cursor-not-allowed opacity-70'
                                    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-red-500 focus:ring-red-500/10 focus:border-red-500'
                            ]" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('work_orders.item.qty') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.qty" dir="ltr"
                            @input="form.qty = normalizeArabicNumerals($event.target.value)"
                            class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-center font-mono focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all text-sm font-extrabold" />
                    </div>
                </div>

                <!-- Row 5: VAT & Total (Styled like Image) -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest ms-1">
                            VAT ({{ quote.tax_rate_snapshot }}%)
                        </label>
                        <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl text-center font-mono text-blue-700 dark:text-blue-300 font-bold text-sm">
                            {{ formatCurrency(vatAmount) }}
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-600 dark:text-gray-400 uppercase tracking-widest ms-1">
                            {{ $t('quotes.show.amount') }}
                        </label>
                        <div class="px-4 py-3 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-center font-mono text-gray-900 dark:text-white font-extrabold text-sm">
                            {{ formatCurrency(grandTotalValue) }}
                        </div>
                    </div>
                </div>

                <!-- Row 6: Linked Service (Bottom as in Image) -->
                <div v-if="showServiceSelect">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                        {{ $t('quotes.service_modal.service') }}
                    </label>
                    <SearchableSelect v-model="form.quote_line_id" :options="serviceOptions" option-label="label"
                        option-value="value" :placeholder="$t('common.none')" />
                </div>

            </div>

            <!-- Toggles Section (Only shown if a service is selected) -->
            <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="form.quote_line_id" class="flex gap-4 px-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" v-model="form.include_in_package" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider group-hover:text-emerald-500 transition-colors">{{ $t('work_orders.item.include_in_package') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" v-model="form.hide_on_print" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider group-hover:text-emerald-500 transition-colors">{{ $t('work_orders.item.hide_on_print') }}</span>
                    </label>
                </div>
            </transition>
        </form>

        <!-- Footer -->
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button type="button" @click="$emit('close')"
                    class="px-6 py-2.5 text-xs font-bold text-gray-500 hover:text-red-500 transition-all uppercase tracking-widest">
                    {{ $t('common.cancel') }}
                </button>
                <button type="button" @click="submitForm"
                    :disabled="form.processing || !form.name || !form.qty || (form.unit_price === '' || form.unit_price === null) || (form.source !== 'warehouse' && !form.unit_id)"
                    class="px-10 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase tracking-widest text-xs transition-all shadow-lg shadow-emerald-500/20 disabled:opacity-50">
                    {{ form.processing ? $t('common.loading') : $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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
    part: Object,
    quoteLineId: Number,
    quoteLines: Array,
    units: { type: Array, default: () => [] },
    showServiceSelect: { type: Boolean, default: true },
    showToggles: { type: Boolean, default: false },
    pendingMode: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();

// State
const partSearch = ref('');
const searchResults = ref([]);
const selectedPart = ref(null);
const searchWrapper = ref(null);

// Form
const form = useForm({
    id: null,
    quote_id: props.quote?.id,
    quote_line_id: null,
    part_id: null,
    source: 'warehouse',
    name: '',
    part_number: '',
    unit_id: null,
    description: '',
    qty: 1,
    unit_price: 0,
    discount: 0,
    include_in_package: false,
    hide_on_print: false,
});

// Store original price to restore it if user untoggles "Include in package"
const stashedPrice = ref(0);
const stashedDiscount = ref(0);

watch(() => form.include_in_package, (newVal, oldVal) => {
    // Only apply zeroing logic if it was explicitly toggled ON by user
    if (newVal && !oldVal && form.quote_line_id) {
        if (Number(form.unit_price) > 0) stashedPrice.value = form.unit_price;
        if (Number(form.discount) > 0) stashedDiscount.value = form.discount;
        
        form.unit_price = 0;
        form.discount = 0;
    } else if (!newVal && oldVal && form.quote_line_id) {
        // Restore values when untoggling
        if (Number(form.unit_price) === 0 && stashedPrice.value > 0) {
            form.unit_price = stashedPrice.value;
        }
        if (Number(form.discount) === 0 && stashedDiscount.value > 0) {
            form.discount = stashedDiscount.value;
        }
    }
});

// Computed Calculations
const calculatedTotal = computed(() => {
    const price = parseFloat(form.unit_price) || 0;
    const qty = parseFloat(form.qty) || 0;
    const discount = parseFloat(form.discount) || 0;
    return Math.max(0, (price * qty) - discount);
});

const vatAmount = computed(() => {
    if (!props.quote.tax_enabled_snapshot) return 0;
    const isInclusive = props.quote.pricing_mode_snapshot === 'inclusive';
    const rate = props.quote.tax_rate_snapshot || 15;
    const netAmount = calculatedTotal.value;
    
    if (isInclusive) {
        return netAmount - (netAmount / (1 + (rate / 100)));
    } else {
        return netAmount * (rate / 100);
    }
});

const grandTotalValue = computed(() => {
    const netAmount = calculatedTotal.value;
    if (!props.quote.tax_enabled_snapshot || props.quote.pricing_mode_snapshot === 'inclusive') {
        return netAmount;
    }
    return netAmount + vatAmount.value;
});

// Options
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

const availableStock = computed(() => {
    if (!selectedPart.value) return 0;
    return selectedPart.value.inventory_balances_sum_qty_on_hand || 0;
});

// Click outside handler
const handleClickOutside = (event) => {
    if (searchWrapper.value && !searchWrapper.value.contains(event.target)) {
        searchResults.value = [];
    }
};

onMounted(() => window.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => window.removeEventListener('mousedown', handleClickOutside));

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
    const total = calculatedTotal.value;
    if (props.pendingMode) {
        emit('saved', { ...form.data(), total });
        return;
    }
    const url = props.part 
        ? route('app.quotes.parts.update', { quote: props.quote.id, quotePart: props.part.id })
        : route('app.quotes.parts.store', { quote: props.quote.id });
    
    const method = props.part ? 'put' : 'post';
    form[method](url, { onSuccess: () => emit('saved') });
}

// Watchers
watch(() => form.source, (newSource) => {
    if (newSource === 'customer') form.unit_price = 0;
    if (newSource !== 'warehouse') clearPartSelection();
});

watch(() => form.include_in_package, (isIncluded) => {
    if (props.showToggles && isIncluded) {
        form.unit_price = 0;
        form.discount = 0;
    }
});

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        if (props.part) {
            Object.assign(form, {
                ...props.part,
                part_number: props.part.part_number || '',
                description: props.part.description || '',
            });
            if (props.part.part) selectedPart.value = props.part.part;
        } else {
            form.reset();
            form.source = 'external';
            form.quote_line_id = props.quoteLineId || null;
            form.qty = 1;
        }
    }
});
</script>
