<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                {{ item ? getName(item.service) : $t('work_orders.show.add_service') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Service Select (Read-only for edit, editable for add) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('quotes.service_modal.service') }}
                </label>
                <select
                    v-if="!item"
                    v-model="form.service_id"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required
                >
                    <option value="">{{ $t('common.choose') }}</option>
                    <option v-for="service in services" :key="service.id" :value="service.id">
                        {{ getName(service) }}
                    </option>
                </select>
                <div 
                    v-else 
                    class="px-4 py-3 bg-gray-100 dark:bg-gray-900 rounded-xl text-gray-900 dark:text-white"
                >
                    {{ getName(item.service) }}
                </div>
            </div>

            <!-- Title/Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('quotes.service_modal.description') }}
                </label>
                <textarea
                    v-model="form.title"
                    rows="2"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                    :placeholder="$t('quotes.service_modal.description_placeholder')"
                ></textarea>
            </div>

            <!-- Price & Quantity Row -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('quotes.service_modal.qty') }}
                    </label>
                    <input
                        type="number"
                        v-model="form.qty"
                        step="0.01"
                        min="0.01"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required
                    />
                </div>
                
                <!-- Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('quotes.service_modal.price') }}
                    </label>
                    <input
                        type="number"
                        v-model="form.unit_price"
                        step="0.01"
                        min="0"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        required
                    />
                </div>
            </div>

            <!-- Discount Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('quotes.service_modal.discount_method') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="form.discount_type = 'none'"
                            :class="[
                                'px-3 py-1 text-sm rounded-lg transition-colors',
                                form.discount_type === 'none'
                                    ? 'bg-gray-600 text-white'
                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                            ]"
                        >
                            {{ $t('quotes.service_modal.no_discount') }}
                        </button>
                        <button
                            type="button"
                            @click="form.discount_type = 'fixed'"
                            :class="[
                                'px-3 py-1 text-sm rounded-lg transition-colors',
                                form.discount_type === 'fixed'
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                            ]"
                        >
                            {{ $t('quotes.service_modal.fixed') }}
                        </button>
                        <button
                            type="button"
                            @click="form.discount_type = 'percentage'"
                            :class="[
                                'px-3 py-1 text-sm rounded-lg transition-colors',
                                form.discount_type === 'percentage'
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                            ]"
                        >
                            %
                        </button>
                    </div>
                </div>

                <!-- Discount Value -->
                <div v-if="form.discount_type !== 'none'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('quotes.service_modal.discount_value') }}
                    </label>
                    <input
                        type="number"
                        v-model="form.discount_value"
                        step="0.01"
                        min="0"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Calculated Total -->
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('quotes.service_modal.total_cost') }}
                </span>
                <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ formatPrice(calculatedTotal) }}
                </span>
            </div>
        </form>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                type="button"
                @click="submitForm"
                :disabled="form.processing"
                class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 transition-all"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    item: Object,
    departmentId: Number,
    services: Array,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();

// Form
const form = useForm({
    service_id: props.item?.service_id || '',
    title: props.item?.title || '',
    qty: props.item?.qty || 1,
    unit_price: props.item?.unit_price || 0,
    discount_type: props.item?.discount_type || 'none',
    discount_value: props.item?.discount_value || 0,
});

// Computed
const calculatedTotal = computed(() => {
    const price = parseFloat(form.unit_price) || 0;
    const qty = parseFloat(form.qty) || 1;
    let discount = 0;

    if (form.discount_type === 'fixed') {
        discount = parseFloat(form.discount_value) || 0;
    } else if (form.discount_type === 'percentage') {
        discount = (price * (parseFloat(form.discount_value) || 0)) / 100;
    }

    return Math.max(0, (price - discount) * qty);
});

// Methods
function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

function submitForm() {
    if (props.item) {
        // Update existing item
        form.put(route('work-orders.items.update', { work_order: props.workOrder.id, item: props.item.id }), {
            onSuccess: () => {
                form.reset();
                emit('saved');
            },
        });
    } else {
        // Add new item
        form.post(route('work-orders.items.store', { work_order: props.workOrder.id }), {
            onSuccess: () => {
                form.reset();
                emit('saved');
            },
        });
    }
}

// Watch for service selection to auto-fill price
watch(() => form.service_id, (serviceId) => {
    if (serviceId && !props.item) {
        const service = props.services.find(s => s.id === serviceId);
        if (service) {
            form.unit_price = service.base_price || 0;
            form.title = getName(service);
        }
    }
});

// Reset form when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        if (props.item) {
            form.service_id = props.item.service_id;
            form.title = props.item.title;
            form.qty = props.item.qty;
            form.unit_price = props.item.unit_price;
            form.discount_type = props.item.discount_type || 'none';
            form.discount_value = props.item.discount_value || 0;
        } else {
            form.reset();
            form.qty = 1;
        }
    }
});
</script>
