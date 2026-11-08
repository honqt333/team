<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const { t } = useI18n();

const props = defineProps({
    receipt: { type: Object, required: true },
});

// Optional chaining on `document?.documentElement?.lang` so the
// computed never throws on a host where the DOM isn't ready yet.
// Matches the defensive pattern in useTheme.js / Pages/Welcome.vue.
const locale = computed(() => document?.documentElement?.lang || 'ar');
const processing = ref(false);
const showRejectModal = ref(false);
const rejectNotes = ref('');

// Approval modal state. Opens when the admin clicks Approve so they
// can review the amount comparison and explicitly confirm when there's
// a mismatch. The amountState computed (defined later in the script)
// tells us whether a mismatch exists and drives the force-approve
// checkbox visibility.
const showApproveModal = ref(false);
const approveNotes = ref('');
const forceApprove = ref(false);
// Partial approval: when true, the admin is approving only PART of
// what the tenant transferred (e.g. received 1000 but invoice is 1200,
// admin approves 1000 and the invoice stays open for the 200 gap).
const partialApproval = ref(false);
const approvedAmount = ref(0);

const hasMismatch = computed(
    () => amountState.value.type === 'short' || amountState.value.type === 'over'
);

const canApprove = computed(() => {
    if (processing.value) return false;
    if (!hasMismatch.value) return true;
    // Mismatch path — admin must tick the explicit override checkbox.
    return forceApprove.value;
});

// When the modal opens, default the approved amount to the full
// transferred amount. The admin then edits it down if they want a
// partial approval.
watch(showApproveModal, (open) => {
    if (open) {
        approvedAmount.value = Number(props.receipt.transfer_amount);
        partialApproval.value = false;
        forceApprove.value = false;
        approveNotes.value = '';
    }
});

const partialShortfall = computed(() => {
    if (!partialApproval.value) return 0;
    return Math.max(0, Number(props.receipt.transfer_amount) - (approvedAmount.value || 0));
});

const statusColors = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
};

const statusLabels = {
    pending: { ar: t('system.bank_transfer_receipts.pending_2'), en: 'Pending' },
    approved: { ar: t('system.bank_transfer_receipts.approved_3'), en: 'Approved' },
    rejected: { ar: t('system.bank_transfer_receipts.rejected_3'), en: 'Rejected' },
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(amount || 0);
};

async function approve() {
    // Opens the confirmation modal. The actual submit happens via
    // confirmApprove() — keeps the same UX pattern as the existing
    // reject modal (clear separation between "intends to" and "did").
    showApproveModal.value = true;
    forceApprove.value = false;
    approveNotes.value = '';
}

async function confirmApprove() {
    if (!canApprove.value) return;
    // For partial approvals, validate the amount before sending — the
    // backend also validates but a client-side check gives the admin
    // immediate feedback without a round-trip.
    if (partialApproval.value) {
        const amt = Number(approvedAmount.value);
        const received = Number(props.receipt.transfer_amount);
        if (!amt || amt <= 0 || amt > received + 0.01) {
            return;
        }
    }
    processing.value = true;
    try {
        const payload = {
            notes: approveNotes.value || null,
            force_approve: hasMismatch.value && forceApprove.value,
        };
        if (partialApproval.value) {
            payload.approved_amount = Number(approvedAmount.value);
        }
        await router.post(`/system/bank-transfer-receipts/${props.receipt.id}/approve`, payload, {
            preserveScroll: true,
        });
        showApproveModal.value = false;
    } finally {
        processing.value = false;
    }
}

async function reject() {
    if (!rejectNotes.value.trim()) return;
    processing.value = true;
    try {
        await router.post(
            `/system/bank-transfer-receipts/${props.receipt.id}/reject`,
            {
                notes: rejectNotes.value,
            },
            {
                preserveScroll: true,
            }
        );
        showRejectModal.value = false;
    } finally {
        processing.value = false;
    }
}

const isImage = computed(() => {
    const mime = props.receipt.receipt_mime_type || '';
    return mime.startsWith('image/');
});

