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
            <!-- Part Source -->
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

            <!-- Content Container -->
            <div class="space-y-4">

                <!-- Warehouse Search -->
                <div v-if="form.source === 'warehouse' && !selectedPart">
                    <div v-if="warehouses.length > 1" class="mb-3">
                        <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ms-1">
                            {{ $t('inventory.parts.warehouse') }} <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect v-model="form.warehouse_id" :options="warehouseOptions" :placeholder="$t('inventory.parts.choose_warehouse')" class="w-full" />
                    </div>
                    <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 ms-1">
                        {{ $t('inventory.parts.search_label') }}
                    </label>
                    <SearchableSelect v-model="form.part_id" :options="searchResults" :async-search="true" @search="handleAsyncSearch" @change="handlePartSelectChange" :placeholder="$t('inventory.parts.search')" class="w-full">
                        <template #option="{ option }">
                            <div class="flex items-center justify-between w-full">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ toEnglish(getName(option)) }}</div>
                                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5">{{ formatCurrency(option.default_sale_price) }}</div>
                                </div>
                                <div class="text-right flex flex-col items-end">
                                    <span class="text-[10px] font-mono text-gray-500">{{ toEnglish(option.sku || option.barcode || '---') }}</span>
                                    <span class="text-[10px] font-bold mt-0.5" :class="Number(option.inventory_balances_sum_qty_on_hand) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'">
                                        {{ $t('inventory.stock.available') }}: {{ formatQuantity(option.inventory_balances_sum_qty_on_hand || 0) }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </SearchableSelect>
                </div>

                <!-- Selected Part Banner -->
                <div v-if="selectedPart" class="flex items-center justify-between p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-base shrink-0">📦</div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ toEnglish(getName(selectedPart)) }}</p>
                            <p class="text-[10px] font-mono font-bold uppercase" :class="availableStock > 0 ? 'text-emerald-600' : 'text-red-500'">
                                {{ $t('inventory.stock.available') }}: {{ formatQuantity(availableStock) }}
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="clearPartSelection" class="p-1.5 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Section: Part Identity -->
                <div v-if="selectedPart || form.source !== 'warehouse'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('inventory.parts.part_details') || 'تفاصيل القطعة' }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 sm:col-span-1 space-y-1">
                            <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ $t('inventory.parts.name') }} <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.name" required :disabled="isReadOnly"
                                class="w-full px-3 py-2.5 border-2 border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all text-sm font-bold disabled:opacity-60" />
                        </div>
                        <div class="col-span-2 sm:col-span-1 space-y-1">
                            <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ $t('inventory.parts.part_number') }}</label>
                            <input type="text" v-model="form.part_number" dir="ltr" :disabled="isReadOnly"
                                class="w-full px-3 py-2.5 border-2 border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 font-mono transition-all text-sm disabled:opacity-60" />
                        </div>
                    </div>
                </div>

                <!-- Section: Quantity & Pricing -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('quotes.service_modal.pricing') || 'التسعير' }}</p>

                    <!-- 3-col: Qty | Price | Discount -->
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Qty -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ $t('work_orders.item.qty') }} <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="decimal" v-model="form.qty" dir="ltr" :disabled="isReadOnly"
                                @input="form.qty = toEnglish($event.target.value).replace(/[^0-9.]/g, '')"
                                class="w-full px-3 py-2.5 border-2 border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white text-center font-mono focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all text-sm font-extrabold disabled:opacity-60" />
                            <SearchableSelect v-model="form.unit_id" :options="unitOptions" option-label="label" :disabled="isReadOnly" option-value="value" :placeholder="$t('common.unit')" />
                        </div>

                        <!-- Unit Price -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ $t('inventory.parts.unit_price') }} <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="decimal" v-model="form.unit_price" dir="ltr"
                                @input="form.unit_price = toEnglish($event.target.value).replace(/[^0-9.]/g, '')"
                                :readonly="form.source === 'customer' || form.include_in_package || isReadOnly"
                                :class="['w-full px-3 py-2.5 border-2 rounded-xl text-center font-mono focus:ring-4 transition-all text-sm font-bold',
                                    isPriceBelowMinimum ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 focus:ring-red-500/10 focus:border-red-500' : 'border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-emerald-500/10 focus:border-emerald-500',
                                    (form.include_in_package || form.source === 'customer' || isReadOnly) ? 'opacity-60 cursor-not-allowed' : '']" />
                            <p v-if="selectedPartMinPrice > 0" class="text-[9px] font-bold text-center" :class="isPriceBelowMinimum ? 'text-red-500' : 'text-gray-400'">
                                {{ $t('inventory.parts.min_sale_price') }}: {{ formatCurrency(selectedPartMinPrice) }}
                            </p>
                        </div>

                        <!-- Discount -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ $t('quotes.service_modal.discount') }}</label>
                                <div class="flex bg-gray-100 dark:bg-gray-700 p-0.5 rounded-lg">
                                    <button type="button" @click="form.discount_type = 'fixed'" :class="['px-1.5 py-0.5 text-[9px] rounded font-bold transition-all', form.discount_type === 'fixed' ? 'bg-white dark:bg-gray-600 shadow-sm text-emerald-600' : 'text-gray-400']">{{ $t('common.currency') }}</button>
                                    <button type="button" @click="form.discount_type = 'percentage'" :class="['px-1.5 py-0.5 text-[9px] rounded font-bold transition-all', form.discount_type === 'percentage' ? 'bg-white dark:bg-gray-600 shadow-sm text-emerald-600' : 'text-gray-400']">%</button>
                                </div>
                            </div>
                            <input type="text" inputmode="decimal" v-model="form.discount_value" dir="ltr"
                                @input="form.discount_value = toEnglish($event.target.value).replace(/[^0-9.]/g, '')"
                                :readonly="form.include_in_package || isReadOnly"
                                :class="['w-full px-3 py-2.5 border-2 rounded-xl text-center font-mono focus:ring-4 transition-all text-sm font-bold',
                                    isPriceBelowMinimum ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 focus:ring-red-500/10' : 'border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-red-500 focus:ring-red-500/10 focus:border-red-400',
                                    (form.include_in_package || isReadOnly) ? 'opacity-60 cursor-not-allowed' : '']" />
                            <p v-if="selectedPartMinPrice > 0 && form.discount_value > 0" class="text-[9px] font-bold text-center" :class="isPriceBelowMinimum ? 'text-red-500' : 'text-gray-400'">
                                <span v-if="form.discount_type === 'fixed'">{{ $t('quotes.max_discount_fixed', { max: formatCurrency(maxAllowedFixedDiscount) }) }}</span>
                                <span v-else>{{ $t('quotes.max_discount_percentage', { max: maxAllowedPercentageDiscount.toFixed(1) }) }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Live Total Bar -->
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <p class="text-[10px] uppercase tracking-widest font-black text-gray-400">{{ $t('quotes.service_modal.total_cost') }}</p>
                            <div v-if="isPriceBelowMinimum" class="flex items-center gap-1 text-red-500 animate-pulse">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span class="text-[9px] font-black">{{ $t('quotes.min_price_warning', { min: formatCurrency(selectedPartMinPrice) }) }}</span>
                            </div>
                        </div>
                        <span class="text-xl font-black font-mono transition-colors duration-300" :class="isPriceBelowMinimum ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                            {{ formatCurrency(calculatedTotal) }}
                        </span>
                    </div>
                </div>

                <!-- Section: Notes & Service -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 space-y-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em]">{{ $t('inventory.parts.description') }}</p>
                    <textarea v-model="form.notes" rows="2" :disabled="isReadOnly"
                        class="w-full px-3 py-2.5 border-2 border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 resize-none transition-all text-sm disabled:opacity-60"
                        :placeholder="$t('inventory.parts.description_placeholder')"></textarea>
                    <div v-if="showServiceSelect">
                        <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">{{ $t('quotes.service_modal.service') }}</label>
                        <SearchableSelect v-model="form.work_order_item_id" :options="serviceOptions" option-label="label" :disabled="isReadOnly" option-value="value" :placeholder="$t('common.none')" />
                    </div>
                </div>

            </div>

            <!-- Toggles Section -->
            <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="form.work_order_item_id || showToggles" class="flex gap-4 px-2">
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
                <div class="flex items-center gap-3">
                    <button v-if="!isReadOnly && !props.part" type="button" @click="submitForm(false)"
                        :disabled="form.processing || !form.name || !form.qty || (form.unit_price === '' || form.unit_price === null) || (form.source === 'warehouse' && (!form.warehouse_id || !form.part_id)) || isPriceBelowMinimum"
                        class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-2xl font-bold text-xs hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm disabled:opacity-50">
                        {{ form.processing ? $t('common.loading') : $t('common.save_and_add_another') }}
                    </button>
                    <button v-if="!isReadOnly" type="button" @click="submitForm(true)"
                        :disabled="form.processing || !form.name || !form.qty || (form.unit_price === '' || form.unit_price === null) || (form.source === 'warehouse' && (!form.warehouse_id || !form.part_id)) || isPriceBelowMinimum"
                        class="px-10 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase tracking-widest text-xs transition-all shadow-lg shadow-emerald-500/20 disabled:opacity-50">
                        {{ form.processing ? $t('common.loading') : $t('common.save') }}
                    </button>
                </div>
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
import { useToast } from '@/Composables/useToast';
import axios from 'axios';

