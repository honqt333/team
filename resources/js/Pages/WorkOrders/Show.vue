<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- Back Button -->
            <Link
                :href="backUrl"
                class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            >
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ $t('common.back') }}
            </Link>

            <!-- Main Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-indigo-500/10 via-purple-500/5 to-transparent dark:from-indigo-900/30 dark:via-purple-900/10 p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                        <!-- Vehicle Info -->
                        <div class="flex items-center gap-4 flex-1">
                            <!-- Vehicle Logo -->
                            <div class="w-16 h-16 rounded-2xl bg-white dark:bg-gray-700 shadow-sm flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="workOrder.vehicle?.make?.logo_path"
                                    :src="`/storage/${workOrder.vehicle.make.logo_path}`"
                                    :alt="getName(workOrder.vehicle.make)"
                                    class="w-12 h-12 object-contain"
                                />
                                <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white" dir="ltr">
                                        {{ workOrder.vehicle?.plate_number }}
                                    </h2>
                                    <span class="text-gray-500 dark:text-gray-400">•</span>
                                    <span class="text-gray-600 dark:text-gray-300">
                                        {{ getName(workOrder.vehicle?.make) }} {{ getName(workOrder.vehicle?.model) }} {{ workOrder.vehicle?.year }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ workOrder.customer?.name }}
                                    </span>
                                    <span class="flex items-center gap-1" dir="ltr">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ workOrder.customer?.phone }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Work Order Code & Status -->
                        <div class="flex items-center gap-4">
                            <div class="text-end">
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ workOrder.code }}</p>
                                <span :class="statusBadgeClass">{{ $t(`work_orders.status.${workOrder.status}`) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <!-- Edit Button -->
                    <button
                        v-if="workOrder.status === 'draft' || workOrder.status === 'open'"
                        @click="showEditModal = true"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ $t('common.edit') }}
                    </button>

                    <!-- Print Button -->
                    <button
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        {{ $t('common.print') }}
                    </button>

                    <div class="flex-1"></div>

                    <!-- Status Change Buttons -->
                    <template v-if="workOrder.status === 'open'">
                        <button
                            @click="changeStatus('in_progress')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg hover:from-amber-600 hover:to-orange-600 shadow-sm transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $t('work_orders.actions.start_work') }}
                        </button>
                    </template>
                    <template v-if="workOrder.status === 'in_progress'">
                        <button
                            @click="changeStatus('done')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg hover:from-green-600 hover:to-emerald-600 shadow-sm transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $t('work_orders.actions.complete') }}
                        </button>
                    </template>
                    <template v-if="workOrder.status !== 'done' && workOrder.status !== 'cancelled'">
                        <button
                            @click="changeStatus('cancelled')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            {{ $t('work_orders.actions.cancel') }}
                        </button>
                    </template>
                </div>

                <!-- Info Cards -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Entry Date -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('work_orders.form.entry_date') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ formatDate(workOrder.entry_date) }}
                            </p>
                        </div>
                        <!-- Expected End -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('work_orders.form.expected_end_date') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ formatDate(workOrder.expected_end_date) }}
                            </p>
                        </div>
                        <!-- Mileage -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('work_orders.form.mileage') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                {{ workOrder.mileage ? formatNumber(workOrder.mileage) + ' km' : '-' }}
                            </p>
                        </div>
                        <!-- Contact -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('work_orders.form.contact_name') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ workOrder.contact_name || '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Customer Complaint & Initial Assessment -->
                <div 
                    v-if="workOrder.customer_complaint || workOrder.initial_assessment" 
                    class="px-6 py-4 border-b border-gray-200 dark:border-gray-700"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Customer Complaint -->
                        <div v-if="workOrder.customer_complaint" class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-700/50">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">💬</span>
                                <h4 class="font-semibold text-orange-700 dark:text-orange-300">{{ $t('quotes.show.customer_complaint') }}</h4>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ workOrder.customer_complaint }}</p>
                        </div>
                        
                        <!-- Initial Assessment -->
                        <div v-if="workOrder.initial_assessment" class="bg-teal-50 dark:bg-teal-900/20 rounded-xl p-4 border border-teal-200 dark:border-teal-700/50">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📋</span>
                                <h4 class="font-semibold text-teal-700 dark:text-teal-300">{{ $t('quotes.show.initial_assessment') }}</h4>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ workOrder.initial_assessment }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="px-6 pt-4">
                    <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700 overflow-x-auto pb-px">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap -mb-px',
                                activeTab === tab.key
                                    ? 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-800 text-indigo-600 dark:text-indigo-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                            ]"
                        >
                            {{ tab.icon }} {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Services Tab -->
                    <div v-show="activeTab === 'services'" class="space-y-4">
                        <!-- Add Department Dropdown -->
                        <div class="flex justify-end">
                            <div class="relative">
                                <button
                                    @click="showDeptMenu = !showDeptMenu"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    {{ $t('quotes.show.add_department') }}
                                </button>
                                <!-- Department Menu -->
                                <div
                                    v-if="showDeptMenu"
                                    class="absolute z-50 start-0 sm:start-auto sm:end-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 max-h-60 overflow-y-auto"
                                >
                                    <button
                                        v-for="dept in availableDepartments"
                                        :key="dept.id"
                                        @click="addDepartment(dept.id)"
                                        class="w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        {{ getName(dept) }}
                                    </button>
                                    <p v-if="availableDepartments.length === 0" class="px-4 py-2 text-sm text-gray-400">
                                        {{ $t('quotes.show.all_departments_added') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Departments Accordion -->
                        <div v-if="displayDepartments.length > 0" class="space-y-3">
                            <div
                                v-for="dept in displayDepartments"
                                :key="dept.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden"
                            >
                                <!-- Department Header -->
                                <div class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-900/50 dark:to-transparent">
                                    <button
                                        @click="toggleDepartment(dept.id)"
                                        class="flex items-center gap-3 flex-1"
                                    >
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                            <span class="text-indigo-600 dark:text-indigo-400">🔧</span>
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ getName(dept) }}</span>
                                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                            {{ getDepartmentItems(dept.id).length }} {{ $t('quotes.show.services_count') }}
                                        </span>
                                    </button>
                                    
                                    <div class="flex items-center gap-2">
                                        <!-- Remove Department Button -->
                                        <button
                                            v-if="getDepartmentItems(dept.id).length === 0"
                                            @click.stop="removeDepartment(dept.id)"
                                            class="w-7 h-7 rounded-lg hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400 text-gray-400 flex items-center justify-center transition-colors"
                                            :title="$t('common.delete')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        <!-- Expand/Collapse Arrow -->
                                        <button
                                            @click="toggleDepartment(dept.id)"
                                            class="w-7 h-7 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center transition-colors"
                                        >
                                            <svg
                                                class="w-5 h-5 text-gray-400 transition-transform"
                                                :class="{ 'rotate-180': expandedDepartments.includes(dept.id) }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Department Content -->
                                <div v-show="expandedDepartments.includes(dept.id)" class="p-4 space-y-2 bg-gray-50/50 dark:bg-gray-900/30">
                                    <!-- Services List -->
                                    <div class="flex flex-col gap-3">
                                        <div
                                            v-for="(item, index) in getDepartmentItems(dept.id)"
                                            :key="item.id"
                                            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-4 transition-all hover:shadow-md hover:border-gray-200 dark:hover:border-gray-600 group relative"
                                        >
                                            <div class="flex items-center justify-between">
                                                <!-- Right Side: Status + Title + Meta -->
                                                <div class="flex items-start gap-4 flex-1">
                                                    <!-- Status Icon (Large) -->
                                                    <div 
                                                        class="mt-1 flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center transition-colors"
                                                        :class="{
                                                            'bg-gray-50 text-gray-400 dark:bg-gray-700 dark:text-gray-500': item.status === 'pending',
                                                            'bg-blue-50 text-blue-500 dark:bg-blue-900/20 dark:text-blue-400': item.status === 'in_progress',
                                                            'bg-green-50 text-green-500 dark:bg-green-900/20 dark:text-green-400': item.status === 'completed',
                                                            'bg-yellow-50 text-yellow-500 dark:bg-yellow-900/20 dark:text-yellow-400': item.status === 'on_hold',
                                                            'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-400': item.status === 'cancelled'
                                                        }"
                                                    >
                                                        <svg v-if="item.status === 'completed'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        <svg v-else-if="item.status === 'in_progress'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        <svg v-else-if="item.status === 'cancelled'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    </div>

                                                    <div class="flex-1 min-w-0">
                                                        <!-- Title Row - CLICKABLE -->
                                                        <div class="flex items-baseline gap-2 mb-1">
                                                            <span class="text-gray-400 font-medium font-mono text-sm leading-none">{{ index + 1 }}.</span>
                                                            <button 
                                                                @click.stop="openEditServiceModal(item)"
                                                                type="button"
                                                                class="font-bold text-gray-900 dark:text-white text-lg hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-start leading-tight"
                                                            >
                                                                {{ item.title || getName(item.service) }}
                                                            </button>
                                                        </div>

                                                        <!-- Meta Row: Price | Technician -->
                                                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                            <!-- Prices Group -->
                                                            <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded-md">
                                                                <!-- Labor -->
                                                                <div class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium" :title="$t('work_orders.item.service_cost')">
                                                                    <span class="text-indigo-500">🔧</span>
                                                                    <span>{{ formatPrice(item.line_total || item.total) }}</span>
                                                                </div>
                                                                <!-- Parts (if any) -->
                                                                <div v-if="item.parts_total > 0" class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium border-s border-gray-200 dark:border-gray-600 ps-3" :title="$t('work_orders.item.parts_cost')">
                                                                    <span class="text-amber-500">🔩</span>
                                                                    <span>{{ formatPrice(item.parts_total) }}</span>
                                                                </div>
                                                            </div>

                                                            <div v-if="item.technicians && item.technicians.length" class="flex items-center gap-1.5">
                                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                                <div class="flex items-center gap-1">
                                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                                    <span>{{ item.technicians[0].name }}</span>
                                                                </div>
                                                            </div>
                                                            <div v-else class="flex items-center gap-1.5 text-amber-500">
                                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                                <span>{{ $t('work_orders.item.assign_technician') }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Left Side: Actions + Date -->
                                                <div class="flex items-center gap-4 pl-2">
                                                    <!-- Date Badge (Red Style) -->
                                                    <span class="hidden sm:inline-flex bg-red-500 text-white text-xs px-2.5 py-1 rounded-md font-bold shadow-sm">
                                                        {{ formatDate(item.created_at) }}
                                                    </span>

                                                    <div class="flex items-center gap-1 border-s border-gray-100 dark:border-gray-700 ps-3">
                                                        <button 
                                                            @click.stop="openEditServiceModal(item)"
                                                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                                            :title="$t('common.edit')"
                                                        >
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                        </button>
                                                        <button 
                                                            @click.stop="deleteServiceItem(item)"
                                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                                            :title="$t('common.delete')"
                                                        >
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty State -->
                                    <p
                                        v-if="getDepartmentItems(dept.id).length === 0"
                                        class="text-center text-gray-400 dark:text-gray-500 py-4 text-sm"
                                    >
                                        {{ $t('quotes.show.no_services') }}
                                    </p>

                                    <!-- Add Service Button -->
                                    <button
                                        @click="openAddServiceModal(dept.id)"
                                        class="w-full flex items-center justify-center gap-2 py-2 text-sm text-indigo-600 dark:text-indigo-400 border border-dashed border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        {{ $t('quotes.show.add_service') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">🔧</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.show.no_services') }}</p>
                        </div>
                    </div>

                    <!-- Spare Parts Tab -->
                    <div v-show="activeTab === 'parts'" class="space-y-4">
                        <!-- Add Part Button -->
                        <div class="flex justify-end">
                            <button
                                @click="showAddPartModal = true"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('inventory.parts.add_to_wo') }}
                            </button>
                        </div>

                        <!-- Parts List -->
                        <div v-if="allWorkOrderParts.length > 0" class="space-y-2">
                            <div
                                v-for="part in allWorkOrderParts"
                                :key="part.id"
                                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-center gap-4">
                                    <!-- Part Icon -->
                                    <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <span class="text-amber-600 dark:text-amber-400">🔩</span>
                                    </div>
                                    
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ part.part?.name_ar || part.part?.name_en || part.name }}
                                        </p>
                                        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                            <span v-if="part.part?.sku" class="font-mono">{{ part.part.sku }}</span>
                                            <span>{{ part.qty }} × {{ formatPrice(part.unit_price) }}</span>
                                            <span 
                                                v-if="part.status" 
                                                :class="{
                                                    'text-green-600': part.status === 'issued',
                                                    'text-red-600': part.status === 'reversed',
                                                    'text-gray-500': part.status === 'pending'
                                                }"
                                            >
                                                {{ $t('inventory.parts.statuses.' + part.status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ formatPrice(part.qty * part.unit_price) }}
                                    </span>
                                    <button 
                                        v-if="part.status !== 'reversed'"
                                        @click="deleteWorkOrderPart(part)"
                                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">🔩</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.show.no_parts') }}</p>
                            <button
                                @click="showAddPartModal = true"
                                class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('inventory.parts.add_to_wo') }}
                            </button>
                        </div>
                    </div>

                    <!-- Technicians Tab -->
                    <div v-show="activeTab === 'technicians'" class="text-center py-12">
                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">👷</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>

                    <!-- Payments Tab -->
                    <div v-show="activeTab === 'payments'" class="text-center py-12">
                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">💰</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>

                    <!-- Condition Report Tab -->
                    <div v-show="activeTab === 'condition'" class="max-w-4xl mx-auto">
                        <VehicleConditionReport
                            v-model:damageMarks="workOrder.damage_marks"
                            class="pointer-events-none"
                        />
                    </div>

                    <!-- Photos Tab -->
                    <div v-show="activeTab === 'photos'">
                        <div v-if="workOrder.photos?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="photo in workOrder.photos" :key="photo.id" class="relative group aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <img :src="`/storage/${photo.path}`" class="w-full h-full object-cover" />
                                <div class="absolute inset-x-0 bottom-0 bg-black/60 p-2 text-white text-xs backdrop-blur-sm">
                                    <p class="font-bold uppercase">{{ $t(`work_orders.photos.types.${photo.type}`) }}</p>
                                    <p v-if="photo.caption" class="truncate">{{ photo.caption }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">📸</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.photos.no_photos') }}</p>
                        </div>
                    </div>

                    <!-- Activities Tab -->
                    <div v-show="activeTab === 'activities'" class="text-center py-12">
                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">📋</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>

                    <!-- Attachments Tab -->
                    <div v-show="activeTab === 'attachments'" class="text-center py-12">
                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">📎</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <WorkOrderFormModal
            :show="showEditModal"
            :work-order="workOrder"
            :customers="customers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="showEditModal = false"
            @saved="handleSaved"
        />

        <!-- Item Edit Modal (comprehensive with tabs) -->
        <WorkOrderItemModal
            v-if="showItemModal"
            :show="showItemModal"
            :work-order="workOrder"
            :item="selectedItem"
            :technicians="technicians"
            @close="closeItemModal"
            @saved="handleItemSaved"
        />

        <!-- Service Add Modal (simple add) -->
        <WorkOrderServiceModal
            v-if="showServiceModal && !selectedItem"
            :show="showServiceModal"
            :work-order="workOrder"
            :item="null"
            :department-id="selectedDepartmentId"
            :services="departmentServices"
            @close="closeServiceModal"
            @saved="handleServiceSaved"
        />

        <!-- Add Part Modal -->
        <AddPartModal
            v-model="showAddPartModal"
            :work-order-id="workOrder.id"
            :warehouses="warehouses"
            :parts="inventoryParts"
            @saved="handlePartSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import VehicleConditionReport from '@/Components/WorkOrders/VehicleConditionReport.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import WorkOrderServiceModal from '@/Components/WorkOrders/WorkOrderServiceModal.vue';
import WorkOrderItemModal from '@/Components/WorkOrders/WorkOrderItemModal.vue';
import AddPartModal from '@/Components/Inventory/AddPartModal.vue';

const props = defineProps({
    workOrder: Object,
    itemsByDepartment: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    technicians: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    inventoryParts: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { getName } = useLocalized();
const { formatNumber, formatCurrency } = useNumberFormat();
const { success } = useToast();
const { confirm } = useConfirm();

const showEditModal = ref(false);
const activeTab = ref('services');
const showDeptMenu = ref(false);
const expandedDepartments = ref([]);
const showAddPartModal = ref(false);

// All parts from all work order items
const allWorkOrderParts = computed(() => {
    const parts = [];
    props.workOrder.items?.forEach(item => {
        item.parts?.forEach(part => {
            parts.push({ ...part, workOrderItemId: item.id });
        });
    });
    return parts;
});

// Handle part saved
function handlePartSaved() {
    showAddPartModal.value = false;
    success(t('common.saved_success'));
    router.reload();
}

// Delete work order part
async function deleteWorkOrderPart(part) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.parts.destroy', part.id), {
            onSuccess: () => success(t('common.deleted_success')),
        });
    }
}

