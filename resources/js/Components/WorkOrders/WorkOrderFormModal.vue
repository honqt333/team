<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                {{ workOrder ? $t('work_orders.edit') : $t('work_orders.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Tabs Navigation -->
            <div class="flex flex-wrap gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                <button v-for="tab in tabs" :key="tab.key" type="button" @click="activeTab = tab.key" :class="[
                    'flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 px-2 sm:px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all flex-1 sm:flex-none',
                    activeTab === tab.key
                        ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                ]">
                    <component :is="tab.icon" class="w-4 h-4 flex-shrink-0" />
                    <span class="text-[10px] sm:text-sm text-center leading-tight">{{ tab.label }}</span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="min-h-[350px]">
                <!-- Tab 1: Main Info -->
                <div v-show="activeTab === 'main_info'" class="space-y-4">
                    <!-- Search Section (Hide if pre-selected vehicle exists and not editing) -->
                    <div v-if="!vehicle && !workOrder"
                        class="relative bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('quotes.form_tabs.search_placeholder') }}
                            </label>
                            <button type="button" @click="showVehicleModal = true"
                                class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('quotes.form_tabs.add_vehicle') }}
                            </button>
                        </div>

                        <!-- Search Input -->
                        <div class="relative">
                            <input v-model="searchQuery" type="text" @input="debouncedSearch"
                                @focus="showResults = true" :placeholder="$t('quotes.form_tabs.search_placeholder')"
                                class="w-full px-4 py-3 ps-10 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            <svg class="w-5 h-5 absolute start-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>

                            <!-- Loading -->
                            <div v-if="searching" class="absolute end-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Search Results Dropdown -->
                        <div v-if="showResults && searchResults.length > 0"
                            class="absolute z-50 mt-2 start-0 end-0 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto">
                            <div v-for="vehicle in searchResults" :key="vehicle.id" :class="[
                                'w-full px-4 py-3 text-start flex items-center gap-3 border-b border-gray-100 dark:border-gray-700 last:border-0',
                                vehicle.has_open_work_order
                                    ? 'bg-red-50 dark:bg-red-900/20 cursor-not-allowed opacity-75'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer'
                            ]" @click="!vehicle.has_open_work_order && selectVehicle(vehicle)">
                                <div :class="[
                                    'w-10 h-10 rounded-lg flex items-center justify-center',
                                    vehicle.has_open_work_order
                                        ? 'bg-red-200 dark:bg-red-800'
                                        : 'bg-gray-200 dark:bg-gray-600'
                                ]">
                                    <svg class="w-5 h-5"
                                        :class="vehicle.has_open_work_order ? 'text-red-600' : 'text-gray-500'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="vehicle.has_open_work_order" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white" dir="ltr">{{
                                        toEnglish(vehicle.plate_number) }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ toEnglish(vehicle.customer?.name) }} -
                                        {{
                                            toEnglish(vehicle.customer?.phone) }}</p>
                                    <!-- Open Work Order Warning -->
                                    <div v-if="vehicle.has_open_work_order" class="mt-1 flex items-center gap-2">
                                        <span class="text-xs text-red-600 dark:text-red-400 font-medium">
                                            {{ $t('work_orders.has_open_work_order') }} ({{
                                                vehicle.open_work_order?.code }})
                                        </span>
                                        <a :href="route('work-orders.show', vehicle.open_work_order?.id)" @click.stop
                                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                            {{ $t('work_orders.go_to_work_order') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="showResults && searchQuery.length >= 2 && searchResults.length === 0 && !searching"
                            class="text-center text-gray-500 dark:text-gray-400 py-4 text-sm">
                            {{ $t('quotes.form_tabs.no_results') }}
                        </p>
                    </div>

                    <!-- Selected Vehicle/Customer Info -->
                    <div v-if="selectedVehicle"
                        class="relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                        <!-- Header / Plate Section -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white dark:bg-gray-800 border-2 border-gray-700 dark:border-gray-500 rounded-md px-3 py-1 flex flex-col items-center justify-center min-w-[100px] shadow-sm">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 leading-tight" dir="ltr">{{ toEnglish(selectedVehicle.plate_number) }}</span>
                                    <div class="w-full h-px bg-gray-200 dark:bg-gray-600 my-0.5"></div>
                                    <span class="text-[8px] font-bold text-gray-400 tracking-widest uppercase">KSA</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">{{ $t('common.selected') }}</span>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ getVehicleDisplay(selectedVehicle) }}</h4>
                                </div>
                            </div>
                            <button type="button" @click="clearSelection"
                                class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Info Grid -->
                        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Column 1: Technical Details -->
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('vehicles.form.vin') }}</span>
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 font-mono">{{ selectedVehicle.vin || '—' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('vehicles.form.year') }}</span>
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ toEnglish(selectedVehicle.year) || '—' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.828 2.828a2 2 0 010 2.828l-8.486 8.486" /></svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('vehicles.form.color') }}</span>
                                            <div class="flex items-center gap-1.5">
                                                <span v-if="selectedVehicle.color" class="w-2.5 h-2.5 rounded-full border border-gray-300 dark:border-gray-600" :style="{ backgroundColor: getColorHex(selectedVehicle.color) }"></span>
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ selectedVehicle.color || '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Customer Details -->
                            <div class="space-y-3 pt-4 sm:pt-0 sm:ps-4 sm:border-s border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm ring-2 ring-white dark:ring-gray-800">
                                        {{ selectedVehicle.customer?.name?.[0]?.toUpperCase() || 'C' }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('quotes.form_tabs.customer_name') }}</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ selectedVehicle.customer?.name }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('quotes.form_tabs.phone') }}</span>
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 font-mono" dir="ltr">{{ toEnglish(selectedVehicle.customer?.phone) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Odometer (standalone like Quote form) -->
                    <div v-if="selectedVehicle"
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('work_orders.form.odometer') }}
                            </label>
                            <div class="flex items-center gap-3">
                                <label v-if="selectedVehicle && lastVehicleOdometer > 0" class="flex items-center gap-2 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" v-model="form.allow_lower_odometer" class="sr-only peer" />
                                        <div class="w-8 h-4 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:bg-indigo-500 transition-all after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-4 rtl:peer-checked:after:-translate-x-4"></div>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-500 group-hover:text-indigo-600 transition-colors uppercase tracking-wider">
                                        {{ $t('quotes.allow_lower_odometer') }}
                                    </span>
                                </label>
                                <button type="button" @click="showMileageModal = true"
                                    class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $t('vehicles.mileage.history') }}
                                </button>
                            </div>
                        </div>
                        <div class="relative">
                            <input v-model="form.odometer" type="text" inputmode="numeric"
                                @input="form.odometer = toEnglish($event.target.value).replace(/[^0-9]/g, '')"
                                :placeholder="$t('work_orders.form.odometer_placeholder')"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            <div class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 text-sm">km</span>
                            </div>
                        </div>
                        <div v-if="showOdometerWarning"
                            class="mt-2 flex items-start gap-2 text-[10px] font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg border border-red-100 dark:border-red-800/50">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ $t('vehicles.mileage.lower_warning', { last: toEnglish(lastVehicleOdometer) }) }}</span>
                        </div>
                        <div v-else-if="selectedVehicle && lastVehicleOdometer > 0"
                            class="mt-1.5 px-1 flex items-center gap-1.5 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $t('vehicles.mileage.last_recorded') }}: {{ toEnglish(lastVehicleOdometer) }} km
                        </div>
                    </div>

                    <!-- Dates Grid -->
                    <div v-if="selectedVehicle" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Entry Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('work_orders.form.entry_date') }}
                            </label>
                            <input v-model="form.entry_date" type="date"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" />
                        </div>

                        <!-- Expected End Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('work_orders.form.expected_end_date') }}
                            </label>
                            <input v-model="form.expected_end_date" type="date" :min="form.entry_date"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Customer Complaint -->
                <div v-show="activeTab === 'customer_complaint'" class="space-y-4">
                    <div
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('quotes.form_tabs.customer_complaint') }}
                        </label>
                        <textarea v-model="form.customer_complaint" rows="10"
                            :placeholder="$t('quotes.form_tabs.customer_complaint_placeholder')"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                    </div>
                </div>

                <!-- Tab 3: Initial Assessment -->
                <div v-show="activeTab === 'initial_assessment'" class="space-y-4">
                    <div
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('quotes.form_tabs.initial_assessment') }}
                        </label>
                        <textarea v-model="form.initial_assessment" rows="10"
                            :placeholder="$t('quotes.form_tabs.initial_assessment_placeholder')"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                    </div>
                </div>

                <!-- Tab 4: Vehicle Condition Report -->
                <div v-show="activeTab === 'condition_report'" class="space-y-4">
                    <VehicleConditionReport v-model:damageMarks="form.damage_marks"
                        v-model:fuelLevel="form.fuel_level" />
                </div>

                <!-- Tab 5: Vehicle Photos -->
                <div v-show="activeTab === 'photos'" class="space-y-4">
                    <VehiclePhotoUploader v-model:photos="form.photos" />
                </div>

                <!-- Tab 6: Departments -->
                <div v-show="activeTab === 'departments'" class="space-y-4">
                    <div
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            {{ $t('quotes.form_tabs.select_departments') }}
                        </label>
                        <div class="space-y-2">
                            <label v-for="(dept, index) in departments" :key="dept.id"
                                class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer transition-colors">
                                <input type="checkbox" v-model="form.departments" :value="dept.id"
                                    class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="text-gray-900 dark:text-white">{{ toEnglish(index + 1) }}. {{ toEnglish(getName(dept)) }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button type="button" @click="submitForm" :disabled="form.processing || !selectedVehicle || (showOdometerWarning && !form.allow_lower_odometer)"
                class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>

    <!-- Vehicle Form Modal -->
    <VehicleFormModal v-if="showVehicleModal" :show="showVehicleModal" :customers="customers" :makes="makes"
        :colors="colors" :modelsByMake="modelsByMake" @close="showVehicleModal = false" @saved="onVehicleSaved"
        @customer-created="handleCustomerCreated" />

    <!-- Vehicle Mileage Modal -->
    <VehicleMileageModal v-if="showMileageModal" :show="showMileageModal" :vehicle="selectedVehicle"
        @close="showMileageModal = false" />
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';
import VehicleMileageModal from '@/Components/Vehicles/VehicleMileageModal.vue';
import VehicleConditionReport from '@/Components/WorkOrders/VehicleConditionReport.vue';
import VehiclePhotoUploader from '@/Components/WorkOrders/VehiclePhotoUploader.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    vehicle: Object, // Pre-selected vehicle for creation
    customers: Array,
    departments: Array,
    makes: Array,
    colors: Array,
    modelsByMake: Object,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();
const { toEnglish } = useNumberFormat();

// Tab Icons as inline SVG components
const IconInfo = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
};
const IconComplaint = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>`
};
const IconAssessment = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>`
};
const IconCondition = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>`
};
const IconPhotos = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>`
};
const IconDepartments = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>`
};

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
        return allTabs.filter(tab => !['departments', 'condition_report', 'photos'].includes(tab.key));
    }
    return allTabs;
});

const activeTab = ref('main_info');

// Search
const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const showResults = ref(false);
const selectedVehicle = ref(null);

// Vehicle Modal
const showVehicleModal = ref(false);
const showMileageModal = ref(false);
const lastVehicleOdometer = ref(0);

// Form
const form = useForm({
    customer_id: props.workOrder?.customer_id || props.vehicle?.customer_id || '',
    vehicle_id: props.workOrder?.vehicle_id || props.vehicle?.id || '',
    customer_complaint: props.workOrder?.customer_complaint || '',
    initial_assessment: props.workOrder?.initial_assessment || '',
    odometer: props.workOrder?.odometer || '',
    allow_lower_odometer: false,
    contact_name: props.workOrder?.contact_name || '',
    contact_phone: props.workOrder?.contact_phone || '',
    entry_date: props.workOrder?.entry_date || new Date().toISOString().split('T')[0],
    expected_end_date: props.workOrder?.expected_end_date || new Date().toISOString().split('T')[0],
    departments: props.workOrder?.departments?.map(d => d.id) || [],
    damage_marks: props.workOrder?.damage_marks || [],
    fuel_level: props.workOrder?.fuel_level ?? 50,
    photos: [],
    notes: props.workOrder?.notes || '',
});

// Watch for modal opening to reinitialize form values (for edit mode)
watch(() => props.show, (newVal) => {
    if (newVal && props.workOrder) {
        form.customer_id = props.workOrder.customer_id || '';
        form.vehicle_id = props.workOrder.vehicle_id || '';
        form.customer_complaint = props.workOrder.customer_complaint || '';
        form.initial_assessment = props.workOrder.initial_assessment || '';
        form.odometer = props.workOrder.odometer || '';
        form.allow_lower_odometer = false;
        form.contact_name = props.workOrder.contact_name || '';
        form.contact_phone = props.workOrder.contact_phone || '';
        form.entry_date = formatDateForInput(props.workOrder.entry_date);
        form.expected_end_date = formatDateForInput(props.workOrder.expected_end_date);
        form.departments = props.workOrder.departments?.map(d => d.id) || [];
        form.damage_marks = props.workOrder.damage_marks || [];
        form.fuel_level = props.workOrder.fuel_level ?? 50;
        form.photos = props.workOrder.photos?.map(photo => ({
            id: photo.id,
            url: photo.url,
            type: photo.type,
            caption: photo.caption
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
}, { immediate: true });

// Watch for vehicle prop (pre-selection)
watch(() => props.vehicle, (val) => {
    if (val && !props.workOrder) { // Only if not editing an existing order
        selectedVehicle.value = val;
        form.vehicle_id = val.id;
        form.customer_id = val.customer_id;
        form.odometer = val.odometer || '';
        lastVehicleOdometer.value = val.odometer || 0;
    }
}, { immediate: true });

// Initial Load for Edit Mode or Pre-selection
onMounted(() => {
    if (props.workOrder?.vehicle) {
        selectedVehicle.value = props.workOrder.vehicle;
    } else if (props.vehicle) {
        selectedVehicle.value = props.vehicle;
    }
});

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

// Debounce search
let debounceTimer = null;
function debouncedSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        searchQuery.value = toEnglish(searchQuery.value);
        performSearch();
    }, 300);
}

async function performSearch() {
    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    searching.value = true;
    try {
        const response = await axios.get('/app/api/vehicles/search', {
            params: { q: searchQuery.value }
        });
        searchResults.value = response.data;
        showResults.value = true;
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        searching.value = false;
    }
}

function selectVehicle(vehicle) {
    selectedVehicle.value = vehicle;
    form.customer_id = vehicle.customer_id || (vehicle.customer ? vehicle.customer.id : '');
    form.vehicle_id = vehicle.id;
    form.odometer = vehicle.odometer || '';
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
    const url = isEdit
        ? `/app/work-orders/${props.workOrder.id}`
        : '/app/work-orders';

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
watch(() => props.show, (isOpen) => {
    if (isOpen && !props.workOrder) {
        resetForm();
    }
});

const showOdometerWarning = computed(() => {
    if (!selectedVehicle.value || !form.odometer || lastVehicleOdometer.value <= 0) return false;
    return Number(form.odometer) < lastVehicleOdometer.value;
});

function getVehicleDisplay(vehicle) {
    if (!vehicle) return '';
    const make = vehicle.make?.name_ar || vehicle.make?.name_en || vehicle.make?.name || vehicle.make_other || '';
    const model = vehicle.model?.name_ar || vehicle.model?.name_en || vehicle.model?.name || vehicle.model_other || '';
    return [make, model].filter(Boolean).join(' ');
}

function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const trimmedColor = String(colorName).trim();
    
    // 1. Try to find in props.colors from database (Most Accurate)
    if (props.colors && props.colors.length > 0) {
        const searchColor = trimmedColor.toLowerCase();
        const found = props.colors.find(c => 
            (c.name_ar && c.name_ar.toLowerCase() === searchColor) || 
            (c.name_en && c.name_en.toLowerCase() === searchColor) || 
            (c.hex_code && c.hex_code.toLowerCase() === searchColor)
        );
        if (found && found.hex_code) return found.hex_code;
    }

    // 2. Fallback to hardcoded map if not in database
    const colorMap = {
        'أبيض': '#ffffff', 'أسود': '#111827', 'فضي': '#9ca3af', 'رمادي': '#4b5563',
        'أحمر': '#dc2626', 'أزرق': '#2563eb', 'أخضر': '#16a34a', 'ذهبي': '#ca8a04',
        'بني': '#78350f', 'برتقالي': '#ea580c',
        'white': '#ffffff', 'black': '#111827', 'silver': '#9ca3af', 'gray': '#4b5563',
        'red': '#dc2626', 'blue': '#2563eb', 'green': '#16a34a', 'gold': '#ca8a04',
        'brown': '#78350f', 'orange': '#ea580c',
    };
    return colorMap[trimmedColor.toLowerCase()] || colorMap[trimmedColor] || '#9ca3af';
}
</script>
