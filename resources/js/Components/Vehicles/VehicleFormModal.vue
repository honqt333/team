<template>
    <BaseModal :show="show" @close="handleClose" size="xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-cyan-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </div>
                {{ vehicle ? $t('vehicles.edit.title') : $t('vehicles.create.title') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-6">
            
            <!-- License Plate Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ $t('vehicles.form.plate') }} <span class="text-red-500">*</span>
                </label>
                <!-- Saudi Plate Interactive Input -->
                <SaudiPlateInput v-model="form.plate_number" />
                <p v-if="form.errors.plate_number" class="mt-2 text-sm text-red-600 dark:text-red-400 text-center">{{ form.errors.plate_number }}</p>
            </div>

            <!-- Make & Model Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('vehicles.form.make') }} / {{ $t('vehicles.form.model') }}</span>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <!-- Make -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.make') }} <span class="text-red-500">*</span></label>
                        <SelectInput
                            v-model="form.make_id"
                            @change="onMakeChange"
                            color="teal"
                            :error="form.errors.make_id"
                        >
                            <option value="">{{ $t('common.choose') }}</option>
                            <option v-for="make in makes" :key="make.id" :value="make.id">
                                {{ make.name }}
                            </option>
                            <option value="__other__">{{ $t('common.other') }}</option>
                        </SelectInput>
                    </div>

                    <!-- Model -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.model') }}</label>
                        <SelectInput
                            v-if="form.make_id && form.make_id !== '__other__'"
                            v-model="form.model_id"
                            color="teal"
                        >
                            <option value="">{{ $t('common.choose') }}</option>
                            <option v-for="model in availableModels" :key="model.id" :value="model.id">
                                {{ model.name }}
                            </option>
                            <option value="__other__">{{ $t('common.other') }}</option>
                        </SelectInput>
                        <input 
                            v-else
                            type="text"
                            disabled
                            :placeholder="$t('vehicles.form.model')"
                            class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-100 dark:bg-gray-900 text-gray-400 text-sm cursor-not-allowed"
                        />
                    </div>
                </div>

                <!-- Other Make/Model Inputs -->
                <div v-if="form.make_id === '__other__' || form.model_id === '__other__'" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 gap-3">
                        <div v-if="form.make_id === '__other__'">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.make_other') }} <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                v-model="form.make_other"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                :class="{ 'border-red-500': form.errors.make_other }"
                            />
                        </div>
                        <div v-if="form.make_id === '__other__' || form.model_id === '__other__'">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.model_other') }}</label>
                            <input 
                                type="text" 
                                v-model="form.model_other"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('vehicles.form.customer') }} <span class="text-red-500">*</span>
                    </span>
                    <button
                        type="button"
                        @click="showCustomerModal = true"
                        class="w-7 h-7 rounded-lg bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 hover:bg-teal-200 dark:hover:bg-teal-900/50 flex items-center justify-center transition-colors"
                        :title="$t('vehicles.quick_add_customer')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>
                <SelectInput
                    v-model="form.customer_id"
                    color="teal"
                    :error="form.errors.customer_id"
                >
                    <option value="">{{ $t('common.choose') }}</option>
                    <option v-for="customer in localCustomers" :key="customer.id" :value="customer.id">
                        {{ customer.name }} ({{ customer.phone }})
                    </option>
                </SelectInput>
            </div>

            <!-- Vehicle Details Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('vehicles.form.details') }}</span>
                
                <div class="grid grid-cols-3 gap-3">
                    <!-- Year -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.year') }}</label>
                        <SelectInput
                            v-model="form.year"
                            color="teal"
                        >
                            <option value="">{{ $t('common.choose') }}</option>
                            <option v-for="year in yearOptions" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </SelectInput>
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.color') }}</label>
                        <SelectInput
                            v-model="form.color"
                            color="teal"
                        >
                            <option value="">{{ $t('common.choose') }}</option>
                            <option v-for="color in colors" :key="color.id" :value="color.name">
                                {{ color.name }}
                            </option>
                        </SelectInput>
                    </div>

                    <!-- Odometer -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.odometer') }}</label>
                        <input 
                            type="number" 
                            v-model="form.odometer"
                            min="0"
                            :placeholder="$t('vehicles.form.odometer_placeholder')"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        />
                    </div>
                </div>

                <!-- VIN -->
                <div class="mt-3">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.vin') }}</label>
                    <input 
                        type="text" 
                        v-model="form.vin"
                        dir="ltr"
                        :placeholder="$t('vehicles.form.vin_placeholder')"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                    />
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('vehicles.form.notes') }}
                </label>
                <textarea 
                    v-model="form.notes"
                    rows="2"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none transition-all"
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
                class="px-5 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>

        <!-- Customer Quick-Add Modal -->
        <CustomerFormModal
            :show="showCustomerModal"
            :customer="null"
            @close="showCustomerModal = false"
            @saved="handleCustomerSaved"
        />
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import BaseModal from '@/Components/BaseModal.vue';
import SelectInput from '@/Components/SelectInput.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import SaudiPlateInput from '@/Components/Vehicles/SaudiPlateInput.vue';

