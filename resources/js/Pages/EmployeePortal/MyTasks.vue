<script setup>
import { ref, reactive } from 'vue';
import { useToast } from '@/Composables/useToast';
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { defineProps, computed } from 'vue';

const props = defineProps({
    workOrders: Object,
    statusFilter: String,
    employee: Object,
});

const currentStatus = ref(props.statusFilter || 'open');
const loadingItems = reactive(new Set());
const toast = useToast();

const statusOptions = [
    { value: 'all', label: 'All' },
    { value: 'open', label: 'Open' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'on_hold', label: 'On Hold' },
    { value: 'ready_for_qc', label: 'Ready for QC' },
];

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        on_hold: 'bg-gray-100 text-gray-800',
        ready_for_qc: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const canCompleteItem = (item) => {
    return item.status !== 'completed' && item.status !== 'cancelled';
};

const canChangeStatus = (item, newStatus) => {
    const validTransitions = {
        open: ['in_progress', 'on_hold', 'cancelled'],
        in_progress: ['ready_for_qc', 'on_hold', 'cancelled'],
        on_hold: ['in_progress', 'cancelled'],
        ready_for_qc: ['completed', 'cancelled'],
    };
    return validTransitions[item.status]?.includes(newStatus) || false;
};

const isLoading = (itemId) => loadingItems.has(itemId);

const setLoading = (itemId, loading) => {
    if (loading) {
        loadingItems.add(itemId);
    } else {
        loadingItems.delete(itemId);
    }
};

const handleApiError = (error, defaultMessage) => {
    console.error('API Error:', error);
    toast.error(error.response?.data?.message || defaultMessage);
};

