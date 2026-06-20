<template>
    <BaseModal :show="show" @close="$emit('close')" size="4xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                {{ $t('invoices.create') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Customer Selection -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('common.customer') }} <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect v-model="form.customer_id" :options="customerOptions"
                            option-label="label" option-value="value"
                            :placeholder="$t('common.select_customer')" class="w-full" />
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('invoices.issue_date') }} <span class="text-red-500">*</span>
                        </label>
                        <CustomDatePicker v-model="form.issue_date" :placeholder="$t('invoices.issue_date')" />
                    </div>
                </div>

                <!-- Parts Section -->
                <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>📦</span> {{ $t('inventory.parts.title') }}
                        </h3>
                        <button type="button" @click="showPartModal = true" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ $t('common.add') }}
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full min-w-[900px] text-sm text-center text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">{{ $t('inventory.parts.part_number') }}</th>
                                    <th class="px-4 py-3">{{ $t('inventory.parts.unit_price') }}</th>
                                    <th class="px-4 py-3">{{ $t('quotes.service_modal.discount') }}</th>
                                    <th class="px-4 py-3">{{ $t('work_orders.item.qty') }}</th>
                                    <th class="px-4 py-3">{{ $t('common.amount') }}</th>
                                    <th class="px-4 py-3">{{ $t('common.vat') }}</th>
                                    <th class="px-4 py-3 w-16">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.items" :key="item.tempId" class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-3 font-medium">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ item.name }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ item.part_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono">{{ formatCurrency(item.unit_price) }}</td>
                                    <td class="px-4 py-3 font-mono text-red-500">{{ formatCurrency(getItemDiscountToShow(item)) }}</td>
                                    <td class="px-4 py-3 font-mono">{{ item.qty }}</td>
                                    <td class="px-4 py-3 font-mono font-bold">{{ formatCurrency(getItemTotalToShow(item)) }}</td>
                                    <td class="px-4 py-3 font-mono">{{ formatCurrency(item.tax_amount) }}</td>
                                    <td class="px-4 py-3">
                                        <Tooltip :content="$t('common.delete')">
                                            <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </Tooltip>
                                    </td>
                                </tr>
                                <tr v-if="form.items.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                        {{ $t('common.no_data') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payments Section -->
                <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>💳</span> {{ $t('payments.title') }}
                        </h3>
                        <button type="button" @click="showPaymentModal = true" :disabled="remainingBalance <= 0" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ $t('common.add') }}
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                        <table class="w-full min-w-[800px] text-sm text-center text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">{{ $t('payments.form.type') }}</th>
                                    <th class="px-4 py-3">{{ $t('payments.form.date') }}</th>
                                    <th class="px-4 py-3">{{ $t('payments.form.notes') }}</th>
                                    <th class="px-4 py-3">{{ $t('payments.form.amount') }}</th>
                                    <th class="px-4 py-3 w-16">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(payment, index) in form.payments" :key="payment.id" class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-3 font-medium">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">{{ $t(`payments.methods.${payment.payment_method}`) }}</td>
                                    <td class="px-4 py-3 font-mono">{{ payment.payment_date }}</td>
                                    <td class="px-4 py-3">{{ payment.notes }}</td>
                                    <td class="px-4 py-3 font-mono font-bold" :class="payment.type === 'refund' ? 'text-red-500' : 'text-emerald-600'">
                                        {{ payment.type === 'refund' ? '-' : '' }}{{ formatCurrency(payment.amount) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Tooltip :content="$t('common.delete')">
                                            <button type="button" @click="removePayment(index)" class="text-red-500 hover:text-red-700 p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </Tooltip>
                                    </td>
                                </tr>
                                <tr v-if="form.payments.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        {{ $t('common.no_data') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary & Notes -->
                <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.notes') }}
                            </label>
                            <textarea v-model="form.notes" rows="4" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('invoices.subtotal') }}</span>
                                <span class="font-mono font-bold dark:text-white">{{ formatCurrency(totals.subtotal) }}</span>
                            </div>
                            <div v-if="settings.vat_enabled && totals.tax > 0" class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('invoices.tax') }} ({{ settings.parts_vat_rate ?? settings.vat_rate ?? 15 }}%)</span>
                                <span class="font-mono font-bold text-red-500">{{ formatCurrency(totals.tax) }}</span>
                            </div>
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                                <span class="font-bold text-gray-900 dark:text-white">{{ $t('invoices.grand_total') }}</span>
                                <span class="font-mono font-black text-lg text-emerald-600 dark:text-emerald-400">{{ formatCurrency(totals.grandTotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ $t('invoices.total_paid') }}</span>
                                <span class="font-mono">{{ formatCurrency(totals.paid) }}</span>
                            </div>
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center text-lg">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $t('invoices.balance') }}</span>
                                <span class="font-mono font-black" :class="remainingBalance > 0 ? 'text-red-500' : 'text-emerald-600'">
                                    {{ formatCurrency(remainingBalance) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button type="button" @click="submitForm" :disabled="form.processing || !form.customer_id || form.items.length === 0" class="px-6 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 disabled:opacity-50 transition-all font-bold">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>

    <!-- Modals -->
    <CustomerFormModal 
        v-if="showCustomerModal" 
        :show="showCustomerModal" 
        @close="showCustomerModal = false" 
        @saved="handleCustomerSaved" 
    />

    <WorkOrderPartModal 
        v-if="showPartModal" 
        :show="showPartModal" 
        :workOrder="mockWorkOrderForTax"
        :warehouses="warehouses"
        :units="units"
        :pendingMode="true"
        :showServiceSelect="false"
        @close="showPartModal = false" 
        @saved="handlePartSaved" 
    />

    <PaymentFormModal 
        v-if="showPaymentModal" 
        :show="showPaymentModal" 
        :workOrderId="0"
        :balance="remainingBalance"
        :totalPaid="totals.paid"
        :pendingMode="true"
        @close="showPaymentModal = false" 
        @saved="handlePaymentSaved" 
    />
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import WorkOrderPartModal from '@/Components/WorkOrders/WorkOrderPartModal.vue';
import PaymentFormModal from '@/Components/WorkOrders/PaymentFormModal.vue';
import Tooltip from '@/Components/Tooltip.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
    customers: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { formatCurrency } = useNumberFormat();
const page = usePage();
const toast = useToast();

// State
const showCustomerModal = ref(false);
const showPartModal = ref(false);
const showPaymentModal = ref(false);

const localCustomers = ref([...props.customers]);

// Ensure settings exist and fallback to sane defaults
const settings = computed(() => page.props.tenant?.tax_settings || {});

// Create a mock workOrder to pass to the PartModal so it uses correct tax settings
const mockWorkOrderForTax = computed(() => {
    return {
        id: 0,
        tax_enabled_snapshot: settings.value.vat_enabled ?? true,
        pricing_mode_snapshot: settings.value.parts_inclusive ? 'inclusive' : 'exclusive',
        tax_rate_snapshot: Number(settings.value.parts_vat_rate ?? settings.value.vat_rate ?? 15),
        status: 'draft'
    };
});

const form = useForm({
    customer_id: null,
    issue_date: new Date().toISOString().split('T')[0],
    notes: '',
    items: [],
    payments: []
});

const customerOptions = computed(() => {
    return localCustomers.value.map(c => ({
        value: c.id,
        label: `${c.name} - ${c.phone}`
    }));
});

const showExclusive = computed(() => {
    const isInclusive = settings.value.parts_inclusive ?? false;
    return !isInclusive || (settings.value.show_amount_before_vat ?? true);
});

const getItemDiscountToShow = (item) => {
    const disc = parseFloat(item.discount) || 0;
    const isInclusive = settings.value.parts_inclusive ?? false;
    const rate = Number(settings.value.parts_vat_rate ?? settings.value.vat_rate ?? 15) / 100;
    
    if (isInclusive && showExclusive.value) {
        return disc / (1 + rate);
    }
    return disc;
};

const getItemTotalToShow = (item) => {
    const isInclusive = settings.value.parts_inclusive ?? false;
    if (isInclusive && showExclusive.value) {
        const itemGrandTotal = parseFloat(item.grand_total) || 0;
        const itemTax = parseFloat(item.tax_amount) || 0;
        return itemGrandTotal - itemTax;
    }
    return parseFloat(item.total) || 0;
};

// Totals Calculation
const totals = computed(() => {
    let subtotal = 0;
    let tax = 0;
    let grandTotal = 0;
    let paid = 0;

    form.items.forEach(item => {
        subtotal += getItemTotalToShow(item);
        tax += parseFloat(item.tax_amount) || 0;
        grandTotal += parseFloat(item.grand_total) || 0;
    });

    form.payments.forEach(payment => {
        const amt = parseFloat(payment.amount) || 0;
        paid += payment.type === 'refund' ? -amt : amt;
    });

    return { subtotal, tax, grandTotal, paid };
});

const remainingBalance = computed(() => {
    return Math.max(0, totals.value.grandTotal - totals.value.paid);
});

// Handlers
function handleCustomerSaved(customer) {
    if (customer) {
        // Add to local list and select
        localCustomers.value.push(customer);
        form.customer_id = customer.id;
    }
    showCustomerModal.value = false;
}

function handlePartSaved(partData, opts) {
    form.items.push(partData);
    if (opts?.close !== false) {
        showPartModal.value = false;
    }
}

function handlePaymentSaved(paymentData) {
    form.payments.push(paymentData);
    showPaymentModal.value = false;
}

function removeItem(index) {
    form.items.splice(index, 1);
}

function removePayment(index) {
    form.payments.splice(index, 1);
}

function submitForm() {
    form.post(route('app.purchasing.sales-invoices.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('invoices.sales.created_successfully') || 'تم إنشاء الفاتورة بنجاح');
            emit('saved');
            emit('close');
        }
    });
}
</script>
