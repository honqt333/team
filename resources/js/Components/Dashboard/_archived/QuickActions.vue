<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/Composables/usePermission';

const { t, locale } = useI18n();
const { can } = usePermission();
const isRtl = computed(() => locale.value === 'ar');

const actions = computed(() => {
    const allActions = [
        {
            label: t('dashboard.new_work_order'),
            href: '/app/work-orders',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>`,
            color: 'indigo',
            permission: 'crm.work_orders.create',
            gradient: 'from-indigo-500 to-indigo-700',
            shadow: 'shadow-indigo-500/30',
        },
        {
            label: t('dashboard.new_customer'),
            href: '/app/customers',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>`,
            color: 'emerald',
            permission: 'crm.customers.create',
            gradient: 'from-emerald-500 to-teal-600',
            shadow: 'shadow-emerald-500/30',
        },
        {
            label: t('dashboard.new_invoice'),
            href: '/app/invoices',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
            color: 'blue',
            permission: 'invoices.create',
            gradient: 'from-blue-500 to-blue-700',
            shadow: 'shadow-blue-500/30',
        },
        {
            label: t('dashboard.new_quote'),
            href: '/app/quotes',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>`,
            color: 'amber',
            permission: 'quotes.create',
            gradient: 'from-amber-500 to-orange-600',
            shadow: 'shadow-amber-500/30',
        },
        {
            label: t('dashboard.reports'),
            href: '/app/reports',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`,
            color: 'violet',
            permission: null,
            gradient: 'from-violet-500 to-purple-700',
            shadow: 'shadow-violet-500/30',
        },
        {
            label: t('dashboard.settings'),
            href: '/app/settings',
            icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`,
            color: 'gray',
            permission: 'settings.company.manage',
            gradient: 'from-gray-500 to-gray-700',
            shadow: 'shadow-gray-500/30',
        },
    ];
    return allActions.filter(function (a) {
        return !a.permission || can(a.permission);
    });
});
</script>

<template>
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
    >
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ $t('dashboard.quick_actions') }}
            </h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                {{ $t('dashboard.quick_actions_subtitle') }}
            </p>
        </div>

        <div class="p-4 grid grid-cols-2 gap-3">
            <a
                v-for="action in actions"
                :key="action.href"
                :href="action.href"
                :class="[
                    'group flex items-center gap-3 p-3 rounded-xl transition-all duration-200',
                    'bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700',
                    'border border-transparent hover:border-gray-200 dark:hover:border-gray-600',
                    'hover:-translate-y-0.5 hover:shadow-md',
                ]"
            >
                <!-- Icon -->
                <div
                    :class="[
                        'w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-gradient-to-br text-white shadow-sm',
                        action.gradient,
                        action.shadow,
                    ]"
                >
                    <span v-safe-html="action.icon" />
                </div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 leading-tight">
                    {{ action.label }}
                </span>
            </a>
        </div>
    </div>
</template>
