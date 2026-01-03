<template>
    <BaseModal :show="show" @close="$emit('close')" size="xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                {{ workOrder ? $t('work_orders.edit.title') : $t('work_orders.create.title') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Customer Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('work_orders.form.customer') }} <span class="text-red-500">*</span>
                    </label>
                    <button
                        type="button"
                        @click="showCustomerModal = true"
                        class="flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('customers.add') }}
                    </button>
                </div>
                
                <!-- Customer Select -->
                <SearchableSelect
                    v-model="form.customer_id"
                    @change="onCustomerChange"
                    :options="customers"
                    :label="''"
                    :placeholder="$t('common.choose')"
                    :option-label="c => `${c.name} - ${c.phone}`"
                    option-value="id"
                    :error="form.errors.customer_id"
                    required
                />
            </div>

            <!-- Vehicle Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('work_orders.form.vehicle') }} <span class="text-red-500">*</span>
                    </label>
                    <button
                        v-if="form.customer_id"
                        type="button"
                        @click="showVehicleModal = true"
                        class="flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('vehicles.add') }}
                    </button>
                </div>
                <SearchableSelect
                    v-model="form.vehicle_id"
                    :options="vehicles"
                    :label="''"
                    :placeholder="form.customer_id ? $t('common.choose') : $t('work_orders.form.select_customer_first')"
                    :option-label="v => `${v.plate_number} - ${getVehicleName(v)}`"
                    option-value="id"
                    :error="form.errors.vehicle_id"
                    required
                    :disabled="!form.customer_id || loadingVehicles"
                />
            </div>

            <!-- Services Checkboxes Section -->
            <div v-if="Object.keys(services).length > 0" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ $t('work_orders.form.services') }}
                </label>
                
                <div v-for="(categoryServices, category) in services" :key="category" class="mb-4 last:mb-0">
                    <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">
                        {{ $t(`work_orders.service_categories.${category}`) || category }}
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <label
                            v-for="service in categoryServices"
                            :key="service.id"
                            class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-all"
                            :class="isServiceSelected(service.id) 
                                ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-300 dark:border-indigo-700' 
                                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        >
                            <input
                                type="checkbox"
                                :checked="isServiceSelected(service.id)"
                                @change="toggleService(service)"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ service.name }}</span>
                            <span v-if="service.default_price > 0" class="text-xs text-indigo-600 dark:text-indigo-400 ms-auto">
                                {{ service.default_price }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Service Items Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('work_orders.form.items') }} <span class="text-red-500">*</span>
                    </span>
                    <button
                        type="button"
                        @click="addItem"
                        class="flex items-center gap-1 px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg text-sm font-medium hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('work_orders.form.add_item') }}
                    </button>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600"
                    >
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <!-- Title -->
                            <div class="col-span-12 sm:col-span-5">
                                <input
                                    v-model="item.title"
                                    type="text"
                                    :placeholder="$t('work_orders.form.item_title')"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                            <!-- Qty -->
                            <div class="col-span-3 sm:col-span-2">
                                <input
                                    v-model.number="item.qty"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    :placeholder="$t('work_orders.form.item_qty')"
                                    class="w-full px-2 py-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                            <!-- Price -->
                            <div class="col-span-4 sm:col-span-2">
                                <input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :placeholder="$t('work_orders.form.item_price')"
                                    class="w-full px-2 py-2 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                            <!-- Total -->
                            <div class="col-span-4 sm:col-span-2 flex items-center justify-center">
                                <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                                    {{ (item.qty * item.unit_price).toFixed(2) }}
                                </span>
                            </div>
                            <!-- Remove -->
                            <div class="col-span-1 flex items-center justify-center">
                                <button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-1 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <p v-if="form.errors.items" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.items }}</p>
                
                <!-- Grand Total -->
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.total') }}</span>
                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ grandTotal }}</span>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('work_orders.form.notes') }}
                </label>
                <textarea
                    v-model="form.notes"
                    rows="2"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-all"
                ></textarea>
            </div>
        </form>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                @click="submitForm"
                :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>

    <!-- Customer Form Modal -->
    <CustomerFormModal
        :show="showCustomerModal"
        @close="showCustomerModal = false"
        @saved="onCustomerSaved"
    />

    <!-- Vehicle Form Modal -->
    <VehicleFormModal
        :show="showVehicleModal"
        :customers="customers"
        :makes="makes"
        :preselectedCustomerId="form.customer_id"
        @close="showVehicleModal = false"
        @saved="onVehicleSaved"
    />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import VehicleFormModal from '@/Components/Vehicles/VehicleFormModal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    workOrder: {
        type: Object,
        default: null,
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    customers: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'saved']);

