<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 dark:bg-gray-900/80 backdrop-blur-sm transition-all duration-300"
    >
        <!-- Modal Panel -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50"
            >
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
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{
                                isEdit
                                    ? $t('services_management.edit_inspection')
                                    : $t('services_management.add_inspection_category')
                            }}
                        </h2>
                    </div>
                </div>
                <button
                    @click="close"
                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <form @submit.prevent="submit" class="p-6">
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

                <!-- Footer -->
                <div
                    class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        type="button"
                        @click="close"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-amber-600 border border-transparent rounded-xl hover:shadow-md hover:shadow-amber-500/20 transition-all shadow-sm flex items-center gap-2"
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
            </form>
        </div>
    </div>
</template>
