<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-600 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('employee_portal.my_attendance') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatMonthName(month) }}</p>
                        </div>
                    </div>

                    <!-- Month Selector -->
                    <input 
                        type="month" 
                        v-model="selectedMonth" 
                        @change="changeMonth"
                        class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
                    />
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-green-100 dark:bg-green-900/30 rounded-xl">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('hr.attendance.present') }}</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ summary?.present || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-red-100 dark:bg-red-900/30 rounded-xl">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('hr.attendance.absent') }}</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ summary?.absent || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('hr.attendance.late') }}</p>
                            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ summary?.late || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-orange-100 dark:bg-orange-900/30 rounded-xl">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('hr.attendance.early_leave') }}</p>
                            <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ summary?.early_leave || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-violet-100 dark:bg-violet-900/30 rounded-xl">
                            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('employee_portal.total_hours') }}</p>
                            <p class="text-xl font-bold text-violet-600 dark:text-violet-400">{{ summary?.total_hours || 0 }}h</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('common.date') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('hr.attendance.check_in') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('hr.attendance.check_out') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('common.status') }}</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('hr.settings.shifts.shift') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="record in (attendance || [])" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(record.date) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-mono" dir="ltr">{{ record.check_in || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-mono" dir="ltr">{{ record.check_out || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full" :class="getStatusClass(record.status)">
                                        {{ $t('hr.attendance.' + record.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ record.shift?.name_ar || '-' }}</td>
                            </tr>
                            <tr v-if="!attendance?.length">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
        </div>
    </EmployeePortalLayout>
</template>

<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    attendance: Array,
    summary: Object,
    month: String,
    employee: Object,
});

const selectedMonth = ref(props.month);

function changeMonth() {
    router.get(route('employee.attendance'), { month: selectedMonth.value }, { preserveState: true });
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('ar-SA', { weekday: 'short', day: 'numeric', month: 'short' });
}

function formatMonthName(month) {
    if (!month) return '';
    return new Date(month + '-01').toLocaleDateString('ar-SA', { year: 'numeric', month: 'long' });
}

function getStatusClass(status) {
    const classes = {
        present: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        absent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        late: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        early_leave: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}
</script>
