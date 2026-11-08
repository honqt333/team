<template>
    <div class="space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ stats?.total_credits?.toLocaleString() || 0 }}
                </p>
                <p class="text-sm text-gray-500">
                    {{ $t('system.credits.total_credits_balance') }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    {{ stats?.total_sent?.toLocaleString() || 0 }}
                </p>
                <p class="text-sm text-gray-500">{{ $t('system.credits.messages_sent') }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {{ stats?.active_tenants || 0 }}
                </p>
                <p class="text-sm text-gray-500">{{ $t('system.credits.active_workshops') }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ stats?.low_balance_tenants || 0 }}
                </p>
                <p class="text-sm text-gray-500">{{ $t('system.credits.low_credit') }}</p>
            </div>
        </div>

        <!-- Search -->
        <div class="flex items-center gap-3">
            <div class="relative flex-1 max-w-md">
                <input
                    v-model="search"
                    @keyup.enter="applySearch"
                    type="text"
                    :placeholder="$t('system.credits.search_workshop')"
                    class="w-full pl-10 pr-4 py-2 border rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                />
                <svg
                    class="w-5 h-5 absolute left-3 top-2.5 text-gray-400"
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
            </div>
            <button
                @click="applySearch"
                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-sm font-medium transition-colors"
            >
                {{ $t('common.search') }}
            </button>
        </div>

        <!-- Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.workshop') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.current_balance') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.total_purchased') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.total_used') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ $t('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="item in balances?.data || []" :key="item.id">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{
                                    item.tenant?.name ||
                                    $t('system.credits.tenant_label', { id: item.tenant_id })
                                }}
                            </div>
                            <div class="text-xs text-gray-500">{{ item.tenant?.email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'font-bold text-lg',
                                    item.balance < 50
                                        ? 'text-red-600'
                                        : item.balance < 200
                                          ? 'text-amber-600'
                                          : 'text-emerald-600',
                                ]"
                            >
                                {{ item.balance?.toLocaleString() }}
                            </span>
                            <span class="text-xs text-gray-500 mr-1">
                                {{ $t('system.credits.message') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                            {{ item.total_purchased?.toLocaleString() || 0 }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                            {{ item.total_used?.toLocaleString() || 0 }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                v-if="item.balance < 50"
                                class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300 rounded-full text-xs"
                            >
                                {{ $t('system.credits.critically_low') }}
                            </span>
                            <span
                                v-else-if="item.balance < 200"
                                class="px-2 py-1 bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 rounded-full text-xs"
                            >
                                {{ $t('system.credits.low') }}
                            </span>
                            <span
                                v-else
                                class="px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 rounded-full text-xs"
                            >
                                {{ $t('system.credits.good') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-left">
                            <button
                                @click="openAddCreditsModal(item)"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            >
                                {{ $t('system.credits.add_credit') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!balances?.data?.length">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            {{ $t('system.credits.no_data_available') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Add Manual Credit Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showAddModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                >
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4"
                        @click.stop
                    >
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('system.credits.add_manual_sms_credit') }}
                        </h3>

                        <div
                            v-if="selectedTenant"
                            class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg text-sm"
                        >
                            <span class="text-gray-500">{{ $t('system.credits.workshop') }}:</span>
                            <span class="font-medium mr-2">{{ selectedTenant.tenant?.name }}</span>
                        </div>

                        <form @submit.prevent="addCredits" class="space-y-4">
                            <div v-if="!selectedTenant">
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ $t('system.credits.tenant_id') }}
                                </label>
                                <input
                                    v-model="form.tenant_id"
                                    type="number"
                                    required
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ $t('system.credits.messages_count') }}
                                </label>
                                <input
                                    v-model="form.credits"
                                    type="number"
                                    min="1"
                                    required
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{ $t('system.credits.reason') }}
                                </label>
                                <textarea
                                    v-model="form.reason"
                                    rows="2"
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    :placeholder="$t('system.credits.reason_placeholder')"
                                ></textarea>
                            </div>

                            <div
                                class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700"
                            >
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"
                                >
                                    {{ $t('common.cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 text-sm font-medium"
                                >
                                    {{
                                        form.processing
                                            ? $t('system.credits.adding')
                                            : $t('system.credits.add')
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    balances: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');
const showAddModal = ref(false);
const selectedTenant = ref(null);

const form = useForm({
    tenant_id: '',
    credits: 100,
    reason: '',
});

const applySearch = () => {
    router.get(
        '/system/sms',
        { tab: 'balances', search: search.value || undefined },
        { preserveState: true }
    );
};

const openAddCreditsModal = (item) => {
    selectedTenant.value = item;
    form.tenant_id = item ? item.tenant_id : '';
    showAddModal.value = true;
};

const closeModal = () => {
    showAddModal.value = false;
    selectedTenant.value = null;
    form.reset();
};

const addCredits = () => {
    form.post('/system/sms/add-credits', {
        onSuccess: () => {
            closeModal();
        },
    });
};

defineExpose({
    openAddModal: openAddCreditsModal,
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
