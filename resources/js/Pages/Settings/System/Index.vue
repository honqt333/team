<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Back Button & Header Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Back Button -->
                        <Link href="/app/settings"
                            class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            {{ $t('common.back') }}
                        </Link>

                        <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>

                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $t('system_settings.title') }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ currentSectionTitle }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation - Separate Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-2 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-1 flex-wrap">

                    <button @click="navigateToSection('makes')" :class="[
                        'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                        activeSection === 'makes'
                            ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        {{ $t('system_settings.sections.makes') }}
                    </button>
                    <button @click="navigateToSection('models')" :class="[
                        'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                        activeSection === 'models'
                            ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        {{ $t('system_settings.sections.models') }}
                    </button>
                    <button @click="navigateToSection('colors')" :class="[
                        'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                        activeSection === 'colors'
                            ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                        {{ $t('system_settings.sections.colors') }}
                    </button>
                    <button @click="navigateToSection('condition-items')" :class="[
                        'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                        activeSection === 'condition-items'
                            ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ $t('system_settings.sections.condition_items') }}
                    </button>

                    <span
                        class="flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium text-gray-400 dark:text-gray-500 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $t('system_settings.sections.service_types') }}
                        <span class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{
                            $t('common.coming_soon') }}</span>
                    </span>
                </div>
            </div>


            <!-- Makes/Models/Colors/Condition Items Section -->
            <div v-if="['makes', 'models', 'colors', 'condition-items'].includes(activeSection)" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                <!-- Toolbar -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="search" :placeholder="$t('common.search')" @input="handleSearch"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
                        </div>

                        <!-- Make Filter (for models only) -->
                        <div v-if="activeSection === 'models'" class="w-64">
                            <SearchableSelect v-model="selectedMake" :options="makeOptions" option-label="label"
                                option-value="value" :placeholder="$t('system_settings.models.all_makes')" :label="''"
                                @change="handleMakeFilter" :compact="false" />
                        </div>

                        <!-- Add Button -->
                        <button @click="openAddModal"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ $t('common.add') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <!-- Makes Table -->
                    <table v-if="activeSection === 'makes'" class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.logo') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_ar') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_en') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.is_active') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.updated_by') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="make in makesData" :key="make.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div v-if="make.logo_path"
                                        class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-1 border border-gray-200 dark:border-gray-600">
                                        <img :src="`/storage/${make.logo_path}`" class="w-full h-full object-contain" />
                                    </div>
                                    <div v-else
                                        class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 font-bold border border-gray-200 dark:border-gray-600">
                                        {{ make.name_ar.charAt(0) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ make.name_ar }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ make.name_en || '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button v-if="make.source !== 'system'" @click="toggleActive('makes', make)" :class="[
                                        'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                                        make.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                    ]">
                                        {{ make.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </button>
                                    <span v-else :class="[
                                        'px-3 py-1 text-xs font-medium rounded-full',
                                        make.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                    ]">
                                        {{ make.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="make.source === 'system'"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                        {{ $t('common.system') }}
                                    </span>
                                    <div v-else class="flex flex-col">
                                        <span>{{ make.updated_by ? make.updated_by.name : '—' }}</span>
                                        <span v-if="make.updated_at" class="text-xs text-gray-400">{{ new
                                            Date(make.updated_at).toLocaleDateString('ar-SA') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="make.source !== 'system'" class="flex items-center justify-center gap-2">
                                        <button @click="openEditModal('make', make)"
                                            class="p-2 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="handleDelete('makes', make)"
                                            class="p-2 text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                </td>
                            </tr>
                            <tr v-if="!makesData?.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('common.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Models Table -->
                    <table v-else-if="activeSection === 'models'" class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_ar') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_en') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.make') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.is_active') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.updated_by') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="model in modelsData" :key="model.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ model.name_ar }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ model.name_en || '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ model.make ? getName(model.make) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button v-if="model.source !== 'system'" @click="toggleActive('models', model)"
                                        :class="[
                                            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                                            model.is_active
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                        ]">
                                        {{ model.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </button>
                                    <span v-else :class="[
                                        'px-3 py-1 text-xs font-medium rounded-full',
                                        model.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                    ]">
                                        {{ model.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="model.source === 'system'"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                        {{ $t('common.system') }}
                                    </span>
                                    <div v-else class="flex flex-col">
                                        <span>{{ model.updated_by ? model.updated_by.name : '—' }}</span>
                                        <span v-if="model.updated_at" class="text-xs text-gray-400">{{ new
                                            Date(model.updated_at).toLocaleDateString('ar-SA') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="model.source !== 'system'"
                                        class="flex items-center justify-center gap-2">
                                        <button @click="openEditModal('model', model)"
                                            class="p-2 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="handleDelete('models', model)"
                                            class="p-2 text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                </td>
                            </tr>
                            <tr v-if="!modelsData?.length">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('common.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Colors Table -->
                    <table v-else-if="activeSection === 'colors'" class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.color') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_ar') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_en') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.is_active') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.updated_by') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="color in colorsData" :key="color.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg border-2 border-gray-200 dark:border-gray-600 shadow-inner"
                                            :style="{ backgroundColor: color.hex_code || '#CCCCCC' }"></div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{
                                            color.hex_code || '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ color.name_ar }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ color.name_en || '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button v-if="color.source !== 'system'" @click="toggleActive('colors', color)"
                                        :class="[
                                            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                                            color.is_active
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                        ]">
                                        {{ color.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </button>
                                    <span v-else :class="[
                                        'px-3 py-1 text-xs font-medium rounded-full',
                                        color.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                    ]">
                                        {{ color.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="color.source === 'system'"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                        {{ $t('common.system') }}
                                    </span>
                                    <div v-else class="flex flex-col">
                                        <span>{{ color.updated_by ? color.updated_by.name : '—' }}</span>
                                        <span v-if="color.updated_at" class="text-xs text-gray-400">{{ new
                                            Date(color.updated_at).toLocaleDateString('ar-SA') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="color.source !== 'system'"
                                        class="flex items-center justify-center gap-2">
                                        <button @click="openEditModal('color', color)"
                                            class="p-2 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="handleDelete('colors', color)"
                                            class="p-2 text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                </td>
                            </tr>
                            <tr v-if="!colorsData?.length">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('common.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Condition Items Table -->
                <div v-if="activeSection === 'condition-items'" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_ar') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.name_en') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.is_active') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('system_settings.columns.updated_by') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in conditionItemsData" :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ item.name_ar }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ item.name_en || '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button v-if="item.source !== 'system'"
                                        @click="toggleActive('condition-items', item)" :class="[
                                            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                                            item.is_active
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                        ]">
                                        {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </button>
                                    <span v-else :class="[
                                        'px-3 py-1 text-xs font-medium rounded-full',
                                        item.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                    ]">
                                        {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="item.source === 'system'"
                                        class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                        {{ $t('common.system') }}
                                    </span>
                                    <div v-else class="flex flex-col">
                                        <span>{{ item.updated_by ? item.updated_by.name : '—' }}</span>
                                        <span v-if="item.updated_at" class="text-xs text-gray-400">{{ new
                                            Date(item.updated_at).toLocaleDateString('ar-SA') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="item.source !== 'system'" class="flex items-center justify-center gap-2">
                                        <button @click="openEditModal('condition_item', item)"
                                            class="p-2 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="handleDelete('condition-items', item)"
                                            class="p-2 text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                </td>
                            </tr>
                            <tr v-if="!conditionItemsData?.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('common.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="currentPagination?.links" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-center gap-1">
                        <template v-for="link in currentPagination.links" :key="link.label">
                            <a v-if="link.url" :href="link.url" :class="[
                                'px-3 py-1.5 text-sm rounded-lg transition-colors',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                            ]" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

        </div>

<!-- Modals -->
        <MakeFormModal v-if="showMakeModal" :make="editingMake" @close="closeMakeModal" @saved="handleSaved" />

        <ModelFormModal v-if="showModelModal" :model="editingModel" :makes="makesForFilter" @close="closeModelModal"
            @saved="handleSaved" />

        <ColorFormModal v-if="showColorModal" :color="editingColor" @close="closeColorModal" @saved="handleSaved" />

        <ConditionItemFormModal v-if="showConditionItemModal" :condition-item="editingConditionItem"
            @close="closeConditionItemModal" @saved="handleSaved" />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MakeFormModal from './Modals/MakeFormModal.vue';
import ModelFormModal from './Modals/ModelFormModal.vue';
import ColorFormModal from './Modals/ColorFormModal.vue';
import ConditionItemFormModal from './Modals/ConditionItemFormModal.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalized } from '@/Composables/useLocalized';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();
const { success } = useToast();
const { confirm } = useConfirm();
const { getName } = useLocalized();

const props = defineProps({
    makes: [Object, Array],
    models: Object,
    colors: Object,
    condition_items: Object,
    settings: Object,
    global_sms_enabled: Boolean,

    activeSection: {
        type: String,
        default: 'makes'
    },
    filters: Object
});

// Search & Filters
const search = ref(props.filters?.search || '');
const selectedMake = ref(props.filters?.make_id ? Number(props.filters.make_id) : '');

// Sync local state with props (e.g. browser back/forward)
watch(() => props.filters?.make_id, (newVal) => {
    selectedMake.value = newVal ? Number(newVal) : '';
});

const globalSmsEnabled = computed(() => props.global_sms_enabled);



// Modal state
const showMakeModal = ref(false);
const showModelModal = ref(false);
const showColorModal = ref(false);
const editingMake = ref(null);
const editingModel = ref(null);
const editingColor = ref(null);
const showConditionItemModal = ref(false);
const editingConditionItem = ref(null);

// Computed data
const makesData = computed(() => props.makes?.data || props.makes || []);
const modelsData = computed(() => props.models?.data || []);
const colorsData = computed(() => props.colors?.data || []);
const conditionItemsData = computed(() => props.condition_items?.data || []);
const makesForFilter = computed(() => props.makes?.data || props.makes || []);

const makeOptions = computed(() => {
    const allOption = { value: '', label: t('system_settings.models.all_makes') };
    const mappedMakes = makesForFilter.value.map(make => ({
        value: make.id,
        label: getName(make)
    }));
    return [allOption, ...mappedMakes];
});

const currentSectionTitle = computed(() => {
    const section = props.activeSection || 'makes';
    // Mapping for general
    if (section === 'general') return 'الإعدادات العامة';
    if (section === 'condition-items') return t('system_settings.sections.condition_items');
    return t(`system_settings.${section}.subtitle`) || t(`system_settings.sections.${section}`);
});

const currentPagination = computed(() => {
    if (props.activeSection === 'models') return props.models;
    if (props.activeSection === 'colors') return props.colors;
    if (props.activeSection === 'condition-items') return props.condition_items;
    return props.makes;
});

// Handlers


function handleSearch() {
    const params = {
        search: search.value || undefined
    };
    if (props.activeSection === 'models') {
        params.make_id = selectedMake.value || undefined;
    }
    router.get(`/app/settings/${props.activeSection}`, params, { preserveState: true });
}

function handleMakeFilter() {
    handleSearch();
}

function navigateToSection(section) {
    router.get(`/app/settings/${section}`, {}, { preserveState: false });
}

function openAddModal() {
    if (props.activeSection === 'makes') {
        editingMake.value = null;
        showMakeModal.value = true;
    } else if (props.activeSection === 'models') {
        editingModel.value = null;
        showModelModal.value = true;
    } else if (props.activeSection === 'colors') {
        editingColor.value = null;
        showColorModal.value = true;
    } else if (props.activeSection === 'condition-items') {
        editingConditionItem.value = null;
        showConditionItemModal.value = true;
    }
}

function openEditModal(type, item) {
    if (type === 'make') {
        editingMake.value = item;
        showMakeModal.value = true;
    } else if (type === 'model') {
        editingModel.value = item;
        showModelModal.value = true;
    } else if (type === 'color') {
        editingColor.value = item;
        showColorModal.value = true;
    } else if (type === 'condition_item') {
        editingConditionItem.value = item;
        showConditionItemModal.value = true;
    }
}

function closeMakeModal() {
    showMakeModal.value = false;
    editingMake.value = null;
}

function closeModelModal() {
    showModelModal.value = false;
    editingModel.value = null;
}

function closeColorModal() {
    showColorModal.value = false;
    editingColor.value = null;
}

function closeConditionItemModal() {
    showConditionItemModal.value = false;
    editingConditionItem.value = null;
}

function handleSaved() {
    closeMakeModal();
    closeModelModal();
    closeColorModal();
    closeConditionItemModal();
    success(t('common.saved_success'));
    router.reload();
}

function toggleActive(section, item) {
    router.patch(`/app/settings/${section}/${item.id}/toggle-active`);
}

async function handleDelete(section, item) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(item)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(`/app/settings/${section}/${item.id}`, {
            onSuccess: () => {
                success(t('common.deleted_success'));
            }
        });
    }
}
</script>
