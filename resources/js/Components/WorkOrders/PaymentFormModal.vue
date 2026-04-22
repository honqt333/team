<template>
    <Modal :show="show" @close="emit('close')" maxWidth="md">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden w-full mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">💰</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ isEditing ? $t('payments.edit_payment') : $t('payments.add_payment') }}
                    </span>
                </div>
                <button
                    @click="emit('close')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 max-h-[80vh] overflow-y-auto">
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Operation Type -->
                    <div v-if="allowRefund">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('payments.form.type') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-4">
                             <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.type" value="payment" class="text-indigo-600 focus:ring-indigo-500 rounded-full" />
                                <span class="text-sm font-medium" :class="form.type === 'payment' ? 'text-indigo-700 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400'">
                                    {{ $t('payments.types.payment') }}
                                </span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.type" value="refund" class="text-red-600 focus:ring-red-500 rounded-full" />
                                <span class="text-sm font-medium" :class="form.type === 'refund' ? 'text-red-700 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'">
                                    {{ $t('payments.types.refund') }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('payments.form.method') }} <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="form.payment_method"
                            :options="paymentMethods"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('common.select')"
                            :class="{ 'border-red-500 rounded-xl': errors.payment_method }"
                        />
                        <p v-if="errors.payment_method" class="mt-1 text-sm text-red-500">{{ errors.payment_method }}</p>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('payments.form.amount') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                inputmode="decimal"
                                v-model="form.amount"
                                @input="forceEnglishNumbers"
                                dir="ltr"
                                class="w-full pr-4 pl-14 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 text-left font-mono"
                                :class="{ 'border-red-500': errors.amount }"
                                :placeholder="$t('payments.form.amount_placeholder')"
                            />
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">SAR</span>
                        </div>
                        <p v-if="errors.amount" class="mt-1 text-sm text-red-500">{{ errors.amount }}</p>
                        
                        <!-- Quick fill buttons -->
                        <div v-if="balance > 0 && form.type === 'payment'" class="flex gap-2 mt-2">
                            <button
                                type="button"
                                @click="form.amount = balance"
                                class="px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full hover:bg-green-200 dark:hover:bg-green-900/50"
                            >
                                {{ $t('payments.pay_full') }} ({{ formatPrice(balance) }})
                            </button>
                        </div>
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('payments.form.date') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            v-model="form.payment_date"
                            dir="ltr"
                            class="w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 text-left font-mono"
                            :class="{ 'border-red-500': errors.payment_date }"
                        />
                        <p v-if="errors.payment_date" class="mt-1 text-sm text-red-500">{{ errors.payment_date }}</p>
                    </div>

                    <!-- Reference -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('payments.form.reference') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.reference"
                            dir="ltr"
                            class="w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 text-left font-mono"
                            :placeholder="$t('payments.form.reference_placeholder')"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('payments.form.notes') }}
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-indigo-500 placeholder-gray-400 dark:placeholder-gray-500"
                            :placeholder="$t('payments.form.notes_placeholder')"
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                        <button
                            type="button"
                            @click="emit('close')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="loading"
                            class="px-4 py-2 text-sm font-medium text-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="form.type === 'refund' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'"
                        >
                            <svg v-if="loading" class="inline-block w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ loading ? $t('common.loading') : (isEditing ? $t('common.save') : $t('payments.add_payment')) }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: { type: Boolean, default: false },
    workOrderId: { type: Number, required: true },
    balance: { type: Number, default: 0 },
    payment: { type: Object, default: null }, // For editing
    allowRefund: { type: Boolean, default: false },
    totalPaid: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'saved']);

const { t, locale } = useI18n();
const { success, error: showError } = useToast();

const loading = ref(false);
const errors = ref({});

const isEditing = computed(() => !!props.payment);

const form = ref({
    type: 'payment',
    payment_method: '',
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    reference: '',
    notes: '',
});

const paymentMethods = computed(() => [
    { value: 'cash', label: t('payments.methods.cash') },
    { value: 'mada', label: t('payments.methods.mada') },
    { value: 'visa', label: t('payments.methods.visa') },
    { value: 'mastercard', label: t('payments.methods.mastercard') },
    { value: 'transfer', label: t('payments.methods.transfer') },
    { value: 'apple_pay', label: t('payments.methods.apple_pay') },
    { value: 'stc_pay', label: t('payments.methods.stc_pay') },
    { value: 'tabby', label: t('payments.methods.tabby') },
    { value: 'tamara', label: t('payments.methods.tamara') },
    { value: 'credit', label: t('payments.methods.credit') },
    { value: 'other', label: t('payments.methods.other') },
]);

const forceEnglishNumbers = (event) => {
    let val = event.target.value;
    if (!val) {
        form.value.amount = '';
        return;
    }
    
    // Replace Arabic numerals
    const arabicNumbers = [/٠/g, /١/g, /٢/g, /٣/g, /٤/g, /٥/g, /٦/g, /٧/g, /٨/g, /٩/g];
    for (let i = 0; i < 10; i++) {
        val = val.replace(arabicNumbers[i], i.toString());
    }
    
    // Remove everything except numbers and dots
    val = val.replace(/[^\d.]/g, '');
    
    // Ensure only one decimal point
    const parts = val.split('.');
    if (parts.length > 2) {
        val = parts[0] + '.' + parts.slice(1).join('');
    }
    
    form.value.amount = val;
    event.target.value = val;
};

const formatPrice = (amount) => {
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

// Reset form when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        errors.value = {};
        if (props.payment) {
            form.value = {
                type: props.payment.type || 'payment',
                payment_method: props.payment.payment_method,
                amount: props.payment.amount,
                payment_date: props.payment.payment_date?.split('T')[0] || new Date().toISOString().split('T')[0],
                reference: props.payment.reference || '',
                notes: props.payment.notes || '',
            };
        } else {
            form.value = {
                type: 'payment',
                payment_method: 'cash',
                amount: props.balance > 0 ? props.balance : '',
                payment_date: new Date().toISOString().split('T')[0],
                reference: '',
                notes: '',
            };
        }
    }
});

function validate() {
    errors.value = {};
    
    if (!form.value.payment_method) {
        errors.value.payment_method = t('common.validation.required', { field: t('payments.form.method') });
    }
    
    if (!form.value.amount || parseFloat(form.value.amount) <= 0) {
        errors.value.amount = t('common.validation.required', { field: t('payments.form.amount') });
    }

    if (form.value.type === 'refund' && parseFloat(form.value.amount) > props.totalPaid) {
        errors.value.amount = t('payments.amount_exceeds_paid');
    }
    
    if (!form.value.payment_date) {
        errors.value.payment_date = t('common.validation.required', { field: t('payments.form.date') });
    }
    
    return Object.keys(errors.value).length === 0;
}

function submitForm() {
    if (!validate()) return;
    
    loading.value = true;
    
    const url = isEditing.value
        ? route('work-orders.payments.update', [props.workOrderId, props.payment.id])
        : route('work-orders.payments.store', props.workOrderId);
    
    const method = isEditing.value ? 'put' : 'post';
    
    const data = {
        ...form.value,
        type: form.value.type,
    };

    router[method](url, data, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('payments.saved_successfully'));
            loading.value = false;
            emit('saved');
            emit('close');
        },
        onError: (err) => {
            errors.value = err;
            showError(t('common.error_occurred'));
            loading.value = false;
        },
    });
}
</script>
