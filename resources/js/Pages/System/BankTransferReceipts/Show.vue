<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    receipt: { type: Object, required: true },
});

const locale = computed(() => document.documentElement.lang || 'ar');
const processing = ref(false);
const showRejectModal = ref(false);
const rejectNotes = ref('');

const statusColors = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
};

const statusLabels = {
    pending: { ar: 'قيد المراجعة', en: 'Pending' },
    approved: { ar: 'موافق عليه', en: 'Approved' },
    rejected: { ar: 'مرفوض', en: 'Rejected' },
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
    processing.value = true;
    try {
        await router.post(
            `/system/bank-transfer-receipts/${props.receipt.id}/approve`,
            {},
            {
                preserveScroll: true,
            }
        );
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
</script>

<template>
    <Head :title="locale === 'ar' ? 'مراجعة الإيصال' : 'Review Receipt'" />

    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link
                        href="/system/bank-transfer-receipts"
                        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg
                            class="h-4 w-4 rtl:rotate-180"
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
                        {{ locale === 'ar' ? 'العودة للقائمة' : 'Back to list' }}
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ locale === 'ar' ? 'مراجعة الإيصال' : 'Review Receipt' }} #{{
                            receipt.id
                        }}
                    </h1>
                </div>
                <span
                    :class="[
                        'inline-flex items-center gap-1 self-start rounded-full px-3 py-1 text-xs font-bold',
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
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Receipt details -->
                <div class="space-y-4 lg:col-span-1">
                    <div
                        class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-500">
                            {{ locale === 'ar' ? 'تفاصيل المستأجر' : 'Tenant details' }}
                        </h2>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ locale === 'ar' ? 'الاسم:' : 'Name:' }}
                                </span>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ receipt.tenant?.trade_name || receipt.tenant?.name }}
                                </p>
                            </div>
                            <div v-if="receipt.tenant?.email">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ locale === 'ar' ? 'البريد:' : 'Email:' }}
                                </span>
                                <p class="text-gray-700 dark:text-gray-200" dir="ltr">
                                    {{ receipt.tenant.email }}
                                </p>
                            </div>
                            <div v-if="receipt.tenant?.phone">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ locale === 'ar' ? 'الهاتف:' : 'Phone:' }}
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
                            {{ locale === 'ar' ? 'تفاصيل الاشتراك' : 'Subscription details' }}
                        </h2>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ locale === 'ar' ? 'الباقة:' : 'Plan:' }}
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
                                    {{ locale === 'ar' ? 'دورة الفوترة:' : 'Cycle:' }}
                                </span>
                                <p class="text-gray-700 dark:text-gray-200">
                                    {{
                                        receipt.subscription?.billing_cycle === 'yearly'
                                            ? locale === 'ar'
                                                ? 'سنوي'
                                                : 'Yearly'
                                            : locale === 'ar'
                                              ? 'شهري'
                                              : 'Monthly'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-800/50 dark:bg-indigo-900/10"
                    >
                        <h2
                            class="mb-3 text-sm font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300"
                        >
                            {{ locale === 'ar' ? 'تفاصيل التحويل' : 'Transfer details' }}
                        </h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ locale === 'ar' ? 'المبلغ' : 'Amount' }}
                                </span>
                                <span
                                    class="font-mono font-bold text-indigo-700 dark:text-indigo-300"
                                >
                                    {{ formatCurrency(receipt.transfer_amount) }} SAR
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ locale === 'ar' ? 'تاريخ التحويل' : 'Transfer date' }}
                                </span>
                                <span class="font-mono text-gray-900 dark:text-white">
                                    {{ formatDate(receipt.transfer_date) }}
                                </span>
                            </div>
                            <div v-if="receipt.bank_name" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ locale === 'ar' ? 'البنك' : 'Bank' }}
                                </span>
                                <span class="text-gray-900 dark:text-white">
                                    {{ receipt.bank_name }}
                                </span>
                            </div>
                            <div v-if="receipt.transfer_reference" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ locale === 'ar' ? 'مرجع التحويل' : 'Reference' }}
                                </span>
                                <span class="font-mono text-gray-900 dark:text-white">
                                    {{ receipt.transfer_reference }}
                                </span>
                            </div>
                            <div v-if="receipt.sender_account_name" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ locale === 'ar' ? 'اسم المرسل' : 'Sender' }}
                                </span>
                                <span class="text-gray-900 dark:text-white">
                                    {{ receipt.sender_account_name }}
                                </span>
                            </div>
                            <div v-if="receipt.sender_account_number" class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ locale === 'ar' ? 'حساب المرسل' : 'Sender account' }}
                                </span>
                                <span class="font-mono text-gray-900 dark:text-white" dir="ltr">
                                    {{ receipt.sender_account_number }}
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
                                locale === 'ar' ? 'موافقة وتفعيل الاشتراك' : 'Approve and activate'
                            }}
                        </button>
                        <button
                            @click="showRejectModal = true"
                            :disabled="processing"
                            class="w-full rounded-lg border border-rose-300 bg-white px-4 py-2.5 text-sm font-bold text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-700 dark:bg-gray-800 dark:text-rose-300 dark:hover:bg-rose-900/20"
                        >
                            {{ locale === 'ar' ? 'رفض الإيصال' : 'Reject receipt' }}
                        </button>
                    </div>

                    <!-- Review history -->
                    <div
                        v-if="receipt.reviewed_at"
                        class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/30"
                    >
                        <h2 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-500">
                            {{ locale === 'ar' ? 'سجل المراجعة' : 'Review history' }}
                        </h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <strong>{{ receipt.reviewer?.name || '—' }}</strong>
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
                            {{ locale === 'ar' ? 'الإيصال المرفوع' : 'Uploaded receipt' }}
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
                            {{ locale === 'ar' ? 'تحميل' : 'Download' }}
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
                            {{ locale === 'ar' ? 'فتح في تبويب جديد' : 'Open in new tab' }}
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
                        {{ locale === 'ar' ? 'رفض الإيصال' : 'Reject receipt' }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{
                            locale === 'ar'
                                ? 'يرجى توضيح سبب الرفض. سيتم إبلاغ المستأجر بالسبب.'
                                : 'Please provide a rejection reason. The tenant will be notified.'
                        }}
                    </p>
                    <textarea
                        v-model="rejectNotes"
                        rows="4"
                        :placeholder="
                            locale === 'ar'
                                ? 'مثال: المبلغ لا يطابق المطلوب، الإيصال غير واضح...'
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
                            {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                        </button>
                        <button
                            type="button"
                            @click="reject"
                            :disabled="!rejectNotes.trim() || processing"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                        >
                            {{ locale === 'ar' ? 'تأكيد الرفض' : 'Confirm reject' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </SystemLayout>
</template>
