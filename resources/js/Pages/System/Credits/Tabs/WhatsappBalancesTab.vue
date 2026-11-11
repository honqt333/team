<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.current_balance') }}
                </p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">
                    {{ (stats.total_balance || 0).toLocaleString() }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.total_purchases') }}
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ (stats.total_purchased || 0).toLocaleString() }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">
                    {{ $t('system.credits.total_used') }}
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ (stats.total_used || 0).toLocaleString() }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm"
            >
                <p class="text-xs text-gray-500 font-medium">{{ $t('system.credits.revenue') }}</p>
                <p class="text-2xl font-bold text-action-primary mt-1">
                    {{ formatCurrency(stats.total_revenue) }}
                </p>
            </div>
        </div>

        <!-- Filter bar -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between gap-4"
        >
            <div class="relative flex-1 max-w-md">
                <svg
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
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
                    v-model="search"
                    type="text"
                    :placeholder="$t('system.tenants.search_placeholder')"
                    @keyup.enter="applySearch"
                    class="w-full px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                />
            </div>
            <button
                @click="openAddModal(null)"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md active:scale-95"
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
        </div>

        <!-- Balances Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm"
        >
            <div class="overflow-x-auto">
                <DataTable structured class="w-full text-right text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3.5 font-bold">{{ $t('system.credits.tenant') }}</th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.current_balance') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.total_purchases') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold">
                                {{ $t('system.credits.total_used') }}
                            </th>
                            <th class="px-6 py-3.5 font-bold text-center">
                                {{ $t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="item in balances?.data || []"
                            :key="item.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                        >
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{
                                    item.tenant?.trade_name ||
                                    item.tenant?.name ||
                                    '#' + item.tenant_id
                                }}
                            </td>
                            <td class="px-6 py-4 font-bold text-emerald-600">
                                {{ (item.balance || 0).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                {{ (item.total_purchased || 0).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                {{ (item.total_used || 0).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    @click="openAddModal(item)"
                                    class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-lg text-xs font-bold hover:bg-emerald-100 transition-colors"
                                >
                                    {{ $t('system.credits.add_label') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!balances?.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                {{ $t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>
        </div>

        <!-- Add Credits Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.credits.add_whatsapp_credit') }}
                        </h3>
                        <form @submit.prevent="addCredits" class="space-y-4">
                            <div
                                v-if="selectedItem"
                                class="bg-emerald-50 dark:bg-emerald-950/40 rounded-xl p-3 border border-emerald-100 dark:border-emerald-800"
                            >
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium">
                                    {{ $t('system.credits.tenant_4') }}
                                </p>
                                <p class="font-bold text-gray-900 dark:text-white mt-0.5">
                                    {{
                                        selectedItem.tenant?.trade_name || selectedItem.tenant?.name
                                    }}
                                </p>
                            </div>
                            <div v-else>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ $t('system.credits.tenant') }} ID
                                </label>
                                <input
                                    type="number"
                                    v-model="form.tenant_id"
                                    required
                                    placeholder="Tenant ID"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    {{ $t('system.credits.message_count_3') }}
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
                                    {{ $t('system.credits.reason') }}
                                </label>
                                <input
                                    type="text"
                                    v-model="form.reason"
                                    :placeholder="$t('common.optional')"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <div class="flex gap-3 pt-3">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium disabled:opacity-50"
                                >
                                    {{ $t('common.add') }}
                                </button>
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"
                                >
                                    {{ $t('common.cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    balances: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const selectedItem = ref(null);

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
        route('system.whatsapp.index'),
        { tab: 'balances', search: search.value || undefined },
        { preserveState: true, preserveScroll: true }
    );
};

const openAddModal = (item) => {
    selectedItem.value = item;
    form.tenant_id = item ? item.tenant_id : '';
    form.credits = 100;
    form.reason = '';
    showModal.value = true;
};

const addCredits = () => {
    form.post('/system/whatsapp/add-credits', {
        onSuccess: () => {
            showModal.value = false;
            selectedItem.value = null;
            form.reset();
        },
    });
};

defineExpose({
    openAddModal,
});
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
