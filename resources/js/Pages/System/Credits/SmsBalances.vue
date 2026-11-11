<template>
    <SystemLayout>
        <Head :title="$t('system.credits.sms_balances_title')" />
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.credits.sms_balances_title')"
                :subtitle="$t('system.credits.sms_credit_balance_per_tenant')"
                :totalCount="balances?.total ?? null"
                :countLabel="$t('system.credits.tabs.balances')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-emerald-500/25 active:scale-95"
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

            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <Link
                    :href="route('system.sms.packages')"
                    :class="
                        route().current('system.sms.packages')
                            ? 'border-action-primary text-action-primary font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.packages') }}
                </Link>
                <Link
                    :href="route('system.sms.balances')"
                    :class="
                        route().current('system.sms.balances')
                            ? 'border-action-primary text-action-primary font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.balances') }}
                </Link>
                <Link
                    :href="route('system.sms.purchases')"
                    :class="
                        route().current('system.sms.purchases')
                            ? 'border-action-primary text-action-primary font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.purchases') }}
                </Link>
                <Link
                    :href="route('system.sms.usage')"
                    :class="
                        route().current('system.sms.usage')
                            ? 'border-action-primary text-action-primary font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.usage') }}
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-action-primary">
                        {{ stats.total_purchased?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.total_purchases') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-amber-600">
                        {{ stats.total_used?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.total_used') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ stats.total_balance?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.current_balance') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ formatCurrency(stats.total_revenue) }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('system.credits.revenue') }}</p>
                </div>
            </div>

            <!-- Search -->
            <div>
                <input
                    type="text"
                    v-model="search"
                    :placeholder="$t('system.credits.search_by_tenant_name')"
                    class="w-full md:w-80 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    @keyup.enter="applySearch"
                />
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <DataTable structured class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.tenant') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.current_balance') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.total_purchases') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.total_used') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="item in balances.data"
                            :key="item.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        >
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ item.tenant?.trade_name || item.tenant?.name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="item.balance > 0 ? 'text-emerald-600' : 'text-red-500'"
                                    class="font-semibold"
                                >
                                    {{ item.balance?.toLocaleString() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ item.total_purchased?.toLocaleString() }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ item.total_used?.toLocaleString() }}
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    @click="openAddCreditsModal(item)"
                                    class="text-action-primary hover:text-selection-text text-sm"
                                >
                                    {{ $t('system.credits.add_credit') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!balances.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                {{ $t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>
        </div>

        <!-- Add Credits Modal -->
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.credits.add_sms_credit') }}
                        </h3>
                        <form @submit.prevent="addCredits" class="space-y-4">
                            <div v-if="!selectedTenant">
                                <SearchableSelect
                                    v-model="form.tenant_id"
                                    :options="balances.data"
                                    :option-label="(t) => t.tenant?.trade_name"
                                    option-value="tenant_id"
                                    :label="$t('system.credits.tenant')"
                                    :placeholder="$t('system.credits.select_the_tenant')"
                                    required
                                />
                            </div>
                            <div v-else class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $t('system.credits.tenant') }}
                                </p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ selectedTenant.tenant?.trade_name }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ $t('system.credits.message_count') }}
                                </label>
                                <input
                                    type="number"
                                    v-model="form.credits"
                                    required
                                    min="1"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ $t('system.credits.reason_optional') }}
                                </label>
                                <input
                                    type="text"
                                    v-model="form.reason"
                                    :placeholder="$t('system.credits.e.g._welcome_bonus')"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50"
                                >
                                    {{ $t('common.add') }}
                                </button>
                                <button
                                    type="button"
                                    @click="showAddModal = false"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg"
                                >
                                    {{ $t('common.cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    balances: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showAddModal = ref(false);
const selectedTenant = ref(null);

const form = useForm({
    tenant_id: '',
    credits: 100,
    reason: '',
});

const formatCurrency = (amount) =>
    new Intl.NumberFormat('ar-SA-u-nu-latn', { style: 'currency', currency: 'SAR' }).format(
        amount || 0
    );

const applySearch = () => {
    router.get(
        '/system/sms/balances',
        { search: search.value || undefined },
        { preserveState: true }
    );
};

const openAddCreditsModal = (item) => {
    selectedTenant.value = item;
    form.tenant_id = item.tenant_id;
    showAddModal.value = true;
};

const addCredits = () => {
    form.post('/system/sms/add-credits', {
        onSuccess: () => {
            showAddModal.value = false;
            selectedTenant.value = null;
            form.reset();
        },
    });
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
