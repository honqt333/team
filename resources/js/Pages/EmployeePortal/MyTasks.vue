<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    workOrders: Object,
    statusFilter: String,
    counts: Object,
    filters: Object,
    employee: Object,
    authUserId: [Number, String],
});

const toast = useToast();

const currentUserId = computed(() => Number(props.authUserId || props.employee?.user_id));

// Global ticking timestamp for all live timers (updates every second)
const now = ref(Date.now());
let timerTickInterval = null;

onMounted(() => {
    timerTickInterval = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (timerTickInterval) {
        clearInterval(timerTickInterval);
    }
});

// Search and filter state
const searchQuery = ref(props.filters?.search || '');
const currentFilter = ref(props.statusFilter || 'active');
const loadingItems = reactive(new Set());

// Tab options
const priorityOptions = computed(() =>
    ['low', 'normal', 'high', 'urgent'].map((value) => ({
        value,
        label: t(`employee_portal.priorities.${value}`),
    }))
);

const tabs = computed(() => [
    { key: 'active', label: 'النشطة', count: props.counts?.active ?? 0, icon: '⚡' },
    { key: 'in_progress', label: 'جاري العمل', count: props.counts?.in_progress ?? 0, icon: '⚙️' },
    { key: 'pending', label: 'قيد الانتظار', count: props.counts?.pending ?? 0, icon: '⏳' },
    {
        key: 'ready_for_qc',
        label: 'فحص الجودة',
        count: props.counts?.ready_for_qc ?? 0,
        icon: '🔍',
    },
    { key: 'completed', label: 'المكتملة', count: props.counts?.completed ?? 0, icon: '✅' },
    { key: 'all', label: 'جميع المهام', count: props.counts?.all ?? 0, icon: '📋' },
]);

