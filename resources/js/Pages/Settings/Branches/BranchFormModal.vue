<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useToast } from '@/Composables/useToast';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { success, error } = useToast();
const { scrollToError } = useFormScrollToError();

const form = useForm({
    name_ar: '',
    name_en: '',
    center_type: '',
    manager_name: '',
    phone: '',
    email: '',
    is_active: true,
});

const centerTypeOptions = computed(() => [
    { value: 'main', label: t('company_profile.branches.center_type_main') },
    { value: 'branch', label: t('company_profile.branches.center_type_branch') },
    { value: 'workshop', label: t('company_profile.branches.center_type_workshop') },
    { value: 'warehouse', label: t('company_profile.branches.center_type_warehouse') },
]);

const close = () => {
    form.clearErrors();
    form.reset();
    emit('close');
};

const submit = () => {
    form.post(route('settings.branches.store'), {
        onSuccess: () => {
            success(t('company_profile.branches.created_successfully'));
            emit('saved');
            close();
        },
        onError: () => {
            error(t('common.error_occurred'));
            scrollToError(form.errors);
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <BaseModal :show="show" @close="close" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-action-primary/10 text-action-primary flex items-center justify-center"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-content-primary">
                        {{ t('company_profile.branches.add') }}
                    </h3>
                    <p class="text-xs text-content-muted">
                        {{ t('company_profile.branches.subtitle') }}
                    </p>
                </div>
            </div>
        </template>

        <form id="branch-form" @submit.prevent="submit" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                    v-slot="field"
                    :label="t('company_profile.branches.name_ar')"
                    :error="form.errors.name_ar"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.name_ar"
                        dir="rtl"
                        :placeholder="t('company_profile.branches.name_ar_placeholder')"
                        class="w-full"
                        required
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    :label="t('company_profile.branches.name_en')"
                    :error="form.errors.name_en"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.name_en"
                        dir="ltr"
                        :placeholder="t('company_profile.branches.name_en_placeholder')"
                        class="w-full"
                        required
                    />
                </FormField>
            </div>

            <SearchableSelect
                v-model="form.center_type"
                :options="centerTypeOptions"
                option-label="label"
                option-value="value"
                :label="t('company_profile.branches.center_type')"
                :placeholder="t('common.select')"
                required
                :error="form.errors.center_type"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField
                    v-slot="field"
                    :label="t('company_profile.branches.manager_name')"
                    :error="form.errors.manager_name"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.manager_name"
                        class="w-full"
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    :label="t('company_profile.branches.phone')"
                    :error="form.errors.phone"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.phone"
                        dir="ltr"
                        class="w-full"
                    />
                </FormField>
            </div>

            <FormField
                v-slot="field"
                :label="t('company_profile.branches.email')"
                :error="form.errors.email"
            >
                <AppInput
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.email"
                    type="email"
                    dir="ltr"
                    class="w-full"
                />
            </FormField>

            <div
                class="flex items-center justify-between p-4 bg-surface-secondary/70 rounded-xl border border-border-subtle"
            >
                <div>
                    <p class="text-sm font-bold text-content-primary">
                        {{ t('common.active') }}
                    </p>
                    <p class="text-xs text-content-muted mt-0.5">
                        {{ t('company_profile.branches.active_help') }}
                    </p>
                </div>
                <AppToggle v-model="form.is_active" color="success" />
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" @click="close" :disabled="form.processing">
                    {{ t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    form="branch-form"
                    variant="primary"
                    :loading="form.processing"
                >
                    {{ form.processing ? t('common.loading') : t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>
