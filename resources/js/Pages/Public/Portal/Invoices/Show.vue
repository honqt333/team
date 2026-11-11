<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="
            t('customer_portal.portal_invoices.invoice_number', { number: invoice.invoice_number })
        "
        :subtitle="invoice.vehicle_label || invoice.center_name || ''"
        show-logout
        show-bottom-nav
        current-route="public.portal.invoices.index"
        :language-base-path="`/${tenant.slug}/portal/invoice/${invoice.id}`"
    >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
            <a
                :href="`/${tenant.slug}/portal/invoices`"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white"
            >
                <svg
                    class="w-4 h-4 rtl:rotate-180"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                {{ t('customer_portal.portal_invoices.back_to_list') }}
            </a>
            <div class="flex items-center gap-2">
                <a
                    v-if="invoice.work_order_id"
                    :href="`/${tenant.slug}/portal/work-order/${invoice.work_order_id}`"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                >
                    {{
                        t('customer_portal.portal_invoices.view_work_order', {
                            code: invoice.work_order_code || invoice.work_order_id,
                        })
                    }}
                </a>
                <button
                    type="button"
                    @click="printInvoice"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white text-xs font-extrabold shadow-sm transition"
                    :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    {{ t('customer_portal.portal_invoices.print_btn') }}
                </button>
            </div>
        </div>

        <article
            class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6 print:shadow-none print:border-0 print:p-0"
        >
            <!-- Document header -->
            <div
                class="flex flex-wrap items-start justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800"
            >
                <div class="space-y-1">
                    <p
                        class="text-xs font-extrabold uppercase tracking-wider"
                        :style="{ color: 'var(--tenant-primary, #2563eb)' }"
                    >
                        {{ documentLabel }}
                    </p>
                    <h2
                        class="text-xl sm:text-2xl font-mono font-extrabold text-slate-900 dark:text-white"
                        dir="ltr"
                    >
                        {{ invoice.invoice_number }}
                    </h2>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <span
                            v-if="!isNote"
                            class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold"
                            :class="paymentStatusClass(invoice.payment_status)"
                        >
                            {{
                                t(
                                    `customer_portal.portal_invoices.status_${invoice.payment_status}`
                                )
                            }}
                        </span>
                        <span
                            v-if="invoice.issue_date"
                            class="text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ t('customer_portal.portal_invoices.issue_date') }}:
                            {{ formatDate(invoice.issue_date) }}
                        </span>
                    </div>
                </div>

                <div class="text-sm sm:text-end space-y-0.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        {{ t('customer_portal.portal_invoices.seller_info') }}
                    </p>
                    <p class="font-extrabold text-slate-900 dark:text-white">
                        {{ invoice.seller_name }}
                    </p>
                    <p
                        v-if="invoice.tax_enabled && invoice.seller_vat"
                        class="text-xs text-slate-500"
                    >
                        {{
                            t('customer_portal.portal_invoices.vat_number', {
                                vat: invoice.seller_vat,
                            })
                        }}
                    </p>
                    <p v-if="invoice.center_address" class="text-xs text-slate-400">
                        {{ invoice.center_address }}
                    </p>
                    <p v-if="invoice.center_phone" class="text-xs text-slate-400" dir="ltr">
                        {{ invoice.center_phone }}
                    </p>
                </div>
            </div>

            <!-- Customer & vehicle -->
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-6 border-b border-slate-100 dark:border-slate-800"
            >
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        {{ t('customer_portal.portal_invoices.customer_info') }}
                    </p>
                    <p class="text-sm font-extrabold text-slate-900 dark:text-white mt-1">
                        {{ invoice.customer_name || '—' }}
                    </p>
                    <p
                        v-if="invoice.tax_enabled && invoice.customer_vat"
                        class="text-xs text-slate-500 mt-0.5"
                    >
                        {{
                            t('customer_portal.portal_invoices.vat_number', {
                                vat: invoice.customer_vat,
                            })
                        }}
                    </p>
                </div>
                <div
                    v-if="invoice.vehicle_label || invoice.vehicle_plate"
                    class="flex items-center justify-between sm:justify-end gap-4"
                >
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200 sm:text-end">
                        {{ invoice.vehicle_label }}
                    </p>
                    <SaudiPlateDisplay
                        v-if="invoice.vehicle_plate"
                        :plate-number="invoice.vehicle_plate"
                        size="xs"
                    />
                </div>
            </div>

            <!-- Lines -->
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
                    {{ t('customer_portal.portal_invoices.items_section') }}
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-200 dark:border-slate-800 text-xs text-slate-400"
                            >
                                <th class="py-2.5 text-start font-bold">
                                    {{ t('customer_portal.portal_invoices.description') }}
                                </th>
                                <th class="py-2.5 text-center font-bold">
                                    {{ t('customer_portal.portal_invoices.qty') }}
                                </th>
                                <th class="py-2.5 text-end font-bold">
                                    {{ t('customer_portal.portal_invoices.unit_price') }}
                                </th>
                                <th class="py-2.5 text-end font-bold">
                                    {{ t('customer_portal.portal_invoices.total') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="line in invoice.lines" :key="line.id">
                                <td class="py-3">
                                    <p class="font-semibold text-slate-900 dark:text-white">
                                        {{ line.description }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span
                                            class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400"
                                        >
                                            {{
                                                line.is_part
                                                    ? t(
                                                          'customer_portal.portal_invoices.part_badge'
                                                      )
                                                    : t(
                                                          'customer_portal.portal_invoices.service_badge'
                                                      )
                                            }}
                                        </span>
                                        <span
                                            v-if="line.is_warranty"
                                            class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
                                        >
                                            {{
                                                t('customer_portal.portal_invoices.warranty_badge')
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="py-3 text-center text-slate-600 dark:text-slate-300"
                                    dir="ltr"
                                >
                                    {{ line.qty }}
                                </td>
                                <td
                                    class="py-3 text-end text-slate-600 dark:text-slate-300"
                                    dir="ltr"
                                >
                                    {{ formatMoney(line.unit_price) }}
                                </td>
                                <td
                                    class="py-3 text-end font-extrabold text-slate-900 dark:text-white"
                                    dir="ltr"
                                >
                                    {{ formatMoney(line.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals & QR -->
            <div
                class="pt-5 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6"
            >
                <div
                    v-if="invoice.zatca_qr_svg"
                    class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-700"
                >
                    <img
                        :src="invoice.zatca_qr_svg"
                        :alt="t('customer_portal.portal_invoices.qr_title')"
                        class="w-28 h-28 rounded-lg bg-white p-1.5 border border-slate-200"
                    />
                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200 max-w-[150px]">
                        {{ t('customer_portal.portal_invoices.qr_title') }}
                    </p>
                </div>
                <div v-else />

                <div class="w-full sm:w-80 space-y-2 text-sm">
                    <div
                        class="flex items-center justify-between text-slate-500 dark:text-slate-400"
                    >
                        <span>{{ t('customer_portal.portal_invoices.subtotal_excl_tax') }}</span>
                        <span class="font-semibold text-slate-900 dark:text-white" dir="ltr">
                            {{ formatMoney(invoice.total_excl_tax) }}
                        </span>
                    </div>
                    <div
                        v-if="invoice.tax_enabled"
                        class="flex items-center justify-between text-slate-500 dark:text-slate-400"
                    >
                        <span>
                            {{
                                t('customer_portal.portal_invoices.tax_History', {
                                    rate: invoice.tax_rate,
                                })
                            }}
                        </span>
                        <span class="font-semibold text-slate-900 dark:text-white" dir="ltr">
                            {{ formatMoney(invoice.total_tax) }}
                        </span>
                    </div>
                    <div
                        class="pt-2.5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-base font-extrabold text-slate-900 dark:text-white"
                    >
                        <span>
                            {{
                                invoice.tax_enabled
                                    ? t('customer_portal.portal_invoices.total_incl_tax')
                                    : t('customer_portal.portal_invoices.total')
                            }}
                        </span>
                        <span dir="ltr">
                            {{ formatMoney(invoice.total_incl_tax) }} {{ invoice.currency_code }}
                        </span>
                    </div>
                    <template v-if="!isNote">
                        <div
                            class="flex items-center justify-between text-xs font-bold text-emerald-600 dark:text-emerald-400"
                        >
                            <span>{{ t('customer_portal.portal_invoices.amount_paid') }}</span>
                            <span dir="ltr">{{ formatMoney(invoice.total_paid) }}</span>
                        </div>
                        <div
                            v-if="invoice.balance > 0"
                            class="flex items-center justify-between text-xs font-extrabold text-rose-600 dark:text-rose-400"
                        >
                            <span>{{ t('customer_portal.portal_invoices.amount_due') }}</span>
                            <span dir="ltr">{{ formatMoney(invoice.balance) }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <p
                v-if="invoice.notes"
                class="pt-4 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 whitespace-pre-line"
            >
                {{ invoice.notes }}
            </p>
        </article>

        <!-- Payments history -->
        <section
            v-if="!isNote"
            class="mt-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-sm print:hidden"
        >
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
                {{ t('customer_portal.portal_invoices.payments_history') }}
            </h3>
            <p v-if="!invoice.payments?.length" class="text-xs text-slate-400">
                {{ t('customer_portal.portal_invoices.no_payments') }}
            </p>
            <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                <div
                    v-for="p in invoice.payments"
                    :key="p.id"
                    class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4 text-sm"
                >
                    <div>
                        <p class="font-bold text-slate-800 dark:text-slate-200">
                            {{ paymentMethodLabel(p.payment_method) }}
                            <span
                                v-if="p.reference"
                                class="text-xs font-mono text-slate-400 ms-1.5"
                                dir="ltr"
                            >
                                #{{ p.reference }}
                            </span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ formatDateTime(p.payment_date) }}
                        </p>
                    </div>
                    <p class="font-extrabold text-emerald-600 dark:text-emerald-400" dir="ltr">
                        {{ formatMoney(p.amount) }} {{ invoice.currency_code }}
                    </p>
                </div>
            </div>
        </section>
    </CustomerPortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { usePortalFormat, paymentStatusClass } from '@/Composables/usePortalFormat';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    invoice: { type: Object, required: true },
});

const { t, formatDate, formatDateTime, formatMoney } = usePortalFormat(props);

const isNote = computed(
    () => props.invoice.type === 'credit_note' || props.invoice.type === 'debit_note'
);

// Tax invoice wording only when VAT was active on the invoice
// snapshot — non-VAT tenants must never see "tax invoice".
const documentLabel = computed(() => {
    if (isNote.value) return t(`customer_portal.portal_invoices.${props.invoice.type}`);
    return props.invoice.tax_enabled
        ? t('customer_portal.portal_invoices.tax_invoice')
        : t('customer_portal.portal_invoices.invoice');
});

const KNOWN_METHODS = [
    'apple_pay',
    'cash',
    'credit',
    'mada',
    'mastercard',
    'stc_pay',
    'tabby',
    'tamara',
    'transfer',
    'visa',
];
function paymentMethodLabel(method) {
    const key = KNOWN_METHODS.includes(method) ? method : 'other';
    return t(`customer_portal.portal_invoices.method_${key}`);
}

function printInvoice() {
    window.print();
}
</script>