// Get departments that have items or are linked to work order
const displayDepartments = computed(() => {
    const deptIds = new Set();
    
    // Add departments with items
    Object.keys(props.itemsByDepartment).forEach(id => {
        if (id !== '0') deptIds.add(parseInt(id));
    });
    
    // Add work order's linked departments
    props.workOrder.departments?.forEach(dept => deptIds.add(dept.id));
    
    return props.departments.filter(d => deptIds.has(d.id));
});

// Departments that can still be added
const availableDepartments = computed(() => {
    const usedIds = displayDepartments.value.map(d => d.id);
    return props.departments.filter(d => !usedIds.includes(d.id));
});

// Get items for a specific department
function getDepartmentItems(deptId) {
    return props.itemsByDepartment[deptId] || [];
}

// Toggle department expansion
function toggleDepartment(deptId) {
    const idx = expandedDepartments.value.indexOf(deptId);
    if (idx > -1) {
        expandedDepartments.value.splice(idx, 1);
    } else {
        expandedDepartments.value.push(deptId);
    }
}

// Add department to work order
function addDepartment(deptId) {
    showDeptMenu.value = false;
    router.post(route('work-orders.departments.store', props.workOrder.id), {
        department_id: deptId,
    }, {
        onSuccess: () => {
            success(t('common.saved_success'));
            if (!expandedDepartments.value.includes(deptId)) {
                expandedDepartments.value.push(deptId);
            }
        },
    });
}

