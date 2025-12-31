<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- Back Button -->
            <Link
                :href="route('app.purchasing.suppliers.index')"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            >
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ $t('common.back') }}
            </Link>

            <!-- Main Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-blue-500/10 via-indigo-500/5 to-transparent dark:from-blue-900/30 dark:via-indigo-900/10 p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                        <!-- Supplier Info -->
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg flex items-center justify-center text-white p-2">
                                <svg v-if="supplier.type === 'parts'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ supplier.name }}
                                    </h2>
                                    <span v-if="supplier.code" class="px-2 py-0.5 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-600">
                                        {{ supplier.code }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="supplier.phone" class="flex items-center gap-1" dir="ltr">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ supplier.phone }}
                                    </span>
                                    <span v-if="supplier.email" class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        {{ supplier.email }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4">
                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(balance) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.balance') }}</p>
                            </div>
                            <div class="h-10 w-px bg-gray-300 dark:bg-gray-600"></div>
                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ counts.orders }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.orders') }}</p>
                            </div>
                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ counts.invoices }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.invoices') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <button
                        @click="showEditModal = true"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ $t('common.edit') }}
                    </button>

                    <button
                        @click="confirmDelete"
                        :disabled="counts.orders > 0"
                        :class="[
                            'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                            counts.orders === 0
                                ? 'text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                                : 'text-gray-400 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 cursor-not-allowed'
                        ]"
                        :title="counts.orders > 0 ? $t('common.cannot_delete') : ''"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ $t('common.delete') }}
                    </button>
                </div>

                <!-- Tabs Navigation -->
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all flex-1 justify-center',
                                activeTab === tab.key
                                    ? 'bg-white dark:bg-gray-800 shadow-sm ' + tab.activeColor
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            {{ tab.label }}
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700">
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- ========== OVERVIEW TAB ========== -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Info -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ $t('common.basic_info') }}</h3>
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.contact') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ supplier.contact_person || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.phone') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white" dir="ltr">{{ supplier.phone || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.email') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ supplier.email || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.type') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ supplier.type === 'parts' ? $t('purchasing.suppliers.type_parts') : $t('purchasing.suppliers.type_services') }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Financial Info -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ $t('purchasing.suppliers.bank_info') }}</h3>
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.tax_number') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ supplier.tax_number || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.cr_number') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ supplier.cr_number || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.bank_name') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ supplier.bank_name || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.iban') }}</dt>
                                        <dd class="text-sm font-medium text-gray-900 dark:text-white" dir="ltr">{{ supplier.iban || '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                             <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">{{ $t('purchasing.suppliers.address') }}</h3>
                             <p class="text-sm text-gray-700 dark:text-gray-300">{{ supplier.address || '-' }}</p>
                             <div v-if="supplier.building_number" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                <div>
                                    <dt class="text-xs text-gray-500">{{ $t('purchasing.suppliers.city') }}</dt>
                                    <dd class="text-sm font-medium">{{ supplier.city }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ $t('purchasing.suppliers.district') }}</dt>
                                    <dd class="text-sm font-medium">{{ supplier.district }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ $t('purchasing.suppliers.street') }}</dt>
                                    <dd class="text-sm font-medium">{{ supplier.street }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ $t('purchasing.suppliers.country') }}</dt>
                                    <dd class="text-sm font-medium">{{ supplier.country }}</dd>
                                </div>
                             </div>
                        </div>
                    </div>

                    <!-- ========== INVOICES TAB ========== -->
                    <div v-if="activeTab === 'invoices'" class="space-y-4">
                        <div class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('purchasing.suppliers.invoices') }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>

                    <!-- ========== PAYMENTS TAB ========== -->
                    <div v-if="activeTab === 'payments'" class="space-y-4">
                        <div class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('purchasing.suppliers.payments') }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <CreateModal
            v-if="showEditModal"
            :show="showEditModal"
            :supplier="supplier"
            @close="showEditModal = false"
        />

        <ConfirmModal />
    </AppLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateModal from './CreateModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    supplier: Object,
    counts: Object,
    balance: Number,
});

const { t, locale } = useI18n();
const { confirm } = useConfirm();

const showEditModal = ref(false);
const activeTab = ref('overview');

// Icons
const HomeIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })]);
const InvoiceIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z' })]);
const PaymentIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' })]);

const tabs = computed(() => [
    { key: 'overview', label: t('common.overview') || 'Overview', count: null, icon: HomeIcon, activeColor: 'text-blue-600 dark:text-blue-400' },
    { key: 'invoices', label: t('purchasing.suppliers.invoices'), count: props.counts.invoices || 0, icon: InvoiceIcon, activeColor: 'text-green-600 dark:text-green-400' },
    { key: 'payments', label: t('purchasing.suppliers.payments'), count: props.counts.payments || 0, icon: PaymentIcon, activeColor: 'text-purple-600 dark:text-purple-400' },
]);

function formatCurrency(value) {
    if (!value && value !== 0) return '';
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0
    }).format(value);
}

async function confirmDelete() {
    const confirmed = await confirm({
        title: t("common.delete_confirm"),
        message: t("common.delete_confirm_message"),
        confirmText: t("common.delete"),
        cancelText: t("common.cancel"),
        type: "danger"
    });
    if (confirmed) {
        router.delete(route('app.purchasing.suppliers.destroy', props.supplier.id), {
             onSuccess: () => router.visit(route('app.purchasing.suppliers.index'))
        });
    }
}
</script>
