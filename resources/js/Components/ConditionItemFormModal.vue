<template>
    <BaseModal
        :show="show"
        size="md"
        :title="
            isEdit
                ? hasCategories
                    ? $t('services_management.edit_inspection')
                    : $t('system_settings.condition_items.edit')
                : hasCategories
                  ? $t('services_management.add_inspection')
                  : $t('system_settings.condition_items.add')
        "
        @close="close"
    >
        <form id="condition-item-form" @submit.prevent="submit" class="space-y-5">
            <SearchableSelect
                v-if="hasCategories"
                v-model="form.category_id"
                :options="categories"
                option-label="name"
                option-value="id"
                :label="$t('common.category')"
                :placeholder="$t('common.select')"
                :error="form.errors.category_id"
                required
            />

            <FormField
                v-slot="field"
                :label="
                    hasCategories
                        ? $t('services_management.form.name_ar')
                        : $t('system_settings.form.name_ar')
                "
                :error="form.errors.name_ar"
                required
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.name_ar"
                    :placeholder="
                        hasCategories ? '' : $t('system_settings.form.name_ar_placeholder')
                    "
                    class="w-full"
                    required
                />
            </FormField>

            <FormField
                v-slot="field"
                :label="
                    hasCategories
                        ? $t('services_management.form.name_en')
                        : $t('system_settings.form.name_en')
                "
                :error="form.errors.name_en"
                required
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.name_en"
                    :placeholder="
                        hasCategories ? '' : $t('system_settings.form.name_en_placeholder')
                    "
                    dir="ltr"
                    class="w-full"
                    required
                />
            </FormField>

            <div
                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700"
            >
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{
                            hasCategories
                                ? $t('common.active')
                                : $t('system_settings.form.is_active')
                        }}
                    </p>
                    <p v-if="hasCategories" class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('services_management.active_hint') }}
                    </p>
                </div>
                <AppToggle v-model="form.is_active" color="amber" />
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" @click="close" :disabled="form.processing">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    form="condition-item-form"
                    variant="primary"
                    :loading="form.processing"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const props = defineProps({
    show: {
        type: Boolean,
        default: true,
    },
    item: {
        type: Object,
        default: null,
    },
    conditionItem: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: null,
    },
    initialCategoryId: {
        type: [Number, String],
        default: '',
    },
});

const emit = defineEmits(['close', 'saved']);

const { scrollToError } = useFormScrollToError();
const activeItem = computed(() => props.item || props.conditionItem);
const isEdit = computed(() => !!activeItem.value);
const hasCategories = computed(() => Array.isArray(props.categories));

const form = useForm({
    name_ar: '',
    name_en: '',
    category_id: '',
    is_active: true,
});

watch(
    [() => props.show, activeItem],
    ([isVisible, current]) => {
        if (!isVisible) return;
        form.clearErrors();
        if (current) {
            form.name_ar = current.name_ar || '';
            form.name_en = current.name_en || '';
            form.category_id = current.category_id || '';
            form.is_active = current.is_active ?? true;
        } else {
            form.reset();
            if (props.initialCategoryId) {
                form.category_id = props.initialCategoryId;
            }
        }
    },
    { immediate: true }
);

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            close();
        },
        onError: () => scrollToError(form.errors),
    };

    if (hasCategories.value) {
        if (isEdit.value) {
            form.put(route('app.condition-items.update', activeItem.value.id), options);
        } else {
            form.post(route('app.condition-items.store'), options);
        }
        return;
    }

    const url = isEdit.value
        ? `/app/settings/condition-items/${activeItem.value.id}`
        : '/app/settings/condition-items';

    if (isEdit.value) {
        form.put(url, options);
    } else {
        form.post(url, options);
    }
};
</script>
