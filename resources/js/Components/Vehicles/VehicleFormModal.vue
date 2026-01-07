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
                <SaudiPlateInput 
                    ref="plateInput"
                    v-model="form.plate_number" 
                    :error="form.errors.plate_number"
                />
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
                        <SearchableSelect
                            v-model="form.make_id"
                            @change="onMakeChange"
                            :options="[...makes, {id: '__other__', name: $t('common.other')}]"
                            option-label="name"
                            option-value="id"
                            :label="''"
                            :placeholder="$t('common.choose')"
                            :error="form.errors.make_id"
                        />
                    </div>

                    <!-- Model -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.model') }}</label>
                        <SearchableSelect
                            v-if="form.make_id && form.make_id !== '__other__'"
                            v-model="form.model_id"
                            :options="[...availableModels, {id: '__other__', name: $t('common.other')}]"
                            option-label="name"
                            option-value="id"
                            :label="''"
                            :placeholder="$t('common.choose')"
                            :error="form.errors.model_id"
                        />
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
                    <!-- Only show add button if no default customer -->
                    <button
                        v-if="!defaultCustomerId"
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
                <!-- Show read-only customer info when defaultCustomerId is set -->
                <div v-if="defaultCustomerId && selectedCustomer" class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center">
                        <span class="text-white font-bold">{{ selectedCustomer.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ selectedCustomer.name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400" dir="ltr">{{ selectedCustomer.phone }}</p>
                    </div>
                </div>
                <!-- Show dropdown if no default customer -->
                <SearchableSelect
                    v-else
                    v-model="form.customer_id"
                    :options="localCustomers"
                    :placeholder="$t('common.choose')"
                    :option-label="c => `${c.name} (${c.phone})`"
                    option-value="id"
                    :label="''"
                    :error="form.errors.customer_id"
                />
            </div>

            <!-- Vehicle Details Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('vehicles.form.details') }}</span>
                
                <div class="grid grid-cols-3 gap-3">
                    <!-- Year -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.year') }}</label>
                        <SearchableSelect
                            v-model="form.year"
                            :options="yearOptions"
                            :placeholder="$t('common.choose')"
                            :label="''"
                        />
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('vehicles.form.color') }}</label>
                        <SearchableSelect
                            v-model="form.color"
                            :options="colors"
                            option-label="name"
                            option-value="name"
                            :placeholder="$t('common.choose')"
                            :label="''"
                        />
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
import SearchableSelect from '@/Components/SearchableSelect.vue';
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
    defaultCustomerId: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(['close', 'saved', 'customer-created']);

const showCustomerModal = ref(false);
const localCustomers = computed(() => props.customers || []);
const selectedCustomer = computed(() => {
    if (props.defaultCustomerId) {
        return localCustomers.value.find(c => c.id === props.defaultCustomerId) || null;
    }
    return null;
});
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
    console.log('[VehicleFormModal] Watch fired - show:', open, 'defaultCustomerId:', props.defaultCustomerId);
    if (open) {
        if (!props.vehicle) {
            form.reset();
            // Auto-fill customer if defaultCustomerId is provided
            if (props.defaultCustomerId) {
                console.log('[VehicleFormModal] Setting customer_id to:', props.defaultCustomerId);
                form.customer_id = props.defaultCustomerId;
                console.log('[VehicleFormModal] form.customer_id after set:', form.customer_id);
            }
        }
        // Snapshot initial form data after short delay to capture initial state
        setTimeout(() => {
            initialFormData.value = JSON.stringify(form.data());
            isDirty.value = false;
        }, 100);
    }
}, { immediate: true });

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

const plateInput = ref(null);

function validate() {
    form.clearErrors();
    let isValid = true;

    // 1. Plate Number Validation
    // Use the component's internal validation if available, otherwise fallback to simple check
    const isPlateValid = plateInput.value ? plateInput.value.validate() : !!form.plate_number;
    if (!isPlateValid) {
        form.setError('plate_number', ' '); // Trigger red border
        isValid = false;
    }

    // 2. Make Validation
    if (!form.make_id) {
        form.setError('make_id', ' ');
        isValid = false;
    } else if (form.make_id === '__other__') {
        // If Make is Other, Make Name is required
        if (!form.make_other) {
            form.setError('make_other', ' ');
            isValid = false;
        }
        // And Model Name is also required (since standard model dropdown is disabled)
        if (!form.model_other) {
            form.setError('model_other', ' ');
            isValid = false;
        }
    } else {
        // Normal Make selected
        // 3. Model Validation
        if (!form.model_id) {
            form.setError('model_id', ' ');
            isValid = false;
        } else if (form.model_id === '__other__') {
            // If Model is Other, Model Name is required
            if (!form.model_other) {
                form.setError('model_other', ' ');
                isValid = false;
            }
        }
    }

    // 4. Customer Validation
    console.log('[VehicleFormModal] Validating customer_id:', form.customer_id);
    if (!form.customer_id) {
        console.log('[VehicleFormModal] Customer validation FAILED');
        form.setError('customer_id', ' ');
        isValid = false;
    }

    console.log('[VehicleFormModal] Validation result:', isValid);
    return isValid;
}

function submitForm() {
    console.log('[VehicleFormModal] submitForm called');
    console.log('[VehicleFormModal] Form data:', form.data());
    if (!validate()) {
        console.log('[VehicleFormModal] Validation failed, not submitting');
        return;
    }

    const url = props.vehicle 
        ? `/app/vehicles/${props.vehicle.id}` 
        : '/app/vehicles';
    
    const method = props.vehicle ? 'put' : 'post';

    // Transform data before sending - convert empty strings to null for optional fields
    form.transform(data => ({
        ...data,
        make_id: data.make_id === '__other__' ? null : (data.make_id || null),
        model_id: data.model_id === '__other__' ? null : (data.model_id || null),
        year: data.year || null,
        odometer: data.odometer || null,
    }))[method](url, {
        preserveScroll: true,
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
