<template>
    <BaseModal :show="show" @close="emit('close')" size="md">
        <template #title>
            <div class="flex items-center gap-2">
                <span class="text-2xl">💰</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ isEditing ? $t('payments.edit_payment') : $t('payments.add_payment') }}
                </span>
            </div>
        </template>

        <form id="payment-form-modal" @submit.prevent="submitForm" class="space-y-4">
            <!-- Operation Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('payments.form.type') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="form.type = 'payment'"
                        :class="[
                            'px-3 py-1.5 rounded-xl border text-sm font-medium transition-all cursor-pointer',
                            form.type === 'payment'
                                ? 'border-action-primary bg-selection-bg text-selection-text'
                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        ]"
                    >
                        {{ $t('payments.types.payment') }}
                    </button>
                    <button
                        v-if="allowRefund"
                        type="button"
                        @click="form.type = 'refund'"
                        :class="[
                            'px-3 py-1.5 rounded-xl border text-sm font-medium transition-all cursor-pointer',
                            form.type === 'refund'
                                ? 'border-danger bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'
                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        ]"
                    >
                        {{ $t('payments.types.refund') }}
                    </button>
                    <button
                        v-if="allowBadDebt"
                        type="button"
                        @click="form.type = 'bad_debt'"
                        :class="[
                            'px-3 py-1.5 rounded-xl border text-sm font-medium transition-all cursor-pointer',
                            form.type === 'bad_debt'
                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'
                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        ]"
                    >
                        {{ $t('payments.types.bad_debt') }}
                    </button>
                </div>
            </div>

            <!-- Payment Method -->
            <div v-if="form.type !== 'bad_debt'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('payments.form.method') }}
                    <span class="text-red-500">*</span>
                </label>
                <SearchableSelect
                    v-model="form.payment_method"
                    :options="paymentMethods"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('common.select')"
                    :class="{ 'border-red-500 rounded-xl': errors.payment_method }"
                />
                <p v-if="errors.payment_method" class="mt-1 text-sm text-red-500">
                    {{ errors.payment_method }}
                </p>
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('payments.form.amount') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <AppInput
                        type="text"
                        inputmode="decimal"
                        v-model="form.amount"
                        @input="forceEnglishNumbers"
                        dir="ltr"
                        class="pr-4 pl-14 text-left font-mono"
                        :error="errors.amount"
                        :placeholder="$t('payments.form.amount_placeholder')"
                    />
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                        {{ activeCurrencySymbol }}
                    </span>
                </div>

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
                    {{ $t('payments.form.date') }}
                    <span class="text-red-500">*</span>
                </label>
                <CustomDatePicker
                    v-model="form.payment_date"
                    name="payment_date"
                    :class="{ 'border-red-500': errors.payment_date }"
                />
                <p v-if="errors.payment_date" class="mt-1 text-sm text-red-500">
                    {{ errors.payment_date }}
                </p>
            </div>

            <!-- Reference -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('payments.form.reference') }}
                </label>
                <AppInput
                    type="text"
                    v-model="form.reference"
                    dir="ltr"
                    class="text-left font-mono"
                    :placeholder="$t('payments.form.reference_placeholder')"
                />
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('payments.form.notes') }}
                </label>
                <AppTextarea
                    v-model="form.notes"
                    rows="2"
                    :placeholder="$t('payments.form.notes_placeholder')"
                />
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton type="button" variant="secondary" @click="emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    form="payment-form-modal"
                    :variant="form.type === 'refund' ? 'danger' : 'primary'"
                    :loading="loading"
                    :disabled="loading"
                >
                    {{
                        loading
                            ? $t('common.loading')
                            : isEditing
                              ? $t('common.save')
                              : $t('payments.add_payment')
                    }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { activeCurrencySymbol } = useNumberFormat();

const props = defineProps({
    show: { type: Boolean, default: false },
    workOrderId: { type: Number, required: true },
    balance: { type: Number, default: 0 },
    excessPaid: { type: Number, default: 0 },
    payment: { type: Object, default: null }, // For editing
    allowRefund: { type: Boolean, default: false },
    allowBadDebt: { type: Boolean, default: false },
    totalPaid: { type: Number, default: 0 },
    pendingMode: { type: Boolean, default: false },
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
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0);
};

// Reset form when modal opens
watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            errors.value = {};
            if (props.payment) {
                form.value = {
                    type: props.payment.type || 'payment',
                    payment_method: props.payment.payment_method,
                    amount: props.payment.amount,
                    payment_date:
                        props.payment.payment_date?.split('T')[0] ||
                        new Date().toISOString().split('T')[0],
                    reference: props.payment.reference || '',
                    notes: props.payment.notes || '',
                };
            } else {
                const defaultRefund = props.allowRefund && props.excessPaid > 0.01;
                form.value = {
                    type: defaultRefund ? 'refund' : 'payment',
                    payment_method: 'cash',
                    amount: defaultRefund
                        ? props.excessPaid
                        : props.balance > 0
                          ? props.balance
                          : '',
                    payment_date: new Date().toISOString().split('T')[0],
                    reference: '',
                    notes: '',
                };
            }
        }
    }
);

watch(
    () => form.value.type,
    (newType) => {
        if (!props.show || isEditing.value) return;
        if (newType === 'refund' && props.excessPaid > 0.01) {
            form.value.amount = props.excessPaid;
        } else if (newType === 'payment' && props.balance > 0.01) {
            form.value.amount = props.balance;
        }
    }
);

function validate() {
    errors.value = {};

    if (form.value.type !== 'bad_debt' && !form.value.payment_method) {
        errors.value.payment_method = t('common.validation.required', {
            field: t('payments.form.method'),
        });
    }

    if (!form.value.amount || parseFloat(form.value.amount) <= 0) {
        errors.value.amount = t('common.validation.required', { field: t('payments.form.amount') });
    }

    if (form.value.type === 'payment' || form.value.type === 'bad_debt') {
        let maxAllowedBalance = parseFloat(props.balance || 0);
        if (isEditing.value && props.payment) {
            if (props.payment.type === 'payment' || props.payment.type === 'bad_debt') {
                maxAllowedBalance += parseFloat(props.payment.amount || 0);
            } else if (props.payment.type === 'refund') {
                maxAllowedBalance = Math.max(
                    0,
                    maxAllowedBalance - parseFloat(props.payment.amount || 0)
                );
            }
        }
        if (parseFloat(form.value.amount) > maxAllowedBalance + 0.001) {
            errors.value.amount = t('payments.amount_exceeds_balance');
        }
    }

    if (form.value.type === 'refund' && parseFloat(form.value.amount) > props.totalPaid + 0.001) {
        errors.value.amount = t('payments.amount_exceeds_paid');
    }

    if (!form.value.payment_date) {
        errors.value.payment_date = t('common.validation.required', {
            field: t('payments.form.date'),
        });
    }

    return Object.keys(errors.value).length === 0;
}

function submitForm() {
    if (form.value.type === 'bad_debt') {
        form.value.payment_method = 'credit';
    }

    if (!validate()) return;

    loading.value = true;

    const data = {
        ...form.value,
        type: form.value.type,
    };

    if (props.pendingMode) {
        emit('saved', { ...data, id: props.payment?.id || Date.now() });
        loading.value = false;
        emit('close');
        return;
    }

    const url = isEditing.value
        ? route('work-orders.payments.update', [props.workOrderId, props.payment.id])
        : route('work-orders.payments.store', props.workOrderId);

    const method = isEditing.value ? 'put' : 'post';

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
