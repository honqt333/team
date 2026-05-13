<template>
    <AppLayout>
        <div class="space-y-8">
            <!-- Header Section -->
            <PageHeader
                :title="$t('hr.title')"
                :subtitle="$t('hr.subtitle')"
                gradientFrom="from-violet-600"
                gradientTo="to-purple-700"
                glowFrom="from-violet-500"
                badgeBg="bg-violet-50/50 dark:bg-violet-900/30"
                badgeText="text-violet-600 dark:text-violet-400"
                badgeBorder="border-violet-100/50 dark:border-violet-800/30"
                badgeDot="bg-violet-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </template>
            </PageHeader>

            <!-- Quick Access Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Employees Card -->
                <Link
                    :href="route('app.hr.employees.index')"
                    class="group relative bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-2xl p-8 border border-violet-200 dark:border-violet-800 hover:border-violet-400 dark:hover:border-violet-600 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/30 group-hover:shadow-xl group-hover:shadow-violet-500/40 group-hover:scale-110 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                        {{ $t('hr.employees.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('hr.employees.subtitle') }}
                    </p>
                    <!-- Count Badge -->
                    <div v-if="stats.employees.total > 0" class="absolute top-4 end-4 px-3 py-1 bg-violet-500 text-white text-sm font-bold rounded-full shadow-lg">
                        {{ toEnglish(stats.employees.total) }}
                    </div>
                </Link>

                <!-- Attendance Card -->
                <Link
                    :href="route('app.hr.attendance.index')"
                    class="group relative bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl p-8 border border-orange-200 dark:border-orange-800 hover:border-orange-400 dark:hover:border-orange-600 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-orange-500/30 group-hover:shadow-xl group-hover:shadow-orange-500/40 group-hover:scale-110 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                        {{ $t('hr.attendance.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('hr.attendance.subtitle') }}
                    </p>
                </Link>

                <!-- Payroll Card -->
                <Link
                    :href="route('app.hr.payroll.index')"
                    class="group relative bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl p-8 border border-emerald-200 dark:border-emerald-800 hover:border-emerald-400 dark:hover:border-emerald-600 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/30 group-hover:shadow-xl group-hover:shadow-emerald-500/40 group-hover:scale-110 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                        {{ $t('hr.payroll.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('hr.payroll.subtitle') }}
                    </p>
                </Link>

                <!-- Settings Card -->
                <Link
                    :href="route('app.hr.settings.index')"
                    class="group relative bg-gradient-to-br from-slate-50 to-gray-100 dark:from-slate-900/20 dark:to-gray-800/20 rounded-2xl p-8 border border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center"
                >
                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-slate-500 to-gray-600 flex items-center justify-center shadow-lg shadow-slate-500/30 group-hover:shadow-xl group-hover:shadow-slate-500/40 group-hover:scale-110 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-gray-900 dark:text-white group-hover:text-slate-600 dark:group-hover:text-slate-400 transition-colors">
                        {{ $t('hr.settings.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('hr.settings.subtitle') }}
                    </p>
                </Link>
            </div>
            
            <!-- Additional Stats/Tables can be added here if needed, but keeping it clean for Hub Style -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" v-if="recentEmployees.length > 0">
                 <!-- Mini Recent Employees Table -->
                 <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/30 dark:bg-gray-900/30">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <h3 class="font-black text-gray-900 dark:text-white tracking-tight uppercase text-sm">{{ $t('hr.employees.title') }}</h3>
                        </div>
                        <Link :href="route('app.hr.employees.index')" class="text-xs font-bold text-violet-600 hover:text-violet-700 dark:text-violet-400 transition-colors flex items-center gap-1">
                            {{ $t('common.view_details') }}
                            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            <tr v-for="emp in recentEmployees" :key="emp.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white">{{ isRtl ? emp.name_ar : emp.name_en }}</span>
                                        <span class="text-[10px] font-medium text-gray-500 uppercase tracking-wider mt-0.5">{{ emp.employee_id || '#EMP-' + emp.id }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-black bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 uppercase tracking-widest border border-gray-200 dark:border-gray-600">
                                        {{ emp.job_title?.name_ar || '-' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                 </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { locale } = useI18n();
const { toEnglish } = useNumberFormat();
const isRtl = computed(() => locale.value === 'ar');

defineProps({
    stats: Object,
    recentEmployees: Array,
});
</script>
