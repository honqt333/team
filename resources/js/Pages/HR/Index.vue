<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg hover:shadow-xl transition-shadow">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('hr.title') }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.subtitle') }}</p>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Employees -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                         <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            {{ stats.employees.active }} {{ $t('common.active') }}
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.employees.total }}</h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.total') }}</span>
                    </div>
                </div>
                
                <!-- Placeholders for other stats (Attendance, etc) -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 opacity-60">
                     <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-400">{{ $t('common.coming_soon') }}</span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white">-</h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.attendance.title') }}</span>
                    </div>
                </div>
             </div>

            <!-- Modules Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Employees Module -->
                <Link :href="route('app.hr.employees.index')" 
                    class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-600 transition-all">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                             <span class="text-2xl">👥</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $t('hr.employees.title') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.subtitle') }}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ $t('common.view_details') }}</span>
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </Link>

                <!-- Attendance Module -->
                <Link :href="route('app.hr.attendance.index')" 
                    class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-violet-300 dark:hover:border-violet-600 transition-all">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                             <span class="text-2xl">📅</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $t('hr.attendance.title') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.attendance.subtitle') }}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ $t('common.view_details') }}</span>
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </Link>

                <!-- Payroll Module -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 border-dashed opacity-75 cursor-not-allowed">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                             <span class="text-2xl">💰</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('hr.payroll.title') }}</h3>
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 text-yellow-800">{{ $t('common.coming_soon') }}</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.payroll.subtitle') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Settings Module -->
                <Link :href="route('app.hr.settings.index')" 
                    class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-gray-400 dark:hover:border-gray-500 transition-all">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                             <span class="text-2xl">⚙️</span>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-gray-700 dark:group-hover:text-gray-200 transition-colors">{{ $t('hr.settings.title') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.settings.subtitle') }}</p>
                        </div>
                    </div>
                     <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded">{{ stats.settings.employee_types }} {{ $t('hr.settings.employee_types.title') }}</div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded">{{ stats.settings.job_titles }} {{ $t('hr.settings.job_titles.title') }}</div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded">{{ stats.settings.allowances }} {{ $t('hr.settings.allowances.title') }}</div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded">{{ stats.settings.deductions }} {{ $t('hr.settings.deductions.title') }}</div>
                    </div>
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                        <span>{{ $t('common.view_details') }}</span>
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>
