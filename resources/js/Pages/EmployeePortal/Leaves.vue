<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-600 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('employee_portal.my_leaves') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ leaves?.total || 0 }} {{ $t('common.records') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <button @click="showRequestModal = true" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-violet-500/30 hover:shadow-xl hover:shadow-violet-500/40 hover:-translate-y-0.5 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>{{ $t('hr.leaves.request_leave') }}</span>
                    </button>
                </div>
            </div>

            <!-- Leaves Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('hr.leaves.leave_type') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('hr.leaves.start_date') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('hr.leaves.end_date') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('hr.leaves.days') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="leave in (leaves?.data || [])" :key="leave.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ leave.type ? $t('hr.leaves.types.' + leave.type) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ leave.start_date }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ leave.end_date }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ leave.days }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full" :class="getStatusClass(leave.status)">
                                        {{ leave.status ? $t('hr.leaves.status.' + leave.status) : '-' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!leaves?.data?.length">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.no_data') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="leaves?.links?.length > 3" class="flex justify-center">
                <nav class="flex gap-1">
                    <template v-for="link in leaves.links" :key="link.label">
                        <Link 
                            v-if="link.url"
                            :href="link.url"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
                            :class="link.active ? 'bg-violet-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
                            v-html="link.label"
                        />
                        <span 
                            v-else
                            class="px-4 py-2 rounded-xl text-sm bg-gray-100 dark:bg-gray-900 text-gray-400 dark:text-gray-600 border border-gray-200 dark:border-gray-700 cursor-not-allowed"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>

        <!-- Request Leave Modal -->
        <BaseModal v-if="showRequestModal" @close="showRequestModal = false" :title="$t('hr.leaves.request_leave')">
            <p class="text-gray-600 dark:text-gray-400 text-center py-8">
                {{ $t('common.coming_soon') }}
            </p>
        </BaseModal>
    </EmployeePortalLayout>
</template>

<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { Link } from '@inertiajs/vue3';
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
