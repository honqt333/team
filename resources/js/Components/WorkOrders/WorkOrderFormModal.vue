<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center"
                >
                    <svg
                        class="w-5 h-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                    </svg>
                </div>
                {{ workOrder ? $t('work_orders.edit') : $t('work_orders.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Tabs Navigation -->
            <div class="flex flex-wrap gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    @click="activeTab = tab.key"
                    :class="[
                        'relative flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 px-2 sm:px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all flex-1 sm:flex-none',
                        activeTab === tab.key
                            ? 'bg-white dark:bg-gray-800 text-action-primary shadow-sm'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
                    ]"
                >
                    <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
                    <span class="text-[10px] sm:text-sm text-center leading-tight">
                        {{ tab.label }}
                    </span>
                    <span
                        v-if="tabErrorCount[tab.key] > 0"
                        class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full"
                    >
                        {{ tabErrorCount[tab.key] }}
                    </span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="min-h-[350px]">
                <!-- Tab 1: Main Info -->
                <div v-show="activeTab === 'main_info'" class="space-y-4">
                    <!-- Search Section (Hide if pre-selected vehicle exists and not editing) -->
                    <div
                        v-if="!vehicle && (!workOrder || !selectedVehicle)"
                        data-field="vehicle_id"
                        class="relative bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('quotes.form_tabs.search_placeholder') }}
                            </label>
                            <button
                                type="button"
                                @click="showVehicleModal = true"
                                class="text-xs text-action-primary hover:text-selection-text flex items-center gap-1"
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
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                {{ $t('quotes.form_tabs.add_vehicle') }}
                            </button>
                        </div>

                        <!-- Search Input -->
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                name="vehicle_id"
                                type="text"
                                @input="debouncedSearch"
                                @focus="showResults = true"
                                :placeholder="$t('quotes.form_tabs.search_placeholder')"
                                class="w-full px-4 py-3 ps-10 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                            />
                            <svg
                                class="w-5 h-5 absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"
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

                            <!-- Loading -->
                            <div v-if="searching" class="absolute end-3 top-1/2 -translate-y-1/2">
                                <svg
                                    class="animate-spin w-5 h-5 text-action-primary"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Search Results Dropdown -->
                        <div
                            v-if="showResults && searchResults.length > 0"
                            class="absolute z-50 mt-2 start-0 end-0 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto"
                        >
                            <VehicleSearchResult
                                v-for="vehicle in searchResults"
                                :key="vehicle.id"
                                :vehicle="vehicle"
                                :blocked="vehicle.has_open_work_order"
                                :customer-name="toEnglish(vehicle.customer?.name)"
                                @select="selectVehicle"
                            >
                                <div
                                    v-if="vehicle.has_open_work_order"
                                    class="mt-1 flex items-center gap-2"
                                >
                                    <span
                                        class="text-xs text-red-600 dark:text-red-400 font-medium"
                                    >
                                        {{ $t('work_orders.has_open_work_order') }} ({{
                                            vehicle.open_work_order?.code
                                        }})
                                    </span>
                                    <a
                                        :href="
                                            route('work-orders.show', vehicle.open_work_order?.id)
                                        "
                                        @click.stop
                                        class="text-xs text-action-primary hover:underline font-medium"
                                    >
                                        {{ $t('work_orders.go_to_work_order') }}
                                    </a>
                                </div>
                            </VehicleSearchResult>
                        </div>

                        <p
                            v-if="
                                showResults &&
                                searchQuery.length >= 2 &&
                                searchResults.length === 0 &&
                                !searching
                            "
                            class="text-center text-gray-500 dark:text-gray-400 py-4 text-sm"
                        >
                            {{ $t('quotes.form_tabs.no_results') }}
                        </p>
                    </div>

                    <!-- Selected Vehicle/Customer Info -->
                    <div
                        v-if="selectedVehicle"
                        class="relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
                    >
                        <!-- Header / Plate Section -->
                        <div
                            class="bg-gray-50 dark:bg-gray-900/50 p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <SaudiPlateDisplay
                                    :plate-number="selectedVehicle.plate_number"
                                    size="sm"
                                />
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs font-bold text-action-primary uppercase tracking-wider"
                                    >
                                        {{ $t('common.selected') }}
                                    </span>
                                    <h4
                                        class="text-lg font-bold text-gray-900 dark:text-white leading-tight"
                                    >
                                        {{ getVehicleDisplay(selectedVehicle) }}
                                    </h4>
                                </div>
                            </div>
                            <button
                                v-if="!vehicle && !workOrder"
                                type="button"
                                @click="clearSelection"
                                class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-all"
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

                        <!-- Info Grid -->
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Column 1: Technical Details -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-selection-bg flex items-center justify-center text-action-primary"
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
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                                        >
                                            {{ $t('vehicles.form.vin') }}
                                        </span>
                                        <span
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300 font-mono"
                                        >
                                            {{ selectedVehicle.vin || '—' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400"
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
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                                            >
                                                {{ $t('vehicles.form.year') }}
                                            </span>
                                            <span
                                                class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                            >
                                                {{ toEnglish(selectedVehicle.year) || '—' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400"
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
                                                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.828 2.828a2 2 0 010 2.828l-8.486 8.486"
                                                />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                                            >
                                                {{ $t('vehicles.form.color') }}
                                            </span>
                                            <div class="flex items-center gap-1.5">
                                                <span
                                                    v-if="selectedVehicle.color"
                                                    class="w-2.5 h-2.5 rounded-full border border-gray-300 dark:border-gray-600"
                                                    :style="{
                                                        backgroundColor: getColorHex(
                                                            selectedVehicle.color
                                                        ),
                                                    }"
                                                ></span>
                                                <span
                                                    class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                                >
                                                    {{ selectedVehicle.color || '—' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Customer Details -->
                            <div
                                class="space-y-3 pt-4 sm:pt-0 sm:ps-4 sm:border-s border-gray-100 dark:border-gray-700"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-action-primary hover:bg-action-primary-hover flex items-center justify-center text-white font-bold text-sm shadow-sm ring-2 ring-white dark:ring-gray-800"
                                    >
                                        {{
                                            selectedVehicle.customer?.name?.[0]?.toUpperCase() ||
                                            'C'
                                        }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                                        >
                                            {{ $t('quotes.form_tabs.customer_name') }}
                                        </span>
                                        <span
                                            class="text-sm font-bold text-gray-900 dark:text-white truncate"
                                        >
                                            {{ selectedVehicle.customer?.name }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400"
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
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                                        >
                                            {{ $t('quotes.form_tabs.phone') }}
                                        </span>
                                        <span
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300 font-mono"
                                            dir="ltr"
                                        >
                                            {{ toEnglish(selectedVehicle.customer?.phone) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Credit Terms & Limit Info -->
                                <div
                                    v-if="
                                        selectedVehicle.customer &&
                                        (selectedVehicle.customer.credit_limit > 0 ||
                                            (selectedVehicle.customer.balance || 0) > 0)
                                    "
                                    class="pt-2 border-t border-gray-100 dark:border-gray-700/60 space-y-2"
                                >
                                    <div
                                        v-if="selectedVehicle.customer.credit_limit > 0"
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ $t('customers.form.credit_limit') }}:
                                        </span>
                                        <span
                                            class="font-bold font-mono text-gray-900 dark:text-white"
                                        >
                                            {{ formatPrice(selectedVehicle.customer.credit_limit) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ $t('customers.balance_due') }}:
                                        </span>
                                        <span
                                            class="font-bold font-mono"
                                            :class="
                                                (selectedVehicle.customer.balance || 0) > 0
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-emerald-600 dark:text-emerald-400'
                                            "
                                        >
                                            {{ formatPrice(selectedVehicle.customer.balance || 0) }}
                                        </span>
                                    </div>

                                    <!-- Exceeded Warning Banner -->
                                    <div
                                        v-if="
                                            selectedVehicle.customer.credit_limit > 0 &&
                                            (selectedVehicle.customer.balance || 0) >=
                                                selectedVehicle.customer.credit_limit
                                        "
                                        class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center gap-1.5 text-rose-700 dark:text-rose-300 font-black text-xs"
                                        >
                                            <svg
                                                class="w-4 h-4 text-rose-500 shrink-0"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                                />
                                            </svg>
                                            <span>
                                                {{ $t('work_orders.credit_limit_warning_title') }}
                                            </span>
                                        </div>
                                        <p
                                            class="text-[11px] text-rose-600 dark:text-rose-400 leading-tight"
                                        >
                                            {{ $t('work_orders.credit_limit_warning_desc') }}
                                        </p>
                                        <div class="pt-1">
                                            <AppCheckbox
                                                v-model="form.allow_credit_override"
                                                :label="$t('work_orders.allow_credit_override')"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="form.errors.customer_id"
                                    class="p-2 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/50 text-xs text-red-600 dark:text-red-400 font-bold"
                                >
                                    {{ form.errors.customer_id }}
                                </div>
                            </div>
                        </div>

                        <!-- Partner / Third-Party Payer -->
                        <div
                            v-if="dealers && dealers.length > 0"
                            class="mt-4 pt-4 border-t border-gray-200/70 dark:border-gray-700/70 space-y-3"
                            data-field="dealer_id"
                        >
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                                >
                                    {{ $t('work_orders.dealer') }}
                                </label>
                                <SearchableSelect
                                    v-model="form.dealer_id"
                                    :options="dealers"
                                    :placeholder="$t('work_orders.dealer_placeholder')"
                                    :option-label="
                                        (d) =>
                                            `${d.name}${d.type ? ' — ' + $t(`dealers.types.${d.type}`) : ''}`
                                    "
                                    option-value="id"
                                    teleport
                                    :error="form.errors.dealer_id"
                                    @update:modelValue="
                                        (val) => {
                                            if (!val) form.is_paid_by_dealer = false;
                                        }
                                    "
                                />
                            </div>
                            <div v-if="form.dealer_id" class="pt-1">
                                <AppToggle
                                    v-model="form.is_paid_by_dealer"
                                    :label="$t('work_orders.is_paid_by_dealer')"
                                    :description="$t('work_orders.is_paid_by_dealer_hint')"
                                    color="primary"
                                />
                            </div>
                        </div>
                    </div>

                    <VehicleOdometerField
                        v-if="selectedVehicle"
                        v-model="form.odometer"
                        v-model:allow-lower="form.allow_lower_odometer"
                        :last-vehicle-odometer="lastVehicleOdometer"
                        :show-odometer-warning="showOdometerWarning"
                        :error="form.errors.odometer"
                        @history="showMileageModal = true"
                    />

                    <!-- Dates Grid -->
                    <div v-if="selectedVehicle" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Entry Date -->
                        <div data-field="entry_date">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                            >
                                {{ $t('work_orders.form.entry_date') }}
                            </label>
                            <CustomDatePicker v-model="form.entry_date" name="entry_date" />
                        </div>

                        <!-- Expected End Date -->
                        <div data-field="expected_end_date">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                            >
                                {{ $t('work_orders.form.expected_end_date') }}
                            </label>
                            <CustomDatePicker
                                v-model="form.expected_end_date"
                                name="expected_end_date"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Customer Complaint -->
                <div v-show="activeTab === 'customer_complaint'" class="space-y-4">
                    <div
                        data-field="customer_complaint"
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                    >
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            {{ $t('quotes.form_tabs.customer_complaint') }}
                        </label>
                        <textarea
                            v-model="form.customer_complaint"
                            name="customer_complaint"
                            rows="10"
                            :placeholder="$t('quotes.form_tabs.customer_complaint_placeholder')"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Tab 3: Initial Assessment -->
                <div v-show="activeTab === 'initial_assessment'" class="space-y-4">
                    <div
                        data-field="initial_assessment"
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                    >
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            {{ $t('quotes.form_tabs.initial_assessment') }}
                        </label>
                        <textarea
                            v-model="form.initial_assessment"
                            name="initial_assessment"
                            rows="10"
                            :placeholder="$t('quotes.form_tabs.initial_assessment_placeholder')"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Tab 4: Vehicle Condition Report -->
                <div v-show="activeTab === 'condition_report'" class="space-y-4">
                    <VehicleConditionReport
                        v-model:damageMarks="form.damage_marks"
                        v-model:fuelLevel="form.fuel_level"
                    />
                </div>

                <!-- Tab 5: Vehicle Photos -->
                <div v-show="activeTab === 'photos'" class="space-y-4">
                    <VehiclePhotoUploader v-model:photos="form.photos" />
                </div>

                <!-- Tab 6: Departments -->
                <div v-show="activeTab === 'departments'" class="space-y-4">
                    <div
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                    >
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
                        >
                            {{ $t('quotes.form_tabs.select_departments') }}
                        </label>
                        <div class="space-y-2">
                            <SelectableCard
                                v-for="(dept, index) in availableDepartmentsList"
                                :key="dept.id"
                                v-model="form.departments"
                                :value="dept.id"
                                :title="`${toEnglish(index + 1)}. ${toEnglish(getName(dept))}`"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <span
                        v-if="Object.keys(form.errors).length > 0"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-300 border border-red-200 dark:border-red-900"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-red-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{
                            $t('common.fix_errors_count', {
                                count: Object.keys(form.errors).length,
                            })
                        }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="submitForm"
                        :disabled="
                            form.processing ||
                            !selectedVehicle ||
                            (showOdometerWarning && !form.allow_lower_odometer)
                        "
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            ></path>
                        </svg>
                        {{ form.processing ? $t('common.loading') : $t('common.save') }}
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>

    <!-- Vehicle Form Modal -->
    <VehicleFormModal
        v-if="showVehicleModal"
        :show="showVehicleModal"
        :customers="customers"
        :dealers="dealers"
        :makes="makes"
        :colors="colors"
        :modelsByMake="modelsByMake"
        @close="showVehicleModal = false"
        @saved="onVehicleSaved"
        @customer-created="handleCustomerCreated"
    />

    <!-- Vehicle Mileage Modal -->
    <VehicleMileageModal
        v-if="showMileageModal"
        :show="showMileageModal"
        :vehicle="selectedVehicle"
        @close="showMileageModal = false"
    />
</template>

<script setup>
import VehicleSearchResult from '@/Components/Vehicles/VehicleSearchResult.vue';
import { isLowerOdometer } from '@/Utils/odometer';
import VehicleOdometerField from '@/Components/Vehicles/VehicleOdometerField.vue';
import { ref, computed, watch, nextTick, onMounted, h } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';
import VehicleMileageModal from '@/Components/Vehicles/VehicleMileageModal.vue';
import VehicleConditionReport from '@/Components/WorkOrders/VehicleConditionReport.vue';
import VehiclePhotoUploader from '@/Components/WorkOrders/VehiclePhotoUploader.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';
import { useVehicleSearch } from '@/Composables/useVehicleSearch';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    vehicle: Object, // Pre-selected vehicle for creation
    customers: Array,
    dealers: {
        type: Array,
        default: () => [],
    },
    departments: Array,
    makes: Array,
    colors: Array,
    modelsByMake: Object,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();
const { toEnglish } = useNumberFormat();
const { scrollToError } = useFormScrollToError();

const workOrderTabMap = {
    customer_id: 'main_info',
    vehicle_id: 'main_info',
    odometer: 'main_info',
    contact_name: 'main_info',
    contact_phone: 'main_info',
    entry_date: 'main_info',
    expected_end_date: 'main_info',
    customer_complaint: 'customer_complaint',
    initial_assessment: 'initial_assessment',
    damage_marks: 'condition_report',
    fuel_level: 'condition_report',
    photos: 'photos',
    departments: 'departments',
    notes: 'main_info',
};

// Tab Icons as inline SVG components (Render functions for Vue 3 SFC compatibility)
const createIcon =
    (d, strokeWidth = 2) =>
    (props, { attrs }) =>
        h(
            'svg',
            {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                ...attrs,
            },
            [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': strokeWidth,
                    d,
                }),
            ]
        );

const IconInfo = createIcon('M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z');
const IconComplaint = createIcon(
    'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'
);
const IconAssessment = createIcon(
    'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'
);
const IconCondition = (props, { attrs }) =>
    h(
        'svg',
        {
            fill: 'none',
            stroke: 'currentColor',
            viewBox: '0 0 24 24',
            ...attrs,
        },
        [
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z',
            }),
            h('path', {
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
                'stroke-width': '2',
                d: 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
            }),
        ]
    );
const IconPhotos = createIcon(
    'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'
);
const IconDepartments = createIcon(
    'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
);

// Tabs - hide departments tab when editing
const allTabs = [
    { key: 'main_info', label: t('work_orders.tabs.main_info'), icon: IconInfo },
    { key: 'customer_complaint', label: t('quotes.tabs.customer_complaint'), icon: IconComplaint },
    { key: 'initial_assessment', label: t('quotes.tabs.initial_assessment'), icon: IconAssessment },
    { key: 'condition_report', label: t('work_orders.tabs.condition_report'), icon: IconCondition },
    { key: 'photos', label: t('work_orders.tabs.photos'), icon: IconPhotos },
    { key: 'departments', label: t('quotes.tabs.departments'), icon: IconDepartments },
];
const tabs = computed(() => {
    if (props.workOrder) {
        // When editing: hide departments, condition_report, and photos tabs
        return allTabs.filter(
            (tab) => !['departments', 'condition_report', 'photos'].includes(tab.key)
        );
    }
    return allTabs;
});

const activeTab = ref('main_info');

// Search
const { searchQuery, searchResults, searching, showResults, debouncedSearch } = useVehicleSearch(
    '/app/api/vehicles/search',
    () => props.show
);
const selectedVehicle = ref(null);

// Vehicle Modal
const showVehicleModal = ref(false);
const showMileageModal = ref(false);
const lastVehicleOdometer = ref(0);

const form = useForm({
    customer_id: props.workOrder?.customer_id || props.vehicle?.customer_id || '',
    vehicle_id: props.workOrder?.vehicle_id || props.vehicle?.id || '',
    dealer_id: props.workOrder?.dealer_id || props.vehicle?.dealer_id || '',
    is_paid_by_dealer: Boolean(props.workOrder?.is_paid_by_dealer || false),
    customer_complaint: props.workOrder?.customer_complaint || '',
    initial_assessment: props.workOrder?.initial_assessment || '',
    odometer: toEnglish(props.workOrder?.odometer ?? props.workOrder?.mileage ?? ''),
    allow_lower_odometer: false,
    allow_credit_override: false,
    contact_name: props.workOrder?.contact_name || '',
    contact_phone: toEnglish(props.workOrder?.contact_phone || ''),
    entry_date: props.workOrder?.entry_date || new Date().toISOString().split('T')[0],
    expected_end_date: props.workOrder?.expected_end_date || new Date().toISOString().split('T')[0],
    departments: (() => {
        const depts = props.workOrder?.departments?.map((d) => d.id) || [];
        if (props.workOrder?.show_packages_section) {
            depts.push('packages');
        }
        return depts;
    })(),
    damage_marks: props.workOrder?.damage_marks || [],
    fuel_level: props.workOrder?.fuel_level ?? 50,
    photos: [],
    notes: props.workOrder?.notes || '',
});

const tabErrorCount = computed(() => ({
    main_info: [
        'customer_id',
        'vehicle_id',
        'odometer',
        'contact_name',
        'contact_phone',
        'entry_date',
        'expected_end_date',
        'notes',
    ].filter((k) => form.errors[k]).length,
    customer_complaint: form.errors.customer_complaint ? 1 : 0,
    initial_assessment: form.errors.initial_assessment ? 1 : 0,
    condition_report: form.errors.damage_marks || form.errors.fuel_level ? 1 : 0,
    photos: form.errors.photos ? 1 : 0,
    departments: form.errors.departments ? 1 : 0,
}));

const availableDepartmentsList = computed(() => {
    const list = [...(props.departments || [])];
    list.push({
        id: 'packages',
        name_ar: 'باقات الخدمات',
        name_en: 'Service Packages',
        name: 'Service Packages',
    });
    return list;
});

// Watch for modal opening to reinitialize form values (for edit mode)
watch(
    () => props.show,
    (newVal) => {
        if (newVal && props.workOrder) {
            form.customer_id = props.workOrder.customer_id || '';
            form.vehicle_id = props.workOrder.vehicle_id || '';
            form.dealer_id = props.workOrder.dealer_id || '';
            form.is_paid_by_dealer = Boolean(props.workOrder.is_paid_by_dealer || false);
            form.customer_complaint = props.workOrder.customer_complaint || '';
            form.initial_assessment = props.workOrder.initial_assessment || '';
            form.odometer = toEnglish(props.workOrder.odometer ?? props.workOrder.mileage ?? '');
            form.allow_lower_odometer = false;
            form.contact_name = props.workOrder.contact_name || '';
            form.contact_phone = toEnglish(props.workOrder.contact_phone || '');
            form.entry_date = formatDateForInput(props.workOrder.entry_date);
            form.expected_end_date = formatDateForInput(props.workOrder.expected_end_date);
            const depts = props.workOrder.departments?.map((d) => d.id) || [];
            if (props.workOrder.show_packages_section) {
                depts.push('packages');
            }
            form.departments = depts;
            form.damage_marks = props.workOrder.damage_marks || [];
            form.fuel_level = props.workOrder.fuel_level ?? 50;
            form.photos =
                props.workOrder.photos?.map((photo) => ({
                    id: photo.id,
                    url: photo.url,
                    type: photo.type,
                    caption: photo.caption,
                })) || [];
            form.notes = props.workOrder.notes || '';

            if (props.workOrder.vehicle) {
                selectedVehicle.value = props.workOrder.vehicle;
                lastVehicleOdometer.value = props.workOrder.vehicle.odometer || 0;
                searchQuery.value = props.workOrder.vehicle.plate_number || '';
                // Ensure customer is set if not already matched
                if (!form.customer_id && props.workOrder.vehicle.customer_id) {
                    form.customer_id = props.workOrder.vehicle.customer_id;
                }
            }
        }
    },
    { immediate: true }
);

// Watch for vehicle prop (pre-selection)
watch(
    () => props.vehicle,
    (val) => {
        if (val && !props.workOrder) {
            // Only if not editing an existing order
            selectedVehicle.value = val;
            form.vehicle_id = val.id;
            form.customer_id = val.customer_id;
            form.dealer_id = val.dealer_id || '';
            form.odometer = val.odometer ?? '';
            lastVehicleOdometer.value = val.odometer || 0;
        }
    },
    { immediate: true }
);

// Watch for selectedVehicle to load customer relationship from props.customers if missing
watch(
    selectedVehicle,
    (val) => {
        if (!val) return;

        if (!val.customer && val.customer_id && props.customers) {
            const customerData = props.customers.find((c) => c.id === val.customer_id);
            if (customerData) {
                selectedVehicle.value = {
                    ...val,
                    customer: customerData,
                };
                return;
            }
        }

        if (val.customer) {
            ensureCustomerBalance(val.customer);
        }

        // Auto-fill contact info ONLY if creating a new record and they are currently empty
        if (!props.workOrder && val.customer) {
            if (!form.contact_name) {
                form.contact_name = val.customer.name;
            }
            if (!form.contact_phone) {
                form.contact_phone = toEnglish(val.customer.phone || '');
            }
        }
    },
    { immediate: true }
);

async function ensureCustomerBalance(customer) {
    if (!customer?.id) return;
    if (
        customer.balance !== undefined &&
        customer.balance !== null &&
        customer.credit_limit !== undefined
    )
        return;

    try {
        const response = await axios.get(`/app/api/customers/${customer.id}/balance`);
        if (response.data && selectedVehicle.value?.customer?.id === customer.id) {
            selectedVehicle.value = {
                ...selectedVehicle.value,
                customer: {
                    ...selectedVehicle.value.customer,
                    balance: response.data.balance,
                    credit_limit: response.data.credit_limit,
                    is_credit_exceeded: response.data.is_credit_exceeded,
                    available_credit: response.data.available_credit,
                },
            };
        }
    } catch (e) {
        // silent fail
    }
}

// Initial Load for Edit Mode or Pre-selection
onMounted(() => {
    if (props.workOrder?.vehicle) {
        selectedVehicle.value = props.workOrder.vehicle;
    } else if (props.vehicle) {
        selectedVehicle.value = props.vehicle;
    }

    if (selectedVehicle.value?.customer) {
        ensureCustomerBalance(selectedVehicle.value.customer);
    }
});

function formatPrice(amount) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount || 0);
}

// Helper function to format date for input (preserves local timezone)
function formatDateForInput(dateStr) {
    if (!dateStr) return '';

    // If already in YYYY-MM-DD format, return as-is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        return dateStr;
    }

    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '';

    // Use local date parts to avoid timezone shift
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function selectVehicle(vehicle) {
    selectedVehicle.value = vehicle;
    form.customer_id = vehicle.customer_id || (vehicle.customer ? vehicle.customer.id : '');
    form.vehicle_id = vehicle.id;
    form.dealer_id = vehicle.dealer_id || '';
    form.odometer = vehicle.odometer ?? '';
    lastVehicleOdometer.value = vehicle.odometer || 0;

    // Auto-fill contact info from customer if available
    if (vehicle.customer) {
        form.contact_name = vehicle.customer.name;
        form.contact_phone = vehicle.customer.phone;
    }

    searchQuery.value = vehicle.plate_number;
    showResults.value = false;
}

function clearSelection() {
    selectedVehicle.value = null;
    lastVehicleOdometer.value = 0;
    form.customer_id = '';
    form.vehicle_id = '';
    form.dealer_id = '';
    form.is_paid_by_dealer = false;
    form.contact_name = '';
    form.contact_phone = '';
    searchQuery.value = '';
}

function onVehicleSaved(newVehicle) {
    showVehicleModal.value = false;
    if (newVehicle) {
        selectVehicle(newVehicle);
    }
}

function handleCustomerCreated() {
    router.reload({ only: ['customers'] });
}

function submitForm() {
    const isEdit = !!props.workOrder;
    const url = isEdit ? `/app/work-orders/${props.workOrder.id}` : '/app/work-orders';

    // Set odometer on vehicle object for warning check
    if (selectedVehicle.value) {
        selectedVehicle.value.odometer = form.odometer;
    }

    const options = {
        onSuccess: () => {
            form.reset();
            emit('saved');
            emit('close');
        },
        onError: (errors) => {
            scrollToError(errors, {
                tabMap: workOrderTabMap,
                onSwitchTab: (targetTab) => {
                    activeTab.value = targetTab;
                },
            });
        },
        forceFormData: true,
    };

    if (isEdit) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(url, options);
    } else {
        form.post(url, options);
    }
}

// Reset form when modal opens for new work order
function resetForm() {
    const today = new Date().toISOString().split('T')[0];
    form.customer_id = '';
    form.vehicle_id = '';
    form.dealer_id = '';
    form.is_paid_by_dealer = false;
    form.customer_complaint = '';
    form.initial_assessment = '';
    form.odometer = '';
    form.contact_name = '';
    form.contact_phone = '';
    form.entry_date = today;
    form.expected_end_date = today;
    form.departments = [];
    form.damage_marks = [];
    form.fuel_level = 50;
    form.photos = [];
    form.notes = '';
    form.clearErrors();
    selectedVehicle.value = null;
    searchQuery.value = '';
    searchResults.value = [];
    showResults.value = false;
    activeTab.value = 'main_info';
    lastVehicleOdometer.value = 0;
}

// Watch for show prop changes - reset when opening fresh
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && !props.workOrder) {
            resetForm();
        }
    }
);

