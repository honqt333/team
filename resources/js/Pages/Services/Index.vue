<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('services_management.title')"
                :subtitle="$t('services_management.subtitle')"
                :totalCount="headerConfig.count"
                :countLabel="headerConfig.countLabel"
                :gradientFrom="headerConfig.gradientFrom"
                :gradientTo="headerConfig.gradientTo"
                :glowFrom="headerConfig.glowFrom"
                :badgeBg="headerConfig.badgeBg"
                :badgeText="headerConfig.badgeText"
                :badgeBorder="headerConfig.badgeBorder"
                :badgeDot="headerConfig.badgeDot"
            >
                <template #icon>
                    <!-- Dynamic Icon based on Active Tab -->
                    <svg v-if="activeTab === 'services'" :class="['w-8 h-8', headerConfig.badgeText]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg v-else-if="activeTab === 'packages'" :class="['w-8 h-8', headerConfig.badgeText]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <svg v-else-if="activeTab === 'inspections'" :class="['w-8 h-8', headerConfig.badgeText]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <template v-if="activeTab === 'services'">
                            <!-- Add Department Button -->
                            <button v-if="can('services.departments.manage') || isAnyAdmin()" @click="openCreateDepartmentModal"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-800 border border-orange-200 dark:border-orange-700 text-orange-600 dark:text-orange-400 rounded-xl font-bold shadow-sm hover:shadow-md hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ $t('departments.add') }}</span>
                            </button>

                            <!-- Add Service Button -->
                            <button v-if="can('services.create') || isAnyAdmin()" @click="openCreateModal"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-black shadow-lg shadow-teal-500/25 hover:shadow-teal-500/40 hover:-translate-y-0.5 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ $t('services_management.add') }}</span>
                            </button>
                        </template>

                        <template v-else-if="activeTab === 'packages'">
                            <!-- Add Package Button -->
                            <button v-if="can('services.create') || isAnyAdmin()" @click="openCreatePackageModal"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-black shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ $t('packages.add') }}</span>
                            </button>
                        </template>

                        <template v-else-if="activeTab === 'inspections'">
                            <!-- Add Inspection Button -->
                            <button v-if="can('services.create') || isAnyAdmin()" @click="openCategoryModal()"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-black shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>{{ $t('services_management.add_inspection_category') }}</span>
                            </button>
                        </template>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="relative flex-1 group">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-teal-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('services_management.search')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all outline-none shadow-sm"
                            />
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Filter Tabs - Modern Compact Design -->
            <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-md rounded-2xl p-2 shadow-sm border border-gray-100 dark:border-gray-700/50 flex flex-wrap gap-2">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.key" 
                    @click="activeTab = tab.key" 
                    :class="[
                        'relative flex items-center gap-3 px-6 py-3 rounded-xl transition-all duration-300 group',
                        activeTab === tab.key
                            ? 'text-white shadow-lg scale-[1.02]'
                            : 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400'
                    ]"
                    :style="activeTab === tab.key ? `background: linear-gradient(135deg, ${tab.gradientFrom}, ${tab.gradientTo})` : ''"
                >
                    <!-- Icon Container -->
                    <div :class="[
                        'w-8 h-8 rounded-lg flex items-center justify-center transition-colors',
                        activeTab === tab.key ? 'bg-white/20' : tab.bgColor
                    ]">
                        <!-- Services Icon -->
                        <svg v-if="tab.key === 'services'" :class="['w-4 h-4', activeTab === tab.key ? 'text-white' : tab.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <!-- Packages Icon -->
                        <svg v-else-if="tab.key === 'packages'" :class="['w-4 h-4', activeTab === tab.key ? 'text-white' : tab.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <!-- Inspections Icon -->
                        <svg v-else-if="tab.key === 'inspections'" :class="['w-4 h-4', activeTab === tab.key ? 'text-white' : tab.iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>

                    <!-- Label & Count -->
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-sm tracking-tight">{{ tab.label }}</span>
                        <span :class="[
                            'px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider',
                            activeTab === tab.key
                                ? 'bg-white/20 text-white'
                                : tab.bgColor + ' ' + tab.iconColor
                        ]">
                            {{ tab.count }}
                        </span>
                    </div>

                    <!-- Active Indicator Dot -->
                    <div v-if="activeTab === tab.key" class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-white shadow-sm"></div>
                </button>
            </div>

            <!-- Services Tab Content -->
            <div v-if="activeTab === 'services'" class="space-y-6">
                <!-- Empty State -->
                <div v-if="departments.length === 0 && unassignedServices.length === 0"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                    <div
                        class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{
                        $t('services_management.empty') }}
                    </p>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('services_management.empty_hint') }}
                    </p>
                    <button v-if="can('services.create')" @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('services_management.add') }}
                    </button>
                </div>

                <!-- Accordion by Departments -->
                <div v-else class="space-y-4">
                    <!-- Department Accordions -->
                    <div v-for="dept in filteredDepartments" :key="dept.id"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Department Header -->
                        <div
                            class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <!-- Clickable part for expand/collapse -->
                            <button @click="toggleDepartment(dept.id)" class="flex items-center gap-3 flex-1">
                                <div
                                    class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-white font-bold">
                                    {{ getName(dept).charAt(0).toUpperCase() }}
                                </div>
                                <div class="text-start">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ getName(dept) }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ dept.services?.length
                                        || 0 }} {{
                                            $t('services_management.services') }}</p>
                                </div>
                                <span v-if="!dept.is_active"
                                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                    {{ $t('common.inactive') }}
                                </span>
                            </button>

                            <!-- Department Actions -->
                            <div class="flex items-center gap-2 me-3">
                                <button @click.stop="openEditDepartmentModal(dept)"
                                    class="p-1.5 text-gray-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors"
                                    :title="$t('departments.edit')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click.stop="handleDeleteDepartment(dept)"
                                    class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    :title="$t('common.delete')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Expand Icon -->
                            <button @click="toggleDepartment(dept.id)">
                                <svg :class="['w-5 h-5 text-gray-400 transition-transform', expandedDepts.has(dept.id) ? 'rotate-180' : '']"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Services Table -->
                        <div v-if="expandedDepts.has(dept.id)" class="border-t border-gray-200 dark:border-gray-700">
                            <div v-if="dept.services?.length === 0"
                                class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ $t('services_management.no_services') }}
                            </div>
                            <div v-else class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.number') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.name') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.base_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.min_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.duration') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.warranty') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.allow_price_override') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.updated_by') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr v-for="(service, index) in dept.services" :key="service.id"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                            <!-- # -->
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ index + 1 }}
                                            </td>
                                            <!-- Name -->
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <button v-if="can('services.update')"
                                                        @click="openQuickEditModal(service)"
                                                        class="font-medium text-gray-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 hover:underline transition-colors cursor-pointer">
                                                        {{ getName(service) }}
                                                    </button>
                                                    <span v-else class="font-medium text-gray-900 dark:text-white">
                                                        {{ getName(service) }}
                                                    </span>
                                                    <span v-if="service.type === 'external'"
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                                        {{ $t('services_management.coming_soon') }}
                                                    </span>
                                                </div>
                                            </td>
                                            <!-- Base Price -->
                                            <td class="px-4 py-3 font-semibold text-teal-600 dark:text-teal-400">
                                                {{ service.base_price }} ر.س
                                            </td>
                                            <!-- Min Price -->
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                {{ service.min_price ? `${service.min_price} ر.س` :
                                                    $t('services_management.not_set') }}
                                            </td>
                                            <!-- Duration -->
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                <span v-if="service.duration_value">
                                                    {{ service.duration_value }} {{
                                                        $t(`services_management.duration_units.${service.duration_unit}`)
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">{{
                                                    $t('services_management.not_set')
                                                    }}</span>
                                            </td>
                                            <!-- Warranty -->
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                <span v-if="service.warranty_value">
                                                    {{ service.warranty_value }} {{
                                                        $t(`services_management.warranty_units.${service.warranty_unit}`)
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">{{
                                                    $t('services_management.not_set')
                                                    }}</span>
                                            </td>
                                            <!-- Allow Price Override -->
                                            <td class="px-4 py-3 text-center">
                                                <span v-if="service.allow_price_override"
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </span>
                                                <span v-else
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </span>
                                            </td>
                                            <!-- Updated By -->
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
                                                {{ service.updater?.name || $t('services_management.not_set') }}
                                            </td>
                                            <!-- Actions -->
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button @click.stop="toggleServiceActive(service)"
                                                        :class="service.is_active
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                                        class="px-2 py-0.5 text-xs font-medium rounded-full transition-all hover:opacity-80">
                                                        {{ service.is_active ? $t('common.active') :
                                                            $t('common.inactive')
                                                        }}
                                                    </button>
                                                    <button @click.stop="openEditModal(service)"
                                                        class="p-1.5 text-gray-500 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button @click.stop="handleDelete(service)"
                                                        class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Unassigned Services -->
                    <div v-if="unassignedServices.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <button @click="toggleDepartment('unassigned')"
                            class="w-full px-5 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </div>
                                <div class="text-start">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{
                                        $t('services_management.unassigned') }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{
                                        unassignedServices.length }} {{
                                            $t('services_management.services') }}</p>
                                </div>
                            </div>
                            <svg :class="['w-5 h-5 text-gray-400 transition-transform', expandedDepts.has('unassigned') ? 'rotate-180' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-if="expandedDepts.has('unassigned')"
                            class="border-t border-gray-200 dark:border-gray-700">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.number') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.name') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.base_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.min_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.duration') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.warranty') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.allow_price_override') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.updated_by') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">
                                                {{ $t('services_management.columns.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr v-for="(service, index) in unassignedServices" :key="service.id"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                            <!-- # -->
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ index + 1 }}
                                            </td>
                                            <!-- Name -->
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <button v-if="can('services.update')"
                                                        @click="openQuickEditModal(service)"
                                                        class="font-medium text-gray-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 hover:underline transition-colors cursor-pointer">
                                                        {{ getName(service) }}
                                                    </button>
                                                    <span v-else class="font-medium text-gray-900 dark:text-white">
                                                        {{ getName(service) }}
                                                    </span>
                                                    <span v-if="service.type === 'external'"
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                                        {{ $t('services_management.coming_soon') }}
                                                    </span>
                                                </div>
                                            </td>
                                            <!-- Base Price -->
                                            <td class="px-4 py-3 font-semibold text-teal-600 dark:text-teal-400">
                                                {{ service.base_price }} ر.س
                                            </td>
                                            <!-- Min Price -->
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                {{ service.min_price ? `${service.min_price} ر.س` :
                                                    $t('services_management.not_set') }}
                                            </td>
                                            <!-- Duration -->
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                <span v-if="service.duration_value">
                                                    {{ service.duration_value }} {{
                                                        $t(`services_management.duration_units.${service.duration_unit}`)
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">{{
                                                    $t('services_management.not_set')
                                                    }}</span>
                                            </td>
                                            <!-- Warranty -->
                                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                                <span v-if="service.warranty_value">
                                                    {{ service.warranty_value }} {{
                                                        $t(`services_management.warranty_units.${service.warranty_unit}`)
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">{{
                                                    $t('services_management.not_set')
                                                    }}</span>
                                            </td>
                                            <!-- Allow Price Override -->
                                            <td class="px-4 py-3 text-center">
                                                <span v-if="service.allow_price_override"
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </span>
                                                <span v-else
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </span>
                                            </td>
                                            <!-- Updated By -->
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
                                                {{ service.updater?.name || $t('services_management.not_set') }}
                                            </td>
                                            <!-- Actions -->
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center gap-1">
                                                    <button @click.stop="toggleServiceActive(service)"
                                                        :class="service.is_active
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                                        class="px-2 py-0.5 text-xs font-medium rounded-full transition-all hover:opacity-80">
                                                        {{ service.is_active ? $t('common.active') :
                                                            $t('common.inactive')
                                                        }}
                                                    </button>
                                                    <button @click.stop="openEditModal(service)"
                                                        class="p-1.5 text-gray-500 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button @click.stop="handleDelete(service)"
                                                        class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            <!-- Packages Tab Content -->
            <div v-if="activeTab === 'packages'" class="space-y-6">
                <!-- Empty State -->
                <div v-if="packages.length === 0"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                    <div
                        class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{
                        $t('packages.empty') }}
                    </p>
                    <button v-if="can('services.create')" @click="openCreatePackageModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 mt-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('packages.add') }}
                    </button>
                </div>

                <!-- Packages List -->
                <div v-else
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                        {{ $t('services_management.columns.number') }}
                                    </th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                        {{ $t('services_management.columns.name') }}
                                    </th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                        {{ $t('services_management.columns.base_price') }}
                                    </th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                        {{ $t('packages.items') }}
                                    </th>
                                    <th class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                        {{ $t('services_management.columns.updated_by') }}
                                    </th>
                                    <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">
                                        {{ $t('services_management.columns.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(pkg, index) in filteredPackages" :key="pkg.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ index + 1 }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <button v-if="can('services.update')" @click="openEditPackageModal(pkg)"
                                            class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline transition-colors cursor-pointer">
                                            {{ getName(pkg) }}
                                        </button>
                                        <span v-else class="font-medium text-gray-900 dark:text-white">
                                            {{ getName(pkg) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-teal-600 dark:text-teal-400">
                                        {{ pkg.base_price }} {{ $t('common.currency_sar') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ pkg.items?.length || 0 }} {{ $t('services_management.services')
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ pkg.updater?.name || $t('services_management.not_set') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click.stop="toggleServiceActive(pkg)" :class="pkg.is_active
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                                class="px-2 py-0.5 text-xs font-medium rounded-full transition-all hover:opacity-80">
                                                {{ pkg.is_active ? $t('common.active') :
                                                    $t('common.inactive')
                                                }}
                                            </button>
                                            <button @click.stop="openEditPackageModal(pkg)"
                                                class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click.stop="handleDelete(pkg)"
                                                class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Inspections Tab Content -->
            <div v-if="activeTab === 'inspections'" class="space-y-6">
                <!-- Empty State -->
                <div v-if="filteredConditionCategories.length === 0"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                    <div
                        class="w-16 h-16 mx-auto rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{
                        $t('services_management.inspections_empty') }}
                    </p>
                    <button @click="openCategoryModal()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 mt-4 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/30 hover:shadow-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('services_management.add_inspection_category') }}
                    </button>
                </div>

                <!-- Inspections List -->
                <div v-else class="space-y-6">
                    <!-- Toggle Setting & Add Button Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 gap-4 mb-4">
                        <div class="flex items-center gap-3">
                            <button 
                                @click="toggleInspectionsSetting"
                                :class="[
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                                    enableSystematicInspections ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'
                                ]"
                                role="switch"
                                :aria-checked="enableSystematicInspections"
                            >
                                <span 
                                    aria-hidden="true" 
                                    :class="[
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                        enableSystematicInspections 
                                            ? ($i18n.locale === 'ar' ? '-translate-x-5' : 'translate-x-5') 
                                            : 'translate-x-0'
                                    ]"
                                />
                            </button>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $t('services_management.enable_inspections') }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $t('services_management.enable_inspections_desc') }}</p>
                            </div>
                        </div>

                    </div>

                    <!-- Categories and Items -->
                    <div v-for="category in filteredConditionCategories" :key="category.id" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-4 shadow-sm">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ getName(category) }}</h3>
                                <span :class="category.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'" class="px-2 py-0.5 text-[10px] font-medium rounded-full">
                                    {{ category.is_active ? $t('common.active') : $t('common.inactive') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="toggleCategoryActive(category)"
                                    :disabled="category.source === 'system'"
                                    :class="[
                                        'px-2 py-0.5 text-[10px] font-medium rounded-full transition-all',
                                        category.source === 'system' ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-500 dark:bg-gray-800' : 'hover:opacity-80',
                                        category.is_active && category.source !== 'system' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : '',
                                        !category.is_active && category.source !== 'system' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : ''
                                    ]">
                                    {{ category.is_active ? $t('common.active') : $t('common.inactive') }}
                                </button>
                                <div class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                                <button @click="openInspectionModal(null, category.id)"
                                    class="p-1 text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors"
                                    :title="$t('services_management.add_inspection')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                                <button @click="openCategoryModal(category)"
                                    class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    :title="$t('common.edit')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteCategory(category)"
                                    :disabled="category.items && category.items.length > 0 || category.source === 'system'"
                                    :class="[
                                        'p-1 rounded-lg transition-colors',
                                        category.items && category.items.length > 0 || category.source === 'system' 
                                            ? 'text-gray-300 cursor-not-allowed' 
                                            : 'text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20'
                                    ]"
                                    :title="category.items && category.items.length > 0 ? $t('services_management.cannot_delete_has_items') : $t('common.delete')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto" v-if="category.items && category.items.length > 0">
                            <table class="w-full text-sm">
                                <thead class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 w-16">
                                            {{ $t('services_management.columns.number') }}
                                        </th>
                                        <th class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400">
                                            {{ $t('services_management.columns.name') }}
                                        </th>
                                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 w-32">
                                            {{ $t('common.status') }}
                                        </th>
                                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 w-32">
                                            {{ $t('services_management.columns.actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="(item, index) in category.items" :key="item.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ index + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button @click="openInspectionModal(item)"
                                                class="font-medium text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 hover:underline transition-colors cursor-pointer"
                                                :disabled="item.source === 'system'">
                                                {{ getName(item) }}
                                            </button>
                                            <span v-if="item.source === 'system'" class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $t('common.system') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="item.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                            class="px-2 py-0.5 text-xs font-medium rounded-full">
                                            {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click.stop="toggleInspectionActive(item)"
                                                :disabled="item.source === 'system'"
                                                :class="[
                                                    'px-2 py-0.5 text-xs font-medium rounded-full transition-all',
                                                    item.source === 'system' ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-500 dark:bg-gray-800' : 'hover:opacity-80',
                                                    item.is_active && item.source !== 'system' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : '',
                                                    !item.is_active && item.source !== 'system' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : ''
                                                ]">
                                                {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                                            </button>
                                            <button @click.stop="openInspectionModal(item)"
                                                :disabled="item.source === 'system'"
                                                :class="['p-1.5 rounded-lg transition-colors', item.source === 'system' ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed' : 'text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20']">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click.stop="deleteInspection(item)"
                                                :disabled="item.source === 'system'"
                                                :class="['p-1.5 rounded-lg transition-colors', item.source === 'system' ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed' : 'text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20']">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Form Modal -->
        <ServiceFormModal :show="showModal" :service="selectedService" :departments="departments" @close="closeModal"
            @saved="handleSaved" />

        <!-- Department Form Modal -->
        <DepartmentFormModal :show="showDepartmentModal" :department="selectedDepartment" @close="closeDepartmentModal"
            @saved="handleDepartmentSaved" />

        <!-- Service Quick Edit Modal -->
        <ServiceQuickEditModal :show="showQuickEditModal" :service="selectedServiceForQuickEdit"
            @close="closeQuickEditModal" @saved="handleQuickEditSaved" />

        <!-- Package Form Modal -->
        <PackageFormModal :show="showPackageModal" :service="selectedPackage" :departments="departments"
            :available-services="availableServices" @close="closePackageModal" @saved="handlePackageSaved" />

        <!-- Condition Category Form Modal -->
        <ConditionCategoryFormModal 
            :show="showCategoryModal" 
            :category="selectedCategory" 
            @close="closeCategoryModal"
            @saved="handleCategorySaved" 
        />

        <!-- Condition Item Form Modal -->
        <ConditionItemFormModal 
            :show="showInspectionModal" 
            :item="selectedInspection" 
            :initialCategoryId="initialCategoryForModal" 
            :categories="conditionCategories"
            @close="closeInspectionModal"
            @saved="handleInspectionSaved" 
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ServiceFormModal from '@/Components/ServiceFormModal.vue';
import PackageFormModal from '@/Components/PackageFormModal.vue';
import ServiceQuickEditModal from '@/Components/ServiceQuickEditModal.vue';
import DepartmentFormModal from '@/Components/DepartmentFormModal.vue';
import ConditionItemFormModal from '@/Components/ConditionItemFormModal.vue';
import ConditionCategoryFormModal from '@/Components/ConditionCategoryFormModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useLocalized } from '@/Composables/useLocalized';
import { usePermission } from '@/Composables/usePermission';

const { t } = useI18n();
const { confirm } = useConfirm();
const { success, error } = useToast();
const { getName } = useLocalized();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
    unassignedServices: {
        type: Array,
        default: () => [],
    },
    packages: {
        type: Array,
        default: () => [],
    },
    conditionCategories: {
        type: Array,
        default: () => [],
    },
    enableSystematicInspections: {
        type: Boolean,
        default: true,
    },
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'services');

watch(activeTab, (newTab) => {
    const url = new URL(window.location);
    url.searchParams.set('tab', newTab);
    window.history.replaceState({}, '', url);
});
const showModal = ref(false);
const selectedService = ref(null);
const searchQuery = ref('');
const expandedDepts = ref(new Set());

// Department modal state
const showDepartmentModal = ref(false);
const selectedDepartment = ref(null);

// Quick edit modal state
const showQuickEditModal = ref(false);
const selectedServiceForQuickEdit = ref(null);

// Package modal state
const showPackageModal = ref(false);
const selectedPackage = ref(null);

// Inspection modal state
const showInspectionModal = ref(false);
const selectedInspection = ref(null);
const selectedCategory = ref(null);

const showCategoryModal = ref(false);

// Open all departments by default on mount
onMounted(() => {
    props.departments.forEach(dept => {
        expandedDepts.value.add(dept.id);
    });
    // Also expand unassigned if there are any
    if (props.unassignedServices.length > 0) {
        expandedDepts.value.add('unassigned');
    }
});

const totalServices = computed(() => {
    let count = props.unassignedServices?.length || 0;
    props.departments.forEach(dept => {
        count += dept.services?.length || 0;
    });
    return count;
});

const filteredDepartments = computed(() => {
    if (!searchQuery.value) return props.departments;

    const query = searchQuery.value.toLowerCase();
    return props.departments.filter(dept =>
        dept.name_ar?.toLowerCase().includes(query) ||
        dept.name_en?.toLowerCase().includes(query) ||
        dept.services?.some(s =>
            s.name_ar?.toLowerCase().includes(query) ||
            s.name_en?.toLowerCase().includes(query)
        )
    );
});

const filteredPackages = computed(() => {
    if (!searchQuery.value) return props.packages;

    const query = searchQuery.value.toLowerCase();
    return props.packages.filter(pkg =>
        pkg.name_ar?.toLowerCase().includes(query) ||
        pkg.name_en?.toLowerCase().includes(query)
    );
});

const filteredConditionCategories = computed(() => {
    if (!props.conditionCategories) return [];

    let result = [...props.conditionCategories];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        // Filter categories that match search or have items that match search
        result = result.filter(cat => {
            const catMatch = (cat.name_ar && cat.name_ar.toLowerCase().includes(query)) ||
                             (cat.name_en && cat.name_en.toLowerCase().includes(query));
            const itemMatch = cat.items && cat.items.some(item => 
                (item.name_ar && item.name_ar.toLowerCase().includes(query)) ||
                (item.name_en && item.name_en.toLowerCase().includes(query))
            );
            return catMatch || itemMatch;
        });
        
        // Also map the categories to only include matching items if the category itself doesn't match
        result = result.map(cat => {
            const catMatch = (cat.name_ar && cat.name_ar.toLowerCase().includes(query)) ||
                             (cat.name_en && cat.name_en.toLowerCase().includes(query));
            
            if (catMatch) return cat; // If category matches, show all its items
            
            return {
                ...cat,
                items: cat.items ? cat.items.filter(item => 
                    (item.name_ar && item.name_ar.toLowerCase().includes(query)) ||
                    (item.name_en && item.name_en.toLowerCase().includes(query))
                ) : []
            };
        });
    }

    return result;
});

// Tab Configuration
const tabs = computed(() => [
    {
        key: 'services',
        label: t('services_management.title'),
        iconColor: 'text-teal-500',
        bgColor: 'bg-teal-100 dark:bg-teal-900/30',
        gradientFrom: '#0d9488', // teal-600
        gradientTo: '#059669',   // emerald-600
        count: totalServices.value
    },
    {
        key: 'packages',
        label: t('packages.title'),
        iconColor: 'text-indigo-500',
        bgColor: 'bg-indigo-100 dark:bg-indigo-900/30',
        gradientFrom: '#6366f1', // indigo-500
        gradientTo: '#4f46e5',   // indigo-600
        count: props.packages?.length || 0
    },
    {
        key: 'inspections',
        label: t('services_management.inspections'),
        iconColor: 'text-amber-500',
        bgColor: 'bg-amber-100 dark:bg-amber-900/30',
        gradientFrom: '#f59e0b', // amber-500
        gradientTo: '#d97706',   // amber-600
        count: props.conditionCategories?.reduce((acc, cat) => acc + (cat.items?.length || 0), 0) || 0
    }
]);

const headerConfig = computed(() => {
    const activeTabData = tabs.value.find(t => t.key === activeTab.value) || tabs.value[0];
    return {
        ...activeTabData,
        countLabel: activeTab.value === 'services' ? t('services_management.services') : 
                   activeTab.value === 'packages' ? t('packages.items') : 
                   t('services_management.inspections'),
        glowFrom: activeTab.value === 'services' ? 'from-teal-600' :
                  activeTab.value === 'packages' ? 'from-indigo-600' :
                  'from-amber-600',
        glowTo: activeTab.value === 'services' ? 'to-emerald-600' :
                activeTab.value === 'packages' ? 'to-purple-600' :
                'to-orange-600',
        badgeBg: activeTab.value === 'services' ? 'bg-teal-50/50 dark:bg-teal-900/30' :
                 activeTab.value === 'packages' ? 'bg-indigo-50/50 dark:bg-indigo-900/30' :
                 'bg-amber-50/50 dark:bg-amber-900/30',
        badgeText: activeTab.value === 'services' ? 'text-teal-600 dark:text-teal-400' :
                   activeTab.value === 'packages' ? 'text-indigo-600 dark:text-indigo-400' :
                   'text-amber-600 dark:text-amber-400',
        badgeBorder: activeTab.value === 'services' ? 'border-teal-100/50 dark:border-teal-800/30' :
                     activeTab.value === 'packages' ? 'border-indigo-100/50 dark:border-indigo-800/30' :
                     'border-amber-100/50 dark:border-amber-800/30',
        badgeDot: activeTab.value === 'services' ? 'bg-teal-500' :
                  activeTab.value === 'packages' ? 'bg-indigo-500' :
                  'bg-amber-500',
    };
});

const availableServices = computed(() => {
    // Collect all services from departments
    const services = [];
    props.departments.forEach(dept => {
        if (dept.services) {
            services.push(...dept.services);
        }
    });
    // Add unassigned services
    services.push(...props.unassignedServices);

    // Filter out inactive items if needed (currently checking for active in form modal would be better, but we return all for now)
    return services;
});

function toggleDepartment(id) {
    if (expandedDepts.value.has(id)) {
        expandedDepts.value.delete(id);
    } else {
        expandedDepts.value.add(id);
    }
}

function openCreateModal() {
    selectedService.value = null;
    showModal.value = true;
}

function openEditModal(service) {
    selectedService.value = service;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedService.value = null;
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments', 'unassignedServices', 'packages'] });
}

function toggleServiceActive(service) {
    router.patch(`/app/services/${service.id}/toggle-active`, {}, {
        preserveScroll: true,
    });
}

async function handleDelete(service) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(service)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (!confirmed) return;

    router.delete(`/app/services/${service.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
        onError: (errors) => {
            // Show error message from backend validation
            const errorMessage = errors.service || Object.values(errors)[0] || t('common.error');
            error(errorMessage);
        },
    });
}

// Department handlers
function openCreateDepartmentModal() {
    selectedDepartment.value = null;
    showDepartmentModal.value = true;
}

function openEditDepartmentModal(department) {
    selectedDepartment.value = department;
    showDepartmentModal.value = true;
}

function closeDepartmentModal() {
    showDepartmentModal.value = false;
    selectedDepartment.value = null;
}

function handleDepartmentSaved() {
    closeDepartmentModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments', 'unassignedServices'] });
}

async function handleDeleteDepartment(department) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(department)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (!confirmed) return;

    router.delete(`/app/departments/${department.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
        onError: (errors) => {
            if (errors.delete) {
                // Show error for cannot delete department with services
                confirm({
                    title: t('common.error'),
                    message: errors.delete,
                    confirmText: t('nav.close'),
                    type: 'danger',
                });
            }
        },
    });
}

// Quick edit modal handlers
function openQuickEditModal(service) {
    selectedServiceForQuickEdit.value = service;
    showQuickEditModal.value = true;
}

function closeQuickEditModal() {
    showQuickEditModal.value = false;
    selectedServiceForQuickEdit.value = null;
}

function handleQuickEditSaved() {
    closeQuickEditModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments', 'unassignedServices'] });
}

// Package handlers
function openCreatePackageModal() {
    selectedPackage.value = null;
    showPackageModal.value = true;
}

function openEditPackageModal(pkg) {
    selectedPackage.value = pkg;
    showPackageModal.value = true;
}

function closePackageModal() {
    showPackageModal.value = false;
    selectedPackage.value = null;
}

function handlePackageSaved() {
    closePackageModal();
    success(t('common.saved_success'));
    activeTab.value = 'packages';
    router.reload({ only: ['departments', 'unassignedServices', 'packages'] });
}
// Inspection Methods
const toggleInspectionsSetting = () => {
    router.post(route('app.services.toggle-inspections-setting'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.updated_success'));
        }
    });
};

