<template>
    <SystemLayout>
        <Head :title="$t('system.credits.whatsapp_balances_title')" />
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.credits.whatsapp_balances_title')"
                :subtitle="$t('system.credits.whatsapp_credit_balance_per_tenant')"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-600"
                glowFrom="from-emerald-500"
                glowTo="to-teal-600"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
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
                        {{ $t('system.credits.add_credit_2') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <Link
                    :href="route('system.whatsapp.packages')"
                    :class="
                        route().current('system.whatsapp.packages')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.packages') }}
                </Link>
                <Link
                    :href="route('system.whatsapp.balances')"
                    :class="
                        route().current('system.whatsapp.balances')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.balances') }}
                </Link>
                <Link
                    :href="route('system.whatsapp.purchases')"
                    :class="
                        route().current('system.whatsapp.purchases')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.purchases') }}
                </Link>
                <Link
                    :href="route('system.whatsapp.usage')"
                    :class="
                        route().current('system.whatsapp.usage')
                            ? 'border-emerald-600 text-emerald-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.usage') }}
                </Link>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ stats.total_purchased?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ $t('system.credits.total_purchases_2') }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-amber-600">
                        {{ stats.total_used?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">{{ $t('common.user') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-2xl font-bold text-blue-600">
                        {{ stats.total_balance?.toLocaleString() }}
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ $t('system.credits.current_balance_2') }}
                    </p>
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

            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.tenant_4') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.current_balance_2') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('system.credits.total_purchases_2') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">
                                {{ $t('common.user') }}
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
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ item.tenant?.trade_name }}
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
                                    @click="openAddModal(item)"
                                    class="text-emerald-600 hover:text-emerald-800 text-sm"
                                >
                                    {{ $t('system.credits.add_label') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!balances.data?.length">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                {{ $t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $t('system.credits.add_whatsapp_credit') }}
                        </h3>
                        <form @submit.prevent="addCredits" class="space-y-4">
                            <div
                                v-if="selectedTenant"
                                class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3"
                            >
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $t('system.credits.tenant_4') }}
                                </p>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ selectedTenant.tenant?.trade_name }}
                                </p>
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
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({ balances: Object, stats: Object, filters: Object });
const showAddModal = ref(false);
const selectedTenant = ref(null);
const form = useForm({ tenant_id: '', credits: 100, reason: '' });

const formatCurrency = (v) =>
    new Intl.NumberFormat('ar-SA-u-nu-latn', { style: 'currency', currency: 'SAR' }).format(v || 0);

const openAddModal = (item) => {
    selectedTenant.value = item;
    form.tenant_id = item.tenant_id;
    showAddModal.value = true;
};

const addCredits = () => {
    form.post('/system/whatsapp/add-credits', {
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
