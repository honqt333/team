<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- Back Button -->
            <Link
                :href="route('app.quotes.index')"
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
                <div class="bg-gradient-to-r from-amber-500/10 via-orange-500/5 to-transparent dark:from-amber-900/30 dark:via-orange-900/10 p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                        <!-- Vehicle Info -->
                        <div class="flex items-center gap-4 flex-1">
                            <!-- Vehicle Logo -->
                            <div class="w-16 h-16 rounded-2xl bg-white dark:bg-gray-700 shadow-sm flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="quote.vehicle?.make?.logo_path"
                                    :src="`/storage/${quote.vehicle.make.logo_path}`"
                                    :alt="getName(quote.vehicle.make)"
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
                                        {{ quote.vehicle?.plate_number }}
                                    </h2>
                                    <span class="text-gray-500 dark:text-gray-400">•</span>
                                    <span class="text-gray-600 dark:text-gray-300">
                                        {{ getName(quote.vehicle?.make) }} {{ getName(quote.vehicle?.model) }} {{ quote.vehicle?.year }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ quote.customer?.name }}
                                    </span>
                                    <span class="flex items-center gap-1" dir="ltr">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ quote.customer?.phone }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Quote Code & Status -->
                        <div class="flex items-center gap-4">
                            <div class="text-end">
                                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ quote.code }}</p>
                                <span :class="statusBadgeClass">{{ $t(`quotes.status.${quote.status}`) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <!-- Edit Button -->
                    <button
                        v-if="quote.status === 'draft'"
                        @click="openEditModal"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ $t('common.edit') }}
                    </button>

                    <!-- Delete Button -->
                    <button
                        v-if="quote.status === 'draft'"
                        @click="deleteQuote"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ $t('common.delete') }}
                    </button>

                    <!-- Print Button -->
                    <button
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        {{ $t('quotes.show.print') }}
                    </button>

                    <!-- Share Dropdown -->
                    <div class="relative">
                        <button
                            @click="showShareMenu = !showShareMenu"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            {{ $t('quotes.show.share') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <!-- Share Menu -->
                        <div
                            v-if="showShareMenu"
                            class="absolute z-50 mt-2 start-0 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1"
                        >
                            <button class="w-full px-4 py-2 text-start text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed flex items-center gap-2">
                                <span>📧</span> Email ({{ $t('common.coming_soon') }})
                            </button>
                            <button class="w-full px-4 py-2 text-start text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed flex items-center gap-2">
                                <span>💬</span> WhatsApp ({{ $t('common.coming_soon') }})
                            </button>
                            <button class="w-full px-4 py-2 text-start text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed flex items-center gap-2">
                                <span>🔗</span> {{ $t('quotes.show.copy_link') }} ({{ $t('common.coming_soon') }})
                            </button>
                        </div>
                    </div>

                    <div class="flex-1"></div>

                    <!-- Status Actions -->
                    <template v-if="quote.status === 'draft' || quote.status === 'sent'">
                        <button
                            @click="approveQuote"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg hover:from-green-600 hover:to-emerald-600 shadow-sm transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $t('quotes.actions.approve') }}
                        </button>
                        <button
                            @click="rejectQuote"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-rose-500 rounded-lg hover:from-red-600 hover:to-rose-600 shadow-sm transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            {{ $t('quotes.actions.reject') }}
                        </button>
                    </template>
                </div>

                <!-- Cost Summary -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('quotes.show.subtotal') }}</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatPrice(quote.subtotal) }}</p>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('quotes.show.total_discount') }}</p>
                            <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ formatPrice(quote.total_discount) }}</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('quotes.show.net_total') }}</p>
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatPrice(quote.total) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="px-6 pt-4">
                    <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                        <button
                            @click="activeTab = 'services'"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-t-lg transition-colors -mb-px',
                                activeTab === 'services'
                                    ? 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-800 text-amber-600 dark:text-amber-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                            ]"
                        >
                            🔧 {{ $t('quotes.show.tabs.services') }} ({{ quote.lines?.length || 0 }})
                        </button>
                        <button
                            @click="activeTab = 'parts'"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-t-lg transition-colors -mb-px',
                                activeTab === 'parts'
                                    ? 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-800 text-amber-600 dark:text-amber-400'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                            ]"
                        >
                            🔩 {{ $t('quotes.show.tabs.spare_parts') }} (0)
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
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors"
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
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                            <span class="text-amber-600 dark:text-amber-400">🔧</span>
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ getName(dept) }}</span>
                                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                            {{ getDepartmentLines(dept.id).length }} {{ $t('quotes.show.services_count') }}
                                        </span>
                                    </button>
                                    
                                    <div class="flex items-center gap-2">
                                        <!-- Delete Department Button (only if empty) -->
                                        <button
                                            v-if="quote.status === 'draft' && getDepartmentLines(dept.id).length === 0"
                                            @click.stop="removeDepartment(dept.id)"
                                            class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 flex items-center justify-center transition-colors"
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
                                    <div
                                        v-for="line in getDepartmentLines(dept.id)"
                                        :key="line.id"
                                        class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 hover:shadow-sm transition-shadow"
                                    >
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ line.description || getName(line.service) }}</p>
                                            <div class="flex items-center gap-4 mt-1 text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">{{ formatPrice(line.unit_price) }}</span>
                                                <span v-if="line.discount_amount > 0" class="text-red-500 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                    </svg>
                                                    -{{ formatPrice(line.discount_amount) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button
                                                @click="openEditServiceModal(line)"
                                                class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteServiceLine(line)"
                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Empty State -->
                                    <p
                                        v-if="getDepartmentLines(dept.id).length === 0"
                                        class="text-center text-gray-400 dark:text-gray-500 py-4 text-sm"
                                    >
                                        {{ $t('quotes.show.no_services') }}
                                    </p>

                                    <!-- Add Service Button -->
                                    <button
                                        @click="openAddServiceModal(dept.id)"
                                        class="w-full flex items-center justify-center gap-2 py-2 text-sm text-amber-600 dark:text-amber-400 border border-dashed border-amber-300 dark:border-amber-700 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
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
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('quotes.show.no_departments') }}</p>
                        </div>
                    </div>

                    <!-- Spare Parts Tab -->
                    <div v-show="activeTab === 'parts'" class="text-center py-12">
                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">🔩</span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Modal -->
        <QuoteServiceModal
            v-if="showServiceModal"
            :show="showServiceModal"
            :quote="quote"
            :line="selectedLine"
            :department-id="selectedDepartmentId"
            :services="departmentServices"
            @close="closeServiceModal"
            @saved="handleServiceSaved"
        />

        <!-- Edit Quote Modal -->
        <QuoteFormModal
            v-if="showEditModal"
            :show="showEditModal"
            :quote="quote"
            :customers="[]"
            :departments="departments"
            :makes="[]"
            :colors="[]"
            :modelsByMake="{}"
            @close="showEditModal = false"
            @saved="handleQuoteSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuoteServiceModal from '@/Components/Quotes/QuoteServiceModal.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';

