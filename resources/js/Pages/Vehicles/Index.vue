<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('vehicles.title') }}</h1>
                            <p v-if="vehicles" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ vehicles.total }} {{ $t('vehicles.total_count') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('vehicles.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            />
                        </div>

                        <!-- View Toggle -->
                        <div class="flex rounded-xl bg-gray-100 dark:bg-gray-900 p-1">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                    viewMode === 'grid'
                                        ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                    viewMode === 'list'
                                        ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Add Button -->
                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center justify-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 text-sm sm:text-base w-full sm:w-auto bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>{{ $t('vehicles.add') }}</span>
                        </button>
                    </div>
                </div>
            </div>

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
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/30 hover:shadow-xl transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('vehicles.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                <div
                    v-for="vehicle in vehicles.data"
                    :key="vehicle.id"
                    @click="openEditModal(vehicle)"
                    class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-lg cursor-pointer transition-all overflow-hidden"
                >
                    <!-- Plate Number Header -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold text-gray-800 dark:text-gray-100 tracking-wider" dir="ltr">
                                {{ vehicle.plate_number }}
                            </span>
                            <span v-if="vehicle.color" class="w-4 h-4 rounded-full border-2 border-white shadow-sm" :style="{ backgroundColor: getColorHex(vehicle.color) }"></span>
                        </div>
                    </div>

                    <!-- Vehicle Info -->
                    <div class="p-4">
                        <!-- Make/Model/Year -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ vehicle.display_make || $t('common.na') }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                    {{ vehicle.display_model || '' }}
                                </p>
                            </div>
                            <span v-if="vehicle.year" class="flex-shrink-0 ms-2 px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg">
                                {{ vehicle.year }}
                            </span>
                        </div>

                        <!-- Customer Info -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ vehicle.customer?.name?.charAt(0) || '?' }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                        {{ vehicle.customer?.name || $t('common.na') }}
                                    </p>
                                    <p v-if="vehicle.customer?.phone" class="text-xs text-gray-500 dark:text-gray-400 truncate" dir="ltr">
                                        {{ vehicle.customer.phone }}
                                    </p>
                                </div>
                            </div>

                            <!-- Contact Buttons -->
                            <div class="flex items-center gap-2 mt-3">
                                <!-- Call Button -->
                                <a
                                    v-if="vehicle.customer?.phone"
                                    :href="`tel:${vehicle.customer.phone}`"
                                    @click.stop
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors text-sm font-medium"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span>{{ $t('common.call') }}</span>
                                </a>
                                <!-- WhatsApp Button -->
                                <a
                                    v-if="getWhatsAppNumber(vehicle.customer)"
                                    :href="`https://wa.me/${getWhatsAppNumber(vehicle.customer)}`"
                                    target="_blank"
                                    @click.stop
                                    class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors text-sm font-medium"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.355-5.033c0-5.458 4.441-9.898 9.899-9.898 2.645 0 5.132 1.03 7.002 2.901l.001.001c1.868 1.87 2.898 4.357 2.898 7.002 0 5.459-4.441 9.899-9.897 9.899L12.05 21.785z"/>
                                    </svg>
                                    <span>{{ $t('customers.quick.whatsapp') }}</span>
                                </a>
                            </div>
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
                                v-for="vehicle in vehicles.data"
                                :key="vehicle.id"
                                @click="openEditModal(vehicle)"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 text-sm font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg" dir="ltr">
                                        {{ vehicle.plate_number }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ vehicle.display_make || $t('common.na') }}
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ vehicle.display_model || $t('common.na') }}
                                </td>
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                                    {{ vehicle.year || $t('common.na') }}
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ vehicle.customer?.name }}
                                </td>
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                                    {{ vehicle.customer?.phone }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="vehicles && vehicles.data.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t('customers.showing') }} 
                <span class="font-medium text-gray-900 dark:text-white">{{ vehicles.from }}</span>-<span class="font-medium text-gray-900 dark:text-white">{{ vehicles.to }}</span>
                {{ $t('customers.of') }}
                <span class="font-medium text-gray-900 dark:text-white">{{ vehicles.total }}</span>
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
            @customer-created="handleCustomerCreated"
        />
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import AppLayout from '@/Layouts/AppLayout.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';

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
});

const { t } = useI18n();
const { success } = useToast();
const showModal = ref(false);
const selectedVehicle = ref(null);
const searchQuery = ref('');
const viewMode = ref(localStorage.getItem('vehiclesViewMode') || 'grid');

watch(viewMode, (newMode) => {
    localStorage.setItem('vehiclesViewMode', newMode);
});

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
    success(t('common.saved_success'));
    router.reload({ only: ['vehicles'] });
}

function handleCustomerCreated(customer) {
    // Reload to get updated customer list
    router.reload({ only: ['customers'] });
}

function getWhatsAppNumber(customer) {
    if (!customer) return null;
    const phone = customer.whatsapp || customer.phone;
    if (!phone) return null;
    return phone.replace(/[^\d+]/g, '').replace(/^\+/, '');
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
</script>
