<template>
    <!-- Security Settings Modal -->
    <div v-if="modals.security" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'security')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.modals.security_title') }}
                </h3>
                <form @submit.prevent="$emit('save-security')">
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer mb-4">
                            <input
                                type="checkbox"
                                :checked="securityForm.two_factor_enabled"
                                @change="securityForm.two_factor_enabled = $event.target.checked"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $t('system.tenants.modals.two_factor_label') }}
                            </span>
                        </label>

                        <div v-if="securityForm.two_factor_enabled">
                            <SearchableSelect
                                :model-value="securityForm.two_factor_enforcement"
                                @update:model-value="securityForm.two_factor_enforcement = $event"
                                :options="twoFactorEnforcementOptions"
                                option-label="label"
                                option-value="value"
                                :label="$t('system.tenants.modals.enforcement_label')"
                            />
                            <p class="text-xs text-gray-500 mt-2">
                                {{ $t('system.tenants.modals.enforcement_hint') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button
                            type="button"
                            @click="$emit('close', 'security')"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="securityForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg"
                        >
                            {{ $t('common.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suspend Modal -->
    <div v-if="modals.suspend" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'suspend')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.modals.suspend_title') }}
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('system.tenants.modals.suspend_reason') }}
                    </label>
                    <textarea
                        v-model="local.suspendReason"
                        rows="3"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    ></textarea>
                </div>
                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        @click="$emit('close', 'suspend')"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="emitSuspend"
                        :disabled="!local.suspendReason"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg disabled:opacity-50"
                    >
                        {{ $t('system.tenants.actions.suspend') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Extend Trial Modal -->
    <div v-if="modals.extend" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'extend')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('system.tenants.modals.extend_title') }}
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('system.tenants.modals.extend_days') }}
                    </label>
                    <input
                        v-model.number="local.extendDays"
                        type="number"
                        min="1"
                        max="365"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        dir="ltr"
                    />
                    <p class="text-xs text-gray-500 mt-2">
                        {{ $t('system.tenants.modals.extend_hint') }}
                    </p>
                </div>
                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        @click="$emit('close', 'extend')"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="emitExtend"
                        :disabled="!local.extendDays"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg disabled:opacity-50"
                    >
                        {{ $t('system.tenants.actions.extend_trial') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="modals.delete" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'delete')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">
                    {{ $t('system.tenants.modals.delete_title') }}
                </h3>
                <div
                    class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800"
                >
                    <p class="text-sm text-red-700 dark:text-red-300">
                        {{ $t('system.tenants.modals.delete_warning', { name: tenant.name }) }}
                    </p>
                    <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                        <li>
                            {{
                                $t('system.tenants.modals.delete_users', { count: analytics.users })
                            }}
                        </li>
                        <li>
                            {{
                                $t('system.tenants.modals.delete_centers', {
                                    count: analytics.centers,
                                })
                            }}
                        </li>
                        <li>
                            {{
                                $t('system.tenants.modals.delete_customers', {
                                    count: analytics.customers,
                                })
                            }}
                        </li>
                        <li>
                            {{
                                $t('system.tenants.modals.delete_work_orders', {
                                    count: analytics.work_orders,
                                })
                            }}
                        </li>
                    </ul>
                    <p class="mt-2 text-sm text-red-700 dark:text-red-300 font-bold">
                        {{ $t('system.tenants.modals.delete_irreversible') }}
                    </p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{
                            $t('system.tenants.modals.delete_confirm_label', { name: tenant.name })
                        }}
                    </label>
                    <input
                        v-model="local.deleteConfirmation"
                        type="text"
                        class="w-full px-4 py-2.5 rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    />
                </div>
                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        @click="$emit('close', 'delete')"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="emitDelete"
                        :disabled="local.deleteConfirmation !== tenant.name"
                        :class="
                            local.deleteConfirmation === tenant.name
                                ? 'bg-red-600 hover:bg-red-700'
                                : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'
                        "
                        class="px-4 py-2 text-white rounded-lg"
                    >
                        {{ $t('system.tenants.actions.delete_permanent') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Plan Modal -->
    <div v-if="modals.assignPlan" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'assignPlan')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-lg w-full shadow-xl"
            >
                <div
                    class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700"
                >
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
                        >
                            <span
                                class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"
                                    />
                                </svg>
                            </span>
                            {{ $t('system.tenants.assign_upgrade_a_package_for_the_tenant') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $t('system.tenants.link_the_tenant') }}
                            <strong>{{ tenant.trade_name || tenant.name }}</strong>
                            {{
                                $t(
                                    'system.tenants.to_a_new_package_and_activate_the_subscription_i'
                                )
                            }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close', 'assignPlan')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAssignPlan" class="space-y-4">
                    <!-- Plan Selection -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.tenants.select_the_package') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="local.assignPlanId"
                            required
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="" disabled>
                                {{ $t('system.tenants.--_select_a_package_--') }}
                            </option>
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{
                                    plan.name_ar ||
                                    plan.name_en + $t('system.tenants.yearly_subscription')
                                }}
                                {{ plan.price_yearly + $t('system.tenants.static_template_text') }}
                            </option>
                        </select>
                    </div>

                    <!-- Billing Cycle (Fixed Annual) -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.tenants.billing_cycle') }}
                        </label>
                        <div
                            class="flex items-center justify-between p-3 rounded-xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300"
                        >
                            <span class="font-bold text-sm flex items-center gap-2">
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
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                {{ $t('system.tenants.yearly_subscription_12_months') }}
                            </span>
                            <span
                                class="text-xs bg-indigo-600 text-white px-2 py-0.5 rounded-full font-medium"
                            >
                                {{ $t('system.tenants.show.yearly') }}
                            </span>
                        </div>
                    </div>

                    <!-- Price Preview Card -->
                    <div
                        v-if="selectedPlanObj"
                        class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 space-y-2 text-xs"
                    >
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">
                                {{ $t('system.tenants.package_type') }}
                            </span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ selectedPlanObj.name_ar || selectedPlanObj.name_en }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">
                                {{ $t('system.tenants.subscription_period') }}
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{
                                    selectedPlanObj.slug === 'trial'
                                        ? $t('system.tenants.trial_days', {
                                              days: selectedPlanObj.trial_days || 14,
                                          })
                                        : $t('system.tenants.full_year_12_months')
                                }}
                            </span>
                        </div>
                        <div
                            class="flex justify-between items-center text-sm pt-2 border-t border-gray-200 dark:border-gray-600 font-bold"
                        >
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $t('system.tenants.total_amount') }}
                            </span>
                            <span class="text-indigo-600 dark:text-indigo-400">
                                {{ selectedPlanObj.price_yearly + $t('common.currency') }}
                            </span>
                        </div>
                    </div>

                    <!-- Notice -->
                    <p
                        class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 p-2.5 rounded-lg border border-amber-200 dark:border-amber-800"
                    >
                        {{ $t('system.tenants.the_subscription_will_be_activated_for_the_tenan') }}
                    </p>

                    <!-- Modal Actions -->
                    <div class="flex gap-3 justify-end pt-2">
                        <button
                            type="button"
                            @click="$emit('close', 'assignPlan')"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-sm"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="!local.assignPlanId || local.isSubmitting"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 transition-colors shadow-sm"
                        >
                            <span v-if="local.isSubmitting">{{ $t('common.saving') }}</span>
                            <span v-else>
                                {{ $t('system.tenants.confirm_and_activate_the_package') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    modals: { type: Object, required: true },
    tenant: { type: Object, required: true },
    analytics: { type: Object, required: true },
    securityForm: { type: Object, required: true },
    plans: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'close',
    'save-security',
    'confirm-suspend',
    'confirm-extend',
    'confirm-delete',
]);