let searchTimeout = null;
const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const applyFilters = () => {
    router.get(
        '/app/my/tasks',
        {
            status: currentFilter.value,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const setFilter = (status) => {
    currentFilter.value = status;
    applyFilters();
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

// Item state helpers
const isItemLoading = (itemId) => loadingItems.has(itemId);

const setItemLoading = (itemId, loading) => {
    if (loading) {
        loadingItems.add(itemId);
    } else {
        loadingItems.delete(itemId);
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending:
            'bg-slate-100 text-slate-800 dark:bg-slate-800/80 dark:text-slate-200 border-slate-200 dark:border-slate-700',
        open: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        in_progress:
            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        on_hold:
            'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 border-orange-200 dark:border-orange-800',
        ready_for_qc: 'bg-purple-100 text-purple-800 border-purple-200',
        completed:
            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        done: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        cancelled:
            'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'قيد الانتظار',
        open: 'مفتوح',
        in_progress: 'جاري العمل',
        on_hold: 'معلق',
        ready_for_qc: 'فحص الجودة',
        completed: 'مكتمل',
        done: 'منجز',
        cancelled: 'ملغي',
    };
    return labels[status] || status;
};

const getPriorityColor = (priority) => {
    const colors = {
        low: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        normal: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        high: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        urgent: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 font-bold',
    };
    return colors[priority] || 'bg-gray-100 text-gray-700';
};

const getPriorityLabel = (priority) => {
    const labels = {
        low: 'منخفضة',
        normal: 'عادية',
        high: 'عالية',
        urgent: 'عاجلة',
    };
    return labels[priority] || 'عادية';
};

// Date formatters
const formatDate = (date) => {
    if (!date) return '';
    try {
        return new Date(date).toLocaleDateString('ar-SA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return date;
    }
};

const formatDateTime = (date) => {
    if (!date) return '';
    try {
        return new Date(date).toLocaleString('ar-SA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return date;
    }
};

// Multi-technician helpers
const myPivot = (item) => {
    if (!item.technicians || !currentUserId.value) return null;
    const tech = item.technicians.find((t) => Number(t.id) === currentUserId.value);
    return tech?.pivot || null;
};

const isMyPartCompleted = (item) => {
    return Boolean(myPivot(item)?.completed_at);
};

const otherAssignedTechs = (item) => {
    if (!item.technicians) return [];
    return item.technicians.filter((t) => Number(t.id) !== currentUserId.value);
};

const otherPendingTechs = (item) => {
    return otherAssignedTechs(item).filter((t) => !t.pivot?.completed_at);
};

// Timer calculations (integrated with WorkOrderLaborService sessions)
const myActiveLaborEntry = (item) => {
    const list = item.labor_entries || item.laborEntries || [];
    return list.find((e) => ['running', 'paused'].includes(e.status)) || null;
};

const isTimerRunning = (item) => {
    const active = myActiveLaborEntry(item);
    if (active) return active.status === 'running';
    return Boolean(item.started_at && !item.paused_at && item.status === 'in_progress');
};

const isTimerPaused = (item) => {
    const active = myActiveLaborEntry(item);
    if (active) return active.status === 'paused';
    return Boolean(item.started_at && item.paused_at);
};

const getElapsedTime = (item) => {
    const active = myActiveLaborEntry(item);
    if (active) {
        const start = new Date(active.started_at).getTime();
        const pausedTotalMs = (Number(active.paused_duration_seconds) || 0) * 1000;
        let liveMs = now.value - start - pausedTotalMs;
        if (active.status === 'paused' && active.paused_at) {
            liveMs -= now.value - new Date(active.paused_at).getTime();
        }
        const totalSeconds = Math.max(0, Math.floor(liveMs / 1000));
        const h = Math.floor(totalSeconds / 3600)
            .toString()
            .padStart(2, '0');
        const m = Math.floor((totalSeconds % 3600) / 60)
            .toString()
            .padStart(2, '0');
        const s = (totalSeconds % 60).toString().padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    if (!item.started_at) return '00:00:00';
    const start = new Date(item.started_at).getTime();
    const pausedDurationMs = (Number(item.paused_duration) || 0) * 1000;
    let end = now.value;
    if (item.completed_at) {
        end = new Date(item.completed_at).getTime();
    } else if (item.paused_at) {
        end = new Date(item.paused_at).getTime();
    }
    let elapsedMs = Math.max(0, end - start - pausedDurationMs);
    const totalSeconds = Math.floor(elapsedMs / 1000);
    const h = Math.floor(totalSeconds / 3600)
        .toString()
        .padStart(2, '0');
    const m = Math.floor((totalSeconds % 3600) / 60)
        .toString()
        .padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
};

// Checklist helpers
const parseChecklist = (checklist) => {
    if (!checklist) return [];
    if (Array.isArray(checklist)) return checklist;
    try {
        return JSON.parse(checklist);
    } catch {
        return [];
    }
};

const getChecklistSummary = (item) => {
    const list = parseChecklist(item.checklist);
    if (!list.length) return null;
    const completed = list.filter((i) => i.completed).length;
    return {
        completed,
        total: list.length,
        percentage: Math.round((completed / list.length) * 100),
    };
};

// Action functions
const startTimer = async (workOrder, item) => {
    const itemId = `${workOrder.id}-${item.id}`;
    setItemLoading(itemId, true);

    try {
        const response = await axios.post(
            `/app/my/work-orders/${workOrder.id}/items/${item.id}/timer/start`
        );
        Object.assign(item, response.data.item);
        if (workOrder.status === 'open' || workOrder.status === 'draft') {
            workOrder.status = 'in_progress';
        }
        toast.success(response.data.success || 'تم بدء المؤقت وتسجيل ساعات العمل');
    } catch (error) {
        toast.error(
            error.response?.data?.error || error.response?.data?.message || 'فشل بدء المؤقت'
        );
    } finally {
        setItemLoading(itemId, false);
    }
};

const pauseTimer = async (workOrder, item) => {
    const itemId = `${workOrder.id}-${item.id}`;
    setItemLoading(itemId, true);

    try {
        const response = await axios.post(
            `/app/my/work-orders/${workOrder.id}/items/${item.id}/timer/pause`
        );
        Object.assign(item, response.data.item);
        toast.success(response.data.success || 'تم إيقاف المؤقت مؤقتاً');
    } catch (error) {
        toast.error(
            error.response?.data?.error || error.response?.data?.message || 'فشل إيقاف المؤقت'
        );
    } finally {
        setItemLoading(itemId, false);
    }
};

const resumeTimer = async (workOrder, item) => {
    const itemId = `${workOrder.id}-${item.id}`;
    setItemLoading(itemId, true);

    try {
        const response = await axios.post(
            `/app/my/work-orders/${workOrder.id}/items/${item.id}/timer/resume`
        );
        Object.assign(item, response.data.item);
        toast.success(response.data.success || 'تم استئناف المؤقت');
    } catch (error) {
        toast.error(
            error.response?.data?.error || error.response?.data?.message || 'فشل استئناف المؤقت'
        );
    } finally {
        setItemLoading(itemId, false);
    }
};

const updateStatus = async (workOrder, item, newStatus) => {
    const itemId = `${workOrder.id}-${item.id}`;
    setItemLoading(itemId, true);

    try {
        const response = await axios.post(
            `/app/my/work-orders/${workOrder.id}/items/${item.id}/status`,
            {
                status: newStatus,
            }
        );
        Object.assign(item, response.data.item);
        toast.success(response.data.success || 'تم تغيير حالة الخدمة');
    } catch (error) {
        toast.error(
            error.response?.data?.error || error.response?.data?.message || 'فشل تغيير الحالة'
        );
    } finally {
        setItemLoading(itemId, false);
    }
};

const completeItem = async (workOrder, item) => {
    const pendingOthers = otherPendingTechs(item);
    let confirmMsg = 'هل أنت متأكد من تأكيد إكمال هذه الخدمة؟';

    if (pendingOthers.length > 0) {
        const names = pendingOthers.map((t) => t.name).join('، ');
        confirmMsg = `هذه الخدمة مشتركة مع فنيين آخرين (${names}).\nهل تؤكد إنهاء دورك فيها؟`;
    }

    if (!confirm(confirmMsg)) return;

    const itemId = `${workOrder.id}-${item.id}`;
    setItemLoading(itemId, true);

    try {
        const response = await axios.post(
            `/app/my/work-orders/${workOrder.id}/items/${item.id}/complete`,
            {
                confirm: 'complete',
            }
        );
        Object.assign(item, response.data.item);
        toast.success(response.data.success || 'تم إنهاء المهمة');
    } catch (error) {
        toast.error(
            error.response?.data?.error || error.response?.data?.message || 'فشل إكمال الخدمة'
        );
    } finally {
        setItemLoading(itemId, false);
    }
};

const updatePriority = async (workOrder, item, newPriority) => {
    const itemId = `${workOrder.id}-${item.id}`;
    setItemLoading(itemId, true);

    try {
        const response = await axios.post(
            `/app/my/work-orders/${workOrder.id}/items/${item.id}/priority`,
            {
                priority: newPriority,
            }
        );
        item.priority = newPriority;
        toast.success(response.data.success || 'تم تحديث الأولوية');
    } catch (error) {
        toast.error(
            error.response?.data?.error || error.response?.data?.message || 'فشل تحديث الأولوية'
        );
    } finally {
        setItemLoading(itemId, false);
    }
};

// Modals State
const showNotesModal = ref(false);
const showPhotosModal = ref(false);
const showChecklistModal = ref(false);
const showLightboxModal = ref(false);
const lightboxImage = ref('');

const activeWorkOrder = ref(null);
const activeItem = ref(null);

// Notes Modal
const notesInput = ref('');
const isSavingNotes = ref(false);

const openNotesModal = (workOrder, item) => {
    activeWorkOrder.value = workOrder;
    activeItem.value = item;
    notesInput.value = item.technician_notes || '';
    showNotesModal.value = true;
};

const closeNotesModal = () => {
    showNotesModal.value = false;
    activeItem.value = null;
    activeWorkOrder.value = null;
    notesInput.value = '';
};

const saveNotes = async () => {
    if (!activeItem.value || !activeWorkOrder.value) return;
    isSavingNotes.value = true;

    try {
        const response = await axios.post(
            `/app/my/work-orders/${activeWorkOrder.value.id}/items/${activeItem.value.id}/notes`,
            { technician_notes: notesInput.value }
        );
        Object.assign(activeItem.value, response.data.item);
        toast.success(response.data.success || 'تم حفظ الملاحظات وتحديث كرت العمل الرئيسي');
        closeNotesModal();
    } catch (error) {
        toast.error(error.response?.data?.error || 'فشل حفظ الملاحظات');
    } finally {
        isSavingNotes.value = false;
    }
};

// Photos Modal
const photoType = ref('general');
const selectedFiles = ref([]);
const isUploadingPhotos = ref(false);
const isDeletingPhotoId = ref(null);
const fileInputRef = ref(null);

const photoTypeOptions = computed(() => [
    { value: 'general', label: t('employee_portal.photo_types.general') },
    { value: 'before', label: t('employee_portal.photo_types.before') },
    { value: 'after', label: t('employee_portal.photo_types.after') },
    { value: 'damage', label: t('employee_portal.photo_types.damage') },
]);

const openPhotosModal = (workOrder, item) => {
    activeWorkOrder.value = workOrder;
    activeItem.value = item;
    selectedFiles.value = [];
    photoType.value = 'general';
    showPhotosModal.value = true;
};

const closePhotosModal = () => {
    showPhotosModal.value = false;
    activeItem.value = null;
    activeWorkOrder.value = null;
    selectedFiles.value = [];
};

const onFileSelect = (event) => {
    const files = Array.from(event.target.files || []);
    selectedFiles.value = files;
};

const uploadPhotos = async () => {
    if (!activeItem.value || !activeWorkOrder.value || !selectedFiles.value.length) return;
    isUploadingPhotos.value = true;

    const formData = new FormData();
    selectedFiles.value.forEach((file) => {
        formData.append('files[]', file);
    });
    formData.append('type', photoType.value);

    try {
        const response = await axios.post(
            `/app/my/work-orders/${activeWorkOrder.value.id}/items/${activeItem.value.id}/media`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        activeItem.value.photos = response.data.photos || [];
        selectedFiles.value = [];
        if (fileInputRef.value) fileInputRef.value.value = '';
        toast.success(response.data.success || 'تم رفع الصور بنجاح');
    } catch (error) {
        toast.error(
            error.response?.data?.message || error.response?.data?.error || 'فشل رفع الصور'
        );
    } finally {
        isUploadingPhotos.value = false;
    }
};

const deletePhoto = async (photo) => {
    if (!confirm('هل أنت متأكد من حذف هذه الصورة؟')) return;
    isDeletingPhotoId.value = photo.id;

    try {
        const response = await axios.delete(
            `/app/my/work-orders/${activeWorkOrder.value.id}/items/${activeItem.value.id}/photos/${photo.id}`
        );
        activeItem.value.photos = response.data.photos || [];
        toast.success(response.data.success || 'تم حذف الصورة بنجاح');
    } catch (error) {
        toast.error(
            error.response?.data?.error || error.response?.data?.message || 'فشل حذف الصورة'
        );
    } finally {
        isDeletingPhotoId.value = null;
    }
};

const openLightbox = (url) => {
    lightboxImage.value = url;
    showLightboxModal.value = true;
};

// Checklist Modal
const checklistItems = ref([]);
const newChecklistText = ref('');
const isSavingChecklist = ref(false);

const openChecklistModal = (workOrder, item) => {
    activeWorkOrder.value = workOrder;
    activeItem.value = item;
    checklistItems.value = JSON.parse(JSON.stringify(parseChecklist(item.checklist)));
    newChecklistText.value = '';
    showChecklistModal.value = true;
};

const closeChecklistModal = () => {
    showChecklistModal.value = false;
    activeItem.value = null;
    activeWorkOrder.value = null;
    checklistItems.value = [];
};

const addChecklistItem = () => {
    const text = newChecklistText.value.trim();
    if (!text) return;
    checklistItems.value.push({
        text,
        completed: false,
    });
    newChecklistText.value = '';
};

const removeChecklistItem = (index) => {
    checklistItems.value.splice(index, 1);
};

const toggleChecklistItem = (index) => {
    checklistItems.value[index].completed = !checklistItems.value[index].completed;
};

const saveChecklist = async () => {
    if (!activeItem.value || !activeWorkOrder.value) return;
    isSavingChecklist.value = true;

    try {
        const response = await axios.post(
            `/app/my/work-orders/${activeWorkOrder.value.id}/items/${activeItem.value.id}/checklist`,
            { checklist: checklistItems.value }
        );
        Object.assign(activeItem.value, response.data.item);
        toast.success(response.data.success || 'تم حفظ قائمة الفحص');
        closeChecklistModal();
    } catch (error) {
        toast.error(error.response?.data?.error || 'فشل حفظ قائمة الفحص');
    } finally {
        isSavingChecklist.value = false;
    }
};
</script>

<template>
    <EmployeePortalLayout :title="$t('employee_portal.my_tasks')">
        <div class="space-y-6 pb-12">
            <!-- Header Banner -->
            <div
                class="bg-gradient-to-r from-action-primary via-purple-700 to-action-primary rounded-2xl p-6 text-white shadow-md relative overflow-hidden"
            >
                <div
                    class="absolute -left-12 -bottom-12 w-48 h-48 bg-white/5 rounded-full blur-2xl"
                ></div>
                <div
                    class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4"
                >
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="p-2.5 bg-white/15 backdrop-blur-sm rounded-xl text-2xl">
                                🔧
                            </span>
                            <div>
                                <h1 class="text-2xl font-bold tracking-tight">
                                    {{ $t('employee_portal.my_tasks') }}
                                </h1>
                                <p class="text-purple-200 text-sm mt-0.5">
                                    مهام الصيانة الموكلة للفني
                                    {{ employee?.name_ar || employee?.name }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div class="w-full md:w-80 relative">
                        <input
                            v-model="searchQuery"
                            @input="handleSearch"
                            type="text"
                            :placeholder="$t('employee_portal.search_tasks_placeholder')"
                            class="w-full bg-white/10 text-white placeholder-purple-200/70 border border-white/20 rounded-xl px-4 py-2.5 pl-10 focus:outline-none focus:ring-2 focus:ring-white/40 focus:bg-white/20 transition-all text-sm"
                        />
                        <button
                            v-if="searchQuery"
                            @click="clearSearch"
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-purple-200 hover:text-white"
                        >
                            ✕
                        </button>
                        <svg
                            v-else
                            class="w-4 h-4 text-purple-200 absolute left-3.5 top-1/2 -translate-y-1/2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Stats Quick Counters -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    @click="setFilter(tab.key)"
                    :class="[
                        'p-4 rounded-xl border text-start transition-all duration-200 flex flex-col justify-between',
                        currentFilter === tab.key
                            ? 'bg-purple-50 border-purple-500 shadow-sm ring-2 ring-purple-500/30'
                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-purple-300',
                    ]"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-lg">{{ tab.icon }}</span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded-full text-xs font-bold',
                                currentFilter === tab.key
                                    ? 'bg-purple-600 text-white'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            ]"
                        >
                            {{ tab.count }}
                        </span>
                    </div>
                    <div class="mt-3">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ tab.label }}
                        </p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">
                            {{ tab.count }}
                        </p>
                    </div>
                </button>
            </div>

            <!-- Work Orders & Tasks List -->
            <div v-if="workOrders.data.length > 0" class="space-y-6">
                <div
                    v-for="workOrder in workOrders.data"
                    :key="workOrder.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <!-- Work Order Card Header: Full Vehicle & Customer Inheritance -->
                    <div
                        class="p-5 bg-gray-50/80 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <!-- Left: Work Order Code & Vehicle Title -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <span
                                        class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-sm"
                                    >
                                        WO
                                    </span>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                                        {{ workOrder.code }}
                                    </h3>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                        :class="getStatusColor(workOrder.status)"
                                    >
                                        {{ getStatusLabel(workOrder.status) }}
                                    </span>
                                    <span class="text-xs text-gray-400 font-medium">
                                        📅 {{ formatDate(workOrder.created_at) }}
                                    </span>
                                </div>

                                <!-- Inherited Vehicle Details -->
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs">
                                    <!-- Full Vehicle Name / Make & Model -->
                                    <div
                                        class="flex items-center gap-1.5 font-bold text-gray-900 dark:text-white"
                                    >
                                        <span class="text-base">🚗</span>
                                        <span>
                                            {{
                                                workOrder.vehicle?.display_name ||
                                                (workOrder.vehicle?.make?.name
                                                    ? `${workOrder.vehicle.make.name} ${workOrder.vehicle.model?.name || ''}`
                                                    : 'مركبة غير محددة')
                                            }}
                                        </span>
                                    </div>

                                    <!-- License Plate -->
                                    <div
                                        v-if="workOrder.vehicle?.plate_number"
                                        class="px-2.5 py-0.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-mono font-bold text-gray-800 dark:text-gray-100 text-xs shadow-2xs"
                                        dir="ltr"
                                    >
                                        {{ workOrder.vehicle.plate_number }}
                                    </div>

                                    <!-- Color -->
                                    <div
                                        v-if="workOrder.vehicle?.color"
                                        class="text-gray-600 dark:text-gray-400 flex items-center gap-1"
                                    >
                                        <span>🎨 اللون:</span>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ workOrder.vehicle.color }}
                                        </span>
                                    </div>

                                    <!-- VIN / Chassis -->
                                    <div
                                        v-if="workOrder.vehicle?.vin"
                                        class="text-gray-600 dark:text-gray-400 flex items-center gap-1"
                                    >
                                        <span>🔢 الشاصي:</span>
                                        <span
                                            class="font-mono text-gray-800 dark:text-gray-200"
                                            dir="ltr"
                                        >
                                            {{ workOrder.vehicle.vin }}
                                        </span>
                                    </div>

                                    <!-- Odometer / Mileage -->
                                    <div
                                        v-if="
                                            workOrder.odometer ||
                                            workOrder.mileage ||
                                            workOrder.vehicle?.odometer
                                        "
                                        class="text-gray-600 dark:text-gray-400 flex items-center gap-1 font-medium"
                                    >
                                        <span>⏱️ العداد:</span>
                                        <span class="text-gray-900 dark:text-white font-bold">
                                            {{
                                                Number(
                                                    workOrder.odometer ||
                                                        workOrder.mileage ||
                                                        workOrder.vehicle?.odometer
                                                ).toLocaleString()
                                            }}
                                        </span>
                                        <span>كم</span>
                                    </div>
                                </div>

                                <!-- Customer Details -->
                                <div
                                    class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600 dark:text-gray-400 pt-1"
                                >
                                    <span class="flex items-center gap-1">
                                        <span>👤 العميل:</span>
                                        <span class="font-bold text-gray-800 dark:text-gray-200">
                                            {{
                                                workOrder.vehicle?.customer?.name_ar ||
                                                workOrder.vehicle?.customer?.name_en ||
                                                workOrder.customer?.name ||
                                                workOrder.contact_name ||
                                                'عميل غير مسجل'
                                            }}
                                        </span>
                                    </span>

                                    <a
                                        v-if="
                                            workOrder.vehicle?.customer?.phone ||
                                            workOrder.contact_phone
                                        "
                                        :href="`tel:${workOrder.vehicle?.customer?.phone || workOrder.contact_phone}`"
                                        class="flex items-center gap-1 text-action-primary font-bold hover:underline"
                                        dir="ltr"
                                    >
                                        <span>📞</span>
                                        <span>
                                            {{
                                                workOrder.vehicle?.customer?.phone ||
                                                workOrder.contact_phone
                                            }}
                                        </span>
                                    </a>
                                </div>
                            </div>

                            <!-- Right: Tasks Count Badge -->
                            <div
                                class="flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-2xs"
                            >
                                <span>المهام الموكلة لك:</span>
                                <span
                                    class="w-6 h-6 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold"
                                >
                                    {{ workOrder.items?.length || 0 }}
                                </span>
                            </div>
                        </div>

                        <!-- Customer Complaint & Diagnosis (if provided) -->
                        <div
                            v-if="workOrder.customer_complaint || workOrder.initial_assessment"
                            class="mt-3.5 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs"
                        >
                            <div
                                v-if="workOrder.customer_complaint"
                                class="p-2.5 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-900/30 text-amber-900 dark:text-amber-200 flex items-start gap-1.5"
                            >
                                <span class="font-bold shrink-0">⚠️ شكوى العميل:</span>
                                <span>{{ workOrder.customer_complaint }}</span>
                            </div>
                            <div
                                v-if="workOrder.initial_assessment"
                                class="p-2.5 rounded-xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/50 dark:border-blue-900/30 text-blue-900 dark:text-blue-200 flex items-start gap-1.5"
                            >
                                <span class="font-bold shrink-0">🔍 الفحص المبدئي:</span>
                                <span>{{ workOrder.initial_assessment }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Work Order Items (Technician Tasks) -->
                    <div class="p-5 space-y-4">
                        <div
                            v-for="item in workOrder.items"
                            :key="item.id"
                            class="bg-gray-50/70 dark:bg-gray-900/40 rounded-2xl p-4 sm:p-5 border border-gray-200/80 dark:border-gray-700 flex flex-col gap-4 transition-all hover:border-purple-300"
                        >
                            <!-- Task Row 1: Title, Status Badges & Timer -->
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex-1 min-w-[280px]">
                                    <div class="flex items-center gap-2.5 flex-wrap mb-1.5">
                                        <h4
                                            class="font-bold text-gray-900 dark:text-white text-base sm:text-lg"
                                        >
                                            {{ item.title }}
                                        </h4>

                                        <!-- Overall Status Badge -->
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold border flex items-center gap-1"
                                            :class="getStatusColor(item.status)"
                                        >
                                            <span
                                                v-if="item.status === 'in_progress'"
                                                class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"
                                            ></span>
                                            {{ getStatusLabel(item.status) }}
                                        </span>

                                        <!-- Priority Badge -->
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                            :class="getPriorityColor(item.priority || 'normal')"
                                        >
                                            {{ getPriorityLabel(item.priority || 'normal') }}
                                        </span>

                                        <span class="text-xs text-gray-400 font-medium">
                                            الكمية: {{ item.qty || 1 }}
                                        </span>
                                    </div>

                                    <!-- Multiple Technicians Breakdown -->
                                    <div class="flex flex-wrap items-center gap-1.5 text-xs mt-2">
                                        <span
                                            class="font-semibold text-gray-600 dark:text-gray-400"
                                        >
                                            👥 فريق العمل على الخدمة:
                                        </span>
                                        <div
                                            v-for="tech in item.technicians"
                                            :key="tech.id"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg border text-xs"
                                            :class="[
                                                tech.pivot?.completed_at
                                                    ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                                            ]"
                                        >
                                            <span class="font-bold">{{ tech.name }}</span>
                                            <span
                                                v-if="Number(tech.id) === currentUserId"
                                                class="text-[10px] text-purple-600 font-bold"
                                            >
                                                (أنت)
                                            </span>
                                            <span
                                                v-if="tech.pivot?.completed_at"
                                                class="text-emerald-600 font-bold"
                                                title="أنهى دوره"
                                            >
                                                ✓
                                            </span>
                                            <span
                                                v-else
                                                class="text-amber-500 font-bold"
                                                title="قيد العمل"
                                            >
                                                ⏳
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Parts list -->
                                    <div
                                        v-if="item.parts && item.parts.length > 0"
                                        class="text-xs text-blue-600 dark:text-blue-400 flex flex-wrap items-center gap-1.5 mt-2"
                                    >
                                        <span class="font-semibold">📦 قطع الغيار:</span>
                                        <span
                                            v-for="part in item.parts"
                                            :key="part.id"
                                            class="bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-md text-[11px] border border-blue-100 dark:border-blue-800"
                                        >
                                            {{ part.name }} ({{ part.qty }})
                                        </span>
                                    </div>
                                </div>

                                <!-- Timer Digital Display (Integrated with WorkOrderLaborService) -->
                                <div
                                    class="flex items-center gap-3 bg-white dark:bg-gray-800 px-4 py-2.5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            v-if="isTimerRunning(item)"
                                            class="flex h-3 w-3 relative"
                                        >
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                            ></span>
                                            <span
                                                class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"
                                            ></span>
                                        </span>
                                        <span
                                            v-else-if="isTimerPaused(item)"
                                            class="h-3 w-3 rounded-full bg-amber-500"
                                        ></span>
                                        <span
                                            v-else
                                            class="h-3 w-3 rounded-full bg-gray-300 dark:bg-gray-600"
                                        ></span>

                                        <span
                                            class="font-mono text-base sm:text-lg font-bold text-gray-900 dark:text-white tracking-widest"
                                            dir="ltr"
                                        >
                                            {{ getElapsedTime(item) }}
                                        </span>
                                    </div>

                                    <!-- Timer Quick Controls -->
                                    <div
                                        v-if="
                                            !isMyPartCompleted(item) &&
                                            item.status !== 'completed' &&
                                            item.status !== 'cancelled'
                                        "
                                        class="flex items-center gap-1 border-r border-gray-200 dark:border-gray-700 pr-2.5"
                                    >
                                        <!-- Start -->
                                        <button
                                            v-if="!isTimerRunning(item) && !isTimerPaused(item)"
                                            type="button"
                                            @click="startTimer(workOrder, item)"
                                            :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1 disabled:opacity-50 transition-colors shadow-2xs"
                                        >
                                            <span>▶</span>
                                            بدء
                                        </button>

                                        <!-- Pause -->
                                        <button
                                            v-if="isTimerRunning(item)"
                                            type="button"
                                            @click="pauseTimer(workOrder, item)"
                                            :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white flex items-center gap-1 disabled:opacity-50 transition-colors shadow-2xs"
                                        >
                                            <span>⏸</span>
                                            إيقاف
                                        </button>

                                        <!-- Resume -->
                                        <button
                                            v-if="isTimerPaused(item)"
                                            type="button"
                                            @click="resumeTimer(workOrder, item)"
                                            :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1 disabled:opacity-50 transition-colors shadow-2xs"
                                        >
                                            <span>▶</span>
                                            استئناف
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Preview (Replaces locally and syncs with Main Work Order) -->
                            <div
                                v-if="item.technician_notes"
                                class="bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/60 rounded-xl p-3 text-xs text-amber-900 dark:text-amber-200 flex items-start justify-between gap-3"
                            >
                                <div class="flex items-start gap-2">
                                    <span class="font-bold shrink-0">
                                        📝 ملاحظة مسجلة في كرت العمل:
                                    </span>
                                    <span class="line-clamp-2">{{ item.technician_notes }}</span>
                                </div>
                                <button
                                    type="button"
                                    @click="openNotesModal(workOrder, item)"
                                    class="text-amber-700 dark:text-amber-300 font-bold hover:underline shrink-0"
                                >
                                    تعديل
                                </button>
                            </div>

                            <!-- Task Row 2: Workflow Actions, Multiple Tech Status & Tools -->
                            <div
                                class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-gray-200 dark:border-gray-700/80"
                            >
                                <!-- Primary Workflow Actions -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Technician has already completed their share -->
                                    <div
                                        v-if="isMyPartCompleted(item)"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800"
                                        >
                                            <span>✓</span>
                                            أنجزت دورك في هذه الخدمة
                                        </span>

                                        <span
                                            v-if="
                                                item.status !== 'completed' &&
                                                otherPendingTechs(item).length > 0
                                            "
                                            class="text-xs text-amber-600 dark:text-amber-400 font-medium"
                                        >
                                            (بانتظار استكمال:
                                            {{
                                                otherPendingTechs(item)
                                                    .map((t) => t.name)
                                                    .join('، ')
                                            }})
                                        </span>
                                    </div>

                                    <!-- If technician has NOT completed their share yet -->
                                    <template v-else>
                                        <!-- Start Work -->
                                        <button
                                            v-if="
                                                item.status === 'pending' ||
                                                item.status === 'on_hold'
                                            "
                                            type="button"
                                            @click="startTimer(workOrder, item)"
                                            :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                            class="px-3.5 py-2 bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold rounded-xl shadow-2xs flex items-center gap-1.5 disabled:opacity-50 transition-colors"
                                        >
                                            <span>▶</span>
                                            {{ $t('employee_portal.actions.start_work') }}
                                        </button>

                                        <!-- In Progress -> Ready for QC -->
                                        <button
                                            v-if="item.status === 'in_progress'"
                                            type="button"
                                            @click="updateStatus(workOrder, item, 'ready_for_qc')"
                                            :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                            class="px-3.5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-2xs flex items-center gap-1.5 disabled:opacity-50 transition-colors"
                                        >
                                            <span>🔍</span>
                                            {{ $t('employee_portal.actions.ready_for_qc') }}
                                        </button>

                                        <!-- Complete Item Button (Handles Single and Multiple Technicians) -->
                                        <button
                                            v-if="
                                                item.status !== 'completed' &&
                                                item.status !== 'cancelled'
                                            "
                                            type="button"
                                            @click="completeItem(workOrder, item)"
                                            :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-2xs flex items-center gap-1.5 disabled:opacity-50 transition-colors"
                                        >
                                            <span>✓</span>
                                            <span v-if="otherAssignedTechs(item).length > 0">
                                                إنهاء دوري في الخدمة
                                            </span>
                                            <span v-else>
                                                {{ $t('employee_portal.actions.complete') }}
                                            </span>
                                        </button>

                                        <!-- Put On Hold -->
                                        <button
                                            v-if="item.status === 'in_progress'"
                                            type="button"
                                            @click="updateStatus(workOrder, item, 'on_hold')"
                                            :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-xl disabled:opacity-50 transition-colors"
                                        >
                                            تعليق الخدمة
                                        </button>
                                    </template>
                                </div>

                                <!-- Secondary Tools: Notes, Photos, Checklist, Priority -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Notes Button -->
                                    <button
                                        type="button"
                                        @click="openNotesModal(workOrder, item)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center gap-1.5 transition-colors relative"
                                    >
                                        <span>📝</span>
                                        <span>{{ $t('employee_portal.notes') }}</span>
                                        <span
                                            v-if="
                                                item.technician_notes ||
                                                (item.item_notes && item.item_notes.length > 0)
                                            "
                                            class="w-2 h-2 rounded-full bg-purple-500"
                                        ></span>
                                    </button>

                                    <!-- Photos Button -->
                                    <button
                                        type="button"
                                        @click="openPhotosModal(workOrder, item)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center gap-1.5 transition-colors"
                                    >
                                        <span>📷</span>
                                        <span>{{ $t('employee_portal.photos') }}</span>
                                        <span
                                            v-if="item.photos && item.photos.length > 0"
                                            class="px-1.5 py-0.2 rounded-full bg-purple-100 text-purple-700 text-[10px] font-bold"
                                        >
                                            {{ item.photos.length }}
                                        </span>
                                    </button>

                                    <!-- Checklist Button -->
                                    <button
                                        type="button"
                                        @click="openChecklistModal(workOrder, item)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center gap-1.5 transition-colors"
                                    >
                                        <span>☑️</span>
                                        <span>{{ $t('employee_portal.checklist') }}</span>
                                        <span
                                            v-if="getChecklistSummary(item)"
                                            class="px-1.5 py-0.2 rounded-full bg-selection-bg text-selection-text text-[10px] font-bold"
                                        >
                                            {{ getChecklistSummary(item).completed }}/{{
                                                getChecklistSummary(item).total
                                            }}
                                        </span>
                                    </button>

                                    <!-- Priority Selector -->
                                    <SearchableSelect
                                        :model-value="item.priority || 'normal'"
                                        :options="priorityOptions"
                                        option-label="label"
                                        option-value="value"
                                        :disabled="isItemLoading(`${workOrder.id}-${item.id}`)"
                                        required
                                        compact
                                        @update:model-value="
                                            updatePriority(workOrder, item, $event)
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <AppPagination
                    v-if="workOrders.links && workOrders.links.length > 3"
                    class="mt-6"
                    :links="workOrders.links"
                    :from="workOrders.from"
                    :to="workOrders.to"
                    :total="workOrders.total"
                    :preserve-state="false"
                    :preserve-scroll="false"
                />
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 px-4"
            >
                <div
                    class="w-16 h-16 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-3xl mx-auto mb-4"
                >
                    📋
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{
                        searchQuery
                            ? $t('employee_portal.no_tasks_filtered')
                            : $t('employee_portal.no_tasks')
                    }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                    {{
                        searchQuery
                            ? 'جرب البحث بكلمات مختلفة أو قم بإلغاء الفلتر الحالي'
                            : 'عند إسناد كروت صيانة وخدمات لحسابك ستظهر هنا مباشرة'
                    }}
                </p>
                <button
                    v-if="searchQuery || currentFilter !== 'active'"
                    @click="
                        setFilter('active');
                        searchQuery = '';
                        applyFilters();
                    "
                    class="mt-4 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors"
                >
                    العودة للمهام النشطة
                </button>
            </div>
        </div>

        <!-- ==================== MODALS ==================== -->

        <!-- 1. Notes Modal (Synced with Main Work Order) -->
        <BaseModal :show="showNotesModal" @close="closeNotesModal" size="lg">
            <template #title>
                <div class="flex items-center gap-2">
                    <span class="text-xl">📝</span>
                    <span>الملاحظات الفنية — {{ activeItem?.title }}</span>
                </div>
            </template>

            <div class="space-y-4">
                <div
                    class="p-3 bg-purple-50 border border-purple-200 rounded-xl text-xs text-purple-800 flex items-center gap-2"
                >
                    <span>💡</span>
                    <span>
                        الملاحظة التي ستكتبها هنا ستنعكس فوراً على كرت العمل الأساسي وسجل نشاطات
                        الصيانة.
                    </span>
                </div>

                <textarea
                    v-model="notesInput"
                    rows="6"
                    placeholder="اكتب ملاحظاتك التقنية حول تنفيذ الخدمة..."
                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white shadow-sm focus:border-action-primary focus:ring-focus-ring text-sm p-3.5"
                ></textarea>

                <div class="flex justify-between items-center text-xs text-gray-400">
                    <span>الحد الأقصى: 2000 حرف</span>
                    <span>{{ notesInput.length }} / 2000</span>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="closeNotesModal"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveNotes"
                        :disabled="isSavingNotes"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        <span v-if="isSavingNotes">{{ $t('common.saving') }}</span>
                        <span v-else>{{ $t('common.save') }}</span>
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- 2. Photos Modal -->
        <BaseModal :show="showPhotosModal" @close="closePhotosModal" size="2xl">
            <template #title>
                <div class="flex items-center gap-2">
                    <span class="text-xl">📷</span>
                    <span>صور الخدمة — {{ activeItem?.title }}</span>
                </div>
            </template>

            <div class="space-y-6">
                <!-- Upload Box -->
                <div
                    class="bg-gray-50 dark:bg-gray-900/60 p-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 space-y-3"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                            {{ $t('employee_portal.upload_new_photos') }}
                        </span>
                        <!-- Photo Type Selector -->
                        <div class="flex items-center gap-1.5 text-xs">
                            <span class="text-gray-500">
                                {{ $t('employee_portal.classification') }}:
                            </span>
                            <SearchableSelect
                                v-model="photoType"
                                :options="photoTypeOptions"
                                option-label="label"
                                option-value="value"
                                compact
                                class="w-36"
                            />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <AppFileUpload
                            v-model="selectedFiles"
                            multiple
                            accept="image/*"
                            :max-size-mb="10"
                        />
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="uploadPhotos"
                                :disabled="!selectedFiles.length || isUploadingPhotos"
                                class="w-full sm:w-auto px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-lg disabled:opacity-50 shrink-0 transition-colors"
                            >
                                <span v-if="isUploadingPhotos">جارٍ الرفع...</span>
                                <span v-else>رفع ({{ selectedFiles.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Gallery of Existing Photos -->
                <div>
                    <h5 class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
                        الصور المرفوعة ({{ activeItem?.photos?.length || 0 }})
                    </h5>

                    <div
                        v-if="activeItem?.photos && activeItem.photos.length > 0"
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3"
                    >
                        <div
                            v-for="photo in activeItem.photos"
                            :key="photo.id"
                            class="group relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 aspect-square"
                        >
                            <img
                                :src="photo.url || '/storage/' + photo.path"
                                class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform duration-200"
                                @click="openLightbox(photo.url || '/storage/' + photo.path)"
                                alt="Service Photo"
                            />

                            <!-- Type badge -->
                            <span
                                class="absolute top-1.5 right-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-black/60 text-white backdrop-blur-sm"
                            >
                                {{ photo.type || 'عام' }}
                            </span>

                            <!-- Delete button -->
                            <button
                                type="button"
                                @click.stop="deletePhoto(photo)"
                                :disabled="isDeletingPhotoId === photo.id"
                                class="absolute bottom-1.5 left-1.5 p-1.5 rounded-lg bg-rose-600 text-white text-xs hover:bg-rose-700 transition-colors shadow-sm"
                                title="حذف الصورة"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-400 text-xs">
                        لا توجد صور مرفوعة لهذه الخدمة حتى الآن
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="closePhotosModal"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.close') }}
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- 3. Checklist Modal -->
        <BaseModal :show="showChecklistModal" @close="closeChecklistModal" size="lg">
            <template #title>
                <div class="flex items-center gap-2">
                    <span class="text-xl">☑️</span>
                    <span>قائمة الفحص والمهام — {{ activeItem?.title }}</span>
                </div>
            </template>

            <div class="space-y-4">
                <!-- Progress Bar -->
                <div
                    v-if="checklistItems.length > 0"
                    class="bg-gray-100 dark:bg-gray-700/50 rounded-xl p-3"
                >
                    <div
                        class="flex items-center justify-between text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                    >
                        <span>نسبة الإنجاز</span>
                        <span>
                            {{ checklistItems.filter((i) => i.completed).length }} من
                            {{ checklistItems.length }} ({{
                                Math.round(
                                    (checklistItems.filter((i) => i.completed).length /
                                        checklistItems.length) *
                                        100
                                )
                            }}%)
                        </span>
                    </div>
                    <div
                        class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 overflow-hidden"
                    >
                        <div
                            class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                            :style="{
                                width: `${Math.round((checklistItems.filter((i) => i.completed).length / checklistItems.length) * 100)}%`,
                            }"
                        ></div>
                    </div>
                </div>

                <!-- Add Item Input -->
                <div class="flex items-center gap-2">
                    <input
                        v-model="newChecklistText"
                        @keyup.enter="addChecklistItem"
                        type="text"
                        placeholder="أدخل عنصر فحص جديد واضغط Enter..."
                        class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs px-3 py-2.5 focus:border-action-primary focus:ring-focus-ring"
                    />
                    <button
                        type="button"
                        @click="addChecklistItem"
                        class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition-colors shrink-0"
                    >
                        إضافة +
                    </button>
                </div>

                <!-- Checklist Items -->
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    <div
                        v-for="(checkItem, idx) in checklistItems"
                        :key="idx"
                        class="flex items-center justify-between p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 gap-3"
                    >
                        <label class="flex items-center gap-2.5 flex-1 cursor-pointer">
                            <AppToggle
                                :checked="checkItem.completed"
                                @change="toggleChecklistItem(idx)"
                            />
                            <span
                                :class="[
                                    'text-xs transition-colors',
                                    checkItem.completed
                                        ? 'line-through text-gray-400 dark:text-gray-500 font-normal'
                                        : 'text-gray-800 dark:text-gray-200 font-medium',
                                ]"
                            >
                                {{ checkItem.text }}
                            </span>
                        </label>

                        <button
                            type="button"
                            @click="removeChecklistItem(idx)"
                            class="text-gray-400 hover:text-rose-600 transition-colors p-1"
                            title="حذف"
                        >
                            ✕
                        </button>
                    </div>

                    <div
                        v-if="!checklistItems.length"
                        class="text-center py-6 text-gray-400 text-xs"
                    >
                        لا توجد عناصر فحص بعد. أضف عناصر لفحصها أثناء العمل.
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closeChecklistModal"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveChecklist"
                        :disabled="isSavingChecklist"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        <span v-if="isSavingChecklist">{{ $t('common.saving') }}</span>
                        <span v-else>{{ $t('common.save') }}</span>
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- 4. Lightbox Image Preview Modal -->
        <BaseModal :show="showLightboxModal" @close="showLightboxModal = false" size="3xl">
            <template #title>
                <span>معاينة الصورة</span>
            </template>

            <div
                class="flex items-center justify-center p-2 bg-black/5 dark:bg-black/30 rounded-xl"
            >
                <img
                    :src="lightboxImage"
                    class="max-h-[70vh] object-contain rounded-lg"
                    alt="Preview"
                />
            </div>

            <template #footer>
                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="showLightboxModal = false"
                        class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        إلغاء
                    </button>
                </div>
            </template>
        </BaseModal>
    </EmployeePortalLayout>
</template>
