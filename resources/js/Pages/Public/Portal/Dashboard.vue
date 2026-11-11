<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.dashboard.title')"
        :subtitle="t('customer_portal.dashboard.greeting_subtitle')"
        show-logout
        show-back-to-site
        show-bottom-nav
        :current-route="'public.portal.dashboard'"
        :language-base-path="`/${tenant.slug}/portal/dashboard`"
    >
        <!-- Top Greeting & Quick Booking CTA -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-black text-slate-900 sm:text-2xl">
                    {{ t('customer_portal.dashboard.greeting', { name: customer.name }) }}
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ t('customer_portal.dashboard.greeting_subtitle') }}
                </p>
            </div>
            <a
                v-if="canBook"
                :href="bookUrl"
                class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:opacity-95 sm:text-sm"
                :style="{ backgroundColor: 'var(--tenant-primary, #2563eb)' }"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                {{ t('customer_portal.dashboard.cta_book') }}
            </a>
        </div>

        <!-- Smart Action Required Banners -->
        <div
            v-if="summary.pending_quotes_count > 0 || summary.unpaid_balance > 0"
            class="mb-6 grid gap-3 sm:grid-cols-2"
        >
            <div
                v-if="summary.pending_quotes_count > 0"
                class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 shadow-sm"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-700"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </span>
                    <p class="text-xs font-bold text-amber-950 sm:text-sm">
                        {{
                            t('customer_portal.dashboard.alert_pending_quotes', {
                                count: summary.pending_quotes_count,
                            })
                        }}
                    </p>
                </div>
                <a
                    :href="`/${tenant.slug}/portal/quotes`"
                    class="shrink-0 rounded-xl bg-amber-600 px-3.5 py-2 text-xs font-extrabold text-white transition hover:bg-amber-700"
                >
                    {{ t('customer_portal.dashboard.alert_pending_quotes_cta') }}
                </a>
            </div>

            <div
                v-if="summary.unpaid_balance > 0"
                class="flex items-center justify-between gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 shadow-sm"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-700"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </span>
                    <p class="text-xs font-bold text-rose-950 sm:text-sm">
                        {{
                            t('customer_portal.dashboard.alert_unpaid_invoices', {
                                amount: formatMoney(summary.unpaid_balance),
                                currency: summary.currency || tenant.currency || 'SAR',
                            })
                        }}
                    </p>
                </div>
                <a
                    :href="`/${tenant.slug}/portal/invoices`"
                    class="shrink-0 rounded-xl bg-rose-600 px-3.5 py-2 text-xs font-extrabold text-white transition hover:bg-rose-700"
                >
                    {{ t('customer_portal.dashboard.alert_unpaid_invoices_cta') }}
                </a>
            </div>
        </div>

        <!-- Primary Hero Card -->
        <PrimaryCard
            v-if="primaryCard.kind === 'appointment'"
            kind="appointment"
            :kicker="t('customer_portal.dashboard.primary_kicker_appointment')"
            :title="primaryCard.title"
            :subtitle="t('customer_portal.dashboard.primary_subtitle_appointment')"
            :when-label="t('customer_portal.dashboard.cta_view')"
            :when-value="primaryCard.when"
            :branch-label="t('customer_portal.booking.review_branch')"
            :branch-value="primaryCard.branch"
            :cta-primary="{
                label: t('customer_portal.dashboard.cta_view'),
                href: primaryCard.viewHref,
            }"
            :cta-secondary="
                canManageAppointment
                    ? {
                          label: t('customer_portal.dashboard.cta_edit'),
                          href: primaryCard.editHref,
                      }
                    : null
            "
        />

        <PrimaryCard
            v-else-if="primaryCard.kind === 'work_order'"
            kind="work_order"
            :kicker="t('customer_portal.dashboard.primary_kicker_workorder')"
            :title="t('customer_portal.dashboard.primary_title_workorder')"
            :subtitle="t('customer_portal.dashboard.primary_subtitle_workorder')"
            :progress-label="t('customer_portal.dashboard.progress_label')"
            :progress-caption="
                t('customer_portal.dashboard.progress_caption', { status: primaryCard.statusLabel })
            "
            :progress-value="primaryCard.progress"
            :cta-primary="{
                label: t('customer_portal.dashboard.track_work_order'),
                href: primaryCard.trackHref,
            }"
            :cta-secondary="
                canBook
                    ? {
                          label: t('customer_portal.dashboard.cta_book'),
                          href: bookUrl,
                      }
                    : null
            "
        />

        <PrimaryCard
            v-else
            kind="empty"
            :kicker="t('customer_portal.dashboard.primary_kicker_empty')"
            :title="t('customer_portal.dashboard.primary_title_empty')"
            :subtitle="t('customer_portal.dashboard.primary_subtitle_empty')"
            :cta-primary="
                canBook
                    ? {
                          label: t('customer_portal.dashboard.cta_book'),
                          href: bookUrl,
                      }
                    : null
            "
        />

        <!-- 4 Quick Navigation KPI Cards -->
        <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <a
                :href="`/${tenant.slug}/portal/work-orders`"
                class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                    </span>
                    <span class="text-2xl font-black text-slate-900">
                        {{ summary.active_work_orders_count || 0 }}
                    </span>
                </div>
                <p class="mt-2.5 text-xs font-bold text-slate-600 group-hover:text-slate-900">
                    {{ t('customer_portal.dashboard.stat_active_jobs') }}
                </p>
            </a>

            <a
                :href="`/${tenant.slug}/portal/quotes`"
                class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </span>
                    <span class="text-2xl font-black text-slate-900">
                        {{ summary.pending_quotes_count || 0 }}
                    </span>
                </div>
                <p class="mt-2.5 text-xs font-bold text-slate-600 group-hover:text-slate-900">
                    {{ t('customer_portal.dashboard.stat_pending_quotes') }}
                </p>
            </a>

            <a
                :href="`/${tenant.slug}/portal/appointments`"
                class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </span>
                    <span class="text-2xl font-black text-slate-900">
                        {{
                            summary.upcoming_appointments_count ?? upcomingAppointments?.length ?? 0
                        }}
                    </span>
                </div>
                <p class="mt-2.5 text-xs font-bold text-slate-600 group-hover:text-slate-900">
                    {{ t('customer_portal.dashboard.stat_upcoming_appointments') }}
                </p>
            </a>

            <a
                :href="`/${tenant.slug}/portal/invoices`"
                class="group rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
            >
                <div class="flex items-center justify-between">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </span>
                    <span class="text-2xl font-black text-slate-900">
                        {{ summary.unpaid_invoices_count || 0 }}
                    </span>
                </div>
                <p class="mt-2.5 text-xs font-bold text-slate-600 group-hover:text-slate-900">
                    {{ t('customer_portal.dashboard.stat_unpaid_invoices') }}
                </p>
            </a>
        </section>

        <!-- Vehicles Fleet Showcase with SaudiPlateDisplay -->
        <section class="mt-8">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
                    {{ t('customer_portal.dashboard.section_vehicles') }}
                </h2>
                <a
                    :href="`/${tenant.slug}/portal/work-orders`"
                    class="text-xs font-bold text-[color:var(--tenant-primary,#2563eb)] hover:underline"
                >
                    {{ t('customer_portal.dashboard.view_all') }}
                </a>
            </div>

            <div
                v-if="!vehicles || vehicles.length === 0"
                class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500"
            >
                {{ t('customer_portal.dashboard.no_vehicles') }}
            </div>

            <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <a
                    v-for="v in vehicles"
                    :key="v.id"
                    :href="`/${tenant.slug}/portal/work-orders?vehicle=${v.id}`"
                    class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-md"
                >
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-extrabold text-slate-900">
                            {{ [v.make, v.model].filter(Boolean).join(' ') || v.plate_number }}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span v-if="v.year">{{ v.year }}</span>
                            <span v-if="v.color">· {{ v.color }}</span>
                            <span v-if="v.odometer" dir="ltr">
                                ·
                                {{
                                    t('customer_portal.portal_work_orders.odometer_unit', {
                                        km: Number(v.odometer).toLocaleString(),
                                    })
                                }}
                            </span>
                        </div>
                    </div>
                    <SaudiPlateDisplay :plate-number="v.plate_number" size="sm" />
                </a>
            </div>
        </section>

        <!-- Pending Quotes Section (if any) -->
        <section v-if="pendingQuotes && pendingQuotes.length > 0" class="mt-8">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
                    {{ t('customer_portal.dashboard.section_pending_quotes') }}
                </h2>
                <a
                    :href="`/${tenant.slug}/portal/quotes`"
                    class="text-xs font-bold text-[color:var(--tenant-primary,#2563eb)] hover:underline"
                >
                    {{ t('customer_portal.dashboard.view_all') }}
                </a>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <a
                    v-for="q in pendingQuotes"
                    :key="q.id"
                    :href="`/${tenant.slug}/portal/quote/${q.id}`"
                    class="flex items-center justify-between gap-4 rounded-2xl border border-amber-200 bg-white p-4 shadow-sm transition hover:border-amber-300 hover:shadow-md"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-extrabold text-slate-900">
                                {{
                                    t('customer_portal.portal_quotes.quote_code', { code: q.code })
                                }}
                            </span>
                            <span
                                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-800"
                            >
                                {{ t('customer_portal.portal_quotes.status_sent') }}
                            </span>
                        </div>
                        <p v-if="q.vehicle_label" class="mt-1 truncate text-xs text-slate-500">
                            {{ q.vehicle_label }}
                        </p>
                        <p v-if="q.valid_until" class="mt-0.5 text-xs text-slate-400">
                            {{
                                t('customer_portal.portal_quotes.valid_until', {
                                    date: formatDateOnly(q.valid_until),
                                })
                            }}
                        </p>
                    </div>
                    <div class="text-end shrink-0">
                        <div class="text-base font-black text-slate-900" dir="ltr">
                            {{ formatMoney(q.total_incl_tax) }}
                            <span class="text-xs font-semibold text-slate-500">
                                {{ q.currency_code }}
                            </span>
                        </div>
                        <span
                            class="mt-1 inline-block text-xs font-extrabold text-[color:var(--tenant-primary,#2563eb)]"
                        >
                            {{ t('customer_portal.dashboard.review_quote') }} ←
                        </span>
                    </div>
                </a>
            </div>
        </section>

        <!-- Recent Work Orders & Recent Invoices Grid -->
        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <!-- Recent Work Orders -->
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
                        {{ t('customer_portal.dashboard.section_recent') }}
                    </h2>
                    <a
                        :href="`/${tenant.slug}/portal/work-orders`"
                        class="text-xs font-bold text-[color:var(--tenant-primary,#2563eb)] hover:underline"
                    >
                        {{ t('customer_portal.dashboard.view_all') }}
                    </a>
                </div>

                <div
                    v-if="!recentWorkOrders || recentWorkOrders.length === 0"
                    class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500"
                >
                    {{ t('customer_portal.dashboard.no_recent') }}
                </div>

                <div v-else class="space-y-3">
                    <a
                        v-for="w in recentWorkOrders"
                        :key="w.id"
                        :href="`/${tenant.slug}/portal/work-order/${w.id}`"
                        class="block rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-extrabold text-slate-900">
                                    {{
                                        t('customer_portal.dashboard.work_order_code', {
                                            code: w.code,
                                        })
                                    }}
                                </div>
                                <div
                                    v-if="w.vehicle_label || w.center_name"
                                    class="mt-0.5 truncate text-xs text-slate-500"
                                >
                                    {{
                                        [w.vehicle_label, w.center_name].filter(Boolean).join(' · ')
                                    }}
                                </div>
                                <div v-if="w.expected_end_date" class="mt-1 text-xs text-slate-500">
                                    {{
                                        t('customer_portal.dashboard.expected_on', {
                                            date: formatDateOnly(w.expected_end_date),
                                        })
                                    }}
                                </div>
                            </div>
                            <StatusBadge
                                :status="w.status"
                                :label="workOrderStatusLabel(w.status)"
                            />
                        </div>

                        <div v-if="w.progress" class="mt-3">
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :style="{
                                        width: `${w.progress}%`,
                                        backgroundColor: 'var(--tenant-primary, #2563eb)',
                                    }"
                                />
                            </div>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Recent Invoices -->
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">
                        {{ t('customer_portal.dashboard.section_recent_invoices') }}
                    </h2>
                    <a
                        :href="`/${tenant.slug}/portal/invoices`"
                        class="text-xs font-bold text-[color:var(--tenant-primary,#2563eb)] hover:underline"
                    >
                        {{ t('customer_portal.dashboard.view_all') }}
                    </a>
                </div>

                <div
                    v-if="!recentInvoices || recentInvoices.length === 0"
                    class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500"
                >
                    {{ t('customer_portal.portal_invoices.empty') }}
                </div>

                <div v-else class="space-y-3">
                    <a
                        v-for="inv in recentInvoices"
                        :key="inv.id"
                        :href="`/${tenant.slug}/portal/invoice/${inv.id}`"
                        class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-md"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-extrabold text-slate-900">
                                    {{
                                        t('customer_portal.portal_invoices.invoice_number', {
                                            number: inv.invoice_number,
                                        })
                                    }}
                                </span>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-extrabold"
                                    :class="invoiceStatusClass(inv.payment_status)"
                                >
                                    {{ invoiceStatusLabel(inv.payment_status) }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{
                                    [inv.center_name, formatDateOnly(inv.issue_date)]
                                        .filter(Boolean)
                                        .join(' · ')
                                }}
                            </div>
                        </div>

                        <div class="text-end shrink-0" dir="ltr">
                            <div class="text-sm font-black text-slate-900">
                                {{ formatMoney(inv.total_incl_tax) }}
                                <span class="text-xs font-semibold text-slate-500">
                                    {{ inv.currency_code }}
                                </span>
                            </div>
                            <div
                                v-if="inv.balance > 0"
                                class="mt-0.5 text-[11px] font-bold text-rose-600"
                            >
                                {{ t('customer_portal.portal_invoices.balance_label') }}:
                                {{ formatMoney(inv.balance) }}
                            </div>
                        </div>
                    </a>
                </div>
            </section>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import PrimaryCard from '@/Components/CustomerPortal/PrimaryCard.vue';
