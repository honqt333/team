<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.subscriptions.title')"
                :subtitle="$t('system.subscriptions.subtitle')"
                :totalCount="subscriptions?.total != null ? toEnglish(subscriptions.total) : null"
                :countLabel="$t('system.subscriptions.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <button
                        @click="openModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-indigo-500/25 active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.subscriptions.create') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div
                    v-for="stat in statCards"
                    :key="stat.key"
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 transition-shadow hover:shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ stat.label }}
                        </p>
                        <div
                            :class="stat.iconBg"
                            class="flex items-center justify-center w-9 h-9 rounded-lg"
                        >
                            <svg
                                class="w-5 h-5"
                                :class="stat.iconColor"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="stat.iconPath"
                                />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-2xl font-bold" :class="stat.valueColor">
                        {{ stat.value }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <svg
                                class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="$t('system.subscriptions.search_placeholder')"
                                class="w-full px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>
                    <SearchableSelect
                        v-model="statusFilter"
                        :options="statusFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.subscriptions.all_statuses')"
                        compact
                    />
                    <SearchableSelect
                        v-model="planFilter"
                        :options="planFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('system.subscriptions.all_plans')"
                        compact
                    />
                </div>
            </div>

            <!-- Subscriptions Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.subscriptions.tenant_label') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.subscriptions.plan_label') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.subscriptions.billing_cycle') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                                >
                                    {{ $t('system.tenants.status') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                                >
                                    {{ $t('system.subscriptions.ends_at') }}
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
                                v-for="sub in subscriptions.data"
                                :key="sub.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td class="px-6 py-4 align-middle">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{
                                                sub.tenant?.trade_name ||
                                                sub.tenant?.name ||
                                                $t('common.na')
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            #{{ sub.tenant?.id }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-middle">
                                    <span
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400"
                                    >
                                        {{ sub.plan?.name_ar || $t('common.na') }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 align-middle text-center"
                                >
                                    {{
                                        sub.billing_cycle === 'yearly'
                                            ? $t('system.subscriptions.yearly')
                                            : $t('system.subscriptions.monthly')
                                    }}
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <span
                                        :class="getStatusClass(sub.status)"
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    >
                                        {{ getStatusLabel(sub.status) }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 align-middle"
                                >
                                    {{ formatDate(sub.ends_at) }}
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <Link
                                        :href="`/system/subscriptions/${sub.id}`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        {{ $t('system.actions.view') }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!subscriptions.data?.length" class="px-6 py-16 text-center">
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
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                    </svg>
                    <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $t('system.subscriptions.no_subscriptions') }}
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    v-if="subscriptions.links?.length > 3"
                    class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2"
                >
                    <Link
                        v-for="link in subscriptions.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 rounded text-sm',
                            link.active
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            !link.url && 'opacity-50 cursor-not-allowed',
                        ]"
                        v-safe-html="link.label"
                    />
                </div>
            </div>

            <!-- Create Modal -->
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div
                        class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0"
                    >
                        <!-- Backdrop -->
                        <div
                            class="fixed inset-0 bg-black/50 transition-opacity"
                            @click="closeModal"
                        ></div>

                        <!-- Modal Panel -->
                        <div
                            class="relative bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full"
                        >
                            <!-- Header -->
                            <div
                                class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                            >
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $t('system.subscriptions.create_title') }}
                                </h3>
                                <button
                                    @click="closeModal"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
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
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <!-- Form Body -->
                            <form
                                @submit.prevent="submit"
                                class="p-6 space-y-5 max-h-[70vh] overflow-y-auto"
                            >
                                <!-- Tenant Selection with Search -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.subscriptions.create_modal.tenant_label') }}
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="tenantSearch"
                                            type="text"
                                            :placeholder="
                                                $t('system.subscriptions.tenant_search_placeholder')
                                            "
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            @focus="showTenantDropdown = true"
                                        />
                                        <div
                                            v-if="showTenantDropdown && filteredTenants.length > 0"
                                            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                                        >
                                            <div
                                                v-for="tenant in filteredTenants"
                                                :key="tenant.id"
                                                @click="selectTenant(tenant)"
                                                class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer flex justify-between items-center"
                                            >
                                                <div>
                                                    <p
                                                        class="font-medium text-gray-900 dark:text-white"
                                                    >
                                                        {{
                                                            tenant.trade_name ||
                                                            tenant.name ||
                                                            $t('common.na')
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-gray-500 dark:text-gray-400"
                                                    >
                                                        #{{ tenant.id }}
                                                    </p>
                                                </div>
                                                <svg
                                                    v-if="form.tenant_id === tenant.id"
                                                    class="w-5 h-5 text-indigo-600"
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
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        v-if="form.errors.tenant_id"
                                        class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ form.errors.tenant_id }}
                                    </p>
                                    <p
                                        v-if="form.tenant_id"
                                        class="mt-1 text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-1"
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
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        {{
                                            $t(
                                                'system.subscriptions.create_modal.selected_tenant',
                                                {
                                                    name:
                                                        getSelectedTenantName() || $t('common.na'),
                                                }
                                            )
                                        }}
                                    </p>
                                </div>

                                <!-- Plan Selection -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <SearchableSelect
                                            v-model="form.plan_id"
                                            :options="planOptions"
                                            option-label="label"
                                            option-value="value"
                                            :label="$t('system.subscriptions.plan_label')"
                                            required
                                            :error="form.errors.plan_id"
                                        />
                                    </div>
                                    <div>
                                        <SearchableSelect
                                            v-model="form.billing_cycle"
                                            :options="formBillingCycleOptions"
                                            option-label="label"
                                            option-value="value"
                                            :label="
                                                $t(
                                                    'system.subscriptions.create_modal.billing_cycle_label'
                                                )
                                            "
                                            required
                                            :error="form.errors.billing_cycle"
                                        />
                                    </div>
                                </div>

                                <!-- Price Preview -->
                                <div
                                    v-if="selectedPlan"
                                    class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600 flex justify-between items-center"
                                >
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{
                                            $t(
                                                'system.subscriptions.create_modal.subscription_price'
                                            )
                                        }}
                                    </span>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{
                                            form.billing_cycle === 'yearly'
                                                ? selectedPlan.price_yearly
                                                : selectedPlan.price_monthly
                                        }}
                                        {{ $t('system.subscriptions.create_modal.currency') }}
                                    </span>
                                </div>

                                <!-- Promo Code -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.subscriptions.promo_code_label') }}
                                    </label>
                                    <input
                                        v-model="form.promo_code"
                                        type="text"
                                        dir="ltr"
                                        :placeholder="
                                            $t('system.subscriptions.promo_code_placeholder')
                                        "
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono uppercase"
                                    />
                                    <p
                                        v-if="form.errors.promo_code"
                                        class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ form.errors.promo_code }}
                                    </p>
                                </div>
                            </form>

                            <!-- Footer -->
                            <div
                                class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3"
                            >
                                <button
                                    @click="closeModal"
                                    type="button"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                >
                                    {{ $t('system.actions.cancel') }}
                                </button>
                                <button
                                    @click="submit"
                                    :disabled="form.processing || !form.tenant_id"
                                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50"
                                >
                                    {{
                                        form.processing
                                            ? $t('system.subscriptions.create_modal.submitting')
                                            : $t('system.subscriptions.create_modal.submit')
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const statusFilterOptions = computed(() => [
    { value: 'all', label: t('system.subscriptions.all_statuses') },
    { value: 'active', label: t('system.status.active') },
    { value: 'trial', label: t('system.status.trial') },
    { value: 'cancelled', label: t('system.status.cancelled') },
    { value: 'expired', label: t('system.status.expired') },
]);

const planFilterOptions = computed(() => [
    { value: '', label: t('system.subscriptions.all_plans') },
    ...(props.plans || []).map((p) => ({ value: p.id, label: p.name_ar })),
]);

const planOptions = computed(() =>
    (props.plans || []).map((p) => ({ value: p.id, label: p.name_ar }))
);

const formBillingCycleOptions = computed(() => [
    { value: 'yearly', label: t('subscription.yearly_12_months') },
]);

const props = defineProps({
    subscriptions: Object,
    plans: Array,
    filters: Object,
    stats: Object,
    eligibleTenants: Array,
});

// Stat cards: extracted to a computed list so the template doesn't
// repeat the same card 5× with only label/value/color swapped.
const statCards = computed(() => [
    {
        key: 'total',
        label: t('system.subscriptions.stats.total'),
        value: props.stats?.total ?? 0,
        valueColor: 'text-gray-900 dark:text-white',
        iconBg: 'bg-gray-100 dark:bg-gray-700',
        iconColor: 'text-gray-500 dark:text-gray-400',
        iconPath:
            'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    },
    {
        key: 'active',
        label: t('system.status.active'),
        value: props.stats?.active ?? 0,
        valueColor: 'text-emerald-600 dark:text-emerald-400',
        iconBg: 'bg-emerald-100 dark:bg-emerald-900/30',
        iconColor: 'text-emerald-600 dark:text-emerald-400',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'trial',
        label: t('system.status.trial'),
        value: props.stats?.trial ?? 0,
        valueColor: 'text-amber-600 dark:text-amber-400',
        iconBg: 'bg-amber-100 dark:bg-amber-900/30',
        iconColor: 'text-amber-600 dark:text-amber-400',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'cancelled',
        label: t('system.status.cancelled'),
        value: props.stats?.cancelled ?? 0,
        valueColor: 'text-red-600 dark:text-red-400',
        iconBg: 'bg-red-100 dark:bg-red-900/30',
        iconColor: 'text-red-600 dark:text-red-400',
        iconPath: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'expired',
        label: t('system.status.expired'),
        value: props.stats?.expired ?? 0,
        valueColor: 'text-gray-500 dark:text-gray-400',
        iconBg: 'bg-gray-100 dark:bg-gray-700',
        iconColor: 'text-gray-500 dark:text-gray-400',
        iconPath: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
]);

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const planFilter = ref(props.filters?.plan_id || '');

// Modal Logic
const showModal = ref(false);
const tenantSearch = ref('');
const showTenantDropdown = ref(false);
const form = useForm({
    tenant_id: '',
    plan_id: '',
    billing_cycle: 'yearly',
    promo_code: '',
});

const openModal = () => {
    form.reset();
    form.billing_cycle = 'yearly';
    tenantSearch.value = '';
    showTenantDropdown.value = false;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

// Filter Tenants
const filteredTenants = computed(() => {
    if (!tenantSearch.value) return props.eligibleTenants || [];
    const query = tenantSearch.value.toLowerCase();
    return (props.eligibleTenants || []).filter(
        (tenant) =>
            (tenant.name && tenant.name.toLowerCase().includes(query)) ||
            (tenant.trade_name && tenant.trade_name.toLowerCase().includes(query)) ||
            tenant.id.toString().includes(query)
    );
});

const selectTenant = (tenant) => {
    form.tenant_id = tenant.id;
    tenantSearch.value = tenant.trade_name || tenant.name;
    showTenantDropdown.value = false;
};

const getSelectedTenantName = () => {
    const tenant = (props.eligibleTenants || []).find((t) => t.id === form.tenant_id);
    return tenant ? tenant.trade_name || tenant.name : '';
};

// Plan Logic
const selectedPlan = computed(() => {
    return props.plans.find((p) => p.id === form.plan_id);
});

const submit = () => {
    form.post('/system/subscriptions', {
        onSuccess: () => closeModal(),
    });
};

const applyFilters = () => {
    router.get(
        '/system/subscriptions',
        {
            search: search.value || undefined,
            status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            plan_id: planFilter.value || undefined,
        },
        { preserveState: true }
    );
};

const formatDate = (date) => {
    if (!date) return t('common.na');
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn');
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        trialing: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        pending: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
    return classes[status] || classes.expired;
};

const getStatusLabel = (status) => {
    const labels = {
        active: t('system.status.active'),
        trial: t('system.status.trial'),
        trialing: t('system.status.trial'),
        cancelled: t('system.status.cancelled'),
        expired: t('system.status.expired'),
        pending: t('system.status.pending'),
    };
    return labels[status] || status || t('common.na');
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