const props = defineProps({
    quote: Object,
    linesByDepartment: Object,
    departments: Array,
    services: Array,
    quoteDepartments: Array,
});

const { t } = useI18n();
const { getName } = useLocalized();
const { success } = useToast();
const { confirm } = useConfirm();
const { formatCurrency } = useNumberFormat();

// State
const activeTab = ref('services');
const expandedDepartments = ref([]);
const showServiceModal = ref(false);
const showShareMenu = ref(false);
const showDeptMenu = ref(false);
const selectedLine = ref(null);
const selectedDepartmentId = ref(null);
const showEditModal = ref(false);

// Computed
const displayDepartments = computed(() => {
    // Combine quoteDepartments with departments that have lines
    const deptIds = new Set(props.quoteDepartments?.map(d => d.id) || []);
    
    // Also add departments that have lines
    if (props.linesByDepartment) {
        Object.keys(props.linesByDepartment).forEach(id => {
            if (id !== '0') deptIds.add(parseInt(id));
        });
    }
    
    return props.departments.filter(d => deptIds.has(d.id));
});

const availableDepartments = computed(() => {
    const existingIds = new Set(displayDepartments.value.map(d => d.id));
    return props.departments.filter(d => !existingIds.has(d.id));
});

const departmentServices = computed(() => {
    if (!selectedDepartmentId.value) return [];
    return props.services.filter(s => s.department_id === selectedDepartmentId.value);
});

