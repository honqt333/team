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
            <div v-if="!part" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button type="button" 
                    :disabled="isReadOnly"
                    @click="form.source = 'warehouse'" :class="[
                    'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all font-bold text-xs',
                    form.source === 'warehouse' ? 'border-emerald-500 bg-emerald-50/30 text-emerald-700 dark:text-emerald-400' : 'border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800',
                    isReadOnly ? 'opacity-50 cursor-not-allowed' : ''
                ]">
                    🏭 {{ $t('quotes.parts.warehouse') }}
                </button>
                <button type="button" 
                    :disabled="isReadOnly"
                    @click="form.source = 'external'" :class="[
                    'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all font-bold text-xs',
                    form.source === 'external' ? 'border-blue-500 bg-blue-50/30 text-blue-700 dark:text-blue-400' : 'border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800',
                    isReadOnly ? 'opacity-50 cursor-not-allowed' : ''
                ]">
                    🛒 {{ $t('quotes.parts.external') }}
                </button>
                <button type="button" 
                    :disabled="isReadOnly"
                    @click="form.source = 'customer'" :class="[
                    'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all font-bold text-xs',
                    form.source === 'customer' ? 'border-amber-500 bg-amber-50/30 text-amber-700 dark:text-amber-400' : 'border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800',
                    isReadOnly ? 'opacity-50 cursor-not-allowed' : ''
                ]">
                    👤 {{ $t('quotes.parts.customer') }}
                </button>
            </div>

            <!-- Content Container (Linear Vertical Stack) -->
            <div class="bg-gray-50 dark:bg-gray-900/40 rounded-3xl p-6 space-y-5 border border-gray-100 dark:border-gray-800">
                
                <!-- Warehouse Search -->
                <div v-if="form.source === 'warehouse' && !selectedPart" class="relative">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 ms-1">
                        {{ $t('inventory.parts.search_label') }}
                    </label>
                    <SearchableSelect
                        v-model="form.part_id"
                        :options="searchResults"
                        :async-search="true"
                        @search="handleAsyncSearch"
                        @change="handlePartSelectChange"
                        :placeholder="$t('inventory.parts.search')"
                        class="w-full"
                    >
                        <template #option="{ option }">
                            <div class="flex items-center justify-between w-full">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ toEnglish(getName(option)) }}</div>
                                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5">{{ formatCurrency(option.default_sale_price) }}</div>
                                </div>
                                <span class="text-[10px] font-mono text-gray-500">{{ toEnglish(option.sku || option.barcode || '---') }}</span>
                            </div>
                        </template>
                    </SearchableSelect>
                </div>

                <!-- Selected Part Banner (Small) -->
                <div v-if="selectedPart" class="flex items-center justify-between p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs">📦</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ toEnglish(getName(selectedPart)) }}</p>
                            <p class="text-[10px] text-emerald-600 font-bold uppercase">{{ $t('inventory.stock.available') }}: {{ toEnglish(availableStock) }}</p>
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
                            :disabled="isReadOnly"
                            class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 font-mono transition-all text-sm disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:text-gray-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('inventory.parts.unit') }}
                        </label>
                        <SearchableSelect v-model="form.unit_id" :options="unitOptions" option-label="label"
                            :disabled="isReadOnly"
                            option-value="value" :placeholder="$t('common.choose')" />
                    </div>
                </div>

                <!-- Row 2: Name -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                        {{ $t('inventory.parts.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name" required
                        :disabled="isReadOnly"
                        class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all text-sm font-bold disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:text-gray-500" />
                </div>

                <!-- Row 3: Description -->
                <div>
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                        {{ $t('inventory.parts.description') }}
                    </label>
                    <textarea v-model="form.description" rows="2"
                        :disabled="isReadOnly"
                        class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 resize-none transition-all text-sm disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:text-gray-500"
                        :placeholder="$t('inventory.parts.description_placeholder')"></textarea>
                </div>

                <!-- Row 4: Pricing Grid [Price | Discount | Qty] -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('inventory.parts.unit_price') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.unit_price" dir="ltr"
                            @input="form.unit_price = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                            :readonly="form.source === 'customer' || form.include_in_package || isReadOnly"
                            :class="[
                                'w-full px-4 py-3 border-2 rounded-2xl text-center font-mono focus:ring-4 transition-all text-sm font-bold',
                                form.include_in_package || form.source === 'customer' || isReadOnly
                                    ? 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed opacity-70'
                                    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-emerald-500/10 focus:border-emerald-500'
                            ]" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('quotes.show.discount') }}
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.discount" dir="ltr"
                            @input="form.discount = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                            :readonly="form.include_in_package || isReadOnly"
                            :class="[
                                'w-full px-4 py-3 border-2 rounded-2xl text-center font-mono focus:ring-4 transition-all text-sm font-bold',
                                form.include_in_package || isReadOnly
                                    ? 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-red-500/50 cursor-not-allowed opacity-70'
                                    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-red-500 focus:ring-red-500/10 focus:border-red-500'
                            ]" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 ms-1">
                            {{ $t('work_orders.item.qty') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" inputmode="decimal" v-model="form.qty" dir="ltr"
                            :disabled="isReadOnly"
                            @input="form.qty = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                            class="w-full px-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-center font-mono focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all text-sm font-extrabold disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:text-gray-500" />
                    </div>
                </div>

                <!-- Row 5: VAT & Total (Styled like Image) -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest ms-1">
                            VAT ({{ toEnglish(quote.tax_rate_snapshot) }}%)
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
                        :disabled="isReadOnly"
                        option-value="value" :placeholder="$t('common.none')" />
                </div>

            </div>

            <!-- Toggles Section (Shown if line is selected OR showToggles prop is true) -->
            <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="form.quote_line_id || showToggles" class="flex gap-4 px-2">
                    <label class="flex items-center gap-2" :class="isReadOnly ? 'cursor-not-allowed' : 'cursor-pointer group'">
                        <input type="checkbox" v-model="form.include_in_package" 
                            :disabled="isReadOnly"
                            class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300 dark:border-gray-700 dark:bg-gray-800 disabled:opacity-50">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider transition-colors" :class="!isReadOnly ? 'group-hover:text-emerald-500' : ''">{{ $t('work_orders.item.include_in_package') }}</span>
                    </label>
                    <label class="flex items-center gap-2" :class="isReadOnly ? 'cursor-not-allowed' : 'cursor-pointer group'">
                        <input type="checkbox" v-model="form.hide_on_print" 
                            :disabled="isReadOnly"
                            class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300 dark:border-gray-700 dark:bg-gray-800 disabled:opacity-50">
                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider transition-colors" :class="!isReadOnly ? 'group-hover:text-emerald-500' : ''">{{ $t('work_orders.item.hide_on_print') }}</span>
                    </label>
                </div>
            </transition>
        </form>

        <!-- Footer -->
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button type="button" @click="$emit('close')"
                    class="px-6 py-2.5 text-xs font-bold text-gray-500 hover:text-red-500 transition-all uppercase tracking-widest">
                    {{ isReadOnly ? $t('common.close') : $t('common.cancel') }}
                </button>
                <button v-if="!isReadOnly" type="button" @click="submitForm"
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

const { formatCurrency, toEnglish } = useNumberFormat();

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

// State
const partSearch = ref('');
const searchResults = ref([]);
const selectedPart = ref(null);
const searchWrapper = ref(null);
const isPopulating = ref(false);

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
    // Now also checking props.showToggles for pending services
    if (newVal && !oldVal && (form.quote_line_id || props.showToggles)) {
        if (Number(form.unit_price) > 0) stashedPrice.value = form.unit_price;
        if (Number(form.discount) > 0) stashedDiscount.value = form.discount;
        
        form.unit_price = 0;
        form.discount = 0;
    } else if (!newVal && oldVal && (form.quote_line_id || props.showToggles)) {
        // Restore values when untoggling
        if (Number(form.unit_price) === 0 && stashedPrice.value > 0) {
            form.unit_price = stashedPrice.value;
        }
        if (Number(form.discount) === 0 && stashedDiscount.value > 0) {
            form.discount = stashedDiscount.value;
        }
    }
});

// Computed
const isReadOnly = computed(() => {
    return ['approved', 'rejected', 'converted'].includes(props.quote.status);
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
        label: toEnglish(line.description || getName(line.service))
    }));
});