// Native debounce implementation
function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

const { formatCurrency, formatQuantity, toEnglish } = useNumberFormat();

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    part: Object,
    workOrderItemId: Number,
    workOrderItems: Array,
    units: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
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
    tempId: null, // For pending parts tracking
    work_order_id: props.workOrder?.id,
    work_order_item_id: null,
    part_id: null,
    warehouse_id: null,
    source: 'warehouse',
    name: '',
    part_number: '',
    unit_id: null,
    notes: '',
    qty: 1,
    unit_price: 0,
    discount_type: 'fixed',
    discount_value: 0,
    discount: 0, // Calculated flat amount for backend
    include_in_package: false,
    hide_on_print: false,
});

// Store original price to restore it if user untoggles "Include in package"
const stashedPrice = ref(0);
const stashedDiscount = ref(0);

watch(() => form.include_in_package, (newVal, oldVal) => {
    // Only apply zeroing logic if it was explicitly toggled ON by user
    // Now also checking props.showToggles for pending services
    if (newVal && !oldVal && (form.work_order_item_id || props.showToggles)) {
        if (Number(form.unit_price) > 0) stashedPrice.value = form.unit_price;
        if (Number(form.discount_value) > 0) stashedDiscount.value = form.discount_value;
        
        form.unit_price = 0;
        form.discount_value = 0;
    } else if (!newVal && oldVal && (form.work_order_item_id || props.showToggles)) {
        // Restore values when untoggling
        if (Number(form.unit_price) === 0 && stashedPrice.value > 0) {
            form.unit_price = stashedPrice.value;
        }
        if (Number(form.discount_value) === 0 && stashedDiscount.value > 0) {
            form.discount_value = stashedDiscount.value;
        }
    }
});

