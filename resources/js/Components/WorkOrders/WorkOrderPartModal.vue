<template>
    <BaseModal
        :show="show"
        @close="$emit('close')"
        size="lg"
        :overflow-visible="true"
        scroll-entire
    >
        <!-- Modern Header -->
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gray-900 dark:bg-gray-800 text-white flex items-center justify-center text-lg shadow-sm"
                >
                    📦
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight">
                        {{ part ? $t('inventory.parts.edit') : $t('quotes.show.add_part') }}
                    </h3>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="inline-flex w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <p
                            class="text-[11px] font-medium text-gray-500 dark:text-gray-400 capitalize"
                        >
                            {{ $t(`quotes.parts.${form.source}`) }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <form @submit.prevent="submitForm(true)" class="space-y-3.5">
            <!-- Part Source Segmented Control -->
            <div
                v-if="!part"
                class="grid grid-cols-3 gap-1.5 p-1 bg-gray-100 dark:bg-gray-800/80 rounded-xl"
            >
                <button
                    type="button"
                    :disabled="isReadOnly"
                    @click="form.source = 'warehouse'"
                    :class="[
                        'py-1.5 px-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5',
                        form.source === 'warehouse'
                            ? 'bg-white dark:bg-gray-700 text-emerald-600 dark:text-emerald-400 shadow-sm'
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
                        'py-1.5 px-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5',
                        form.source === 'external'
                            ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
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
                        'py-1.5 px-2 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5',
                        form.source === 'customer'
                            ? 'bg-white dark:bg-gray-700 text-amber-600 dark:text-amber-400 shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white',
                        isReadOnly ? 'opacity-50 cursor-not-allowed' : '',
                    ]"
                >
                    <span>👤</span>
                    <span>{{ $t('quotes.parts.customer') }}</span>
                </button>
            </div>

            <!-- Content Container -->
            <div class="space-y-3">
                <!-- Warehouse Search -->
                <div v-if="form.source === 'warehouse' && !selectedPart" class="space-y-2">
                    <div v-if="warehouses.length > 1">
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('inventory.parts.warehouse') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="form.warehouse_id"
                            name="warehouse_id"
                            data-field="warehouse_id"
                            :options="warehouseOptions"
                            :placeholder="$t('inventory.parts.choose_warehouse')"
                            class="w-full text-xs"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('inventory.parts.search_label') }}
                        </label>
                        <InventoryPartPicker
                            v-model="form.part_id"
                            :warehouse-id="form.warehouse_id"
                            @select="selectPart"
                        />
                    </div>
                </div>

                <!-- Selected Part Card -->
                <div
                    v-if="selectedPart"
                    class="flex items-center justify-between p-2.5 bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-xl"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-sm shrink-0"
                        >
                            📦
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-900 dark:text-white leading-tight"
                            >
                                {{ toEnglish(getName(selectedPart)) }}
                            </p>
                            <p
                                class="text-[10px] font-mono font-bold mt-0.5"
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
                        class="p-1 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg text-gray-400 hover:text-red-500 transition-colors"
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
                    <span>{{ $t('work_orders.item.duplicate_part_notice') }}</span>
                </div>

                <!-- Part Identity Fields (when part selected or external/customer) -->
                <div v-if="selectedPart || form.source !== 'warehouse'" class="space-y-3">
                    <!-- Row 1: Part Number & Unit -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
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
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-xs focus:ring-2 focus:ring-focus-ring disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:opacity-60"
                            />
                        </div>
                        <div>
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
                    <div>
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
                            class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-focus-ring disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:opacity-60"
                        />
                    </div>

                    <!-- Row 3: Service Selector (if enabled) & Notes -->
                    <div
                        :class="[
                            'grid gap-3',
                            showServiceSelect ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1',
                        ]"
                    >
                        <div v-if="showServiceSelect">
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('quotes.service_modal.service') }}
                            </label>
                            <SearchableSelect
                                v-model="form.work_order_item_id"
                                name="work_order_item_id"
                                data-field="work_order_item_id"
                                :options="serviceOptions"
                                option-label="label"
                                :disabled="isReadOnly"
                                option-value="value"
                                :placeholder="$t('common.none')"
                                class="w-full text-xs"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                            >
                                {{ $t('inventory.parts.description') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.notes"
                                name="notes"
                                data-field="notes"
                                :disabled="isReadOnly || form.source === 'warehouse'"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs focus:ring-2 focus:ring-focus-ring disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:opacity-60"
                                :placeholder="$t('inventory.parts.description_placeholder')"
                            />
                        </div>
                    </div>
                </div>

                <!-- 4-Column Financial Row: Price | Discount | Qty | Amount -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
                    <!-- 1. Unit Price -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('work_orders.item.part_price') }}
                            <span class="text-red-500">*</span>
                        </label>
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
                                'w-full py-2 px-2 border rounded-xl font-mono text-center text-xs font-bold transition-all focus:ring-2',
                                isPriceBelowMinimum
                                    ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 focus:ring-danger'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-focus-ring',
                                !isPriceEditable
                                    ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-900'
                                    : '',
                            ]"
                        />
                        <p
                            v-if="isPriceBelowMinimum"
                            class="text-[10px] text-red-500 font-bold mt-0.5 truncate"
                        >
                            {{
                                $t('quotes.min_price_warning', {
                                    min: formatCurrency(selectedPartMinPrice),
                                })
                            }}
                        </p>
                    </div>

                    <!-- 2. Discount with inline currency/% switcher -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label
                                class="block text-xs font-semibold text-gray-700 dark:text-gray-300"
                            >
                                {{ $t('quotes.service_modal.discount') }}
                            </label>
                            <div
                                class="flex items-center bg-gray-200 dark:bg-gray-700 rounded-md p-0.5 text-[9px] font-bold"
                            >
                                <button
                                    type="button"
                                    @click="form.discount_type = 'fixed'"
                                    :class="[
                                        'px-1.5 py-0.2 rounded transition-all',
                                        form.discount_type === 'fixed'
                                            ? 'bg-white dark:bg-gray-800 text-emerald-600 shadow-xs'
                                            : 'text-gray-500 dark:text-gray-400',
                                    ]"
                                >
                                    {{ $t('common.currency') }}
                                </button>
                                <button
                                    type="button"
                                    @click="form.discount_type = 'percentage'"
                                    :class="[
                                        'px-1.5 py-0.2 rounded transition-all',
                                        form.discount_type === 'percentage'
                                            ? 'bg-white dark:bg-gray-800 text-emerald-600 shadow-xs'
                                            : 'text-gray-500 dark:text-gray-400',
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
                                'w-full py-2 px-2 border rounded-xl font-mono text-center text-xs font-bold transition-all focus:ring-2',
                                isPriceBelowMinimum
                                    ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-600 focus:ring-danger'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-focus-ring',
                                form.include_in_package || isReadOnly
                                    ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-900'
                                    : '',
                            ]"
                        />
                    </div>

                    <!-- 3. Quantity -->
                    <div>
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
                            class="w-full py-2 px-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-center text-xs font-bold focus:ring-2 focus:ring-focus-ring disabled:opacity-60 disabled:bg-gray-100"
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
                        <AppToggle
                            v-if="form.work_order_item_id || showToggles"
                            v-model="form.include_in_package"
                            size="sm"
                            color="success"
                            :label="$t('work_orders.item.include_in_package')"
                        />
                        <AppToggle
                            v-if="form.include_in_package"
                            v-model="form.hide_on_print"
                            size="sm"
                            color="success"
                            :label="$t('work_orders.item.hide_on_print')"
                        />
                    </div>

                    <!-- Totals on right -->
                    <div class="flex items-center gap-4 self-end sm:self-auto font-mono">
                        <!-- VAT if enabled -->
                        <div v-if="props.workOrder.tax_enabled_snapshot" class="text-right">
                            <span class="text-[10px] font-bold text-gray-400 block uppercase">
                                {{
                                    $t('common.vat_with_rate', {
                                        rate: props.workOrder.tax_rate_snapshot || 15,
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
                        class="px-4 py-2 text-xs font-semibold text-gray-500 hover:text-red-500 transition-colors"
                    >
                        {{ isReadOnly ? $t('common.close') : $t('common.cancel') }}
                    </button>
                    <span
                        v-if="Object.keys(form.errors).length > 0"
                        class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold rounded-lg bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300 border border-red-200 dark:border-red-900"
                    >
                        <svg class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
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
                <div class="flex items-center gap-2">
                    <button
                        v-if="!isReadOnly && !props.part"
                        type="button"
                        @click="submitForm(false)"
                        :disabled="
                            form.processing ||
                            !form.name ||
                            !form.qty ||
                            form.unit_price === '' ||
                            form.unit_price === null ||
                            (form.source === 'warehouse' &&
                                (!form.warehouse_id || !form.part_id)) ||
                            isPriceBelowMinimum
                        "
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold text-xs hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? $t('common.loading')
                                : $t('quotes.service_modal.add_and_another')
                        }}
                    </button>
                    <button
                        v-if="!isReadOnly"
                        type="button"
                        @click="submitForm(true)"
                        :disabled="
                            form.processing ||
                            !form.name ||
                            !form.qty ||
                            form.unit_price === '' ||
                            form.unit_price === null ||
                            (form.source === 'warehouse' &&
                                (!form.warehouse_id || !form.part_id)) ||
                            isPriceBelowMinimum
                        "
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs transition-all shadow-md shadow-emerald-600/20 disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white"
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
import AppToggle from '@/Components/Common/AppToggle.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const { formatCurrency, formatQuantity, toEnglish } = useNumberFormat();
const { scrollToError } = useFormScrollToError();

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    part: Object,
    suggestedPart: { type: Object, default: null },
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
const selectedPart = ref(null);
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
    }
);

