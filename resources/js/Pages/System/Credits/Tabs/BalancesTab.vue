<template>
    <div class="space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-sm text-gray-500">
                    {{ $t('system.credits.total_credits_balance') }}
                </p>
                <p
                    :class="[
                        'text-2xl font-bold mt-1',
                        isWhatsapp
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-action-primary',
                    ]"
                >
                    {{ (stats?.total_balance || 0).toLocaleString() }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-sm text-gray-500">{{ $t('system.credits.total_purchased') }}</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ (stats?.total_purchased || 0).toLocaleString() }}
                </p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-sm text-gray-500">{{ $t('system.credits.total_used') }}</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">
                    {{ (stats?.total_used || 0).toLocaleString() }}
                </p>
            </div>
        </div>

        <!-- Search -->
        <div class="flex gap-4 items-center">
            <div class="flex-1 max-w-md">
                <AppInput
                    v-model="search"
                    type="text"
                    :placeholder="$t('system.credits.search_workshop')"
                    @enter="applySearch"
                />
            </div>
            <button
                @click="applySearch"
                :class="[
                    'px-4 py-2 text-white rounded-lg text-sm font-medium',
                    isWhatsapp
                        ? 'bg-emerald-600 hover:bg-emerald-700'
                        : 'bg-action-primary hover:bg-action-primary-hover',
                ]"
            >
                {{ $t('common.search') }}
            </button>
        </div>

        <!-- Balances Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <DataTable structured class="w-full">
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
                            {{ $t('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="item in balances?.data || []" :key="item.id">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{
                                    item.tenant?.trade_name ||
                                    item.tenant?.name ||
                                    $t('system.credits.tenant_label', { id: item.tenant_id })
                                }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="
                                    item.balance < 10
                                        ? 'text-red-600 font-bold'
                                        : 'text-emerald-600 font-bold'
                                "
                            >
                                {{ item.balance.toLocaleString() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                            {{ item.total_purchased.toLocaleString() }}
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                            {{ item.total_used.toLocaleString() }}
                        </td>
                        <td class="px-6 py-4">
                            <button
                                @click="openAddModal(item)"
                                :class="[
                                    'hover:underline text-sm font-medium',
                                    isWhatsapp
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-action-primary',
                                ]"
                            >
                                + {{ $t('system.credits.add_credit') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!balances?.data?.length">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            {{ $t('system.credits.no_data_available') }}
                        </td>
                    </tr>
                </tbody>
            </DataTable>
            <AppPagination
                :links="balances?.links || []"
                :from="balances?.from"
                :to="balances?.to"
                :total="balances?.total"
            />
        </div>

        <!-- Add Manual Credit Modal -->
        <BaseModal
            :show="showAddModal"
            :title="
                isWhatsapp
                    ? $t('system.credits.add_whatsapp_credit')
                    : $t('system.credits.add_manual_sms_credit')
            "
            size="sm"
            @close="closeModal"
        >
            <div
                v-if="selectedTenant"
                class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg text-sm mb-4"
            >
                <span class="text-gray-500">{{ $t('system.credits.workshop') }}:</span>
                <span class="font-medium mr-2">
                    {{ selectedTenant.tenant?.trade_name || selectedTenant.tenant?.name }}
                </span>
            </div>

            <form @submit.prevent="addCredits" class="space-y-4">
                <AppInput
                    v-if="!selectedTenant"
                    v-model="form.tenant_id"
                    type="number"
                    :label="$t('system.credits.tenant_id')"
                    required
                />

                <AppInput
                    v-model="form.credits"
                    type="number"
                    min="1"
                    :label="$t('system.credits.messages_count')"
                    required
                />

                <AppTextarea
                    v-model="form.reason"
                    :rows="2"
                    :label="$t('system.credits.reason')"
                    :placeholder="$t('system.credits.reason_placeholder')"
                />

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
                        :class="[
                            'px-4 py-2 text-white rounded-lg disabled:opacity-50 text-sm font-medium',
                            isWhatsapp
                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                : 'bg-action-primary hover:bg-action-primary-hover',
                        ]"
                    >
                        {{
                            form.processing ? $t('system.credits.adding') : $t('system.credits.add')
                        }}
                    </button>
                </div>
            </form>
        </BaseModal>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    channel: { type: String, required: true },
    balances: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const isWhatsapp = computed(() => props.channel === 'whatsapp');

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
        `/system/${props.channel}`,
        { tab: 'balances', search: search.value },
        { preserveState: true }
    );
};

const openAddModal = (item = null) => {
    selectedTenant.value = item;
    form.tenant_id = item ? item.tenant_id : '';
    form.credits = 100;
    form.reason = '';
    showAddModal.value = true;
};

const closeModal = () => {
    showAddModal.value = false;
    selectedTenant.value = null;
    form.reset();
};

const addCredits = () => {
    form.post(`/system/${props.channel}/add-credits`, {
        onSuccess: () => closeModal(),
    });
};

defineExpose({
    openAddModal,
});
</script>