const { t } = useI18n();
const { confirm } = useConfirm();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    vehicle: {
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

const emit = defineEmits(['close', 'saved', 'customer-created']);

const showCustomerModal = ref(false);
const localCustomers = computed(() => props.customers || []);
const pendingCustomerPhone = ref(null); // Store phone instead of ID
const isDirty = ref(false);
const initialFormData = ref(null);

// Generate year options (last 40 years)
const currentYear = new Date().getFullYear();
const yearOptions = computed(() => {
    const years = [];
    for (let y = currentYear + 1; y >= currentYear - 40; y--) {
        years.push(y);
    }
    return years;
});

// Available models based on selected make
const availableModels = computed(() => {
    if (!props.modelsByMake || !form.make_id || form.make_id === '__other__') {
        return [];
    }
    return props.modelsByMake[form.make_id] || [];
});

const form = useForm({
    customer_id: '',
    plate_number: '',
    make_id: '',
    model_id: '',
    make_other: '',
    model_other: '',
    year: '',
    color: '',
    vin: '',
    odometer: '',
    notes: '',
});



// Initialize form when vehicle prop changes (for edit mode)
watch(() => props.vehicle, (newVehicle) => {
    if (newVehicle) {
        form.customer_id = newVehicle.customer_id || '';
        form.plate_number = newVehicle.plate_number || '';
        form.make_id = newVehicle.make_id || (newVehicle.make_other ? '__other__' : '');
        form.model_id = newVehicle.model_id || (newVehicle.model_other ? '__other__' : '');
        form.make_other = newVehicle.make_other || '';
        form.model_other = newVehicle.model_other || '';
        form.year = newVehicle.year || '';
        form.color = newVehicle.color || '';
        form.vin = newVehicle.vin || '';
        form.odometer = newVehicle.odometer || '';
        form.notes = newVehicle.notes || '';
    }
}, { immediate: true });

// Reset form when modal opens for create
watch(() => props.show, (open) => {
    if (open) {
        if (!props.vehicle) {
            form.reset();
        }
        // Snapshot initial form data after short delay to capture initial state
        setTimeout(() => {
            initialFormData.value = JSON.stringify(form.data());
            isDirty.value = false;
        }, 100);
    }
});

// Track form changes
watch(() => form.data(), () => {
    if (initialFormData.value) {
        isDirty.value = JSON.stringify(form.data()) !== initialFormData.value;
    }
}, { deep: true });

// Watch for customers prop updates to auto-select pending customer
watch(() => props.customers, (newCustomers, oldCustomers) => {
    console.log('[VehicleFormModal] Customers watcher fired');
    console.log('[VehicleFormModal] Pending customer phone:', pendingCustomerPhone.value);
    console.log('[VehicleFormModal] New customers count:', newCustomers?.length);
    console.log('[VehicleFormModal] Old customers count:', oldCustomers?.length);
    
    if (pendingCustomerPhone.value && newCustomers) {
        // Find customer by phone number (unique identifier)
        const customerExists = newCustomers.find(c => 
            c.phone === pendingCustomerPhone.value || 
            c.phone?.replace(/\s/g, '') === pendingCustomerPhone.value?.replace(/\s/g, '')
        );
        console.log('[VehicleFormModal] Customer found by phone:', !!customerExists);
        
        if (customerExists) {
            console.log('[VehicleFormModal] AUTO-SELECTING customer ID:', customerExists.id);
            form.customer_id = customerExists.id;
            pendingCustomerPhone.value = null; // Clear after selection
            console.log('[VehicleFormModal] form.customer_id is now:', form.customer_id);
        } else {
            console.warn('[VehicleFormModal] Pending customer not found in updated list');
        }
    }
}, { deep: true });

function resetForm() {
    form.reset();
    isDirty.value = false;
    initialFormData.value = null;
}

async function handleClose() {
    if (isDirty.value) {
        const confirmed = await confirm({
            title: t('common.unsaved_changes'),
            message: t('common.unsaved_changes_message'),
            confirmText: t('common.yes_close'),
            cancelText: t('common.cancel'),
            type: 'warning',
        });
        
        if (!confirmed) return;
    }
    
    resetForm();
    emit('close');
}

function onMakeChange() {
    // Reset model when make changes
    form.model_id = '';
    form.model_other = '';
}

function handleCustomerSaved(savedCustomer) {
    showCustomerModal.value = false;
    
    console.log('[VehicleFormModal] Customer saved:', savedCustomer);
    
    // Store the phone number for finding customer after reload
    // Phone is unique and we have it before ID is assigned
    if (savedCustomer && savedCustomer.phone) {
        pendingCustomerPhone.value = savedCustomer.phone;
        console.log('[VehicleFormModal] Set pendingCustomerPhone:', pendingCustomerPhone.value);
    }
    
    // Emit the new customer to parent so it can reload customers
    emit('customer-created', savedCustomer);
}

// Listen for customer-reloaded event from parent
function handleCustomerReloaded(event) {
    const { customerId } = event.detail;
    console.log('[VehicleFormModal] Received customer-reloaded event:', customerId);
    console.log('[VehicleFormModal] Current customers:', props.customers?.length);
    
    if (customerId && props.customers) {
        const customerExists = props.customers.find(c => c.id === customerId);
        console.log('[VehicleFormModal] Customer found:', !!customerExists);
        
        if (customerExists) {
            console.log('[VehicleFormModal] Setting form.customer_id to:', customerId);
            form.customer_id = customerId;
            pendingCustomerId.value = null;
        }
    }
}

onMounted(() => {
    console.log('[VehicleFormModal] Component mounted, adding event listener');
    window.addEventListener('customer-reloaded', handleCustomerReloaded);
});

onUnmounted(() => {
    console.log('[VehicleFormModal] Component unmounting, removing event listener');
    window.removeEventListener('customer-reloaded', handleCustomerReloaded);
});

function submitForm() {
    const url = props.vehicle 
        ? `/app/vehicles/${props.vehicle.id}` 
        : '/app/vehicles';
    
    const method = props.vehicle ? 'put' : 'post';

    // Prepare data - convert empty strings to null for optional fields
    const data = {
        ...form.data(),
        make_id: form.make_id === '__other__' ? null : (form.make_id || null),
        model_id: form.model_id === '__other__' ? null : (form.model_id || null),
        year: form.year || null,
        odometer: form.odometer || null,
    };

    form[method](url, {
        preserveScroll: true,
        data,
        onSuccess: (page) => {
            // Get the saved vehicle from page props
            const savedVehicle = page.props.vehicle || props.vehicle || {
                ...form.data(),
                customer_id: form.customer_id,
                plate_number: form.plate_number,
            };
            
            console.log('[VehicleFormModal] Vehicle saved successfully:', savedVehicle);
            
            // Reset form and dirty state
            resetForm();
            emit('saved', savedVehicle);
            emit('close');
        },
    });
}
</script>
