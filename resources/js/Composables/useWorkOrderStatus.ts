import { ref, computed, type Ref, type ComputedRef } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

export type StatusAction = 'start' | 'resume' | 'complete' | 'cancel' | 'hold';

export interface WorkOrderItem {
    status: string;
    [key: string]: unknown;
}

export interface WorkOrderPart {
    status: string;
    [key: string]: unknown;
}

export interface WorkOrderStatusLite {
    id: number;
    status: string;
    items?: WorkOrderItem[];
    parts?: WorkOrderPart[];
    total_paid?: number | string;
    totalPaid?: number;
    [key: string]: unknown;
}

export type WorkOrderInput = WorkOrderStatusLite | (() => WorkOrderStatusLite);

export interface UseWorkOrderStatusReturn {
    isReadOnly: ComputedRef<boolean>;
    // exit modal
    showExitModal: Ref<boolean>;
    exitDate: Ref<string>;
    exitNotes: Ref<string>;
    isDeferred: Ref<boolean>;
    dueDate: Ref<string>;
    cancelExit: () => void;
    confirmExit: () => void;
    // hold modal
    showHoldModal: Ref<boolean>;
    holdReason: Ref<string>;
    cancelHold: () => void;
    confirmHold: () => void;
    // action
    changeStatus: (action: StatusAction) => Promise<void>;
}

export function useWorkOrderStatus(params: {
    workOrder: WorkOrderInput;
    workOrderBalance: Ref<number> | { value: number };
}): UseWorkOrderStatusReturn {
    const { t } = useI18n();
    const { success, error: errorToast } = useToast();
    const { confirm } = useConfirm();

    const { workOrder, workOrderBalance } = params;

    const getWorkOrder = (): WorkOrderStatusLite =>
        typeof workOrder === 'function' ? (workOrder as () => WorkOrderStatusLite)() : workOrder;

    // ─── Read-only flag ───────────────────────────────────────────────────────
    const isReadOnly = computed<boolean>(() => {
        const closedStatuses = ['done', 'cancelled', 'closed', 'on_hold'];
        return closedStatuses.includes(getWorkOrder().status);
    });

    // ─── Vehicle Exit modal ───────────────────────────────────────────────────
    const showExitModal = ref(false);
    const exitDate = ref('');
    const exitNotes = ref('');
    const isDeferred = ref(false);
    const dueDate = ref('');

    function cancelExit(): void {
        showExitModal.value = false;
        exitDate.value = '';
        exitNotes.value = '';
        isDeferred.value = false;
        dueDate.value = '';
    }

    function confirmExit(): void {
        if (!exitDate.value) return;
        if (isDeferred.value && !dueDate.value) return;
        showExitModal.value = false;
        router.post(
            route('work-orders.complete', getWorkOrder().id),
            {
                exit_date: exitDate.value,
                notes: exitNotes.value,
                is_deferred: isDeferred.value,
                due_date: isDeferred.value ? dueDate.value : null,
            },
            {
                // Inertia follows the server redirect automatically:
                //   - to the invoice page when one is issued
                //   - to the work order page when done without invoice
                // The flash message from the server is shown by the global handler.
                onSuccess: () => {
                    cancelExit();
                },
                onError: (err: Record<string, unknown>) => {
                    showExitModal.value = true; // reopen modal on error
                    const msg =
                        (err.message as string) ||
                        (Object.values(err)[0] as string) ||
                        t('common.error');
                    errorToast(msg);
                },
            }
        );
    }

    // ─── Hold modal ───────────────────────────────────────────────────────────
    const showHoldModal = ref(false);
    const holdReason = ref('');

    function cancelHold(): void {
        showHoldModal.value = false;
        holdReason.value = '';
    }

    function confirmHold(): void {
        if (!holdReason.value.trim()) return;
        showHoldModal.value = false;
        router.post(
            route('work-orders.hold', getWorkOrder().id),
            {
                reason: holdReason.value,
            },
            {
                onSuccess: () => {
                    success(t('common.saved_success'));
                    holdReason.value = '';
                },
                onError: (err: Record<string, unknown>) => {
                    const msg =
                        (err.reason as string) ||
                        (Object.values(err)[0] as string) ||
                        t('common.error');
                    errorToast(msg);
                },
            }
        );
    }

    // ─── Status change dispatcher ─────────────────────────────────────────────
    async function changeStatus(action: StatusAction): Promise<void> {
        // "hold" opens the reason modal; confirmHold() fires the POST
        if (action === 'hold') {
            holdReason.value = '';
            showHoldModal.value = true;
            return;
        }

        if (action === 'cancel') {
            const wo = getWorkOrder();
            const totalPaid = parseFloat(String(wo.total_paid ?? wo.totalPaid ?? 0));
            const hasPayments = totalPaid > 0;

            // Cancelled items are treated as if they were removed — only active
            // (non-cancelled) items should block work-order cancellation.
            const activeItems = (wo.items || []).filter((i) => i.status !== 'cancelled');
            const hasItems = activeItems.length > 0;

            // Cancelled and reversed parts are treated as removed.
            const activeParts = (wo.parts || []).filter(
                (p) => !['cancelled', 'reversed'].includes(p.status)
            );
            const hasParts = activeParts.length > 0;

            if (hasPayments || hasItems || hasParts) {
                errorToast(t('messages.cannot_cancel_has_technicians_or_parts'));
                return;
            }
        }

        // "complete" opens the vehicle exit modal; confirmExit() fires the POST
        if (action === 'complete') {
            if (workOrderBalance.value < -0.01) {
                errorToast(t('messages.cannot_complete_excess_payments'));
                return;
            }
            exitDate.value = new Date().toISOString().substring(0, 10);
            exitNotes.value = '';
            isDeferred.value = false;
            dueDate.value = '';
            showExitModal.value = true;
            return;
        }

        const routeMap: Record<string, { routeName: string; type: 'success' | 'danger' }> = {
            start: { routeName: 'work-orders.start', type: 'success' },
            resume: { routeName: 'work-orders.resume', type: 'success' },
            complete: { routeName: 'work-orders.complete', type: 'success' },
            cancel: { routeName: 'work-orders.cancel', type: 'danger' },
        };

        const config = routeMap[action];
        if (!config) return;

        const labelKey = {
            start: 'work_orders.actions.start_work',
            resume: 'work_orders.actions.resume_work',
            complete: 'work_orders.actions.complete',
            cancel: 'work_orders.actions.cancel',
        }[action];

        const confirmed = await confirm({
            title: t(labelKey) || action,
            message: t('work_orders.messages.confirm_status_change'),
            confirmText: t('common.confirm'),
            cancelText: t('common.cancel'),
            type: config.type,
        });

        if (confirmed) {
            router.post(
                route(config.routeName, getWorkOrder().id),
                {},
                {
                    onSuccess: () => success(t('common.saved_success')),
                    onError: (err: Record<string, unknown>) => {
                        const msg =
                            (err.message as string) ||
                            (Object.values(err)[0] as string) ||
                            t('common.error');
                        errorToast(msg);
                    },
                }
            );
        }
    }

    return {
        // computed
        isReadOnly,
        // exit modal
        showExitModal,
        exitDate,
        exitNotes,
        isDeferred,
        dueDate,
        cancelExit,
        confirmExit,
        // hold modal
        showHoldModal,
        holdReason,
        cancelHold,
        confirmHold,
        // action
        changeStatus,
    };
}
