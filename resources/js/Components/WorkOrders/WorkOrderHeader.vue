<template>
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <!-- Left side: Back and Main Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <BackButton :href="backUrl" />

            <div class="flex items-center gap-1.5 p-1.5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <!-- Print Button -->
                <button @click="emit('print')"
                    class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                    :title="$t('common.print')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </button>

                <!-- Payments Button -->
                <button v-if="showPaymentsBtn" @click="emit('payments')"
                    class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                    :title="$t('payments.title')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </button>

                <!-- Deferred Invoice Button (Done + no invoice + balance > 0) -->
                <button
                    v-if="showDeferredInvoiceBtn"
                    @click="emit('create-deferred-invoice')"
                    class="p-2.5 text-violet-600 hover:text-violet-700 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-xl transition-all"
                    :title="$t('invoices.create_deferred') || 'إنشاء فاتورة آجلة'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </button>

                <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                <!-- Edit Button -->
                <button v-if="!isReadOnly" @click="emit('edit')"
                    class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                    :title="$t('common.edit')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
            </div>

            <!-- Status Change Buttons -->
            <div v-if="!['done', 'cancelled'].includes(workOrder.status)" class="flex items-center gap-2">
                <!-- Start Work button (Open/In Progress -> In Progress) -->
                <template v-if="['open', 'in_progress'].includes(workOrder.status) && (workOrder.items || []).some(item => item.status === 'pending')">
                    <button @click="emit('change-status', 'start')"
                        class="p-2.5 bg-amber-500 text-white rounded-xl shadow-md shadow-amber-500/20 hover:shadow-amber-500/35 hover:-translate-y-0.5 transition-all flex items-center justify-center"
                        :title="$t('work_orders.actions.start_work')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        </svg>
                    </button>
                </template>

                <!-- Resume Work button (On Hold -> Resume) -->
                <template v-if="workOrder.status === 'on_hold'">
                    <button @click="emit('change-status', 'resume')"
                        class="p-2.5 bg-indigo-600 text-white rounded-xl shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/35 hover:-translate-y-0.5 transition-all flex items-center justify-center"
                        :title="$t('work_orders.actions.resume_work') || 'استئناف العمل'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                        </svg>
                    </button>
                </template>

                <!-- Complete / Exit button (In Progress -> Complete) -->
                <template v-if="workOrder.status === 'in_progress' && allItemsCompleted">
                    <button @click="emit('change-status', 'complete')"
                        class="p-2.5 bg-emerald-600 text-white rounded-xl shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/35 hover:-translate-y-0.5 transition-all flex items-center justify-center"
                        :title="$t('work_orders.actions.complete')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </template>

                <!-- Close / Approve QC button (Ready for QC -> Done).
                     This is the missing link between auto-transition into QC
                     and actually closing the work order. Without it, the
                     operator would have to wait for the system to do
                     nothing — there was no UI to approve and close. The
                     tooltip "خروج المركبة" matches the modal that opens
                     (WorkOrderController::complete -> markAsCompleted).
                     Guarded by allItemsCompleted so the operator cannot
                     close a WO that still has pending / in-progress
                     services, even if the WO status slipped to ready_for_qc. -->
                <template v-if="workOrder.status === 'ready_for_qc' && allItemsCompleted">
                    <button @click="emit('change-status', 'complete')"
                        class="p-2.5 bg-emerald-600 text-white rounded-xl shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/35 hover:-translate-y-0.5 transition-all flex items-center justify-center"
                        :title="$t('work_orders.actions.vehicle_exit') || 'خروج المركبة'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </template>

                <!-- Put on Hold button (In Progress -> On Hold) -->
                <template v-if="workOrder.status === 'in_progress' && !allItemsCompleted">
                    <button @click="emit('change-status', 'hold')"
                        class="p-2.5 bg-gray-500 hover:bg-gray-600 text-white rounded-xl shadow-md shadow-gray-500/20 hover:shadow-gray-500/35 hover:-translate-y-0.5 transition-all flex items-center justify-center"
                        :title="$t('work_orders.actions.put_on_hold') || 'تعليق الكرت'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </template>

                <!-- Cancel button (Not Done or Cancelled) -->
                <button v-if="!['done', 'cancelled'].includes(workOrder.status)"
                    @click="emit('change-status', 'cancel')"
                    class="p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all shadow-sm flex items-center justify-center"
                    :title="$t('work_orders.actions.cancel')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Right side: Code and Status -->
        <div class="flex items-center gap-4">
            <div class="text-end">
                <div class="flex items-center gap-3">
                    <span :class="statusBadgeClass"
                        class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest">{{ $t(`work_orders.status.${workOrder.status}`) }}</span>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ workOrder.code }}</h1>
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 shadow-xl shadow-indigo-500/30 flex items-center justify-center text-white shrink-0">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>

                <!-- Linked invoice (when work order has been invoiced). Sits
                     directly under the WO code so the operator can jump to
                     the invoice in one click. -->
                <div v-if="workOrder.invoice && workOrder.invoice.id" class="mt-2 flex items-center justify-end gap-2">
                    <Link
                        :href="route('app.invoices.show', workOrder.invoice.id)"
                        class="inline-flex items-center gap-1.5 text-xs font-black text-emerald-700 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 hover:underline transition-colors group/inv"
                        :title="$t('invoices.title') || 'الفاتورة'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ $t('invoices.invoice') || 'فاتورة' }} #{{ workOrder.invoice.invoice_number }}</span>
                        <svg class="w-3 h-3 opacity-60 group-hover/inv:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </Link>
                </div>

                <!-- Hold reason display -->
                <div v-if="workOrder.status === 'on_hold' && workOrder.hold_reason" class="text-xs text-red-500 dark:text-red-400 mt-1.5 font-bold">
                    {{ $t('work_orders.hold_reason_label') || 'سبب التعليق:' }} {{ workOrder.hold_reason }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import BackButton from '@/Components/BackButton.vue';

const props = defineProps({
    workOrder: { type: Object, required: true },
    isReadOnly: { type: Boolean, default: false },
    balance: { type: Number, default: 0 },
});

const emit = defineEmits(['print', 'payments', 'edit', 'change-status', 'create-deferred-invoice']);

const { t } = useI18n();

// Show deferred invoice button when:
// - WO is done
// - No invoice has been issued yet
// - There is an outstanding balance
const showDeferredInvoiceBtn = computed(() => {
    return props.workOrder.status === 'done'
        && !props.workOrder.invoice
        && props.balance > 0.01;
});

const showPaymentsBtn = computed(() => {
    if (!props.isReadOnly) {
        return true;
    }
    const status = props.workOrder.status;
    const allowedStatuses = ['done', 'in_progress', 'ready_for_qc'];
    if (allowedStatuses.includes(status)) {
        const hasUnpaid = props.balance > 0;
        const hasDeferred = !!(props.workOrder.invoice && props.workOrder.invoice.due_date);
        return hasUnpaid || hasDeferred;
    }
    return false;
});

const allItemsCompleted = computed(() => {
    const items = props.workOrder.items || [];
    if (items.length === 0) return false;
    
    // Check if there is at least one completed item
    const hasCompleted = items.some(item => item.status === 'completed');
    if (!hasCompleted) return false;
    
    return items.every(item => ['completed', 'cancelled'].includes(item.status));
});

// Back URL: open work orders return to the open list, closed to the
// closed list. Kept inside the header so the navigation concern lives
// next to the rest of the header state.
const backUrl = computed(() => {
    const status = props.workOrder.status;
    
    // If completed ('done') but has outstanding balance and no invoice, it belongs to the open list (pending_payment subfilter)
    if (status === 'done' && props.balance > 0 && (!props.workOrder.invoice || !props.workOrder.invoice.id)) {
        return route('work-orders.index', { status: 'open', sub_filter: 'pending_payment' });
    }
    
    if (['done', 'cancelled'].includes(status)) {
        return route('work-orders.index', { status: 'closed' });
    }
    return route('work-orders.index', { status: 'open' });
});

// Status pill styling per status. Centralizing the map here means the
// header owns its own visual state and the parent doesn't need to know
// which Tailwind classes apply to which status.
const statusBadgeClass = computed(() => {
    const baseClass = 'inline-block px-2 py-1 text-xs font-medium rounded-full mt-1';
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        on_hold: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        ready_for_qc: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
        done: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    };
    return `${baseClass} ${classes[props.workOrder.status] || classes.draft}`;
});
</script>
