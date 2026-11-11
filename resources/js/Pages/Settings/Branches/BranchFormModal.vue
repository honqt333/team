<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { success, error } = useToast();

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
        onError: () => error(t('common.error_occurred')),
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

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Name AR + Name EN -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-content-secondary mb-1.5">
                        {{ t('company_profile.branches.name_ar') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input
                        v-model="form.name_ar"
                        type="text"
                        required
                        dir="rtl"
                        :class="[
                            'w-full px-4 py-2.5 rounded-xl border bg-surface-secondary text-content-primary focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all',
                            form.errors.name_ar ? 'border-danger' : 'border-border-subtle',
                        ]"
                        :placeholder="t('company_profile.branches.name_ar_placeholder')"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1.5 text-xs text-danger">
                        {{ form.errors.name_ar }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary mb-1.5">
                        {{ t('company_profile.branches.name_en') }}
                        <span class="text-danger">*</span>
                    </label>
                    <input
                        v-model="form.name_en"
                        type="text"
                        required
                        dir="ltr"
                        :class="[
                            'w-full px-4 py-2.5 rounded-xl border bg-surface-secondary text-content-primary focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all',
                            form.errors.name_en ? 'border-danger' : 'border-border-subtle',
                        ]"
                        :placeholder="t('company_profile.branches.name_en_placeholder')"
                    />
                    <p v-if="form.errors.name_en" class="mt-1.5 text-xs text-danger">
                        {{ form.errors.name_en }}
                    </p>
                </div>
            </div>

            <!-- Center Type -->
            <div>
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
            </div>

            <!-- Manager + Phone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-content-secondary mb-1.5">
                        {{ t('company_profile.branches.manager_name') }}
                    </label>
                    <input
                        v-model="form.manager_name"
                        type="text"
                        :class="[
                            'w-full px-4 py-2.5 rounded-xl border bg-surface-secondary text-content-primary focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all',
                            form.errors.manager_name ? 'border-danger' : 'border-border-subtle',
                        ]"
                    />
                    <p v-if="form.errors.manager_name" class="mt-1.5 text-xs text-danger">
                        {{ form.errors.manager_name }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-content-secondary mb-1.5">
                        {{ t('company_profile.branches.phone') }}
                    </label>
                    <input
                        v-model="form.phone"
                        type="text"
                        dir="ltr"
                        :class="[
                            'w-full px-4 py-2.5 rounded-xl border bg-surface-secondary text-content-primary focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all',
                            form.errors.phone ? 'border-danger' : 'border-border-subtle',
                        ]"
                    />
                    <p v-if="form.errors.phone" class="mt-1.5 text-xs text-danger">
                        {{ form.errors.phone }}
                    </p>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-content-secondary mb-1.5">
                    {{ t('company_profile.branches.email') }}
                </label>
                <input
                    v-model="form.email"
                    type="email"
                    dir="ltr"
                    :class="[
                        'w-full px-4 py-2.5 rounded-xl border bg-surface-secondary text-content-primary focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-all',
                        form.errors.email ? 'border-danger' : 'border-border-subtle',
                    ]"
                />
                <p v-if="form.errors.email" class="mt-1.5 text-xs text-danger">
                    {{ form.errors.email }}
                </p>
            </div>

            <!-- Active Toggle -->
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
            <button
                type="button"
                @click="close"
                class="px-4 py-2 text-content-secondary hover:text-content-primary font-bold transition-colors cursor-pointer"
            >
                {{ t('common.cancel') }}
            </button>
            <button
                @click="submit"
                :disabled="form.processing"
                class="px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs disabled:opacity-50 disabled:cursor-not-allowed transition-colors cursor-pointer"
            >
                {{ form.processing ? t('common.loading') : t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>
