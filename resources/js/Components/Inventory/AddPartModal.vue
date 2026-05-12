<template>
    <Teleport to="body">
        <div 
            v-if="modelValue" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="close"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $t('inventory.parts.add_to_wo') }}
                    </h3>
                    <button 
                        @click="close" 
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <!-- Source Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('inventory.parts.source') }}
                        </label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.source" value="warehouse" class="text-indigo-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('inventory.parts.from_warehouse') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.source" value="external" class="text-indigo-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('inventory.parts.external') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Warehouse (if from warehouse) -->
                    <div v-if="form.source === 'warehouse'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('inventory.warehouses.title') }} *
                        </label>
                        <SearchableSelect
                            v-model="form.warehouse_id"
                            :options="warehouses"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('common.select')"
                            required
                        />
                    </div>

                    <!-- Part Selection (if from warehouse) -->
                    <div v-if="form.source === 'warehouse'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('inventory.parts.title') }} *
                        </label>
                        <SearchableSelect
                            v-model="form.part_id"
                            :options="parts"
                            :option-label="opt => `${opt.sku} - ${opt.name_ar || opt.name_en}`"
                            option-value="id"
                            :placeholder="$t('inventory.parts.search_placeholder')"
                            @change="onPartChange"
                            required
                        />
                        <!-- Selected Part -->
                        <div v-if="selectedPart" class="mt-2 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200">{{ selectedPart.sku }}</p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-300">{{ selectedPart.name_ar }}</p>
                            </div>
                            <button type="button" @click="clearPart" class="text-indigo-400 hover:text-indigo-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Stock Info -->
                        <div v-if="stockInfo" class="mt-2 text-xs" :class="stockInfo.sufficient ? 'text-green-600' : 'text-red-600'">
                            {{ $t('inventory.stock.available') }}: {{ stockInfo.on_hand }}
                        </div>
                    </div>

                    <!-- Part Name (if external) -->
                    <div v-if="form.source === 'external'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('inventory.parts.name') }} *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            required
                        />
                    </div>

                    <!-- Qty & Price -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.qty') }} *
                            </label>
                            <input
                                v-model="form.qty"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                required
                                @input="checkStock"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('common.unit_price') }} *
                            </label>
                            <input
                                v-model="form.unit_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                required
                            />
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('common.notes') }}
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        ></textarea>
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-300 rounded-lg text-sm">
                        {{ error }}
                    </div>
                </form>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="close"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        @click="submit"
                        :disabled="processing || !canSubmit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ processing ? '...' : $t('common.add') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    modelValue: Boolean,
    workOrderId: [Number, String],
    workOrderItemId: [Number, String],
    warehouses: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const form = ref({
    source: 'warehouse',
    warehouse_id: '',
    part_id: '',
    name: '',
    qty: 1,
    unit_price: 0,
    notes: '',
});

const selectedPart = computed(() => props.parts.find(p => p.id === form.value.part_id));
const stockInfo = ref(null);
const processing = ref(false);
const error = ref('');

const onPartChange = (partId) => {
    const part = props.parts.find(p => p.id === partId);
    if (part) {
        form.value.name = part.name_ar || part.name_en;
        checkStock();
    } else {
        form.value.name = '';
        stockInfo.value = null;
    }
};

const canSubmit = computed(() => {
    if (form.value.source === 'warehouse') {
        return form.value.part_id && form.value.warehouse_id && form.value.qty > 0;
    }
    return form.value.name && form.value.qty > 0;
});

const close = () => {
    emit('update:modelValue', false);
    resetForm();
};

const resetForm = () => {
    form.value = {
        source: 'warehouse',
        warehouse_id: '',
        part_id: '',
        name: '',
        qty: 1,
        unit_price: 0,
        notes: '',
    };
    stockInfo.value = null;
    error.value = '';
};



const checkStock = async () => {
    if (!form.value.warehouse_id || !form.value.part_id) {
        stockInfo.value = null;
        return;
    }
    try {
        const { data } = await axios.get(route('work-orders.parts.check-stock'), {
            params: {
                warehouse_id: form.value.warehouse_id,
                part_id: form.value.part_id,
                qty: form.value.qty,
            }
        });
        stockInfo.value = data;
    } catch (e) {
        stockInfo.value = null;
    }
};

const submit = () => {
    if (!canSubmit.value) return;
    
    processing.value = true;
    error.value = '';

    router.post(route('work-orders.parts.store', props.workOrderId), {
        work_order_item_id: props.workOrderItemId,
        part_id: form.value.source === 'warehouse' ? form.value.part_id : null,
        warehouse_id: form.value.source === 'warehouse' ? form.value.warehouse_id : null,
        name: form.value.name,
        source: form.value.source,
        qty: form.value.qty,
        unit_price: form.value.unit_price,
        notes: form.value.notes,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
        onError: (errors) => {
            error.value = Object.values(errors).flat().join(', ');
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

watch(() => form.value.warehouse_id, checkStock);
</script>
