<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.plans.title')"
                :subtitle="$t('system.plans.subtitle')"
                :totalCount="plans ? toEnglish(plans.length) : null"
                :countLabel="$t('system.plans.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                </template>

                <template #actions>
                    <AppButton variant="action" @click="openModal()">
                        <template #iconLeft>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </template>
                        {{ $t('system.plans.create') }}
                    </AppButton>
                </template>
            </PageHeader>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border overflow-hidden relative',
                        plan.is_featured
                            ? 'border-action-primary ring-2 ring-focus-ring'
                            : 'border-gray-200 dark:border-gray-700',
                    ]"
                >
                    <!-- Featured Badge -->
                    <div
                        v-if="plan.is_featured"
                        class="absolute top-4 left-4 px-2.5 py-1 bg-action-primary text-white text-xs font-medium rounded-full"
                    >
                        {{ $t('system.plans.featured') }}
                    </div>

                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4">
                        <span
                            :class="
                                plan.is_active
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                            "
                            class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                        >
                            {{
                                $t(plan.is_active ? 'system.plans.active' : 'system.plans.inactive')
                            }}
                        </span>
                    </div>

                    <div class="p-6 pt-12">
                        <!-- Plan Name -->
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ plan.name_ar }}
                        </h3>
                        <p
                            v-if="plan.slug"
                            class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                            dir="ltr"
                        >
                            {{ plan.slug }}
                        </p>

                        <!-- Subscribers Count -->
                        <div
                            class="mt-3 flex items-center gap-1.5 text-xs text-action-primary font-medium bg-selection-bg px-2.5 py-1 rounded-md w-fit"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                            <span>
                                {{ plan.active_subscribers_count || 0 }}
                                {{ $t('system.plans.subscribed_tenant') }}
                            </span>
                        </div>

                        <!-- Pricing (Yearly Only) -->
                        <div class="mt-6">
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ plan.price_yearly }}
                                </span>
                                <span class="text-sm font-semibold text-gray-500">
                                    {{ $t('system.plans.yearly_price') }}
                                </span>
                            </div>
                            <p
                                v-if="Number(plan.price_yearly) <= 0"
                                class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium"
                            >
                                {{ $t('system.plans.free_trial_for') }} {{ plan.trial_days }}
                                {{ $t('common.day') }}
                            </p>
                        </div>

                        <!-- Limits -->
                        <div class="mt-6 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div v-if="plan.limits?.max_users" class="flex items-center gap-2">
                                <svg
                                    class="w-4 h-4 text-emerald-500 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span>
                                    {{ $t('system.plans.max_users', { n: plan.limits.max_users }) }}
                                </span>
                            </div>
                            <div v-if="plan.limits?.max_centers" class="flex items-center gap-2">
                                <svg
                                    class="w-4 h-4 text-emerald-500 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span>
                                    {{
                                        $t('system.plans.max_centers', {
                                            n: plan.limits.max_centers,
                                        })
                                    }}
                                </span>
                            </div>
                            <div v-if="plan.limits?.storage_gb" class="flex items-center gap-2">
                                <svg
                                    class="w-4 h-4 text-emerald-500 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <span>
                                    {{
                                        $t('system.plans.storage_gb', { n: plan.limits.storage_gb })
                                    }}
                                </span>
                            </div>
                        </div>

                        <!-- Trial -->
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.plans.trial_period', { days: plan.trial_days }) }}
                        </p>

                        <!-- Actions -->
                        <div class="mt-6 flex gap-2">
                            <AppButton
                                variant="secondary"
                                size="sm"
                                class="flex-1"
                                @click="openModal(plan)"
                            >
                                <template #iconLeft>
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </template>
                                {{ $t('system.actions.edit') }}
                            </AppButton>
                            <AppButton variant="danger" size="sm" @click="deletePlan(plan)">
                                <template #iconLeft>
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </template>
                                {{ $t('system.actions.delete') }}
                            </AppButton>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="!plans?.length"
                    class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700"
                >
                    <svg
                        class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $t('system.plans.empty') }}
                    </p>
                    <button
                        @click="openModal()"
                        class="mt-4 inline-flex items-center text-sm font-medium text-action-primary hover:text-selection-text"
                    >
                        {{ $t('system.plans.create_first') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Plan Form Modal -->
        <BaseModal
            :show="showModal"
            :title="editingPlan ? $t('system.plans.edit_title') : $t('system.plans.create_title')"
            size="lg"
            @close="closeModal"
        >
            <!-- Form Body -->
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <!-- Basic Info -->
                <div class="grid grid-cols-2 gap-4">
                    <AppInput
                        v-model="form.name_ar"
                        type="text"
                        :label="$t('system.plans.form.name_ar')"
                        required
                    />
                    <AppInput
                        v-model="form.name_en"
                        type="text"
                        :label="$t('system.plans.form.name_en')"
                        required
                        dir="ltr"
                    />
                    <AppInput
                        v-model="form.slug"
                        type="text"
                        :label="$t('system.plans.form.slug')"
                        :placeholder="$t('system.plans.form.slug_placeholder')"
                        required
                        dir="ltr"
                    />
                    <AppInput
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        :label="$t('system.plans.form.sort_order')"
                    />
                    <div class="col-span-2">
                        <AppTextarea
                            v-model="form.description_ar"
                            :rows="2"
                            :label="$t('system.plans.form.description_ar')"
                        />
                    </div>
                    <div class="col-span-2">
                        <AppTextarea
                            v-model="form.description_en"
                            :rows="2"
                            :label="$t('system.plans.form.description_en')"
                            dir="ltr"
                        />
                    </div>
                </div>

                <!-- Pricing -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ $t('system.plans.form.pricing') }}
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <AppInput
                            v-model.number="form.price_yearly"
                            type="number"
                            min="0"
                            step="0.01"
                            :label="$t('system.plans.form.price_yearly')"
                            required
                        />
                        <AppInput
                            v-model.number="form.trial_days"
                            type="number"
                            min="0"
                            :label="$t('system.plans.form.trial_days')"
                            required
                        />
                    </div>
                </div>

                <!-- Limits -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ $t('system.plans.form.limits') }}
                    </h4>
                    <div class="grid grid-cols-3 gap-4">
                        <AppInput
                            v-model.number="form.limits.max_users"
                            type="number"
                            min="1"
                            :label="$t('system.plans.form.max_users')"
                        />
                        <AppInput
                            v-model.number="form.limits.max_centers"
                            type="number"
                            min="1"
                            :label="$t('system.plans.form.max_centers')"
                        />
                        <AppInput
                            v-model.number="form.limits.storage_gb"
                            type="number"
                            min="1"
                            :label="$t('system.plans.form.storage_gb')"
                        />
                    </div>
                </div>

                <!-- Status -->
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <AppToggle v-model="form.is_active" />
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $t('system.plans.active') }}
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <AppToggle v-model="form.is_featured" />
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $t('system.plans.featured') }}
                        </span>
                    </label>
                </div>

                <!-- Features (Arabic & English) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            {{ $t('system.plans.form.features_ar_title') }}
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(feature, index) in form.features_ar"
                                :key="'ar-' + index"
                                class="flex gap-2"
                            >
                                <div class="flex-1">
                                    <AppInput
                                        v-model="form.features_ar[index]"
                                        type="text"
                                        :placeholder="
                                            $t('system.plans.form.feature_placeholder_ar', {
                                                n: index + 1,
                                            })
                                        "
                                    />
                                </div>
                                <button
                                    @click="form.features_ar.splice(index, 1)"
                                    type="button"
                                    class="px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                >
                                    ✕
                                </button>
                            </div>
                            <button
                                @click="form.features_ar.push('')"
                                type="button"
                                class="text-sm text-action-primary hover:text-selection-text"
                            >
                                {{ $t('system.plans.form.add_feature_ar') }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            {{ $t('system.plans.form.features_en_title') }}
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(feature, index) in form.features_en"
                                :key="'en-' + index"
                                class="flex gap-2"
                            >
                                <div class="flex-1">
                                    <AppInput
                                        v-model="form.features_en[index]"
                                        type="text"
                                        :placeholder="
                                            $t('system.plans.form.feature_placeholder_en', {
                                                n: index + 1,
                                            })
                                        "
                                    />
                                </div>
                                <button
                                    @click="form.features_en.splice(index, 1)"
                                    type="button"
                                    class="px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                >
                                    ✕
                                </button>
                            </div>
                            <button
                                @click="form.features_en.push('')"
                                type="button"
                                class="text-sm text-action-primary hover:text-selection-text"
                            >
                                {{ $t('system.plans.form.add_feature_en') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3 w-full">
                    <AppButton variant="ghost" @click="closeModal">
                        {{ $t('system.actions.cancel') }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        @click="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{
                            editingPlan
                                ? $t('system.plans.save_changes')
                                : $t('system.plans.create')
                        }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>
    </SystemLayout>
</template>

<script setup>
import AppButton from '@/Components/App/AppButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { confirm } = useConfirm();
const { toEnglish } = useNumberFormat();

defineProps({
    plans: Array,
});

const showModal = ref(false);
const editingPlan = ref(null);

const form = useForm({
    name_ar: '',
    name_en: '',
    slug: '',
    description_ar: '',
    description_en: '',
    price_yearly: 0,
    trial_days: 14,
    limits: {
        max_users: 5,
        max_centers: 1,
        storage_gb: 10,
    },
    features_ar: [],
    features_en: [],
    is_active: true,
    is_featured: false,
    sort_order: 0,
});

const openModal = (plan = null) => {
    editingPlan.value = plan;
    if (plan) {
        form.name_ar = plan.name_ar || '';
        form.name_en = plan.name_en || '';
        form.slug = plan.slug || '';
        form.description_ar = plan.description_ar || '';
        form.description_en = plan.description_en || '';
        form.price_yearly = plan.price_yearly || 0;
        form.trial_days = plan.trial_days || 14;
        form.limits = {
            max_users: plan.limits?.max_users || 5,
            max_centers: plan.limits?.max_centers || 1,
            storage_gb: plan.limits?.storage_gb || 10,
        };
        form.features_ar = plan.features_ar || [];
        form.features_en = plan.features_en || [];
        form.is_active = plan.is_active ?? true;
        form.is_featured = plan.is_featured ?? false;
        form.sort_order = plan.sort_order || 0;
    } else {
        form.reset();
        form.limits = { max_users: 5, max_centers: 1, storage_gb: 10 };
        form.features_ar = [];
        form.features_en = [];
        form.is_active = true;
        form.trial_days = 14;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingPlan.value = null;
};

const submit = () => {
    if (editingPlan.value) {
        form.put(`/system/plans/${editingPlan.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/system/plans', {
            onSuccess: () => closeModal(),
        });
    }
};

const deletePlan = async (plan) => {
    const ok = await confirm({
        title: t('common.delete'),
        message: t('system.plans.confirm_delete', { name: plan.name_ar }),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        router.delete(`/system/plans/${plan.id}`);
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
