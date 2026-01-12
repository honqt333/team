<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Profile Header Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-violet-600 to-purple-600 h-32"></div>
                <div class="px-6 pb-6 -mt-12">
                    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                        <div class="w-24 h-24 rounded-2xl bg-white dark:bg-gray-700 border-4 border-white dark:border-gray-800 flex items-center justify-center text-3xl font-bold text-violet-600 dark:text-violet-400 shadow-lg">
                            {{ employee?.name_ar?.charAt(0) || '?' }}
                        </div>
                        <div class="pb-2">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ employee?.name_ar }}</h1>
                            <p class="text-gray-500 dark:text-gray-400">{{ employee?.name_en }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Personal Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2.5 bg-violet-100 dark:bg-violet-900/30 rounded-xl">
                            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('employee_portal.personal_info') }}</h3>
                    </div>
                    <dl class="space-y-4">
                        <div class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.employee_number') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ employee?.employee_number || '-' }}</dd>
                        </div>
                        <div class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('common.phone') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white font-mono" dir="ltr">{{ employee?.phone || '-' }}</dd>
                        </div>
                        <div class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('common.email') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white" dir="ltr">{{ employee?.email || '-' }}</dd>
                        </div>
                        <div class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.gender') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ employee?.gender === 'male' ? $t('common.male') : employee?.gender === 'female' ? $t('common.female') : '-' }}
                            </dd>
                        </div>
                        <div v-if="employee?.nationality" class="flex justify-between py-3">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.nationality') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ employee?.nationality?.name_ar }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Job Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2.5 bg-green-100 dark:bg-green-900/30 rounded-xl">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('employee_portal.job_info') }}</h3>
                    </div>
                    <dl class="space-y-4">
                        <div v-if="employee?.job_title" class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.job_title') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ employee?.job_title?.name_ar }}</dd>
                        </div>
                        <div v-if="employee?.department" class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.department') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ employee?.department?.name_ar }}</dd>
                        </div>
                        <div v-if="employee?.center" class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.branch') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ employee?.center?.name }}</dd>
                        </div>
                        <div v-if="employee?.hire_date" class="flex justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.hire_date') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ employee?.hire_date }}</dd>
                        </div>
                        <div v-if="employee?.default_shift" class="flex justify-between py-3">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.settings.shifts.shift') }}</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ employee?.default_shift?.name_ar }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';

defineProps({
    employee: Object,
});
</script>
