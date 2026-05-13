<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('vehicles.title')"
                :subtitle="$t('vehicles.subtitle')"
                :totalCount="vehicles ? toEnglish(vehicles.total) : null"
                :countLabel="$t('vehicles.total_count')"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <button v-if="can('crm.vehicles.export') || isAnyAdmin()" @click="exportVehicles"
                            :disabled="exporting" :title="$t('common.export')"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md disabled:opacity-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </button>
                        <button v-if="can('crm.vehicles.print') || isAnyAdmin()" @click="printVehicles"
                            :title="$t('common.print')"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>

                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <div class="flex gap-1.5">
                            <button @click="viewMode = 'grid'" :title="$t('common.grid_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'grid'
                                        ? 'bg-indigo-600 text-white shadow-indigo-200 dark:shadow-none'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button @click="viewMode = 'list'" :title="$t('common.list_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'list'
                                        ? 'bg-indigo-600 text-white shadow-indigo-200 dark:shadow-none'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button v-if="can('crm.vehicles.create') || isAnyAdmin()" @click="openCreateModal"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl font-black shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all group/add">
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('vehicles.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="relative flex-1 group">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none group-focus-within:text-indigo-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('vehicles.search')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all outline-none shadow-sm"
                            />
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-full sm:w-48">
                                <SearchableSelect
                                    v-model="filterMakeId"
                                    :options="makeOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('vehicles.filter.all_makes')"
                                    :label="''"
                                    class="!rounded-2xl"
                                />
                            </div>

                            <button @click="resetFilters"
                                class="p-3.5 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all shadow-sm"
                                :title="$t('common.reset')">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Loading State -->
            <div v-if="!vehicles" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-blue-200 dark:border-blue-900 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="vehicles.data.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('vehicles.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('vehicles.empty_hint') }}</p>
                <button
                    v-if="can('crm.vehicles.create') || isAnyAdmin()"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('vehicles.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                <div
                    v-for="vehicle in allVehicles"
                    :key="vehicle.id"
                    @click="visitShowPage(vehicle)"
                    class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-teal-500 transition-all duration-300 shadow-lg shadow-gray-200/50 dark:shadow-black/20 hover:shadow-teal-500/10 cursor-pointer overflow-hidden"
                >
                    <!-- Logo Watermark -->
                    <div v-if="vehicle.make?.logo_path" class="absolute inset-0 flex items-center justify-center opacity-[0.05] dark:opacity-[0.15] pointer-events-none select-none z-0">
                        <img :src="`/storage/${vehicle.make.logo_path}`" class="w-2/3 h-2/3 object-contain grayscale" alt="" />
                    </div>

                    <div class="relative z-10 p-5 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 bg-gray-900 text-white rounded-lg font-mono font-bold tracking-widest text-sm shadow-md">
                                {{ toEnglish(vehicle.plate_number) }}
                            </span>
                            <div v-if="vehicle.color" class="w-4 h-4 rounded-full border-2 border-white dark:border-gray-700 shadow-sm" :style="{ backgroundColor: getColorHex(vehicle.color) }"></div>
                        </div>

                        <div class="mb-4">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white group-hover:text-teal-600 transition-colors truncate">
                                {{ vehicle.display_make || $t('common.na') }}
                            </h3>
                            <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider truncate">
                                {{ vehicle.display_model || '' }}
                            </p>
                        </div>

                        <div class="mt-auto pt-4 border-t border-gray-50 dark:border-gray-700/50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0 text-indigo-600 dark:text-indigo-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate">
                                {{ vehicle.customer?.name || $t('common.unknown') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('vehicles.form.plate') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('vehicles.form.make') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('vehicles.form.model') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('vehicles.form.year') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('vehicles.form.customer') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('customers.form.phone') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="(vehicle, index) in allVehicles"
                                :key="vehicle.id"
                                @click="visitShowPage(vehicle)"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 text-sm font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg" dir="ltr">
                                        {{ toEnglish(vehicle.plate_number) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ vehicle.display_make || $t('common.na') }}
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ vehicle.display_model || $t('common.na') }}
                                </td>
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                                    {{ toEnglish(vehicle.year) || $t('common.na') }}
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ vehicle.customer?.name }}
                                </td>
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                                    {{ toEnglish(vehicle.customer?.phone) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Infinite Scroll Sentinel -->
            <div ref="loadMoreSentinel" class="py-6 flex justify-center w-full">
                <div v-if="loadingMore" class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ $t('common.loading') }}</span>
                </div>
                <div v-else-if="allVehicles.length >= vehicles.total && vehicles.total > 0" class="text-sm text-gray-400 dark:text-gray-600">
                    {{ $t('vehicles.all_loaded') || 'All vehicles loaded' }}
                </div>
            </div>
        </div>

        <!-- Vehicle Form Modal -->
        <VehicleFormModal
            :show="showModal"
            :vehicle="selectedVehicle"
            :customers="customers"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="closeModal"
            @saved="handleSaved"
        />
        <!-- Print Section -->
        <Teleport to="body">
            <div class="print-section hidden">
                <!-- Header -->
                <div class="print-header">
                    <div class="grid grid-cols-3 items-start">
                        <!-- Column 1: Info (Right in Arabic, Left in English) -->
                        <div :class="isRtl ? 'text-right' : 'text-left'">
                            <h1 class="text-xl font-bold">
                                {{ isRtl ? ($page.props.tenant?.trade_name || $page.props.tenant?.name) : ($page.props.tenant?.name_en || $page.props.tenant?.name) }}
                            </h1>
                            <!-- Branch Logic -->
                            <p v-if="$page.props.auth.available_centers?.length > 1" class="text-sm font-medium text-gray-600">
                                {{ isRtl ? $page.props.center?.name : ($page.props.center?.name_en || $page.props.center?.name) }}
                            </p>
                            <div class="mt-2 text-[10px] space-y-0.5 text-gray-500">
                                <p v-if="$page.props.auth.center?.phone || $page.props.tenant?.phone">
                                    {{ isRtl ? 'الهاتف' : 'Phone' }}: {{ $page.props.auth.center?.phone || $page.props.tenant?.phone }}
                                </p>
                                <p v-if="$page.props.auth.center?.email || $page.props.tenant?.email">
                                    {{ isRtl ? 'البريد' : 'Email' }}: {{ $page.props.auth.center?.email || $page.props.tenant?.email }}
                                </p>
                                <p v-if="$page.props.tenant?.cr_number">
                                    {{ isRtl ? 'السجل التجاري' : 'CR' }}: {{ $page.props.tenant?.cr_number }}
                                </p>
                            </div>
                        </div>

                        <!-- Column 2: Logo (Always Center) -->
                        <div class="flex justify-center">
                            <div v-if="$page.props.tenant?.logo_url" class="w-24 h-24 flex-shrink-0">
                                <img :src="$page.props.tenant.logo_url" class="w-full h-full object-contain" />
                            </div>
                        </div>

                        <!-- Column 3: Empty (For Balance) -->
                        <div></div>
                    </div>

                    <!-- Report Title -->
                    <div class="mt-8 text-center">
                        <h2 class="text-lg font-bold border-y-2 border-gray-100 py-2 uppercase tracking-wider">
                            {{ $t('vehicles.title') }}
                        </h2>
                        <p class="text-[10px] text-gray-400 mt-1" dir="ltr">
                            {{ new Date().toLocaleDateString('en-GB').split('/').reverse().join('/') }}
                        </p>
                    </div>
                </div>

                <!-- Table -->
                <table class="print-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>{{ $t('vehicles.form.plate') }}</th>
                            <th>{{ $t('vehicles.form.make') }}</th>
                            <th>{{ $t('vehicles.form.model') }}</th>
                            <th>{{ $t('vehicles.form.year') }}</th>
                            <th>{{ $t('vehicles.form.customer') }}</th>
                            <th>{{ $t('customers.form.phone') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(vehicle, index) in allVehicles" :key="vehicle.id">
                            <td>{{ index + 1 }}</td>
                            <td class="font-bold">{{ toEnglish(vehicle.plate_number) }}</td>
                            <td>{{ vehicle.display_make || '-' }}</td>
                            <td>{{ vehicle.display_model || '-' }}</td>
                            <td>{{ toEnglish(vehicle.year) || '-' }}</td>
                            <td>{{ vehicle.customer?.name || '-' }}</td>
                            <td dir="ltr">{{ toEnglish(vehicle.customer?.phone) || '-' }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Footer -->
                <div class="mt-8 text-center text-[10px] text-gray-400">
                    {{ $page.props.auth.user.name }} - <span dir="ltr">{{ new Date().toLocaleString('en-GB') }}</span>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { useToast } from "@/Composables/useToast";
import { useNumberFormat } from "@/Composables/useNumberFormat";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import VehicleFormModal from "@/Components/Vehicles/VehicleFormModal.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import CustomDatePicker from "@/Components/CustomDatePicker.vue";
import { usePermission } from "@/Composables/usePermission";

const props = defineProps({
    vehicles: {
        type: Object,
        default: null,
    },
    customers: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
    colors: {
        type: Array,
        default: () => [],
    },
    modelsByMake: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const { success } = useToast();
const { toEnglish } = useNumberFormat();
const { can, isAnyAdmin } = usePermission();

const makeOptions = computed(() => [
    { value: '', label: t('vehicles.filter.all_makes') },
    ...props.makes.map(make => ({
        value: make.id,
        label: isRtl.value ? (make.name_ar || make.name_en) : (make.name_en || make.name_ar),
    })),
]);

const showModal = ref(false);
const selectedVehicle = ref(null);
const searchQuery = ref(props.filters.search || "");
const filterMakeId = ref(props.filters.make_id || "");
const dateFrom = ref(props.filters.date_from || "");
const dateTo = ref(props.filters.date_to || "");
const viewMode = ref(localStorage.getItem("vehiclesViewMode") || "grid");
const exporting = ref(false);

// Infinite Scroll Refs
const allVehicles = ref(props.vehicles.data || []);
const nextPageUrl = ref(props.vehicles.next_page_url);
const loadingMore = ref(false);
const loadMoreSentinel = ref(null);
let observer = null;

// Watch props change (filters)
watch(() => props.vehicles, (newVal) => {
    allVehicles.value = newVal.data;
    nextPageUrl.value = newVal.next_page_url;
});

// Load more data
const loadMore = async () => {
    if (loadingMore.value || !nextPageUrl.value) return;

    loadingMore.value = true;
    try {
        // Extract page number and use API endpoint
        const url = new URL(nextPageUrl.value, window.location.origin);
        const page = url.searchParams.get('page') || 2;
        
        const params = new URLSearchParams();
        params.set('page', page);
        if (searchQuery.value) params.set('search', searchQuery.value);
        if (filterMakeId.value) params.set('make_id', filterMakeId.value);
        // if (dateFrom.value) params.set('date_from', dateFrom.value);
        // if (dateTo.value) params.set('date_to', dateTo.value);
        // Add other filters if any
        
        const apiUrl = '/app/api/vehicles-index?' + params.toString();
        
        const response = await axios.get(apiUrl);
        const data = response.data;
        
        allVehicles.value.push(...data.data);
        nextPageUrl.value = data.next_page_url;
    } catch (e) {
        console.error('Failed to load more vehicles', e);
    } finally {
        loadingMore.value = false;
    }
};

// Intersection Observer Setup
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && nextPageUrl.value) {
            loadMore();
        }
    }, {
        root: null,
        threshold: 0.1,
    });

    if (loadMoreSentinel.value) {
        observer.observe(loadMoreSentinel.value);
    }
});

