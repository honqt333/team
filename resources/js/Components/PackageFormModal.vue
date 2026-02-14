<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                {{ service ? $t('services_management.edit') : $t('packages.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Department (Required by backend) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('services_management.form.department') }} <span class="text-red-500">*</span>
                </label>
                <SearchableSelect v-model="form.department_id" :options="departments" :label="''"
                    :placeholder="$t('common.choose')" option-label="name" option-value="id"
                    :error="form.errors.department_id" />
                <p v-if="form.errors.department_id" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{
                    form.errors.department_id }}</p>
            </div>

            <!-- Names Row -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Name Arabic -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name_ar" dir="rtl"
                        :placeholder="$t('services_management.form.name_ar_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.name_ar }" />
                    <p v-if="form.errors.name_ar" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{
                        form.errors.name_ar
                    }}</p>
                </div>

                <!-- Name English -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_en') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name_en" dir="ltr"
                        :placeholder="$t('services_management.form.name_en_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
                </div>
            </div>

            <!-- Package Items Section -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl">
                <div
                    class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center rounded-t-xl">
                    <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        {{ $t('packages.items') }}
                    </h3>
                    <button type="button" @click="calculateTotal"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                        v-if="form.items.length > 0">
                        {{ $t('packages.calculate_total') }}
                    </button>
                </div>

                <div class="p-4 space-y-4 rounded-b-xl">
                    <!-- Add Item Row -->
                    <div class="flex gap-2 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('packages.select_service')
                            }}</label>
                            <SearchableSelect v-model="newItem.id" :options="availableServices" :label="''"
                                :placeholder="$t('common.choose')" option-label="name" option-value="id" />
                        </div>
                        <div class="w-24">
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('packages.quantity')
                            }}</label>
                            <input type="number" v-model.number="newItem.quantity" min="1"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <button type="button" @click="addItem" :disabled="!newItem.id"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed h-[38px]">
                            {{ $t('packages.add_item') }}
                        </button>
                    </div>

                    <!-- Items List -->
                    <div v-if="form.items.length > 0" class="space-y-2">
                        <div v-for="(item, index) in form.items" :key="index"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold text-sm">
                                    {{ item.quantity }}x
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{
                                        getServiceName(item.id) }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ getServicePrice(item.id) }} {{
                                        $t('common.currency_sar')
                                    }} / {{ $t('common.unit') }}</p>
                                </div>
                            </div>
                            <button type="button" @click="removeItem(index)"
                                class="text-red-500 hover:text-red-700 dark:hover:text-red-400 p-1 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm italic">
                        {{ $t('packages.empty') }}
                    </div>
                    <p v-if="form.errors.items" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.items }}
                    </p>
                </div>
            </div>

            <!-- Pricing Section -->
            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $t('pricing.base_price') }}
                </h3>

                <!-- Base Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('pricing.base_price') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" v-model.number="form.base_price" inputmode="decimal" lang="en" step="0.01"
                            min="0"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.base_price }" />
                        <span
                            class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">ر.س</span>
                    </div>
                    <p v-if="form.errors.base_price" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{
                        form.errors.base_price }}</p>
                </div>

                <!-- Min Price Section with Toggle -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('pricing.min_price') }}
                        </label>
                        <!-- Toggle: Percentage vs Fixed -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('pricing.fixed_amount')
                            }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="minPriceAsPercentage" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600">
                                </div>
                            </label>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('pricing.percentage') }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Input field -->
                        <div class="relative">
                            <input type="number" v-model.number="minPriceInput" inputmode="decimal" lang="en"
                                step="0.01" min="0" :max="minPriceAsPercentage ? 100 : form.base_price"
                                :placeholder="minPriceAsPercentage ? '10' : '0'"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                :class="{ 'border-red-500': form.errors.min_price }" />
                            <span
                                class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">
                                {{ minPriceAsPercentage ? '%' : 'ر.س' }}
                            </span>
                        </div>

                        <!-- Calculated Min Price Display (when percentage mode) -->
                        <div v-if="minPriceAsPercentage"
                            class="flex items-center gap-2 px-4 py-2.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <span class="text-xs text-indigo-600 dark:text-indigo-400">{{ $t('pricing.min_price')
                                }}:</span>
                                <span class="font-semibold text-indigo-700 dark:text-indigo-300 ms-1">{{
                                    calculatedMinPrice.toFixed(2) }} ر.س</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('pricing.min_price_hint') }}</p>
                    <p v-if="minPriceValidationError" class="text-sm text-red-600 dark:text-red-400">{{
                        minPriceValidationError
                    }}</p>
                    <p v-else-if="form.errors.min_price" class="text-sm text-red-600 dark:text-red-400">{{
                        form.errors.min_price
                    }}</p>
                </div>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                    <div
                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600">
                    </div>
                </label>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{
                    $t('services_management.form.is_active')
                }}</span>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button @click="submitForm" :disabled="form.processing || minPriceValidationError"
                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useLocalized } from '@/Composables/useLocalized';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    service: {
        type: Object,
        default: null,
    },
    departments: {
        type: Array,
        default: () => [],
    },
    availableServices: {
        type: Array,
        default: () => [],
    }
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();

