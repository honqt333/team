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
        <form id="income-category-form" @submit.prevent="submit" class="space-y-4">
            <FormField
                v-slot="field"
                :label="$t('system_settings.form.name_ar')"
                :error="form.errors.name_ar"
                required
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.name_ar"
                    :placeholder="$t('system_settings.form.name_ar_placeholder')"
                    class="w-full"
                    required
                />
            </FormField>

            <FormField
                v-slot="field"
                :label="$t('system_settings.form.name_en')"
                :error="form.errors.name_en"
                required
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.name_en"
                    :placeholder="$t('system_settings.form.name_en_placeholder')"
                    dir="ltr"
                    class="w-full"
                    required
                />
            </FormField>

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
                <AppButton variant="secondary" @click="$emit('close')" :disabled="form.processing">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    form="income-category-form"
                    variant="primary"
                    :loading="form.processing"
                >
                    {{ $t('common.save') }}
                </AppButton>
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
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const { t } = useI18n();
const { scrollToError } = useFormScrollToError();

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
        onSuccess: () => emit('saved'),
        onError: () => scrollToError(form.errors),
    });
}
</script>