import StatusBadge from '@/Components/CustomerPortal/StatusBadge.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    customer: { type: Object, required: true },
    summary: { type: Object, default: () => ({}) },
    vehicles: { type: Array, default: () => [] },
    upcomingAppointments: { type: Array, default: () => [] },
    recentWorkOrders: { type: Array, default: () => [] },
    pendingQuotes: { type: Array, default: () => [] },
    recentInvoices: { type: Array, default: () => [] },
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

const canBook = computed(() => {
    try {
        return typeof route === 'function' && route().has('public.portal.appointments.new');
    } catch {
        return true;
    }
});

const bookUrl = computed(() => {
    try {
        if (typeof route === 'function') {
            return route('public.portal.appointments.new', { tenant: props.tenant.slug });
        }
    } catch {
        // fall through
    }
    return `/${props.tenant.slug}/portal/appointments/new`;
});

const canManageAppointment = computed(() => {
    const first = (props.upcomingAppointments || [])[0];
    if (!first) return false;
    return first.status === 'scheduled' || first.status === 'confirmed';
});

function workOrderStatusLabel(status) {
    const key = `customer_portal.work_order_statuses.${status}`;
    const v = tRaw(key);
    return v === key ? status : v;
}

function invoiceStatusLabel(paymentStatus) {
    if (paymentStatus === 'paid') return t('customer_portal.portal_invoices.status_paid');
    if (paymentStatus === 'partial') return t('customer_portal.portal_invoices.status_partial');
    return t('customer_portal.portal_invoices.status_unpaid');
}