const showOdometerWarning = computed(
    () =>
        Boolean(selectedVehicle.value) && isLowerOdometer(form.odometer, lastVehicleOdometer.value)
);

function getVehicleDisplay(vehicle) {
    if (!vehicle) return '';
    const make =
        vehicle.make?.name_ar ||
        vehicle.make?.name_en ||
        vehicle.make?.name ||
        vehicle.make_other ||
        '';
    const model =
        vehicle.model?.name_ar ||
        vehicle.model?.name_en ||
        vehicle.model?.name ||
        vehicle.model_other ||
        '';
    return [make, model].filter(Boolean).join(' ');
}

function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const trimmedColor = String(colorName).trim();

    // 1. Try to find in props.colors from database (Most Accurate)
    if (props.colors && props.colors.length > 0) {
        const searchColor = trimmedColor.toLowerCase();
        const found = props.colors.find(
            (c) =>
                (c.name_ar && c.name_ar.toLowerCase() === searchColor) ||
                (c.name_en && c.name_en.toLowerCase() === searchColor) ||
                (c.hex_code && c.hex_code.toLowerCase() === searchColor)
        );
        if (found && found.hex_code) return found.hex_code;
    }

    // 2. Fallback to hardcoded map if not in database
    const colorMap = {
        أبيض: '#ffffff',
        أسود: '#111827',
        فضي: '#9ca3af',
        رمادي: '#4b5563',
        أحمر: '#dc2626',
        أزرق: '#2563eb',
        أخضر: '#16a34a',
        ذهبي: '#ca8a04',
        بني: '#78350f',
        برتقالي: '#ea580c',
        white: '#ffffff',
        black: '#111827',
        silver: '#9ca3af',
        gray: '#4b5563',
        red: '#dc2626',
        blue: '#2563eb',
        green: '#16a34a',
        gold: '#ca8a04',
        brown: '#78350f',
        orange: '#ea580c',
    };
    return colorMap[trimmedColor.toLowerCase()] || colorMap[trimmedColor] || '#9ca3af';
}
</script>
