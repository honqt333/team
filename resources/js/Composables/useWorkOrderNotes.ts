import { ref, computed, type Ref, type ComputedRef } from 'vue';
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
export interface Note {
    id: number;
    content: string;
    created_at: string;
    user?: { name?: string; [key: string]: unknown };
    work_order_item_id?: number | null;
    work_order_item?: {
        id: number;
        title: string;
        service?: { name_ar?: string; name_en?: string; [key: string]: unknown };
        [key: string]: unknown;
    } | null;
    [key: string]: unknown;
}

export interface FormattedNote {
    id: number;
    content: string;
    created_at: string;
    user: Note['user'];
    item_id: number | null;
    service_title_formatted: string;
}

export interface Service {
    id: number;
    type: string;
    department_id?: number | string;
    [key: string]: unknown;
}

export interface WorkOrderItem {
    id: number;
    [key: string]: unknown;
}

export interface WorkOrderNoteItem extends WorkOrderItem {
    work_order_item_id?: number;
    work_order_item?: { id: number } | null;
    workOrderItem?: { id: number } | null;
}

export interface WorkOrderNotesLite {
    id: number;
    general_notes?: Note[];
    generalNotes?: Note[];
    items?: WorkOrderItem[];
    [key: string]: unknown;
}

export type WorkOrderInput = WorkOrderNotesLite | (() => WorkOrderNotesLite);

export type ServiceModalTab = 'service' | 'notes' | 'parts' | 'technicians';

export interface UseWorkOrderNotesReturn {
    // state
    showAddNoteModal: Ref<boolean>;
    newNoteContent: Ref<string>;
    isSubmittingNote: Ref<boolean>;
    showServiceModal: Ref<boolean>;
    showItemModal: Ref<boolean>;
    selectedItemId: Ref<number | null>;
    selectedDepartmentId: Ref<number | string | null>;
    serviceModalInitialTab: Ref<ServiceModalTab>;
    // computed
    allNotes: ComputedRef<FormattedNote[]>;
    departmentServices: ComputedRef<Service[]>;
    selectedItem: ComputedRef<WorkOrderItem | null>;
    // note actions
    handleAddNote: () => void;
    handleDeleteNote: (itemId: number | null, noteId: number) => Promise<void>;
    // modal plumbing
    openAddServiceModal: (deptId: number | string) => void;
    openEditServiceModal: (item: WorkOrderItem) => void;
    openServiceModal: (itemId: number) => void;
    openServiceNotesModal: (itemId: number) => void;
    openServicePartsModal: (itemId: number) => void;
    openServiceTechniciansModal: (itemId: number) => void;
    handlePartServiceClick: (part: WorkOrderNoteItem) => void;
    closeServiceModal: () => void;
    closeItemModal: () => void;
    handleServiceSaved: () => void;
    handleItemSaved: () => void;
}

