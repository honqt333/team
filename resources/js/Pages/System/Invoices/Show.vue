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
                :title="
                    (isAr ? $t('system.invoices.invoice_2') : 'Invoice') +
                    ' ' +
                    invoice.invoice_number
                "
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
                                {{ isAr ? $t('system.invoices.download_pdf') : 'Download PDF' }}
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
                                        ? isAr
                                            ? $t('common.sending')
                                            : 'Sending...'
                                        : isAr
                                          ? $t('system.invoices.send_by_email')
                                          : 'Send by email'
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
                            <span>{{ isAr ? $t('common.cancel') : 'Cancel' }}</span>
                        </button>
                    </div>

                    <!-- Primary Mark As Paid CTA -->
                    <button
                        v-if="invoice.status === 'pending'"
                        @click="showMarkPaidModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-emerald-500/25 active:scale-95"
                    >
                        <CheckIcon class="h-4 w-4" />
                        <span>
                            {{ isAr ? $t('system.invoices.mark_as_paid') : 'Mark as paid' }}
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
                    class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700"
                >
                    <h3
                        class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                    >
                        {{ isAr ? $t('system.invoices.pdf_preview') : 'PDF Preview' }}
                    </h3>
                    <a
                        :href="`/system/invoices/${invoice.id}/download`"
                        target="_blank"
                        class="text-xs font-semibold text-action-primary hover:text-selection-text"
                    >
                        {{ isAr ? $t('system.invoices.open_in_new_tab') : 'Open in new tab' }} ↗
                    </a>
                </div>
                <iframe v-if="pdfUrl" :src="pdfUrl" class="h-[700px] w-full border-0" />
                <div v-else class="flex h-64 items-center justify-center text-sm text-gray-400">
                    {{ isAr ? $t('system.invoices.pdf_not_available') : 'PDF not available' }}
                </div>
            </div>
        </div>

        <!-- Mark Paid Modal -->
        <Transition name="modal">
            <div v-if="showMarkPaidModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showMarkPaidModal = false" />
                    <div
                        class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800"
                    >
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                            {{
                                isAr
                                    ? $t('system.invoices.confirm_payment_manually')
                                    : 'Confirm payment manually'
                            }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{
                                        isAr
                                            ? $t('system.invoices.reference_number')
                                            : 'Reference number'
                                    }}
                                </label>
                                <input
                                    type="text"
                                    v-model="markPaidForm.reference"
                                    :placeholder="
                                        isAr
                                            ? $t('system.invoices.transfer_or_receipt_number')
                                            : 'Transfer or receipt number'
                                    "
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{ isAr ? $t('common.notes') : 'Notes' }}
                                </label>
                                <textarea
                                    v-model="markPaidForm.notes"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <button
                                @click="markAsPaid"
                                :disabled="markingPaid"
                                class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                            >
                                {{
                                    markingPaid
                                        ? isAr
                                            ? $t('system.invoices.confirming...')
                                            : 'Confirming...'
                                        : isAr
                                          ? $t('system.invoices.mark_as_paid')
                                          : 'Confirm payment'
                                }}
                            </button>
                            <button
                                @click="showMarkPaidModal = false"
                                class="rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                            >
                                {{ isAr ? $t('common.cancel') : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, h, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
    if (
        props.invoice.status === 'pending' &&
        props.invoice.due_at &&
        new Date(props.invoice.due_at) < new Date()
    ) {
        return 'overdue';
    }
    return props.invoice.status;
});

const sending = ref(false);
const showMarkPaidModal = ref(false);
const markingPaid = ref(false);
const markPaidForm = ref({ reference: '', notes: '' });

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

function cancelInvoice() {
    if (
        confirm(
            isAr.value
                ? t('system.invoices.are_you_sure_you_want_to_cancel_this_invoice')
                : 'Are you sure you want to cancel this invoice?'
        )
    ) {
        router.post(`/system/invoices/${props.invoice.id}/cancel`);
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
