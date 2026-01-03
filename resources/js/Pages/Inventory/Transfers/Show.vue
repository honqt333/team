<template>
    <AppLayout :title="transfer.code">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ transfer.code }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ transfer.from_warehouse?.name }} ← {{ transfer.to_warehouse?.name }}
                    </p>
                </div>
                <span :class="statusClass(transfer.status)" class="px-3 py-1.5 text-sm font-medium rounded-full">
                    {{ $t('inventory.transfers.statuses.' + transfer.status) }}
                </span>
            </div>

            <!-- Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.transfers.from') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ transfer.from_warehouse?.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.transfers.to') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ transfer.to_warehouse?.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.created_at') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(transfer.created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.created_by') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ transfer.created_by_user?.name || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 mb-6" v-if="transfer.status === 'draft' || transfer.status === 'sent'">
                <button
                    v-if="transfer.status === 'draft'"
                    @click="sendTransfer"
                    :disabled="transfer.items?.length === 0"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ $t('inventory.transfers.send') }}
                </button>
                <button
                    v-if="transfer.status === 'sent'"
                    @click="receiveTransfer"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    {{ $t('inventory.transfers.receive') }}
                </button>
                <button
                    @click="showCancelModal = true"
                    class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30"
                >
                    {{ $t('common.cancel') }}
                </button>
            </div>

            <!-- Items -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ $t('inventory.transfers.items') }}</h2>
                    <button
                        v-if="transfer.status === 'draft'"
                        @click="showAddModal = true"
                        class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                    >
                        + {{ $t('common.add') }}
                    </button>
                </div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.sku') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.name') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.transfers.qty_requested') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.transfers.qty_sent') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.transfers.qty_received') }}</th>
                            <th class="px-6 py-3" v-if="transfer.status === 'draft'"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in transfer.items" :key="item.id">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ item.part?.sku }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ item.part?.name_ar || item.part?.name_en }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-end">{{ item.qty_requested }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-end">{{ item.qty_sent }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white text-end">{{ item.qty_received }}</td>
                            <td class="px-6 py-4 text-end" v-if="transfer.status === 'draft'">
                                <button @click="removeItem(item)" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!transfer.items?.length" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    {{ $t('inventory.transfers.no_items') }}
                </div>
            </div>

            <!-- Add Item Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $t('inventory.transfers.add_item') }}</h3>
                    <form @submit.prevent="addItem">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('inventory.parts.title') }}</label>
                                <select v-model="newItem.part_id" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" required>
                                    <option value="">-- {{ $t('common.select') }} --</option>
                                    <option v-for="part in parts" :key="part.id" :value="part.id">{{ part.sku }} - {{ part.name_ar }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('inventory.transfers.qty_requested') }}</label>
                                <input v-model="newItem.qty_requested" type="number" step="0.001" min="0.001" dir="ltr" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600" required />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showAddModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $t('common.cancel') }}</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ $t('common.add') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    transfer: Object,
    parts: Array,
});

const showAddModal = ref(false);
const showCancelModal = ref(false);
const newItem = ref({ part_id: '', qty_requested: '' });

const statusClass = (status) => ({
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    received: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
}[status]);

const formatDate = (date) => new Date(date).toLocaleDateString();

const addItem = () => {
    router.post(route('app.inventory.transfers.items.store', props.transfer.id), newItem.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            newItem.value = { part_id: '', qty_requested: '' };
        }
    });
};

const removeItem = (item) => {
    router.delete(route('app.inventory.transfers.items.destroy', [props.transfer.id, item.id]), { preserveScroll: true });
};

const sendTransfer = () => {
    router.post(route('app.inventory.transfers.send', props.transfer.id), {}, { preserveScroll: true });
};

const receiveTransfer = () => {
    router.post(route('app.inventory.transfers.receive', props.transfer.id), {}, { preserveScroll: true });
};
</script>
