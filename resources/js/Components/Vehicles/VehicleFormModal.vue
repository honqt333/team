<template>
    <BaseModal :show="show" @close="handleClose" size="lg" scroll-entire>
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-cyan-600 flex items-center justify-center"
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
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                        />
                    </svg>
                </div>
                {{ vehicle ? $t('vehicles.edit.title') : $t('vehicles.create.title') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- License Plate Section -->
            <div
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ $t('vehicles.form.plate') }}
                    <span class="text-red-500">*</span>
                </label>
                <!-- Saudi Plate Interactive Input -->
                <SaudiPlateInput
                    ref="plateInput"
                    v-model="form.plate_number"
                    :error="form.errors.plate_number"
                    @blur="checkPlateExistence"
                />
                <p
                    v-if="form.errors.plate_number"
                    class="mt-2 text-sm text-red-600 dark:text-red-400 text-center"
                >
                    {{ form.errors.plate_number }}
                </p>
            </div>

            <!-- Make & Model Section -->
            <div
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('vehicles.form.make') }} / {{ $t('vehicles.form.model') }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Make -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            {{ $t('vehicles.form.make') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="form.make_id"
                            @change="onMakeChange"
                            :options="[
                                ...makes,
                                {
                                    id: '__other__',
                                    name_en: $t('common.other'),
                                    name_ar: $t('common.other'),
                                },
                            ]"
                            :option-label="(m) => m.name_en || m.name_ar"
                            option-value="id"
                            :label="''"
                            :placeholder="$t('common.choose')"
                            :error="form.errors.make_id"
                        />
                    </div>

                    <!-- Model -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            {{ $t('vehicles.form.model') }}
                        </label>
                        <SearchableSelect
                            v-if="form.make_id && form.make_id !== '__other__'"
                            v-model="form.model_id"
                            :options="[
                                ...availableModels,
                                {
                                    id: '__other__',
                                    name_en: $t('common.other'),
                                    name_ar: $t('common.other'),
                                },
                            ]"
                            :option-label="(m) => m.name_en || m.name_ar"
                            option-value="id"
                            :label="''"
                            :placeholder="$t('common.choose')"
                            :error="form.errors.model_id"
                        />
                        <input
                            v-else
                            type="text"
                            disabled
                            :placeholder="$t('vehicles.form.model')"
                            class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-100 dark:bg-gray-900 text-gray-400 text-sm cursor-not-allowed"
                        />
                    </div>
                </div>

                <!-- Other Make/Model Inputs -->
                <div
                    v-if="form.make_id === '__other__' || form.model_id === '__other__'"
                    class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-if="form.make_id === '__other__'">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ $t('vehicles.form.make_other') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="form.make_other"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                :class="{ 'border-red-500': form.errors.make_other }"
                            />
                        </div>
                        <div v-if="form.make_id === '__other__' || form.model_id === '__other__'">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ $t('vehicles.form.model_other') }}
                            </label>
                            <input
                                type="text"
                                v-model="form.model_other"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Section -->
            <div
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('vehicles.form.customer') }}
                        <span class="text-red-500">*</span>
                    </span>
                    <!-- Only show add button if no default customer -->
                    <Tooltip v-if="!defaultCustomerId" :text="$t('vehicles.quick_add_customer')">
                        <button
                            type="button"
                            @click="showCustomerModal = true"
                            class="w-7 h-7 rounded-lg bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 hover:bg-teal-200 dark:hover:bg-teal-900/50 flex items-center justify-center transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </button>
                    </Tooltip>
                </div>
                <!-- Show read-only customer info when defaultCustomerId is set -->
                <div
                    v-if="defaultCustomerId && selectedCustomer"
                    class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600"
                >
                    <div
                        class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center"
                    >
                        <span class="text-white font-bold">
                            {{ selectedCustomer.name?.charAt(0)?.toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ selectedCustomer.name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" dir="ltr">
                            {{ selectedCustomer.phone }}
                        </p>
                    </div>
                </div>
                <!-- Show dropdown if no default customer -->
                <SearchableSelect
                    v-else
                    v-model="form.customer_id"
                    :options="localCustomers"
                    :placeholder="$t('common.choose')"
                    :option-label="(c) => `${c.name} (${c.phone})`"
                    option-value="id"
                    :label="''"
                    :error="form.errors.customer_id"
                />
            </div>

            <!-- Vehicle Details Section -->
            <div
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ $t('vehicles.form.details') }}
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Year -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            {{ $t('vehicles.form.year') }}
                        </label>
                        <SearchableSelect
                            v-model="form.year"
                            :options="yearOptions"
                            :placeholder="$t('common.choose')"
                            :label="''"
                        />
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            {{ $t('vehicles.form.color') }}
                        </label>
                        <SearchableSelect
                            v-model="form.color"
                            :options="colors"
                            option-label="name"
                            option-value="name"
                            :placeholder="$t('common.choose')"
                            :label="''"
                        >
                            <template #prefix="{ option }">
                                <span
                                    v-if="option && option.hex_code"
                                    class="w-3.5 h-3.5 rounded-full border border-gray-300 dark:border-gray-600 inline-block shrink-0"
                                    :style="{ backgroundColor: option.hex_code }"
                                ></span>
                            </template>
                        </SearchableSelect>
                    </div>

                    <!-- Odometer -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            {{ $t('vehicles.form.odometer') }}
                        </label>
                        <input
                            type="text"
                            inputmode="numeric"
                            v-model="form.odometer"
                            @input="
                                form.odometer = normalizeArabicNumerals(
                                    $event.target.value
                                ).replace(/[^0-9]/g, '')
                            "
                            :placeholder="$t('vehicles.form.odometer_placeholder')"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        />
                    </div>
                </div>

                <!-- VIN -->
                <div class="mt-3">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ $t('vehicles.form.vin') }}
                    </label>
                    <input
                        type="text"
                        v-model="form.vin"
                        dir="ltr"
                        @input="
                            form.vin = normalizeArabicNumerals($event.target.value)
                                .replace(/[^a-zA-Z0-9]/g, '')
                                .toUpperCase()
                        "
                        :placeholder="$t('vehicles.form.vin_placeholder')"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500 focus:ring-red-500': form.errors.vin }"
                    />
                    <p v-if="form.errors.vin" class="mt-1 text-xs text-red-600 dark:text-red-400">
                        {{ form.errors.vin }}
                    </p>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('vehicles.form.notes') }}
                </label>
                <textarea
                    v-model="form.notes"
                    rows="2"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none transition-all"
                ></textarea>
            </div>
        </form>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                @click="submitForm"
                :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>

        <!-- Customer Quick-Add Modal -->
        <CustomerFormModal
            :show="showCustomerModal"
            :customer="null"
            @close="showCustomerModal = false"
            @saved="handleCustomerSaved"
        />

        <!-- Duplicate Plate Alert Modal -->
        <BaseModal :show="showDuplicateModal" @close="showDuplicateModal = false" size="md">
            <template #title>
                <div class="flex items-center gap-3 text-amber-600 dark:text-amber-400">
                    <span class="text-2xl">⚠️</span>
                    <span>
                        {{
                            $t('vehicles.validation.plate_taken_title') ||
                            'رقم اللوحة موجود مسبقاً!'
                        }}
                    </span>
                </div>
            </template>

            <div class="space-y-4 p-1">
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{
                        $t('vehicles.validation.plate_taken_desc') ||
                        'المركبة التي تحمل رقم اللوحة هذا مسجلة مسبقاً بالنظام. يمكنك الضغط على بطاقة المركبة أدناه للانتقال لملفها مباشرة:'
                    }}
                </p>

                <div
                    v-if="duplicateVehicle"
                    @click="navigateToDuplicateVehicle"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden"
                >
                    <!-- Card Content -->
                    <div class="relative z-10 flex flex-col h-full">
                        <!-- Header: Plate -->
                        <div
                            class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent dark:from-gray-900/50"
                        >
                            <SaudiPlateDisplay
                                :plate-number="duplicateVehicle.plate_number"
                                size="sm"
                            />
                        </div>

                        <!-- Body -->
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div class="mb-3">
                                <div class="flex items-baseline justify-between mb-1 gap-1">
                                    <h3
                                        class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate"
                                    >
                                        {{ duplicateVehicle.make_name || $t('common.na') }}
                                    </h3>
                                    <span
                                        v-if="duplicateVehicle.year"
                                        class="px-1.5 py-0.5 text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-md"
                                    >
                                        {{ duplicateVehicle.year }}
                                    </span>
                                </div>
                                <p
                                    class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate"
                                >
                                    {{ duplicateVehicle.model_name }}
                                </p>
                            </div>

                            <!-- Customer -->
                            <div
                                class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700/50 flex items-center gap-2.5"
                            >
                                <div
                                    class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0 text-indigo-600 dark:text-indigo-400"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                </div>
                                <div class="flex flex-col truncate">
                                    <span
                                        class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate"
                                    >
                                        {{ duplicateVehicle.customer_name || $t('common.unknown') }}
                                    </span>
                                    <span
                                        v-if="duplicateVehicle.customer_phone"
                                        class="text-[10px] text-gray-400 font-mono"
                                    >
                                        {{ duplicateVehicle.customer_phone }}
                                    </span>
                                </div>
                            </div>

                            <!-- VIN / Color -->
                            <div
                                class="text-[10px] text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700/50 flex flex-col gap-1.5 mt-3"
                            >
                                <div
                                    v-if="duplicateVehicle.color"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-3 h-3 rounded-full border border-white dark:border-gray-700 shadow-sm"
                                        :style="{
                                            backgroundColor: getColorHex(duplicateVehicle.color),
                                        }"
                                    ></span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ duplicateVehicle.color }}
                                    </span>
                                </div>
                                <div v-if="duplicateVehicle.vin" class="flex items-center gap-1.5">
                                    <svg
                                        class="w-3.5 h-3.5 text-indigo-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                        />
                                    </svg>
                                    <span class="font-mono text-[9px] truncate max-w-[150px]">
                                        {{ duplicateVehicle.vin }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="showDuplicateModal = false"
                        class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="navigateToDuplicateVehicle"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow"
                    >
                        {{ $t('vehicles.view_details') || 'عرض التفاصيل' }}
                    </button>
                </div>
            </template>
        </BaseModal>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Tooltip from '@/Components/Tooltip.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import SaudiPlateInput from '@/Components/Vehicles/SaudiPlateInput.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import axios from 'axios';

// Convert Arabic numerals to English
function normalizeArabicNumerals(value) {
    const arabicToEnglish = {
        '٠': '0',
        '١': '1',
        '٢': '2',
        '٣': '3',
        '٤': '4',
        '٥': '5',
        '٦': '6',
        '٧': '7',
        '٨': '8',
        '٩': '9',
    };
    return String(value)
        .split('')
        .map((char) => arabicToEnglish[char] || char)
        .join('');
}

const { t } = useI18n();
const { confirm } = useConfirm();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    vehicle: {
        type: Object,
        default: null,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
    colors: {
        type: Array,
        default: () => [],
    },
    modelsByMake: {
        type: Object,
        default: () => ({}),
    },
    defaultCustomerId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['close', 'saved', 'customer-created']);

const showCustomerModal = ref(false);
const duplicateVehicle = ref(null);
const showDuplicateModal = ref(false);
const checkingPlate = ref(false);

const checkPlateExistence = async () => {
    if (props.vehicle || !form.plate_number?.trim()) {
        return;
    }

    checkingPlate.value = true;
    try {
        const response = await axios.get(
            route('vehicles.check-plate', { plate_number: form.plate_number })
        );
        if (response.data.exists) {
            duplicateVehicle.value = response.data.vehicle;
            showDuplicateModal.value = true;
            form.errors.plate_number =
                t('vehicles.validation.plate_taken') || 'رقم اللوحة هذا مسجل مسبقاً!';
        } else {
            duplicateVehicle.value = null;
            if (
                form.errors.plate_number ===
                (t('vehicles.validation.plate_taken') || 'رقم اللوحة هذا مسجل مسبقاً!')
            ) {
                form.errors.plate_number = null;
            }
        }
    } catch (err) {
        console.error('Error checking plate existence:', err);
    } finally {
        checkingPlate.value = false;
    }
};

const navigateToDuplicateVehicle = () => {
    if (duplicateVehicle.value) {
        showDuplicateModal.value = false;
        emit('close');
        router.visit(route('vehicles.show', duplicateVehicle.value.id));
    }
};

const getColorHex = (colorName) => {
    if (!colorName) return null;
    const found = props.colors?.find(
        (c) => c.name_ar === colorName || c.name_en === colorName || c.name === colorName
    );
    return found ? found.hex_code : null;
};
const localCustomers = computed(() => props.customers || []);
const selectedCustomer = computed(() => {
    if (props.defaultCustomerId) {
        return localCustomers.value.find((c) => c.id === props.defaultCustomerId) || null;
    }
    return null;
});
const pendingCustomerPhone = ref(null); // Store phone instead of ID
const isDirty = ref(false);
const initialFormData = ref(null);
const isConfirming = ref(false);

// Generate year options (last 40 years)
const currentYear = new Date().getFullYear();
const yearOptions = computed(() => {
    const years = [];
    for (let y = currentYear + 1; y >= currentYear - 40; y--) {
        years.push(y);
    }
    return years;
});

// Available models based on selected make
const availableModels = computed(() => {
    if (!props.modelsByMake || !form.make_id || form.make_id === '__other__') {
        return [];
    }
    return props.modelsByMake[form.make_id] || [];
});

const form = useForm({
    customer_id: '',
    plate_number: '',
    make_id: '',
    model_id: '',
    make_other: '',
    model_other: '',
    year: '',
    color: '',
    vin: '',
    odometer: '',
    notes: '',
});

// Initialize form when vehicle prop changes (for edit mode)
watch(
    () => props.vehicle,
    (newVehicle) => {
        if (newVehicle) {
            form.customer_id = newVehicle.customer_id || '';
            form.plate_number = newVehicle.plate_number || '';
            form.make_id = newVehicle.make_id || (newVehicle.make_other ? '__other__' : '');
            form.model_id = newVehicle.model_id || (newVehicle.model_other ? '__other__' : '');
            form.make_other = newVehicle.make_other || '';
            form.model_other = newVehicle.model_other || '';
            form.year = newVehicle.year || '';
            form.color = newVehicle.color || '';
            form.vin = newVehicle.vin || '';
            form.odometer = newVehicle.odometer || '';
            form.notes = newVehicle.notes || '';
        }
    },
    { immediate: true }
);

// Reset form when modal opens for create
watch(
    () => props.show,
    (open) => {
        if (open) {
            if (!props.vehicle) {
                form.reset();
                // Auto-fill customer if defaultCustomerId is provided
                if (props.defaultCustomerId) {
                    form.customer_id = props.defaultCustomerId;
                }
            }
            // Snapshot initial form data after short delay to capture initial state
            setTimeout(() => {
                initialFormData.value = JSON.stringify(form.data());
                isDirty.value = false;
            }, 100);
        }
    },
    { immediate: true }
);

// Track form changes
watch(
    () => form.data(),
    () => {
        if (initialFormData.value) {
            isDirty.value = JSON.stringify(form.data()) !== initialFormData.value;
        }
    },
    { deep: true }
);

// Clear VIN validation error on change
watch(
    () => form.vin,
    () => {
        if (form.errors.vin) {
            form.clearErrors('vin');
        }
    }
);

// Watch for customers prop updates to auto-select pending customer
watch(
    () => props.customers,
    (newCustomers, oldCustomers) => {
        if (pendingCustomerPhone.value && newCustomers) {
            // Find customer by phone number (unique identifier)
            const customerExists = newCustomers.find(
                (c) =>
                    c.phone === pendingCustomerPhone.value ||
                    c.phone?.replace(/\s/g, '') === pendingCustomerPhone.value?.replace(/\s/g, '')
            );

            if (customerExists) {
                form.customer_id = customerExists.id;
                pendingCustomerPhone.value = null; // Clear after selection
            } else {
                console.warn('[VehicleFormModal] Pending customer not found in updated list');
            }
        }
    },
    { deep: true }
);

function resetForm() {
    form.reset();
    isDirty.value = false;
    initialFormData.value = null;
}

async function handleClose() {
    if (isConfirming.value) return;

    if (isDirty.value) {
        isConfirming.value = true;
        try {
            const confirmed = await confirm({
                title: t('common.unsaved_changes'),
                message: t('common.unsaved_changes_message'),
                confirmText: t('common.yes_close'),
                cancelText: t('common.cancel'),
                type: 'warning',
            });

            if (!confirmed) return;
        } finally {
            isConfirming.value = false;
        }
    }

    resetForm();
    emit('close');
}

function onMakeChange() {
    // Reset model when make changes
    form.model_id = '';
    form.model_other = '';
}

function handleCustomerSaved(savedCustomer) {
    showCustomerModal.value = false;

    // Store the phone number for finding customer after reload
    // Phone is unique and we have it before ID is assigned
    if (savedCustomer && savedCustomer.phone) {
        pendingCustomerPhone.value = savedCustomer.phone;
    }

    // Emit the new customer to parent so it can reload customers
    emit('customer-created', savedCustomer);
}

// Listen for customer-reloaded event from parent
function handleCustomerReloaded(event) {
    const { customerId } = event.detail;

    if (customerId && props.customers) {
        const customerExists = props.customers.find((c) => c.id === customerId);

        if (customerExists) {
            form.customer_id = customerId;
            pendingCustomerId.value = null;
        }
    }
}

onMounted(() => {
    window.addEventListener('customer-reloaded', handleCustomerReloaded);
});

onUnmounted(() => {
    window.removeEventListener('customer-reloaded', handleCustomerReloaded);
});

const plateInput = ref(null);

function validate() {
    form.clearErrors();
    let isValid = true;

    // 1. Plate Number Validation
    // Use the component's internal validation if available, otherwise fallback to simple check
    const isPlateValid = plateInput.value ? plateInput.value.validate() : !!form.plate_number;
    if (!isPlateValid) {
        form.setError('plate_number', ' '); // Trigger red border
        isValid = false;
    }

    // 2. Make Validation
    if (!form.make_id) {
        form.setError('make_id', ' ');
        isValid = false;
    } else if (form.make_id === '__other__') {
        // If Make is Other, Make Name is required
        if (!form.make_other) {
            form.setError('make_other', ' ');
            isValid = false;
        }
        // And Model Name is also required (since standard model dropdown is disabled)
        if (!form.model_other) {
            form.setError('model_other', ' ');
            isValid = false;
        }
    } else {
        // Normal Make selected
        // 3. Model Validation
        if (!form.model_id) {
            form.setError('model_id', ' ');
            isValid = false;
        } else if (form.model_id === '__other__') {
            // If Model is Other, Model Name is required
            if (!form.model_other) {
                form.setError('model_other', ' ');
                isValid = false;
            }
        }
    }

    // 4. Customer Validation
    if (!form.customer_id) {
        form.setError('customer_id', ' ');
        isValid = false;
    }

    return isValid;
}

function submitForm() {
    if (!validate()) {
        return;
    }

    const url = props.vehicle ? `/app/vehicles/${props.vehicle.id}` : '/app/vehicles';

    const method = props.vehicle ? 'put' : 'post';

    // Transform data before sending - convert empty strings to null for optional fields
    form.transform((data) => ({
        ...data,
        make_id: data.make_id === '__other__' ? null : data.make_id || null,
        model_id: data.model_id === '__other__' ? null : data.model_id || null,
        year: data.year || null,
        odometer: data.odometer || null,
    }))[method](url, {
        preserveScroll: true,
        onSuccess: (page) => {
            // Get the saved vehicle from page props
            const savedVehicle = page.props.vehicle ||
                props.vehicle || {
                    ...form.data(),
                    customer_id: form.customer_id,
                    plate_number: form.plate_number,
                };

            // Reset form and dirty state
            resetForm();
            emit('saved', savedVehicle);
            emit('close');
        },
    });
}
</script>
