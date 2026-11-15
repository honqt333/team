<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    bay: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();
const toast = useToast();

const saving = ref(false);
const errors = ref({});

const form = ref({
    name: '',
    code: '',
    type: '',
    is_active: true,
    notes: '',
});

const isEdit = computed(() => Boolean(props.bay?.id));

const typeOptions = computed(() => [
    { value: '', label: t('service_bays.no_type') },
    { value: 'maintenance', label: t('service_bays.types.maintenance') },
    { value: 'polish', label: t('service_bays.types.polish') },
    { value: 'tires', label: t('service_bays.types.tires') },
    { value: 'electrical', label: t('service_bays.types.electrical') },
    { value: 'wash', label: t('service_bays.types.wash') },
    { value: 'inspection', label: t('service_bays.types.inspection') },
    { value: 'general', label: t('service_bays.types.general') },
]);

const resetForm = () => {
    errors.value = {};
    form.value = {
        name: '',
        code: '',
        type: '',
        is_active: true,
        notes: '',
    };
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            errors.value = {};
            if (props.bay) {
                form.value = {
                    name: props.bay.name || '',
                    code: props.bay.code || '',
                    type: props.bay.type || '',
                    is_active: Boolean(props.bay.is_active),
                    notes: props.bay.notes || '',
                };
            } else {
                resetForm();
            }
        } else {
            errors.value = {};
        }
    }
);

const close = () => {
    resetForm();
    emit('close');
};

const submit = async () => {
    saving.value = true;
    errors.value = {};

    const payload = {
        name: form.value.name,
        code: form.value.code || null,
        type: form.value.type || null,
        is_active: form.value.is_active,
        notes: form.value.notes || null,
    };

    try {
        if (isEdit.value) {
            await axios.put(route('service-bays.update', { serviceBay: props.bay.id }), payload, {
                headers: { Accept: 'application/json' },
            });
            toast.success(t('common.updated_success'));
        } else {
            await axios.post(route('service-bays.store'), payload, {
                headers: { Accept: 'application/json' },
            });
            toast.success(t('common.created_success'));
        }
        emit('saved');
        close();
        router.reload({ preserveScroll: true });
    } catch (error) {
        if (error.response?.status === 422 && error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
        toast.error(error.response?.data?.message || t('common.error_occurred'));
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <BaseModal :show="!!show" size="md" @close="close">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="p-2.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </div>
                <span class="text-lg font-bold text-text-primary">
                    {{ isEdit ? $t('service_bays.edit_bay') : $t('service_bays.add_bay') }}
                </span>
            </div>
        </template>

        <form id="service-bay-form" @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                    v-slot="field"
                    :label="$t('service_bays.name')"
                    :error="errors.name?.[0]"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.name"
                        :placeholder="$t('service_bays.name_placeholder')"
                        class="w-full"
                        required
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    :label="$t('service_bays.code')"
                    :error="errors.code?.[0]"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.code"
                        :placeholder="$t('service_bays.code_placeholder')"
                        class="w-full"
                    />
                </FormField>
            </div>

            <FormField
                v-slot="field"
                :label="$t('service_bays.type')"
                :error="errors.type?.[0]"
            >
                <SearchableSelect
                    :id="field.id"
                    v-model="form.type"
                    :options="typeOptions"
                    option-value="value"
                    option-label="label"
                    :placeholder="$t('service_bays.no_type')"
                />
            </FormField>

            <FormField
                v-slot="field"
                :label="$t('common.notes')"
                :error="errors.notes?.[0]"
            >
                <AppTextarea
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.notes"
                    :placeholder="$t('common.notes_placeholder')"
                    :rows="3"
                    class="w-full"
                />
            </FormField>

            <div
                class="flex items-center justify-between p-4 bg-surface-secondary/60 rounded-xl border border-border-subtle"
            >
                <span class="font-medium text-text-primary">
                    {{ $t('service_bays.active') }}
                </span>
                <AppToggle v-model="form.is_active" color="success" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3 w-full">
                <AppButton type="button" variant="secondary" @click="close">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    form="service-bay-form"
                    variant="primary"
                    :loading="saving"
                    :disabled="saving"
                >
                    {{ isEdit ? $t('common.save') : $t('common.add') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>
