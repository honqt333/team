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
            class="mb-4 rounded-2xl bg-success-soft border border-success/30 p-4 text-sm font-bold text-text-primary flex items-center gap-3"
        >
            <svg
                class="w-5 h-5 text-success shrink-0"
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
            class="mb-4 rounded-2xl bg-danger-soft border border-danger/30 p-4 text-sm font-bold text-danger"
        >
            {{ flashError }}
        </div>

        <div class="mb-4 flex items-center justify-between gap-3 print:hidden">
            <a
                :href="`/${tenant.slug}/portal/quotes`"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-text-secondary hover:text-text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus-ring rounded"
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
            <AppPrintButton
                size="sm"
                variant="icon"
                :label="t('customer_portal.portal_quotes.print_btn')"
                @click="printPage"
            />
        </div>

        <!-- Decision banner -->
        <section
            v-if="quote.can_be_actioned"
            class="mb-6 rounded-2xl p-5 bg-warning-soft border border-warning/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden"
        >
            <div class="flex items-start gap-3.5">
                <div
                    class="w-11 h-11 rounded-xl bg-warning text-text-inverse flex items-center justify-center shrink-0 shadow-sm"
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
                        class="font-extrabold text-sm sm:text-base text-text-primary"
                    >
                        {{ t('customer_portal.portal_quotes.action_banner_title') }}
                    </h2>
                    <p class="text-xs text-text-secondary mt-0.5">
                        {{ t('customer_portal.portal_quotes.action_banner_body') }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <AppButton
                    @click="openApproveModal"
                    variant="primary"
                    size="md"
                    class="bg-success hover:opacity-90 text-xs font-extrabold shadow-sm"
                >
                    {{ t('customer_portal.portal_quotes.approve_btn') }}
                </AppButton>
                <AppButton
                    @click="openRejectModal"
                    variant="secondary"
                    size="md"
                    class="border-danger/30 text-danger hover:bg-danger-soft text-xs"
                >
                    {{ t('customer_portal.portal_quotes.reject_btn') }}
                </AppButton>
            </div>
        </section>

        <!-- Rejection reason (already rejected) -->
        <div
            v-if="quote.status === 'rejected' && quote.rejection_reason"
            class="mb-6 rounded-2xl p-4 bg-danger-soft border border-danger/30 text-xs font-semibold text-danger"
        >
            {{
                t('customer_portal.portal_quotes.rejection_reason_box', {
                    reason: quote.rejection_reason,
                })
            }}
        </div>

        <!-- Header card -->
        <section
            class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-sm mb-6"
        >
            <div
                class="flex flex-wrap items-start justify-between gap-4 pb-5 border-b border-border"
            >
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span
                            class="text-lg font-mono font-extrabold text-text-primary"
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
                        class="text-xs text-text-secondary mt-1"
                    >
                        {{ quote.center_name }}
                        <span v-if="quote.center_phone" dir="ltr">· {{ quote.center_phone }}</span>
                    </p>
                    <p v-if="quote.center_address" class="text-xs text-text-secondary mt-0.5">
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
                    <p class="text-text-secondary">
                        {{ t('customer_portal.portal_quotes.created_at') }}
                    </p>
                    <p class="font-bold text-text-primary mt-0.5">
                        {{ formatDate(quote.created_at) || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-text-secondary">{{ t('customer_portal.booking.review_vehicle') }}</p>
                    <p class="font-bold text-text-primary mt-0.5">
                        {{ quote.vehicle_label || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-text-secondary">{{ t('customer_portal.portal_quotes.odometer') }}</p>
                    <p class="font-bold text-text-primary mt-0.5">
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
                    <p class="text-text-secondary">
                        {{ t('customer_portal.portal_quotes.total_incl_tax') }}
                    </p>
                    <p
                        class="font-extrabold mt-0.5 text-brand-primary"
                        dir="ltr"
                    >
                        {{ formatMoney(quote.total_incl_tax) }} {{ quote.currency_code }}
                    </p>
                </div>
            </div>
            <p
                v-if="quote.valid_until"
                class="mt-4 text-xs font-semibold"
                :class="quote.is_expired ? 'text-danger' : 'text-text-secondary'"
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
            class="rounded-2xl bg-surface border border-border p-5 shadow-sm mb-6"
        >
            <h3 class="text-sm font-extrabold text-text-primary mb-3">
                {{ t('customer_portal.portal_quotes.complaint_title') }}
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <p
                    v-if="quote.customer_complaint"
                    class="rounded-xl bg-surface-muted p-3.5 text-sm text-text-primary whitespace-pre-line"
                >
                    {{ quote.customer_complaint }}
                </p>
                <p
                    v-if="quote.initial_assessment"
                    class="rounded-xl bg-brand-primary-soft p-3.5 text-sm text-text-primary whitespace-pre-line"
                >
                    {{ quote.initial_assessment }}
                </p>
            </div>
        </section>

        <!-- Services -->
        <section
            v-if="quote.lines?.length"
            class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-sm mb-6"
        >
            <h3 class="text-sm font-extrabold text-text-primary mb-4">
                {{ t('customer_portal.portal_quotes.services_section') }}
            </h3>
            <div class="divide-y divide-border">
                <div
                    v-for="line in quote.lines"
                    :key="line.id"
                    class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-text-primary">
                            {{ line.title }}
                        </p>
                        <p
                            v-if="line.description && line.description !== line.title"
                            class="text-xs text-text-secondary mt-0.5"
                        >
                            {{ line.description }}
                        </p>
                        <p class="text-xs text-text-secondary mt-0.5">
                            {{ t('customer_portal.portal_quotes.qty') }}:
                            <span dir="ltr">{{ line.qty }}</span>
                            · {{ t('customer_portal.portal_quotes.unit_price') }}:
                            <span dir="ltr">{{ formatMoney(line.unit_price) }}</span>
                        </p>
                    </div>
                    <p
                        class="text-sm font-extrabold text-text-primary shrink-0"
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
            class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-sm mb-6"
        >
            <h3 class="text-sm font-extrabold text-text-primary mb-4">
                {{ t('customer_portal.portal_quotes.parts_section') }}
            </h3>
            <div class="divide-y divide-border">
                <div
                    v-for="part in quote.parts"
                    :key="part.id"
                    class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-text-primary">
                            {{ part.name }}
                        </p>
                        <p class="text-xs text-text-secondary mt-0.5">
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
                        class="text-sm font-extrabold text-text-primary shrink-0"
                        dir="ltr"
                    >
                        {{ formatMoney(part.total) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Totals -->
        <section
            class="rounded-2xl bg-surface border border-border p-5 sm:p-6 shadow-sm mb-6"
        >
            <div class="max-w-xs ms-auto space-y-2.5 text-sm">
                <div class="flex items-center justify-between text-text-secondary">
                    <span>{{ t('customer_portal.portal_quotes.subtotal') }}</span>
                    <span class="font-semibold text-text-primary" dir="ltr">
                        {{ formatMoney(quote.subtotal) }}
                    </span>
                </div>
                <div
                    v-if="quote.total_discount > 0"
                    class="flex items-center justify-between text-success"
                >
                    <span>{{ t('customer_portal.portal_quotes.discount') }}</span>
                    <span class="font-semibold" dir="ltr">
                        -{{ formatMoney(quote.total_discount) }}
                    </span>
                </div>
                <div
                    v-if="quote.tax_enabled_snapshot"
                    class="flex items-center justify-between text-text-secondary"
                >
                    <span>
                        {{ t('customer_portal.portal_quotes.tax_amount') }} (
                        <span dir="ltr">{{ quote.tax_rate_snapshot }}%</span>
                        )
                    </span>
                    <span class="font-semibold text-text-primary" dir="ltr">
                        {{ formatMoney(quote.total_tax) }}
                    </span>
                </div>
                <div
                    class="pt-3 border-t border-border flex items-center justify-between text-base font-extrabold text-text-primary"
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
            class="rounded-2xl bg-surface border border-border p-5 shadow-sm mb-6"
        >
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-text-secondary mb-2">
                {{ t('customer_portal.portal_quotes.notes_title') }}
            </h3>
            <p
                v-if="quote.notes"
                class="text-sm text-text-primary whitespace-pre-line mb-2"
            >
                {{ quote.notes }}
            </p>
            <p
                v-if="quote.quote_terms"
                class="text-xs text-text-secondary whitespace-pre-line leading-relaxed"
            >
                {{ quote.quote_terms }}
            </p>
        </section>

        <!-- Signature (if signed) -->
        <section
            v-if="quote.signature_url"
            class="rounded-2xl bg-success-soft border border-success/30 p-5 shadow-sm flex flex-wrap items-center justify-between gap-4"
        >
            <p class="text-xs font-extrabold text-text-primary">
                {{ t('customer_portal.portal_quotes.signed_badge') }}
            </p>
            <img
                :src="quote.signature_url"
                :alt="t('customer_portal.portal_quotes.signed_badge')"
                class="h-16 bg-surface rounded-xl border border-success/30 p-1.5 object-contain"
            />
        </section>

        <!-- Approve modal -->
        <BaseModal
            :show="showApproveModal"
            :title="t('customer_portal.portal_quotes.approve_modal_title')"
            size="md"
            @close="showApproveModal = false"
        >
            <p class="text-xs text-text-secondary mb-4">
                {{ t('customer_portal.portal_quotes.approve_modal_subtitle') }}
            </p>
            <SignaturePad
                v-model="signatureData"
                :label="t('customer_portal.portal_quotes.signature_label')"
            />
            <p v-if="approveError" class="mt-2 text-xs font-semibold text-danger">
                {{ approveError }}
            </p>
            <template #footer>
                <div class="flex items-center justify-end gap-2.5 w-full">
                    <AppButton type="button" variant="secondary" @click="showApproveModal = false">
                        {{ t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="primary"
                        :loading="submitting"
                        @click="submitApprove"
                    >
                        {{ t('customer_portal.portal_quotes.approve_confirm_btn') }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>

        <!-- Reject modal -->
        <BaseModal
            :show="showRejectModal"
            :title="t('customer_portal.portal_quotes.reject_modal_title')"
            size="sm"
            @close="showRejectModal = false"
        >
            <AppTextarea
                id="rejection_reason"
                v-model="rejectionReason"
                :label="t('customer_portal.portal_quotes.reject_reason_label')"
                :rows="4"
                maxlength="1000"
                :placeholder="t('customer_portal.portal_quotes.reject_reason_placeholder')"
                required
                :error="rejectError"
            />
            <template #footer>
                <div class="flex items-center justify-end gap-2.5 w-full">
                    <AppButton type="button" variant="secondary" @click="showRejectModal = false">
                        {{ t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="danger"
                        :loading="submitting"
                        :disabled="!rejectionReason.trim()"
                        @click="submitReject"
                    >
                        {{ t('customer_portal.portal_quotes.reject_confirm_btn') }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>
    </CustomerPortalLayout>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { printDocument } from '@/Composables/usePrint';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import SignaturePad from '@/Components/Common/SignaturePad.vue';
import BaseModal from '@/Components/BaseModal.vue';
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
    printDocument();
}
</script>
