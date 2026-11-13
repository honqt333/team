<template>
    <SystemLayout>
        <Head
            :title="
                isAr
                    ? $t('system.invoices.invoice', { number: invoice.invoice_number })
                    : `Invoice ${invoice.invoice_number}`
            "
        />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.invoices.invoice_2') + ' ' + invoice.invoice_number"
                :subtitle="invoice.tenant?.trade_name || invoice.tenant?.name || ''"
            >
                <template #back>
                    <Link
                        href="/system/invoices"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700/60 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors"
                        :title="$t('system.invoices.title')"
                    >
                        <ChevronIcon class="w-5 h-5 rtl:rotate-180" />
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <StatusBadge :status="effectiveStatus" />

                    <!-- Actions Group -->
                    <div
                        class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner"
                    >
                        <a
                            :href="`/system/invoices/${invoice.id}/download`"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-action-primary hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm"
                        >
                            <DownloadIcon class="h-4 w-4" />
                            <span>
                                {{ $t('system.invoices.download_pdf') }}
                            </span>
                        </a>

                        <button
                            @click="sendInvoice"
                            :disabled="sending"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-action-primary hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm disabled:opacity-50"
                        >
                            <MailIcon class="h-4 w-4" />
                            <span>
                                {{
                                    sending
                                        ? $t('common.sending')
                                        : $t('system.invoices.send_by_email')
                                }}
                            </span>
                        </button>

                        <button
                            v-if="invoice.status === 'pending'"
                            @click="cancelInvoice"
                            :disabled="cancelling"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-rose-600 dark:text-rose-400 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm disabled:opacity-50"
                        >
                            <XIcon class="h-4 w-4" />
                            <span>{{ $t('common.cancel') }}</span>
                        </button>
                    </div>

                    <!-- Primary Mark As Paid CTA -->
                    <button
                        v-if="invoice.status === 'pending'"
                        @click="openMarkPaidModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-emerald-500/25 active:scale-95"
                    >
                        <CheckIcon class="h-4 w-4" />
                        <span>
                            {{ $t('system.invoices.mark_as_paid') }}
                        </span>
                    </button>
                </template>
            </PageHeader>

            <!-- Main grid: 2/3 main + 1/3 sidebar -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main column -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Timeline -->
                    <InvoiceTimeline :invoice="invoice" />

                    <!-- Items table -->
                    <InvoiceItemsTable
                        :invoice="invoice"
                        :subscription="invoice.subscription"
                        :plan="invoice.subscription?.plan"
                    />

                    <!-- Totals -->
                    <InvoiceTotalsCard :invoice="invoice" currency="SAR" />

                    <!-- Payments History -->
                    <div
                        v-if="invoice.payments?.length"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                            <h3
                                class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('system.invoices.payments_history') }}
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-for="payment in invoice.payments"
                                :key="payment.id"
                                class="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5 text-sm"
                            >
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{
                                                formatPaymentMethod(
                                                    payment.payment_method || payment.gateway
                                                )
                                            }}
                                        </span>
                                        <StatusBadge :status="payment.status" />
                                    </div>
                                    <p
                                        v-if="payment.gateway_ref"
                                        class="font-mono text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $t('system.invoices.reference_number') }}:
                                        {{ payment.gateway_ref }}
                                    </p>
                                </div>
                                <div class="text-end">
                                    <p
                                        class="font-mono font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ formatCurrency(payment.amount) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDate(payment.paid_at || payment.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <InvoiceCustomerCard :tenant="invoice.tenant" />
                    <InvoiceSubscriptionCard
                        :subscription="invoice.subscription"
                        :plan="invoice.subscription?.plan"
                    />
                </div>
            </div>

            <!-- PDF preview (full width, at the bottom for max focus) -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-700"
                >
                    <h3
                        class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        {{ $t('system.invoices.pdf_preview') }}
                    </h3>
                    <div class="flex items-center gap-4">
                        <button
                            type="button"
                            @click="regeneratePdf"
                            :disabled="regenerating"
                            class="text-xs font-semibold text-gray-600 hover:text-action-primary disabled:opacity-50 dark:text-gray-300"
                        >
                            {{ $t('system.invoices.regenerate_pdf') }}
                        </button>
                        <a
                            :href="pdfUrl || `/system/invoices/${invoice.id}/download?inline=1`"
                            target="_blank"
                            class="text-xs font-semibold text-action-primary hover:text-selection-text"
                        >
                            {{ $t('system.invoices.open_in_new_tab') }} ↗
                        </a>
                    </div>
                </div>
                <iframe
                    v-if="pdfUrl"
                    :key="pdfUrl"
                    :src="pdfUrl"
                    class="h-[700px] w-full border-0"
                />
                <div v-else class="flex h-64 items-center justify-center text-sm text-gray-400">
                    {{ $t('system.invoices.pdf_not_available') }}
                </div>
            </div>
        </div>

        <!-- Mark Paid Modal -->
        <BaseModal
            :show="showMarkPaidModal"
            :title="$t('system.invoices.confirm_payment_manually')"
            size="sm"
            @close="showMarkPaidModal = false"
        >
            <div class="space-y-4">
                <div>
                    <SearchableSelect
                        v-model="markPaidForm.payment_method"
                        :options="paymentMethodOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('system.invoices.payment_method')"
                    />
                </div>
                <AppInput
                    v-model.number="markPaidForm.paid_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :label="$t('system.invoices.paid_amount')"
                    dir="ltr"
                />
                <AppInput
                    v-model="markPaidForm.reference"
                    type="text"
                    :label="$t('system.invoices.reference_number')"
                    :placeholder="$t('system.invoices.transfer_or_receipt_number')"
                />
                <AppTextarea v-model="markPaidForm.notes" :rows="2" :label="$t('common.notes')" />
            </div>
            <template #footer>
                <div class="flex gap-3 w-full">
                    <button
                        @click="markAsPaid"
                        :disabled="markingPaid"
                        class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                    >
                        {{
                            markingPaid
                                ? $t('system.invoices.confirming')
                                : $t('system.invoices.mark_as_paid')
                        }}
                    </button>
                    <button
                        @click="showMarkPaidModal = false"
                        class="rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                </div>
            </template>
        </BaseModal>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, h, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormatters } from '@/Composables/useFormatters';