// Amount comparison helpers — what the invoice was for vs. what the
// tenant actually transferred. Null when there's no payment linked
// (legacy receipts or one created before the link was required).
const amountState = computed(() => {
    if (props.receipt.amount_diff === null || props.receipt.amount_diff === undefined) {
        return { type: 'unknown', diff: null, expected: null };
    }
    const diff = Number(props.receipt.amount_diff);
    if (Math.abs(diff) < 0.01) {
        return { type: 'match', diff: 0, expected: Number(props.receipt.expected_amount) };
    }
    if (diff < 0) {
        return {
            type: 'short',
            diff,
            expected: Number(props.receipt.expected_amount),
            message:
                locale.value === 'ar'
                    ? t('system.bank_transfer_receipts.transferred_less_than_required', {
                          amount: formatCurrency(-diff),
                      })
                    : `Transferred amount is ${formatCurrency(-diff)} SAR short`,
        };
    }
    return {
        type: 'over',
        diff,
        expected: Number(props.receipt.expected_amount),
        message:
            locale.value === 'ar'
                ? t('system.bank_transfer_receipts.transferred_more_than_required', {
                      amount: formatCurrency(diff),
                  })
                : `Transferred amount is ${formatCurrency(diff)} SAR over (credit will be recorded)`,
    };
});
</script>

