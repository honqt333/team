<template>
    <BaseModal
        :show="show"
        @close="$emit('close')"
        size="lg"
        :overflow-visible="true"
        scroll-entire
    >
        <!-- Modern Clean Header -->
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm shrink-0"
                >
                    <svg
                        class="w-5 h-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ part ? $t('inventory.parts.edit') : $t('quotes.show.add_part') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">
                        {{ $t(`quotes.parts.${form.source}`) }}
                    </p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Part Source Segmented Control -->
            <div
                v-if="!part"
                class="grid grid-cols-3 gap-2 p-1 bg-gray-100/90 dark:bg-gray-800/90 rounded-xl"
            >
                <button
                    type="button"
                    :disabled="isReadOnly"
                    @click="form.source = 'warehouse'"
                    :class="[
                        'flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg font-bold text-xs transition-all',
                        form.source === 'warehouse'
                            ? 'bg-white dark:bg-gray-700 text-emerald-700 dark:text-emerald-400 shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                        isReadOnly ? 'opacity-50 cursor-not-allowed' : '',
                    ]"
                >
                    <span>🏭</span>
                    <span>{{ $t('quotes.parts.warehouse') }}</span>
                </button>
                <button
                    type="button"
                    :disabled="isReadOnly"
                    @click="form.source = 'external'"
                    :class="[
                        'flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg font-bold text-xs transition-all',
                        form.source === 'external'
                            ? 'bg-white dark:bg-gray-700 text-blue-700 dark:text-blue-400 shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                        isReadOnly ? 'opacity-50 cursor-not-allowed' : '',
                    ]"
                >
                    <span>🛒</span>
                    <span>{{ $t('quotes.parts.external') }}</span>
                </button>
                <button
                    type="button"
                    :disabled="isReadOnly"
                    @click="form.source = 'customer'"
                    :class="[
                        'flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg font-bold text-xs transition-all',
                        form.source === 'customer'
                            ? 'bg-white dark:bg-gray-700 text-amber-700 dark:text-amber-400 shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                        isReadOnly ? 'opacity-50 cursor-not-allowed' : '',
                    ]"
                >
                    <span>👤</span>
                    <span>{{ $t('quotes.parts.customer') }}</span>
                </button>
            </div>

            <!-- Content Container -->
            <div class="space-y-4">
                <!-- Warehouse Search -->
                <div v-if="form.source === 'warehouse' && !selectedPart" data-field="part_id">
                    <label
                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                    >
                        {{ $t('inventory.parts.search_label') }}
                    </label>
                    <InventoryPartPicker
                        v-model="form.part_id"
                        show-currency
                        @select="selectPart"
                    />
                </div>

                <!-- Selected Part Card -->
                <div
                    v-if="selectedPart"
                    class="flex items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-xl"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-sm shrink-0"
                        >
                            📦
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-900 dark:text-white">
                                {{ toEnglish(getName(selectedPart)) }}
                            </p>
                            <p
                                class="text-[10px] font-mono font-bold"
                                :class="
                                    availableStock > 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-500'
                                "
                            >
                                {{ $t('inventory.stock.available') }}:
                                {{ formatQuantity(availableStock) }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="clearPartSelection"
                        class="p-1 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Duplicate Warning Notice -->
                <div
                    v-if="isDuplicatePart"
                    class="flex items-center gap-2.5 p-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 text-amber-800 dark:text-amber-300 text-xs font-medium"
                >
                    <svg
                        class="w-4 h-4 flex-shrink-0 text-amber-600 dark:text-amber-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <span>{{ $t('quotes.parts.duplicate_notice') }}</span>
                </div>

                <!-- Part Identity Fields (when part selected or external/customer) -->
                <div v-if="selectedPart || form.source !== 'warehouse'" class="space-y-3">
                    <!-- Row 1: Part Number & Unit -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div data-field="part_number">
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('inventory.parts.part_number') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.part_number"
                                name="part_number"
                                data-field="part_number"
                                dir="ltr"
                                :disabled="isReadOnly || form.source === 'warehouse'"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-xs focus:ring-2 focus:ring-emerald-500 disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:opacity-60"
                            />
                        </div>
                        <div data-field="unit_id">
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('inventory.parts.unit') }}
                            </label>
                            <SearchableSelect
                                v-model="form.unit_id"
                                name="unit_id"
                                data-field="unit_id"
                                :options="unitOptions"
                                option-label="label"
                                :disabled="isReadOnly || form.source === 'warehouse'"
                                option-value="value"
                                :placeholder="$t('common.unit')"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <!-- Row 2: Part Name -->
                    <div data-field="name">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('inventory.parts.name') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            v-model="form.name"
                            name="name"
                            data-field="name"
                            required
                            :disabled="isReadOnly || form.source === 'warehouse'"
                            class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-emerald-500 disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:opacity-60"
                        />
                    </div>

                    <!-- Row 3: Service Selector (if enabled) & Notes -->
                    <div
                        :class="[
                            'grid gap-3',
                            showServiceSelect ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1',
                        ]"
                    >
                        <div v-if="showServiceSelect" data-field="quote_line_id">
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('quotes.service_modal.service') }}
                            </label>
                            <SearchableSelect
                                v-model="form.quote_line_id"
                                name="quote_line_id"
                                data-field="quote_line_id"
                                :options="serviceOptions"
                                option-label="label"
                                :disabled="isReadOnly"
                                option-value="value"
                                :placeholder="$t('common.none')"
                                class="w-full text-xs"
                            />
                        </div>
                        <div data-field="description">
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('inventory.parts.description') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.description"
                                name="description"
                                data-field="description"
                                :disabled="isReadOnly || form.source === 'warehouse'"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-emerald-500 disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:opacity-60"
                                :placeholder="$t('inventory.parts.description_placeholder')"
                            />
                        </div>
                    </div>
                </div>

                <!-- 4-Column Financial Grid (Like Wrshah) -->
                <div
                    class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50/80 dark:bg-gray-900/40 p-3 rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <!-- 1. Price -->
                    <div data-field="unit_price">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('work_orders.item.part_price') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                inputmode="decimal"
                                v-model="form.unit_price"
                                name="unit_price"
                                data-field="unit_price"
                                dir="ltr"
                                @input="
                                    form.unit_price = toEnglish($event.target.value).replace(
                                        /[^0-9.]/g,
                                        ''
                                    )
                                "
                                :readonly="!isPriceEditable"
                                :class="[
                                    'w-full py-2 pl-2 pr-9 border rounded-xl font-mono text-center text-xs font-bold focus:ring-2',
                                    isPriceBelowMinimum
                                        ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 focus:ring-red-500'
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-emerald-500',
                                    !isPriceEditable
                                        ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-900'
                                        : '',
                                ]"
                            />
                            <span
                                class="absolute inset-y-0 right-2 flex items-center text-[10px] text-gray-400 font-bold pointer-events-none"
                            >
                                {{ $t('common.currency') }}
                            </span>
                        </div>
                        <p
                            v-if="isPriceBelowMinimum"
                            class="text-[10px] text-red-500 font-bold mt-1"
                        >
                            {{
                                $t('quotes.min_price_warning', {
                                    min: formatCurrency(selectedPartMinPrice),
                                })
                            }}
                        </p>
                    </div>

                    <!-- 2. Discount -->
                    <div data-field="discount_value">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $t('quotes.service_modal.discount') }}
                            </label>
                            <div class="flex bg-gray-200 dark:bg-gray-700 p-0.5 rounded-md">
                                <button
                                    type="button"
                                    @click="form.discount_type = 'fixed'"
                                    :class="[
                                        'px-1.5 py-0.5 text-[9px] font-bold rounded transition-all',
                                        form.discount_type === 'fixed'
                                            ? 'bg-white dark:bg-gray-600 shadow-sm text-emerald-600'
                                            : 'text-gray-400',
                                    ]"
                                >
                                    {{ $t('common.currency') }}
                                </button>
                                <button
                                    type="button"
                                    @click="form.discount_type = 'percentage'"
                                    :class="[
                                        'px-1.5 py-0.5 text-[9px] font-bold rounded transition-all',
                                        form.discount_type === 'percentage'
                                            ? 'bg-white dark:bg-gray-600 shadow-sm text-emerald-600'
                                            : 'text-gray-400',
                                    ]"
                                >
                                    %
                                </button>
                            </div>
                        </div>
                        <input
                            type="text"
                            inputmode="decimal"
                            v-model="form.discount_value"
                            name="discount_value"
                            data-field="discount_value"
                            dir="ltr"
                            @input="
                                form.discount_value = toEnglish($event.target.value).replace(
                                    /[^0-9.]/g,
                                    ''
                                )
                            "
                            :readonly="form.include_in_package || isReadOnly"
                            :class="[
                                'w-full py-2 px-2 border rounded-xl font-mono text-center text-xs font-bold focus:ring-2',
                                isPriceBelowMinimum
                                    ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 focus:ring-red-500'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-emerald-500',
                                form.include_in_package || isReadOnly
                                    ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-900'
                                    : '',
                            ]"
                        />
                    </div>

                    <!-- 3. Quantity -->
                    <div data-field="qty">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('work_orders.item.qty') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            inputmode="decimal"
                            v-model="form.qty"
                            name="qty"
                            data-field="qty"
                            dir="ltr"
                            :disabled="isReadOnly"
                            @input="
                                form.qty = toEnglish($event.target.value).replace(/[^0-9.]/g, '')
                            "
                            class="w-full py-2 px-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-center text-xs font-bold focus:ring-2 focus:ring-emerald-500 disabled:opacity-60 disabled:bg-gray-100"
                        />
                    </div>

                    <!-- 4. Subtotal / Amount -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('work_orders.item.amount') }}
                        </label>
                        <input
                            type="text"
                            readonly
                            :value="formatCurrency(calculatedTotal)"
                            dir="ltr"
                            class="w-full py-2 px-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 font-mono text-center text-xs font-bold cursor-not-allowed"
                        />
                    </div>
                </div>

                <!-- Options & Totals Bar (Bottom Summary) -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-gray-50/80 dark:bg-gray-900/40 rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <!-- Toggles on left -->
                    <div
                        class="flex flex-wrap items-center gap-4 text-xs font-semibold text-gray-700 dark:text-gray-300"
                    >
                        <label
                            v-if="form.quote_line_id || showToggles"
                            class="flex items-center gap-2 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                v-model="form.include_in_package"
                                class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 border-gray-300 dark:border-gray-600"
                            />
                            <span>{{ $t('work_orders.item.include_in_package') }}</span>
                        </label>
                        <label
                            v-if="form.include_in_package"
                            class="flex items-center gap-2 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                v-model="form.hide_on_print"
                                class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 border-gray-300 dark:border-gray-600"
                            />
                            <span>{{ $t('work_orders.item.hide_on_print') }}</span>
                        </label>
                    </div>

                    <!-- Totals on right -->
                    <div class="flex items-center gap-4 self-end sm:self-auto font-mono">
                        <!-- VAT if enabled -->
                        <div v-if="props.quote.tax_enabled_snapshot" class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 block uppercase">
                                {{
                                    $t('common.vat_with_rate', {
                                        rate: props.quote.tax_rate_snapshot || 15,
                                    })
                                }}
                            </span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{ formatCurrency(vatAmount) }} {{ $t('common.currency') }}
                            </span>
                        </div>

                        <!-- Grand Total Badge -->
                        <div
                            class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl"
                        >
                            <span
                                class="text-xs font-bold text-emerald-800 dark:text-emerald-300 font-sans"
                            >
                                {{ $t('quotes.show.total') }}:
                            </span>
                            <span
                                class="text-base font-black text-emerald-600 dark:text-emerald-400"
                            >
                                {{ formatCurrency(grandTotalValue) }}
                            </span>
                            <span class="text-[10px] font-bold text-emerald-700 uppercase">
                                {{ $t('common.currency') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-6 py-2.5 text-xs font-bold text-gray-500 hover:text-red-500 transition-all uppercase tracking-widest"
                    >
                        {{ isReadOnly ? $t('common.close') : $t('common.cancel') }}
                    </button>
                    <span
                        v-if="Object.keys(form.errors).length > 0"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300 border border-red-200 dark:border-red-900"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-red-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{
                            $t('common.fix_errors_count', {
                                count: Object.keys(form.errors).length,
                            })
                        }}
                    </span>
                </div>
                <button
                    v-if="!isReadOnly"
                    type="button"
                    @click="submitForm"
                    :disabled="
                        form.processing ||
                        !form.name ||
                        !form.qty ||
                        form.unit_price === '' ||
                        form.unit_price === null ||
                        (form.source !== 'warehouse' && !form.unit_id) ||
                        isPriceBelowMinimum
                    "
                    class="px-10 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase tracking-widest text-xs transition-all shadow-lg shadow-emerald-500/20 disabled:opacity-50 flex items-center gap-2"
                >
                    <svg
                        v-if="form.processing"
                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        ></path>
                    </svg>
                    {{
                        form.processing
                            ? $t('common.loading')
                            : part
                              ? $t('common.save')
                              : $t('common.add')
                    }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import InventoryPartPicker from '@/Components/Inventory/InventoryPartPicker.vue';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const { formatCurrency, formatQuantity, toEnglish } = useNumberFormat();
const { scrollToError } = useFormScrollToError();

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
const selectedPart = ref(null);
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
    discount_type: 'fixed',
    discount_value: 0,
    discount: 0,
    include_in_package: false,
    hide_on_print: false,
});

