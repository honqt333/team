<template>
    <CustomerPortalLayout
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.portal_quotes.quote_code', { code: quote.code })"
        :subtitle="quote.vehicle_label || ''"
        show-logout
        show-bottom-nav
        current-route="public.portal.quotes.index"
        :language-base-path="`/${tenant.slug}/portal/quote/${quote.id}`"
    >
        <!-- Flash feedback -->
        <div
            v-if="flashSuccess"
            class="mb-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 p-4 text-sm font-bold text-emerald-800 dark:text-emerald-200 flex items-center gap-3"
        >
            <svg
                class="w-5 h-5 text-emerald-600 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                />
            </svg>
            <span>{{ flashSuccess }}</span>
        </div>
        <div
            v-if="flashError"
            class="mb-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 p-4 text-sm font-bold text-rose-800 dark:text-rose-200"
        >
            {{ flashError }}
        </div>

        <div class="mb-4 flex items-center justify-between gap-3 print:hidden">
            <a
                :href="`/${tenant.slug}/portal/quotes`"
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
                {{ t('customer_portal.portal_quotes.back_to_list') }}
            </a>
            <button
                type="button"
                @click="printPage"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
                {{ t('customer_portal.portal_quotes.print_btn') }}
            </button>
        </div>

        <!-- Decision banner -->
        <section
            v-if="quote.can_be_actioned"
            class="mb-6 rounded-2xl p-5 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/40 dark:to-orange-950/30 border border-amber-200/80 dark:border-amber-800/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden"
        >
            <div class="flex items-start gap-3.5">
                <div
                    class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
                <div>
                    <h2
                        class="font-extrabold text-sm sm:text-base text-amber-950 dark:text-amber-100"
                    >
                        {{ t('customer_portal.portal_quotes.action_banner_title') }}
                    </h2>
                    <p class="text-xs text-amber-800/90 dark:text-amber-200/80 mt-0.5">
                        {{ t('customer_portal.portal_quotes.action_banner_body') }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <button
                    type="button"
                    @click="openApproveModal"
                    class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold transition shadow-sm"
                >
                    {{ t('customer_portal.portal_quotes.approve_btn') }}
                </button>
                <button
                    type="button"
                    @click="openRejectModal"
                    class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-800/60 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-xs font-bold transition"
                >
                    {{ t('customer_portal.portal_quotes.reject_btn') }}
                </button>
            </div>
        </section>

        <!-- Rejection reason (already rejected) -->
        <div
            v-if="quote.status === 'rejected' && quote.rejection_reason"
            class="mb-6 rounded-2xl p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 text-xs font-semibold text-rose-800 dark:text-rose-200"
        >
            {{
                t('customer_portal.portal_quotes.rejection_reason_box', {
                    reason: quote.rejection_reason,
                })
            }}
        </div>

        <!-- Header card -->
        <section
            class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-sm mb-6"
        >
            <div
                class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800"
            >
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span
                            class="text-lg font-mono font-extrabold text-slate-900 dark:text-white"
                            dir="ltr"
                        >
                            {{ quote.code }}
                        </span>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
                            :class="quoteStatusClass(quote)"
                        >
                            {{ statusLabel }}
                        </span>
                    </div>
                    <p
                        v-if="quote.center_name"
                        class="text-xs text-slate-500 dark:text-slate-400 mt-1"
                    >
                        {{ quote.center_name }}
                        <span v-if="quote.center_phone" dir="ltr">· {{ quote.center_phone }}</span>
                    </p>
                    <p v-if="quote.center_address" class="text-xs text-slate-400 mt-0.5">
                        {{ quote.center_address }}
                    </p>
                </div>
                <SaudiPlateDisplay
                    v-if="quote.vehicle_plate"
                    :plate-number="quote.vehicle_plate"
                    size="sm"
                />
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
                <div>
                    <p class="text-slate-400">
                        {{ t('customer_portal.portal_quotes.created_at') }}
                    </p>
                    <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                        {{ formatDate(quote.created_at) || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-slate-400">{{ t('customer_portal.booking.review_vehicle') }}</p>
                    <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                        {{ quote.vehicle_label || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-slate-400">{{ t('customer_portal.portal_quotes.odometer') }}</p>
                    <p class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                        <span v-if="quote.odometer" dir="ltr">
                            {{
                                t('customer_portal.portal_work_orders.odometer_unit', {
                                    km: formatMoney(quote.odometer).replace('.00', ''),
                                })
                            }}
                        </span>
                        <span v-else>—</span>
                    </p>
                </div>
                <div>
                    <p class="text-slate-400">
                        {{ t('customer_portal.portal_quotes.total_incl_tax') }}
                    </p>
                    <p
                        class="font-extrabold mt-0.5"
                        :style="{ color: 'var(--tenant-primary, #2563eb)' }"
                        dir="ltr"
                    >
                        {{ formatMoney(quote.total_incl_tax) }} {{ quote.currency_code }}
                    </p>
                </div>
            </div>
            <p
                v-if="quote.valid_until"
                class="mt-4 text-xs font-semibold"
                :class="quote.is_expired ? 'text-rose-600' : 'text-slate-500 dark:text-slate-400'"
            >
                {{
                    t('customer_portal.portal_quotes.valid_until', {
                        date: formatDate(quote.valid_until),
                    })
                }}
            </p>
        </section>

        <!-- Complaint & assessment -->
        <section
            v-if="quote.customer_complaint || quote.initial_assessment"
            class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm mb-6"
        >
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-3">
                {{ t('customer_portal.portal_quotes.complaint_title') }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <p
                    v-if="quote.customer_complaint"
                    class="rounded-xl bg-slate-50 dark:bg-slate-800/50 p-3.5 text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line"
                >
                    {{ quote.customer_complaint }}
                </p>
                <p
                    v-if="quote.initial_assessment"
                    class="rounded-xl bg-blue-50/60 dark:bg-blue-950/20 p-3.5 text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line"
                >
                    {{ quote.initial_assessment }}
                </p>
            </div>
        </section>

        <!-- Services -->
        <section
            v-if="quote.lines?.length"
            class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-sm mb-6"
        >
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-4">
                {{ t('customer_portal.portal_quotes.services_section') }}
            </h3>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                <div
                    v-for="line in quote.lines"
                    :key="line.id"
                    class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ line.title }}
                        </p>
                        <p
                            v-if="line.description && line.description !== line.title"
                            class="text-xs text-slate-500 mt-0.5"
                        >
                            {{ line.description }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ t('customer_portal.portal_quotes.qty') }}:
                            <span dir="ltr">{{ line.qty }}</span>
                            · {{ t('customer_portal.portal_quotes.unit_price') }}:
                            <span dir="ltr">{{ formatMoney(line.unit_price) }}</span>
                        </p>
                    </div>
                    <p
                        class="text-sm font-extrabold text-slate-900 dark:text-white shrink-0"
                        dir="ltr"
                    >
                        {{ formatMoney(line.line_total) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Parts -->
        <section
            v-if="quote.parts?.length"
            class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-sm mb-6"
        >
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white mb-4">
                {{ t('customer_portal.portal_quotes.parts_section') }}
            </h3>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                <div
                    v-for="part in quote.parts"
                    :key="part.id"
                    class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ part.name }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span v-if="part.part_number" class="font-mono" dir="ltr">
                                {{ part.part_number }} ·
                            </span>
                            {{ t('customer_portal.portal_quotes.qty') }}:
                            <span dir="ltr">{{ part.qty }}</span>
                            · {{ t('customer_portal.portal_quotes.unit_price') }}:
                            <span dir="ltr">{{ formatMoney(part.unit_price) }}</span>
                        </p>
                    </div>
                    <p
                        class="text-sm font-extrabold text-slate-900 dark:text-white shrink-0"
                        dir="ltr"
                    >
                        {{ formatMoney(part.total) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Totals -->
        <section
            class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 sm:p-6 shadow-sm mb-6"
        >
            <div class="max-w-xs ms-auto space-y-2.5 text-sm">
                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                    <span>{{ t('customer_portal.portal_quotes.subtotal') }}</span>
                    <span class="font-semibold text-slate-900 dark:text-white" dir="ltr">
                        {{ formatMoney(quote.subtotal) }}
                    </span>
                </div>
                <div
                    v-if="quote.total_discount > 0"
                    class="flex items-center justify-between text-emerald-600 dark:text-emerald-400"
                >
                    <span>{{ t('customer_portal.portal_quotes.discount') }}</span>
                    <span class="font-semibold" dir="ltr">
                        -{{ formatMoney(quote.total_discount) }}
                    </span>
                </div>
                <div
                    v-if="quote.tax_enabled_snapshot"
                    class="flex items-center justify-between text-slate-500 dark:text-slate-400"
                >
                    <span>
                        {{ t('customer_portal.portal_quotes.tax_amount') }} (
                        <span dir="ltr">{{ quote.tax_rate_snapshot }}%</span>
                        )
                    </span>
                    <span class="font-semibold text-slate-900 dark:text-white" dir="ltr">
                        {{ formatMoney(quote.total_tax) }}
                    </span>
                </div>
                <div
                    class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-base font-extrabold text-slate-900 dark:text-white"
                >
                    <span>{{ t('customer_portal.portal_quotes.total_incl_tax') }}</span>
                    <span dir="ltr">
                        {{ formatMoney(quote.total_incl_tax) }} {{ quote.currency_code }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Notes & terms -->
        <section
            v-if="quote.notes || quote.quote_terms"
            class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm mb-6"
        >
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                {{ t('customer_portal.portal_quotes.notes_title') }}
            </h3>
            <p
                v-if="quote.notes"
                class="text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line mb-2"
            >
                {{ quote.notes }}
            </p>
            <p
                v-if="quote.quote_terms"
                class="text-xs text-slate-500 dark:text-slate-400 whitespace-pre-line leading-relaxed"
            >
                {{ quote.quote_terms }}
            </p>
        </section>

        <!-- Signature (if signed) -->
        <section
            v-if="quote.signature_url"
            class="rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-900/50 p-5 shadow-sm flex flex-wrap items-center justify-between gap-4"
        >
            <p class="text-xs font-extrabold text-emerald-800 dark:text-emerald-200">
                {{ t('customer_portal.portal_quotes.signed_badge') }}
            </p>
            <img
                :src="quote.signature_url"
                :alt="t('customer_portal.portal_quotes.signed_badge')"
                class="h-16 bg-white rounded-xl border border-emerald-200 p-1.5 object-contain"
            />
        </section>

        <!-- Approve modal -->
        <div
            v-if="showApproveModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="showApproveModal = false"
        >
            <div
                role="dialog"
                aria-modal="true"
                class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl"
            >
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                    {{ t('customer_portal.portal_quotes.approve_modal_title') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-4">
                    {{ t('customer_portal.portal_quotes.approve_modal_subtitle') }}
                </p>
                <SignaturePad
                    v-model="signatureData"
                    :label="t('customer_portal.portal_quotes.signature_label')"
                />
                <p v-if="approveError" class="mt-2 text-xs font-semibold text-rose-600">
                    {{ approveError }}
                </p>
                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="showApproveModal = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        :disabled="submitting"
                        @click="submitApprove"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold disabled:opacity-50 transition"
                    >
                        {{ t('customer_portal.portal_quotes.approve_confirm_btn') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject modal -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="showRejectModal = false"
        >
            <div
                role="dialog"
                aria-modal="true"
                class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-2xl"
            >
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white mb-4">
                    {{ t('customer_portal.portal_quotes.reject_modal_title') }}
                </h3>
                <label
                    for="rejection_reason"
                    class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5"
                >
                    {{ t('customer_portal.portal_quotes.reject_reason_label') }}
                    <span class="text-rose-500">*</span>
                </label>
                <textarea
                    id="rejection_reason"
                    v-model="rejectionReason"
                    rows="4"
                    maxlength="1000"
                    :placeholder="t('customer_portal.portal_quotes.reject_reason_placeholder')"
                    class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm"
                ></textarea>
                <p v-if="rejectError" class="mt-1.5 text-xs font-semibold text-rose-600">
                    {{ rejectError }}
                </p>
                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button
                        type="button"
                        @click="showRejectModal = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        :disabled="submitting || !rejectionReason.trim()"
                        @click="submitReject"
                        class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold disabled:opacity-50 transition"
                    >
                        {{ t('customer_portal.portal_quotes.reject_confirm_btn') }}
                    </button>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import SignaturePad from '@/Components/Common/SignaturePad.vue';
import { usePortalFormat, quoteStatusClass } from '@/Composables/usePortalFormat';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    quote: { type: Object, required: true },
});

const { t, formatDate, formatMoney } = usePortalFormat(props);
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const statusLabel = computed(() => {
    const q = props.quote;
    const approved = q.status === 'approved' || q.status === 'converted';
    const status = q.is_expired && !approved && q.status !== 'rejected' ? 'expired' : q.status;
    return t(`customer_portal.portal_quotes.status_${status}`);
});

const submitting = ref(false);
const showApproveModal = ref(false);
const signatureData = ref(null);
const approveError = ref(null);
const showRejectModal = ref(false);
const rejectionReason = ref('');
const rejectError = ref(null);

const baseUrl = computed(() => `/${props.tenant.slug}/portal/quote/${props.quote.id}`);

function openApproveModal() {
    signatureData.value = null;
    approveError.value = null;
    showApproveModal.value = true;
}

function openRejectModal() {
    rejectionReason.value = '';
    rejectError.value = null;
    showRejectModal.value = true;
}

function submitApprove() {
    submitting.value = true;
    approveError.value = null;
    router.post(
        `${baseUrl.value}/approve`,
        { signature: signatureData.value || null },
        {
            preserveScroll: true,
            onSuccess: () => (showApproveModal.value = false),
            onError: (errors) => (approveError.value = Object.values(errors || {})[0] || null),
            onFinish: () => (submitting.value = false),
        }
    );
}

function submitReject() {
    if (!rejectionReason.value.trim()) return;
    submitting.value = true;
    rejectError.value = null;
    router.post(
        `${baseUrl.value}/reject`,
        { rejection_reason: rejectionReason.value.trim() },
        {
            preserveScroll: true,
            onSuccess: () => (showRejectModal.value = false),
            onError: (errors) =>
                (rejectError.value =
                    errors?.rejection_reason || Object.values(errors || {})[0] || null),
            onFinish: () => (submitting.value = false),
        }
    );
}

function printPage() {
    window.print();
}
</script>
