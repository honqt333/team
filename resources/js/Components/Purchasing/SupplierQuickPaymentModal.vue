<template>
    <BaseModal
        :show="show"
        @close="$emit('close')"
        size="lg"
        z-index-class="z-[60]"
        :overflow-visible="true"
    >
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/25"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-white">
                        {{ $t('purchasing.suppliers.record_payment_title') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ supplier?.name }} ({{ $t('purchasing.suppliers.balance') }}:
                        <span
                            class="font-mono font-bold"
                            :class="balance > 0 ? 'text-red-600' : 'text-emerald-600'"
                        >
                            {{ formatCurrency(balance) }}
                        </span>
                        )
                    </p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Allocation Type Radio -->
            <div
                class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 space-y-2"
            >
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('purchasing.suppliers.payment_allocation') }}
                    <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                        type="button"
                        @click="allocationType = 'fifo'"
                        class="flex items-center gap-3 p-2.5 rounded-xl border cursor-pointer transition-all text-start"
                        :class="
                            allocationType === 'fifo'
                                ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-500 text-emerald-900 dark:text-emerald-200'
                                : 'border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800'
                        "
                    >
                        <span
                            class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                            :class="
                                allocationType === 'fifo'
                                    ? 'border-emerald-600 bg-emerald-600'
                                    : 'border-gray-300 dark:border-gray-600'
                            "
                        >
                            <span
                                v-if="allocationType === 'fifo'"
                                class="w-1.5 h-1.5 rounded-full bg-white"
                            ></span>
                        </span>
                        <div>
                            <p class="text-xs font-bold">
                                {{ $t('purchasing.suppliers.auto_allocate') }}
                            </p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ $t('purchasing.suppliers.auto_allocate_hint') }}
                            </p>
                        </div>
                    </button>

                    <button
                        type="button"
                        @click="allocationType = 'specific'"
                        class="flex items-center gap-3 p-2.5 rounded-xl border cursor-pointer transition-all text-start"
                        :class="
                            allocationType === 'specific'
                                ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-500 text-emerald-900 dark:text-emerald-200'
                                : 'border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800'
                        "
                    >
                        <span
                            class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0"
                            :class="
                                allocationType === 'specific'
                                    ? 'border-emerald-600 bg-emerald-600'
                                    : 'border-gray-300 dark:border-gray-600'
                            "
                        >
                            <span
                                v-if="allocationType === 'specific'"
                                class="w-1.5 h-1.5 rounded-full bg-white"
                            ></span>
                        </span>
                        <div>
                            <p class="text-xs font-bold">
                                {{ $t('purchasing.suppliers.specific_invoice') }}
                            </p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ $t('purchasing.suppliers.specific_invoice_hint') }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Specific Invoice Selector -->
            <div v-if="allocationType === 'specific'" class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                    {{ $t('purchasing.suppliers.select_invoice') }}
                    <span class="text-red-500">*</span>
                </label>

                <!-- If supplier has no open invoices with remaining balance -->
                <div
                    v-if="invoiceOptions.length === 0"
                    class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200 flex items-center gap-2.5"
                >
                    <svg
                        class="w-4 h-4 flex-shrink-0 text-amber-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>
                        {{ $t('purchasing.suppliers.no_open_invoices') }}
                    </span>
                </div>

                <div v-else class="space-y-2">
                    <SearchableSelect
                        v-model="form.purchase_invoice_id"
                        :options="invoiceOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('common.select')"
                        @change="onInvoiceSelected"
                    />

                    <!-- Selected Invoice Summary Badge -->
                    <div
                        v-if="selectedInvoice"
                        class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs"
                    >
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $t('purchasing.invoices.total') }}:
                            <strong class="font-mono text-gray-900 dark:text-white">
                                {{ formatCurrency(selectedInvoice.total) }}
                            </strong>
                        </span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">
                            {{ $t('purchasing.invoices.remaining') }}:
                            <strong class="font-mono">
                                {{ formatCurrency(selectedInvoice.balance) }}
                            </strong>
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Amount -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            {{ $t('payments.form.amount') }} ({{ activeCurrencySymbol }})
                            <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                            {{ $t('payments.form.max') }}:
                            <strong class="text-gray-900 dark:text-white font-bold">
                                {{ formatCurrency(maxAllowedAmount) }}
                            </strong>
                        </span>
                    </div>
                    <AppInput
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="maxAllowedAmount"
                        required
                        class="font-mono font-black"
                        :error="
                            isOverAmount
                                ? $t('purchasing.payment_exceeds_max', {
                                      formatCurrency_maxAllowedAmount_:
                                          formatCurrency(maxAllowedAmount),
                                  })
                                : ''
                        "
                        placeholder="0.00"
                    />
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('payments.form.method') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.payment_method"
                        :options="paymentMethodOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('common.select')"
                    />
                </div>

                <!-- Payment Date -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('payments.form.date') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <CustomDatePicker
                        v-model="form.payment_date"
                        :placeholder="$t('payments.form.date')"
                        :required="true"
                    />
                </div>

                <!-- Reference / Cheque No / Transfer No -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('payments.form.reference') }}
                    </label>
                    <AppInput
                        v-model="form.reference"
                        type="text"
                        class="font-mono"
                        placeholder="REF-1234..."
                    />
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('payments.form.notes') }}
                </label>
                <AppTextarea
                    v-model="form.notes"
                    rows="2"
                    class="resize-none"
                    :placeholder="$t('payments.form.notes_placeholder')"
                />
            </div>

            <!-- Modal Footer -->
            <div
                class="pt-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-end gap-3"
            >
                <AppButton variant="secondary" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    variant="action"
                    :loading="submitting"
                    :disabled="
                        submitting ||
                        !form.amount ||
                        parseFloat(form.amount) <= 0 ||
                        isOverAmount ||
                        maxAllowedAmount <= 0 ||
                        (allocationType === 'specific' && !form.purchase_invoice_id)
                    "
                >
                    {{
                        submitting ? $t('common.saving') : $t('purchasing.suppliers.record_payment')
                    }}
                </AppButton>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';

