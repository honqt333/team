<template>
    <Modal :show="!!true" unstyled @close="$emit('close')">
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="$emit('close')"></div>

            <!-- Modal -->
            <div
                class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700"
                >
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{
                            incomeCategory
                                ? $t('system_settings.income_categories.edit')
                                : $t('system_settings.income_categories.add')
                        }}
                    </h3>
                    <button
                        @click="$emit('close')"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <!-- Name AR -->
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system_settings.form.name_ar') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name_ar"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent"
                            :placeholder="$t('system_settings.form.name_ar_placeholder')"
                        />
                        <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-500">
                            {{ form.errors.name_ar }}
                        </p>
                    </div>

                    <!-- Name EN -->
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system_settings.form.name_en') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name_en"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent"
                            :placeholder="$t('system_settings.form.name_en_placeholder')"
                        />
                        <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-500">
                            {{ form.errors.name_en }}
                        </p>
                    </div>

                    <!-- Transaction Type -->
                    <div>
                        <SearchableSelect
                            v-model="form.transaction_type"
                            :options="transactionTypeOptions"
                            option-label="label"
                            option-value="value"
                            :label="$t('system_settings.form.transaction_type')"
                            :placeholder="$t('common.choose')"
                            required
                            :error="form.errors.transaction_type"
                        />
                    </div>

                    <!-- Is Taxable (Default Taxable) -->
                    <div
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <div>
                            <label
                                class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                            >
                                {{ $t('company_profile.income_categories.form.is_taxable') }}
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $t('company_profile.income_categories.form.is_taxable_hint') }}
                            </p>
                        </div>
                        <AppToggle v-model="form.is_taxable" color="success" />
                    </div>

                    <!-- Is Active -->
                    <div
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ $t('system_settings.form.is_active') }}
                        </label>
                        <AppToggle v-model="form.is_active" />
                    </div>

                    <!-- Actions -->
                    <div
                        class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                    >
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-lg transition-all disabled:opacity-50"
                        >
                            {{ $t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    incomeCategory: Object,
});

const emit = defineEmits(['close', 'saved']);

const transactionTypeOptions = [
    { value: 'revenue', label: t('system_settings.form.transaction_type_revenue') },
    { value: 'expense', label: t('system_settings.form.transaction_type_expense') },
];

const form = useForm({
    name_ar: props.incomeCategory?.name_ar || '',
    name_en: props.incomeCategory?.name_en || '',
    transaction_type: props.incomeCategory?.transaction_type || 'revenue',
    is_taxable: props.incomeCategory?.is_taxable ?? true,
    is_active: props.incomeCategory?.is_active ?? true,
});

function submit() {
    const url = props.incomeCategory
        ? `/app/settings/income-categories/${props.incomeCategory.id}`
        : '/app/settings/income-categories';

    const method = props.incomeCategory ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            emit('saved');
        },
    });
}
</script>
