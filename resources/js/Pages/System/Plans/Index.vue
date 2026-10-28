<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('system.plans.title') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.plans.subtitle') }}
                    </p>
                </div>
                <button
                    @click="openModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    {{ $t('system.plans.create') }}
                </button>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border overflow-hidden relative',
                        plan.is_featured
                            ? 'border-indigo-500 ring-2 ring-indigo-500'
                            : 'border-gray-200 dark:border-gray-700',
                    ]"
                >
                    <!-- Featured Badge -->
                    <div
                        v-if="plan.is_featured"
                        class="absolute top-4 left-4 px-2.5 py-1 bg-indigo-600 text-white text-xs font-medium rounded-full"
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
                            class="mt-3 flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 font-medium bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-1 rounded-md w-fit"
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
                                v-if="Number(plan.price_yearly) > 0"
                                class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                            >
                                {{ $t('system.plans.annual_includes') }}
                                {{ Math.round(plan.price_yearly / 12) }}
                                {{ $t('system.plans.sar_per_month') }}
                            </p>
                            <p
                                v-else
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
                            <button
                                @click="openModal(plan)"
                                class="flex-1 inline-flex items-center justify-center gap-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors"
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
                                @click="deletePlan(plan)"
                                class="inline-flex items-center justify-center gap-1 px-3 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm transition-colors"
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
                        class="mt-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        {{ $t('system.plans.create_first') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Plan Form Modal -->
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
                                {{
                                    editingPlan
                                        ? $t('system.plans.edit_title')
                                        : $t('system.plans.create_title')
                                }}
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
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.plans.form.name_ar') }}
                                    </label>
                                    <input
                                        v-model="form.name_ar"
                                        type="text"
                                        required
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.plans.form.name_en') }}
                                    </label>
                                    <input
                                        v-model="form.name_en"
                                        type="text"
                                        required
                                        dir="ltr"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.plans.form.slug') }}
                                    </label>
                                    <input
                                        v-model="form.slug"
                                        type="text"
                                        required
                                        dir="ltr"
                                        :placeholder="$t('system.plans.form.slug_placeholder')"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.plans.form.sort_order') }}
                                    </label>
                                    <input
                                        v-model.number="form.sort_order"
                                        type="number"
                                        min="0"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.plans.form.description_ar') }}
                                    </label>
                                    <textarea
                                        v-model="form.description_ar"
                                        rows="2"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    ></textarea>
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                        dir="ltr"
                                    >
                                        {{ $t('system.plans.form.description_en') }}
                                    </label>
                                    <textarea
                                        v-model="form.description_en"
                                        rows="2"
                                        dir="ltr"
                                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Pricing -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    {{ $t('system.plans.form.pricing') }}
                                </h4>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm text-gray-600 dark:text-gray-400 mb-1"
                                        >
                                            {{ $t('system.plans.form.price_monthly') }}
                                        </label>
                                        <input
                                            v-model.number="form.price_monthly"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm text-gray-600 dark:text-gray-400 mb-1"
                                        >
                                            {{ $t('system.plans.form.price_yearly') }}
                                        </label>
                                        <input
                                            v-model.number="form.price_yearly"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm text-gray-600 dark:text-gray-400 mb-1"
                                        >
                                            {{ $t('system.plans.form.trial_days') }}
                                        </label>
                                        <input
                                            v-model.number="form.trial_days"
                                            type="number"
                                            min="0"
                                            required
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Limits -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                                    {{ $t('system.plans.form.limits') }}
                                </h4>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm text-gray-600 dark:text-gray-400 mb-1"
                                        >
                                            {{ $t('system.plans.form.max_users') }}
                                        </label>
                                        <input
                                            v-model.number="form.limits.max_users"
                                            type="number"
                                            min="1"
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm text-gray-600 dark:text-gray-400 mb-1"
                                        >
                                            {{ $t('system.plans.form.max_centers') }}
                                        </label>
                                        <input
                                            v-model.number="form.limits.max_centers"
                                            type="number"
                                            min="1"
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm text-gray-600 dark:text-gray-400 mb-1"
                                        >
                                            {{ $t('system.plans.form.storage_gb') }}
                                        </label>
                                        <input
                                            v-model.number="form.limits.storage_gb"
                                            type="number"
                                            min="1"
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ $t('system.plans.active') }}
                                    </span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        v-model="form.is_featured"
                                        type="checkbox"
                                        class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ $t('system.plans.featured') }}
                                    </span>
                                </label>
                            </div>

                            <!-- Features (Arabic & English) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4
                                        class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                                    >
                                        {{ $t('system.plans.form.features_ar_title') }}
                                    </h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(feature, index) in form.features_ar"
                                            :key="'ar-' + index"
                                            class="flex gap-2"
                                        >
                                            <input
                                                v-model="form.features_ar[index]"
                                                type="text"
                                                class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-950 dark:text-white"
                                                :placeholder="
                                                    $t('system.plans.form.feature_placeholder_ar', {
                                                        n: index + 1,
                                                    })
                                                "
                                            />
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
                                            class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            {{ $t('system.plans.form.add_feature_ar') }}
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                                    >
                                        {{ $t('system.plans.form.features_en_title') }}
                                    </h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(feature, index) in form.features_en"
                                            :key="'en-' + index"
                                            class="flex gap-2"
                                        >
                                            <input
                                                v-model="form.features_en[index]"
                                                type="text"
                                                class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-950 dark:text-white"
                                                :placeholder="
                                                    $t('system.plans.form.feature_placeholder_en', {
                                                        n: index + 1,
                                                    })
                                                "
                                            />
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
                                            class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            {{ $t('system.plans.form.add_feature_en') }}
                                        </button>
                                    </div>
                                </div>
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
                                :disabled="form.processing"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50"
                            >
                                {{
                                    editingPlan
                                        ? $t('system.plans.save_changes')
                                        : $t('system.plans.create')
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const { t } = useI18n();

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
    price_monthly: 0,
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
        form.price_monthly = plan.price_monthly || 0;
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

const deletePlan = (plan) => {
    if (confirm(t('system.plans.confirm_delete', { name: plan.name_ar }))) {
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