const unitOptions = computed(() => {
    return (props.units || []).map(unit => ({
        value: unit.id,
        label: toEnglish(getName(unit))
    }));
});

const availableStock = computed(() => {
    if (!selectedPart.value) return 0;
    return selectedPart.value.inventory_balances_sum_qty_on_hand || 0;
});

// Methods
const handleAsyncSearch = debounce(async (query) => {
    try {
        const response = await axios.get(route('app.inventory.parts.search'), {
            params: { q: query || '' }
        });
        searchResults.value = response.data;
    } catch (e) {
        searchResults.value = [];
    }
}, 300);

const handlePartSelectChange = (partId) => {
    if (!partId) return;
    const part = searchResults.value.find(p => p.id === partId);
    if (part) selectPart(part);
};

// Methods
function selectPart(part) {
    selectedPart.value = part;
    form.part_id = part.id;
    form.name = toEnglish(getName(part));
    form.part_number = toEnglish(part.sku || part.barcode || '');
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
    if (form.source === 'warehouse') handleAsyncSearch(''); // Refresh default list
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
    if (isPopulating.value) return; // Skip if we are just loading data
    if (newSource === 'customer') form.unit_price = 0;
    if (newSource !== 'warehouse') clearPartSelection();
    if (newSource === 'warehouse' && !selectedPart.value) handleAsyncSearch(''); // Pre-fetch
});

watch(() => form.include_in_package, (isIncluded) => {
    if (isPopulating.value) return; // Skip if we are just loading data
    if (props.showToggles && isIncluded) {
        form.unit_price = 0;
        form.discount = 0;
    }
});

// Enhanced Watcher for robust data population
watch([() => props.show, () => props.part], ([isOpen, part]) => {
    if (isOpen) {
        isPopulating.value = true;
        if (part) {
            // Populate form explicitly for better reactivity
            form.source = part.source || 'warehouse';
            form.quote_line_id = part.quote_line_id;
            form.part_id = part.part_id;
            form.name = part.name || '';
            form.part_number = part.part_number || '';
            form.unit_id = part.unit_id;
            form.description = part.description || '';
            form.qty = part.qty || 1;
            form.unit_price = part.unit_price || 0;
            form.discount = part.discount || 0;
            form.include_in_package = !!part.include_in_package;
            form.hide_on_print = !!part.hide_on_print;

            if (part.part) {
                selectedPart.value = part.part;
            } else {
                selectedPart.value = null;
            }
        } else {
            // Reset for new part
            form.reset();
            form.id = null;
            form.source = 'warehouse';
            form.quote_line_id = props.quoteLineId || null;
            form.qty = 1;
            form.unit_price = 0;
            form.discount = 0;
            form.name = '';
            form.part_id = null;
            selectedPart.value = null;
            partSearch.value = '';
            if (form.source === 'warehouse') handleAsyncSearch('');
        }
        
        // Use timeout to ensure reactivity settles before re-enabling watchers
        setTimeout(() => {
            isPopulating.value = false;
        }, 100);
    }
}, { immediate: true });
</script>
