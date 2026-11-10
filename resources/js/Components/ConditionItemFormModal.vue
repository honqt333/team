<template>
    <Modal :show="show" :max-width="'md'" @close="close">
        <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-xl">
            <!-- Header -->
            <div
                class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center"
                    >
                        <svg
                            class="w-5 h-5 text-amber-600 dark:text-amber-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{
                                isEdit
                                    ? $t('services_management.edit_inspection')
                                    : $t('services_management.add_inspection')
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
                    <!-- Category Dropdown -->
                    <div>
                        <SearchableSelect
                            v-model="form.category_id"
                            :options="categories"
                            option-label="name"
                            option-value="id"
                            :label="$t('common.category')"
                            :placeholder="$t('common.select')"
                            :error="form.errors.category_id"
                            required
                        />
                    </div>

                    <!-- Arabic Name -->
                    <FormField
                        v-slot="field"
                        :label="$t('services_management.form.name_ar')"
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

                    <!-- English Name -->
                    <FormField
                        v-slot="field"
                        :label="$t('services_management.form.name_en')"
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

                <!-- Footer Actions -->
                <div class="mt-8 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="close"
                        class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-medium"
                        :disabled="form.processing"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="px-5 py-2.5 text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 rounded-xl shadow-lg shadow-amber-500/30 transition-all font-medium flex items-center justify-center min-w-[120px]"
                        :disabled="form.processing"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                        {{ form.processing ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';

const props = defineProps({
    show: Boolean,
    item: Object,
    categories: {
        type: Array,
        default: () => [],
    },
    initialCategoryId: {
        type: [Number, String],
        default: '',
    },
});

const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.item);

const form = useForm({
    name_ar: '',
    name_en: '',
    category_id: '',
    is_active: true,
});

watch(
    () => props.show,
    (show) => {
        if (show) {
            if (props.item) {
                form.name_ar = props.item.name_ar || '';
                form.name_en = props.item.name_en || '';
                form.category_id = props.item.category_id || '';
                form.is_active = props.item.is_active ?? true;
            } else {
                form.reset();
                if (props.initialCategoryId) {
                    form.category_id = props.initialCategoryId;
                }
            }
        }
    }
);

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('app.condition-items.update', props.item.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                close();
            },
        });
    } else {
        form.post(route('app.condition-items.store'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                close();
            },
        });
    }
};
</script>