// Computed
const isReadOnly = computed(() => {
    return ['approved', 'rejected', 'converted'].includes(props.workOrder.status);
});

// Min price logic
const selectedPartMinPrice = computed(() => {
    if (!selectedPart.value) return 0;
    return parseFloat(selectedPart.value.min_sale_price) || 0;
});

const currentDiscountAmount = computed(() => {
    const price = parseFloat(toEnglish(form.unit_price)) || 0;
    const value = parseFloat(toEnglish(form.discount_value)) || 0;
    if (form.discount_type === 'fixed') return value;
    if (form.discount_type === 'percentage') return (price * value) / 100;
    return 0;
});

const maxAllowedFixedDiscount = computed(() => {
    const minPrice = selectedPartMinPrice.value;
    const price = parseFloat(toEnglish(form.unit_price)) || 0;
    if (minPrice <= 0) return price;
    return Math.max(0, price - minPrice);
});

const maxAllowedPercentageDiscount = computed(() => {
    const minPrice = selectedPartMinPrice.value;
    const price = parseFloat(toEnglish(form.unit_price)) || 0;
    if (minPrice <= 0 || price <= 0) return 100;
    return Math.max(0, ((price - minPrice) / price) * 100);
});

const isPriceBelowMinimum = computed(() => {
    if (!selectedPart.value) return false;
    const minPrice = selectedPartMinPrice.value;
    if (minPrice <= 0) return false;
    
    const finalUnitPrice = (parseFloat(toEnglish(form.unit_price)) || 0) - currentDiscountAmount.value;
    return finalUnitPrice < minPrice;
});

const calculatedTotal = computed(() => {
    const price = parseFloat(toEnglish(form.unit_price)) || 0;
    const qty = parseFloat(toEnglish(form.qty)) || 0;
    const discountAmount = currentDiscountAmount.value;
    return Math.max(0, (price - discountAmount) * qty);
});

