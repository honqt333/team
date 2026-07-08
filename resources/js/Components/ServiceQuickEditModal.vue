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

            <!-- Discount -->
            <div class="pt-3 border-t border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('pricing.discount_type') }}</label>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="form.default_discount_type = 'none'"
                            :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all',
                                form.default_discount_type === 'none'
                                    ? 'bg-teal-600 border-teal-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                            {{ $t('pricing.discount_types.none') }}
                        </button>
                        <button type="button" @click="form.default_discount_type = 'fixed'"
                            :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all',
                                form.default_discount_type === 'fixed'
                                    ? 'bg-teal-600 border-teal-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                            {{ $t('pricing.discount_types.fixed') }}
                        </button>
                        <button type="button" @click="form.default_discount_type = 'percentage'"
                            :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all',
                                form.default_discount_type === 'percentage'
                                    ? 'bg-teal-600 border-teal-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                            %
                        </button>
                    </div>
                </div>
                <div v-if="form.default_discount_type !== 'none'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ $t('pricing.discount_value') }}</label>
                    <div class="relative">
                        <input type="number" v-model.number="form.default_discount_value" inputmode="decimal" lang="en" step="0.01" min="0"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        />
                        <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">{{ form.default_discount_type === 'percentage' ? '%' : 'ر.س' }}</span>
                    </div>
                </div>
            </div>

            <!-- Min Price -->
            <div class="space-y-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('pricing.min_price') }}
                </label>
                <div class="relative">
                    <input type="number" v-model.number="form.min_price" inputmode="decimal" lang="en" step="0.01"
                        min="0" :max="form.base_price"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.min_price || minPriceValidationError }" />
                    <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">
                        {{ $t('common.currency_sar') }}
                    </span>
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
import { ref, computed, watch, nextTick } from 'vue';
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

// Retain all original attributes required for backend service validation
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

const minPriceValidationError = computed(() => {
    if (form.min_price > 0 && form.base_price > 0 && form.min_price > form.base_price) {
        return t('pricing.errors.min_price_exceeds_base', { price: form.base_price, currency: t('common.currency_sar') });
    }
    return null;
});

const isInitializing = ref(false);

watch(
    () => [form.base_price, form.default_discount_type, form.default_discount_value],
    ([basePrice, discountType, discountValue]) => {
        if (isInitializing.value) return;

        const bp = parseFloat(basePrice) || 0;
        const dv = parseFloat(discountValue) || 0;

        if (discountType === 'none') {
            form.min_price = bp;
        } else if (discountType === 'fixed') {
            form.min_price = Math.max(0, bp - dv);
        } else if (discountType === 'percentage') {
            form.min_price = Math.max(0, bp - (bp * dv / 100));
        }
    }
);

watch(() => props.service, (newService) => {
    if (newService) {
        isInitializing.value = true;
        form.department_id          = newService.department_id || '';
        form.name_ar                = newService.name_ar || '';
        form.name_en                = newService.name_en || '';
        form.description_ar         = newService.description_ar || '';
        form.description_en         = newService.description_en || '';
        form.base_price             = parseFloat(newService.base_price) || 0;
        form.min_price              = parseFloat(newService.min_price) || 0;
        form.default_discount_type  = newService.default_discount_type || 'none';
        form.default_discount_value = newService.default_discount_value ? parseFloat(newService.default_discount_value) : null;
        form.allow_price_override   = Boolean(newService.allow_price_override);
        form.duration_value         = newService.duration_value || null;
        form.duration_unit          = newService.duration_unit || 'minutes';
        form.warranty_value         = newService.warranty_value || null;
        form.warranty_unit          = newService.warranty_unit || 'months';
        form.type                   = newService.type || 'internal';
        form.is_active              = newService.is_active ?? true;
        nextTick(() => {
            isInitializing.value = false;
        });
    }
}, { immediate: true });

watch(() => props.show, (open) => {
    if (open && props.service) {
        isInitializing.value = true;
        form.department_id          = props.service.department_id || '';
        form.name_ar                = props.service.name_ar || '';
        form.name_en                = props.service.name_en || '';
        form.description_ar         = props.service.description_ar || '';
        form.description_en         = props.service.description_en || '';
        form.base_price             = parseFloat(props.service.base_price) || 0;
        form.min_price              = parseFloat(props.service.min_price) || 0;
        form.default_discount_type  = props.service.default_discount_type || 'none';
        form.default_discount_value = props.service.default_discount_value ? parseFloat(props.service.default_discount_value) : null;
        form.allow_price_override   = Boolean(props.service.allow_price_override);
        form.duration_value         = props.service.duration_value || null;
        form.duration_unit          = props.service.duration_unit || 'minutes';
        form.warranty_value         = props.service.warranty_value || null;
        form.warranty_unit          = props.service.warranty_unit || 'months';
        form.type                   = props.service.type || 'internal';
        form.is_active              = props.service.is_active ?? true;
        nextTick(() => {
            isInitializing.value = false;
        });
    }
});

function submitForm() {
    if (minPriceValidationError.value) return;

    if (form.default_discount_type === 'none') {
        form.default_discount_value = null;
    } else if (form.default_discount_value === '' || form.default_discount_value === undefined) {
        form.default_discount_value = null;
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
