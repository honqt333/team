<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                {{ quote ? $t('quotes.edit') : $t('quotes.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Tabs Navigation -->
            <div class="flex flex-wrap gap-1 bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                <button v-for="(tab, index) in tabs" :key="tab.key" type="button" @click="activeTab = tab.key" :class="[
                    'flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 px-2 sm:px-3 py-2 text-xs sm:text-sm font-medium rounded-lg transition-all flex-1 sm:flex-none',
                    activeTab === tab.key
                        ? 'bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-400 shadow-sm'
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
                    <div v-if="!vehicle && !quote"
                        class="relative bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('quotes.form_tabs.search_placeholder') }}
                            </label>
                            <button type="button" @click="showVehicleModal = true"
                                class="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 flex items-center gap-1">
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
                                class="w-full px-4 py-3 ps-10 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500" />
                            <svg class="w-5 h-5 absolute start-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>

                            <!-- Loading -->
                            <div v-if="searching" class="absolute end-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24">
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
                                vehicle.has_open_quote
                                    ? 'bg-red-50 dark:bg-red-900/20 cursor-not-allowed opacity-75'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer'
                            ]" @click="!vehicle.has_open_quote && selectVehicle(vehicle)">
                                <div :class="[
                                    'w-10 h-10 rounded-lg flex items-center justify-center',
                                    vehicle.has_open_quote
                                        ? 'bg-red-200 dark:bg-red-800'
                                        : 'bg-gray-200 dark:bg-gray-600'
                                ]">
                                    <svg class="w-5 h-5"
                                        :class="vehicle.has_open_quote ? 'text-red-600' : 'text-gray-500'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="vehicle.has_open_quote" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white" dir="ltr">{{
                                        vehicle.plate_number }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ vehicle.customer?.name }} -
                                        {{
                                            vehicle.customer?.phone }}</p>
                                    <!-- Open Quote Warning -->
                                    <div v-if="vehicle.has_open_quote" class="mt-1 flex items-center gap-2">
                                        <span class="text-xs text-red-600 dark:text-red-400 font-medium">
                                            {{ $t('quotes.form_tabs.has_open_quote') }} ({{ vehicle.open_quote?.code }})
                                        </span>
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
                        class="relative bg-gradient-to-br from-amber-500/10 via-orange-500/10 to-red-500/10 dark:from-amber-900/30 dark:via-orange-900/20 dark:to-red-900/20 rounded-2xl p-5 border border-amber-300/50 dark:border-amber-700/50 overflow-hidden">
                        <!-- Decorative Background -->
                        <div
                            class="absolute top-0 end-0 w-32 h-32 bg-gradient-to-br from-amber-400/20 to-orange-400/20 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2">
                        </div>

                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $t('common.selected') }}</h4>
                                <p class="text-sm text-amber-600 dark:text-amber-400">{{ selectedVehicle.plate_number }}
                                </p>
                            </div>
                            <button type="button" @click="clearSelection"
                                class="ms-auto p-2 rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50 transition-colors">
                                <svg class="w-5 h-5 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <!-- Customer Name -->
                            <div class="bg-white/60 dark:bg-gray-800/60 rounded-xl p-3 backdrop-blur-sm">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">{{
                                        $t('quotes.form_tabs.customer_name') }}</label>
                                </div>
                                <p class="font-semibold text-gray-900 dark:text-white truncate">{{
                                    selectedVehicle.customer?.name }}</p>
                            </div>

                            <!-- Phone -->
                            <div class="bg-white/60 dark:bg-gray-800/60 rounded-xl p-3 backdrop-blur-sm">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">{{
                                        $t('quotes.form_tabs.phone')
                                    }}</label>
                                </div>
                                <p class="font-semibold text-gray-900 dark:text-white" dir="ltr">{{
                                    selectedVehicle.customer?.phone }}</p>
                            </div>

                            <!-- Plate Number -->
                            <div class="bg-white/60 dark:bg-gray-800/60 rounded-xl p-3 backdrop-blur-sm">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <label class="text-xs text-gray-500 dark:text-gray-400">{{
                                        $t('vehicles.plate_number')
                                    }}</label>
                                </div>
                                <p class="font-semibold text-gray-900 dark:text-white" dir="ltr">{{
                                    selectedVehicle.plate_number
                                }}</p>
                            </div>
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
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"></textarea>
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
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"></textarea>
                    </div>
                </div>

                <!-- Tab 4: Departments -->
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
                                    class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
                                <span class="text-gray-900 dark:text-white">{{ index + 1 }}. {{ getName(dept) }}</span>
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
            <button type="button" @click="submitForm" :disabled="form.processing || !selectedVehicle"
                class="px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:from-amber-600 hover:to-orange-600 disabled:opacity-50 transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>

    <!-- Vehicle Form Modal -->
    <VehicleFormModal v-if="showVehicleModal" :show="showVehicleModal" :customers="customers" :makes="makes"
        :colors="colors" :modelsByMake="modelsByMake" @close="showVehicleModal = false" @saved="onVehicleSaved"
        @customer-created="handleCustomerCreated" />
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import BaseModal from '@/Components/BaseModal.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    quote: Object,
    vehicle: Object, // Pre-selected vehicle for creation
    customers: Array,
    departments: Array,
    makes: Array,
    colors: Array,
    modelsByMake: Object,
    services: Array,
});
const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();

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
const IconDepartments = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>`
};

// Tabs - hide departments tab when editing (managed in Show.vue)
const allTabs = [
    { key: 'main_info', label: t('quotes.tabs.main_info'), icon: IconInfo },
    { key: 'customer_complaint', label: t('quotes.tabs.customer_complaint'), icon: IconComplaint },
    { key: 'initial_assessment', label: t('quotes.tabs.initial_assessment'), icon: IconAssessment },
    { key: 'departments', label: t('quotes.tabs.departments'), icon: IconDepartments },
];
const tabs = computed(() => {
    if (props.quote) {
        // Edit mode: hide departments tab
        return allTabs.filter(tab => tab.key !== 'departments');
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

// Form
const form = useForm({
    customer_id: props.quote?.customer_id || props.vehicle?.customer_id || '',
    vehicle_id: props.quote?.vehicle_id || props.vehicle?.id || '',
    department_id: props.quote?.department_id || '',
    customer_complaint: props.quote?.customer_complaint || '',
    initial_assessment: props.quote?.initial_assessment || '',
    departments: props.quote?.departments?.map(d => d.id) || [],
    notes: props.quote?.notes || '',
    lines: [],
});

// Watch for modal opening to reinitialize form values (for edit mode)
watch(() => props.show, (newVal) => {
    if (newVal && props.quote) {
        // Reset form with quote data when opening in edit mode
        form.customer_id = props.quote.customer_id || '';
        form.vehicle_id = props.quote.vehicle_id || '';
        form.customer_complaint = props.quote.customer_complaint || '';
        form.initial_assessment = props.quote.initial_assessment || '';
        form.departments = props.quote.departments?.map(d => d.id) || [];
        form.notes = props.quote.notes || '';
        form.lines = [];

        // Set selected vehicle for display
        if (props.quote.vehicle) {
            selectedVehicle.value = props.quote.vehicle;
            searchQuery.value = props.quote.vehicle.plate_number || '';
        }
    }
});

// Debounce search
let debounceTimer = null;
function debouncedSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
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
        const response = await axios.get('/app/quotes/search', {
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
    console.log('[QuoteFormModal] selectVehicle called with:', vehicle);

    // Ensure customer data is populated
    let vehicleWithCustomer = { ...vehicle };

    // If customer object is missing but customer_id exists, fetch from customers array
    if (!vehicleWithCustomer.customer && vehicleWithCustomer.customer_id && props.customers) {
        const customerData = props.customers.find(c => c.id === vehicleWithCustomer.customer_id);
        if (customerData) {
            vehicleWithCustomer.customer = customerData;
            console.log('[QuoteFormModal] Populated customer data from customers array');
        }
    }

    selectedVehicle.value = vehicleWithCustomer;
    form.customer_id = vehicleWithCustomer.customer_id;
    form.vehicle_id = vehicleWithCustomer.id;
    searchQuery.value = vehicleWithCustomer.plate_number;
    showResults.value = false;

    console.log('[QuoteFormModal] Selected vehicle with customer:', selectedVehicle.value);
}

function clearSelection() {
    selectedVehicle.value = null;
    form.customer_id = '';
    form.vehicle_id = '';
    searchQuery.value = '';
}

function onVehicleSaved(newVehicle) {
    showVehicleModal.value = false;
    if (newVehicle) {
        selectVehicle(newVehicle);
    }
}

function handleCustomerCreated(savedCustomer) {
    console.log('[QuoteFormModal] handleCustomerCreated called:', savedCustomer);
    console.log('[QuoteFormModal] Current customers count:', props.customers?.length);

    // Store the phone number (unique identifier)
    const customerPhone = savedCustomer?.phone;

    // Reload customers from backend to include the newly created one
    router.reload({
        only: ['customers'],
        preserveScroll: true,
        preserveState: true,
        onStart: () => {
            console.log('[QuoteFormModal] Reload started');
        },
        onSuccess: async () => {
            console.log('[QuoteFormModal] Reload successful');
            console.log('[QuoteFormModal] New customers count:', props.customers?.length);

            // Give Vue time to propagate props to VehicleFormModal
            await nextTick();

            // Trigger a custom event with phone number
            window.dispatchEvent(new CustomEvent('customer-reloaded', {
                detail: { customerPhone }
            }));
            console.log('[QuoteFormModal] Dispatched customer-reloaded event with phone:', customerPhone);
        },
        onError: (errors) => {
            console.error('[QuoteFormModal] Reload error:', errors);
        }
    });
}

function submitForm() {
    const isEdit = !!props.quote;
    const method = isEdit ? 'put' : 'post';
    const url = isEdit
        ? `/app/quotes/${props.quote.id}`
        : '/app/quotes';

    // For new quotes, let server redirect to show page
    // For edits, close modal and stay on page
    const options = isEdit ? {
        onSuccess: () => {
            form.reset();
            emit('saved');
            emit('close');
        },
    } : {};

    form.transform((data) => {
        // If editing, exclude lines to prevent overwriting them with empty array
        // Also exclude departments as they are managed separately
        if (props.quote) {
            const { lines, departments, ...rest } = data;
            return rest;
        }
        return data;
    })[method](url, options);
}

// Close results when clicking outside
function handleClickOutside(event) {
    if (!event.target.closest('.relative')) {
        showResults.value = false;
    }
}

// Reset form when modal opens for new quote
function resetForm() {
    form.reset();
    form.customer_id = '';
    form.vehicle_id = '';
    form.customer_complaint = '';
    form.initial_assessment = '';
    form.departments = [];
    form.notes = '';
    form.lines = [];
    selectedVehicle.value = null;
    searchQuery.value = '';
    searchResults.value = [];
    showResults.value = false;
    activeTab.value = 'main_info';
}

// Watch for show prop changes - reset when opening fresh (no quote)
watch(() => props.show, (isOpen) => {
    if (isOpen && !props.quote) {
        resetForm();
    }
});

// Watch for quote prop changes (edit mode)
watch(() => props.quote, (newQuote) => {
    if (newQuote) {
        form.customer_id = newQuote.customer_id;
        form.vehicle_id = newQuote.vehicle_id;
        form.customer_complaint = newQuote.customer_complaint || '';
        form.initial_assessment = newQuote.initial_assessment || '';
        form.notes = props.quote.notes || '';
        if (props.quote.vehicle) {
            selectedVehicle.value = props.quote.vehicle;
            searchQuery.value = props.quote.vehicle.plate_number || '';
        }
    }
}, { immediate: true });

// Watch for vehicle prop (pre-selection)
watch(() => props.vehicle, (val) => {
    if (val && !props.quote) { // Only if not editing
        selectedVehicle.value = val;
        form.vehicle_id = val.id;
        form.customer_id = val.customer_id;
    }
}, { immediate: true });

// Initial Load for Edit Mode or Pre-selection
onMounted(() => {
    if (props.quote?.vehicle) {
        selectedVehicle.value = props.quote.vehicle;
    } else if (props.vehicle) {
        selectedVehicle.value = props.vehicle;
    }
});
</script>
