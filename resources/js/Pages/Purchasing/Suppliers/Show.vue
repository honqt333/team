<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Main Header Card (Compact & Clean) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <!-- Top Action Bar -->
                <div
                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/80 bg-gray-50/60 dark:bg-gray-900/30 flex flex-wrap items-center justify-between gap-2"
                >
                    <BackButton :href="route('app.purchasing.suppliers.index')" />

                    <!-- Actions Group -->
                    <div class="flex flex-wrap items-center gap-2">
                        <Tooltip
                            v-if="balance > 0.01"
                            :text="$t('purchasing.suppliers.record_payment')"
                        >
                            <button
                                v-if="can('purchasing.suppliers.update') || isAnyAdmin()"
                                @click="showPaymentModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs border border-emerald-200/80 dark:border-emerald-800/40 bg-emerald-50/70 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100/70 dark:hover:bg-emerald-900/50 transition-colors cursor-pointer"
                            >
                                <svg
                                    class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">
                                    {{ $t('purchasing.suppliers.record_payment') }}
                                </span>
                            </button>
                        </Tooltip>

                        <Tooltip :text="$t('common.edit')">
                            <button
                                v-if="can('purchasing.suppliers.update') || isAnyAdmin()"
                                @click="showEditModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs border border-orange-200/80 dark:border-orange-800/40 bg-orange-50/70 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 hover:bg-orange-100/70 dark:hover:bg-orange-900/40 transition-colors cursor-pointer"
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
                                <span class="hidden sm:inline">{{ $t('common.edit') }}</span>
                            </button>
                        </Tooltip>

                        <Tooltip
                            :text="
                                counts.orders > 0 ||
                                counts.invoices > 0 ||
                                counts.returns > 0 ||
                                counts.payments > 0
                                    ? $t('common.cannot_delete_has_data')
                                    : $t('common.delete')
                            "
                        >
                            <button
                                v-if="can('purchasing.suppliers.destroy') || isAnyAdmin()"
                                @click="confirmDelete"
                                :disabled="
                                    counts.orders > 0 ||
                                    counts.invoices > 0 ||
                                    counts.returns > 0 ||
                                    counts.payments > 0
                                "
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs border transition-colors',
                                    counts.orders === 0 &&
                                    counts.invoices === 0 &&
                                    counts.returns === 0 &&
                                    counts.payments === 0
                                        ? 'border-red-200/80 dark:border-red-800/40 bg-red-50/70 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-100/70 dark:hover:bg-red-900/40 cursor-pointer'
                                        : 'border-gray-200 dark:border-gray-700 bg-gray-100/70 dark:bg-gray-900/30 text-gray-400 cursor-not-allowed opacity-70',
                                ]"
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
                                <span class="hidden sm:inline">{{ $t('common.delete') }}</span>
                            </button>
                        </Tooltip>
                    </div>
                </div>

                <!-- Main Info Area -->
                <div class="p-4 sm:p-5">
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-5">
                        <!-- Supplier Identity -->
                        <div class="flex items-start gap-4 min-w-0 flex-1">
                            <div class="relative shrink-0">
                                <div
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-600 to-red-600 flex items-center justify-center text-white shadow-sm"
                                >
                                    <span class="text-2xl font-black">
                                        {{ supplier.name?.charAt(0)?.toUpperCase() || '?' }}
                                    </span>
                                </div>
                                <div
                                    class="absolute -bottom-1 -right-1 w-6 h-6 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-600 flex items-center justify-center"
                                >
                                    <svg
                                        v-if="supplier.type === 'parts'"
                                        class="w-3.5 h-3.5 text-orange-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-3.5 h-3.5 text-blue-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1
                                        class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight truncate"
                                    >
                                        {{ supplier.name }}
                                    </h1>
                                    <span
                                        v-if="supplier.code"
                                        class="px-2.5 py-0.5 rounded-lg text-[11px] font-bold border bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 border-orange-200/70 dark:border-orange-800/40 font-mono"
                                    >
                                        {{ supplier.code }}
                                    </span>
                                </div>

                                <div
                                    class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300"
                                >
                                    <span
                                        v-if="supplier.contact_person"
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <svg
                                            class="w-4 h-4 text-gray-400 shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                        {{ supplier.contact_person }}
                                    </span>

                                    <a
                                        v-if="supplier.phone"
                                        :href="`tel:${supplier.phone}`"
                                        class="inline-flex items-center gap-1.5 hover:text-orange-600 dark:hover:text-orange-400 transition-colors font-mono"
                                        dir="ltr"
                                    >
                                        <svg
                                            class="w-4 h-4 text-orange-500 shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                            />
                                        </svg>
                                        {{ supplier.phone }}
                                    </a>

                                    <a
                                        v-if="supplier.email"
                                        :href="`mailto:${supplier.email}`"
                                        class="inline-flex items-center gap-1.5 hover:text-blue-600 dark:hover:text-blue-400 transition-colors max-w-[240px]"
                                        dir="ltr"
                                    >
                                        <svg
                                            class="w-4 h-4 text-blue-500 shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                            />
                                        </svg>
                                        <span class="truncate">{{ supplier.email }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Stats Grid -->
                        <div
                            class="grid grid-cols-2 sm:grid-cols-3 xl:flex items-center gap-2 shrink-0"
                        >
                            <div
                                class="flex flex-col items-center justify-center px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200/80 dark:border-gray-700 min-w-[76px]"
                            >
                                <span
                                    class="text-base font-bold font-mono text-gray-900 dark:text-white"
                                >
                                    {{ counts.orders }}
                                </span>
                                <span class="text-[11px] font-medium text-gray-500 mt-0.5">
                                    {{ $t('purchasing.suppliers.orders') }}
                                </span>
                            </div>

                            <div
                                class="flex flex-col items-center justify-center px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200/80 dark:border-gray-700 min-w-[76px]"
                            >
                                <span
                                    class="text-base font-bold font-mono text-gray-900 dark:text-white"
                                >
                                    {{ counts.invoices }}
                                </span>
                                <span class="text-[11px] font-medium text-gray-500 mt-0.5">
                                    {{ $t('purchasing.suppliers.invoices') }}
                                </span>
                            </div>

                            <div
                                :class="[
                                    'flex flex-col items-center justify-center px-3.5 py-2 rounded-xl border col-span-2 sm:col-span-1 min-w-[110px]',
                                    balance < -0.01
                                        ? 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/50'
                                        : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/50',
                                ]"
                            >
                                <span
                                    :class="[
                                        'text-sm font-bold font-mono',
                                        balance < -0.01
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-emerald-600 dark:text-emerald-400',
                                    ]"
                                >
                                    {{
                                        formatCurrency(
                                            balance < -0.01
                                                ? amountDueFromSupplier || Math.abs(balance)
                                                : balance
                                        )
                                    }}
                                </span>
                                <span
                                    :class="[
                                        'text-[11px] font-semibold mt-0.5',
                                        balance < -0.01
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-emerald-600 dark:text-emerald-400',
                                    ]"
                                >
                                    {{
                                        balance < -0.01
                                            ? $t('suppliers.amount_due_from_supplier')
                                            : $t('purchasing.suppliers.balance')
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Content Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden min-h-[500px]"
            >
                <!-- Tabs Navigation -->
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" sync-url />
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- ========== OVERVIEW TAB ========== -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Info -->
                            <div
                                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                            >
                                <h3
                                    class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4"
                                >
                                    {{ $t('common.basic_info') }}
                                </h3>
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.contact') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ supplier.contact_person || '-' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.phone') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                            dir="ltr"
                                        >
                                            {{ supplier.phone || '-' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.email') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ supplier.email || '-' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.type') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{
                                                supplier.type === 'parts'
                                                    ? $t('purchasing.suppliers.type_parts')
                                                    : $t('purchasing.suppliers.type_services')
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Financial Info -->
                            <div
                                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                            >
                                <h3
                                    class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4"
                                >
                                    {{ $t('purchasing.suppliers.bank_info') }}
                                </h3>
                                <dl class="space-y-3">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.tax_number') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ supplier.tax_number || '-' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.cr_number') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ supplier.cr_number || '-' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.bank_name') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ supplier.bank_name || '-' }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $t('purchasing.suppliers.iban') }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                            dir="ltr"
                                        >
                                            {{ supplier.iban || '-' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Address -->
                        <div
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                        >
                            <h3
                                class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4"
                            >
                                {{ $t('purchasing.suppliers.address') }}
                            </h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                {{ supplier.address || '-' }}
                            </p>
                            <div
                                v-if="supplier.building_number"
                                class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4"
                            >
                                <div>
                                    <dt class="text-xs text-gray-500">
                                        {{ $t('purchasing.suppliers.city') }}
                                    </dt>
                                    <dd class="text-sm font-medium">{{ supplier.city }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">
                                        {{ $t('purchasing.suppliers.district') }}
                                    </dt>
                                    <dd class="text-sm font-medium">{{ supplier.district }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">
                                        {{ $t('purchasing.suppliers.street') }}
                                    </dt>
                                    <dd class="text-sm font-medium">{{ supplier.street }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">
                                        {{ $t('purchasing.suppliers.country') }}
                                    </dt>
                                    <dd class="text-sm font-medium">{{ supplier.country }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========== STATEMENT TAB (كشف حساب المورد) ========== -->
                    <div v-if="activeTab === 'statement'" class="space-y-6">
                        <!-- Filter Bar (Date Range + Quick Presets + Print & Export) -->
                        <div
                            class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-gray-50 dark:bg-gray-900/60 p-4 rounded-2xl border border-gray-200 dark:border-gray-700"
                        >
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <label
                                        class="text-xs font-bold text-gray-600 dark:text-gray-300"
                                    >
                                        {{ $t('common.from') }}:
                                    </label>
                                    <CustomDatePicker v-model="statementStartDate" />
                                </div>
                                <div class="flex items-center gap-2">
                                    <label
                                        class="text-xs font-bold text-gray-600 dark:text-gray-300"
                                    >
                                        {{ $t('common.to') }}:
                                    </label>
                                    <CustomDatePicker v-model="statementEndDate" />
                                </div>

                                <!-- Presets -->
                                <div
                                    class="flex items-center gap-1 bg-white dark:bg-gray-800 p-1 rounded-xl border border-gray-200 dark:border-gray-700 text-xs"
                                >
                                    <button
                                        type="button"
                                        @click="setStatementPreset('this_month')"
                                        class="px-2.5 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('purchasing.current_month') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="setStatementPreset('last_3_months')"
                                        class="px-2.5 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('purchasing.last_3_months') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="setStatementPreset('this_year')"
                                        class="px-2.5 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('common.this_year') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="setStatementPreset('all')"
                                        class="px-2.5 py-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 font-medium"
                                    >
                                        {{ $t('common.all') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons: Print Statement -->
                            <div class="flex items-center gap-2 self-end lg:self-auto">
                                <AppPrintButton
                                    variant="button"
                                    :label="$t('purchasing.suppliers.print_statement')"
                                    @click="printStatement"
                                />
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            <!-- Opening Balance -->
                            <div
                                class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 text-center"
                            >
                                <span
                                    class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block"
                                >
                                    {{ $t('purchasing.suppliers.opening_balance') }}
                                </span>
                                <span class="text-base font-black font-mono mt-1 block" dir="ltr">
                                    {{ formatCurrency(statementLedger.openingBalance) }}
                                </span>
                            </div>

                            <!-- Period Purchases -->
                            <div
                                class="p-3.5 rounded-2xl bg-blue-50/50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/40 text-center"
                            >
                                <span
                                    class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider block"
                                >
                                    {{ $t('purchasing.suppliers.period_purchases') }}
                                </span>
                                <span
                                    class="text-base font-black text-blue-700 dark:text-blue-300 font-mono mt-1 block"
                                    dir="ltr"
                                >
                                    {{ formatCurrency(statementLedger.periodInvoiced) }}
                                </span>
                            </div>

                            <!-- Period Payments -->
                            <div
                                class="p-3.5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/40 text-center"
                            >
                                <span
                                    class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block"
                                >
                                    {{ $t('purchasing.suppliers.period_payments') }}
                                </span>
                                <span
                                    class="text-base font-black text-emerald-700 dark:text-emerald-300 font-mono mt-1 block"
                                    dir="ltr"
                                >
                                    {{ formatCurrency(statementLedger.periodPaid) }}
                                </span>
                            </div>

                            <!-- Period Returns -->
                            <div
                                class="p-3.5 rounded-2xl bg-rose-50/50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800/40 text-center"
                            >
                                <span
                                    class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider block"
                                >
                                    {{ $t('purchasing.suppliers.period_returns') }}
                                </span>
                                <span
                                    class="text-base font-black text-rose-700 dark:text-rose-300 font-mono mt-1 block"
                                    dir="ltr"
                                >
                                    {{ formatCurrency(statementLedger.periodReturned) }}
                                </span>
                            </div>

                            <!-- Closing Balance -->
                            <div
                                class="p-3.5 rounded-2xl border text-center col-span-2 md:col-span-1"
                                :class="
                                    statementLedger.closingBalance > 0.01
                                        ? 'bg-amber-50/70 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800'
                                        : 'bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-700'
                                "
                            >
                                <span
                                    class="text-[10px] font-bold uppercase tracking-wider block"
                                    :class="
                                        statementLedger.closingBalance > 0.01
                                            ? 'text-amber-700 dark:text-amber-400'
                                            : 'text-gray-500'
                                    "
                                >
                                    {{ $t('purchasing.suppliers.closing_balance') }}
                                </span>
                                <span
                                    class="text-base font-black font-mono mt-1 block"
                                    :class="
                                        statementLedger.closingBalance > 0.01
                                            ? 'text-amber-700 dark:text-amber-300'
                                            : 'text-gray-800 dark:text-gray-200'
                                    "
                                    dir="ltr"
                                >
                                    {{ formatCurrency(statementLedger.closingBalance) }}
                                </span>
                            </div>
                        </div>

                        <!-- Statement Ledger Table -->
                        <div
                            class="overflow-x-auto bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/50 shadow-sm"
                        >
                            <DataTable structured class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50"
                                    >
                                        <th class="py-3 px-4 text-center font-bold">#</th>
                                        <th class="py-3 px-4 text-center font-bold">
                                            {{ $t('common.date') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold">
                                            {{ $t('common.type') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold">
                                            {{ $t('payments.reference') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold">
                                            {{ $t('common.notes') }}
                                        </th>
                                        <th
                                            class="py-3 px-4 text-center font-bold text-blue-600 dark:text-blue-400"
                                        >
                                            {{ $t('invoices.purchases.total') }}
                                        </th>
                                        <th
                                            class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ $t('payments.title') }}
                                        </th>
                                        <th
                                            class="py-3 px-4 text-center font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ $t('purchasing.suppliers.running_balance') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                    <!-- Row 0: Opening Balance row if exists -->
                                    <tr
                                        v-if="statementLedger.openingBalance !== 0"
                                        class="bg-gray-50/40 dark:bg-gray-800/40 font-bold text-gray-500"
                                    >
                                        <td class="py-3 px-4 text-center font-mono">—</td>
                                        <td class="py-3 px-4 text-center font-mono" dir="ltr">
                                            {{ statementStartDate || $t('common.start') }}
                                        </td>
                                        <td class="py-3 px-4 text-center" colspan="3">
                                            <span
                                                class="text-xs px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                            >
                                                {{ $t('purchasing.suppliers.opening_balance') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-mono">—</td>
                                        <td class="py-3 px-4 text-center font-mono">—</td>
                                        <td
                                            class="py-3 px-4 text-center font-black font-mono"
                                            dir="ltr"
                                        >
                                            {{ formatCurrency(statementLedger.openingBalance) }}
                                        </td>
                                    </tr>

                                    <!-- Transaction Rows -->
                                    <tr
                                        v-for="(tx, idx) in statementLedger.rows"
                                        :key="idx"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors"
                                    >
                                        <td
                                            class="py-3.5 px-4 text-center text-gray-400 font-mono text-xs"
                                        >
                                            {{ idx + 1 }}
                                        </td>
                                        <td
                                            class="py-3.5 px-4 text-center font-mono text-xs text-gray-700 dark:text-gray-300"
                                            dir="ltr"
                                        >
                                            {{ formatDate(tx.date) }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span
                                                v-if="tx.type === 'invoice'"
                                                class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                            >
                                                {{
                                                    $t(
                                                        'company_profile.invoices_tab.table.type_purchase'
                                                    )
                                                }}
                                            </span>
                                            <span
                                                v-else-if="tx.type === 'return'"
                                                class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                                            >
                                                {{ $t('purchasing.purchase_return') }}
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                            >
                                                {{ $t('purchasing.payment_voucher') }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-3.5 px-4 text-center font-mono font-bold text-xs"
                                        >
                                            <Link
                                                v-if="tx.link"
                                                :href="tx.link"
                                                class="text-amber-600 dark:text-amber-400 hover:underline"
                                            >
                                                {{ tx.code }}
                                            </Link>
                                            <span v-else class="text-gray-600 dark:text-gray-400">
                                                {{ tx.code || '—' }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-3.5 px-4 text-center text-xs text-gray-500 max-w-xs truncate"
                                        >
                                            {{ tx.notes || '—' }}
                                        </td>
                                        <td
                                            class="py-3.5 px-4 text-center font-mono font-bold text-blue-600 dark:text-blue-400"
                                            dir="ltr"
                                        >
                                            {{ tx.debit > 0 ? formatCurrency(tx.debit) : '—' }}
                                        </td>
                                        <td
                                            class="py-3.5 px-4 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                            dir="ltr"
                                        >
                                            {{ tx.credit > 0 ? formatCurrency(tx.credit) : '—' }}
                                        </td>
                                        <td
                                            class="py-3.5 px-4 text-center font-mono font-black"
                                            :class="
                                                tx.runningBalance > 0.01
                                                    ? 'text-amber-700 dark:text-amber-300'
                                                    : 'text-gray-700 dark:text-gray-300'
                                            "
                                            dir="ltr"
                                        >
                                            {{ formatCurrency(tx.runningBalance) }}
                                        </td>
                                    </tr>

                                    <tr
                                        v-if="statementLedger.rows.length === 0"
                                        class="text-center"
                                    >
                                        <td colspan="8" class="py-12 text-gray-400 text-sm">
                                            {{ $t('common.no_data') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                    </div>

                    <!-- ========== INVOICES TAB ========== -->
                    <div v-if="activeTab === 'invoices'" class="space-y-6">
                        <!-- Toolbar (Search + View Switcher) -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-[2rem] border border-gray-100 dark:border-gray-700/50 shadow-sm"
                        >
                            <div class="relative flex-1 max-w-md">
                                <div
                                    class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none text-gray-400"
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
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="invoiceSearchQuery"
                                    :placeholder="$t('common.search') + '...'"
                                    class="block w-full ps-11 pe-4 py-2.5 text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-focus-ring/10 focus:border-action-primary transition-all outline-none"
                                />
                            </div>
                            <div class="flex items-center gap-2 self-end sm:self-auto">
                                <!-- Add Invoice Button -->
                                <button
                                    v-if="can('purchasing.invoices.create') || isAnyAdmin()"
                                    @click="showInvoiceModal = true"
                                    class="flex items-center justify-center w-fit shrink-0 whitespace-nowrap gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl hover:from-amber-700 hover:to-orange-700 transition-all font-black text-sm select-none active:scale-95 shadow-md shadow-amber-500/20"
                                >
                                    <svg
                                        class="w-4.5 h-4.5 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    {{ $t('invoices.purchases.add') }}
                                </button>
                                <AppViewModeToggle v-model="invoiceViewMode" />
                            </div>
                        </div>

                        <!-- Invoices Content -->
                        <div v-if="filteredInvoices.length > 0">
                            <!-- Grid View -->
                            <div
                                v-if="invoiceViewMode === 'grid'"
                                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"
                            >
                                <Link
                                    v-for="invoice in filteredInvoices"
                                    :key="invoice.id"
                                    :href="route('app.invoices.purchases.show', invoice.id)"
                                    class="group relative flex flex-col bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.12)] hover:-translate-y-1 transition-all duration-500 cursor-pointer overflow-hidden shadow-sm"
                                >
                                    <!-- Status Indicator Bar (Vertical) -->
                                    <div
                                        class="absolute inset-y-0 start-0 w-1.5"
                                        :class="stripeClass(invoice.status)"
                                    ></div>

                                    <!-- Header -->
                                    <div
                                        class="bg-slate-50/50 dark:bg-slate-900/40 p-4 pe-6 flex justify-between items-start"
                                    >
                                        <span
                                            class="text-[10px] font-black text-sky-600 dark:text-sky-400 font-mono bg-sky-50 dark:bg-sky-900/20 px-2 py-0.5 rounded-full"
                                        >
                                            {{ formatDate(invoice.issue_date) }}
                                        </span>
                                        <div class="text-end">
                                            <div
                                                class="flex items-center justify-end gap-1.5 text-slate-800 dark:text-white"
                                            >
                                                <span
                                                    class="text-base font-black font-mono tracking-tighter"
                                                >
                                                    {{ invoice.code }}
                                                </span>
                                                <svg
                                                    class="w-4 h-4 text-amber-500"
                                                    fill="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"
                                                    />
                                                </svg>
                                            </div>
                                            <div class="flex items-center justify-end gap-1 mt-1">
                                                <p
                                                    class="text-[9px] font-black text-slate-500 font-mono truncate max-w-[80px]"
                                                >
                                                    {{ invoice.invoice_number || '—' }}
                                                </p>
                                                <span
                                                    class="text-[8px] font-black text-gray-400 uppercase tracking-tighter"
                                                >
                                                    {{ $t('invoices.purchases.supplier_ref') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-6 ps-8 flex-1 flex flex-col">
                                        <!-- Body Content -->
                                        <div class="text-center mb-5">
                                            <h4
                                                class="text-[11px] font-black text-slate-800 dark:text-white mb-5 line-clamp-1 opacity-90"
                                            >
                                                {{
                                                    $page.props.center?.name ||
                                                    $page.props.auth?.center?.name
                                                }}
                                            </h4>
                                            <div
                                                class="flex items-center gap-2 justify-end mb-4 group-hover:translate-x-[-4px] transition-transform"
                                            >
                                                <p
                                                    class="text-[11px] font-black text-slate-600 dark:text-slate-300"
                                                >
                                                    {{ props.supplier?.name || '—' }}
                                                </p>
                                                <div
                                                    class="w-1.5 h-1.5 rounded-full bg-amber-500"
                                                ></div>
                                            </div>
                                            <div
                                                class="w-full h-[1.5px] bg-gradient-to-r from-transparent via-gray-100 dark:via-gray-700 to-transparent my-5"
                                            ></div>
                                        </div>

                                        <!-- Bottom Info -->
                                        <div class="mt-auto space-y-4">
                                            <!-- Overdue Alert -->
                                            <div
                                                v-if="invoice.balance > 0"
                                                class="flex justify-center mb-4"
                                            >
                                                <div
                                                    class="px-4 py-1.5 rounded-2xl border border-red-100 bg-red-50/50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-[9px] font-black shadow-sm flex items-center gap-1.5"
                                                >
                                                    <span class="relative flex h-2 w-2">
                                                        <span
                                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"
                                                        ></span>
                                                        <span
                                                            class="relative inline-flex rounded-full h-2 w-2 bg-red-500"
                                                        ></span>
                                                    </span>
                                                    {{ getOverdueLabel(invoice.due_date) }}
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-3">
                                                <div
                                                    class="flex justify-between items-center bg-slate-50/30 dark:bg-slate-800/30 p-2 rounded-xl border border-transparent hover:border-slate-100 dark:hover:border-slate-700 transition-colors"
                                                >
                                                    <span
                                                        class="text-xs font-black text-slate-800 dark:text-white font-mono"
                                                        dir="ltr"
                                                    >
                                                        {{ formatCurrency(invoice.total) }}
                                                    </span>
                                                    <span
                                                        class="text-[10px] font-black text-slate-400 uppercase"
                                                    >
                                                        {{ $t('invoices.amount') }}
                                                    </span>
                                                </div>

                                                <template v-if="invoice.balance > 0">
                                                    <div
                                                        class="flex justify-between items-center bg-red-50/10 dark:bg-red-900/10 p-2 rounded-xl"
                                                    >
                                                        <span
                                                            class="text-sm font-black text-red-600 dark:text-red-400 font-mono"
                                                            dir="ltr"
                                                        >
                                                            {{
                                                                formatCurrency(invoice.balance || 0)
                                                            }}
                                                        </span>
                                                        <span
                                                            class="text-[10px] font-black text-red-400 uppercase"
                                                        >
                                                            {{ $t('invoices.balance') }}
                                                        </span>
                                                    </div>
                                                    <div
                                                        class="flex justify-between items-center p-2"
                                                    >
                                                        <span
                                                            class="text-[10px] font-bold text-slate-700 dark:text-slate-300 font-mono"
                                                            dir="ltr"
                                                        >
                                                            {{
                                                                formatDate(
                                                                    invoice.due_date ||
                                                                        invoice.issue_date
                                                                )
                                                            }}
                                                        </span>
                                                        <span
                                                            class="text-[10px] font-black text-slate-400 uppercase"
                                                        >
                                                            {{
                                                                $t(
                                                                    'invoices.purchases.due_date_label'
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            </div>

                            <!-- List View -->
                            <div
                                v-else
                                class="overflow-x-auto bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/50 shadow-sm"
                            >
                                <DataTable structured class="w-full text-sm">
                                    <thead>
                                        <tr
                                            class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50"
                                        >
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                #
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('purchasing.invoices.code') }}
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('purchasing.invoices.date') }}
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('invoices.purchases.subtotal') }}
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('invoices.purchases.discount') }}
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('invoices.purchases.subtotal_after') }}
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('common.vat') }}
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('invoices.purchases.total') }}
                                            </th>
                                            <th
                                                class="py-3 px-4 text-center font-bold align-middle"
                                            >
                                                {{ $t('invoices.purchases.remaining') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                        <tr
                                            v-for="(inv, index) in filteredInvoices"
                                            :key="inv.id"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                        >
                                            <td
                                                class="py-4 px-4 text-center text-gray-500 font-bold font-mono align-middle"
                                            >
                                                {{ index + 1 }}
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center font-bold text-gray-700 dark:text-gray-300 font-mono align-middle"
                                            >
                                                <div class="flex items-center justify-center gap-2">
                                                    <Link
                                                        :href="
                                                            route(
                                                                'app.invoices.purchases.show',
                                                                inv.id
                                                            )
                                                        "
                                                        class="text-blue-600 dark:text-blue-400 hover:underline"
                                                    >
                                                        {{ inv.code || inv.invoice_number }}
                                                    </Link>
                                                    <span
                                                        :class="[
                                                            'px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider border shadow-sm transition-all',
                                                            getStatusClass(inv.status),
                                                        ]"
                                                    >
                                                        {{
                                                            $t('common.status_' + inv.status) ||
                                                            inv.status
                                                        }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono align-middle"
                                                dir="ltr"
                                            >
                                                {{ formatDate(inv.issue_date) }}
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle"
                                                dir="ltr"
                                            >
                                                {{ formatCurrency(inv.subtotal) }}
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center font-black text-red-600 dark:text-red-400 font-mono align-middle"
                                                dir="ltr"
                                            >
                                                {{ formatCurrency(inv.discount_amount) }}
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle"
                                                dir="ltr"
                                            >
                                                {{
                                                    formatCurrency(
                                                        Number(inv.subtotal) -
                                                            Number(inv.discount_amount)
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle"
                                                dir="ltr"
                                            >
                                                {{ formatCurrency(inv.tax_amount) }}
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle"
                                                dir="ltr"
                                            >
                                                {{ formatCurrency(inv.total) }}
                                            </td>
                                            <td
                                                class="py-4 px-4 text-center font-black font-mono align-middle"
                                                :class="
                                                    inv.balance > 0
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-emerald-600 dark:text-emerald-400'
                                                "
                                                dir="ltr"
                                            >
                                                {{ formatCurrency(inv.balance) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </DataTable>
                            </div>
                        </div>
                        <div
                            v-else
                            class="text-center py-12 bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 shadow-sm"
                        >
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4"
                            >
                                <svg
                                    class="w-8 h-8 text-green-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                                    />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ $t('common.no_data') }}
                            </p>
                        </div>
                    </div>

                    <!-- ========== RETURNS TAB ========== -->
                    <div v-if="activeTab === 'returns'" class="space-y-4">
                        <div
                            v-if="returnsList.length > 0"
                            class="overflow-x-auto bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/50 shadow-sm"
                        >
                            <DataTable structured class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50"
                                    >
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            #
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('invoices.return_code') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('payments.form.date') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('purchasing.invoices.code') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('invoices.purchases.subtotal') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('common.vat') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('common.total') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('suppliers.remaining_refund') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('invoices.returned_items') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('payments.form.notes') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                    <tr
                                        v-for="(ret, index) in returnsList"
                                        :key="ret.id"
                                        class="hover:bg-rose-50/30 dark:hover:bg-rose-950/10 transition-colors"
                                    >
                                        <td
                                            class="py-4 px-4 text-center text-gray-500 font-bold font-mono align-middle"
                                        >
                                            {{ index + 1 }}
                                        </td>
                                        <td class="py-4 px-4 text-center font-bold align-middle">
                                            <Link
                                                :href="
                                                    route(
                                                        'app.invoices.purchases.returns.show',
                                                        ret.id
                                                    )
                                                "
                                                class="text-rose-600 dark:text-rose-400 hover:underline font-mono"
                                            >
                                                {{ ret.code }}
                                            </Link>
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono align-middle"
                                            dir="ltr"
                                        >
                                            {{ formatDate(ret.return_date) }}
                                        </td>
                                        <td class="py-4 px-4 text-center align-middle">
                                            <Link
                                                v-if="ret.purchase_invoice"
                                                :href="
                                                    route(
                                                        'app.invoices.purchases.show',
                                                        ret.purchase_invoice.id
                                                    )
                                                "
                                                class="text-blue-600 dark:text-blue-400 hover:underline font-mono font-bold"
                                            >
                                                {{
                                                    ret.purchase_invoice.code ||
                                                    ret.purchase_invoice.invoice_number
                                                }}
                                            </Link>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle"
                                            dir="ltr"
                                        >
                                            {{ formatCurrency(ret.subtotal) }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle"
                                            dir="ltr"
                                        >
                                            {{ formatCurrency(ret.tax_amount) }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center font-black text-rose-600 dark:text-rose-400 font-mono align-middle"
                                            dir="ltr"
                                        >
                                            {{ formatCurrency(ret.total) }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center font-black text-amber-600 dark:text-amber-400 font-mono align-middle"
                                            dir="ltr"
                                        >
                                            {{
                                                formatCurrency(
                                                    ret.remaining_refund !== undefined &&
                                                        ret.remaining_refund !== null
                                                        ? ret.remaining_refund
                                                        : ret.total
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center font-black text-gray-700 dark:text-gray-300 align-middle"
                                            dir="ltr"
                                        >
                                            <span
                                                class="px-2 py-0.5 rounded-lg bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 text-[10px] font-black"
                                            >
                                                {{ ret.lines?.length || 0 }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center text-gray-500 dark:text-gray-400 max-w-xs truncate align-middle"
                                            :title="ret.notes"
                                        >
                                            {{ ret.notes || '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                        <div
                            v-else
                            class="text-center py-12 bg-white dark:bg-gray-800 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 shadow-sm"
                        >
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4"
                            >
                                <svg
                                    class="w-8 h-8 text-rose-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"
                                    />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ $t('common.no_data') }}
                            </p>
                        </div>
                    </div>

                    <!-- ========== PAYMENTS TAB ========== -->
                    <div v-if="activeTab === 'payments'" class="space-y-4">
                        <div v-if="paymentsList.length > 0" class="overflow-x-auto">
                            <DataTable structured class="w-full text-sm">
                                <thead>
                                    <tr
                                        class="text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-3 bg-gray-50/50 dark:bg-gray-900/50"
                                    >
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            #
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('payments.form.method') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('payments.form.type') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('payments.form.date') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('purchasing.invoices.code') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('payments.form.amount') }}
                                        </th>
                                        <th class="py-3 px-4 text-center font-bold align-middle">
                                            {{ $t('payments.recorded_by') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                                    <tr
                                        v-for="(payment, index) in paymentsList"
                                        :key="payment.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                    >
                                        <td
                                            class="py-4 px-4 text-center text-gray-500 font-bold font-mono align-middle"
                                        >
                                            {{ index + 1 }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center font-bold text-gray-700 dark:text-gray-300 align-middle"
                                        >
                                            {{
                                                $t(`payments.methods.${payment.payment_method}`) ||
                                                payment.payment_method
                                            }}
                                        </td>
                                        <td class="py-4 px-4 text-center font-bold align-middle">
                                            <span
                                                :class="
                                                    payment.type === 'refund'
                                                        ? 'text-red-600 dark:text-red-450 bg-red-50 dark:bg-red-950/30'
                                                        : 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/30'
                                                "
                                                class="px-2.5 py-1 rounded-lg text-xs font-bold"
                                            >
                                                {{
                                                    payment.type === 'refund'
                                                        ? $t('payments.types.refund')
                                                        : $t('payments.types.payment')
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-mono align-middle"
                                            dir="ltr"
                                        >
                                            {{ formatDate(payment.payment_date) }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center text-gray-600 dark:text-gray-400 font-mono align-middle"
                                        >
                                            <Link
                                                v-if="payment.purchase_invoice"
                                                :href="
                                                    route(
                                                        'app.invoices.purchases.show',
                                                        payment.purchase_invoice.id
                                                    )
                                                "
                                                class="text-blue-600 dark:text-blue-400 hover:underline"
                                            >
                                                {{
                                                    payment.purchase_invoice.code ||
                                                    payment.purchase_invoice.invoice_number
                                                }}
                                            </Link>
                                            <span v-else>—</span>
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center font-black text-gray-900 dark:text-white font-mono align-middle"
                                            dir="ltr"
                                        >
                                            {{ formatCurrency(payment.amount) }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-center text-gray-600 dark:text-gray-300 font-semibold align-middle"
                                        >
                                            {{ payment.received_by?.name || '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto rounded-full bg-purple-100 flex items-center justify-center mb-4"
                            >
                                <svg
                                    class="w-8 h-8 text-purple-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ $t('common.no_data') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </PrintEngine>

        <!-- Create Modal -->
        <CreateModal
            v-if="showEditModal"
            :show="showEditModal"
            :supplier="supplier"
            @close="showEditModal = false"
        />

        <!-- Quick Payment Modal -->
        <SupplierQuickPaymentModal
            v-if="showPaymentModal"
            :show="showPaymentModal"
            :supplier="supplier"
            :balance="balance"
            :purchase-invoices="props.purchaseInvoices || []"
            @close="showPaymentModal = false"
        />

        <ConfirmModal />

        <!-- Direct Purchase Invoice Form Modal -->
        <PurchaseInvoiceFormModal
            v-if="showInvoiceModal"
            :show="showInvoiceModal"
            :suppliers="suppliers"
            :warehouses="warehouses"
            :units="units"
            :defaultWarehouse="defaultWarehouse"
            :default-supplier-id="supplier.id"
            @close="showInvoiceModal = false"
            @saved="onInvoiceSaved"
        />

        <!-- Official Statement Print Area (Visible only when printed) -->
        <Teleport to="body">
            <div id="supplier-statement-print-area" class="hidden">
                <div class="print-statement-container p-6 bg-white text-black font-sans text-xs">
                    <StandardReportHeader
                        :title="$t('purchasing.supplier_statement')"
                        :subtitle="`${$t('common.period_label')} ${statementStartDate || $t('common.start')} ${$t('common.to')} ${statementEndDate || $t('common.until_now')}`"
                    >
                        <template #extra>
                            <div class="space-y-0.5 text-xs">
                                <p class="font-bold text-gray-900">{{ supplier.name }}</p>
                                <p v-if="supplier.code" class="font-mono text-gray-600">
                                    {{ $t('common.code_label') }}: {{ supplier.code }}
                                </p>
                                <p v-if="supplier.tax_number" class="font-mono text-gray-600">
                                    {{ $t('purchasing.supplier_is_vat_payer') }}:
                                    {{ supplier.tax_number }}
                                </p>
                                <p v-if="supplier.phone" dir="ltr" class="font-mono text-gray-600">
                                    {{ supplier.phone }}
                                </p>
                            </div>
                        </template>
                    </StandardReportHeader>

                    <!-- Summary Table -->
                    <div class="grid grid-cols-5 gap-2 mb-4 text-center">
                        <div class="p-2 border border-gray-400 rounded">
                            <span class="block text-[10px] text-gray-600 font-bold">
                                {{ $t('purchasing.opening_balance') }}
                            </span>
                            <span class="block font-black font-mono mt-0.5" dir="ltr">
                                {{ formatCurrency(statementLedger.openingBalance) }}
                            </span>
                        </div>
                        <div class="p-2 border border-gray-400 rounded">
                            <span class="block text-[10px] text-gray-600 font-bold">
                                {{ $t('purchasing.total_invoices') }}
                            </span>
                            <span class="block font-black font-mono mt-0.5" dir="ltr">
                                {{ formatCurrency(statementLedger.periodInvoiced) }}
                            </span>
                        </div>
                        <div class="p-2 border border-gray-400 rounded">
                            <span class="block text-[10px] text-gray-600 font-bold">
                                {{ $t('purchasing.total_payments') }}
                            </span>
                            <span class="block font-black font-mono mt-0.5" dir="ltr">
                                {{ formatCurrency(statementLedger.periodPaid) }}
                            </span>
                        </div>
                        <div class="p-2 border border-gray-400 rounded">
                            <span class="block text-[10px] text-gray-600 font-bold">
                                {{ $t('purchasing.total_returns') }}
                            </span>
                            <span class="block font-black font-mono mt-0.5" dir="ltr">
                                {{ formatCurrency(statementLedger.periodReturned) }}
                            </span>
                        </div>
                        <div class="p-2 border-2 border-black rounded bg-gray-100">
                            <span class="block text-[10px] font-black">
                                {{ $t('purchasing.final_due_balance') }}
                            </span>
                            <span class="block font-black font-mono mt-0.5" dir="ltr">
                                {{ formatCurrency(statementLedger.closingBalance) }}
                            </span>
                        </div>
                    </div>

                    <!-- Ledger Table -->
                    <DataTable
                        structured
                        class="w-full border-collapse border border-gray-400 text-[11px] mb-6"
                    >
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="border border-gray-400 p-1.5 text-center">#</th>
                                <th class="border border-gray-400 p-1.5 text-center">
                                    {{ $t('common.date') }}
                                </th>
                                <th class="border border-gray-400 p-1.5 text-center">
                                    {{ $t('customers.statement.type') }}
                                </th>
                                <th class="border border-gray-400 p-1.5 text-center">
                                    {{ $t('system.credits.reference') }}
                                </th>
                                <th class="border border-gray-400 p-1.5 text-center">
                                    {{ $t('common.statement') }}
                                </th>
                                <th class="border border-gray-400 p-1.5 text-center">
                                    {{ $t('purchasing.invoice_plus') }}
                                </th>
                                <th class="border border-gray-400 p-1.5 text-center">
                                    {{ $t('purchasing.paid_or_returned') }}
                                </th>
                                <th class="border border-gray-400 p-1.5 text-center">
                                    {{ $t('invoices.columns.balance') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="statementLedger.openingBalance !== 0">
                                <td class="border border-gray-400 p-1 text-center font-mono">—</td>
                                <td class="border border-gray-400 p-1 text-center font-mono">
                                    {{ statementStartDate || $t('common.start') }}
                                </td>
                                <td
                                    class="border border-gray-400 p-1 text-center font-bold"
                                    colspan="3"
                                >
                                    {{ $t('purchasing.opening_balance') }}
                                </td>
                                <td class="border border-gray-400 p-1 text-center font-mono">—</td>
                                <td class="border border-gray-400 p-1 text-center font-mono">—</td>
                                <td
                                    class="border border-gray-400 p-1 text-center font-black font-mono"
                                    dir="ltr"
                                >
                                    {{ formatCurrency(statementLedger.openingBalance) }}
                                </td>
                            </tr>
                            <tr v-for="(tx, idx) in statementLedger.rows" :key="idx">
                                <td class="border border-gray-400 p-1 text-center font-mono">
                                    {{ idx + 1 }}
                                </td>
                                <td
                                    class="border border-gray-400 p-1 text-center font-mono"
                                    dir="ltr"
                                >
                                    {{ formatDate(tx.date) }}
                                </td>
                                <td class="border border-gray-400 p-1 text-center font-bold">
                                    {{
                                        tx.type === 'invoice'
                                            ? $t('company_profile.invoices_tab.table.type_purchase')
                                            : tx.type === 'return'
                                              ? $t('purchasing.purchase_return')
                                              : $t('purchasing.payment_voucher')
                                    }}
                                </td>
                                <td class="border border-gray-400 p-1 text-center font-mono">
                                    {{ tx.code }}
                                </td>
                                <td class="border border-gray-400 p-1 text-center text-[10px]">
                                    {{ tx.notes || '—' }}
                                </td>
                                <td
                                    class="border border-gray-400 p-1 text-center font-mono font-bold"
                                    dir="ltr"
                                >
                                    {{ tx.debit > 0 ? formatCurrency(tx.debit) : '—' }}
                                </td>
                                <td
                                    class="border border-gray-400 p-1 text-center font-mono font-bold"
                                    dir="ltr"
                                >
                                    {{ tx.credit > 0 ? formatCurrency(tx.credit) : '—' }}
                                </td>
                                <td
                                    class="border border-gray-400 p-1 text-center font-mono font-black"
                                    dir="ltr"
                                >
                                    {{ formatCurrency(tx.runningBalance) }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>

                    <!-- Unified Footer (NO stamps/signatures per instructions) -->
                    <StandardReportFooter />
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch, h } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateModal from './CreateModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import PurchaseInvoiceFormModal from '@/Components/Purchasing/PurchaseInvoiceFormModal.vue';
import SupplierQuickPaymentModal from '@/Components/Purchasing/SupplierQuickPaymentModal.vue';
import Tooltip from '@/Components/Tooltip.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import BackButton from '@/Components/BackButton.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    supplier: Object,
    purchaseInvoices: Array,
    returnInvoices: Array,
    payments: Array,
    counts: Object,
    balance: Number,
    amountDueFromSupplier: { type: Number, default: 0 },
    suppliers: Array,
    defaultWarehouse: Object,
    warehouses: Array,
    units: Array,
});

const { t, locale } = useI18n();
const { formatMoney } = useNumberFormat();
const { confirm } = useConfirm();
const { can, isAnyAdmin } = usePermission();

const showEditModal = ref(false);
const showInvoiceModal = ref(false);
const showPaymentModal = ref(false);
const activeTab = ref('overview');

const onInvoiceSaved = () => {
    router.reload({
        only: ['purchaseInvoices', 'returnInvoices', 'counts', 'balance', 'payments'],
    });
    showInvoiceModal.value = false;
};

const invoicesList = computed(() => props.purchaseInvoices || []);
const returnsList = computed(() => props.returnInvoices || []);
const paymentsList = computed(() => props.payments || []);

// Statement Date Range and Logic
const currentYear = new Date().getFullYear();
const statementStartDate = ref(`${currentYear}-01-01`);
const statementEndDate = ref(new Date().toISOString().split('T')[0]);

function setStatementPreset(preset) {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');
    const todayStr = `${y}-${m}-${d}`;
    statementEndDate.value = todayStr;

    if (preset === 'this_month') {
        statementStartDate.value = `${y}-${m}-01`;
    } else if (preset === 'last_3_months') {
        const threeMonthsAgo = new Date();
        threeMonthsAgo.setMonth(today.getMonth() - 3);
        const y3 = threeMonthsAgo.getFullYear();
        const m3 = String(threeMonthsAgo.getMonth() + 1).padStart(2, '0');
        const d3 = String(threeMonthsAgo.getDate()).padStart(2, '0');
        statementStartDate.value = `${y3}-${m3}-${d3}`;
    } else if (preset === 'this_year') {
        statementStartDate.value = `${y}-01-01`;
    } else if (preset === 'all') {
        statementStartDate.value = '';
        statementEndDate.value = '';
    }
}

const statementLedger = computed(() => {
    const allTxs = [];

    // Invoices
    (props.purchaseInvoices || []).forEach((inv) => {
        if (inv.status === 'cancelled' || inv.status === 'draft') return;
        allTxs.push({
            date: inv.issue_date || (inv.created_at ? inv.created_at.split('T')[0] : ''),
            type: 'invoice',
            code: inv.code || inv.invoice_number,
            link: route('app.invoices.purchases.show', inv.id),
            debit: Number(inv.total) || 0,
            credit: 0,
            notes:
                inv.notes ||
                t('purchasing.purchase_invoice_number', {
                    invoice_number: inv.code || inv.invoice_number,
                }),
            id: `inv-${inv.id}`,
        });
    });

    // Returns
    (props.returnInvoices || []).forEach((ret) => {
        allTxs.push({
            date: ret.return_date || (ret.created_at ? ret.created_at.split('T')[0] : ''),
            type: 'return',
            code: ret.code,
            link: route('app.invoices.purchases.returns.show', ret.id),
            debit: 0,
            credit: Number(ret.total) || 0,
            notes: ret.notes || t('purchasing.purchase_return_note', { code: ret.code }),
            id: `ret-${ret.id}`,
        });
    });

    // Payments
    (props.payments || []).forEach((p) => {
        const isRefund = p.type === 'refund';
        allTxs.push({
            date: p.payment_date || (p.created_at ? p.created_at.split('T')[0] : ''),
            type: isRefund ? 'refund' : 'payment',
            code: p.reference
                ? t('common.voucher_label', { reference: p.reference })
                : p.purchase_invoice?.code || t('common.payment_number', { id: p.id }),
            link: p.purchase_invoice
                ? route('app.invoices.purchases.show', p.purchase_invoice.id)
                : null,
            debit: isRefund ? Number(p.amount) || 0 : 0,
            credit: isRefund ? 0 : Number(p.amount) || 0,
            notes:
                p.notes ||
                (isRefund
                    ? t('purchasing.cash_refund')
                    : t('purchasing.payment_voucher_prefix', {
                          payment_method:
                              t('payments.methods.' + p.payment_method) || p.payment_method,
                      })),
            id: `pay-${p.id}`,
        });
    });

    // Sort chronologically
    allTxs.sort((a, b) => new Date(a.date) - new Date(b.date));

    const sDate = statementStartDate.value;
    const eDate = statementEndDate.value;

    let openingBalance = 0;
    const filteredRows = [];

    allTxs.forEach((tx) => {
        if (sDate && tx.date < sDate) {
            openingBalance += tx.debit - tx.credit;
        } else if (!eDate || tx.date <= eDate) {
            filteredRows.push({ ...tx });
        }
    });

    let currentRunning = openingBalance;
    let periodInvoiced = 0;
    let periodPaid = 0;
    let periodReturned = 0;

    filteredRows.forEach((row) => {
        currentRunning += row.debit - row.credit;
        row.runningBalance = currentRunning;

        if (row.type === 'invoice') {
            periodInvoiced += row.debit;
        } else if (row.type === 'return') {
            periodReturned += row.credit;
        } else if (row.type === 'payment') {
            periodPaid += row.credit;
        } else if (row.type === 'refund') {
            periodPaid -= row.debit;
        }
    });

    return {
        openingBalance,
        rows: filteredRows,
        periodInvoiced,
        periodPaid,
        periodReturned,
        closingBalance: currentRunning,
    };
});

function printStatement() {
    printDocument();
}

const invoiceSearchQuery = ref('');
const invoiceViewMode = ref(localStorage.getItem('supplierInvoicesViewMode') || 'list');

watch(invoiceViewMode, (val) => {
    localStorage.setItem('supplierInvoicesViewMode', val);
});

const filteredInvoices = computed(() => {
    const list = invoicesList.value;
    if (!invoiceSearchQuery.value) return list;
    const q = invoiceSearchQuery.value.trim().toLowerCase();
    return list.filter(
        (inv) =>
            (inv.code && inv.code.toLowerCase().includes(q)) ||
            (inv.invoice_number && inv.invoice_number.toLowerCase().includes(q))
    );
});

const stripeClass = (status) => {
    const map = {
        paid: 'bg-emerald-500',
        open: 'bg-blue-500',
        draft: 'bg-gray-300 dark:bg-gray-600',
        cancelled: 'bg-red-500',
    };
    return map[status] || 'bg-gray-200 dark:bg-gray-700';
};

const getOverdueLabel = (dueDate) => {
    if (!dueDate) return t('invoices.due_amount');
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = today - due;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 0) {
        return t('invoices.purchases.overdue_days', { days: diffDays });
    }
    return t('invoices.purchases.amount_due');
};

// Icons
const HomeIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        }),
    ]);
const StatementIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        }),
    ]);
const InvoiceIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
        }),
    ]);
const ReturnIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M16 15v-6a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3',
        }),
    ]);
const PaymentIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        }),
    ]);

const tabs = computed(() => [
    {
        id: 'overview',
        label: t('common.overview'),
        count: null,
        icon: HomeIcon,
    },
    {
        id: 'statement',
        label: t('purchasing.suppliers.statement_tab'),
        count: null,
        icon: StatementIcon,
    },
    {
        id: 'invoices',
        label: t('purchasing.suppliers.invoices'),
        count: props.counts.invoices || 0,
        icon: InvoiceIcon,
    },
    {
        id: 'returns',
        label: t('invoices.return_history') || t('invoices.return_invoice'),
        count: props.counts.returns || 0,
        icon: ReturnIcon,
    },
    {
        id: 'payments',
        label: t('purchasing.suppliers.payments'),
        count: props.counts.payments || 0,
        icon: PaymentIcon,
    },
]);

function formatCurrency(value) {
    if (!value && value !== 0) return '';
    const val = Math.abs(value) < 0.01 ? 0 : value;
    return formatMoney(val);
}

function formatDate(dateString) {
    if (!dateString) return '—';
    return new Date(dateString).toLocaleDateString('en-GB');
}

function getStatusClass(status) {
    switch (status) {
        case 'paid':
            return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/30';
        case 'partial':
            return 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/30';
        case 'cancelled':
            return 'bg-red-50 text-red-700 border-red-100 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800/30';
        case 'open':
        case 'unpaid':
            return 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/30';
        case 'draft':
        default:
            return 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800/30 dark:text-gray-400 dark:border-gray-700/30';
    }
}

async function confirmDelete() {
    const confirmed = await confirm({
        title: t('common.delete_confirm'),
        message: t('common.delete_confirm_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (confirmed) {
        router.delete(route('app.purchasing.suppliers.destroy', props.supplier.id), {
            onSuccess: () => router.visit(route('app.purchasing.suppliers.index')),
        });
    }
}
</script>

<style scoped>
@media print {
    body * {
        visibility: hidden !important;
    }
    #supplier-statement-print-area,
    #supplier-statement-print-area * {
        visibility: visible !important;
    }
    #supplier-statement-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: block !important;
        background: white !important;
        color: black !important;
    }
}
</style>