const updateStatus = async (workOrderId, item, newStatus) => {
    const itemId = `${workOrderId}-${item.id}`;
    setLoading(itemId, true);

    try {
        const response = await fetch(`/app/my/work-orders/${workOrderId}/items/${item.id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ status: newStatus }),
        });

        const data = await response.json();

        if (response.ok) {
            item.status = newStatus;
            toast.success('تم تحديث الحالة بنجاح');
        } else {
            handleApiError({ response: { data } }, 'فشل تحديث الحالة');
            // Revert on error
            await refreshItem(workOrderId, item);
        }
    } catch (error) {
        handleApiError(error, 'خطأ في الاتصال بالخادم');
        await refreshItem(workOrderId, item);
    } finally {
        setLoading(itemId, false);
    }
};

const completeItem = async (workOrderId, item) => {
    if (!confirm('تأكيد إكمال هذا الصنف؟')) return;

    const itemId = `${workOrderId}-${item.id}`;
    setLoading(itemId, true);

    try {
        const response = await fetch(
            `/app/my/work-orders/${workOrderId}/items/${item.id}/complete`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ confirm: 'complete' }),
            }
        );

        const data = await response.json();

        if (response.ok) {
            item.status = 'completed';
            item.completed_at = data.item?.completed_at || new Date().toISOString();
            toast.success('تم إكمال الصنف بنجاح');
        } else {
            handleApiError({ response: { data } }, 'فشل إكمال الصنف');
            await refreshItem(workOrderId, item);
        }
    } catch (error) {
        handleApiError(error, 'خطأ في الاتصال بالخادم');
        await refreshItem(workOrderId, item);
    } finally {
        setLoading(itemId, false);
    }
};

const refreshItem = async (workOrderId, item) => {
    try {
        const response = await fetch(`/app/my/work-orders/${workOrderId}/items/${item.id}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        if (response.ok) {
            const data = await response.json();
            Object.assign(item, data.item);
        }
    } catch (error) {
        console.error('Failed to refresh item:', error);
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-SA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('en-SA', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getPartsDisplay = (item) => {
    if (!item.parts?.length) return null;
    return item.parts.map((p) => `${p.name} (${p.qty})`).join(', ');
};

const getMediaCount = (item) => {
    return item.media?.length || 0;
};
</script>

<template>
    <EmployeePortalLayout :title="$t('employee_portal.my_tasks')">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="md:flex md:items-center md:justify-between mb-6">
                    <div class="flex-1 min-w-0">
                        <h2
                            class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight"
                        >
                            {{ $t('employee_portal.my_tasks') }}
                        </h2>
                    </div>
                    <div class="mt-4 flex md:ml-4 md:mt-0">
                        <select
                            v-model="currentStatus"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                v-for="opt in statusOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Work Orders List -->
                <div class="space-y-4">
                    <div
                        v-for="workOrder in workOrders.data"
                        :key="workOrder.id"
                        class="bg-white shadow overflow-hidden sm:rounded-lg"
                    >
                        <!-- Work Order Header -->
                        <div class="px-4 py-5 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                                        {{ workOrder.code }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ workOrder.vehicle?.customer?.name_ar || 'No Customer' }}
                                        · {{ workOrder.vehicle?.license_plate || 'No Plate' }}
                                    </p>
                                </div>
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="getStatusColor(workOrder.status)"
                                >
                                    {{ workOrder.status }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-500">
                                Created: {{ formatDate(workOrder.created_at) }}
                            </div>
                        </div>

                        <!-- Items List -->
                        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                {{ $t('work_orders.items') }}
                            </h4>

                            <div v-if="workOrder.items.length === 0" class="text-sm text-gray-500">
                                {{ $t('work_orders.no_items') }}
                            </div>

                            <div v-else class="space-y-2">
                                <div
                                    v-for="item in workOrder.items"
                                    :key="item.id"
                                    class="p-3 bg-gray-50 rounded-lg border border-gray-100"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h5
                                                    class="text-sm font-medium text-gray-900 truncate"
                                                >
                                                    {{ item.title }}
                                                </h5>
                                                <span
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full whitespace-nowrap"
                                                    :class="getStatusColor(item.status)"
                                                >
                                                    {{ item.status }}
                                                </span>
                                            </div>

                                            <div
                                                class="flex flex-wrap items-center gap-3 text-sm text-gray-500"
                                            >
                                                <span>Qty: {{ item.qty }}</span>

                                                <span
                                                    v-if="getPartsDisplay(item)"
                                                    class="flex items-center gap-1 text-blue-600"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                                        />
                                                    </svg>
                                                    {{ getPartsDisplay(item) }}
                                                </span>

                                                <span
                                                    v-if="getMediaCount(item) > 0"
                                                    class="flex items-center gap-1 text-purple-600"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                                        />
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                                        />
                                                    </svg>
                                                    {{ getMediaCount(item) }} صورة
                                                </span>
                                            </div>

                                            <div
                                                v-if="item.technicians?.length"
                                                class="mt-1 flex items-center gap-1 text-xs text-gray-400"
                                            >
                                                <svg
                                                    class="w-3 h-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                                    />
                                                </svg>
                                                {{ item.technicians.map((t) => t.name).join(', ') }}
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-1 ml-4 shrink-0">
                                            <!-- Status Change Buttons -->
                                            <button
                                                v-if="canChangeStatus(item, 'in_progress')"
                                                @click="
                                                    updateStatus(workOrder.id, item, 'in_progress')
                                                "
                                                :disabled="isLoading(`${workOrder.id}-${item.id}`)"
                                                class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors whitespace-nowrap"
                                            >
                                                <span
                                                    v-if="isLoading(`${workOrder.id}-${item.id}`)"
                                                    class="flex items-center gap-1"
                                                >
                                                    <svg
                                                        class="animate-spin h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <circle
                                                            class="opacity-25"
                                                            cx="12"
                                                            cy="12"
                                                            r="10"
                                                            stroke="currentColor"
                                                            stroke-width="4"
                                                            fill="none"
                                                        ></circle>
                                                        <path
                                                            class="opacity-75"
                                                            fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                        ></path>
                                                    </svg>
                                                    جاري...
                                                </span>
                                                <span v-else>
                                                    {{ $t('work_orders.actions.start_work') }}
                                                </span>
                                            </button>

                                            <button
                                                v-if="canChangeStatus(item, 'ready_for_qc')"
                                                @click="
                                                    updateStatus(workOrder.id, item, 'ready_for_qc')
                                                "
                                                :disabled="isLoading(`${workOrder.id}-${item.id}`)"
                                                class="px-3 py-1.5 text-xs font-medium text-white bg-purple-600 rounded hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors whitespace-nowrap"
                                            >
                                                <span
                                                    v-if="isLoading(`${workOrder.id}-${item.id}`)"
                                                    class="flex items-center gap-1"
                                                >
                                                    <svg
                                                        class="animate-spin h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <circle
                                                            class="opacity-25"
                                                            cx="12"
                                                            cy="12"
                                                            r="10"
                                                            stroke="currentColor"
                                                            stroke-width="4"
                                                            fill="none"
                                                        ></circle>
                                                        <path
                                                            class="opacity-75"
                                                            fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                        ></path>
                                                    </svg>
                                                    جاري...
                                                </span>
                                                <span v-else>
                                                    {{ $t('work_orders.actions.ready_for_qc') }}
                                                </span>
                                            </button>

                                            <button
                                                v-if="canCompleteItem(item)"
                                                @click="completeItem(workOrder.id, item)"
                                                :disabled="isLoading(`${workOrder.id}-${item.id}`)"
                                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors whitespace-nowrap"
                                            >
                                                <span
                                                    v-if="isLoading(`${workOrder.id}-${item.id}`)"
                                                    class="flex items-center gap-1"
                                                >
                                                    <svg
                                                        class="animate-spin h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <circle
                                                            class="opacity-25"
                                                            cx="12"
                                                            cy="12"
                                                            r="10"
                                                            stroke="currentColor"
                                                            stroke-width="4"
                                                            fill="none"
                                                        ></circle>
                                                        <path
                                                            class="opacity-75"
                                                            fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                        ></path>
                                                    </svg>
                                                    جاري...
                                                </span>
                                                <span v-else>
                                                    {{ $t('work_orders.actions.complete') }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Completed At -->
                                    <div
                                        v-if="item.completed_at"
                                        class="mt-2 pt-2 border-t border-gray-200 text-xs text-gray-500"
                                    >
                                        مكتمل في: {{ formatDateTime(item.completed_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="workOrders.data.length === 0"
                        class="text-center py-12 bg-white rounded-lg shadow"
                    >
                        <p class="text-gray-500">{{ $t('employee_portal.no_tasks') }}</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="workOrders.data.length > 0" class="mt-6">
                    <nav class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ workOrders.from }} to {{ workOrders.to }} of
                            {{ workOrders.total }} results
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-if="workOrders.prev_page_url"
                                @click="
                                    workOrders.prev_page_url &&
                                    $inertia.get(workOrders.prev_page_url)
                                "
                                class="px-3 py-1 border border-gray-300 rounded text-sm"
                            >
                                {{ $t('common.previous') }}
                            </button>
                            <button
                                v-if="workOrders.next_page_url"
                                @click="
                                    workOrders.next_page_url &&
                                    $inertia.get(workOrders.next_page_url)
                                "
                                class="px-3 py-1 border border-gray-300 rounded text-sm"
                            >
                                {{ $t('common.next') }}
                            </button>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </EmployeePortalLayout>
</template>
