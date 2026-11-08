<template>
    <SystemLayout>
        <Head
            :title="
                isAr
                    ? $t('system.communication.communication_templates_2')
                    : 'Communication Templates'
            "
        />

        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('system.nav_items.templates')"
                :subtitle="$t('system.communication.templates_subtitle')"
                :totalCount="templates ? templates.length : null"
                :countLabel="$t('system.nav_items.templates')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Table Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.communication.template_name') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('system.communication.template_code') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('common.type') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-start text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('common.status') }}
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-end text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800"
                        >
                            <tr
                                v-for="template in templates"
                                :key="template.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ template.name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ template.subject || '—' }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300 font-mono"
                                >
                                    <span class="px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700">
                                        {{ template.code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800"
                                    >
                                        {{ $t('quotes.actions.email') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full"
                                        :class="
                                            template.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800'
                                                : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800'
                                        "
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="
                                                template.is_active
                                                    ? 'bg-emerald-500'
                                                    : 'bg-rose-500'
                                            "
                                        ></span>
                                        {{ $t('common.active') }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'system.communication.templates.edit',
                                                template.id
                                            )
                                        "
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/40 transition-colors"
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                        <span>{{ $t('common.edit') }}</span>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!templates || templates.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('system.communication.templates_empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

defineProps({
    templates: Array,
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');
</script>