watch(loadMoreSentinel, (el) => {
    if (el && observer) observer.observe(el);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

function exportVehicles() {
    exporting.value = true;
    const params = new URLSearchParams(window.location.search);
    window.location.href = route('vehicles.export', params.toString());
    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

const printVehicles = async () => {
    // If more pages exist, load them all first
    if (nextPageUrl.value) {
        // Show loading indication
        const loadingToast = useToast().info(t('vehicles.loading_all_printing') || 'Loading all vehicles for printing...', { timeout: 0 }); // persistent toast
        
        try {
            while (nextPageUrl.value) {
                await loadMore();
                // Add small delay to prevent UI freezing
                await new Promise(resolve => setTimeout(resolve, 50));
            }
            // All loaded
            useToast().clear(); 
            // Small delay to ensure DOM updates before print
            setTimeout(() => window.print(), 100);
        } catch (e) {
            console.error('Error loading all vehicles', e);
            error(t('common.error'));
        }
    } else {
        // Already loaded all
        window.print();
    }
};

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const applyFilters = () => {
    router.get(
        route("vehicles.index"),
        {
            search: searchQuery.value || undefined,
            make_id: filterMakeId.value || undefined,
            // date_from: dateFrom.value || undefined,
            // date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const debouncedApplyFilters = debounce(applyFilters, 400);

watch([searchQuery, filterMakeId /*, dateFrom, dateTo*/], () => {
    debouncedApplyFilters();
});

watch(viewMode, (newMode) => {
    localStorage.setItem("vehiclesViewMode", newMode);
});

// Navigation handler
const resetFilters = () => {
    searchQuery.value = "";
    filterMakeId.value = "";
    applyFilters();
};

function visitShowPage(vehicle) {
    router.visit(route("vehicles.show", vehicle.id));
}

function openCreateModal() {
    selectedVehicle.value = null;
    showModal.value = true;
}

function openEditModal(vehicle) {
    selectedVehicle.value = vehicle;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedVehicle.value = null;
}

function handleSaved() {
    closeModal();
    success(t("common.saved_success"));
    router.reload({ only: ["vehicles"] });
}

function handleCustomerCreated(customer) {
    // Reload to get updated customer list
    router.reload({ only: ["customers"] });
}

function getWhatsAppNumber(customer) {
    if (!customer) return null;
    const phone = customer.whatsapp || customer.phone;
    if (!phone) return null;
    return phone.replace(/[^\d+]/g, "").replace(/^\+/, "");
}

// Map common color names to hex values
function getColorHex(colorName) {
    if (!colorName) return "#gray";
    const colorMap = {
        "أبيض": "#ffffff",
        "أسود": "#1f2937",
        "فضي": "#9ca3af",
        "رمادي": "#6b7280",
        "أحمر": "#ef4444",
        "أزرق": "#3b82f6",
        "أخضر": "#22c55e",
        "ذهبي": "#eab308",
        "بني": "#92400e",
        "برتقالي": "#f97316",
        "بيج": "#d4c4a8",
        "white": "#ffffff",
        "black": "#1f2937",
        "silver": "#9ca3af",
        "gray": "#6b7280",
        "red": "#ef4444",
        "blue": "#3b82f6",
        "green": "#22c55e",
        "gold": "#eab308",
        "brown": "#92400e",
        "orange": "#f97316",
        "beige": "#d4c4a8",
    };
    return colorMap[colorName.toLowerCase()] || colorMap[colorName] || "#9ca3af";
}
</script>

<style scoped>
@media print {
    .print-section {
        display: block !important;
        font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif !important;
        padding: 20px;
        background: white;
    }

    .print-header {
        margin-bottom: 30px;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 20px;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #e5e7eb;
        padding: 8px;
        text-align: center !important;
    }

    .print-table th {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        font-weight: bold;
    }
}
</style>