// Store original price to restore it if user untoggles "Include in package"
const stashedPrice = ref(0);
const stashedDiscount = ref(0);

watch(
    () => form.include_in_package,
    (newVal, oldVal) => {
        if (!newVal) {
            form.hide_on_print = false;
        }
        if (form.source === 'customer') {
            form.unit_price = 0;
            form.discount_value = 0;
            stashedPrice.value = 0;
            stashedDiscount.value = 0;
            return;
        }
        // Only apply zeroing logic if it was explicitly toggled ON by user
        // Now also checking props.showToggles for pending services
        if (newVal && !oldVal && (form.quote_line_id || props.showToggles)) {
            if (Number(form.unit_price) > 0) stashedPrice.value = form.unit_price;
            if (Number(form.discount_value) > 0) stashedDiscount.value = form.discount_value;

            form.unit_price = 0;
            form.discount_value = 0;
        } else if (!newVal && oldVal && (form.quote_line_id || props.showToggles)) {
            // Restore values when untoggling
            if (Number(form.unit_price) === 0 && stashedPrice.value > 0) {
                form.unit_price = stashedPrice.value;
            }
            if (Number(form.discount_value) === 0 && stashedDiscount.value > 0) {
                form.discount_value = stashedDiscount.value;
            }
        }
    }
);

