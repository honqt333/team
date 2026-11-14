<template>
    <AppLayout>
        <Head :title="$t('settings.title')" />

        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader :title="$t('settings.title')" :subtitle="$t('settings.subtitle')">
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
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Settings Cards -->
            <div
                class="bg-surface-primary rounded-2xl p-6 shadow-2xs border border-border-subtle space-y-4"
            >
                <Link
                    v-for="card in visibleCards"
                    :key="card.id"
                    :href="card.href"
                    class="group flex items-center gap-5 p-4 rounded-xl border border-border-subtle hover:border-action-primary/50 hover:bg-surface-secondary/50 transition-all"
                >
                    <div
                        :class="[
                            'w-12 h-12 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform shrink-0',
                            card.iconBg,
                        ]"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                v-for="(d, idx) in card.paths"
                                :key="idx"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="d"
                            />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3
                            class="text-base font-bold text-content-primary group-hover:text-action-primary transition-colors"
                        >
                            {{ card.title }}
                        </h3>
                        <p class="text-xs text-content-muted mt-0.5">
                            {{ card.description }}
                        </p>
                    </div>
                    <svg
                        class="w-5 h-5 text-content-muted group-hover:text-action-primary group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-all shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { usePermission } from '@/Composables/usePermission';

const { t } = useI18n();
const { can } = usePermission();

const settingCards = computed(() => [
    {
        id: 'company',
        visible: can('settings.company.manage'),
        href: route('settings.company'),
        title: t('settings.cards.company.title'),
        description: t('settings.cards.company.description'),
        iconBg: 'bg-success-soft text-success',
        paths: [
            'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        ],
    },
    {
        id: 'branches',
        visible: can('settings.centers.view'),
        href: route('settings.branches'),
        title: t('settings.cards.branches.title'),
        description: t('settings.cards.branches.description'),
        iconBg: 'bg-info-soft text-info',
        paths: [
            'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
            'M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        ],
    },
    {
        id: 'users',
        visible: can('users.view'),
        href: route('settings.users.index'),
        title: t('users.management'),
        description: t('users.description'),
        iconBg: 'bg-action-primary/10 text-action-primary',
        paths: [
            'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        ],
    },
    {
        id: 'system',
        visible: can('crm.vehicles.settings.view'),
        href: route('settings.system'),
        title: t('settings.cards.system.title'),
        description: t('settings.cards.system.description'),
        iconBg: 'bg-action-primary/10 text-action-primary',
        paths: [
            'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
        ],
    },
    {
        id: 'inventory_settings',
        visible: can('inventory.settings.manage'),
        href: route('app.inventory.settings.index'),
        title: t('settings.cards.inventory.title'),
        description: t('settings.cards.inventory.description'),
        iconBg: 'bg-orange-500/10 text-orange-600 dark:text-orange-400',
        paths: ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    },
    {
        id: 'hr_settings',
        visible: can('hr.settings.manage'),
        href: route('app.hr.settings.index'),
        title: t('settings.cards.hr.title'),
        description: t('settings.cards.hr.description'),
        iconBg: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
        paths: [
            'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        ],
    },
    {
        id: 'billing',
        visible: can('settings.company.manage'),
        href: route('app.billing.index'),
        title: t('settings.cards.billing.title'),
        description: t('settings.cards.billing.description'),
        iconBg: 'bg-danger-soft text-danger',
        paths: [
            'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        ],
    },
]);

const visibleCards = computed(() => settingCards.value.filter((card) => card.visible));
</script>
