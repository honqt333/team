<template>
    <BaseModal
        :show="true"
        size="sm"
        :title="model ? $t('system_settings.models.edit') : $t('system_settings.models.add')"
        @close="$emit('close')"
    >
        <form id="model-form" @submit.prevent="submit" class="space-y-4">
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
                v-model="form.make_id"
                :options="makeOptions"
                option-label="label"
                option-value="value"
                :label="$t('system_settings.form.make')"
                :placeholder="$t('common.choose')"
                required
                :error="form.errors.make_id"
            />

            <div
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
            >
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
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
                    form="model-form"
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
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useLocalized } from '@/Composables/useLocalized';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const { getName } = useLocalized();
const { scrollToError } = useFormScrollToError();

const props = defineProps({
    model: Object,
    makes: Array,
});

const emit = defineEmits(['close', 'saved']);

const makeOptions = computed(() => {
    return (props.makes || []).map((make) => ({
        value: make.id,
        label: getName(make),
    }));
});

const form = useForm({
    name_ar: props.model?.name_ar || '',
    name_en: props.model?.name_en || '',
    make_id: props.model?.make_id || '',
    is_active: props.model?.is_active ?? true,
});

function submit() {
    const url = props.model ? `/app/settings/models/${props.model.id}` : '/app/settings/models';
    const method = props.model ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => emit('saved'),
        onError: () => scrollToError(form.errors),
    });
}
</script>
