<template>
    <SystemLayout>
        <Head :title="$t('system.credits.whatsapp_hub_title')" />

        <div class="space-y-6">
            <!-- Unified Page Header -->
            <PageHeader
                :title="$t('system.credits.whatsapp_hub_title')"
                :subtitle="$t('system.credits.whatsapp_hub_subtitle')"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-600"
                glowFrom="from-emerald-500"
                glowTo="to-teal-600"
                badgeBg="bg-emerald-50/70 dark:bg-emerald-950/40"
                badgeText="text-emerald-600 dark:text-emerald-400"
                badgeBorder="border-emerald-200/50 dark:border-emerald-800/40"
                badgeDot="bg-emerald-500"
            >
                <template #icon>
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <button
                        v-if="currentTab === 'packages'"
                        @click="packagesTabRef?.openAddModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.credits.add_package_2') }}
                    </button>
                    <button
                        v-else-if="currentTab === 'balances'"
                        @click="balancesTabRef?.openAddModal(null)"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md active:scale-95"
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
                <WhatsappPackagesTab
                    v-if="currentTab === 'packages'"
                    ref="packagesTabRef"
                    :packages="packages"
                />
                <WhatsappBalancesTab
                    v-else-if="currentTab === 'balances'"
                    ref="balancesTabRef"
                    :balances="balances"
                    :stats="stats"
                    :filters="filters"
                />
                <WhatsappPurchasesTab
                    v-else-if="currentTab === 'purchases'"
                    :purchases="purchases"
                    :stats="stats"
                    :filters="filters"
                />
                <WhatsappUsageTab
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
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import WhatsappPackagesTab from './Tabs/WhatsappPackagesTab.vue';
import WhatsappBalancesTab from './Tabs/WhatsappBalancesTab.vue';
import WhatsappPurchasesTab from './Tabs/WhatsappPurchasesTab.vue';
import WhatsappUsageTab from './Tabs/WhatsappUsageTab.vue';

const { t } = useI18n();

const props = defineProps({
    tab: { type: String, default: 'packages' },
    packages: { type: Array, default: () => [] },
    balances: { type: Object, default: () => ({ data: [] }) },
    purchases: { type: Object, default: () => ({ data: [] }) },
    logs: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const currentTab = ref(props.tab || 'packages');
const packagesTabRef = ref(null);
const balancesTabRef = ref(null);

const tabs = computed(() => [
    {
        id: 'packages',
        label: t('system.credits.tabs.packages'),
        badge: props.stats?.packages_count || props.packages?.length,
    },
    {
        id: 'balances',
        label: t('system.credits.tabs.balances'),
    },
    {
        id: 'purchases',
        label: t('system.credits.tabs.purchases'),
    },
    {
        id: 'usage',
        label: t('system.credits.tabs.usage'),
    },
]);

const handleTabChange = (newTab) => {
    currentTab.value = newTab;
    router.get(
        route('system.whatsapp.index'),
        { tab: newTab },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};
</script>
