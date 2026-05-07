<template>
    <AppLayout :title="$t('purchasing.grn.create')">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('app.purchasing.orders.show', purchaseOrder.id)"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $t('purchasing.grn.create_for') }}: {{ purchaseOrder.code }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ purchaseOrder.supplier?.name }} | {{ purchaseOrder.warehouse?.name }}
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- General Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('common.general_info') }}</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        {{ $t('inventory.warehouses.title') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="form.warehouse_id" required
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all">
                                        <option value="" disabled>{{ $t('inventory.warehouses.select') }}</option>
                                        <option :value="purchaseOrder.warehouse_id">{{ purchaseOrder.warehouse?.name }}</option>
                                    </select>
                                    <div v-if="form.errors.warehouse_id" class="text-red-500 text-xs mt-1">{{ form.errors.warehouse_id }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        {{ $t('purchasing.grn.received_date') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" v-model="form.received_date" required
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all" />
                                    <div v-if="form.errors.received_date" class="text-red-500 text-xs mt-1">{{ form.errors.received_date }}</div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                        {{ $t('purchasing.grn.delivery_note') }}
                                    </label>
                                    <input type="text" v-model="form.delivery_note"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all" />
                                    <div v-if="form.errors.delivery_note" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_note }}</div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    {{ $t('common.notes') }}
                                </label>
                                <textarea v-model="form.notes" rows="2"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('purchasing.orders.items') }}</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/30 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                    <tr>
                                        <th class="px-6 py-4 text-start font-semibold">{{ $t('inventory.parts.title') }}</th>
                                        <th class="px-6 py-4 text-center font-semibold">{{ $t('purchasing.items.qty_ordered') }}</th>
                                        <th class="px-6 py-4 text-center font-semibold text-blue-600 dark:text-blue-400">{{ $t('purchasing.items.qty_received') }}</th>
                                        <th class="px-6 py-4 text-center font-semibold text-amber-600 dark:text-amber-400">{{ $t('purchasing.items.qty_pending') }}</th>
                                        <th class="px-6 py-4 text-center font-semibold bg-green-50 dark:bg-green-900/10">{{ $t('purchasing.items.receive_now') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="(item, index) in form.items" :key="item.purchase_order_item_id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 dark:text-white">{{ item.part?.name_ar || item.part?.name_en }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5 font-mono">{{ item.part?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-mono">{{ item.qty_ordered }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-mono text-xs">
                                                {{ item.qty_received }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-1 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 font-mono text-xs">
                                                {{ item.qty_pending }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center bg-green-50/30 dark:bg-green-900/5">
                                            <div class="flex flex-col items-center justify-center gap-1">
                                                <input type="number" v-model.number="item.qty_to_receive" step="0.001" min="0"
                                                    class="w-32 px-3 py-1.5 border border-green-200 dark:border-green-800 rounded-lg bg-white dark:bg-gray-800 text-center font-bold text-green-700 dark:text-green-400 focus:ring-2 focus:ring-green-500 transition-all shadow-sm" />
                                                <span v-if="item.qty_to_receive > item.qty_pending" class="text-[10px] text-amber-600 font-bold uppercase animate-pulse">
                                                    {{ $t('purchasing.grn.over_reception') || 'Over Reception' }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4">
                        <Link :href="route('app.purchasing.orders.show', purchaseOrder.id)"
                            class="px-6 py-3 text-sm font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                            {{ $t('common.cancel') }}
                        </Link>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="submit(false)" :disabled="form.processing || !hasReceptions"
                                class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-2xl font-bold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm disabled:opacity-50">
                                {{ $t('common.save_as_draft') || 'Save as Draft' }}
                            </button>
                            <button type="button" @click="submit(true)" :disabled="form.processing || !hasReceptions"
                                class="px-10 py-3 bg-green-600 hover:bg-green-700 text-white rounded-2xl font-black uppercase tracking-widest text-sm transition-all shadow-lg shadow-green-500/20 disabled:opacity-50">
                                <span v-if="form.processing">{{ $t('common.loading') }}</span>
                                <span v-else>{{ $t('purchasing.grn.save_and_post') || 'Save & Post' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    purchaseOrder: Object,
    pendingItems: Array,
});

const form = useForm({
    warehouse_id: props.purchaseOrder.warehouse_id || '',
    received_date: new Date().toISOString().split('T')[0],
    delivery_note: '',
    notes: '',
    items: props.pendingItems.map(item => ({
        purchase_order_item_id: item.purchase_order_item_id,
        part_id: item.part_id,
        part: item.part,
        qty_ordered: item.qty_ordered,
        qty_received: item.qty_received,
        qty_pending: item.qty_pending,
        qty_to_receive: item.qty_pending, // Default to full pending
        unit_cost: item.unit_cost,
    })),
});

const hasReceptions = computed(() => {
    return form.items.some(item => item.qty_to_receive > 0);
});

const submit = (postNow = false) => {
    // Filter out items with 0 reception
    const payload = {
        ...form.data(),
        post_now: postNow,
        items: form.items
            .filter(item => item.qty_to_receive > 0)
            .map(item => ({
                purchase_order_item_id: item.purchase_order_item_id,
                part_id: item.part_id,
                qty_received: item.qty_to_receive,
                unit_cost: item.unit_cost,
            })),
    };

    form.transform(() => payload).post(route('app.purchasing.grn.store', props.purchaseOrder.id));
};
</script>