<template>
    <Head :title="locale === 'ar' ? $t('system.bank_transfer_receipts.title') : 'Review Receipt'" />

    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="
                    (locale === 'ar'
                        ? $t('system.bank_transfer_receipts.title')
                        : 'Review Receipt') +
                    ' #' +
                    receipt.id
                "
                :subtitle="receipt.tenant?.trade_name || receipt.tenant?.name || ''"
            >
                <template #back>
                    <Link
                        href="/system/bank-transfer-receipts"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700/60 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors"
                        :title="
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.back_to_list_2')
                                : 'Back to list'
                        "
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <span
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold',
                            statusColors[receipt.status],
                        ]"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                        {{
                            locale === 'ar'
                                ? statusLabels[receipt.status]?.ar
                                : statusLabels[receipt.status]?.en
                        }}
                    </span>
                </template>
            </PageHeader>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Receipt details -->
                <div class="space-y-4 lg:col-span-1">
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-500">
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.tenant_details_2')
                                    : 'Tenant details'
                            }}
                        </h2>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.name_2')
                                            : 'Name:'
                                    }}
                                </span>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ receipt.tenant?.trade_name || receipt.tenant?.name }}
                                </p>
                            </div>
                            <div v-if="receipt.tenant?.email">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.email_2')
                                            : 'Email:'
                                    }}
                                </span>
                                <p class="text-gray-700 dark:text-gray-200" dir="ltr">
                                    {{ receipt.tenant.email }}
                                </p>
                            </div>
                            <div v-if="receipt.tenant?.phone">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ locale === 'ar' ? $t('common.phone_label') : 'Phone:' }}
                                </span>
                                <p class="text-gray-700 dark:text-gray-200" dir="ltr">
                                    {{ receipt.tenant.phone }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-500">
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.subscription_details')
                                    : 'Subscription details'
                            }}
                        </h2>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.plan_2')
                                            : 'Plan:'
                                    }}
                                </span>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{
                                        locale === 'ar'
                                            ? receipt.subscription?.plan?.name_ar
                                            : receipt.subscription?.plan?.name_en
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.cycle_2')
                                            : 'Cycle:'
                                    }}
                                </span>
                                <p class="text-gray-700 dark:text-gray-200">
                                    {{
                                        receipt.subscription?.billing_cycle === 'yearly'
                                            ? locale === 'ar'
                                                ? $t('system.bank_transfer_receipts.yearly')
                                                : 'Yearly'
                                            : locale === 'ar'
                                              ? $t('system.bank_transfer_receipts.monthly')
                                              : 'Monthly'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Amount comparison card — surfaces any mismatch between
                         the invoice amount and the transferred amount BEFORE the
                         admin clicks Approve. Replaces the previous flow where
                         mismatches were only caught in the audit log. -->
                    <div
                        v-if="amountState.type !== 'unknown'"
                        class="rounded-xl border p-5"
                        :class="{
                            'border-emerald-200 bg-emerald-50 dark:border-emerald-800/50 dark:bg-emerald-900/10':
                                amountState.type === 'match',
                            'border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/10':
                                amountState.type === 'short',
                            'border-orange-200 bg-orange-50 dark:border-orange-800/50 dark:bg-orange-900/10':
                                amountState.type === 'over',
                        }"
                    >
                        <h2
                            class="mb-3 text-sm font-bold uppercase tracking-wider"
                            :class="{
                                'text-emerald-700 dark:text-emerald-300':
                                    amountState.type === 'match',
                                'text-amber-700 dark:text-amber-300': amountState.type === 'short',
                                'text-orange-700 dark:text-orange-300': amountState.type === 'over',
                            }"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.amount_verification')
                                    : 'Amount verification'
                            }}
                        </h2>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.due')
                                            : 'Due'
                                    }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-lg font-bold text-gray-900 dark:text-white"
                                >
                                    {{ formatCurrency(amountState.expected) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.transferred')
                                            : 'Transferred'
                                    }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-lg font-bold text-gray-900 dark:text-white"
                                >
                                    {{ formatCurrency(receipt.transfer_amount) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.difference')
                                            : 'Difference'
                                    }}
                                </p>
                                <p
                                    v-if="amountState.type === 'match'"
                                    class="mt-1 font-mono text-lg font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    ✓
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.match')
                                            : 'Match'
                                    }}
                                </p>
                                <p
                                    v-else
                                    class="mt-1 font-mono text-lg font-bold"
                                    :class="{
                                        'text-amber-700 dark:text-amber-300':
                                            amountState.type === 'short',
                                        'text-orange-700 dark:text-orange-300':
                                            amountState.type === 'over',
                                    }"
                                >
                                    {{ amountState.diff > 0 ? '+' : ''
                                    }}{{ formatCurrency(amountState.diff) }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="amountState.message"
                            class="mt-3 text-center text-sm font-medium"
                            :class="{
                                'text-amber-700 dark:text-amber-300': amountState.type === 'short',
                                'text-orange-700 dark:text-orange-300': amountState.type === 'over',
                            }"
                        >
                            {{ amountState.message }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-800/50 dark:bg-indigo-900/10"
                    >
                        <h2
                            class="mb-3 text-sm font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.transfer_details')
                                    : 'Transfer details'
                            }}
                        </h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.amount')
                                            : 'Amount'
                                    }}
                                </span>
                                <span
                                    class="font-mono font-bold text-indigo-700 dark:text-indigo-300"
                                >
                                    {{ formatCurrency(receipt.transfer_amount) }} SAR
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.transfer_date')
                                            : 'Transfer date'
                                    }}
                                </span>
                                <span class="font-mono text-gray-900 dark:text-white">
                                    {{ formatDate(receipt.transfer_date) }}
                                </span>
                            </div>
                            <div v-if="receipt.bank_name" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.bank')
                                            : 'Bank'
                                    }}
                                </span>
                                <span class="text-gray-900 dark:text-white">
                                    {{ receipt.bank_name }}
                                </span>
                            </div>
                            <div v-if="receipt.transfer_reference" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.reference')
                                            : 'Reference'
                                    }}
                                </span>
                                <span class="font-mono text-gray-900 dark:text-white">
                                    {{ receipt.transfer_reference }}
                                </span>
                            </div>
                            <div v-if="receipt.account_name" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{
                                        locale === 'ar'
                                            ? $t('system.bank_transfer_receipts.account_holder')
                                            : 'Account holder'
                                    }}
                                </span>
                                <span class="text-gray-900 dark:text-white">
                                    {{ receipt.account_name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div v-if="receipt.status === 'pending'" class="space-y-2">
                        <button
                            @click="approve"
                            :disabled="processing"
                            class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.approve_and_activate_2')
                                    : 'Approve and activate'
                            }}
                        </button>
                        <button
                            @click="showRejectModal = true"
                            :disabled="processing"
                            class="w-full rounded-lg border border-rose-300 bg-white px-4 py-2.5 text-sm font-bold text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-700 dark:bg-gray-800 dark:text-rose-300 dark:hover:bg-rose-900/20"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.reject_receipt_2')
                                    : 'Reject receipt'
                            }}
                        </button>
                    </div>

                    <!-- Review history -->
                    <div
                        v-if="receipt.reviewed_at"
                        class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/30"
                    >
                        <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-500">
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.review_history')
                                    : 'Review history'
                            }}
                        </h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <strong>{{ receipt.reviewer_name || '—' }}</strong>
                            <br />
                            <span class="text-xs text-gray-500">
                                {{ formatDateTime(receipt.reviewed_at) }}
                            </span>
                        </p>
                        <p
                            v-if="receipt.review_notes"
                            class="mt-2 whitespace-pre-line rounded bg-white p-2 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{ receipt.review_notes }}
                        </p>
                    </div>
                </div>

                <!-- Receipt file preview -->
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800 lg:col-span-2"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.uploaded_receipt')
                                    : 'Uploaded receipt'
                            }}
                        </h2>
                        <a
                            :href="`/system/bank-transfer-receipts/${receipt.id}/download`"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.download_2')
                                    : 'Download'
                            }}
                        </a>
                    </div>

                    <div
                        v-if="isImage"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700"
                    >
                        <img
                            :src="receipt.receipt_url"
                            :alt="receipt.receipt_filename"
                            class="mx-auto max-h-[600px] w-auto"
                        />
                    </div>
                    <div
                        v-else
                        class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900/50"
                    >
                        <svg
                            class="mx-auto h-16 w-16 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ receipt.receipt_filename }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ receipt.receipt_mime_type }} ·
                            {{ ((receipt.receipt_size_bytes || 0) / 1024).toFixed(1) }} KB
                        </p>
                        <a
                            :href="receipt.receipt_url"
                            target="_blank"
                            class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.open_new_tab')
                                    : 'Open in new tab'
                            }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject modal -->
        <Teleport to="body">
            <div
                v-if="showRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showRejectModal = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.reject_receipt_2')
                                : 'Reject receipt'
                        }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{
                            locale === 'ar'
                                ? $t(
                                      'system.bank_transfer_receipts.please_provide_a_rejection_reason._the_tenant_wi'
                                  )
                                : 'Please provide a rejection reason. The tenant will be notified.'
                        }}
                    </p>
                    <textarea
                        v-model="rejectNotes"
                        rows="4"
                        :placeholder="
                            locale === 'ar'
                                ? $t(
                                      'system.bank_transfer_receipts.e.g._amount_mismatch_receipt_unclear...'
                                  )
                                : 'e.g. Amount mismatch, receipt unclear...'
                        "
                        class="mt-4 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-rose-500 focus:ring-1 focus:ring-rose-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    ></textarea>
                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showRejectModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.cancel')
                                    : 'Cancel'
                            }}
                        </button>
                        <button
                            type="button"
                            @click="reject"
                            :disabled="!rejectNotes.trim() || processing"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.confirm_reject_2')
                                    : 'Confirm reject'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Approval confirmation modal. Surfaces the amount comparison
             and forces the admin to opt-in via force_approve when there
             is a mismatch. Lives next to the reject modal for visual
             parity between approve and reject flows. -->
        <Teleport to="body">
            <div
                v-if="showApproveModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showApproveModal = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.confirm_receipt_approval')
                                : 'Confirm receipt approval'
                        }}
                    </h2>

                    <!-- Compact amount summary, mirrors the card on the
                         page so the admin sees the same numbers at a glance -->
                    <div
                        v-if="amountState.type !== 'unknown'"
                        class="mt-4 rounded-lg border p-3 text-sm"
                        :class="{
                            'border-emerald-200 bg-emerald-50 dark:border-emerald-800/50 dark:bg-emerald-900/10':
                                amountState.type === 'match',
                            'border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-900/10':
                                amountState.type === 'short',
                            'border-orange-200 bg-orange-50 dark:border-orange-800/50 dark:bg-orange-900/10':
                                amountState.type === 'over',
                        }"
                    >
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-300">
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.due')
                                        : 'Due'
                                }}
                            </span>
                            <span class="font-mono font-bold">
                                {{ formatCurrency(amountState.expected) }} SAR
                            </span>
                        </div>
                        <div class="mt-1 flex justify-between">
                            <span class="text-gray-600 dark:text-gray-300">
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.transferred')
                                        : 'Transferred'
                                }}
                            </span>
                            <span class="font-mono font-bold">
                                {{ formatCurrency(receipt.transfer_amount) }} SAR
                            </span>
                        </div>
                        <div class="mt-2 flex justify-between border-t border-current/10 pt-2">
                            <span class="font-semibold">
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.difference')
                                        : 'Difference'
                                }}
                            </span>
                            <span
                                v-if="amountState.type === 'match'"
                                class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                ✓
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.match')
                                        : 'Match'
                                }}
                            </span>
                            <span
                                v-else
                                class="font-mono font-bold"
                                :class="{
                                    'text-amber-700 dark:text-amber-300':
                                        amountState.type === 'short',
                                    'text-orange-700 dark:text-orange-300':
                                        amountState.type === 'over',
                                }"
                            >
                                {{ amountState.diff > 0 ? '+' : ''
                                }}{{ formatCurrency(amountState.diff) }} SAR
                            </span>
                        </div>
                    </div>

                    <!-- Mismatch override — only shows when there's a diff.
                         The checkbox is the explicit "I know what I'm doing"
                         signal that gates the confirm button. -->
                    <label
                        v-if="hasMismatch"
                        class="mt-4 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-700 dark:bg-amber-900/20"
                    >
                        <input
                            v-model="forceApprove"
                            type="checkbox"
                            class="mt-0.5 rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                        />
                        <span class="text-amber-800 dark:text-amber-200">
                            {{
                                locale === 'ar'
                                    ? $t(
                                          'system.bank_transfer_receipts.approve_despite_the_amount_mismatch_will_be_reco'
                                      )
                                    : 'Approve despite the amount mismatch (will be recorded in the audit log)'
                            }}
                        </span>
                    </label>

                    <!-- Partial approval — admin can choose to credit only
                         part of what the tenant transferred. The shortfall
                         is logged and the invoice stays open for the
                         remaining balance. Useful when a tenant pays
                         most of an invoice but the admin is writing off
                         the rest as a goodwill gesture. -->
                    <div
                        v-if="receipt.transfer_amount"
                        class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="partialApproval"
                                type="checkbox"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="font-medium text-slate-700 dark:text-slate-200">
                                {{
                                    locale === 'ar'
                                        ? $t(
                                              'system.bank_transfer_receipts.partial_approval_credit_a_different_amount_than_'
                                          )
                                        : 'Partial approval (credit a different amount than received)'
                                }}
                            </span>
                        </label>
                        <div v-if="partialApproval" class="mt-2 space-y-2 pl-6">
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-200"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t('system.bank_transfer_receipts.approved_amount')
                                        : 'Approved amount (SAR)'
                                }}
                            </label>
                            <input
                                v-model.number="approvedAmount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="Number(receipt.transfer_amount)"
                                dir="ltr"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                            />
                            <p
                                v-if="partialShortfall > 0"
                                class="text-xs text-amber-700 dark:text-amber-300"
                            >
                                {{
                                    locale === 'ar'
                                        ? $t(
                                              'system.bank_transfer_receipts.shortfall_recorded_as_credit',
                                              { amount: formatCurrency(partialShortfall) }
                                          )
                                        : `Shortfall of ${formatCurrency(partialShortfall)} SAR will be recorded as credit (invoice stays open)`
                                }}
                            </p>
                        </div>
                    </div>

                    <label
                        class="mt-4 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        {{
                            locale === 'ar'
                                ? $t('system.bank_transfer_receipts.notes_optional')
                                : 'Notes (optional)'
                        }}
                    </label>
                    <textarea
                        v-model="approveNotes"
                        rows="3"
                        :placeholder="
                            locale === 'ar'
                                ? $t(
                                      'system.bank_transfer_receipts.e.g._confirmed_full_amount_received_from_tenant.'
                                  )
                                : 'e.g. Confirmed full amount received from tenant...'
                        "
                        class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    ></textarea>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showApproveModal = false"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.cancel')
                                    : 'Cancel'
                            }}
                        </button>
                        <button
                            type="button"
                            @click="confirmApprove"
                            :disabled="!canApprove"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {{
                                locale === 'ar'
                                    ? $t('system.bank_transfer_receipts.confirm_approve_2')
                                    : 'Confirm approve'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </SystemLayout>
</template>