const { t } = useI18n();

// Local state for modal inputs. We use reactive() so we can v-model
// directly. Parent owns the form/values that matter for submission;
// these are scratch inputs cleared when the modal opens/closes.
const local = reactive({
    suspendReason: '',
    extendDays: 14,
    deleteConfirmation: '',
    assignPlanId: '',
    assignBillingCycle: 'monthly',
    isSubmitting: false,
});

const selectedPlanObj = computed(() => {
    return (props.plans || []).find((p) => p.id === Number(local.assignPlanId));
});

// Reset scratch inputs when a modal closes so reopening starts fresh.
watch(
    () => props.modals.assignPlan,
    (open) => {
        if (open) {
            local.assignPlanId = props.plans[0]?.id || '';
            local.assignBillingCycle = 'monthly';
            local.isSubmitting = false;
        }
    }
);
watch(
    () => props.modals.suspend,
    (open) => {
        if (!open) local.suspendReason = '';
    }
);
watch(
    () => props.modals.extend,
    (open) => {
        if (!open) local.extendDays = 14;
    }
);
watch(
    () => props.modals.delete,
    (open) => {
        if (!open) local.deleteConfirmation = '';
    }
);

const submitAssignPlan = () => {
    if (!local.assignPlanId) return;
    local.isSubmitting = true;
    router.post(
        '/system/subscriptions',
        {
            tenant_id: props.tenant.id,
            plan_id: local.assignPlanId,
            billing_cycle: local.assignBillingCycle,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                local.isSubmitting = false;
                emit('close', 'assignPlan');
            },
            onError: () => {
                local.isSubmitting = false;
            },
        }
    );
};

const emitSuspend = () => emit('confirm-suspend', local.suspendReason);
const emitExtend = () => emit('confirm-extend', local.extendDays);
const emitDelete = () => emit('confirm-delete', local.deleteConfirmation);

const twoFactorEnforcementOptions = computed(() => [
    { value: 'disabled', label: t('system.tenants.modals.enforcement_disabled') },
    { value: 'optional', label: t('system.tenants.modals.enforcement_optional') },
    { value: 'required', label: t('system.tenants.modals.enforcement_required') },
]);
</script>
