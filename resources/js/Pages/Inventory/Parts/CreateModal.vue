<template>
    <DialogModal :show="show" @close="handleClose">
        <template #title>
            {{ form.id ? $t('inventory.parts.edit') : $t('inventory.parts.add') }}
        </template>

        <template #content>
            <div class="space-y-8">
                <!-- Basic Information Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 dark:text-blue-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        {{ $t('inventory.parts.basic_info') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name AR -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.name_ar') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_ar"
                                type="text"
                                :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.name_ar ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                                :placeholder="$t('inventory.parts.name_ar_placeholder')"
                            />
                            <p v-if="form.errors.name_ar" class="mt-1 text-xs text-red-500">{{ form.errors.name_ar }}</p>
                        </div>

                        <!-- Name EN -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.name_en') }}
                            </label>
                            <input
                                v-model="form.name_en"
                                type="text"
                                dir="ltr"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all"
                                :placeholder="$t('inventory.parts.name_en_placeholder')"
                            />
                        </div>

                        <!-- SKU -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.sku') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.sku"
                                    type="text"
                                    dir="ltr"
                                    :class="['w-full pl-4 pr-10 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.sku ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                                    :placeholder="$t('inventory.parts.sku_placeholder')"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="form.errors.sku" class="mt-1 text-xs text-red-500">{{ form.errors.sku }}</p>
                        </div>

                        <!-- Category -->
                        <div>
                            <SearchableSelect
                                v-model="form.category_id"
                                :options="categoryOptions"
                                :label="$t('inventory.parts.category')"
                                :placeholder="$t('common.select')"
                                :error="form.errors.category_id"
                            />
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Inventory & Pricing Section -->
                <div>
                     <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="p-1.5 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600 dark:text-green-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        {{ $t('inventory.parts.inventory_pricing') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Unit -->
                        <div>
                            <SearchableSelect
                                v-model="form.unit_id"
                                :options="unitOptions"
                                :label="$t('inventory.parts.unit')"
                                :placeholder="$t('common.select')"
                                :error="form.errors.unit_id"
                                required
                            />
                        </div>

                        <!-- Default Sale Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.default_sale_price') }}
                            </label>
                            <div class="relative">
                                <input
                                    v-model.number="form.default_sale_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    dir="ltr"
                                    class="w-full pl-4 pr-12 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500 dark:text-gray-400 text-sm">
                                    {{ $t('common.currency_sar') }}
                                </div>
                            </div>
                        </div>

                         <!-- Reorder Settings (Grouped) -->
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.min_qty') }} / {{ $t('inventory.parts.reorder_qty') }}
                            </label>
                            <div class="flex gap-2">
                                <input
                                    v-model.number="form.min_qty"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    dir="ltr"
                                    :title="$t('inventory.parts.min_qty')"
                                    :placeholder="$t('inventory.parts.min_qty')"
                                    class="w-1/2 px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-shadow"
                                />
                                <input
                                    v-model.number="form.reorder_qty"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    dir="ltr"
                                    :title="$t('inventory.parts.reorder_qty')"
                                    :placeholder="$t('inventory.parts.reorder_qty')"
                                    class="w-1/2 px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all"
                                />
                            </div>
                             <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.min_qty_hint') }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Description -->
                <div>
                     <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="p-1.5 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-purple-600 dark:text-purple-400">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                            </svg>
                        </span>
                        {{ $t('inventory.parts.additional_info') }}
                    </h3>
                    <div>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            :placeholder="$t('inventory.parts.description_placeholder')"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <button
                @click="close"
                class="px-4 py-2.5 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 mr-3"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                @click="submit"
                :disabled="form.processing"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 transition-all disabled:opacity-50 disabled:shadow-none"
            >
                {{ form.processing ? $t('common.saving') : $t('common.save') }}
            </button>
        </template>
    </DialogModal>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: Boolean,
    part: Object,
    units: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const { t } = useI18n();
const { confirm } = useConfirm();
const page = usePage();

const unitOptions = computed(() => {
    return props.units.map(unit => ({
        value: unit.id,
        label: page.props.auth.user.locale === 'ar' ? unit.name_ar : (unit.name_en || unit.name_ar)
    }));
});

const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        value: cat.id,
        label: page.props.auth.user.locale === 'ar' ? cat.name_ar : (cat.name_en || cat.name_ar)
    }));
});

const isDirty = ref(false);
const initialFormData = ref(null);

const form = useForm({
    id: null,
    sku: '',
    name_ar: '',
    name_en: '',
    unit_id: '',
    category_id: '',
    description: '',
    min_qty: 0,
    reorder_qty: 0,
    default_sale_price: 0,
});

watch(() => props.part, (part) => {
    if (part) {
        form.id = part.id;
        form.sku = part.sku;
        form.name_ar = part.name_ar;
        form.name_en = part.name_en;
        form.unit_id = part.unit_id;
        form.category_id = part.category_id;
        form.description = part.description;
        form.min_qty = part.min_qty;
        form.reorder_qty = part.reorder_qty;
        form.default_sale_price = part.default_sale_price;
    } else {
        form.reset();
        form.id = null;
        form.unit_id = props.units.length > 0 ? props.units[0].id : '';
    }
}, { immediate: true });

// Handle modal open
watch(() => props.show, (open) => {
    if (open) {
        setTimeout(() => {
            initialFormData.value = JSON.stringify(form.data());
            isDirty.value = false;
        }, 100);
    }
});

// Track form changes
watch(() => form.data(), (newData) => {
    if (initialFormData.value) {
        const currentData = JSON.stringify(newData);
        isDirty.value = currentData !== initialFormData.value;
    }
}, { deep: true });

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
    isDirty.value = false;
    initialFormData.value = null;
};

async function handleClose() {
    if (isDirty.value) {
        const confirmed = await confirm({
            title: t('common.unsaved_changes'),
            message: t('common.unsaved_changes_message'),
            confirmText: t('common.yes_close'),
            cancelText: t('common.cancel'),
            type: 'warning',
        });
        if (!confirmed) return;
    }
    close();
}

const submit = () => {
    if (form.id) {
        form.put(route('app.inventory.parts.update', form.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('app.inventory.parts.store'), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    }
};
</script>