export function useWorkOrderNotes(params: {
    workOrder: WorkOrderInput;
    services?: Service[];
}): UseWorkOrderNotesReturn {
    const { t } = useI18n();
    const { getName } = useLocalized();
    const { success } = useToast();
    const { confirm } = useConfirm();

    const { workOrder, services } = params;

    const getWorkOrder = (): WorkOrderNotesLite =>
        typeof workOrder === 'function' ? (workOrder as () => WorkOrderNotesLite)() : workOrder;

    // ─── State ────────────────────────────────────────────────────────────────
    const showAddNoteModal = ref(false);
    const newNoteContent = ref('');
    const isSubmittingNote = ref(false);

    // Service modal plumbing
    const showServiceModal = ref(false);
    const showItemModal = ref(false);
    const selectedItemId = ref<number | null>(null);
    const selectedDepartmentId = ref<number | string | null>(null);
    const serviceModalInitialTab = ref<ServiceModalTab>('service');

    // ─── Computed ─────────────────────────────────────────────────────────────
    /**
     * Flat, sorted list of general notes with localized service titles.
     * Notes from individual service items are NOT included here — the
     * WorkOrderNotesTab handles them via the @open-service-notes event.
     */
    const allNotes = computed<FormattedNote[]>(() => {
        const notes = getWorkOrder()?.general_notes || getWorkOrder()?.generalNotes || [];
        return notes
            .map((note: Note): FormattedNote => {
                const serviceTitle = note.work_order_item
                    ? getName(note.work_order_item.service) || note.work_order_item.title
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
            .sort(
                (a: FormattedNote, b: FormattedNote) =>
                    new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
            );
    });

    // Services filtered by the currently selected department
    const departmentServices = computed<Service[]>(() => {
        if (!selectedDepartmentId.value) return [];
        if (selectedDepartmentId.value === 'packages') {
            return (services || []).filter((s) => s.type === 'package');
        }
        return (services || []).filter(
            (s) => s.department_id === selectedDepartmentId.value && s.type !== 'package'
        );
    });

    const selectedItem = computed<WorkOrderItem | null>(() => {
        if (!selectedItemId.value) return null;
        return getWorkOrder()?.items?.find((i) => i.id == selectedItemId.value) || null;
    });

    // ─── Note Actions ────────────────────────────────────────────────────────
    function handleAddNote(): void {
        if (!newNoteContent.value.trim()) return;

        isSubmittingNote.value = true;
        router.post(
            route('work-orders.notes.store', { work_order: getWorkOrder().id }),
            {
                content: newNoteContent.value,
            },
            {
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
            }
        );
    }

    async function handleDeleteNote(itemId: number | null, noteId: number): Promise<void> {
        const confirmed = await confirm({
            title: t('common.confirm_delete_title'),
            message: t('common.confirm_delete_message'),
            confirmText: t('common.delete'),
            cancelText: t('common.cancel'),
            type: 'danger',
        });

        if (!confirmed) return;

        const deleteRoute = itemId
            ? route('work-orders.items.notes.destroy', {
                  work_order: getWorkOrder().id,
                  item: itemId,
                  note: noteId,
              })
            : route('work-orders.notes.destroy', { work_order: getWorkOrder().id, note: noteId });

        router.delete(deleteRoute, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => success(t('common.deleted_success')),
        });
    }

    // ─── Service Modal Plumbing ───────────────────────────────────────────────
    function openAddServiceModal(deptId: number | string): void {
        selectedDepartmentId.value = deptId;
        selectedItemId.value = null;
        showServiceModal.value = true;
    }

    function openEditServiceModal(item: WorkOrderItem): void {
        selectedItemId.value = item.id;
        serviceModalInitialTab.value = 'service';
        showItemModal.value = true;
    }

    function openServiceModal(itemId: number): void {
        const item = getWorkOrder().items?.find((i) => i.id == itemId);
        if (item) {
            selectedItemId.value = itemId;
            serviceModalInitialTab.value = 'service';
            showItemModal.value = true;
        }
    }

    function openServiceNotesModal(itemId: number): void {
        const item = getWorkOrder().items?.find((i) => i.id == itemId);
        if (item) {
            selectedItemId.value = itemId;
            serviceModalInitialTab.value = 'notes';
            showItemModal.value = true;
        }
    }

    function openServicePartsModal(itemId: number): void {
        const item = getWorkOrder().items?.find((i) => i.id == itemId);
        if (item) {
            selectedItemId.value = itemId;
            serviceModalInitialTab.value = 'parts';
            showItemModal.value = true;
        }
    }

    function openServiceTechniciansModal(itemId: number): void {
        const item = getWorkOrder().items?.find((i) => i.id == itemId);
        if (item) {
            selectedItemId.value = itemId;
            serviceModalInitialTab.value = 'technicians';
            showItemModal.value = true;
        }
    }

    function handlePartServiceClick(part: WorkOrderNoteItem): void {
        const itemId =
            part.work_order_item_id || part.work_order_item?.id || part.workOrderItem?.id;
        if (itemId) openServicePartsModal(itemId);
    }

    function closeServiceModal(): void {
        showServiceModal.value = false;
        selectedItemId.value = null;
        selectedDepartmentId.value = null;
        serviceModalInitialTab.value = 'service';
    }

    function closeItemModal(): void {
        showItemModal.value = false;
        selectedItemId.value = null;
        serviceModalInitialTab.value = 'service';
    }

    function handleServiceSaved(): void {
        closeServiceModal();
        success(t('common.saved_success'));
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    }

    function handleItemSaved(): void {
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
        openServiceModal,
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
