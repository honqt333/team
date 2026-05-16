<template>
    <Modal :show="show" @close="close" max-width="4xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        {{ $t('purchasing.grn.create') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $t('purchasing.grn.create_for') }}: <span class="font-bold text-gray-700 dark:text-gray-300">{{ purchaseOrder.code }}</span>
                    </p>
                </div>
                <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- General Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">
                            {{ $t('purchasing.grn.received_date') }} <span class="text-red-500">*</span>
                        </label>
                        <CustomDatePicker v-model="form.received_date" />
                        <div v-if="form.errors.received_date" class="text-red-500 text-xs mt-1">{{ form.errors.received_date }}</div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">
                            {{ $t('purchasing.grn.delivery_note') }}
                        </label>
                        <input type="text" v-model="form.delivery_note"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all shadow-inner" />
                        <div v-if="form.errors.delivery_note" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_note }}</div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50/50 dark:bg-gray-900/50 text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-start font-black uppercase text-[10px] tracking-wider">{{ $t('inventory.parts.title') }}</th>
                                    <th class="px-4 py-3 text-center font-black uppercase text-[10px] tracking-wider">{{ $t('purchasing.items.qty_ordered') }}</th>
                                    <th class="px-4 py-3 text-center font-black uppercase text-[10px] tracking-wider">{{ $t('purchasing.items.qty_received') }}</th>
                                    <th class="px-4 py-3 text-center font-black uppercase text-[10px] tracking-wider bg-emerald-50/50 dark:bg-emerald-900/10 text-emerald-600">{{ $t('purchasing.items.receive_now') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                <tr v-for="(item, index) in form.items" :key="item.purchase_order_item_id">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ item.part?.name_ar || item.part?.name_en }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">{{ item.part?.sku }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono text-gray-500">{{ toEnglish(item.qty_ordered) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-bold text-gray-400">
                                            {{ toEnglish(item.qty_received) }} / {{ toEnglish(item.qty_ordered) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center bg-emerald-50/20 dark:bg-emerald-900/5">
                                        <div class="flex flex-col items-center">
                                            <input type="number" v-model.number="item.qty_to_receive" step="0.001" min="0" :max="item.qty_pending"
                                                class="w-24 px-3 py-1.5 border border-emerald-200 dark:border-emerald-800 rounded-lg bg-white dark:bg-gray-800 text-center font-black text-emerald-600 dark:text-emerald-400 focus:ring-2 focus:ring-emerald-500 transition-all shadow-sm" />
                                            <span v-if="item.qty_to_receive > item.qty_pending" class="text-[9px] text-red-500 font-black uppercase mt-1">
                                                {{ $t('purchasing.grn.over_reception') || 'Limit Exceeded' }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="close"
                        class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-all">
                        {{ $t('common.cancel') }}
                    </button>
                    
                    <button type="button" @click="submit(true)" :disabled="form.processing || !hasReceptions || hasErrors"
                        class="px-8 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-black uppercase tracking-widest text-xs shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:translate-y-0">
                        <span v-if="form.processing">{{ $t('common.loading') }}</span>
                        <span v-else>{{ $t('purchasing.grn.save_and_post') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    purchaseOrder: Object,
});

const emit = defineEmits(['close', 'success']);

const { toEnglish } = useNumberFormat();

// Prepare pending items
const pendingItems = computed(() => {
    if (!props.purchaseOrder?.items) return [];
    return props.purchaseOrder.items
        .filter(item => item.qty_pending > 0)
        .map(item => ({
            purchase_order_item_id: item.id,
            part_id: item.part_id,
            part: item.part,
            qty_ordered: item.qty_ordered,
            qty_received: item.qty_received,
            qty_pending: item.qty_pending,
            qty_to_receive: item.qty_pending,
            unit_cost: item.unit_cost,
        }));
});

const form = useForm({
    warehouse_id: props.purchaseOrder?.warehouse_id || '',
    received_date: new Date().toISOString().split('T')[0],
    delivery_note: '',
    notes: '',
    items: [],
});

// Watch show to reset form
import { watch } from 'vue';
watch(() => props.show, (isShown) => {
    if (isShown) {
        form.items = pendingItems.value;
        form.warehouse_id = props.purchaseOrder.warehouse_id;
    }
});

const hasReceptions = computed(() => {
    return form.items.some(item => item.qty_to_receive > 0);
});

const hasErrors = computed(() => {
    return form.items.some(item => item.qty_to_receive > item.qty_pending);
});

const close = () => {
    emit('close');
    form.reset();
};

const submit = (postNow = true) => {
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

    form.transform(() => payload).post(route('app.purchasing.grn.store', props.purchaseOrder.id), {
        preserveScroll: true,
        onSuccess: () => {
            close();
            emit('success');
        },
    });
};
</script>