const props = defineProps({
    show: Boolean,
    supplier: {
        type: Object,
        required: true,
    },
    balance: {
        type: Number,
        default: 0,
    },
    purchaseInvoices: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const { t } = useI18n();
const { formatMoney: formatCurrency, activeCurrencySymbol } = useNumberFormat();

const allocationType = ref('fifo');
const submitting = ref(false);

const form = ref({
    amount: '',
    payment_method: 'transfer',
    payment_date: new Date().toISOString().split('T')[0],
    purchase_invoice_id: null,
    reference: '',
    notes: '',
});

// Only open invoices with an outstanding balance > 0.01
const availableInvoices = computed(() => {
    const list = props.purchaseInvoices || [];
    return list.filter(
        (inv) => Number(inv.balance) > 0.01 && inv.status !== 'cancelled' && inv.status !== 'draft'
    );
});

const invoiceOptions = computed(() => {
    return availableInvoices.value
        .map((inv) => {
            const invBalance = Number(inv.balance ?? 0);
            const invTotal = Number(inv.total ?? 0);
            const codeStr = inv.code || inv.invoice_number || '#' + inv.id;

            let dateStr = '';
            if (inv.issue_date) {
                dateStr = String(inv.issue_date).split('T')[0];
            }

            const label = t('purchasing.remaining_label');

            return {
                id: inv.id,
                value: inv.id,
                name: label,
                label: label,
                balance: invBalance,
                total: invTotal,
            };
        })
        .sort((a, b) => b.id - a.id);
});

const selectedInvoice = computed(() => {
    if (!form.value.purchase_invoice_id) return null;
    return availableInvoices.value.find((i) => i.id === form.value.purchase_invoice_id);
});

const maxAllowedAmount = computed(() => {
    if (allocationType.value === 'specific') {
        return selectedInvoice.value ? Number(selectedInvoice.value.balance || 0) : 0;
    }
    return Number(props.balance || 0);
});

const isOverAmount = computed(() => {
    const val = parseFloat(form.value.amount);
    return !isNaN(val) && val > maxAllowedAmount.value + 0.009;
});

const paymentMethodOptions = computed(() => [
    { value: 'transfer', label: t('payments.methods.transfer') },
    { value: 'cash', label: t('payments.methods.cash') },
    { value: 'mada', label: t('payments.methods.mada') },
    { value: 'visa', label: t('payments.methods.visa') },
    { value: 'mastercard', label: t('payments.methods.mastercard') },
    { value: 'other', label: t('payments.methods.other') },
]);

// formatCurrency provided by useNumberFormat()

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            allocationType.value = 'fifo';
            form.value = {
                amount: props.balance > 0 ? Number(props.balance).toFixed(2) : '',
                payment_method: 'transfer',
                payment_date: new Date().toISOString().split('T')[0],
                purchase_invoice_id: null,
                reference: '',
                notes: '',
            };
        }
    }
);

watch(allocationType, (newType) => {
    if (newType === 'fifo') {
        form.value.purchase_invoice_id = null;
        form.value.amount = props.balance > 0 ? Number(props.balance).toFixed(2) : '';
    } else {
        form.value.amount = selectedInvoice.value
            ? Number(selectedInvoice.value.balance).toFixed(2)
            : '';
    }
});

function onInvoiceSelected(val) {
    const selected = availableInvoices.value.find((i) => i.id === val);
    if (selected) {
        const rem = Number(selected.balance) || 0;
        form.value.amount = rem > 0 ? rem.toFixed(2) : '';
    } else {
        form.value.amount = '';
    }
}

function submit() {
    if (
        submitting.value ||
        !form.value.amount ||
        parseFloat(form.value.amount) <= 0 ||
        isOverAmount.value ||
        maxAllowedAmount.value <= 0
    )
        return;
    if (allocationType.value === 'specific' && !form.value.purchase_invoice_id) return;

    submitting.value = true;
    const payload = {
        amount: form.value.amount,
        payment_method: form.value.payment_method,
        payment_date: form.value.payment_date,
        purchase_invoice_id:
            allocationType.value === 'specific' ? form.value.purchase_invoice_id : null,
        reference: form.value.reference,
        notes: form.value.notes,
    };

    router.post(`/app/purchasing/suppliers/${props.supplier.id}/payments`, payload, {
        onSuccess: () => {
            emit('close');
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>