const statusBadgeClass = computed(() => {
    const baseClass = 'inline-block px-2 py-1 text-xs font-medium rounded-full mt-1';
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        converted: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    };
    return `${baseClass} ${classes[props.quote.status] || classes.draft}`;
});

// Methods
function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

function toggleDepartment(deptId) {
    const index = expandedDepartments.value.indexOf(deptId);
    if (index === -1) {
        expandedDepartments.value.push(deptId);
    } else {
        expandedDepartments.value.splice(index, 1);
    }
}

function getDepartmentLines(deptId) {
    return props.linesByDepartment?.[deptId] || [];
}

function addDepartment(deptId) {
    showDeptMenu.value = false;
    router.post(route('app.quotes.departments.store', props.quote.id), {
        department_id: deptId,
    }, {
        onSuccess: () => {
            success(t('common.saved_success'));
            // Auto-expand the new department
            if (!expandedDepartments.value.includes(deptId)) {
                expandedDepartments.value.push(deptId);
            }
        },
    });
}

async function removeDepartment(deptId) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('quotes.messages.confirm_remove_department'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('app.quotes.departments.destroy', { quote: props.quote.id, department: deptId }), {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}

function openAddServiceModal(deptId) {
    selectedDepartmentId.value = deptId;
    selectedLine.value = null;
    showServiceModal.value = true;
}

function openEditServiceModal(line) {
    selectedDepartmentId.value = line.service?.department_id;
    selectedLine.value = line;
    showServiceModal.value = true;
}

function closeServiceModal() {
    showServiceModal.value = false;
    selectedLine.value = null;
    selectedDepartmentId.value = null;
}

function handleServiceSaved() {
    closeServiceModal();
    success(t('common.saved_success'));
    router.reload({ only: ['quote', 'linesByDepartment', 'quoteDepartments'] });
}

async function deleteServiceLine(line) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${line.description || getName(line.service)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('app.quotes.services.destroy', { quote: props.quote.id, line: line.id }), {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}

function openEditModal() {
    showEditModal.value = true;
}

function handleQuoteSaved() {
    showEditModal.value = false;
    router.reload();
}

async function approveQuote() {
    const confirmed = await confirm({
        title: t('quotes.actions.approve'),
        message: t('quotes.messages.confirm_approve'),
        confirmText: t('quotes.actions.approve'),
        cancelText: t('common.cancel'),
        type: 'success',
    });

    if (confirmed) {
        router.post(route('app.quotes.approve', props.quote.id), {}, {
            onSuccess: () => {
                success(t('quotes.messages.approved_success'));
            },
        });
    }
}

async function rejectQuote() {
    const confirmed = await confirm({
        title: t('quotes.actions.reject'),
        message: t('quotes.messages.confirm_reject'),
        confirmText: t('quotes.actions.reject'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.post(route('app.quotes.reject', props.quote.id), {}, {
            onSuccess: () => {
                success(t('quotes.messages.rejected_success'));
            },
        });
    }
}

async function deleteQuote() {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('quotes.messages.confirm_delete'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('app.quotes.destroy', props.quote.id), {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}

// Close menus when clicking outside
function closeMenus(e) {
    if (!e.target.closest('.relative')) {
        showShareMenu.value = false;
        showDeptMenu.value = false;
    }
}
</script>
