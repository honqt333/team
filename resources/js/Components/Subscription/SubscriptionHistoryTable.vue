<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    subscriptions: { type: Array, default: () => [] },
    canCancel: { type: Boolean, default: true },
    canResume: { type: Boolean, default: true },
});

const emit = defineEmits(['refresh']);

const locale = computed(() => document.documentElement.lang || 'ar');
const processing = ref(null);
const confirmCancel = ref(null);

const statusConfig = {
    active: {
        label_ar: 'نشط',
        label_en: 'Active',
        class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
    },
    trialing: {
        label_ar: 'تجريبي',
        label_en: 'Trial',
        class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    },
    trial: {
        label_ar: 'تجريبي',
        label_en: 'Trial',
        class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    },
    pending: {
        label_ar: 'معلق',
        label_en: 'Pending',
        class: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    },
    cancelled: {
        label_ar: 'ملغي',
        label_en: 'Cancelled',
        class: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    },
    expired: {
        label_ar: 'منتهي',
        label_en: 'Expired',
        class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    },
};

const planName = (sub) => {
    if (locale.value === 'ar') return sub.plan?.name_ar || sub.plan?.name || '—';
    return sub.plan?.name_en || sub.plan?.name || '—';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(amount || 0);
};

async function cancel(sub) {
    processing.value = sub.id;
    try {
        await fetch(`/app/settings/company/subscription/cancel/${sub.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        confirmCancel.value = null;
        emit('refresh');
    } catch (e) {
        // silent
    } finally {
        processing.value = null;
    }
}

async function resume(sub) {
    processing.value = sub.id;
    try {
        await fetch(`/app/settings/company/subscription/resume/${sub.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        emit('refresh');
    } catch (e) {
        // silent
    } finally {
        processing.value = null;
    }
}

async function toggleAutoRenew(sub) {
    processing.value = sub.id;
    try {
        await fetch(`/app/settings/company/subscription/auto-renew/${sub.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        emit('refresh');
    } catch (e) {
        // silent
    } finally {
        processing.value = null;
    }
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="!subscriptions.length"
            class="rounded-lg border border-dashed border-slate-200 p-8 text-center dark:border-slate-700"
        >
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ locale === 'ar' ? 'لا يوجد سجل اشتراكات' : 'No subscription history' }}
            </p>
        </div>

        <div
            v-else
            class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700"
        >
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th
                            class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            {{ locale === 'ar' ? 'الباقة' : 'Plan' }}
                        </th>
                        <th
                            class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            {{ locale === 'ar' ? 'الفترة' : 'Cycle' }}
                        </th>
                        <th
                            class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            {{ locale === 'ar' ? 'من' : 'From' }}
                        </th>
                        <th
                            class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            {{ locale === 'ar' ? 'إلى' : 'To' }}
                        </th>
                        <th
                            class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            {{ locale === 'ar' ? 'المبلغ' : 'Amount' }}
                        </th>
                        <th
                            class="px-4 py-3 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            {{ locale === 'ar' ? 'الحالة' : 'Status' }}
                        </th>
                        <th
                            class="px-4 py-3 text-end text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            {{ locale === 'ar' ? 'إجراءات' : 'Actions' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <tr
                        v-for="sub in subscriptions"
                        :key="sub.id"
                        class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                    >
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900 dark:text-white">
                                {{ planName(sub) }}
                            </p>
                            <p
                                v-if="sub.promo_code"
                                class="mt-0.5 font-mono text-xs text-emerald-600 dark:text-emerald-400"
                            >
                                {{ sub.promo_code }}
                            </p>
                        </td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                            {{
                                sub.billing_cycle === 'yearly'
                                    ? locale === 'ar'
                                        ? 'سنوي'
                                        : 'Yearly'
                                    : locale === 'ar'
                                      ? 'شهري'
                                      : 'Monthly'
                            }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                            {{ formatDate(sub.starts_at) }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                            {{ formatDate(sub.ends_at) }}
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-900 dark:text-white">
                            {{ formatCurrency(sub.price) }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-semibold',
                                    statusConfig[sub.status]?.class,
                                ]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                                {{
                                    locale === 'ar'
                                        ? statusConfig[sub.status]?.label_ar
                                        : statusConfig[sub.status]?.label_en
                                }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-1">
                                <!-- Auto-renew toggle for active -->
                                <button
                                    v-if="sub.status === 'active'"
                                    type="button"
                                    @click="toggleAutoRenew(sub)"
                                    :disabled="processing === sub.id"
                                    :title="
                                        sub.auto_renew
                                            ? locale === 'ar'
                                                ? 'إيقاف التجديد التلقائي'
                                                : 'Disable auto-renew'
                                            : locale === 'ar'
                                              ? 'تفعيل التجديد التلقائي'
                                              : 'Enable auto-renew'
                                    "
                                    :class="[
                                        'rounded p-1.5 transition-colors disabled:opacity-50',
                                        sub.auto_renew
                                            ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30'
                                            : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700',
                                    ]"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        />
                                    </svg>
                                </button>

                                <!-- Cancel for active/trialing -->
                                <button
                                    v-if="
                                        canCancel &&
                                        ['active', 'trialing', 'trial'].includes(sub.status)
                                    "
                                    type="button"
                                    @click="confirmCancel = sub"
                                    :disabled="processing === sub.id"
                                    class="rounded p-1.5 text-rose-600 hover:bg-rose-50 disabled:opacity-50 dark:hover:bg-rose-900/30"
                                    :title="
                                        locale === 'ar' ? 'إلغاء الاشتراك' : 'Cancel subscription'
                                    "
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>

                                <!-- Resume for cancelled -->
                                <button
                                    v-if="canResume && sub.status === 'cancelled'"
                                    type="button"
                                    @click="resume(sub)"
                                    :disabled="processing === sub.id"
                                    class="rounded p-1.5 text-emerald-600 hover:bg-emerald-50 disabled:opacity-50 dark:hover:bg-emerald-900/30"
                                    :title="
                                        locale === 'ar' ? 'استئناف الاشتراك' : 'Resume subscription'
                                    "
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cancel confirmation modal -->
        <Teleport to="body">
            <div
                v-if="confirmCancel"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="confirmCancel = null"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-800">
                    <div
                        class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ locale === 'ar' ? 'تأكيد الإلغاء' : 'Confirm cancellation' }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{
                            locale === 'ar'
                                ? 'هل أنت متأكد من إلغاء هذا الاشتراك؟ ستفقد الوصول للميزات في نهاية الفترة الحالية.'
                                : 'Are you sure you want to cancel this subscription? You will lose access to features at the end of the current period.'
                        }}
                    </p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="confirmCancel = null"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ locale === 'ar' ? 'تراجع' : 'Go back' }}
                        </button>
                        <button
                            type="button"
                            @click="cancel(confirmCancel)"
                            :disabled="processing === confirmCancel.id"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-50"
                        >
                            {{ locale === 'ar' ? 'نعم، إلغاء' : 'Yes, cancel' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