// Toggle for percentage vs fixed min price
const minPriceAsPercentage = ref(false);
const minPricePercentageValue = ref(0);

// New item state
const newItem = ref({
    id: null,
    quantity: 1
});

const form = useForm({
    department_id: '',
    name_ar: '',
    name_en: '',
    description_ar: '',
    description_en: '', // Fixed description_en field name
    base_price: 0,
    min_price: 0,
    items: [],
    type: 'package',
    is_active: true,
    // Add default values for required fields (even if not used in form)
    default_discount_type: 'none',
    default_discount_value: null,
    allow_price_override: false,
    duration_value: null,
    duration_unit: 'minutes',
    warranty_value: null,
    warranty_unit: 'months',
});

// Computed min price input (handles both modes)
const minPriceInput = computed({
    get() {
        if (minPriceAsPercentage.value) {
            return minPricePercentageValue.value;
        }
        return form.min_price;
    },
    set(value) {
        if (minPriceAsPercentage.value) {
            minPricePercentageValue.value = value;
            // Calculate actual min_price from percentage
            form.min_price = form.base_price > 0 ? (form.base_price * (100 - value) / 100) : 0;
        } else {
            form.min_price = value;
        }
    }
});

// Calculated min price display (for percentage mode)
const calculatedMinPrice = computed(() => {
    if (minPriceAsPercentage.value && form.base_price > 0) {
        return form.base_price * (100 - minPricePercentageValue.value) / 100;
    }
    return form.min_price || 0;
});

// Real-time validation for min_price
const minPriceValidationError = computed(() => {
    if (!minPriceAsPercentage.value) {
        // Fixed amount mode - check if min_price > base_price
        if (form.min_price > 0 && form.base_price > 0 && form.min_price > form.base_price) {
            return t('pricing.errors.min_price_exceeds_base', { price: form.base_price, currency: t('common.currency_sar') });
        }
    } else {
        // Percentage mode - check if percentage > 100
        if (minPricePercentageValue.value > 100) {
            return t('pricing.errors.percentage_invalid');
        }
    }
    return null;
});

// Recalculate min_price when base_price changes in percentage mode
watch(() => form.base_price, (newBasePrice) => {
    if (minPriceAsPercentage.value && newBasePrice > 0) {
        form.min_price = newBasePrice * (100 - minPricePercentageValue.value) / 100;
    }
});

// Recalculate when switching modes
watch(minPriceAsPercentage, (isPercentage) => {
    if (isPercentage) {
        // Switching to percentage mode - calculate percentage from current min_price
        if (form.base_price > 0 && form.min_price > 0) {
            minPricePercentageValue.value = Math.round((1 - form.min_price / form.base_price) * 100);
        } else {
            minPricePercentageValue.value = 0;
        }
    }
});

// Helper to get service name from ID
function getServiceName(id) {
    const service = props.availableServices.find(s => s.id === id);
    return service ? getName(service) : '';
}

// Helper to get service price from ID
function getServicePrice(id) {
    const service = props.availableServices.find(s => s.id === id);
    return service ? service.base_price : 0;
}

// Add item to list
function addItem() {
    if (newItem.value.id) {
        // Check if already exists
        const existing = form.items.find(i => i.id === newItem.value.id);
        if (existing) {
            existing.quantity += newItem.value.quantity;
        } else {
            form.items.push({
                id: newItem.value.id,
                quantity: newItem.value.quantity
            });
        }
        // Reset inputs
        newItem.value.id = null;
        newItem.value.quantity = 1;
        form.clearErrors('items');
    }
}

// Remove item from list
function removeItem(index) {
    form.items.splice(index, 1);
}

// Calculate total price from items
function calculateTotal() {
    let total = 0;
    form.items.forEach(item => {
        const price = getServicePrice(item.id);
        total += price * item.quantity;
    });
    form.base_price = total;
}

// Initialize form when service prop changes
watch(() => props.service, (newService) => {
    if (newService) {
        form.department_id = newService.department_id || '';
        form.name_ar = newService.name_ar || '';
        form.name_en = newService.name_en || '';
        form.description_ar = newService.description_ar || '';
        form.description_en = newService.description_en || '';
        form.base_price = parseFloat(newService.base_price) || 0;
        form.min_price = parseFloat(newService.min_price) || 0;
        form.type = 'package';
        form.is_active = newService.is_active ?? true;

        // Load items if editing
        if (newService.items) {
            form.items = newService.items.map(item => ({
                id: item.id,
                quantity: item.pivot.quantity
            }));
        } else {
            form.items = [];
        }

        // Reset percentage mode
        minPriceAsPercentage.value = false;
        minPricePercentageValue.value = 0;
    }
}, { immediate: true });

// Reset form when modal opens for create
watch(() => props.show, (open) => {
    if (open && !props.service) {
        form.reset();
        form.items = [];
        form.type = 'package';
        form.is_active = true;
        minPriceAsPercentage.value = false;
        minPricePercentageValue.value = 0;
        newItem.value = { id: null, quantity: 1 };
    }
});

function submitForm() {
    if (minPriceValidationError.value) return;

    const url = props.service
        ? `/app/services/${props.service.id}`
        : '/app/services';

    const method = props.service ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('saved');
            emit('close');
        },
    });
}
</script>