const vatAmount = computed(() => {
    if (!props.workOrder.tax_enabled_snapshot) return 0;
    const isInclusive = props.workOrder.pricing_mode_snapshot === 'inclusive';
    const rate = props.workOrder.tax_rate_snapshot || 15;
    const netAmount = calculatedTotal.value;
    
    if (isInclusive) {
        return netAmount - (netAmount / (1 + (rate / 100)));
    } else {
        return netAmount * (rate / 100);
    }
});

const grandTotalValue = computed(() => {
    const netAmount = calculatedTotal.value;
    if (!props.workOrder.tax_enabled_snapshot || props.workOrder.pricing_mode_snapshot === 'inclusive') {
        return netAmount;
    }
    return netAmount + vatAmount.value;
});

// Options
const serviceOptions = computed(() => {
    return (props.workOrderItems || []).map(line => ({
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

const warehouseOptions = computed(() => {
    return (props.warehouses || []).map(w => ({
        value: w.id,
        label: toEnglish(w.name)
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
            params: { 
                q: query || '',
                hide_out_of_stock: true
            }
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
    form.discount_type = 'fixed';
    form.discount_value = 0;
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
    form.discount_type = 'fixed';
    form.discount_value = 0;
    if (form.source === 'warehouse') handleAsyncSearch(''); // Pre-fetch
}

function submitForm(shouldClose = true) {
    if (isReadOnly.value) return;

    // Flatten discount for backend
    form.discount = currentDiscountAmount.value * (parseFloat(toEnglish(form.qty)) || 0);

    // Validation: Cannot add more than available in warehouse
    if (form.source === 'warehouse' && selectedPart.value) {
        const available = Number(selectedPart.value.inventory_balances_sum_qty_on_hand) || 0;
        const requested = Number(form.qty);
        if (requested > available) {
            useToast().error(t('inventory.stock.insufficient_warning_with_qty', { 
                requested: formatQuantity(requested), 
                available: formatQuantity(available) 
            }));
            return;
        }
    }

    // Normalize data before processing
    form.qty = toEnglish(form.qty);
    form.unit_price = toEnglish(form.unit_price);
    form.discount = toEnglish(form.discount);

    const total = calculatedTotal.value;
    const tax_amount = vatAmount.value;
    const grand_total = grandTotalValue.value;

    if (props.pendingMode) {
        emit('saved', { 
            ...form.data(), 
            total, 
            tax_amount,
            grand_total,
            isPending: true,
            tempId: form.tempId || Date.now()
        }, { close: shouldClose });
        if (shouldClose) return;
        
        // If not closing, reset for next part
        const currentWarehouse = form.warehouse_id;
        const currentItem = form.work_order_item_id;
        form.reset();
        form.warehouse_id = currentWarehouse;
        form.work_order_item_id = currentItem;
        selectedPart.value = null;
        partSearch.value = '';
        return;
    }

    let url;
    let method = props.part ? 'put' : 'post';

    url = props.part
        ? route('work-orders.parts.update', { workOrderPart: props.part.id })
        : route('work-orders.parts.store', { workOrder: props.workOrder.id });
    
    form[method](url, { 
        preserveScroll: true,
        onSuccess: (page) => {
            // Get the saved part from the response or form data
            const savedData = { ...form.data(), total };
            emit('saved', savedData, { close: shouldClose });
            
            if (!shouldClose) {
                // Keep warehouse and item selection for next part
                const currentWarehouse = form.warehouse_id;
                const currentItem = form.work_order_item_id;
                form.reset();
                form.warehouse_id = currentWarehouse;
                form.work_order_item_id = currentItem;
                selectedPart.value = null;
                partSearch.value = '';
            }
        } 
    });
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
            form.id = part.id;
            form.tempId = part.tempId;
            form.source = part.source || 'warehouse';
            form.work_order_item_id = part.work_order_item_id;
            form.part_id = part.part_id;
            form.warehouse_id = part.warehouse_id;
            form.name = part.name || '';
            form.part_number = part.part_number || '';
            form.unit_id = part.unit_id;
            form.notes = part.notes || '';
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
            form.work_order_item_id = props.workOrderItemId || null;
            form.warehouse_id = props.warehouses?.length > 0 ? props.warehouses[0].id : null;
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
