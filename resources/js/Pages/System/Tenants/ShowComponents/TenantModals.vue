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
</template>

<script setup>
import { reactive, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    modals: { type: Object, required: true },
    tenant: { type: Object, required: true },
    analytics: { type: Object, required: true },
    securityForm: { type: Object, required: true },
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
});

// Reset scratch inputs when a modal closes so reopening starts fresh.
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

const emitSuspend = () => emit('confirm-suspend', local.suspendReason);
const emitExtend = () => emit('confirm-extend', local.extendDays);
const emitDelete = () => emit('confirm-delete', local.deleteConfirmation);

const twoFactorEnforcementOptions = computed(() => [
    { value: 'disabled', label: t('system.tenants.modals.enforcement_disabled') },
    { value: 'optional', label: t('system.tenants.modals.enforcement_optional') },
    { value: 'required', label: t('system.tenants.modals.enforcement_required') },
]);
</script>
