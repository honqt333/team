<template>
    <BaseModal
        :show="true"
        size="sm"
        :title="color ? $t('system_settings.colors.edit') : $t('system_settings.colors.add')"
        @close="$emit('close')"
    >
        <form id="color-form" @submit.prevent="submit" class="space-y-4">
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

            <FormField
                v-slot="field"
                :label="$t('system_settings.form.hex_code')"
                :error="form.errors.hex_code"
            >
                <div class="flex items-center gap-3">
                    <input
                        v-model="form.hex_code"
                        type="color"
                        class="w-12 h-10 p-1 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer"
                    />
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.hex_code"
                        maxlength="7"
                        placeholder="#RRGGBB"
                        dir="ltr"
                        class="flex-1"
                    />
                </div>
            </FormField>

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
                    form="color-form"
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
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const props = defineProps({
    color: Object,
});

const emit = defineEmits(['close', 'saved']);
const { scrollToError } = useFormScrollToError();

const form = useForm({
    name_ar: props.color?.name_ar || '',
    name_en: props.color?.name_en || '',
    hex_code: props.color?.hex_code || '#000000',
    is_active: props.color?.is_active ?? true,
});

function submit() {
    const url = props.color ? `/app/settings/colors/${props.color.id}` : '/app/settings/colors';
    const method = props.color ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => emit('saved'),
        onError: () => scrollToError(form.errors),
    });
}
</script>
