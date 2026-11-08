<template>
    <SystemLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Back & Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        href="/system/subscriptions"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $t('system.subscriptions.create_title') }}
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ $t('system.subscriptions.create_subtitle') }}
                        </p>
                    </div>
                </div>
            </div>

            <form
                @submit.prevent="submit"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6"
            >
                <!-- Tenant Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('system.subscriptions.tenant') }}
                    </label>
                    <SearchableSelect
                        v-model="form.tenant_id"
                        :options="tenantOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.subscriptions.tenant_search_placeholder')"
                        :error="form.errors.tenant_id"
                    />
                    <p v-if="form.errors.tenant_id" class="text-red-500 text-xs mt-1">
                        {{ form.errors.tenant_id }}
                    </p>
                </div>

                <!-- Plan Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('system.subscriptions.package') }}
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label
                            v-for="plan in plans"
                            :key="plan.id"
                            :class="[
                                'border rounded-lg p-4 cursor-pointer transition-colors',
                                form.plan_id === plan.id
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300',
                            ]"
                        >
                            <input
                                type="radio"
                                :value="plan.id"
                                v-model="form.plan_id"
                                class="hidden"
                            />
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ plan.name_ar }}
                                </p>
                                <span
                                    v-if="plan.is_featured"
                                    class="px-2 py-0.5 bg-indigo-600 text-white text-xs rounded-full"
                                >
                                    {{ $t('system.subscriptions.featured') }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ plan.slug }}</p>
                            <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                <p>
                                    <span class="font-semibold">{{ plan.price_monthly }}</span>
                                    {{ $t('system.subscriptions.sar_monthly') }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ plan.price_yearly + $t('system.subscriptions.sar_yearly') }}
                                </p>
                                <p v-if="plan.trial_days > 0" class="text-xs text-emerald-600 mt-1">
                                    {{
                                        $t('system.subscriptions.free_trial') + plan.trial_days
                                    }}$t('common.day') }}
                                </p>
                            </div>
                        </label>
                    </div>
                    <p v-if="form.errors.plan_id" class="text-red-500 text-xs mt-1">
                        {{ form.errors.plan_id }}
                    </p>
                </div>

                <!-- Billing Cycle -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('system.subscriptions.billing_cycle_2') }}
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            v-for="opt in billingCycleOptions"
                            :key="opt.value"
                            :class="[
                                'border rounded-lg p-3 cursor-pointer text-center transition-colors',
                                form.billing_cycle === opt.value
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300',
                            ]"
                        >
                            <input
                                type="radio"
                                :value="opt.value"
                                v-model="form.billing_cycle"
                                class="hidden"
                            />
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ opt.label }}
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Promo Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('system.subscriptions.promo_code_label') }}
                    </label>
                    <input
                        v-model="form.promo_code"
                        type="text"
                        :placeholder="$t('system.subscriptions.promo_code_placeholder')"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                        dir="ltr"
                    />
                    <p v-if="form.errors.promo_code" class="text-red-500 text-xs mt-1">
                        {{ form.errors.promo_code }}
                    </p>
                </div>

                <!-- Installments removed — use Tabby/Tamara BNPL for yearly payment splitting -->

                <!-- Actions -->
                <div
                    class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? $t('system.subscriptions.create_modal.submitting')
                                : $t('system.subscriptions.create_modal.submit')
                        }}
                    </button>
                    <Link
                        href="/system/subscriptions"
                        class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
                    </Link>
                </div>
            </form>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    plans: Array,
    tenants: Array,
    eligibleTenants: Array,
});

const form = useForm({
    tenant_id: '',
    plan_id: '',
    billing_cycle: 'yearly',
    promo_code: '',
});

const tenantOptions = computed(() =>
    (props.tenants || []).map((t) => ({
        value: t.id,
        label: `${t.trade_name || t.name} (${t.status})`,
    }))
);

const billingCycleOptions = [
    { value: 'yearly', label: t('system.subscriptions.yearly_12_months') },
];

const submit = () => {
    form.post('/system/subscriptions', {
        onSuccess: () => {
            // redirect handled by controller
        },
    });
};
</script>
