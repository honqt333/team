<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('app.inventory.parts.index')"
                        class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        {{ $t('common.back') }}
                    </Link>
                    <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ part ? $t('inventory.parts.edit') : $t('inventory.parts.add') }}
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SKU -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.sku') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.sku"
                            type="text"
                            dir="ltr"
                            :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500', form.errors.sku ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                            :placeholder="$t('inventory.parts.sku_placeholder')"
                        />
                        <p v-if="form.errors.sku" class="mt-1 text-sm text-red-500">{{ form.errors.sku }}</p>
                    </div>

                    <!-- Unit -->
                    <div>
                        <SearchableSelect
                            v-model="form.unit_id"
                            :options="unitOptions"
                            :label="$t('inventory.parts.unit')"
                            required
                            :error="form.errors.unit_id"
                        />
                    </div>

                    <!-- Name AR -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.name_ar') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name_ar"
                            type="text"
                            :class="['w-full px-4 py-2.5 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500', form.errors.name_ar ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                        />
                        <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-500">{{ form.errors.name_ar }}</p>
                    </div>

                    <!-- Name EN -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.name_en') }}
                        </label>
                        <input
                            v-model="form.name_en"
                            type="text"
                            dir="ltr"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Category -->
                    <div>
                        <SearchableSelect
                            v-model="form.category_id"
                            :options="categoryOptions"
                            :label="$t('inventory.parts.category')"
                            :placeholder="$t('inventory.parts.category_placeholder')"
                            :error="form.errors.category_id"
                        />
                    </div>

                    <!-- Default Sale Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.default_sale_price') }}
                        </label>
                        <input
                            v-model.number="form.default_sale_price"
                            type="number"
                            step="0.01"
                            min="0"
                            dir="ltr"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Min Qty -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.min_qty') }}
                        </label>
                        <input
                            v-model.number="form.min_qty"
                            type="number"
                            step="0.001"
                            min="0"
                            dir="ltr"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.min_qty_hint') }}</p>
                    </div>

                    <!-- Reorder Qty -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.reorder_qty') }}
                        </label>
                        <input
                            v-model.number="form.reorder_qty"
                            type="number"
                            step="0.001"
                            min="0"
                            dir="ltr"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('inventory.parts.description') }}
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Link
                        :href="route('app.inventory.parts.index')"
                        class="px-4 py-2.5 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        {{ $t('common.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { computed } from 'vue';

const props = defineProps({
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

const page = usePage();

const unitOptions = computed(() => {
    return props.units.map(unit => ({
        value: unit.id,
        label: isAr.value ? unit.name_ar : (unit.name_en || unit.name_ar)
    }));
});

const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        value: cat.id,
        label: isAr.value ? cat.name_ar : (cat.name_en || cat.name_ar)
    }));
});

const form = useForm({
    sku: props.part?.sku || '',
    name_ar: props.part?.name_ar || '',
    name_en: props.part?.name_en || '',
    unit_id: props.part?.unit_id || '',
    category_id: props.part?.category_id || '',
    description: props.part?.description || '',
    min_qty: props.part?.min_qty || 0,
    reorder_qty: props.part?.reorder_qty || 0,
    default_sale_price: props.part?.default_sale_price || 0,
});

const submit = () => {
    if (props.part) {
        form.put(route('app.inventory.parts.update', props.part.id));
    } else {
        form.post(route('app.inventory.parts.store'));
    }
};
</script>
