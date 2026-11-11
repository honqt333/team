<template>
    <AppLayout>
        <Head :title="$t('hr.title')" />

        <div class="space-y-8">
            <!-- Header Section -->
            <PageHeader :title="$t('hr.title')" :subtitle="$t('hr.subtitle')">
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
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
                </template>
            </PageHeader>

            <!-- Quick Access Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Employees Card -->
                <Link
                    :href="route('app.hr.employees.index')"
                    class="group relative bg-surface-primary rounded-2xl p-8 border border-border-subtle hover:border-action-primary/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl bg-action-primary/10 text-action-primary flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-5 text-lg font-bold text-content-primary group-hover:text-action-primary transition-colors"
                    >
                        {{ $t('hr.employees.title') }}
                    </h3>
                    <p class="mt-2 text-xs text-content-muted">
                        {{ $t('hr.employees.subtitle') }}
                    </p>
                    <div
                        v-if="stats?.employees?.total > 0"
                        class="absolute top-4 end-4 px-2.5 py-0.5 bg-action-primary text-white text-xs font-bold rounded-full shadow-2xs font-mono"
                    >
                        {{ toEnglish(stats.employees.total) }}
                    </div>
                </Link>

                <!-- Attendance Card -->
                <Link
                    :href="route('app.hr.attendance.index')"
                    class="group relative bg-surface-primary rounded-2xl p-8 border border-border-subtle hover:border-warning/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl bg-warning-soft text-warning flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-5 text-lg font-bold text-content-primary group-hover:text-warning transition-colors"
                    >
                        {{ $t('hr.attendance.title') }}
                    </h3>
                    <p class="mt-2 text-xs text-content-muted">
                        {{ $t('hr.attendance.subtitle') }}
                    </p>
                </Link>

                <!-- Payroll Card -->
                <Link
                    :href="route('app.hr.payroll.index')"
                    class="group relative bg-surface-primary rounded-2xl p-8 border border-border-subtle hover:border-success/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl bg-success-soft text-success flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-5 text-lg font-bold text-content-primary group-hover:text-success transition-colors"
                    >
                        {{ $t('hr.payroll.title') }}
                    </h3>
                    <p class="mt-2 text-xs text-content-muted">
                        {{ $t('hr.payroll.subtitle') }}
                    </p>
                </Link>

                <!-- Settings Card -->
                <Link
                    :href="route('app.hr.settings.index')"
                    class="group relative bg-surface-primary rounded-2xl p-8 border border-border-subtle hover:border-info/50 hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 text-center shadow-2xs"
                >
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl bg-info-soft text-info flex items-center justify-center group-hover:scale-110 transition-transform duration-300"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="mt-5 text-lg font-bold text-content-primary group-hover:text-info transition-colors"
                    >
                        {{ $t('hr.settings.title') }}
                    </h3>
                    <p class="mt-2 text-xs text-content-muted">
                        {{ $t('hr.settings.subtitle') }}
                    </p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { toEnglish } = useNumberFormat();

defineProps({
    stats: Object,
});
</script>