// Computed
const isReadOnly = computed(() => {
    return ['approved', 'rejected', 'converted'].includes(props.workOrder.status);
});

const existingParts = computed(() => {
    const list = [...(props.workOrder?.parts || [])];
    if (props.workOrderItems) {
        props.workOrderItems.forEach((item) => {
            if (item.parts && Array.isArray(item.parts)) {
                list.push(...item.parts);
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
    if (!selectedPart.value || !form.warehouse_id) return null;
    const balances = selectedPart.value.inventory_balances || [];
    return balances.find((b) => b.warehouse_id === form.warehouse_id) || null;
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
    if (!props.workOrder.tax_enabled_snapshot) return 0;
    const isInclusive = props.workOrder.pricing_mode_snapshot === 'inclusive';
    const rate = props.workOrder.tax_rate_snapshot || 15;
    const netAmount = calculatedTotal.value;

    if (isInclusive) {
        return netAmount - netAmount / (1 + rate / 100);
    } else {
        return netAmount * (rate / 100);
    }
});

const grandTotalValue = computed(() => {
    const netAmount = calculatedTotal.value;
    if (
        !props.workOrder.tax_enabled_snapshot ||
        props.workOrder.pricing_mode_snapshot === 'inclusive'
    ) {
        return netAmount;
    }
    return netAmount + vatAmount.value;
});

// Options
const serviceOptions = computed(() => {
    return (props.workOrderItems || []).map((line) => ({
        value: line.id,
        label: toEnglish(getName(line.service) || line.title || ''),
    }));
});

const unitOptions = computed(() => {
    return (props.units || []).map((unit) => ({
        value: unit.id,
        label: toEnglish(getName(unit)),
    }));
});

const warehouseOptions = computed(() => {
    return (props.warehouses || []).map((w) => ({
        value: w.id,
        label: toEnglish(w.name),
    }));
});

const availableStock = computed(() => {
    if (!selectedPart.value) return 0;
    if (activeBalance.value) {
        return activeBalance.value.qty_on_hand || 0;
    }
    return selectedPart.value.inventory_balances_sum_qty_on_hand || 0;
});

// Methods
function selectPart(part) {
    selectedPart.value = part;
    form.part_id = part.id;
    form.name = toEnglish(getName(part));
    form.part_number = toEnglish(part.sku || part.barcode || '');

    // Check if there is an active warehouse balance
    const balances = part.inventory_balances || [];
    const balance = balances.find((b) => b.warehouse_id === form.warehouse_id);
    if (balance && parseFloat(balance.sale_price) > 0) {
        form.unit_price = parseFloat(balance.sale_price);
    } else {
        form.unit_price = parseFloat(part.default_sale_price) || 0;
    }

    form.unit_id = part.unit_id;
    form.notes = part.description || '';
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
    form.notes = '';
    form.discount_type = 'fixed';
    form.discount_value = 0;
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

    // Normalize data before processing
    form.qty = toEnglish(form.qty);
    form.unit_price = toEnglish(form.unit_price);
    form.discount = toEnglish(form.discount);

    const total = calculatedTotal.value;
    const tax_amount = vatAmount.value;
    const grand_total = grandTotalValue.value;

    if (props.pendingMode) {
        emit(
            'saved',
            {
                ...form.data(),
                total,
                tax_amount,
                grand_total,
                isPending: true,
                tempId: form.tempId || Date.now(),
            },
            { close: shouldClose }
        );
        if (shouldClose) return;

        // If not closing, reset for next part
        const currentWarehouse = form.warehouse_id;
        const currentItem = form.work_order_item_id;
        form.reset();
        form.warehouse_id = currentWarehouse;
        form.work_order_item_id = currentItem;
        selectedPart.value = null;
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
            }
        },
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

watch(
    () => form.warehouse_id,
    (newWarehouseId) => {
        if (isPopulating.value || form.source !== 'warehouse') return;
        if (!selectedPart.value) return;
        const balances = selectedPart.value.inventory_balances || [];
        const balance = balances.find((b) => b.warehouse_id === newWarehouseId);
        if (balance && parseFloat(balance.sale_price) > 0) {
            form.unit_price = parseFloat(balance.sale_price);
        } else {
            form.unit_price = parseFloat(selectedPart.value.default_sale_price) || 0;
        }
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
                if (props.suggestedPart) selectPart(props.suggestedPart);
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