// Computed
const isReadOnly = computed(() => {
    return ['approved', 'rejected', 'converted'].includes(props.quote.status);
});

const existingParts = computed(() => {
    const list = [...(props.quote?.parts || [])];
    if (props.quote?.lines) {
        props.quote.lines.forEach((l) => {
            if (l.parts && Array.isArray(l.parts)) {
                list.push(...l.parts);
            }
        });
    }
    return list;
});

const isDuplicatePart = computed(() => {
    if (props.part) return false;
    if (form.source === 'warehouse' && form.part_id) {
        return existingParts.value.some((p) => p.part_id && p.part_id == form.part_id);
    }
    if (form.part_number && form.part_number.trim()) {
        const pn = form.part_number.trim().toLowerCase();
        return existingParts.value.some(
            (p) => p.part_number && p.part_number.trim().toLowerCase() === pn
        );
    }
    return false;
});

const activeBalance = computed(() => {
    if (!selectedPart.value) return null;
    const balances = selectedPart.value.inventory_balances || [];
    return (
        balances.find(
            (b) =>
                b.warehouse &&
                b.warehouse.center_id === props.quote?.center_id &&
                b.warehouse.is_default
        ) ||
        balances.find((b) => b.warehouse && b.warehouse.center_id === props.quote?.center_id) ||
        balances[0] ||
        null
    );
});

