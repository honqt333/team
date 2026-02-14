<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Back Button & Title -->
                    <div class="flex items-center gap-4">
                        <Link href="/app/settings"
                            class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            {{ $t('common.back') }}
                        </Link>

                        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>

                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{
                                $t('company_profile.branches.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{
                                $t('company_profile.branches.subtitle') }}</p>
                        </div>
                    </div>

                    <!-- Add Button (disabled for now) -->
                    <button
                        class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-xl transition-colors flex items-center gap-2"
                        disabled>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('company_profile.branches.add') }}
                    </button>
                </div>
            </div>

            <!-- Branches List -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 space-y-4">
                    <!-- Branches Grid -->
                    <div v-if="props.branches?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Link v-for="branch in props.branches" :key="branch.id"
                            :href="`/app/settings/centers/${branch.id}`"
                            class="block p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md transition-all group">
                            <div class="flex items-start justify-between mb-4">
                                <!-- Dynamic Logo Display -->
                                <div
                                    class="w-12 h-12 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden shadow-sm shadow-blue-500/10 group-hover:shadow-blue-500/20 transition-all">
                                    <template
                                        v-if="isDark ? (branch.logo_dark_url || branch.logo_light_url) : branch.logo_light_url">
                                        <img :src="isDark ? (branch.logo_dark_url || branch.logo_light_url) : branch.logo_light_url"
                                            :alt="branch.name" class="w-full h-full object-contain p-1" />
                                    </template>
                                    <template v-else-if="$page.props.tenant?.logo_url">
                                        <img :src="$page.props.tenant.logo_url" :alt="$page.props.tenant.name"
                                            class="w-full h-full object-contain p-1.5 opacity-80 group-hover:opacity-100 transition-opacity" />
                                    </template>
                                    <div v-else
                                        class="w-full h-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                </div>

                                <span :class="[
                                    'px-2.5 py-1 rounded-full text-xs font-medium',
                                    branch.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                ]">
                                    {{ branch.is_active ? $t('common.active') : $t('common.inactive') }}
                                </span>
                            </div>

                            <!-- Commercial Name (Tenant) -->
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ $page.props.tenant?.trade_name
                                || $page.props.tenant?.name
                                }}</p>

                            <!-- Center Name -->
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-0.5">{{ branch.name }}</h3>

                            <!-- Center Type -->
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-2">{{ branch.center_type
                                || '#' }}</p>

                            <!-- Manager Name -->
                            <div v-if="branch.manager_name"
                                class="flex items-center gap-2 mb-3 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-700/50 p-1.5 rounded-lg border border-gray-100 dark:border-gray-700/50">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="font-medium">{{ branch.manager_name }}</span>
                            </div>

                            <!-- Contact Info -->
                            <div class="space-y-1.5 mb-4">
                                <div v-if="branch.phone"
                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span dir="ltr">{{ branch.phone }}</span>
                                </div>
                                <div v-if="branch.email"
                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="truncate">{{ branch.email }}</span>
                                </div>
                            </div>

                            <!-- System ID & Action -->
                            <div
                                class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-3 mt-auto">
                                <span class="text-xs text-gray-400 font-mono">#{{ branch.id }}</span>
                                <span
                                    class="text-sm font-medium text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">
                                    {{ $t('company_profile.branches.settings') }} →
                                </span>
                            </div>
                        </Link>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-12 text-center">
                        <div
                            class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{
                            $t('company_profile.branches.empty') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('company_profile.branches.empty_hint')
                            }}</p>
                    </div>

                    <!-- Coming Soon Notice -->
                    <div
                        class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-sm text-amber-700 dark:text-amber-300">{{
                                $t('company_profile.branches.coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    branches: Array,
});

// Theme detection
const isDark = computed(() => {
    return document.documentElement.classList.contains('dark');
});
</script>
