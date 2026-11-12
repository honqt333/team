<template>
    <BaseModal
        :show="true"
        size="sm"
        :title="
            incomeCategory
                ? $t('system_settings.income_categories.edit')
                : $t('system_settings.income_categories.add')
        "
        @close="$emit('close')"
    >
        <!-- Form -->
        <form id="income-category-form" @submit.prevent="submit" class="space-y-4">
            <!-- Name AR -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
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
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
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
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
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
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    form="income-category-form"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                >
                    {{ $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
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
