import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

/**
 * Manages work-order note state and interactions.
 *
 * Responsibilities:
 * - `allNotes` — derives the flat, sorted note list for WorkOrderNotesTab.
 *   The tab owns its own `viewMode` + `searchQuery`; Show.vue owns `allNotes`
 *   because the `service_title_formatted` field needs access to the work-order
 *   tree (work_order_item → service → localized name).
 * - Note CRUD: add, delete.
 * - Service modal plumbing: opens WorkOrderServiceModal in notes/technicians/parts tab.
 */
export function useWorkOrderNotes({ workOrder, services }) {
    const { t } = useI18n();
    const { getName } = useLocalized();
    const { success } = useToast();
    const { confirm } = useConfirm();

    const getWorkOrder = () => typeof workOrder === 'function' ? workOrder() : workOrder;

    // ─── State ────────────────────────────────────────────────────────────────
    const showAddNoteModal = ref(false);
    const newNoteContent = ref('');
    const isSubmittingNote = ref(false);

    // Service modal plumbing
    const showServiceModal = ref(false);
    const showItemModal = ref(false);
    const selectedItemId = ref(null);
    const selectedDepartmentId = ref(null);
    const serviceModalInitialTab = ref('service');

    // ─── Computed ─────────────────────────────────────────────────────────────
    /**
     * Flat, sorted list of general notes with localized service titles.
     * Notes from individual service items are NOT included here — the
     * WorkOrderNotesTab handles them via the @open-service-notes event.
     */
    const allNotes = computed(() => {
        const notes = getWorkOrder()?.general_notes || getWorkOrder()?.generalNotes || [];
        return notes
            .map(note => {
                const serviceTitle = note.work_order_item
                    ? (getName(note.work_order_item.service) || note.work_order_item.title)
                    : '';
                return {
                    id: note.id,
                    content: note.content,
                    created_at: note.created_at,
                    user: note.user,
                    item_id: note.work_order_item_id || null,
                    service_title_formatted: serviceTitle || t('work_orders.general_note'),
                };
            })
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    });

    // Services filtered by the currently selected department
    const departmentServices = computed(() => {
        if (!selectedDepartmentId.value) return [];
        if (selectedDepartmentId.value === 'packages') {
            return (services || []).filter(s => s.type === 'package');
        }
        return (services || []).filter(
            s => s.department_id === selectedDepartmentId.value && s.type !== 'package'
        );
    });

    const selectedItem = computed(() => {
        if (!selectedItemId.value) return null;
        return getWorkOrder()?.items?.find(i => i.id === selectedItemId.value) || null;
    });

    // ─── Note Actions ────────────────────────────────────────────────────────
    function handleAddNote() {
        if (!newNoteContent.value.trim()) return;

        isSubmittingNote.value = true;
        router.post(route('work-orders.notes.store', { work_order: getWorkOrder().id }), {
            content: newNoteContent.value,
        }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                newNoteContent.value = '';
                showAddNoteModal.value = false;
                success(t('common.saved_success'));
            },
            onFinish: () => {
                isSubmittingNote.value = false;
            },
        });
    }

    async function handleDeleteNote(itemId, noteId) {
        const confirmed = await confirm({
            title: t('common.confirm_delete_title'),
            message: t('common.confirm_delete_message'),
            confirmText: t('common.delete'),
            cancelText: t('common.cancel'),
            type: 'danger',
        });

        if (!confirmed) return;

        const deleteRoute = itemId
            ? route('work-orders.items.notes.destroy', { work_order: getWorkOrder().id, item: itemId, note: noteId })
            : route('work-orders.notes.destroy', { work_order: getWorkOrder().id, note: noteId });

        router.delete(deleteRoute, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => success(t('common.deleted_success')),
        });
    }

    // ─── Service Modal Plumbing ───────────────────────────────────────────────
    function openAddServiceModal(deptId) {
        selectedDepartmentId.value = deptId;
        selectedItemId.value = null;
        showServiceModal.value = true;
    }

    function openEditServiceModal(item) {
        selectedItemId.value = item.id;
        serviceModalInitialTab.value = 'service';
        showItemModal.value = true;
    }

    function openServiceNotesModal(itemId) {
        const item = getWorkOrder().items?.find(i => i.id === itemId);
        if (item) {
            selectedItemId.value = itemId;
            serviceModalInitialTab.value = 'notes';
            showItemModal.value = true;
        }
    }

    function openServicePartsModal(itemId) {
        const item = getWorkOrder().items?.find(i => i.id === itemId);
        if (item) {
            selectedItemId.value = itemId;
            serviceModalInitialTab.value = 'parts';
            showItemModal.value = true;
        }
    }

    function openServiceTechniciansModal(itemId) {
        const item = workOrder.items?.find(i => i.id === itemId);
        if (item) {
            selectedItemId.value = itemId;
            serviceModalInitialTab.value = 'technicians';
            showItemModal.value = true;
        }
    }

    function handlePartServiceClick(part) {
        const itemId = part.work_order_item_id || part.work_order_item?.id || part.workOrderItem?.id;
        if (itemId) openServicePartsModal(itemId);
    }

    function closeServiceModal() {
        showServiceModal.value = false;
        selectedItemId.value = null;
        selectedDepartmentId.value = null;
        serviceModalInitialTab.value = 'service';
    }

    function closeItemModal() {
        showItemModal.value = false;
        selectedItemId.value = null;
        serviceModalInitialTab.value = 'service';
    }

    function handleServiceSaved() {
        closeServiceModal();
        success(t('common.saved_success'));
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    }

    function handleItemSaved() {
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    }

    return {
        // state
        showAddNoteModal,
        newNoteContent,
        isSubmittingNote,
        showServiceModal,
        showItemModal,
        selectedItemId,
        selectedDepartmentId,
        serviceModalInitialTab,
        // computed
        allNotes,
        departmentServices,
        selectedItem,
        // note actions
        handleAddNote,
        handleDeleteNote,
        // modal plumbing
        openAddServiceModal,
        openEditServiceModal,
        openServiceNotesModal,
        openServicePartsModal,
        openServiceTechniciansModal,
        handlePartServiceClick,
        closeServiceModal,
        closeItemModal,
        handleServiceSaved,
        handleItemSaved,
    };
}
