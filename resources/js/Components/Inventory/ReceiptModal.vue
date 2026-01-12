<template>
    <BaseModal :show="show" @close="$emit('close')" :title="$t('inventory.moves.receipt')">
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
                <p v-if="errors.part_id" class="mt-1 text-sm text-red-500">{{ errors.part_id }}</p>
            </div>

            <!-- Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('inventory.stock.qty') }} <span class="text-red-500">*</span>
                </label>
                <input
                    v-model.number="form.qty"
                    type="number"
                    step="0.001"
                    min="0.001"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                />
                <p v-if="errors.qty" class="mt-1 text-sm text-red-500">{{ errors.qty }}</p>
            </div>

            <!-- Unit Cost -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('inventory.moves.cost') }} <span class="text-red-500">*</span>
                </label>
                <input
                    v-model.number="form.unit_cost"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                />
                <p v-if="errors.unit_cost" class="mt-1 text-sm text-red-500">{{ errors.unit_cost }}</p>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.notes') }}
                </label>
                <textarea
                    v-model="form.notes"
                    rows="2"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                ></textarea>
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
                    class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    <span v-if="processing">{{ $t('common.saving') }}</span>
                    <span v-else>{{ $t('common.save') }}</span>
                </button>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    warehouseId: Number,
});

const emit = defineEmits(['close', 'saved']);

const { success, error: showError } = useToast();
const processing = ref(false);
const errors = reactive({});
const parts = ref([]);

const form = reactive({
    warehouse_id: props.warehouseId,
    part_id: null,
    qty: null,
    unit_cost: null,
    notes: '',
});

watch(() => props.warehouseId, (val) => {
    form.warehouse_id = val;
});

watch(() => props.show, (val) => {
    if (val) {
        // Reset form when modal opens
        form.part_id = null;
        form.qty = null;
        form.unit_cost = null;
        form.notes = '';
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
            default_sale_price: p.default_sale_price,
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
            default_sale_price: p.default_sale_price,
        }));
    } catch (e) {
        console.error('Search failed', e);
    }
}

function submit() {
    processing.value = true;
    Object.keys(errors).forEach(key => delete errors[key]);

    router.post(route('app.inventory.moves.receipt'), form, {
        preserveScroll: true,
        onSuccess: () => {
            success('تم تسجيل الاستلام بنجاح');
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