// Service modal state
const showServiceModal = ref(false);
const selectedItem = ref(null);
const selectedDepartmentId = ref(null);

// Services filtered by department
const departmentServices = computed(() => {
    if (!selectedDepartmentId.value) return [];
    return props.services.filter(s => s.department_id === selectedDepartmentId.value);
});

// Open add service modal
function openAddServiceModal(deptId) {
    selectedDepartmentId.value = deptId;
    selectedItem.value = null;
    showServiceModal.value = true;
}

// Open edit service modal (advanced modal with tabs)
function openEditServiceModal(item) {
    selectedItem.value = item;
    showItemModal.value = true;
}

// Close service modal
function closeServiceModal() {
    showServiceModal.value = false;
    selectedItem.value = null;
    selectedDepartmentId.value = null;
}

// Handle service saved
function handleServiceSaved() {
    closeServiceModal();
    success(t('common.saved_success'));
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Item Modal state
const showItemModal = ref(false);

// Close item modal
function closeItemModal() {
    showItemModal.value = false;
    selectedItem.value = null;
}

// Handle item saved
function handleItemSaved() {
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Delete service item
async function deleteServiceItem(item) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.items.destroy', { work_order: props.workOrder.id, item: item.id }), {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}

const tabs = [
    { key: 'services', label: t('work_orders.show.tabs.services'), icon: '🔧' },
    { key: 'parts', label: t('work_orders.show.tabs.parts'), icon: '🔩' },
    { key: 'technicians', label: t('work_orders.show.tabs.technicians'), icon: '👷' },
    { key: 'payments', label: t('work_orders.show.tabs.payments'), icon: '💰' },
    { key: 'condition', label: t('work_orders.show.tabs.condition'), icon: '🚗' },
    { key: 'photos', label: t('work_orders.show.tabs.photos'), icon: '📸' },
    { key: 'activities', label: t('work_orders.show.tabs.activities'), icon: '📋' },
    { key: 'attachments', label: t('work_orders.show.tabs.attachments'), icon: '📎' },
];

// Compute back URL based on work order status
const backUrl = computed(() => {
    const status = props.workOrder.status;
    // Open statuses go back to open list, closed statuses go to closed list
    if (['done', 'cancelled'].includes(status)) {
        return route('work-orders.index', { status: 'closed' });
    }
    return route('work-orders.index', { status: 'open' });
});

const statusBadgeClass = computed(() => {
    const baseClass = 'inline-block px-2 py-1 text-xs font-medium rounded-full mt-1';
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        done: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    };
    return `${baseClass} ${classes[props.workOrder.status] || classes.draft}`;
});

function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

function formatDate(date) {
    if (!date) return '-';
    
    // If date is in YYYY-MM-DD format, parse manually to avoid timezone issues
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(date)) {
        const parts = date.split('T')[0].split('-');
        const day = parts[2];
        const month = parts[1];
        const year = parts[0];
        return `${day}/${month}/${year}`;
    }
    
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

function handleSaved() {
    showEditModal.value = false;
    success(t('common.saved_success'));
    router.reload();
}

// Remove department
async function removeDepartment(deptId) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.departments.destroy', { work_order: props.workOrder.id, department: deptId }), {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}

async function changeStatus(newStatus) {
    const statusLabels = {
        in_progress: t('work_orders.actions.start_work'),
        done: t('work_orders.actions.complete'),
        cancelled: t('work_orders.actions.cancel'),
    };

    const confirmed = await confirm({
        title: statusLabels[newStatus],
        message: t('work_orders.messages.confirm_status_change'),
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
        type: newStatus === 'cancelled' ? 'danger' : 'success',
    });

    if (confirmed) {
        router.put(route('work-orders.update', props.workOrder.id), {
            status: newStatus,
        }, {
            onSuccess: () => {
                success(t('common.saved_success'));
            },
        });
    }
}
</script>