// Category Methods
const openCategoryModal = (category = null) => {
    selectedCategory.value = category;
    showCategoryModal.value = true;
};

const closeCategoryModal = () => {
    showCategoryModal.value = false;
    setTimeout(() => {
        selectedCategory.value = null;
    }, 200);
};

const handleCategorySaved = () => {
    success(selectedCategory.value ? t('common.updated_success') : t('common.created_success'));
};

const toggleCategoryActive = (category) => {
    if (category.source === 'system') return;

    router.patch(route('app.condition-categories.toggle', category.id), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const deleteCategory = async (category) => {
    if (category.source === 'system') return;

    const confirmed = await confirm({
        title: t('services_management.delete_inspection_category'),
        message: t('common.delete_confirmation'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (!confirmed) return;

    router.delete(route('app.condition-categories.destroy', category.id), {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
        onError: (errors) => {
            if (errors.error) {
                error(errors.error);
            }
        }
    });
};

const initialCategoryForModal = ref(null);

const openInspectionModal = (item = null, categoryData = null) => {
    selectedInspection.value = item;
    initialCategoryForModal.value = categoryData;
    showInspectionModal.value = true;
};

const closeInspectionModal = () => {
    showInspectionModal.value = false;
    setTimeout(() => {
        selectedInspection.value = null;
        initialCategoryForModal.value = null;
    }, 200);
};

const handleInspectionSaved = () => {
    success(selectedInspection.value ? t('common.updated_success') : t('common.created_success'));
};

const toggleInspectionActive = (item) => {
    if (item.source === 'system') return;

    router.patch(route('app.condition-items.toggle', item.id), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const deleteInspection = async (item) => {
    if (item.source === 'system') return;

    const confirmed = await confirm({
        title: t('services_management.delete_inspection'),
        message: t('common.delete_confirmation'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (!confirmed) return;

    router.delete(route('app.condition-items.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
    });
};
</script>
