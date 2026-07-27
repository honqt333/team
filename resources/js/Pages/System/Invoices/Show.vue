<template>
    <SystemLayout>
        <Head
            :title="isAr ? `فاتورة ${invoice.invoice_number}` : `Invoice ${invoice.invoice_number}`"
        />

        <div class="space-y-6">
            <!-- Header card: back link + invoice number + status badge -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <Link
                            href="/system/invoices"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700/40 dark:hover:text-white"
                        >
                            <ChevronIcon class="h-4 w-4" />
                        </Link>
                        <div class="min-w-0">
                            <p
                                class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                {{ isAr ? 'فاتورة' : 'Invoice' }}
                            </p>
                            <h1 class="truncate text-xl font-bold text-gray-900 dark:text-white">
                                {{ invoice.invoice_number }}
                            </h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="effectiveStatus" />
                    </div>
                </div>

                <!-- Action bar -->
                <div
                    class="flex flex-wrap items-center gap-2 border-t border-gray-200 bg-gray-50/50 px-6 py-3 dark:border-gray-700 dark:bg-gray-900/20"
                >
                    <a
                        :href="`/system/invoices/${invoice.id}/download`"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/40"
                    >
                        <DownloadIcon class="h-4 w-4" />
                        {{ isAr ? 'تحميل PDF' : 'Download PDF' }}
                    </a>

                    <button
                        @click="sendInvoice"
                        :disabled="sending"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <MailIcon class="h-4 w-4" />
                        {{
                            sending
                                ? isAr
                                    ? 'جاري الإرسال...'
                                    : 'Sending...'
                                : isAr
                                  ? 'إرسال بالإيميل'
                                  : 'Send by email'
                        }}
                    </button>

                    <button
                        v-if="invoice.status === 'pending'"
                        @click="showMarkPaidModal = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <CheckIcon class="h-4 w-4" />
                        {{ isAr ? 'تأكيد الدفع' : 'Mark as paid' }}
                    </button>

                    <button
                        v-if="invoice.status === 'pending'"
                        @click="cancelInvoice"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50 dark:border-rose-800 dark:bg-gray-800 dark:text-rose-400 dark:hover:bg-rose-900/20"
                    >
                        <XIcon class="h-4 w-4" />
                        {{ isAr ? 'إلغاء الفاتورة' : 'Cancel invoice' }}
                    </button>
                </div>
            </div>

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
                        {{ isAr ? 'معاينة الـ PDF' : 'PDF Preview' }}
                    </h3>
                    <a
                        :href="`/system/invoices/${invoice.id}/download`"
                        target="_blank"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                    >
                        {{ isAr ? 'فتح في تبويب جديد' : 'Open in new tab' }} ↗
                    </a>
                </div>
                <iframe v-if="pdfUrl" :src="pdfUrl" class="h-[700px] w-full border-0" />
                <div v-else class="flex h-64 items-center justify-center text-sm text-gray-400">
                    {{ isAr ? 'الـ PDF غير متاح' : 'PDF not available' }}
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
                            {{ isAr ? 'تأكيد الدفع يدوياً' : 'Confirm payment manually' }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{ isAr ? 'رقم المرجع' : 'Reference number' }}
                                </label>
                                <input
                                    type="text"
                                    v-model="markPaidForm.reference"
                                    :placeholder="
                                        isAr
                                            ? 'رقم الحوالة أو الإيصال'
                                            : 'Transfer or receipt number'
                                    "
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{ isAr ? 'ملاحظات' : 'Notes' }}
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
                                            ? 'جاري التأكيد...'
                                            : 'Confirming...'
                                        : isAr
                                          ? 'تأكيد الدفع'
                                          : 'Confirm payment'
                                }}
                            </button>
                            <button
                                @click="showMarkPaidModal = false"
                                class="rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                            >
                                {{ isAr ? 'إلغاء' : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { computed, h, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
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
                ? 'هل أنت متأكد من إلغاء هذه الفاتورة؟'
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
