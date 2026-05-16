<template>
    <BaseModal :show="show" @close="$emit('close')" size="md" z-index-class="z-[60]">
        <template #title>
            <div class="flex items-center gap-2">
                <span class="text-2xl">💰</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ isEditing ? $t('payments.edit_payment') : $t('payments.add_payment') }}
                </span>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Payment Method -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.method') }} <span class="text-red-500">*</span>
                </label>
                <SearchableSelect
                    v-model="localPayment.payment_method"
                    :options="paymentMethods"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('common.select')"
                />
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.amount') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input
                        type="text"
                        inputmode="decimal"
                        dir="ltr"
                        v-model="localPayment.amount"
                        @input="handleAmountInput"
                        class="w-full pr-20 pl-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 dark:focus:ring-blue-500/20 shadow-sm transition-all"
                        :class="parseFloat(localPayment.amount) > maxAmount ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500'"
                        :placeholder="$t('payments.form.amount_placeholder')"
                    />
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400 text-sm font-medium">
                        SAR
                    </div>
                </div>
                <p v-if="parseFloat(localPayment.amount) > maxAmount" class="mt-1.5 text-sm text-red-500">
                    {{ $t('payments.amount_exceeds_balance') }}
                </p>
            </div>

            <!-- Payment Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.date') }} <span class="text-red-500">*</span>
                </label>
                <CustomDatePicker
                    v-model="localPayment.payment_date"
                    :placeholder="$t('payments.form.date')"
                    class="w-full"
                />
            </div>

            <!-- Reference -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.reference') }}
                </label>
                <input
                    type="text"
                    v-model="localPayment.reference"
                    dir="ltr"
                    class="w-full px-4 py-2.5 text-sm border rounded-xl bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 text-left font-mono"
                    :placeholder="$t('payments.form.reference_placeholder')"
                />
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.notes') }}
                </label>
                <textarea
                    v-model="localPayment.notes"
                    rows="2"
                    class="w-full px-4 py-2.5 text-sm border rounded-xl bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-gray-500 resize-none"
                    :placeholder="$t('payments.form.notes_placeholder')"
                ></textarea>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                type="button"
                @click="submit"
                :disabled="parseFloat(localPayment.amount) > maxAmount || !localPayment.amount || parseFloat(localPayment.amount) <= 0"
                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ isEditing ? $t('common.save') : $t('payments.add_payment') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    payment: Object,
    balance: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { toEnglish, formatCurrency } = useNumberFormat();

const isEditing = computed(() => !!props.payment);

const localPayment = ref({
    payment_method: 'cash',
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    reference: '',
    notes: '',
});

const maxAmount = computed(() => {
    if (props.payment) {
        return props.balance + Number(props.payment.amount || 0);
    }
    return props.balance;
});

const paymentMethods = computed(() => [
    { value: 'cash', label: t('payments.methods.cash') },
    { value: 'mada', label: t('payments.methods.mada') },
    { value: 'visa', label: t('payments.methods.visa') },
    { value: 'mastercard', label: t('payments.methods.mastercard') },
    { value: 'transfer', label: t('payments.methods.transfer') },
    { value: 'apple_pay', label: t('payments.methods.apple_pay') },
    { value: 'stc_pay', label: t('payments.methods.stc_pay') },
    { value: 'other', label: t('payments.methods.other') },
]);

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.payment) {
            localPayment.value = { ...props.payment };
        } else {
            localPayment.value = {
                payment_method: 'cash',
                amount: props.balance > 0 ? props.balance : '',
                payment_date: new Date().toISOString().split('T')[0],
                reference: '',
                notes: '',
            };
        }
    }
}, { immediate: true });

const handleAmountInput = (e) => {
    localPayment.value.amount = toEnglish(e.target.value).replace(/[^\d.]/g, '');
};

const submit = () => {
    if (!localPayment.value.amount || parseFloat(localPayment.value.amount) <= 0) return;
    if (!localPayment.value.payment_method) return;
    if (parseFloat(localPayment.value.amount) > maxAmount.value) {
        return;
    }
    
    emit('saved', { ...localPayment.value });
    emit('close');
};
</script>