const vehicles = ref([]);
const loadingVehicles = ref(false);
const selectedServiceIds = ref(new Set());
const showCustomerModal = ref(false);
const showVehicleModal = ref(false);

const form = useForm({
    customer_id: '',
    vehicle_id: '',
    notes: '',
    items: [{ title: '', qty: 1, unit_price: 0 }],
});

const grandTotal = computed(() => {
    return form.items
        .reduce((sum, item) => sum + (item.qty * item.unit_price), 0)
        .toFixed(2);
});

function onCustomerChange() {
    form.vehicle_id = '';
    if (form.customer_id) {
        loadVehicles();
    } else {
        vehicles.value = [];
    }
}

async function loadVehicles() {
    if (!form.customer_id) return;
    
    loadingVehicles.value = true;
    try {
        const response = await fetch(`/app/api/vehicles?customer_id=${form.customer_id}`);
        if (response.ok) {
            vehicles.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to load vehicles:', error);
    } finally {
        loadingVehicles.value = false;
    }
}

function getVehicleName(vehicle) {
    if (vehicle.make?.name) {
        return `${vehicle.make.name} ${vehicle.model?.name || ''} ${vehicle.year || ''}`.trim();
    }
    return vehicle.make_other || '';
}

function isServiceSelected(serviceId) {
    return selectedServiceIds.value.has(serviceId);
}

function toggleService(service) {
    if (selectedServiceIds.value.has(service.id)) {
        selectedServiceIds.value.delete(service.id);
        const index = form.items.findIndex(item => item.service_id === service.id);
        if (index > -1 && form.items.length > 1) {
            form.items.splice(index, 1);
        }
    } else {
        selectedServiceIds.value.add(service.id);
        form.items.push({
            service_id: service.id,
            title: service.name,
            qty: 1,
            unit_price: parseFloat(service.default_price) || 0,
        });
    }
}

function addItem() {
    form.items.push({ title: '', qty: 1, unit_price: 0 });
}

function removeItem(index) {
    if (form.items.length > 1) {
        const item = form.items[index];
        if (item.service_id) {
            selectedServiceIds.value.delete(item.service_id);
        }
        form.items.splice(index, 1);
    }
}

function onCustomerSaved(newCustomer) {
    // Reload page to get updated customers list, then select the new customer
    router.reload({ 
        only: ['customers'],
        onSuccess: () => {
            if (newCustomer?.id) {
                form.customer_id = newCustomer.id;
                loadVehicles();
            }
        }
    });
}

function onVehicleSaved(newVehicle) {
    // Reload vehicles for selected customer
    loadVehicles().then(() => {
        if (newVehicle?.id) {
            form.vehicle_id = newVehicle.id;
        }
    });
}

function submitForm() {
    const url = props.workOrder 
        ? `/app/work-orders/${props.workOrder.id}` 
        : '/app/work-orders';
    
    const method = props.workOrder ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.items = [{ title: '', qty: 1, unit_price: 0 }];
            vehicles.value = [];
            selectedServiceIds.value.clear();
            emit('saved');
            emit('close');
            router.reload({ only: ['workOrders'] });
        },
    });
}

// Reset form when modal opens for create
watch(() => props.show, (open) => {
    if (open && !props.workOrder) {
        form.reset();
        form.items = [{ title: '', qty: 1, unit_price: 0 }];
        vehicles.value = [];
        selectedServiceIds.value.clear();
    }
});

// Initialize form for edit mode
watch(() => props.workOrder, (newWorkOrder) => {
    if (newWorkOrder) {
        form.customer_id = newWorkOrder.customer_id || '';
        form.vehicle_id = newWorkOrder.vehicle_id || '';
        form.notes = newWorkOrder.notes || '';
        form.items = newWorkOrder.items?.length 
            ? newWorkOrder.items.map(item => ({
                service_id: item.service_id,
                title: item.title,
                qty: item.qty,
                unit_price: item.unit_price,
            }))
            : [{ title: '', qty: 1, unit_price: 0 }];
        
        if (newWorkOrder.customer_id) {
            loadVehicles();
        }
    }
}, { immediate: true });
</script>
