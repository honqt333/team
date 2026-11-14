<template>
    <BaseModal :show="show" @close="$emit('close')" size="md" z-index-class="z-[60]">
        <template #title>
            <div class="flex items-center gap-2">
                <span class="text-2xl">💰</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">
                    {{
                        isEditing
                            ? $t('payments.edit_payment')
                            : localPayment.type === 'bad_debt'
                              ? $t('payments.types.bad_debt')
                              : localPayment.type === 'refund'
                                ? $t('payments.types.refund')
                                : $t('payments.add_payment')
                    }}
                </span>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Operation Type Selector (Only if allowed) -->
            <div v-if="allowBadDebt" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('payments.form.type') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="localPayment.type = 'payment'"
                        :class="[
                            'px-3 py-1.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                            localPayment.type === 'payment'
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400'
                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        ]"
                    >
                        {{ $t('payments.types.payment') }}
                    </button>
                    <button
                        type="button"
                        @click="localPayment.type = 'bad_debt'"
                        :class="[
                            'px-3 py-1.5 rounded-xl border text-sm font-semibold transition-all cursor-pointer',
                            localPayment.type === 'bad_debt'
                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'
                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800',
                        ]"
                    >
                        {{ $t('payments.types.bad_debt') }}
                    </button>
                </div>
            </div>

            <!-- Transaction Type (Readonly) -->
            <div v-else-if="localPayment.type === 'refund'" class="mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.type') }}
                </label>
                <div
                    class="w-full px-4 py-2.5 rounded-xl border bg-red-50 text-red-700 border-red-200 dark:bg-red-950/30 dark:border-red-900/40 dark:text-red-400 font-bold flex items-center justify-between"
                >
                    <span>{{ $t('payments.types.refund') }}</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"
                        ></path>
                    </svg>
                </div>
            </div>

            <!-- Payment Method -->
            <div v-if="localPayment.type !== 'bad_debt'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.method') }}
                    <span class="text-red-500">*</span>
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
                    {{ $t('payments.form.amount') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <AppInput
                        type="text"
                        inputmode="decimal"
                        dir="ltr"
                        v-model="localPayment.amount"
                        @input="handleAmountInput"
                        class="pr-20"
                        :error="
                            parseFloat(localPayment.amount) > maxAmount
                                ? $t('payments.amount_exceeds_balance')
                                : ''
                        "
                        :placeholder="$t('payments.form.amount_placeholder')"
                    />
                    <div
                        class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400 text-sm font-medium"
                    >
                        {{ activeCurrencySymbol }}
                    </div>
                </div>
            </div>

            <!-- Payment Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.date') }}
                    <span class="text-red-500">*</span>
                </label>
                <CustomDatePicker
                    v-model="localPayment.payment_date"
                    :placeholder="$t('payments.form.date')"
                    class="w-full"
                />
            </div>

            <!-- Reference -->
            <div v-if="localPayment.type !== 'bad_debt'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.reference') }}
                </label>
                <AppInput
                    type="text"
                    v-model="localPayment.reference"
                    dir="ltr"
                    class="text-left font-mono"
                    :placeholder="$t('payments.form.reference_placeholder')"
                />
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.notes') }}
                </label>
                <AppTextarea
                    v-model="localPayment.notes"
                    rows="2"
                    class="resize-none"
                    :placeholder="$t('payments.form.notes_placeholder')"
                />
            </div>
        </div>

        <template #footer>
            <AppButton variant="secondary" @click="$emit('close')">
                {{ $t('common.cancel') }}
            </AppButton>
            <AppButton
                variant="action"
                @click="submit"
                :disabled="
                    parseFloat(localPayment.amount) > maxAmount ||
                    !localPayment.amount ||
                    parseFloat(localPayment.amount) <= 0
                "
            >
                {{
                    isEditing
                        ? $t('common.save')
                        : localPayment.type === 'bad_debt'
                          ? $t('payments.types.bad_debt')
                          : $t('payments.add_payment')
                }}
            </AppButton>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    payment: Object,
    balance: { type: Number, default: 0 },
    defaultType: { type: String, default: 'payment' },
    allowBadDebt: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { toEnglish, formatCurrency, activeCurrencySymbol } = useNumberFormat();

const isEditing = computed(() => !!props.payment);

const localPayment = ref({
    payment_method: 'cash',
    type: 'payment',
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

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            if (props.payment) {
                localPayment.value = { ...props.payment };
            } else {
                localPayment.value = {
                    payment_method: 'cash',
                    type: props.defaultType,
                    amount: props.balance > 0 ? props.balance : '',
                    payment_date: new Date().toISOString().split('T')[0],
                    reference: '',
                    notes: '',
                };
            }
        }
    },
    { immediate: true }
);

const handleAmountInput = (e) => {
    let cleanVal = toEnglish(e.target.value).replace(/[^\d.]/g, '');
    if (parseFloat(cleanVal) > maxAmount.value) {
        cleanVal = maxAmount.value.toString();
    }
    localPayment.value.amount = cleanVal;
};

const submit = () => {
    if (!localPayment.value.amount || parseFloat(localPayment.value.amount) <= 0) return;
    if (!localPayment.value.payment_method && localPayment.value.type !== 'bad_debt') return;
    if (parseFloat(localPayment.value.amount) > maxAmount.value) {
        return;
    }

    // Default payment_method to cash if bad_debt is chosen to satisfy backend requirements
    if (localPayment.value.type === 'bad_debt') {
        localPayment.value.payment_method = 'cash';
    }

    emit('saved', { ...localPayment.value });
    emit('close');
};
</script>
