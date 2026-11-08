<template>
    <SystemLayout>
        <Head :title="$t('system.credits.sms_hub_title')" />

        <div class="space-y-6">
            <!-- Unified Page Header -->
            <PageHeader
                :title="$t('system.credits.sms_hub_title')"
                :subtitle="$t('system.credits.sms_hub_subtitle')"
                gradientFrom="from-indigo-600"
                gradientTo="to-purple-600"
                glowFrom="from-indigo-500"
                glowTo="to-purple-600"
                badgeBg="bg-indigo-50/70 dark:bg-indigo-950/40"
                badgeText="text-indigo-600 dark:text-indigo-400"
                badgeBorder="border-indigo-200/50 dark:border-indigo-800/40"
                badgeDot="bg-indigo-500"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <button
                        v-if="currentTab === 'packages'"
                        @click="packagesTabRef?.openAddModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.credits.add_package') }}
                    </button>
                    <button
                        v-else-if="currentTab === 'balances'"
                        @click="balancesTabRef?.openAddModal(null)"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.credits.add_manual_credit') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Standardized Approved Tabs Component -->
            <AppTabs
                v-model="currentTab"
                :tabs="tabs"
                variant="underline"
                @change="handleTabChange"
            />

            <!-- Tab Panels -->
            <div class="mt-4">
                <SmsPackagesTab
                    v-if="currentTab === 'packages'"
                    ref="packagesTabRef"
                    :packages="packages"
                />
                <SmsBalancesTab
                    v-else-if="currentTab === 'balances'"
                    ref="balancesTabRef"
                    :balances="balances"
                    :stats="stats"
                    :filters="filters"
                />
                <SmsPurchasesTab
                    v-else-if="currentTab === 'purchases'"
                    :purchases="purchases"
                    :stats="stats"
                    :filters="filters"
                />
                <SmsUsageTab
                    v-else-if="currentTab === 'usage'"
                    :logs="logs"
                    :stats="stats"
                    :filters="filters"
                />
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import SmsPackagesTab from './Tabs/SmsPackagesTab.vue';
import SmsBalancesTab from './Tabs/SmsBalancesTab.vue';
import SmsPurchasesTab from './Tabs/SmsPurchasesTab.vue';
import SmsUsageTab from './Tabs/SmsUsageTab.vue';

const { t } = useI18n();

const props = defineProps({
    tab: { type: String, default: 'packages' },
    packages: { type: Array, default: () => [] },
    balances: { type: Object, default: () => ({}) },
    purchases: { type: Object, default: () => ({}) },
    logs: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const currentTab = ref(props.tab || 'packages');
const packagesTabRef = ref(null);
const balancesTabRef = ref(null);

watch(
    () => props.tab,
    (newTab) => {
        if (newTab && newTab !== currentTab.value) {
            currentTab.value = newTab;
        }
    }
);

const tabs = computed(() => [
    {
        id: 'packages',
        label: t('system.credits.tabs.packages'),
        name: t('system.credits.tabs.packages'),
        count: props.packages?.length || null,
    },
    {
        id: 'balances',
        label: t('system.credits.tabs.balances'),
        name: t('system.credits.tabs.balances'),
        count: props.balances?.total || null,
    },
    {
        id: 'purchases',
        label: t('system.credits.tabs.purchases'),
        name: t('system.credits.tabs.purchases'),
        count: props.purchases?.total || null,
    },
    {
        id: 'usage',
        label: t('system.credits.tabs.usage'),
        name: t('system.credits.tabs.usage'),
        count: props.logs?.total || null,
    },
]);

const handleTabChange = (tabId) => {
    currentTab.value = tabId;
    router.get(
        '/system/sms',
        { tab: tabId },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>
