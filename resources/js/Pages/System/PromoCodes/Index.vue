<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.promo_codes.title')"
                :subtitle="$t('system.promo_codes.subtitle')"
                :totalCount="promoCodes ? toEnglish(promoCodes.length) : null"
                :countLabel="$t('system.promo_codes.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
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
                        {{ $t('system.promo_codes.create') }}
                    </AppButton>
                </template>
            </PageHeader>

            <!-- Promo Codes Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable structured class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.promo_codes.col.code') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.promo_codes.col.name') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.promo_codes.col.discount') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.promo_codes.col.usage') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.tenants.status') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.backups.col.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="code in promoCodes"
                                :key="code.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td class="px-6 py-4 align-middle text-center">
                                    <span
                                        class="font-mono font-bold text-action-primary bg-selection-bg px-2 py-1 rounded"
                                    >
                                        {{ code.code }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-gray-900 dark:text-white align-middle text-center"
                                >
                                    {{ code.name || $t('common.na') }}
                                </td>
                                <td
                                    class="px-6 py-4 text-gray-900 dark:text-white align-middle text-center"
                                >
                                    {{ code.discount_description }}
                                </td>
                                <td
                                    class="px-6 py-4 text-gray-600 dark:text-gray-400 align-middle text-center"
                                >
                                    {{ code.times_used }} /
                                    {{ code.max_uses || $t('system.promo_codes.unlimited') }}
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <span
                                        v-if="code.is_valid"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                    >
                                        {{ $t('system.promo_codes.status.valid') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                                    >
                                        {{
                                            $t(
                                                !code.is_active
                                                    ? 'system.promo_codes.status.inactive'
                                                    : 'system.promo_codes.status.expired'
                                            )
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            @click="openModal(code)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-action-primary hover:text-selection-text hover:bg-selection-bg rounded-lg transition-colors"
                                        >
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
                                            {{ $t('system.actions.edit') }}
                                        </button>
                                        <button
                                            @click="deleteCode(code)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                        >
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
                                            {{ $t('system.actions.delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>

                <!-- Empty State -->
                <div v-if="!promoCodes?.length" class="px-6 py-16 text-center">
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
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                        />
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $t('system.promo_codes.empty') }}
                    </p>
                    <button
                        @click="openModal()"
                        class="mt-4 inline-flex items-center text-sm font-medium text-action-primary hover:text-selection-text"
                    >
                        {{ $t('system.promo_codes.create_first') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Promo Code Form Modal -->
        <BaseModal
            :show="showModal"
            :title="
                editingCode
                    ? $t('system.promo_codes.edit_title')
                    : $t('system.promo_codes.create_title')
            "
            size="lg"
            @close="closeModal"
        >
            <!-- Form Body -->
            <form @submit.prevent="submit" class="p-6 space-y-5">
                <!-- Code & Name -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.promo_codes.form.code') }}
                        </label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <AppInput
                                    v-model="form.code"
                                    type="text"
                                    required
                                    dir="ltr"
                                    class="font-mono uppercase"
                                />
                            </div>
                            <button
                                v-if="!editingCode"
                                type="button"
                                @click="generateCode"
                                class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 text-sm"
                                :title="$t('system.promo_codes.form.generate')"
                            >
                                🎲
                            </button>
                        </div>
                    </div>
                    <AppInput
                        v-model="form.name"
                        type="text"
                        :label="$t('system.promo_codes.form.name')"
                        required
                    />
                </div>

                <!-- Discount -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ $t('system.promo_codes.form.discount') }}
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <SearchableSelect
                                v-model="form.discount_type"
                                :options="discountTypeOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.promo_codes.form.discount_type')"
                            />
                        </div>
                        <AppInput
                            v-model.number="form.discount_value"
                            type="number"
                            min="0"
                            :max="form.discount_type === 'percentage' ? 100 : 9999"
                            step="0.01"
                            :label="discountLabel"
                            required
                        />
                    </div>
                </div>

                <!-- Limits -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ $t('system.promo_codes.form.limits') }}
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <AppInput
                            v-model.number="form.max_uses"
                            type="number"
                            min="1"
                            :label="$t('system.promo_codes.form.max_uses')"
                            :placeholder="$t('system.promo_codes.unlimited')"
                        />
                        <AppInput
                            v-model.number="form.max_uses_per_tenant"
                            type="number"
                            min="1"
                            :label="$t('system.promo_codes.form.max_uses_per_tenant')"
                            required
                        />
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">
                                {{ $t('system.promo_codes.form.starts_at') }}
                            </label>
                            <input
                                v-model="form.starts_at"
                                type="datetime-local"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">
                                {{ $t('system.promo_codes.form.expires_at') }}
                            </label>
                            <input
                                v-model="form.expires_at"
                                type="datetime-local"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Restrictions -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        {{ $t('system.promo_codes.form.restrictions') }}
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <SearchableSelect
                                v-model="form.plan_id"
                                :options="planOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.promo_codes.form.plan')"
                                :placeholder="$t('system.promo_codes.all_plans')"
                            />
                        </div>
                        <div>
                            <SearchableSelect
                                v-model="form.billing_cycle"
                                :options="billingCycleOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.promo_codes.form.billing_cycle')"
                            />
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <AppToggle v-model="form.first_subscription_only" />
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $t('system.promo_codes.form.first_subscription_only') }}
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <AppToggle v-model="form.is_active" />
                        <span class="text-gray-700 dark:text-gray-300">
                            {{ $t('system.status.active') }}
                        </span>
                    </label>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3 w-full">
                    <AppButton variant="ghost" @click="closeModal">
                        {{ $t('system.actions.cancel') }}
                    </AppButton>
                    <AppButton
                        variant="action"
                        @click="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{
                            editingCode
                                ? $t('system.promo_codes.save_changes')
                                : $t('system.promo_codes.create_action')
                        }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { confirm } = useConfirm();
const { toEnglish } = useNumberFormat();

const discountTypeOptions = computed(() => [
    { value: 'percentage', label: t('system.promo_codes.discount_type.percentage') },
    { value: 'fixed', label: t('system.promo_codes.discount_type.fixed') },
    { value: 'trial_days', label: t('system.promo_codes.discount_type.trial_days') },
]);

const billingCycleOptions = computed(() => [
    { value: 'any', label: t('system.common.all') },
    {
        value: 'yearly',
        label: t('system.subscriptions.yearly') + ' (' + t('system.common.only') + ')',
    },
]);

const props = defineProps({
    promoCodes: Array,
    plans: Array,
});

const planOptions = computed(() =>
    (props.plans || []).map((p) => ({ value: p.id, label: p.name_ar }))
);

const showModal = ref(false);
const editingCode = ref(null);

const form = useForm({
    code: '',
    name: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 10,
    max_uses: null,
    max_uses_per_tenant: 1,
    starts_at: '',
    expires_at: '',
    plan_id: null,
    billing_cycle: 'any',
    first_subscription_only: true,
    is_active: true,
});

const discountLabel = computed(() => {
    return (
        {
            percentage: t('system.promo_codes.discount_value.percentage'),
            fixed: t('system.promo_codes.discount_value.fixed'),
            trial_days: t('system.promo_codes.discount_value.trial_days'),
        }[form.discount_type] || ''
    );
});

const openModal = (code = null) => {
    editingCode.value = code;
    if (code) {
        form.code = code.code || '';
        form.name = code.name || '';
        form.description = code.description || '';
        form.discount_type = code.discount_type || 'percentage';
        form.discount_value = code.discount_value || 10;
        form.max_uses = code.max_uses || null;
        form.max_uses_per_tenant = code.max_uses_per_tenant || 1;
        form.starts_at = code.starts_at || '';
        form.expires_at = code.expires_at || '';
        form.plan_id = code.plan_id || null;
        form.billing_cycle = code.billing_cycle || 'any';
        form.first_subscription_only = code.first_subscription_only ?? true;
        form.is_active = code.is_active ?? true;
    } else {
        form.reset();
        form.discount_type = 'percentage';
        form.discount_value = 10;
        form.max_uses_per_tenant = 1;
        form.first_subscription_only = true;
        form.is_active = true;
        generateCode();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCode.value = null;
};

const generateCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.code = code;
};

const submit = () => {
    if (editingCode.value) {
        form.put(`/system/promo-codes/${editingCode.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/system/promo-codes', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCode = async (code) => {
    const ok = await confirm({
        title: t('common.delete'),
        message: t('system.promo_codes.confirm_delete', { code: code.code }),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        router.delete(`/system/promo-codes/${code.id}`);
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
