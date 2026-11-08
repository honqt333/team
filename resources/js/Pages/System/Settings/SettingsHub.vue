<template>
    <SystemLayout>
        <Head :title="$t('system.settings.hub_title')" />

        <div class="space-y-6">
            <!-- Unified Page Header -->
            <PageHeader
                :title="$t('system.settings.hub_title')"
                :subtitle="$t('system.settings.hub_subtitle')"
                gradientFrom="from-slate-700"
                gradientTo="to-zinc-900"
                glowFrom="from-slate-500"
                glowTo="to-zinc-700"
                badgeBg="bg-slate-50/70 dark:bg-slate-900/40"
                badgeText="text-slate-700 dark:text-slate-300"
                badgeBorder="border-slate-200/50 dark:border-slate-700/40"
                badgeDot="bg-slate-500"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <!-- Standardized Approved Tabs Component -->
            <AppTabs
                v-model="currentTab"
                :tabs="tabs"
                variant="underline"
                @change="handleTabChange"
            />

            <!-- Tab Panels -->
            <div class="mt-4">
                <GeneralSettingsTab v-if="currentTab === 'general'" :settings="generalSettings" />
                <CompanySettingsTab
                    v-else-if="currentTab === 'company'"
                    :settings="companySettings"
                />
                <PaymentSettingsTab
                    v-else-if="currentTab === 'payment'"
                    :settings="paymentSettings"
                />
                <DunningSettingsTab
                    v-else-if="currentTab === 'dunning'"
                    :config="dunningConfig"
                    :tier_definitions="tierDefinitions"
                    :default_channel_matrix="defaultChannelMatrix"
                    :logs="dunningLogs"
                    :filters="dunningFilters"
                    :sub="sub"
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
import GeneralSettingsTab from './Tabs/GeneralSettingsTab.vue';
import CompanySettingsTab from './Tabs/CompanySettingsTab.vue';
import PaymentSettingsTab from './Tabs/PaymentSettingsTab.vue';
import DunningSettingsTab from './Tabs/DunningSettingsTab.vue';

const { t } = useI18n();

const props = defineProps({
    tab: { type: String, default: 'general' },
    sub: { type: String, default: 'rules' },
    generalSettings: { type: Object, default: () => ({}) },
    companySettings: { type: Object, default: () => ({}) },
    paymentSettings: { type: Object, default: () => ({}) },
    dunningConfig: { type: Object, default: () => ({}) },
    tierDefinitions: { type: Object, default: () => ({}) },
    defaultChannelMatrix: { type: Object, default: () => ({}) },
    dunningLogs: { type: Object, default: () => ({}) },
    dunningFilters: { type: Object, default: () => ({}) },
});

const currentTab = ref(props.tab || 'general');

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
        id: 'general',
        label: t('system.settings.tabs.general'),
        name: t('system.settings.tabs.general'),
    },
    {
        id: 'company',
        label: t('system.settings.tabs.company'),
        name: t('system.settings.tabs.company'),
    },
    {
        id: 'payment',
        label: t('system.settings.tabs.payment'),
        name: t('system.settings.tabs.payment'),
    },
    {
        id: 'dunning',
        label: t('system.settings.tabs.dunning'),
        name: t('system.settings.tabs.dunning'),
    },
]);

const handleTabChange = (tabId) => {
    currentTab.value = tabId;
    router.get(
        '/system/settings',
        { tab: tabId },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>
