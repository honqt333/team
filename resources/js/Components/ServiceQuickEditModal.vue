<template>
    <BaseModal :show="show" @close="$emit('close')" size="sm">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $t('pricing.quick_edit') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ service?.name }}</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Base Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('pricing.base_price') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" v-model.number="form.base_price" inputmode="decimal" lang="en" step="0.01"
                        min="0"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.base_price }" />
                    <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">{{
                        $t('common.currency_sar') }}</span>
                </div>
                <p v-if="form.errors.base_price" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{
                    form.errors.base_price
                }}</p>
            </div>

            <!-- Min Price Section with Toggle -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('pricing.min_price') }}
                    </label>
                    <!-- Toggle: Percentage vs Fixed -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('pricing.fixed_amount') }}</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="minPriceAsPercentage" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-300 dark:peer-focus:ring-teal-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-teal-600">
                            </div>
                        </label>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('pricing.percentage') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <!-- Input field -->
                    <div class="relative">
                        <input type="number" v-model.number="minPriceInput" inputmode="decimal" lang="en" step="0.01"
                            min="0" :max="minPriceAsPercentage ? 100 : form.base_price"
                            :placeholder="minPriceAsPercentage ? '10' : '0'"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.min_price || minPriceValidationError }" />
                        <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">
                            {{ minPriceAsPercentage ? '%' : $t('common.currency_sar') }}
                        </span>
                    </div>

                    <!-- Calculated Min Price Display (when percentage mode) -->
                    <div v-if="minPriceAsPercentage"
                        class="flex items-center gap-2 px-3 py-2.5 bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-200 dark:border-teal-800">
                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400 flex-shrink-0" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="font-semibold text-teal-700 dark:text-teal-300 text-sm">{{
                            calculatedMinPrice.toFixed(2) }}
                            {{ $t('common.currency_sar') }}</span>
                    </div>
                </div>
                <p v-if="minPriceValidationError" class="text-sm text-red-600 dark:text-red-400">{{
                    minPriceValidationError }}
                </p>
                <p v-else-if="form.errors.min_price" class="text-sm text-red-600 dark:text-red-400">{{
                    form.errors.min_price }}
                </p>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button @click="submitForm" :disabled="form.processing || minPriceValidationError"
                class="px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all">
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

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    service: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

// Toggle for percentage vs fixed min price
const minPriceAsPercentage = ref(false);
const minPricePercentageValue = ref(0);

const form = useForm({
    department_id: '',
    name_ar: '',
    name_en: '',
    description_ar: '',
    description_en: '',
    base_price: 0,
    min_price: 0,
    default_discount_type: 'none',
    default_discount_value: null,
    allow_price_override: false,
    duration_value: null,
    duration_unit: 'minutes',
    warranty_value: null,
    warranty_unit: 'months',
    type: 'internal',
    is_active: true,
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
        form.default_discount_type = newService.default_discount_type || 'none';
        form.default_discount_value = newService.default_discount_value ? parseFloat(newService.default_discount_value) : null;
        form.allow_price_override = Boolean(newService.allow_price_override);
        form.duration_value = newService.duration_value || null;
        form.duration_unit = newService.duration_unit || 'minutes';
        form.warranty_value = newService.warranty_value || null;
        form.warranty_unit = newService.warranty_unit || 'months';
        form.type = newService.type || 'internal';
        form.is_active = newService.is_active ?? true;

        // Reset percentage mode
        minPriceAsPercentage.value = false;
        minPricePercentageValue.value = 0;
    }
}, { immediate: true });

// Reset form when modal opens
watch(() => props.show, (open) => {
    if (open && props.service) {
        form.department_id = props.service.department_id || '';
        form.name_ar = props.service.name_ar || '';
        form.name_en = props.service.name_en || '';
        form.description_ar = props.service.description_ar || '';
        form.description_en = props.service.description_en || '';
        form.base_price = parseFloat(props.service.base_price) || 0;
        form.min_price = parseFloat(props.service.min_price) || 0;
        form.default_discount_type = props.service.default_discount_type || 'none';
        form.default_discount_value = props.service.default_discount_value ? parseFloat(props.service.default_discount_value) : null;
        form.allow_price_override = Boolean(props.service.allow_price_override);
        form.duration_value = props.service.duration_value || null;
        form.duration_unit = props.service.duration_unit || 'minutes';
        form.warranty_value = props.service.warranty_value || null;
        form.warranty_unit = props.service.warranty_unit || 'months';
        form.type = props.service.type || 'internal';
        form.is_active = props.service.is_active ?? true;

        minPriceAsPercentage.value = false;
        minPricePercentageValue.value = 0;
    }
});

function submitForm() {
    // Prevent submission if there's a validation error
    if (minPriceValidationError.value) {
        return;
    }

    form.put(`/app/services/${props.service.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
        },
    });
}
</script>
