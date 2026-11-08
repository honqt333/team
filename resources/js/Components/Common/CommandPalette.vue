<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();

const isOpen = ref(false);
const searchQuery = ref('');
const selectedIndex = ref(0);
const inputRef = ref(null);

const emit = defineEmits(['close']);

// All accessible system navigation targets categorized
const commands = computed(() => {
    const list = [
        // Navigation - Core
        {
            id: 'dashboard',
            category: t('nav.groups.general') || 'الرئيسية',
            label: t('nav.dashboard') || 'لوحة التحكم',
            url: '/dashboard',
            icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            keywords: ['home', 'dashboard', 'الرئيسية', 'لوحة التحكم'],
        },
        {
            id: 'work_orders',
            category: t('nav.groups.operations') || 'العمليات',
            label: t('nav.work_orders') || 'كروت العمل',
            url: route('work-orders.index'),
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            keywords: ['work orders', 'كروت العمل', 'كرت عمل', 'صيانة'],
        },
        {
            id: 'workshop_board',
            category: t('nav.groups.operations') || 'العمليات',
            label: t('workshop.board.title') || 'لوحة الورشة (Kanban)',
            url: route('app.workshop.index'),
            icon: 'M4 6h6v6H4V6zm0 10h6v4H4v-4zm10-10h6v4h-6V6zm0 8h6v6h-6v-6z',
            keywords: ['workshop', 'board', 'kanban', 'الورشة', 'كانبان', 'فنيين'],
        },
        {
            id: 'customers',
            category: t('nav.groups.crm') || 'العملاء',
            label: t('nav.customers') || 'العملاء',
            url: route('customers.index'),
            icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            keywords: ['customers', 'clients', 'العملاء', 'عميل'],
        },
        {
            id: 'vehicles',
            category: t('nav.groups.crm') || 'العملاء',
            label: t('nav.vehicles') || 'المركبات',
            url: route('vehicles.index'),
            icon: 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
            keywords: ['vehicles', 'cars', 'المركبات', 'سيارات', 'لوحة'],
        },
        // Finance
        {
            id: 'sales_invoices',
            category: t('nav.groups.finance') || 'المالية والفواتير',
            label: t('invoices.hub.sales_invoices') || 'فواتير المبيعات',
            url: route('app.invoices.sales.index'),
            icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            keywords: ['invoices', 'sales', 'فواتير', 'مبيعات', 'ضريبة', 'zatca'],
        },
        {
            id: 'purchase_invoices',
            category: t('nav.groups.finance') || 'المالية والفواتير',
            label: t('invoices.hub.purchase_invoices') || 'فواتير المشتريات',
            url: route('app.invoices.purchases.index'),
            icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            keywords: ['purchases', 'مشتريات', 'موردين', 'فواتير شراء'],
        },
        // Inventory
        {
            id: 'inventory',
            category: t('nav.groups.inventory') || 'المخزون',
            label: t('nav.inventory') || 'المستودع وقطع الغيار',
            url: '/app/inventory',
            icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            keywords: ['inventory', 'parts', 'stock', 'المخزون', 'قطع غيار', 'مستودع'],
        },
        // HR
        {
            id: 'hr_employees',
            category: t('nav.groups.hr') || 'الموارد البشرية',
            label: t('hr.employees.title') || 'الموظفون والفنيون',
            url: '/app/hr/employees',
            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            keywords: ['employees', 'staff', 'hr', 'الموظفين', 'فنيين', 'رواتب'],
        },
        // Reports
        {
            id: 'reports',
            category: t('nav.groups.reports') || 'التقارير',
            label: t('nav.reports') || 'مركز التقارير الشاملة',
            url: '/app/reports',
            icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            keywords: ['reports', 'analytics', 'تقارير', 'أرباح', 'مالية'],
        },
    ];

    return list;
});

const filteredCommands = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return commands.value;

    return commands.value.filter((item) => {
        return (
            item.label.toLowerCase().includes(q) ||
            item.category.toLowerCase().includes(q) ||
            item.keywords.some((k) => k.toLowerCase().includes(q))
        );
    });
});

