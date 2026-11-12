<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';

const props = defineProps({
    show: Boolean,
    category: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const isEdit = computed(() => !!props.category);

const form = useForm({
    name_ar: '',
    name_en: '',
    is_active: true,
});

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            if (props.category) {
                form.name_ar = props.category.name_ar;
                form.name_en = props.category.name_en;
                form.is_active = props.category.is_active;
            } else {
                form.reset();
            }
        } else {
            form.clearErrors();
        }
    }
);

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('app.condition-categories.update', props.category.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('app.condition-categories.store'), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    }
};
</script>

<template>
    <BaseModal :show="!!show" size="md" @close="close">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="p-2.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                </div>
                <span class="text-lg font-bold text-gray-900 dark:text-white">
                    {{
                        isEdit
                            ? $t('services_management.edit_inspection')
                            : $t('services_management.add_inspection_category')
                    }}
                </span>
            </div>
        </template>

        <!-- Form -->
        <form id="condition-category-form" @submit.prevent="submit">
            <div class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormField
                        v-slot="field"
                        :label="$t('services_management.form.category_ar')"
                        :error="form.errors.name_ar"
                        required
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            v-model="form.name_ar"
                            class="w-full"
                            required
                        />
                    </FormField>
                    <FormField
                        v-slot="field"
                        :label="$t('services_management.form.category_en')"
                        :error="form.errors.name_en"
                        required
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            v-model="form.name_en"
                            dir="ltr"
                            class="w-full"
                            required
                        />
                    </FormField>
                </div>

                <!-- Active Toggle -->
                <div
                    class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $t('common.active') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('services_management.active_hint') }}
                        </p>
                    </div>
                    <AppToggle v-model="form.is_active" color="amber" />
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3 w-full">
                <button
                    type="button"
                    @click="close"
                    class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    form="condition-category-form"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                >
                    <svg
                        v-if="form.processing"
                        class="animate-spin h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span>{{ isEdit ? $t('common.save') : $t('common.add') }}</span>
                </button>
            </div>
        </template>
    </BaseModal>
</template>
