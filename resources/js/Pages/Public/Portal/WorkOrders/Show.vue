<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.portal_work_orders.code_title', { code: workOrder.code })"
        :subtitle="workOrder.vehicle_label || workOrder.center_name || ''"
        show-logout
        show-back-to-site
        show-bottom-nav
        :current-route="'public.portal.workOrders.show'"
        :language-base-path="`/${tenant.slug}/portal/work-order/${workOrder.id}`"
    >
        <!-- Back Link -->
        <div class="mb-4">
            <a
                :href="`/${tenant.slug}/portal/work-orders`"
                class="inline-flex items-center gap-1.5 text-xs font-extrabold text-slate-600 hover:text-slate-900"
            >
                <span>←</span>
                <span>{{ t('customer_portal.portal_work_orders.back_to_list') }}</span>
            </a>
        </div>

        <!-- Hero Tracker Card -->
        <section class="rounded-3xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-black text-slate-900 sm:text-2xl">
                            {{
                                t('customer_portal.portal_work_orders.code_title', {
                                    code: workOrder.code,
                                })
                            }}
                        </h2>
                        <StatusBadge
                            :status="workOrder.status"
                            :label="workOrderStatusLabel(workOrder.status)"
                        />
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        {{
                            [workOrder.vehicle_label, workOrder.center_name]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>
                </div>

                <SaudiPlateDisplay
                    v-if="workOrder.vehicle_plate"
                    :plate-number="workOrder.vehicle_plate"
                    size="md"
                />
            </div>

            <!-- Hold Alert Banner -->
            <div
                v-if="workOrder.status === 'on_hold'"
                class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                <div class="font-extrabold">
                    {{ t('customer_portal.portal_work_orders.hold_alert_title') }}
                </div>
                <p v-if="workOrder.hold_reason" class="mt-1 text-xs text-amber-800">
                    {{ workOrder.hold_reason }}
                </p>
            </div>

            <!-- 4-Step Visual Progress Stepper -->
            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between text-xs">
                    <span class="font-extrabold text-slate-700">
                        {{ t('customer_portal.portal_work_orders.progress_title') }}
                    </span>
                    <span class="font-black text-[color:var(--tenant-primary,#2563eb)]">
                        {{ workOrder.progress || 20 }}%
                    </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{
                            width: `${workOrder.progress || 20}%`,
                            backgroundColor: 'var(--tenant-primary, #2563eb)',
                        }"
                    />
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div
                        v-for="(stage, idx) in stages"
                        :key="stage.key"
                        class="rounded-2xl border p-3 text-xs transition"
                        :class="
                            (workOrder.stage_index || 1) >= idx + 1
                                ? 'border-[color:var(--tenant-primary,#2563eb)]/30 bg-blue-50/50 text-slate-900'
                                : 'border-slate-200/70 bg-slate-50/60 text-slate-400'
                        "
                    >
                        <div class="text-[10px] font-bold uppercase opacity-70">0{{ idx + 1 }}</div>
                        <div class="mt-0.5 font-extrabold">{{ stage.label }}</div>
                    </div>
                </div>
            </div>

            <!-- Metadata Grid -->
            <div
                class="mt-6 grid grid-cols-2 gap-3 border-t border-slate-100 pt-5 text-xs sm:grid-cols-4"
            >
                <div>
                    <span class="text-slate-400">
                        {{ t('customer_portal.portal_work_orders.branch_label') }}
                    </span>
                    <p class="mt-0.5 font-bold text-slate-800">
                        {{ workOrder.center_name || '—' }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-400">
                        {{ t('customer_portal.portal_work_orders.entry_date') }}
                    </span>
                    <p class="mt-0.5 font-bold text-slate-800">
                        {{ formatDateOnly(workOrder.entry_date || workOrder.opened_at) || '—' }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-400">
                        {{ t('customer_portal.portal_work_orders.expected_delivery') }}
                    </span>
                    <p class="mt-0.5 font-bold text-slate-800">
                        {{ formatDateOnly(workOrder.expected_end_date) || '—' }}
                    </p>
                </div>
                <div v-if="workOrder.odometer">
                    <span class="text-slate-400">Odometer</span>
                    <p class="mt-0.5 font-bold text-slate-800" dir="ltr">
                        {{
                            t('customer_portal.portal_work_orders.odometer_unit', {
                                km: Number(workOrder.odometer).toLocaleString(),
                            })
                        }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Unified Linked Invoice Bar -->
        <div
            v-if="workOrder.linked_invoice"
            class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-xs"
        >
            <div class="flex items-center gap-2">
                <span class="text-xs font-extrabold text-slate-600">
                    {{ t('invoices.title') }}
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <LinkedInvoiceBadge
                    :href="`/${tenant.slug}/portal/invoice/${workOrder.linked_invoice.id}`"
                    :invoice="workOrder.linked_invoice"
                />
            </div>
        </div>

        <!-- Complaint & Technical Assessment -->
        <section
            v-if="workOrder.customer_complaint || workOrder.initial_assessment"
            class="mt-6 grid gap-4 sm:grid-cols-2"
        >
            <div
                v-if="workOrder.customer_complaint"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">
                    {{ t('customer_portal.portal_work_orders.complaint_label') }}
                </h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-800">
                    {{ workOrder.customer_complaint }}
                </p>
            </div>

            <div
                v-if="workOrder.initial_assessment"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">
                    {{ t('customer_portal.portal_work_orders.assessment_label') }}
                </h3>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-800">
                    {{ workOrder.initial_assessment }}
                </p>
            </div>
        </section>

        <!-- Services & Parts Breakdown -->
        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Services Section -->
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-sm font-extrabold text-slate-900">
                        {{ t('customer_portal.portal_work_orders.services_section') }}
                    </h3>

                    <div
                        v-if="!workOrder.items || workOrder.items.length === 0"
                        class="mt-3 text-sm text-slate-500"
                    >
                        —
                    </div>

                    <div v-else class="mt-4 divide-y divide-slate-100">
                        <div
                            v-for="item in workOrder.items"
                            :key="item.id"
                            class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">
                                        {{ item.title }}
                                    </span>
                                    <span
                                        v-if="item.is_warranty"
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800"
                                    >
                                        {{ t('customer_portal.portal_invoices.warranty_badge') }}
                                    </span>
                                </div>
                                <div class="mt-1">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                        :class="itemStatusClass(item.status)"
                                    >
                                        {{ itemStatusLabel(item.status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-end text-sm font-extrabold text-slate-900" dir="ltr">
                                {{ formatMoney(item.total) }}
                                <span class="text-xs font-normal text-slate-500">
                                    {{ workOrder.currency_code }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Spare Parts Section -->
                <section
                    v-if="workOrder.parts && workOrder.parts.length > 0"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
                >
                    <h3 class="text-sm font-extrabold text-slate-900">
                        {{ t('customer_portal.portal_work_orders.parts_section') }}
                    </h3>

                    <div class="mt-4 divide-y divide-slate-100">
                        <div
                            v-for="part in workOrder.parts"
                            :key="part.id"
                            class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-slate-900">
                                    {{ part.name }}
                                </div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    <span v-if="part.part_number" dir="ltr">
                                        #{{ part.part_number }} ·
                                    </span>
                                    <span>
                                        {{ t('customer_portal.portal_invoices.qty') }}:
                                        {{ part.qty }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-end text-sm font-extrabold text-slate-900" dir="ltr">
                                {{ formatMoney(part.total) }}
                                <span class="text-xs font-normal text-slate-500">
                                    {{ workOrder.currency_code }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Financial Summary & Linked Invoice -->
            <div>
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h3 class="text-sm font-extrabold text-slate-900">
                        {{ t('customer_portal.portal_work_orders.financial_summary') }}
                    </h3>

                    <div class="mt-4 space-y-2.5 text-sm">
                        <div
                            v-if="workOrder.tax_enabled"
                            class="flex items-center justify-between text-slate-600"
                        >
                            <span>
                                {{ t('customer_portal.portal_invoices.subtotal_excl_tax') }}
                            </span>
                            <span class="font-bold" dir="ltr">
                                {{ formatMoney(workOrder.total_excl_tax) }}
                                {{ workOrder.currency_code }}
                            </span>
                        </div>
                        <div
                            v-if="workOrder.tax_enabled"
                            class="flex items-center justify-between text-slate-600"
                        >
                            <span>{{ t('customer_portal.portal_quotes.tax_amount') }}</span>
                            <span class="font-bold" dir="ltr">
                                {{ formatMoney(workOrder.total_tax) }}
                                {{ workOrder.currency_code }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between border-t border-slate-100 pt-3 text-base font-black text-slate-900"
                        >
                            <span>{{ t('customer_portal.portal_quotes.total_incl_tax') }}</span>
                            <span dir="ltr">
                                {{ formatMoney(workOrder.total_incl_tax) }}
                                {{ workOrder.currency_code }}
                            </span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import StatusBadge from '@/Components/CustomerPortal/StatusBadge.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import LinkedInvoiceBadge from '@/Components/Common/LinkedInvoiceBadge.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    workOrder: { type: Object, required: true },
});

const { t: tRaw, locale: i18nLocale } = useI18n();
const page = usePage();

const t = (path, params) => {
    const v = tRaw(path, params || {});
    return v === path ? path.split('.').pop() : v;
};

const activeLocale = computed(() => {
    const current = props.locale?.current || page.props.locale || i18nLocale.value || 'ar';
    return current === 'en' ? 'en-US' : 'ar-SA';
});

const stages = computed(() => [
    { key: 1, label: t('customer_portal.portal_work_orders.stage_1') },
    { key: 2, label: t('customer_portal.portal_work_orders.stage_2') },
    { key: 3, label: t('customer_portal.portal_work_orders.stage_3') },
    { key: 4, label: t('customer_portal.portal_work_orders.stage_4') },
]);

function workOrderStatusLabel(status) {
    const key = `customer_portal.work_order_statuses.${status}`;
    const v = tRaw(key);
    return v === key ? status : v;
}

function itemStatusLabel(status) {
    const key = `customer_portal.portal_work_orders.item_status_${status}`;
    const v = tRaw(key);
    return v === key ? status : v;
}

function itemStatusClass(status) {
    switch (status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-800';
        case 'in_progress':
        case 'ready_for_qc':
            return 'bg-blue-100 text-blue-800';
        case 'on_hold':
            return 'bg-amber-100 text-amber-800';
        default:
            return 'bg-slate-100 text-slate-700';
    }
}

function formatMoney(value) {
    const n = Number(value || 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDateOnly(iso) {
    if (!iso) return '';
    try {
        const d = new Date(iso);
        return d.toLocaleDateString(activeLocale.value, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return iso;
    }
}
</script>