function open() {
    isOpen.value = true;
    searchQuery.value = '';
    selectedIndex.value = 0;
    nextTick(() => {
        inputRef.value?.focus();
    });
}

function close() {
    isOpen.value = false;
    emit('close');
}

function navigateTo(item) {
    close();
    if (item.url.startsWith('/')) {
        router.visit(item.url);
    } else {
        router.visit(item.url);
    }
}

function onKeydown(e) {
    if (!isOpen.value) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = (selectedIndex.value + 1) % filteredCommands.value.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value =
            (selectedIndex.value - 1 + filteredCommands.value.length) %
            filteredCommands.value.length;
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const selected = filteredCommands.value[selectedIndex.value];
        if (selected) {
            navigateTo(selected);
        }
    } else if (e.key === 'Escape') {
        e.preventDefault();
        close();
    }
}

// Global shortcut listener: Cmd+K / Ctrl+K
function onGlobalKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        if (isOpen.value) {
            close();
        } else {
            open();
        }
    }
}

watch(searchQuery, () => {
    selectedIndex.value = 0;
});

onMounted(() => {
    window.addEventListener('keydown', onGlobalKeydown);
    window.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onGlobalKeydown);
    window.removeEventListener('keydown', onKeydown);
});

defineExpose({ open, close });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20 bg-gray-900/60 backdrop-blur-sm flex items-start justify-center"
                @click.self="close"
            >
                <div
                    class="w-full max-w-xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all animate-fade-in"
                >
                    <!-- Search Input Bar -->
                    <div
                        class="relative flex items-center px-4 border-b border-gray-100 dark:border-gray-700/80"
                    >
                        <svg
                            class="w-5 h-5 text-gray-400 dark:text-gray-500 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <input
                            ref="inputRef"
                            type="text"
                            v-model="searchQuery"
                            :placeholder="
                                $t('common.quick_search') || 'ابحث عن أي قسم، كرت عمل، أو صفحة...'
                            "
                            class="w-full py-4 px-3 bg-transparent text-sm sm:text-base text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 border-0 focus:outline-none focus:ring-0"
                        />
                        <button
                            type="button"
                            @click="close"
                            class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs font-mono"
                        >
                            ESC
                        </button>
                    </div>

                    <!-- Results List -->
                    <div
                        class="max-h-80 overflow-y-auto p-2 divide-y divide-gray-50 dark:divide-gray-700/40"
                    >
                        <div v-if="filteredCommands.length === 0" class="p-8 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('common.no_results') || 'لم يتم العثور على نتائج مطابقة' }}
                            </p>
                        </div>

                        <div v-else class="space-y-1">
                            <button
                                v-for="(item, idx) in filteredCommands"
                                :key="item.id"
                                type="button"
                                @click="navigateTo(item)"
                                @mouseenter="selectedIndex = idx"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-start text-sm transition-all cursor-pointer',
                                    idx === selectedIndex
                                        ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50',
                                ]"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        :class="[
                                            'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors',
                                            idx === selectedIndex
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                                        ]"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                :d="item.icon"
                                            />
                                        </svg>
                                    </div>
                                    <span class="font-medium truncate">{{ item.label }}</span>
                                </div>
                                <span
                                    class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 shrink-0 px-2 py-0.5 rounded-md bg-gray-50 dark:bg-gray-700/40"
                                >
                                    {{ item.category }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Footer hint -->
                    <div
                        class="px-4 py-2.5 bg-gray-50/80 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-700/80 flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500"
                    >
                        <div class="flex items-center gap-2">
                            <span>للتنقل:</span>
                            <kbd
                                class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600"
                            >
                                ↑
                            </kbd>
                            <kbd
                                class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600"
                            >
                                ↓
                            </kbd>
                            <span>للاختيار:</span>
                            <kbd
                                class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600"
                            >
                                ↵
                            </kbd>
                        </div>
                        <div>
                            <span>لإغلاق النافذة:</span>
                            <kbd
                                class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600"
                            >
                                ESC
                            </kbd>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