import StatusBadge from '@/Components/Billing/StatusBadge.vue';
import InvoiceTimeline from '@/Components/Billing/InvoiceTimeline.vue';
import InvoiceItemsTable from '@/Components/Billing/InvoiceItemsTable.vue';
import InvoiceTotalsCard from '@/Components/Billing/InvoiceTotalsCard.vue';
import InvoiceCustomerCard from '@/Components/Billing/InvoiceCustomerCard.vue';
import InvoiceSubscriptionCard from '@/Components/Billing/InvoiceSubscriptionCard.vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    pdfUrl: { type: String, default: null },
});

// Defensive locale read — same pattern as Analytics/Show.vue.
const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

// Compute the effective status — we still show 'overdue' for
// pending invoices past their due date, so the admin gets a
// visual signal even before dunning kicks in.
const effectiveStatus = computed(() => {
    if (props.invoice.status === 'pending' && props.invoice.due_at) {
        const startOfToday = new Date();
        startOfToday.setHours(0, 0, 0, 0);
        if (new Date(props.invoice.due_at) < startOfToday) {
            return 'overdue';
        }
    }
    return props.invoice.status;
});

const { confirm } = useConfirm();
const { formatDate, formatCurrency } = useFormatters();
const sending = ref(false);
const cancelling = ref(false);
const regenerating = ref(false);
const showMarkPaidModal = ref(false);
const markingPaid = ref(false);
const markPaidForm = ref({
    payment_method: 'cash',
    paid_amount: Number(props.invoice?.total || 0),
    reference: '',
    notes: '',
});

const openMarkPaidModal = () => {
    markPaidForm.value = {
        payment_method: 'cash',
        paid_amount: Number(props.invoice?.total || 0),
        reference: '',
        notes: '',
    };
    showMarkPaidModal.value = true;
};

const formatPaymentMethod = (method) => {
    const key = `system.invoices.methods.${method}`;
    const translated = t(key);
    return translated === key ? method || t('common.na') : translated;
};

const paymentMethodOptions = computed(() => [
    { value: 'cash', label: t('system.invoices.methods.cash') },
    { value: 'pos', label: t('system.invoices.methods.pos') },
    { value: 'bank_transfer', label: t('system.invoices.methods.bank_transfer') },
    { value: 'manual', label: t('system.invoices.methods.manual') },
]);

// Inline icons (single helper). Each component returns a fresh
// VNode so Vue can key them safely inside v-for if we ever need
// to.
const makeIcon = (path, opts = {}) =>
    h(
        'svg',
        {
            xmlns: 'http://www.w3.org/2000/svg',
            fill: 'none',
            viewBox: '0 0 24 24',
            strokeWidth: opts.strokeWidth || 1.8,
            stroke: 'currentColor',
        },
        [
            h('path', {
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
                d: path,
            }),
        ]
    );

const ChevronIcon = () => makeIcon('M8.25 4.5l7.5 7.5-7.5 7.5', { strokeWidth: 2 });
const DownloadIcon = () =>
    makeIcon(
        'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3'
    );
const MailIcon = () =>
    makeIcon(
        'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'
    );
const CheckIcon = () =>
    makeIcon('M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', { strokeWidth: 2 });
const XIcon = () => makeIcon('M6 18L18 6M6 6l12 12', { strokeWidth: 2 });

function sendInvoice() {
    sending.value = true;
    router.post(
        `/system/invoices/${props.invoice.id}/send`,
        {},
        { onFinish: () => (sending.value = false) }
    );
}

function markAsPaid() {
    markingPaid.value = true;
    router.post(`/system/invoices/${props.invoice.id}/mark-paid`, markPaidForm.value, {
        onSuccess: () => (showMarkPaidModal.value = false),
        onFinish: () => (markingPaid.value = false),
    });
}

function regeneratePdf() {
    regenerating.value = true;
    router.post(
        `/system/invoices/${props.invoice.id}/regenerate-pdf`,
        {},
        { onFinish: () => (regenerating.value = false) }
    );
}

async function cancelInvoice() {
    const ok = await confirm({
        title: t('system.invoices.cancel_invoice'),
        message: t('system.invoices.are_you_sure_you_want_to_cancel_this_invoice'),
        confirmText: t('system.invoices.cancel_invoice'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        cancelling.value = true;
        router.post(
            `/system/invoices/${props.invoice.id}/cancel`,
            {},
            { onFinish: () => (cancelling.value = false) }
        );
    }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
