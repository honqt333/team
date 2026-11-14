<template>
    <BaseModal :show="!!show" size="md" z-index-class="z-[70]" @close="$emit('close')">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center"
                >
                    <svg
                        class="w-6 h-6 text-amber-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ term.id ? $t('common.edit') : $t('common.add') }}
                </span>
            </div>
        </template>

        <form id="term-edit-form" @submit.prevent="handleSave" class="space-y-5">
            <FormField v-slot="field" :label="$t('print_settings.name_ar')">
                <AppTextarea :id="field.id" v-model="localTerm.text_ar" :rows="4" class="w-full" />
            </FormField>

            <FormField v-slot="field" :label="$t('print_settings.name_en')">
                <AppTextarea
                    :id="field.id"
                    v-model="localTerm.text_en"
                    :rows="4"
                    dir="ltr"
                    class="w-full"
                />
            </FormField>

            <FormField v-slot="field" :label="$t('print_settings.order_by')">
                <AppInput :id="field.id" v-model="localTerm.order" type="number" class="w-full" />
            </FormField>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton type="submit" form="term-edit-form" variant="primary">
                    {{ $t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
    term: {
        type: Object,
        default: () => ({
            text_ar: '',
            text_en: '',
            order: 1,
        }),
    },
});

const emit = defineEmits(['close', 'save']);

const localTerm = ref({ ...props.term });

function handleSave() {
    emit('save', { ...localTerm.value });
    emit('close');
}
</script>
