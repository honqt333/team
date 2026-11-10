<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    userContext: {
        type: Object,
        default: () => ({}),
    },
});

const allActions = [
    {
        id: 'new_work_order',
        label: 'dashboard.new_work_order',
        href: '/app/work-orders',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
        color: 'indigo',
        visible: (ctx) => ctx.canCreateWorkOrder !== false && !ctx.isTechnician,
    },
    {
        id: 'workshop_board',
        label: 'dashboard.workshop_board',
        href: '/app/workshop',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>',
        color: 'blue',
        visible: (ctx) =>
            ctx.isTechnician ||
            ctx.isReceptionist ||
            !ctx.role ||
            ctx.role === 'super_admin' ||
            ctx.role === 'manager',
    },
    {
        id: 'new_customer',
        label: 'dashboard.new_customer',
        href: '/app/customers',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>',
        color: 'emerald',
        visible: (ctx) => !ctx.isTechnician,
    },
    {
        id: 'new_quote',
        label: 'dashboard.new_quote',
        href: '/app/quotes',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        color: 'sky',
        visible: (ctx) => !ctx.isTechnician,
    },
    {
        id: 'appointments',
        label: 'dashboard.appointments',
        href: '/app/appointments',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        color: 'amber',
        visible: (ctx) => ctx.isReceptionist || ctx.isTechnician,
    },
    {
        id: 'inventory',
        label: 'dashboard.inventory',
        href: '/app/inventory',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
        color: 'slate',
        visible: (ctx) => ctx.canViewInventory && ctx.isTechnician,
    },
    {
        id: 'new_invoice',
        label: 'dashboard.new_invoice',
        href: '/app/invoices',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
        color: 'amber',
        visible: (ctx) => ctx.canViewFinancials,
    },
    {
        id: 'reports',
        label: 'dashboard.reports',
        href: '/app/reports',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
        color: 'violet',
        visible: (ctx) => ctx.canViewFinancials,
    },
    {
        id: 'settings',
        label: 'dashboard.settings',
        href: '/app/settings',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        color: 'slate',
        visible: (ctx) => !ctx.isTechnician && !ctx.isReceptionist,
    },
];

const visibleActions = computed(() => {
    const ctx = props.userContext || {};
    return allActions.filter((a) => !a.visible || a.visible(ctx));
});

const colorClasses = {
    indigo: 'bg-indigo-50/70 text-indigo-700 hover:bg-indigo-100/80 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50',
    blue: 'bg-blue-50/70 text-blue-700 hover:bg-blue-100/80 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-900/50',
    emerald:
        'bg-emerald-50/70 text-emerald-700 hover:bg-emerald-100/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50',
    sky: 'bg-sky-50/70 text-sky-700 hover:bg-sky-100/80 dark:bg-sky-950/40 dark:text-sky-300 dark:hover:bg-sky-900/50',
    amber: 'bg-amber-50/70 text-amber-700 hover:bg-amber-100/80 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/50',
    violet: 'bg-violet-50/70 text-violet-700 hover:bg-violet-100/80 dark:bg-violet-950/40 dark:text-violet-300 dark:hover:bg-violet-900/50',
    slate: 'bg-slate-100/80 text-slate-700 hover:bg-slate-200/80 dark:bg-slate-800/60 dark:text-slate-200 dark:hover:bg-slate-700/60',
};
</script>

<template>
    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
        <Link
            v-for="action in visibleActions"
            :key="action.id"
            :href="action.href"
            :class="[
                'group flex items-center gap-2.5 rounded-xl p-3 text-xs font-semibold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm border border-slate-200/60 dark:border-slate-800/80',
                colorClasses[action.color] || colorClasses.slate,
            ]"
        >
            <div class="flex-shrink-0" v-html="action.icon"></div>
            <span class="truncate">{{ $t(action.label) }}</span>
        </Link>
    </div>
</template>