const isPriceEditable = computed(() => {
    if (isReadOnly.value) return false;
    if (form.include_in_package) return false;
    if (form.source === 'customer') return false;
    if (form.source === 'external') return true;
    if (form.source === 'warehouse') {
        return activeBalance.value ? !!activeBalance.value.allow_price_change : false;
    }
    return true;
});

// Min price logic
const selectedPartMinPrice = computed(() => {
    if (form.source !== 'warehouse' || !selectedPart.value) return 0;
    if (activeBalance.value && parseFloat(activeBalance.value.min_sale_price) > 0) {
        return parseFloat(activeBalance.value.min_sale_price);
    }
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
    if (form.include_in_package) return false;
    if (!selectedPart.value) return false;
    const minPrice = selectedPartMinPrice.value;
    if (minPrice <= 0) return false;

    const finalUnitPrice =
        (parseFloat(toEnglish(form.unit_price)) || 0) - currentDiscountAmount.value;
    return finalUnitPrice < minPrice;
});

const calculatedTotal = computed(() => {
    const price = parseFloat(toEnglish(form.unit_price)) || 0;
    const qty = parseFloat(toEnglish(form.qty)) || 0;
    const discountAmount = currentDiscountAmount.value;
    return Math.max(0, (price - discountAmount) * qty);
});