function invoiceStatusClass(paymentStatus) {
    if (paymentStatus === 'paid') return 'bg-emerald-100 text-emerald-800';
    if (paymentStatus === 'partial') return 'bg-amber-100 text-amber-800';
    return 'bg-rose-100 text-rose-800';
}

function workOrderProgress(status) {
    switch (status) {
        case 'open':
            return 25;
        case 'in_progress':
            return 60;
        case 'on_hold':
            return 45;
        case 'ready_for_qc':
            return 85;
        case 'done':
            return 100;
        default:
            return 15;
    }
}

function formatMoney(value) {
    const n = Number(value || 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatWhen(iso) {
    if (!iso) return '';
    try {
        const d = new Date(iso);
        return d.toLocaleString(activeLocale.value, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
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

function appointmentRelativeTitle(iso) {
    if (!iso) return t('customer_portal.dashboard.primary_title_appointment');
    try {
        const target = new Date(iso);
        const now = new Date();
        const sameDay =
            target.getFullYear() === now.getFullYear() &&
            target.getMonth() === now.getMonth() &&
            target.getDate() === now.getDate();
        if (sameDay) {
            return t('customer_portal.dashboard.primary_title_appointment_today');
        }
        const tomorrow = new Date(now);
        tomorrow.setDate(now.getDate() + 1);
        const isTomorrow =
            target.getFullYear() === tomorrow.getFullYear() &&
            target.getMonth() === tomorrow.getMonth() &&
            target.getDate() === tomorrow.getDate();
        if (isTomorrow) {
            return t('customer_portal.dashboard.primary_title_appointment_tomorrow');
        }
    } catch {
        // fall through
    }
    return t('customer_portal.dashboard.primary_title_appointment');
}

const primaryCard = computed(() => {
    const nextAppt = (props.upcomingAppointments || [])[0];
    if (nextAppt) {
        return {
            kind: 'appointment',
            title: appointmentRelativeTitle(nextAppt.scheduled_at),
            when: formatWhen(nextAppt.scheduled_at),
            branch: nextAppt.center_name || '—',
            viewHref: `/${props.tenant.slug}/portal/appointment/${nextAppt.id}`,
            editHref: `/${props.tenant.slug}/portal/appointment/${nextAppt.id}/edit`,
        };
    }

    const activeWo = (props.recentWorkOrders || []).find(
        (w) => w.status !== 'done' && w.status !== 'cancelled'
    );
    if (activeWo) {
        return {
            kind: 'work_order',
            statusLabel: workOrderStatusLabel(activeWo.status),
            progress: activeWo.progress || workOrderProgress(activeWo.status),
            trackHref: `/${props.tenant.slug}/portal/work-order/${activeWo.id}`,
        };
    }

    return { kind: 'empty' };
});
</script>
