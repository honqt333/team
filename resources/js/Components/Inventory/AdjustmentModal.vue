<template>
    <BaseModal :show="show" @close="$emit('close')" :title="$t('inventory.moves.adjust')">
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Part Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('inventory.parts.name') }} <span class="text-red-500">*</span>
                </label>
                <SearchableSelect
                    v-model="form.part_id"
                    :options="parts"
                    option-label="name"
                    option-value="id"
                    :placeholder="$t('inventory.parts.search_placeholder')"
                    :searchable="true"
                    @search="searchParts"
                />
                <p v-if="selectedPart" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('inventory.stock.available') }}: {{ formatQuantity(selectedPart.qty_on_hand || 0) }}
                </p>
                <p v-if="errors.part_id" class="mt-1 text-sm text-red-500">{{ errors.part_id }}</p>
            </div>

            <!-- Quantity (can be positive or negative) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('inventory.stock.qty') }} <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <button 
                        type="button"
                        @click="adjustmentType = 'increase'"
                        :class="[
                            'flex-1 py-2.5 rounded-lg font-medium transition-colors',
                            adjustmentType === 'increase' 
                                ? 'bg-green-600 text-white' 
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                        ]"
                    >
                        + زيادة
                    </button>
                    <button 
                        type="button"
                        @click="adjustmentType = 'decrease'"
                        :class="[
                            'flex-1 py-2.5 rounded-lg font-medium transition-colors',
                            adjustmentType === 'decrease' 
                                ? 'bg-red-600 text-white' 
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                        ]"
                    >
                        - نقص
                    </button>
                </div>
                <input
                    v-model.number="form.qty"
                    type="number"
                    step="0.001"
                    min="0.001"
                    class="w-full mt-3 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-yellow-500"
                    placeholder="أدخل الكمية"
                />
                <p v-if="errors.qty" class="mt-1 text-sm text-red-500">{{ errors.qty }}</p>
            </div>

            <!-- Notes (Required for adjustments) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.notes') }} <span class="text-red-500">*</span>
                </label>
                <textarea
                    v-model="form.notes"
                    rows="3"
                    required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-yellow-500"
                    :placeholder="$t('inventory.moves.adjust') + ' - السبب مطلوب'"
                ></textarea>
                <p v-if="errors.notes" class="mt-1 text-sm text-red-500">{{ errors.notes }}</p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    :disabled="processing"
                    class="px-4 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    <span v-if="processing">{{ $t('common.saving') }}</span>
                    <span v-else>{{ $t('common.save') }}</span>
                </button>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    warehouseId: Number,
});

const emit = defineEmits(['close', 'saved']);

const { success, error: showError } = useToast();
const { formatQuantity } = useNumberFormat();
const processing = ref(false);
const errors = reactive({});
const parts = ref([]);
const adjustmentType = ref('increase');

const form = reactive({
    warehouse_id: props.warehouseId,
    part_id: null,
    qty: null,
    notes: '',
});

const selectedPart = computed(() => {
    return parts.value.find(p => p.id === form.part_id);
});

watch(() => props.warehouseId, (val) => {
    form.warehouse_id = val;
});

watch(() => props.show, (val) => {
    if (val) {
        // Reset form when modal opens
        form.part_id = null;
        form.qty = null;
        form.notes = '';
        adjustmentType.value = 'increase';
        Object.keys(errors).forEach(key => delete errors[key]);
        loadParts();
    }
});

async function loadParts() {
    try {
        const response = await axios.get(route('app.inventory.parts.search'), { params: { q: '' } });
        parts.value = response.data.map(p => ({
            id: p.id,
            name: `${p.sku} - ${p.name_ar}`,
            qty_on_hand: p.inventory_balances_sum_qty_on_hand || 0,
        }));
    } catch (e) {
        console.error('Failed to load parts', e);
    }
}

async function searchParts(query) {
    try {
        const response = await axios.get(route('app.inventory.parts.search'), { params: { q: query } });
        parts.value = response.data.map(p => ({
            id: p.id,
            name: `${p.sku} - ${p.name_ar}`,
            qty_on_hand: p.inventory_balances_sum_qty_on_hand || 0,
        }));
    } catch (e) {
        console.error('Search failed', e);
    }
}

function submit() {
    processing.value = true;
    Object.keys(errors).forEach(key => delete errors[key]);

    // Calculate qty with sign based on adjustment type
    const qty = adjustmentType.value === 'decrease' ? -Math.abs(form.qty) : Math.abs(form.qty);

    router.post(route('app.inventory.moves.adjustment'), {
        ...form,
        qty,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            success('تم تسجيل التسوية بنجاح');
            emit('saved');
            emit('close');
        },
        onError: (errs) => {
            Object.assign(errors, errs);
            showError('حدث خطأ أثناء الحفظ');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>
