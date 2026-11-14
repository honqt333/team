<template>
    <BaseModal
        :show="true"
        size="sm"
        :title="make ? $t('system_settings.makes.edit') : $t('system_settings.makes.add')"
        @close="$emit('close')"
    >
        <form id="make-form" @submit.prevent="submit" class="space-y-4">
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

            <AppFileUpload
                v-model="form.logo"
                variant="image"
                :label="$t('system_settings.form.logo')"
                :existing-url="form.logo_path ? `/storage/${form.logo_path}` : ''"
                accept="image/*"
                :max-size-mb="2"
                :error="form.errors.logo"
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
                    form="make-form"
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
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const props = defineProps({
    make: Object,
});

const emit = defineEmits(['close', 'saved']);
const { scrollToError } = useFormScrollToError();

const form = useForm({
    name_ar: props.make?.name_ar || '',
    name_en: props.make?.name_en || '',
    is_active: props.make?.is_active ?? true,
    logo: null,
    logo_path: props.make?.logo_path || '',
    _method: props.make ? 'put' : undefined,
});

function submit() {
    const url = props.make ? `/app/settings/makes/${props.make.id}` : '/app/settings/makes';

    form.post(url, {
        onSuccess: () => emit('saved'),
        onError: () => scrollToError(form.errors),
    });
}
</script>
