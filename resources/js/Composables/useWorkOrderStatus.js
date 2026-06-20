import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

export function useWorkOrderStatus({ workOrder, workOrderBalance }) {
    const { t } = useI18n();
    const { success, error: errorToast } = useToast();
    const { confirm } = useConfirm();

    // ─── Read-only flag ───────────────────────────────────────────────────────
    const isReadOnly = computed(() => {
        const closedStatuses = ['done', 'cancelled', 'closed', 'on_hold'];
        return closedStatuses.includes(workOrder.status);
    });

    // ─── Vehicle Exit modal ───────────────────────────────────────────────────
    const showExitModal = ref(false);
    const exitDate = ref('');
    const exitNotes = ref('');
    const isDeferred = ref(false);
    const dueDate = ref('');

    function cancelExit() {
        showExitModal.value = false;
        exitDate.value = '';
        exitNotes.value = '';
        isDeferred.value = false;
        dueDate.value = '';
    }

    function confirmExit() {
        if (!exitDate.value) return;
        if (isDeferred.value && !dueDate.value) return;
        showExitModal.value = false;
        router.post(route('work-orders.complete', workOrder.id), {
            exit_date: exitDate.value,
            notes: exitNotes.value,
            is_deferred: isDeferred.value,
            due_date: isDeferred.value ? dueDate.value : null,
        }, {
            onSuccess: () => {
                success(t('common.saved_success'));
                cancelExit();
            },
            onError: (err) => {
                const msg = err.message || Object.values(err)[0] || t('common.error');
                errorToast(msg);
            },
        });
    }

    // ─── Hold modal ───────────────────────────────────────────────────────────
    const showHoldModal = ref(false);
    const holdReason = ref('');

    function cancelHold() {
        showHoldModal.value = false;
        holdReason.value = '';
    }

    function confirmHold() {
        if (!holdReason.value.trim()) return;
        showHoldModal.value = false;
        router.post(route('work-orders.hold', workOrder.id), {
            reason: holdReason.value,
        }, {
            onSuccess: () => {
                success(t('common.saved_success'));
                holdReason.value = '';
            },
            onError: (err) => {
                const msg = err.reason || Object.values(err)[0] || t('common.error');
                errorToast(msg);
            },
        });
    }

    // ─── Status change dispatcher ─────────────────────────────────────────────
    async function changeStatus(action) {
        // "hold" opens the reason modal; confirmHold() fires the POST
        if (action === 'hold') {
            holdReason.value = '';
            showHoldModal.value = true;
            return;
        }

        // "complete" opens the vehicle exit modal; confirmExit() fires the POST
        if (action === 'complete') {
            exitDate.value = new Date().toISOString().substring(0, 10);
            exitNotes.value = '';
            isDeferred.value = workOrderBalance.value > 0;
            dueDate.value = '';
            showExitModal.value = true;
            return;
        }

        const routeMap = {
            start:    { routeName: 'work-orders.start',    type: 'success' },
            resume:   { routeName: 'work-orders.resume',   type: 'success' },
            complete: { routeName: 'work-orders.complete',  type: 'success' },
            cancel:   { routeName: 'work-orders.cancel',    type: 'danger'  },
        };

        const config = routeMap[action];
        if (!config) return;

        const labelKey = {
            start:    'work_orders.actions.start_work',
            resume:   'work_orders.actions.resume_work',
            complete: 'work_orders.actions.complete',
            cancel:   'work_orders.actions.cancel',
        }[action];

        const confirmed = await confirm({
            title: t(labelKey) || action,
            message: t('work_orders.messages.confirm_status_change'),
            confirmText: t('common.confirm'),
            cancelText: t('common.cancel'),
            type: config.type,
        });

        if (confirmed) {
            router.post(route(config.routeName, workOrder.id), {}, {
                onSuccess: () => success(t('common.saved_success')),
                onError: (err) => {
                    const msg = err.message || Object.values(err)[0] || t('common.error');
                    errorToast(msg);
                },
            });
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
