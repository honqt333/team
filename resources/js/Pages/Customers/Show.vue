<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- Back Button -->
            <Link
                :href="route('customers.index')"
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
                <div class="bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-transparent dark:from-emerald-900/30 dark:via-teal-900/10 p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                        <!-- Customer Info -->
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 shadow-lg flex items-center justify-center">
                                <span class="text-2xl font-bold text-white">
                                    {{ customer.name?.charAt(0)?.toUpperCase() || '?' }}
                                </span>
                            </div>
                            
                            <div class="flex-1">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ customer.name }}
                                </h2>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1" dir="ltr">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        {{ customer.phone }}
                                    </span>
                                    <a v-if="customer.whatsapp" :href="`https://wa.me/${getWhatsAppNumber(customer)}`" target="_blank" class="flex items-center gap-1 text-green-600 hover:text-green-700" dir="ltr">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        {{ customer.whatsapp }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4">
                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ counts.vehicles }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('customers.vehicles') }}</p>
                            </div>
                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ counts.quotes }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('customers.quotes') }}</p>
                            </div>
                            <div class="text-center px-4 py-2 bg-white/50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ counts.workOrders }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('customers.work_orders') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <button
                        @click="showEditModal = true"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ $t('common.edit') }}
                    </button>

                    <!-- Merge Button (only for customers with data) -->
                    <button
                        v-if="canMerge"
                        @click="showMergeModal = true"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-amber-700 dark:text-amber-400 bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-700 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        {{ $t('customers.merge.button') }}
                    </button>

                    <button
                        @click="confirmDelete"
                        :disabled="!canDelete"
                        :class="[
                            'inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                            canDelete
                                ? 'text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                                : 'text-gray-400 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 cursor-not-allowed'
                        ]"
                        :title="canDelete ? '' : $t('customers.cannot_delete_has_data')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ $t('common.delete') }}
                    </button>
                </div>

                <!-- Tabs Navigation -->
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all flex-1 justify-center',
                                activeTab === tab.key
                                    ? 'bg-white dark:bg-gray-800 shadow-sm ' + tab.activeColor
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            {{ tab.label }}
                            <span class="px-1.5 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700">
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- ========== VEHICLES TAB ========== -->
                    <div v-if="activeTab === 'vehicles'" class="space-y-4">
                        <!-- Toolbar -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <!-- Search -->
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="vehicleSearch"
                                        :placeholder="$t('vehicles.search')"
                                        class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500"
                                    />
                                </div>
                                <!-- View Toggle -->
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button 
                                        @click="vehicleViewMode = 'grid'" 
                                        :class="['px-2 py-1 rounded transition-colors', vehicleViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>
                                    </button>
                                    <button 
                                        @click="vehicleViewMode = 'list'" 
                                        :class="['px-2 py-1 rounded transition-colors', vehicleViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Add Button -->
                            <button
                                @click="openVehicleModal"
                                class="flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl hover:-translate-y-0.5 transition-all"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('vehicles.add') }}
                            </button>
                        </div>

                        <!-- Grid View -->
                        <div v-if="vehicleViewMode === 'grid' && filteredVehicles.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="vehicle in filteredVehicles"
                                :key="vehicle.id"
                                @click="router.visit(route('vehicles.show', vehicle.id))"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden"
                            >
                                <!-- Background Logo Watermark -->
                                <div 
                                    v-if="vehicle.make?.logo_path"
                                    class="absolute inset-0 flex items-center justify-center opacity-[0.06] dark:opacity-[0.3] pointer-events-none select-none z-0 overflow-hidden"
                                >
                                    <img 
                                        :src="`/storage/${vehicle.make.logo_path}`" 
                                        class="w-3/4 h-3/4 object-contain grayscale dark:brightness-150 transform -rotate-12 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-700"
                                        alt=""
                                    />
                                </div>

                                <!-- Card Content -->
                                <div class="relative z-10 flex flex-col h-full">
                                    <!-- Header: Plate & Status -->
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent dark:from-gray-900/50">
                                        <!-- Plate Number Badge -->
                                        <div class="flex items-center gap-2">
                                            <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-0.5 shadow-sm">
                                                <span class="text-lg font-bold text-gray-900 dark:text-gray-100 tracking-wider font-mono">
                                                    {{ vehicle.plate_number }}
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Color Dot -->
                                        <span 
                                            v-if="vehicle.color" 
                                            class="w-4 h-4 rounded-full border-2 border-white dark:border-gray-700 shadow-sm" 
                                            :style="{ backgroundColor: getColorHex(vehicle.color) }"
                                            :title="vehicle.color"
                                        ></span>
                                    </div>

                                    <!-- Body: Vehicle Info -->
                                    <div class="p-4 flex-1">
                                        <div class="mb-3">
                                            <div class="flex items-baseline justify-between mb-1">
                                                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate">
                                                    {{ getMakeName(vehicle) || $t('common.na') }}
                                                </h3>
                                                <span v-if="vehicle.year" class="px-2 py-0.5 text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-md">
                                                    {{ vehicle.year }}
                                                </span>
                                            </div>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                                {{ getModelName(vehicle) }}
                                            </p>
                                        </div>
                                        <div v-if="vehicle.odometer" class="text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700/50 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span class="font-medium">{{ vehicle.odometer?.toLocaleString() }}</span>
                                            <span>{{ $t('common.km') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div v-else-if="vehicleViewMode === 'list' && filteredVehicles.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('vehicles.form.plate') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('vehicles.form.make') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('vehicles.form.model') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('vehicles.form.year') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="vehicle in filteredVehicles" :key="vehicle.id" @click="router.visit(route('vehicles.show', vehicle.id))" class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td class="px-4 py-3 font-bold text-gray-800 dark:text-gray-200"><span dir="ltr">{{ vehicle.plate_number }}</span></td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ getMakeName(vehicle) }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ getModelName(vehicle) }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ vehicle.year }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_vehicles') }}</p>
                            <button @click="openVehicleModal" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-medium shadow-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('vehicles.add') }}
                            </button>
                        </div>
                    </div>

                    <!-- ========== WORK ORDERS TAB ========== -->
                    <div v-if="activeTab === 'workOrders'" class="space-y-4">
                        <!-- Toolbar -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input type="text" v-model="workOrderSearch" :placeholder="$t('work_orders.search')" class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"/>
                                </div>
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button 
                                        @click="workOrderViewMode = 'grid'" 
                                        :class="['px-2 py-1 rounded transition-colors', workOrderViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>
                                    </button>
                                    <button 
                                        @click="workOrderViewMode = 'list'" 
                                        :class="['px-2 py-1 rounded transition-colors', workOrderViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Add button hidden - only showing existing work orders -->
                        </div>

                        <!-- Grid View -->
                        <div v-if="workOrderViewMode === 'grid' && filteredWorkOrders.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <Link
                                v-for="order in filteredWorkOrders"
                                :key="order.id"
                                :href="route('work-orders.show', order.id)"
                                class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ order.code }}</span>
                                    <span :class="getStatusClass(order.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`work_orders.status.${order.status}`) }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400 mb-3">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span dir="ltr" class="truncate">{{ order.vehicle?.plate_number }}</span>
                                    <span class="text-gray-400 mx-0.5">•</span>
                                    <span class="truncate">{{ getVehicleName(order.vehicle) }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                    <span class="text-xs text-gray-400">{{ formatDate(order.created_at) }}</span>
                                </div>
                            </Link>
                        </div>

                        <!-- List View -->
                        <div v-else-if="workOrderViewMode === 'list' && filteredWorkOrders.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.code') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.vehicle') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.status') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('work_orders.columns.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="order in filteredWorkOrders" :key="order.id" @click="router.visit(route('work-orders.show', order.id))" class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ order.code }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            <div class="flex flex-col">
                                                <span dir="ltr" class="text-sm font-bold w-fit text-start">{{ order.vehicle?.plate_number }}</span>
                                                <span class="text-xs text-gray-500">{{ getVehicleName(order.vehicle) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span :class="getStatusClass(order.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`work_orders.status.${order.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredWorkOrders.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_work_orders') }}</p>
                            
                        </div>
                    </div>

                    <!-- ========== QUOTES TAB ========== -->
                    <div v-if="activeTab === 'quotes'" class="space-y-4">
                        <!-- Toolbar -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input type="text" v-model="quoteSearch" :placeholder="$t('quotes.search')" class="w-full sm:w-48 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500"/>
                                </div>
                                <div class="flex rounded-lg bg-gray-100 dark:bg-gray-900 p-1">
                                    <button 
                                        @click="quoteViewMode = 'grid'" 
                                        :class="['px-2 py-1 rounded transition-colors', quoteViewMode === 'grid' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>
                                    </button>
                                    <button 
                                        @click="quoteViewMode = 'list'" 
                                        :class="['px-2 py-1 rounded transition-colors', quoteViewMode === 'list' ? 'bg-white dark:bg-gray-800 shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200']"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <!-- Add button hidden - only showing existing quotes -->
                        </div>

                        <!-- Grid View -->
                        <div v-if="quoteViewMode === 'grid' && filteredQuotes.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <Link
                                v-for="quote in filteredQuotes"
                                :key="quote.id"
                                :href="route('app.quotes.show', quote.id)"
                                class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 hover:shadow-md transition-all"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-mono font-bold text-amber-600 dark:text-amber-400">{{ quote.code }}</span>
                                    <span :class="getQuoteStatusClass(quote.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`quotes.status.${quote.status}`) }}</span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400" dir="ltr">{{ quote.vehicle?.plate_number }}</div>
                                <div class="mt-2 text-xs text-gray-400">{{ formatDate(quote.created_at) }}</div>
                            </Link>
                        </div>
                        
                        <!-- List View -->
                        <div v-else-if="quoteViewMode === 'list' && filteredQuotes.length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.code') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.vehicle') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.status') }}</th>
                                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">{{ $t('quotes.columns.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="quote in filteredQuotes" :key="quote.id" @click="router.visit(route('app.quotes.show', quote.id))" class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ quote.code }}</td>
                                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                            <div class="flex flex-col">
                                                <span dir="ltr" class="text-sm font-bold w-fit text-start">{{ quote.vehicle?.plate_number }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span :class="getQuoteStatusClass(quote.status)" class="px-2 py-0.5 text-xs font-medium rounded-full">{{ $t(`quotes.status.${quote.status}`) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(quote.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else-if="filteredQuotes.length === 0" class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ $t('customers.no_quotes') }}</p>
                            
                        </div>
                    </div>

                    <!-- ========== INVOICES TAB ========== -->
                    <div v-if="activeTab === 'invoices'" class="space-y-4">
                        <div class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('customers.invoices') }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>

                    <!-- ========== PAYMENTS TAB ========== -->
                    <div v-if="activeTab === 'payments'" class="space-y-4">
                        <div class="text-center py-12">
                            <div class="w-16 h-16 mx-auto rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('customers.payments') }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CustomerFormModal v-if="showEditModal" :show="showEditModal" :customer="customer" @close="showEditModal = false" @saved="handleCustomerSaved" />
        <VehicleFormModal v-if="showVehicleModal" :show="showVehicleModal" :vehicle="selectedVehicle" :customers="[customer]" :makes="makes" :colors="colors" :modelsByMake="modelsByMake" @close="showVehicleModal = false" @saved="handleVehicleSaved" />
        <WorkOrderFormModal v-if="showWorkOrderModal" :show="showWorkOrderModal" :customers="[customer]" :makes="makes" :colors="colors" :modelsByMake="modelsByMake" :departments="departments" @close="showWorkOrderModal = false" @saved="handleWorkOrderSaved" />
        <QuoteFormModal v-if="showQuoteModal" :show="showQuoteModal" :customers="[customer]" :makes="makes" :colors="colors" :modelsByMake="modelsByMake" :departments="departments" :services="services" @close="showQuoteModal = false" @saved="handleQuoteSaved" />
        <CustomerMergeModal v-if="showMergeModal" :show="showMergeModal" :customer="customer" :counts="counts" @close="showMergeModal = false" />
        <ConfirmModal />
    </AppLayout>
</template>

<script setup>
import { ref, computed, h, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import CustomerMergeModal from '@/Components/Customers/CustomerMergeModal.vue';

const props = defineProps({
    customer: Object,
    counts: Object,
    canDelete: Boolean,
    vehicles: Array,
    workOrders: Array,
    quotes: Array,
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const { t, locale } = useI18n();
const { confirm } = useConfirm();

// Modal states
const showEditModal = ref(false);
const showVehicleModal = ref(false);
const showWorkOrderModal = ref(false);
const showQuoteModal = ref(false);

const showMergeModal = ref(false);
const selectedVehicle = ref(null);

// Tab & view states
const activeTab = ref('vehicles');
const vehicleViewMode = ref(localStorage.getItem('customer_vehicle_view_mode') || 'grid');
const workOrderViewMode = ref(localStorage.getItem('customer_work_order_view_mode') || 'grid');
const quoteViewMode = ref(localStorage.getItem('customer_quote_view_mode') || 'grid');

// Search states
const vehicleSearch = ref('');
const workOrderSearch = ref('');
const quoteSearch = ref('');

// Persistence watchers
watch(vehicleViewMode, (val) => localStorage.setItem('customer_vehicle_view_mode', val));
watch(workOrderViewMode, (val) => localStorage.setItem('customer_work_order_view_mode', val));
watch(quoteViewMode, (val) => localStorage.setItem('customer_quote_view_mode', val));

// Icons
const CarIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z' })]);
const ClipboardIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' })]);
const DocumentIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })]);
const InvoiceIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z' })]);
const PaymentIcon = () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' })]);

const tabs = computed(() => [
    { key: 'vehicles', label: t('customers.vehicles'), count: props.counts.vehicles, icon: CarIcon, activeColor: 'text-teal-600 dark:text-teal-400' },
    { key: 'workOrders', label: t('customers.work_orders'), count: props.counts.workOrders, icon: ClipboardIcon, activeColor: 'text-indigo-600 dark:text-indigo-400' },
    { key: 'quotes', label: t('customers.quotes'), count: props.counts.quotes, icon: DocumentIcon, activeColor: 'text-amber-600 dark:text-amber-400' },
    { key: 'invoices', label: t('customers.invoices'), count: props.counts.invoices || 0, icon: InvoiceIcon, activeColor: 'text-green-600 dark:text-green-400' },
    { key: 'payments', label: t('customers.payments'), count: props.counts.payments || 0, icon: PaymentIcon, activeColor: 'text-purple-600 dark:text-purple-400' },
]);

// Filtered data
const canMerge = computed(() => props.counts.vehicles > 0 || props.counts.quotes > 0 || props.counts.workOrders > 0);
const filteredVehicles = computed(() => {
    if (!props.vehicles) return [];
    if (!vehicleSearch.value) return props.vehicles;
    const q = vehicleSearch.value.toLowerCase();
    return props.vehicles.filter(v => v.plate_number?.toLowerCase().includes(q) || getName(v.make)?.toLowerCase().includes(q));
});

const filteredWorkOrders = computed(() => {
    if (!props.workOrders) return [];
    if (!workOrderSearch.value) return props.workOrders;
    const q = workOrderSearch.value.toLowerCase();
    return props.workOrders.filter(w => w.code?.toLowerCase().includes(q) || w.vehicle?.plate_number?.toLowerCase().includes(q));
});

const filteredQuotes = computed(() => {
    if (!props.quotes) return [];
    if (!quoteSearch.value) return props.quotes;
    const q = quoteSearch.value.toLowerCase();
    return props.quotes.filter(qu => qu.code?.toLowerCase().includes(q) || qu.vehicle?.plate_number?.toLowerCase().includes(q));
});

// Helpers
function getName(item) {
    if (!item) return '';
    return locale.value === 'ar' ? (item.name_ar || item.name_en) : (item.name_en || item.name_ar);
}

function getMakeName(vehicle) {
    if (!vehicle) return '';
    return getName(vehicle.make) || vehicle.make_other || '';
}

function getModelName(vehicle) {
    if (!vehicle) return '';
    return getName(vehicle.model) || vehicle.model_other || '';
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    return [getMakeName(vehicle), getModelName(vehicle), vehicle.year].filter(Boolean).join(' ');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US');
}

function getWhatsAppNumber(customer) {
    const phone = customer?.whatsapp || customer?.phone;
    return phone ? phone.replace(/[^\d+]/g, '').replace(/^\+/, '') : '';
}

function getStatusClass(status) {
    const classes = { open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', closed: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
    return classes[status] || classes.open;
}

// Map common color names to hex values
function getColorHex(colorName) {
    if (!colorName) return '#gray';
    const colorMap = {
        'أبيض': '#ffffff',
        'أسود': '#1f2937',
        'فضي': '#9ca3af',
        'رمادي': '#6b7280',
        'أحمر': '#ef4444',
        'أزرق': '#3b82f6',
        'أخضر': '#22c55e',
        'ذهبي': '#eab308',
        'بني': '#92400e',
        'برتقالي': '#f97316',
        'بيج': '#d4c4a8',
        'white': '#ffffff',
        'black': '#1f2937',
        'silver': '#9ca3af',
        'gray': '#6b7280',
        'red': '#ef4444',
        'blue': '#3b82f6',
        'green': '#22c55e',
        'gold': '#eab308',
        'brown': '#92400e',
        'orange': '#f97316',
        'beige': '#d4c4a8',
    };
    return colorMap[colorName.toLowerCase()] || colorMap[colorName] || '#9ca3af';
}

function getQuoteStatusClass(status) {
    const classes = { draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400', rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', converted: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' };
    return classes[status] || classes.draft;
}

// Modal handlers
function openVehicleModal() { selectedVehicle.value = null; showVehicleModal.value = true; }
function openVehicleEditModal(vehicle) { selectedVehicle.value = vehicle; showVehicleModal.value = true; }
function openWorkOrderModal() { showWorkOrderModal.value = true; }
function openQuoteModal() { showQuoteModal.value = true; }
async function confirmDelete() { if (!props.canDelete) return; const confirmed = await confirm({ title: t("customers.delete_confirm_title"), message: t("customers.delete_confirm_message"), confirmText: t("common.delete"), cancelText: t("common.cancel"), type: "danger" }); if (confirmed) deleteCustomer(); }

function deleteCustomer() { router.delete(route('customers.destroy', props.customer.id)); }
function handleCustomerSaved() { showEditModal.value = false; router.reload(); }
function handleVehicleSaved() { showVehicleModal.value = false; router.reload(); }
function handleWorkOrderSaved() { showWorkOrderModal.value = false; router.reload(); }
function handleQuoteSaved() { showQuoteModal.value = false; router.reload(); }
</script>
