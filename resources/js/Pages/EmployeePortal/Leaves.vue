<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center shadow-lg shadow-focus-ring-soft"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $t('employee_portal.my_leaves') }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ leaves?.total || 0 }} {{ $t('common.records') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <button
                        @click="showRequestModal = true"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-medium shadow-lg shadow-focus-ring-soft hover:shadow-xl hover:shadow-focus-ring-soft hover:-translate-y-0.5 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('hr.leaves.request_leave') }}</span>
                    </button>
                </div>
            </div>

            <!-- Leaves Table -->
            <DataTable
                :items="leaves?.data || []"
                :columns="[
                    { key: 'type', label: $t('hr.leaves.leave_type') },
                    { key: 'start_date', label: $t('hr.leaves.start_date') },
                    { key: 'end_date', label: $t('hr.leaves.end_date') },
                    { key: 'days', label: $t('hr.leaves.days') },
                    { key: 'status', label: $t('common.status') },
                ]"
                :empty-title="$t('common.no_data')"
            >
                <template #cell-type="{ value }">
                    {{ value ? $t('hr.leaves.types.' + value) : '-' }}
                </template>
                <template #cell-status="{ value }">
                    <span
                        class="px-3 py-1 text-xs font-bold rounded-full"
                        :class="getStatusClass(value)"
                    >
                        {{ value ? $t('hr.leaves.statuses.' + value) : '-' }}
                    </span>
                </template>
            </DataTable>

            <!-- Pagination -->
            <AppPagination
                v-if="leaves?.links?.length > 3"
                :links="leaves.links"
                :show-summary="false"
            />
        </div>

        <!-- Request Leave Modal -->
        <BaseModal
            v-if="showRequestModal"
            @close="showRequestModal = false"
            :title="$t('hr.leaves.request_leave')"
        >
            <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                {{ $t('common.coming_soon') }}
            </p>
        </BaseModal>
    </EmployeePortalLayout>
</template>

<script setup>
import AppPagination from '@/Components/Common/AppPagination.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { ref } from 'vue';

defineProps({
    leaves: Object,
    usedByType: Object,
    employee: Object,
});

const showRequestModal = ref(false);

function getStatusClass(status) {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}
</script>