const vatAmount = computed(() => {
    if (!props.quote.tax_enabled_snapshot) return 0;
    const isInclusive = props.quote.pricing_mode_snapshot === 'inclusive';
    const rate = props.quote.tax_rate_snapshot || 15;
    const netAmount = calculatedTotal.value;

    if (isInclusive) {
        return netAmount - netAmount / (1 + rate / 100);
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
    return (props.quoteLines || []).map((line) => ({
        value: line.id,
        label: toEnglish(getName(line.service) || line.description || ''),
    }));
});

const unitOptions = computed(() => {
    return (props.units || []).map((unit) => ({
        value: unit.id,
        label: toEnglish(getName(unit)),
    }));
});

const availableStock = computed(() => {
    if (!selectedPart.value) return 0;
    return selectedPart.value.inventory_balances_sum_qty_on_hand || 0;
});

// Methods
function selectPart(part) {
    selectedPart.value = part;
    form.part_id = part.id;
    form.name = toEnglish(getName(part));
    form.part_number = toEnglish(part.sku || part.barcode || '');

    // Check if there is an active warehouse balance
    if (activeBalance.value && parseFloat(activeBalance.value.sale_price) > 0) {
        form.unit_price = parseFloat(activeBalance.value.sale_price);
    } else {
        form.unit_price = parseFloat(part.default_sale_price) || 0;
    }

    form.unit_id = part.unit_id;
    form.description = part.description || '';
    form.discount_type = 'fixed';
    form.discount_value = 0;
}

function clearPartSelection() {
    selectedPart.value = null;
    form.part_id = null;
    form.name = '';
    form.part_number = '';
    form.unit_price = 0;
    form.unit_id = null;
    form.description = '';
    form.discount_type = 'fixed';
    form.discount_value = 0;
}

function submitForm() {
    if (isReadOnly.value) return;

    // Flatten discount for backend
    form.discount = currentDiscountAmount.value * (parseFloat(toEnglish(form.qty)) || 0);

    // Validation: Cannot add more than available in warehouse
    if (form.source === 'warehouse' && selectedPart.value) {
        const available = Number(selectedPart.value.inventory_balances_sum_qty_on_hand) || 0;
        const requested = Number(form.qty);
        if (requested > available) {
            useToast().error(
                t('inventory.stock.insufficient_warning_with_qty', {
                    requested: formatQuantity(requested),
                    available: formatQuantity(available),
                })
            );
            return;
        }
    }

    if (isPriceBelowMinimum.value) {
        useToast().error(
            t('quotes.min_price_warning', { min: formatCurrency(selectedPartMinPrice.value) })
        );
        return;
    }

    const total = calculatedTotal.value;
    if (props.pendingMode) {
        emit('saved', { ...form.data(), total });
        return;
    }
    const url = props.part
        ? route('app.quotes.parts.update', { quote: props.quote.id, quotePart: props.part.id })
        : route('app.quotes.parts.store', { quote: props.quote.id });

    const method = props.part ? 'put' : 'post';
    form[method](url, {
        onSuccess: () => emit('saved'),
        onError: (errors) => {
            scrollToError(errors);
        },
    });
}

// Watchers
watch(
    () => form.source,
    (newSource) => {
        if (isPopulating.value) return; // Skip if we are just loading data

        // Clear stashes since source changed
        stashedPrice.value = 0;
        stashedDiscount.value = 0;

        if (newSource === 'customer') {
            form.unit_price = 0;
            form.discount_value = 0;
        }
        if (newSource !== 'warehouse') clearPartSelection();
    }
);

// Enhanced Watcher for robust data population
watch(
    [() => props.show, () => props.part],
    ([isOpen, part]) => {
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
                form.discount_type = 'fixed';
                form.discount_value = part.discount ? part.discount / (part.qty || 1) : 0;
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
                form.discount_type = 'fixed';
                form.discount_value = 0;
                form.discount = 0;
                form.name = '';
                form.part_id = null;
                selectedPart.value = null;
            }

            // Use timeout to ensure reactivity settles before re-enabling watchers
            setTimeout(() => {
                isPopulating.value = false;
            }, 100);
        }
    },
    { immediate: true }
);
</script>
