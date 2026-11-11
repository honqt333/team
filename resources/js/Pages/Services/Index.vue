<template>
    <AppLayout :title="$t('services_management.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('services_management.title')"
                :subtitle="$t('services_management.subtitle')"
                :totalCount="toEnglish(headerConfig.count)"
                :countLabel="headerConfig.countLabel"
            >
                <template #icon>
                    <!-- Icon based on Active Tab -->
                    <svg
                        v-if="activeTab === 'services'"
                        class="w-8 h-8 text-action-primary"
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
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                    <svg
                        v-else-if="activeTab === 'packages'"
                        class="w-8 h-8 text-action-primary"
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
                        v-else-if="activeTab === 'inspections'"
                        class="w-8 h-8 text-action-primary"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Actions when activeTab === 'services' -->
                        <template v-if="activeTab === 'services'">
                            <button
                                v-if="can('services.departments.manage') || isAnyAdmin()"
                                @click="openCreateDepartmentModal"
                                type="button"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-surface-secondary hover:bg-surface-secondary/80 text-content-primary border border-border-subtle rounded-xl font-bold shadow-2xs transition-colors cursor-pointer"
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
                                        stroke-width="2.5"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                <span class="text-sm">{{ $t('departments.add') }}</span>
                            </button>

                            <button
                                v-if="can('services.create') || isAnyAdmin()"
                                @click="openCreateModal"
                                type="button"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                        stroke-width="2.5"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                <span class="text-sm">{{ $t('services_management.add') }}</span>
                            </button>
                        </template>

                        <!-- Actions when activeTab === 'packages' -->
                        <template v-else-if="activeTab === 'packages'">
                            <div
                                class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                            >
                                <AppViewModeToggle
                                    v-model="packagesViewMode"
                                    storage-key="services_packages_view_mode"
                                />
                            </div>

                            <button
                                v-if="can('services.create') || isAnyAdmin()"
                                @click="openCreatePackageModal"
                                type="button"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                        stroke-width="2.5"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                <span class="text-sm">{{ $t('packages.add') }}</span>
                            </button>
                        </template>

                        <!-- Actions when activeTab === 'inspections' -->
                        <template v-else-if="activeTab === 'inspections'">
                            <button
                                v-if="can('services.create') || isAnyAdmin()"
                                @click="openCategoryModal()"
                                type="button"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                        stroke-width="2.5"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                <span class="text-sm">
                                    {{ $t('services_management.add_inspection_category') }}
                                </span>
                            </button>
                        </template>
                    </div>
                </template>

                <template #filters>
                    <div class="relative group flex-1 w-full">
                        <div
                            class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none text-content-muted group-focus-within:text-action-primary transition-colors"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('services_management.search')"
                            class="w-full ps-11 pe-10 py-2.5 bg-surface-primary dark:bg-surface-secondary/50 border border-border-subtle rounded-xl text-sm text-content-primary placeholder-content-muted focus:outline-hidden focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all shadow-2xs"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            type="button"
                            class="absolute inset-y-0 end-0 pe-3 flex items-center text-content-muted hover:text-content-primary transition-colors cursor-pointer"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Filter Tabs - Standard AppTabs -->
            <div class="w-full">
                <AppTabs v-model="activeTab" :tabs="mainTabs" variant="underline" />
            </div>

            <!-- Services Tab Content -->
            <div v-if="activeTab === 'services'" class="space-y-6">
                <!-- Empty State -->
                <AppEmptyState
                    v-if="departments.length === 0 && unassignedServices.length === 0"
                    :title="$t('departments.empty')"
                    :description="$t('services_management.add_department_first')"
                >
                    <template #actions>
                        <button
                            v-if="can('services.departments.manage') || isAnyAdmin()"
                            @click="openCreateDepartmentModal"
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span>{{ $t('departments.add') }}</span>
                        </button>
                    </template>
                </AppEmptyState>

                <!-- Accordion by Departments -->
                <div v-else class="space-y-4">
                    <!-- Department Accordions -->
                    <div
                        v-for="dept in filteredDepartments"
                        :key="dept.id"
                        class="bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs overflow-hidden"
                    >
                        <!-- Department Header -->
                        <div
                            class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-3 px-4 sm:px-5 py-4 hover:bg-surface-secondary/50 transition-colors"
                        >
                            <!-- Clickable part for expand/collapse -->
                            <button
                                @click="toggleDepartment(dept.id)"
                                class="flex items-center gap-3 flex-1 text-start cursor-pointer"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl bg-action-primary/10 text-action-primary flex items-center justify-center font-bold text-base"
                                >
                                    {{ getName(dept).charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-content-primary">
                                        {{ getName(dept) }}
                                    </h3>
                                    <p class="text-xs text-content-muted">
                                        {{ dept.services?.length || 0 }}
                                        {{ $t('services_management.services') }}
                                    </p>
                                </div>
                                <span
                                    v-if="!dept.is_active"
                                    class="px-2 py-0.5 text-xs font-medium rounded-full bg-danger-soft text-danger"
                                >
                                    {{ $t('common.inactive') }}
                                </span>
                            </button>

                            <!-- Department Actions -->
                            <div class="flex items-center gap-2 me-3">
                                <!-- Add Service Button -->
                                <button
                                    v-if="can('services.create') || isAnyAdmin()"
                                    @click.stop="openCreateModalForDepartment(dept)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs bg-action-primary hover:bg-action-primary-hover text-white rounded-lg font-bold shadow-2xs transition-colors cursor-pointer me-1"
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
                                            stroke-width="2.5"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    <span>{{ $t('services_management.add') }}</span>
                                </button>
                                <button
                                    @click.stop="openEditDepartmentModal(dept)"
                                    class="p-1.5 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                    :title="$t('departments.edit')"
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
                                </button>
                                <button
                                    @click.stop="handleDeleteDepartment(dept)"
                                    class="p-1.5 text-content-muted hover:text-danger hover:bg-danger-soft/20 rounded-lg transition-colors cursor-pointer"
                                    :title="$t('common.delete')"
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
                                </button>
                            </div>

                            <!-- Expand Icon -->
                            <button @click="toggleDepartment(dept.id)">
                                <svg
                                    :class="[
                                        'w-5 h-5 text-gray-400 transition-transform',
                                        !collapsedDepts.has(dept.id) ? 'rotate-180' : '',
                                    ]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Services Table -->
                        <div
                            v-if="!collapsedDepts.has(dept.id)"
                            class="border-t border-gray-200 dark:border-gray-700"
                        >
                            <div
                                v-if="dept.services?.length === 0"
                                class="px-5 py-8 text-center text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('services_management.no_services') }}
                            </div>
                            <div v-else class="overflow-x-auto">
                                <DataTable structured class="w-full min-w-[720px] text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.number') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.name') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.base_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.min_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.duration') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.warranty') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 align-middle"
                                            >
                                                {{
                                                    $t(
                                                        'services_management.columns.allow_price_override'
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.updated_by') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 align-middle"
                                            >
                                                {{ $t('services_management.columns.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr
                                            v-for="(service, index) in dept.services"
                                            :key="service.id"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors"
                                        >
                                            <!-- # -->
                                            <td
                                                class="px-4 py-3 text-gray-500 dark:text-gray-400 align-middle"
                                            >
                                                {{ index + 1 }}
                                            </td>
                                            <!-- Name -->
                                            <td class="px-4 py-3 align-middle">
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        v-if="can('services.update')"
                                                        @click="openQuickEditModal(service)"
                                                        class="font-medium text-gray-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 hover:underline transition-colors cursor-pointer"
                                                    >
                                                        {{ getName(service) }}
                                                    </button>
                                                    <span
                                                        v-else
                                                        class="font-medium text-gray-900 dark:text-white"
                                                    >
                                                        {{ getName(service) }}
                                                    </span>
                                                    <span
                                                        v-if="service.type === 'external'"
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400"
                                                    >
                                                        {{ $t('services_management.coming_soon') }}
                                                    </span>
                                                </div>
                                            </td>
                                            <!-- Base Price -->
                                            <td
                                                class="px-4 py-3 font-semibold text-teal-600 dark:text-teal-400 align-middle"
                                            >
                                                {{ service.base_price }} {{ $t('common.currency') }}
                                            </td>
                                            <!-- Min Price -->
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300 align-middle"
                                            >
                                                {{
                                                    service.min_price
                                                        ? $t('common.price_in_sar', {
                                                              min_price: service.min_price,
                                                          })
                                                        : $t('services_management.not_set')
                                                }}
                                            </td>
                                            <!-- Duration -->
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300 align-middle"
                                            >
                                                <span v-if="service.duration_value">
                                                    {{ service.duration_value }}
                                                    {{
                                                        $t(
                                                            `services_management.duration_units.${service.duration_unit}`
                                                        )
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">
                                                    {{ $t('services_management.not_set') }}
                                                </span>
                                            </td>
                                            <!-- Warranty -->
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300 align-middle"
                                            >
                                                <span v-if="service.warranty_value">
                                                    {{ service.warranty_value }}
                                                    {{
                                                        $t(
                                                            `services_management.warranty_units.${service.warranty_unit}`
                                                        )
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">
                                                    {{ $t('services_management.not_set') }}
                                                </span>
                                            </td>
                                            <!-- Allow Price Override -->
                                            <td class="px-4 py-3 text-center align-middle">
                                                <span
                                                    v-if="service.allow_price_override"
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400"
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
                                                            d="M5 13l4 4L19 7"
                                                        />
                                                    </svg>
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
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
                                                            d="M6 18L18 6M6 6l12 12"
                                                        />
                                                    </svg>
                                                </span>
                                            </td>
                                            <!-- Updated By -->
                                            <td
                                                class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm align-middle"
                                            >
                                                {{
                                                    service.updater?.name ||
                                                    $t('services_management.not_set')
                                                }}
                                            </td>
                                            <!-- Actions -->
                                            <td class="px-4 py-3 align-middle">
                                                <div class="flex items-center justify-center gap-2">
                                                    <AppToggle
                                                        :model-value="Boolean(service.is_active)"
                                                        color="success"
                                                        size="sm"
                                                        @update:model-value="
                                                            toggleServiceActive(service)
                                                        "
                                                    />
                                                    <button
                                                        @click.stop="openEditModal(service)"
                                                        class="p-1.5 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                                        :title="$t('common.edit')"
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
                                                    </button>
                                                    <button
                                                        @click.stop="handleDelete(service)"
                                                        class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
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
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </DataTable>
                            </div>
                        </div>
                    </div>

                    <!-- Unassigned Services -->
                    <div
                        v-if="unassignedServices.length > 0"
                        class="bg-surface-primary rounded-2xl border border-border-subtle shadow-2xs overflow-hidden"
                    >
                        <button
                            @click="toggleDepartment('unassigned')"
                            class="w-full px-5 py-4 flex items-center justify-between hover:bg-surface-secondary/50 transition-colors cursor-pointer"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-surface-tertiary text-content-muted flex items-center justify-center"
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
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                    </svg>
                                </div>
                                <div class="text-start">
                                    <h3 class="font-bold text-content-primary">
                                        {{ $t('services_management.unassigned') }}
                                    </h3>
                                    <p class="text-xs text-content-muted">
                                        {{ unassignedServices.length }}
                                        {{ $t('services_management.services') }}
                                    </p>
                                </div>
                            </div>
                            <svg
                                :class="[
                                    'w-5 h-5 text-gray-400 transition-transform',
                                    !collapsedDepts.has('unassigned') ? 'rotate-180' : '',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-if="!collapsedDepts.has('unassigned')"
                            class="border-t border-gray-200 dark:border-gray-700"
                        >
                            <div class="overflow-x-auto">
                                <DataTable structured class="w-full min-w-[720px] text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.number') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.name') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.base_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.min_price') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.duration') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.warranty') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 align-middle"
                                            >
                                                {{
                                                    $t(
                                                        'services_management.columns.allow_price_override'
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                            >
                                                {{ $t('services_management.columns.updated_by') }}
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 align-middle"
                                            >
                                                {{ $t('services_management.columns.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr
                                            v-for="(service, index) in unassignedServices"
                                            :key="service.id"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors"
                                        >
                                            <!-- # -->
                                            <td
                                                class="px-4 py-3 text-gray-500 dark:text-gray-400 align-middle"
                                            >
                                                {{ index + 1 }}
                                            </td>
                                            <!-- Name -->
                                            <td class="px-4 py-3 align-middle">
                                                <div class="flex items-center gap-2">
                                                    <button
                                                        v-if="can('services.update')"
                                                        @click="openQuickEditModal(service)"
                                                        class="font-medium text-gray-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 hover:underline transition-colors cursor-pointer"
                                                    >
                                                        {{ getName(service) }}
                                                    </button>
                                                    <span
                                                        v-else
                                                        class="font-medium text-gray-900 dark:text-white"
                                                    >
                                                        {{ getName(service) }}
                                                    </span>
                                                    <span
                                                        v-if="service.type === 'external'"
                                                        class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400"
                                                    >
                                                        {{ $t('services_management.coming_soon') }}
                                                    </span>
                                                </div>
                                            </td>
                                            <!-- Base Price -->
                                            <td
                                                class="px-4 py-3 font-semibold text-teal-600 dark:text-teal-400 align-middle"
                                            >
                                                {{ service.base_price }} {{ $t('common.currency') }}
                                            </td>
                                            <!-- Min Price -->
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300 align-middle"
                                            >
                                                {{
                                                    service.min_price
                                                        ? $t('common.price_in_sar', {
                                                              min_price: service.min_price,
                                                          })
                                                        : $t('services_management.not_set')
                                                }}
                                            </td>
                                            <!-- Duration -->
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300 align-middle"
                                            >
                                                <span v-if="service.duration_value">
                                                    {{ service.duration_value }}
                                                    {{
                                                        $t(
                                                            `services_management.duration_units.${service.duration_unit}`
                                                        )
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">
                                                    {{ $t('services_management.not_set') }}
                                                </span>
                                            </td>
                                            <!-- Warranty -->
                                            <td
                                                class="px-4 py-3 text-gray-600 dark:text-gray-300 align-middle"
                                            >
                                                <span v-if="service.warranty_value">
                                                    {{ service.warranty_value }}
                                                    {{
                                                        $t(
                                                            `services_management.warranty_units.${service.warranty_unit}`
                                                        )
                                                    }}
                                                </span>
                                                <span v-else class="text-gray-400">
                                                    {{ $t('services_management.not_set') }}
                                                </span>
                                            </td>
                                            <!-- Allow Price Override -->
                                            <td class="px-4 py-3 text-center align-middle">
                                                <span
                                                    v-if="service.allow_price_override"
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400"
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
                                                            d="M5 13l4 4L19 7"
                                                        />
                                                    </svg>
                                                </span>
                                                <span
                                                    v-else
                                                    class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
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
                                                            d="M6 18L18 6M6 6l12 12"
                                                        />
                                                    </svg>
                                                </span>
                                            </td>
                                            <!-- Updated By -->
                                            <td
                                                class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm align-middle"
                                            >
                                                {{
                                                    service.updater?.name ||
                                                    $t('services_management.not_set')
                                                }}
                                            </td>
                                            <!-- Actions -->
                                            <td class="px-4 py-3 align-middle">
                                                <div class="flex items-center justify-center gap-2">
                                                    <AppToggle
                                                        :model-value="Boolean(service.is_active)"
                                                        color="success"
                                                        size="sm"
                                                        @update:model-value="
                                                            toggleServiceActive(service)
                                                        "
                                                    />
                                                    <button
                                                        @click.stop="openEditModal(service)"
                                                        class="p-1.5 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                                        :title="$t('common.edit')"
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
                                                    </button>
                                                    <button
                                                        @click.stop="handleDelete(service)"
                                                        class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
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
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </DataTable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packages Tab Content -->
            <div v-if="activeTab === 'packages'" class="space-y-6">
                <!-- Empty State -->
                <AppEmptyState v-if="filteredPackages.length === 0" :title="$t('packages.empty')">
                    <template #actions>
                        <button
                            v-if="can('services.create')"
                            @click="openCreatePackageModal"
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span>{{ $t('packages.add') }}</span>
                        </button>
                    </template>
                </AppEmptyState>

                <!-- Packages List (Table or Grid) -->
                <div v-else>
                    <!-- Table View -->
                    <DataTable
                        v-if="packagesViewMode === 'list'"
                        :items="filteredPackages"
                        :columns="packageColumns"
                    >
                        <template #cell-number="{ index }">
                            <span class="text-content-muted">{{ index + 1 }}</span>
                        </template>
                        <template #cell-name="{ item }">
                            <button
                                v-if="can('services.update')"
                                @click="openEditPackageModal(item)"
                                type="button"
                                class="font-medium text-content-primary hover:text-action-primary hover:underline transition-colors cursor-pointer text-start"
                            >
                                {{ getName(item) }}
                            </button>
                            <span v-else class="font-medium text-content-primary">
                                {{ getName(item) }}
                            </span>
                        </template>
                        <template #cell-base_price="{ item }">
                            <span class="font-bold text-action-primary">
                                {{ toEnglish(item.base_price) }} {{ $t('common.currency_sar') }}
                            </span>
                        </template>
                        <template #cell-items="{ item }">
                            <span class="text-content-muted">
                                {{ item.items?.length || 0 }}
                                {{ $t('services_management.services') }}
                            </span>
                        </template>
                        <template #cell-updated_by="{ item }">
                            <span class="text-content-muted">
                                {{ item.updater?.name || $t('services_management.not_set') }}
                            </span>
                        </template>
                        <template #cell-is_active="{ item }">
                            <div class="flex items-center justify-center">
                                <AppToggle
                                    :model-value="Boolean(item.is_active)"
                                    color="success"
                                    size="sm"
                                    @update:model-value="toggleServiceActive(item)"
                                />
                            </div>
                        </template>
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center gap-1">
                                <button
                                    @click.stop="openEditPackageModal(item)"
                                    class="p-1.5 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                    :title="$t('common.edit')"
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
                                </button>
                                <button
                                    @click.stop="handleDelete(item)"
                                    class="p-1.5 text-content-muted hover:text-danger hover:bg-danger-soft/20 rounded-lg transition-colors cursor-pointer"
                                    :title="$t('common.delete')"
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
                                </button>
                            </div>
                        </template>
                    </DataTable>

                    <!-- Grid View -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="pkg in filteredPackages"
                            :key="pkg.id"
                            class="bg-surface-primary rounded-2xl border border-border-subtle p-5 shadow-2xs hover:shadow-xs transition-shadow flex flex-col justify-between gap-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-action-primary/10 text-action-primary flex items-center justify-center font-bold"
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
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-content-primary">
                                            {{ getName(pkg) }}
                                        </h3>
                                        <p class="text-xs text-content-muted">
                                            {{ pkg.items?.length || 0 }}
                                            {{ $t('services_management.services') }}
                                        </p>
                                    </div>
                                </div>
                                <AppToggle
                                    :model-value="Boolean(pkg.is_active)"
                                    color="success"
                                    size="sm"
                                    @update:model-value="toggleServiceActive(pkg)"
                                />
                            </div>

                            <div
                                class="flex items-center justify-between pt-3 border-t border-border-subtle text-sm"
                            >
                                <div>
                                    <span class="text-xs text-content-muted block">
                                        {{ $t('services_management.columns.base_price') }}
                                    </span>
                                    <span class="font-bold text-action-primary text-base">
                                        {{ toEnglish(pkg.base_price) }}
                                        {{ $t('common.currency_sar') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click.stop="openEditPackageModal(pkg)"
                                        class="p-1.5 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                        :title="$t('common.edit')"
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
                                    </button>
                                    <button
                                        @click.stop="handleDelete(pkg)"
                                        class="p-1.5 text-content-muted hover:text-danger hover:bg-danger-soft/20 rounded-lg transition-colors cursor-pointer"
                                        :title="$t('common.delete')"
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
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inspections Tab Content -->
            <div v-if="activeTab === 'inspections'" class="space-y-6">
                <!-- Empty State -->
                <AppEmptyState
                    v-if="filteredConditionCategories.length === 0"
                    :title="$t('services_management.inspections_empty')"
                >
                    <template #actions>
                        <button
                            @click="openCategoryModal()"
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span>{{ $t('services_management.add_inspection_category') }}</span>
                        </button>
                    </template>
                </AppEmptyState>

                <!-- Inspections List -->
                <div v-else class="space-y-6">
                    <!-- Toggle Setting & Add Button Header -->
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between bg-surface-primary p-4 rounded-2xl border border-border-subtle shadow-2xs gap-4 mb-4"
                    >
                        <div class="flex items-center gap-3">
                            <AppToggle
                                :model-value="enableSystematicInspections"
                                color="success"
                                @change="toggleInspectionsSetting"
                            />
                            <div>
                                <h4 class="text-sm font-bold text-content-primary">
                                    {{ $t('services_management.enable_inspections') }}
                                </h4>
                                <p class="text-xs text-content-muted mt-0.5">
                                    {{ $t('services_management.enable_inspections_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Categories and Items -->
                    <div
                        v-for="category in filteredConditionCategories"
                        :key="category.id"
                        class="bg-surface-primary rounded-2xl border border-border-subtle overflow-hidden mb-4 shadow-2xs"
                    >
                        <div
                            class="px-4 py-3 bg-surface-secondary/50 border-b border-border-subtle flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <h3 class="text-sm font-bold text-content-primary">
                                    {{ getName(category) }}
                                </h3>
                                <span
                                    :class="
                                        category.is_active
                                            ? 'bg-success-soft text-success'
                                            : 'bg-danger-soft text-danger'
                                    "
                                    class="px-2 py-0.5 text-[10px] font-medium rounded-full"
                                >
                                    {{
                                        category.is_active
                                            ? $t('common.active')
                                            : $t('common.inactive')
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <AppToggle
                                    :model-value="Boolean(category.is_active)"
                                    :disabled="category.source === 'system'"
                                    color="success"
                                    size="sm"
                                    @update:model-value="toggleCategoryActive(category)"
                                />
                                <div class="w-px h-4 bg-border-subtle mx-1"></div>
                                <button
                                    @click="openInspectionModal(null, category.id)"
                                    class="p-1 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                    :title="$t('services_management.add_inspection')"
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
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                </button>
                                <button
                                    @click="openCategoryModal(category)"
                                    class="p-1 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                    :title="$t('common.edit')"
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
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteCategory(category)"
                                    :disabled="
                                        (category.items && category.items.length > 0) ||
                                        category.source === 'system'
                                    "
                                    :class="[
                                        'p-1 rounded-lg transition-colors',
                                        (category.items && category.items.length > 0) ||
                                        category.source === 'system'
                                            ? 'text-content-muted/30 cursor-not-allowed'
                                            : 'text-content-muted hover:text-danger hover:bg-danger-soft/20 cursor-pointer',
                                    ]"
                                    :title="
                                        category.items && category.items.length > 0
                                            ? $t('services_management.cannot_delete_has_items')
                                            : $t('common.delete')
                                    "
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
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div
                            class="overflow-x-auto"
                            v-if="category.items && category.items.length > 0"
                        >
                            <DataTable structured class="w-full text-sm">
                                <thead
                                    class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700/50"
                                >
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 w-16 align-middle text-center"
                                        >
                                            {{ $t('services_management.columns.number') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-start font-medium text-gray-500 dark:text-gray-400 align-middle text-center"
                                        >
                                            {{ $t('services_management.columns.name') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 w-32 align-middle"
                                        >
                                            {{ $t('common.status') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 w-32 align-middle"
                                        >
                                            {{ $t('services_management.columns.actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr
                                        v-for="(item, index) in category.items"
                                        :key="item.id"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors"
                                    >
                                        <td
                                            class="px-4 py-3 text-gray-500 dark:text-gray-400 align-middle"
                                        >
                                            {{ index + 1 }}
                                        </td>
                                        <td class="px-4 py-3 align-middle">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @click="openInspectionModal(item)"
                                                    class="font-medium text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 hover:underline transition-colors cursor-pointer"
                                                    :disabled="item.source === 'system'"
                                                >
                                                    {{ getName(item) }}
                                                </button>
                                                <span
                                                    v-if="item.source === 'system'"
                                                    class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                                >
                                                    {{ $t('common.system') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center align-middle">
                                            <div class="flex items-center justify-center">
                                                <AppToggle
                                                    :model-value="Boolean(item.is_active)"
                                                    :disabled="item.source === 'system'"
                                                    color="success"
                                                    size="sm"
                                                    @update:model-value="
                                                        toggleInspectionActive(item)
                                                    "
                                                />
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 align-middle">
                                            <div class="flex items-center justify-center gap-1">
                                                <button
                                                    @click.stop="openInspectionModal(item)"
                                                    :disabled="item.source === 'system'"
                                                    :class="[
                                                        'p-1.5 rounded-lg transition-colors',
                                                        item.source === 'system'
                                                            ? 'text-content-muted/30 cursor-not-allowed'
                                                            : 'text-content-muted hover:text-action-primary hover:bg-surface-secondary cursor-pointer',
                                                    ]"
                                                    :title="$t('common.edit')"
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
                                                </button>
                                                <button
                                                    @click.stop="deleteInspection(item)"
                                                    :disabled="item.source === 'system'"
                                                    :class="[
                                                        'p-1.5 rounded-lg transition-colors',
                                                        item.source === 'system'
                                                            ? 'text-content-muted/30 cursor-not-allowed'
                                                            : 'text-content-muted hover:text-danger hover:bg-danger-soft/20 cursor-pointer',
                                                    ]"
                                                    :title="$t('common.delete')"
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
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Form Modal (Catalog link or Edit) -->
        <ServiceFormModal
            :show="showModal"
            :service="selectedService"
            :departments="departments"
            :current-department="currentDepartment"
            :other-branches-services="otherBranchesServices"
            :pending-catalog-service-id="pendingCatalogServiceId"
            @close="closeModal"
            @saved="handleSaved"
            @open-new-definition="openNewDefinitionModal"
        />

        <!-- New Service Definition Modal (separate, for permission gating later) -->
        <NewServiceDefinitionModal
            :show="showNewDefinitionModal"
            :department-id="newDefinitionDepartmentId"
            @close="closeNewDefinitionModal"
            @saved="handleNewDefinitionSaved"
        />

        <!-- Department Form Modal -->
        <DepartmentFormModal
            :show="showDepartmentModal"
            :department="selectedDepartment"
            :existing-departments="departments"
            @close="closeDepartmentModal"
            @saved="handleDepartmentSaved"
        />

        <!-- Service Quick Edit Modal -->
        <ServiceQuickEditModal
            :show="showQuickEditModal"
            :service="selectedServiceForQuickEdit"
            @close="closeQuickEditModal"
            @saved="handleQuickEditSaved"
        />

        <!-- Package Form Modal -->
        <PackageFormModal
            :show="showPackageModal"
            :service="selectedPackage"
            :departments="departments"
            :available-services="availableServices"
            :other-branches-packages="otherBranchesPackages"
            :pending-catalog-package-id="pendingCatalogPackageId"
            @close="closePackageModal"
            @saved="handlePackageSaved"
            @open-new-definition="handleOpenNewPackageDefinition"
        />

        <!-- New Package Definition Modal -->
        <NewPackageDefinitionModal
            :show="showNewPackageDefinitionModal"
            :available-services="availableServices"
            @close="showNewPackageDefinitionModal = false"
            @saved="handleNewPackageDefinitionSaved"
        />

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
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import ServiceFormModal from '@/Components/ServiceFormModal.vue';
import NewServiceDefinitionModal from '@/Components/NewServiceDefinitionModal.vue';
import PackageFormModal from '@/Components/PackageFormModal.vue';
import NewPackageDefinitionModal from '@/Components/NewPackageDefinitionModal.vue';
import ServiceQuickEditModal from '@/Components/ServiceQuickEditModal.vue';
import DepartmentFormModal from '@/Components/DepartmentFormModal.vue';
import ConditionItemFormModal from '@/Components/ConditionItemFormModal.vue';
import ConditionCategoryFormModal from '@/Components/ConditionCategoryFormModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useLocalized } from '@/Composables/useLocalized';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { confirm } = useConfirm();
const { success, error } = useToast();
const { getName } = useLocalized();
const { can, isAnyAdmin } = usePermission();
const { toEnglish } = useNumberFormat();

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
    otherBranchesServices: {
        type: Array,
        default: () => [],
    },
    otherBranchesPackages: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'services');
const packagesViewMode = ref('list');

watch(activeTab, (newTab) => {
    const url = new URL(window.location);
    url.searchParams.set('tab', newTab);
    window.history.replaceState({}, '', url);
});

const mainTabs = computed(() => [
    {
        id: 'services',
        label: t('services_management.title'),
        count: totalServices.value,
    },
    {
        id: 'packages',
        label: t('packages.title'),
        count: props.packages?.length || 0,
    },
    {
        id: 'inspections',
        label: t('services_management.inspections'),
        count:
            props.conditionCategories?.reduce((acc, cat) => acc + (cat.items?.length || 0), 0) || 0,
    },
]);

const packageColumns = computed(() => [
    { key: 'number', label: t('services_management.columns.number'), class: 'w-16 text-center' },
    { key: 'name', label: t('services_management.columns.name') },
    { key: 'base_price', label: t('services_management.columns.base_price') },
    { key: 'items', label: t('packages.items') },
    { key: 'updated_by', label: t('services_management.columns.updated_by') },
    { key: 'is_active', label: t('common.status'), class: 'w-24 text-center' },
    { key: 'actions', label: t('services_management.columns.actions'), class: 'w-28 text-center' },
]);
const showModal = ref(false);
const selectedService = ref(null);
const currentDepartment = ref(null);
const pendingCatalogServiceId = ref(null); // auto-selects newly created service in ServiceFormModal
const searchQuery = ref('');

// New Service Definition modal state (separate modal for permission gating)
const showNewDefinitionModal = ref(false);
const newDefinitionDepartmentId = ref(null);
const collapsedDepts = ref(new Set());

// Department modal state
const showDepartmentModal = ref(false);
const selectedDepartment = ref(null);

// Quick edit modal state
const showQuickEditModal = ref(false);
const selectedServiceForQuickEdit = ref(null);

// Package modal state
const showPackageModal = ref(false);
const selectedPackage = ref(null);
const showNewPackageDefinitionModal = ref(false);
const pendingCatalogPackageId = ref(null);

// Inspection modal state
const showInspectionModal = ref(false);
const selectedInspection = ref(null);
const selectedCategory = ref(null);

const showCategoryModal = ref(false);

// Load collapsed departments from localStorage on mount
onMounted(() => {
    const savedState = localStorage.getItem('collapsed_departments');
    if (savedState) {
        try {
            collapsedDepts.value = new Set(JSON.parse(savedState));
        } catch (e) {
            console.error('Error parsing collapsed departments state', e);
        }
    }
});

const totalServices = computed(() => {
    let count = props.unassignedServices?.length || 0;
    props.departments.forEach((dept) => {
        count += dept.services?.length || 0;
    });
    return count;
});

const filteredDepartments = computed(() => {
    if (!searchQuery.value) return props.departments;

    const query = searchQuery.value.toLowerCase();
    return props.departments.filter(
        (dept) =>
            dept.name_ar?.toLowerCase().includes(query) ||
            dept.name_en?.toLowerCase().includes(query) ||
            dept.services?.some(
                (s) =>
                    s.name_ar?.toLowerCase().includes(query) ||
                    s.name_en?.toLowerCase().includes(query)
            )
    );
});

const filteredPackages = computed(() => {
    if (!searchQuery.value) return props.packages;

    const query = searchQuery.value.toLowerCase();
    return props.packages.filter(
        (pkg) =>
            pkg.name_ar?.toLowerCase().includes(query) || pkg.name_en?.toLowerCase().includes(query)
    );
});

const filteredConditionCategories = computed(() => {
    if (!props.conditionCategories) return [];

    let result = [...props.conditionCategories];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        // Filter categories that match search or have items that match search
        result = result.filter((cat) => {
            const catMatch =
                (cat.name_ar && cat.name_ar.toLowerCase().includes(query)) ||
                (cat.name_en && cat.name_en.toLowerCase().includes(query));
            const itemMatch =
                cat.items &&
                cat.items.some(
                    (item) =>
                        (item.name_ar && item.name_ar.toLowerCase().includes(query)) ||
                        (item.name_en && item.name_en.toLowerCase().includes(query))
                );
            return catMatch || itemMatch;
        });

        // Also map the categories to only include matching items if the category itself doesn't match
        result = result.map((cat) => {
            const catMatch =
                (cat.name_ar && cat.name_ar.toLowerCase().includes(query)) ||
                (cat.name_en && cat.name_en.toLowerCase().includes(query));

            if (catMatch) return cat; // If category matches, show all its items

            return {
                ...cat,
                items: cat.items
                    ? cat.items.filter(
                          (item) =>
                              (item.name_ar && item.name_ar.toLowerCase().includes(query)) ||
                              (item.name_en && item.name_en.toLowerCase().includes(query))
                      )
                    : [],
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
        gradientTo: '#059669', // emerald-600
        count: totalServices.value,
    },
    {
        key: 'packages',
        label: t('packages.title'),
        iconColor: 'text-indigo-500',
        bgColor: 'bg-indigo-100 dark:bg-indigo-900/30',
        gradientFrom: '#6366f1', // indigo-500
        gradientTo: '#2563eb', // indigo-600
        count: props.packages?.length || 0,
    },
    {
        key: 'inspections',
        label: t('services_management.inspections'),
        iconColor: 'text-amber-500',
        bgColor: 'bg-amber-100 dark:bg-amber-900/30',
        gradientFrom: '#f59e0b', // amber-500
        gradientTo: '#d97706', // amber-600
        count:
            props.conditionCategories?.reduce((acc, cat) => acc + (cat.items?.length || 0), 0) || 0,
    },
]);

const headerConfig = computed(() => {
    const activeTabData = tabs.value.find((t) => t.key === activeTab.value) || tabs.value[0];
    return {
        ...activeTabData,
        countLabel:
            activeTab.value === 'services'
                ? t('services_management.services')
                : activeTab.value === 'packages'
                  ? t('packages.items')
                  : t('services_management.inspections'),
        glowFrom:
            activeTab.value === 'services'
                ? 'from-teal-600'
                : activeTab.value === 'packages'
                  ? 'from-indigo-600'
                  : 'from-amber-600',
        glowTo:
            activeTab.value === 'services'
                ? 'to-emerald-600'
                : activeTab.value === 'packages'
                  ? 'to-purple-600'
                  : 'to-orange-600',
        badgeBg:
            activeTab.value === 'services'
                ? 'bg-teal-50/50 dark:bg-teal-900/30'
                : activeTab.value === 'packages'
                  ? 'bg-indigo-50/50 dark:bg-indigo-900/30'
                  : 'bg-amber-50/50 dark:bg-amber-900/30',
        badgeText:
            activeTab.value === 'services'
                ? 'text-teal-600 dark:text-teal-400'
                : activeTab.value === 'packages'
                  ? 'text-indigo-600 dark:text-indigo-400'
                  : 'text-amber-600 dark:text-amber-400',
        badgeBorder:
            activeTab.value === 'services'
                ? 'border-teal-100/50 dark:border-teal-800/30'
                : activeTab.value === 'packages'
                  ? 'border-indigo-100/50 dark:border-indigo-800/30'
                  : 'border-amber-100/50 dark:border-amber-800/30',
        badgeDot:
            activeTab.value === 'services'
                ? 'bg-teal-500'
                : activeTab.value === 'packages'
                  ? 'bg-indigo-500'
                  : 'bg-amber-500',
    };
});

const availableServices = computed(() => {
    // Collect all services from departments
    const services = [];
    props.departments.forEach((dept) => {
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
    if (collapsedDepts.value.has(id)) {
        collapsedDepts.value.delete(id);
    } else {
        collapsedDepts.value.add(id);
    }
    localStorage.setItem('collapsed_departments', JSON.stringify(Array.from(collapsedDepts.value)));
}

function openCreateModal() {
    if (props.departments.length === 0) {
        error(t('services_management.add_department_first'));
        return;
    }
    selectedService.value = null;
    showModal.value = true;
}

function openCreateModalForDepartment(dept) {
    if (props.departments.length === 0) {
        error(t('services_management.add_department_first'));
        return;
    }
    currentDepartment.value = dept;
    selectedService.value = {
        department_id: dept.id,
    };
    showModal.value = true;
}

function openEditModal(service) {
    selectedService.value = service;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedService.value = null;
    currentDepartment.value = null;
    pendingCatalogServiceId.value = null;
}

function handleSaved() {
    pendingCatalogServiceId.value = null;
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments', 'unassignedServices', 'packages'] });
}

// Helper to find a service by ID in the reloaded props
function findServiceById(id) {
    if (!id) return null;
    // Search in departments
    for (const dept of props.departments) {
        if (dept.services) {
            const found = dept.services.find((s) => s.id === Number(id) || s.id === String(id));
            if (found) return found;
        }
    }
    // Search in unassigned
    return props.unassignedServices.find((s) => s.id === Number(id) || s.id === String(id)) || null;
}

// New definition modal handlers
function openNewDefinitionModal() {
    // Keep department ID context
    newDefinitionDepartmentId.value =
        currentDepartment.value?.id || selectedService.value?.department_id || null;
    // Close the pricing/catalog modal so they don't overlap
    showModal.value = false;
    showNewDefinitionModal.value = true;
}

function closeNewDefinitionModal() {
    showNewDefinitionModal.value = false;
    newDefinitionDepartmentId.value = null;
    // Re-open the pricing/catalog modal so the user doesn't lose context
    showModal.value = true;
}

function handleNewDefinitionSaved(newServiceId) {
    showNewDefinitionModal.value = false;
    newDefinitionDepartmentId.value = null;

    // Reload the services data to include the newly created service
    router.reload({
        only: ['departments', 'unassignedServices', 'otherBranchesServices'],
        onSuccess: () => {
            if (newServiceId) {
                const foundService = findServiceById(newServiceId);
                if (foundService) {
                    // Open ServiceFormModal in edit mode to complete the pricing
                    selectedService.value = foundService;
                    showModal.value = true;
                } else {
                    showModal.value = true;
                }
            } else {
                showModal.value = true;
            }
            success(t('common.saved_success'));
        },
    });
}

function toggleServiceActive(service) {
    router.patch(
        `/app/services/${service.id}/toggle-active`,
        {},
        {
            preserveScroll: true,
        }
    );
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
    pendingCatalogPackageId.value = null;
    showPackageModal.value = true;
}

function openEditPackageModal(pkg) {
    selectedPackage.value = pkg;
    showPackageModal.value = true;
}

function closePackageModal() {
    showPackageModal.value = false;
    selectedPackage.value = null;
    pendingCatalogPackageId.value = null;
}

function handlePackageSaved() {
    closePackageModal();
    success(t('common.saved_success'));
    activeTab.value = 'packages';
    router.reload({
        only: ['departments', 'unassignedServices', 'packages', 'otherBranchesPackages'],
    });
}

function handleOpenNewPackageDefinition() {
    showPackageModal.value = false;
    showNewPackageDefinitionModal.value = true;
}

function handleNewPackageDefinitionSaved(newPackageId) {
    showNewPackageDefinitionModal.value = false;

    // Reload the services and packages list to include the new definition
    router.reload({
        only: ['departments', 'unassignedServices', 'packages', 'otherBranchesPackages'],
        onSuccess: () => {
            if (newPackageId) {
                const foundPackage = props.packages.find(
                    (p) => p.id === Number(newPackageId) || p.id === String(newPackageId)
                );
                if (foundPackage) {
                    // Open PackageFormModal in edit mode to complete the pricing
                    selectedPackage.value = foundPackage;
                    showPackageModal.value = true;
                } else {
                    showPackageModal.value = true;
                }
            } else {
                showPackageModal.value = true;
            }
            success(t('common.saved_success'));
        },
    });
}
// Inspection Methods
const toggleInspectionsSetting = () => {
    router.post(
        route('app.services.toggle-inspections-setting'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                success(t('common.updated_success'));
            },
        }
    );
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

    router.patch(
        route('app.condition-categories.toggle', category.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
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
        },
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

    router.patch(
        route('app.condition-items.toggle', item.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
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
