<template>
    <DialogModal :show="show" @close="handleClose">
        <template #title>
            {{ form.id ? $t('purchasing.suppliers.edit') : $t('purchasing.suppliers.add') }}
        </template>

        <template #content>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Type -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ $t('purchasing.suppliers.type') }}
                    </label>
                    <div class="flex gap-4">
                        <label class="relative flex items-center p-4 rounded-xl border cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                            :class="form.type === 'parts' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 ring-1 ring-indigo-500' : 'border-gray-200 dark:border-gray-700'"
                        >
                            <input type="radio" v-model="form.type" value="parts" class="sr-only" />
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600"
                                    :class="{'border-indigo-500 bg-indigo-500': form.type === 'parts'}"
                                >
                                    <span v-if="form.type === 'parts'" class="w-2 h-2 rounded-full bg-white"></span>
                                </span>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $t('purchasing.suppliers.type_parts') }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.type_parts_desc') }}</span>
                                </div>
                            </div>
                        </label>

                        <label class="relative flex items-center p-4 rounded-xl border cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                            :class="form.type === 'services' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 ring-1 ring-purple-500' : 'border-gray-200 dark:border-gray-700'"
                        >
                            <input type="radio" v-model="form.type" value="services" class="sr-only" />
                            <div class="flex items-center gap-3">
                                <span class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600"
                                    :class="{'border-purple-500 bg-purple-500': form.type === 'services'}"
                                >
                                    <span v-if="form.type === 'services'" class="w-2 h-2 rounded-full bg-white"></span>
                                </span>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $t('purchasing.suppliers.type_services') }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $t('purchasing.suppliers.type_services_desc') }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Name -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 shadow-sm transition-all', form.errors.name ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-indigo-500 dark:focus:border-indigo-500']"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-500">{{ form.errors.name }}</p>
                </div>

                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.code') }}
                    </label>
                    <input
                        v-model="form.code"
                        type="text"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                        :placeholder="$t('purchasing.suppliers.code_placeholder')"
                    />
                </div>

                <!-- Contact Person -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.contact') }}
                    </label>
                    <input
                        v-model="form.contact_person"
                        type="text"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                    />
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.phone') }}
                    </label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.email') }}
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                    />
                </div>

                <!-- Tax Number -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.tax_number') }}
                    </label>
                    <input
                        v-model="form.tax_number"
                        type="text"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                    />
                </div>

                <!-- CR Number -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.cr_number') }}
                    </label>
                    <input
                        v-model="form.cr_number"
                        type="text"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                    />
                </div>

                <!-- Address Section -->
                <div class="md:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-5 mt-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $t('purchasing.suppliers.address_details') }}
                        </h3>
                         <button
                            type="button"
                            @click="clearAddressFields"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        >
                            {{ $t('purchasing.suppliers.clear_address') }}
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                         <!-- Map Container -->
                        <div class="order-1 lg:order-2">
                            <div 
                                ref="mapContainer" 
                                class="w-full h-[260px] rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 overflow-hidden"
                            >
                                <div v-if="!mapReady" class="h-full flex flex-col items-center justify-center text-center p-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('common.loading_map') }}</p>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                                {{ $t('purchasing.suppliers.map.click_hint') }}
                            </p>
                        </div>

                         <!-- Address Fields -->
                         <div class="order-2 lg:order-1 space-y-3">
                            <!-- Address Line -->
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('purchasing.suppliers.address_line') }}
                                </label>
                                <textarea 
                                    v-model="form.address"
                                    rows="2"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all resize-none placeholder-gray-400 dark:placeholder-gray-600"
                                ></textarea>
                            </div>

                            <!-- Building + Postal + District -->
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('purchasing.suppliers.building_number') }}
                                    </label>
                                    <input v-model="form.building_number" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('purchasing.suppliers.postal_code') }}
                                    </label>
                                    <input v-model="form.postal_code" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('purchasing.suppliers.district') }}
                                    </label>
                                    <input v-model="form.district" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                            </div>
                            
                            <!-- City + Region + Country -->
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('purchasing.suppliers.city') }}
                                    </label>
                                    <input v-model="form.city" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('purchasing.suppliers.region') }}
                                    </label>
                                    <input v-model="form.region" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $t('purchasing.suppliers.country') }}
                                    </label>
                                    <input v-model="form.country" type="text" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                            </div>
                            
                            <!-- Lat/Lng -->
                             <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('purchasing.suppliers.form.lat') }}</label>
                                    <input type="number" step="any" dir="ltr" v-model="form.lat" @change="updateMarkerFromInputs" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('purchasing.suppliers.form.lng') }}</label>
                                    <input type="number" step="any" dir="ltr" v-model="form.lng" @change="updateMarkerFromInputs" class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bank Section -->
                <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ $t('purchasing.suppliers.bank_info') }}</h3>
                </div>

                <!-- Bank Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.bank_name') }}
                    </label>
                    <input
                        v-model="form.bank_name"
                        type="text"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                    />
                </div>

                <!-- IBAN -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('purchasing.suppliers.iban') }}
                    </label>
                    <input
                        v-model="form.iban"
                        type="text"
                        dir="ltr"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all placeholder-gray-400 dark:placeholder-gray-600"
                        placeholder="SA..."
                    />
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('common.notes') }}
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/20 focus:border-indigo-500 dark:focus:border-indigo-500 shadow-sm transition-all resize-none placeholder-gray-400 dark:placeholder-gray-600"
                    ></textarea>
                </div>
                
                <!-- Active Status -->
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                         <div class="relative inline-block w-11 h-6 transition-colors duration-200 ease-in-out border-2 border-transparent rounded-full cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                             :class="form.is_active ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'"
                             @click="form.is_active = !form.is_active"
                        >
                            <span
                                class="inline-block w-5 h-5 transform transition duration-200 ease-in-out bg-white rounded-full shadow ring-0"
                                :class="form.is_active ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                            ></span>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ form.is_active ? $t('common.active') : $t('common.inactive') }}
                        </span>
                    </label>
                </div>
            </div>
        </template>

        <template #footer>
            <button
                @click="close"
                class="px-4 py-2.5 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 mr-3"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                @click="submit"
                :disabled="form.processing"
                class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 transition-all disabled:opacity-50 disabled:shadow-none"
            >
                {{ form.processing ? $t('common.saving') : $t('common.save') }}
            </button>
        </template>
    </DialogModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import { ref, watch, nextTick, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default marker icon issue
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const props = defineProps({
    show: Boolean,
    supplier: Object,
});

const emit = defineEmits(['close', 'saved']);
const { locale, t } = useI18n();
const { confirm } = useConfirm();

const isDirty = ref(false);
const initialFormData = ref(null);

const mapContainer = ref(null);
const mapReady = ref(false);
const geocodingLoading = ref(false);

let map = null;
let marker = null;
const defaultLat = 24.7136;
const defaultLng = 46.6753;

const form = useForm({
    id: null,
    name: '',
    type: 'parts',
    code: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    city: '',
    region: '',
    postal_code: '',
    building_number: '',
    district: '',
    street: '',
    country: '',
    lat: null,
    lng: null,
    tax_number: '',
    cr_number: '',
    bank_name: '',
    iban: '',
    notes: '',
    is_active: true,
});

watch(() => props.supplier, (supplier) => {
    if (supplier) {
        form.id = supplier.id;
        form.name = supplier.name;
        form.type = supplier.type || 'parts';
        form.code = supplier.code;
        form.contact_person = supplier.contact_person;
        form.phone = supplier.phone;
        form.email = supplier.email;
        form.address = supplier.address;
        form.city = supplier.city;
        form.region = supplier.region;
        form.postal_code = supplier.postal_code;
        form.building_number = supplier.building_number;
        form.district = supplier.district;
        form.street = supplier.street;
        form.country = supplier.country;
        form.lat = supplier.lat;
        form.lng = supplier.lng;
        form.tax_number = supplier.tax_number;
        form.cr_number = supplier.cr_number;
        form.bank_name = supplier.bank_name;
        form.iban = supplier.iban;
        form.notes = supplier.notes;
        form.is_active = supplier.is_active !== false;
    } else {
        form.reset();
        form.id = null;
        form.type = 'parts';
        form.is_active = true;
    }
}, { immediate: true });

// Map Methods
watch(() => props.show, async (open) => {
    if (open) {
        if (!props.supplier) {
            form.reset();
            form.type = 'parts';
            form.is_active = true;
        }
        await nextTick();
        initMap();
        setTimeout(() => {
            if (map) map.invalidateSize();
            // Snapshot initial form data
            initialFormData.value = JSON.stringify(form.data());
            isDirty.value = false;
        }, 300);
    } else {
        destroyMap();
    }
});

// Track form changes
watch(() => form.data(), (newData) => {
    if (initialFormData.value) {
        const currentData = JSON.stringify(newData);
        isDirty.value = currentData !== initialFormData.value;
    }
}, { deep: true });

import { onMounted } from 'vue';

onMounted(async () => {
    if (props.show) {
        await nextTick();
        initMap();
        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 300);
    }
});

function initMap() {
    if (!mapContainer.value || map) return;

    const startLat = form.lat || defaultLat;
    const startLng = form.lng || defaultLng;

    map = L.map(mapContainer.value).setView([startLat, startLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    if (form.lat && form.lng) {
        marker = L.marker([form.lat, form.lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    map.on('click', onMapClick);
    mapReady.value = true;
}

function destroyMap() {
    if (map) {
        map.off();
        map.remove();
        map = null;
        marker = null;
        mapReady.value = false;
    }
}

function onMapClick(e) {
    const { lat, lng } = e.latlng;
    setMarker(lat, lng);
}

function onMarkerDrag(e) {
    const { lat, lng } = e.target.getLatLng();
    form.lat = parseFloat(lat.toFixed(7));
    form.lng = parseFloat(lng.toFixed(7));
    reverseGeocode(form.lat, form.lng);
}

function setMarker(lat, lng) {
    form.lat = parseFloat(lat.toFixed(7));
    form.lng = parseFloat(lng.toFixed(7));

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    map.panTo([lat, lng]);
    reverseGeocode(form.lat, form.lng);
}

function updateMarkerFromInputs() {
    const lat = parseFloat(form.lat);
    const lng = parseFloat(form.lng);

    if (!isNaN(lat) && !isNaN(lng) && map) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', onMarkerDrag);
        }
        map.setView([lat, lng], 15);
    }
}

function clearAddressFields() {
    form.address = '';
    form.building_number = '';
    form.postal_code = '';
    form.district = '';
    form.city = '';
    form.region = '';
    form.country = '';
    form.lat = null;
    form.lng = null;
    if (marker && map) {
        map.removeLayer(marker);
        marker = null;
    }
}

async function reverseGeocode(lat, lng) {
    if (geocodingLoading.value) return;
    geocodingLoading.value = true;
    try {
        const lang = locale.value === 'ar' ? 'ar' : 'en';
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=${lang}`,
            { headers: { 'User-Agent': 'Carag-App/1.0' } }
        );
        if (!response.ok) throw new Error('Geocoding failed');
        const data = await response.json();
        if (data && data.address) {
            const addr = data.address;
            form.country = addr.country || '';
            form.region = addr.state || addr.region || '';
            form.city = addr.city || addr.town || addr.village || addr.municipality || '';
            form.district = addr.suburb || addr.neighbourhood || addr.quarter || addr.district || '';
            form.postal_code = addr.postcode || '';
            form.building_number = addr.house_number || '';
            const addressParts = [];
            if (addr.road) addressParts.push(addr.road);
            if (addr.neighbourhood && addr.neighbourhood !== form.district) addressParts.push(addr.neighbourhood);
            form.address = addressParts.length > 0 ? addressParts.join('، ') : (data.display_name?.split(',').slice(0, 2).join('،') || '');
        }
    } catch (error) {
        // Silently fail or use toast if critical
    } finally {
        geocodingLoading.value = false;
    }
}

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
    isDirty.value = false;
    initialFormData.value = null;
};

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
    close();
}

const submit = () => {
    if (form.id) {
        form.put(route('app.purchasing.suppliers.update', form.id), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('app.purchasing.suppliers.store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                emit('saved', page.props.flash?.data || form.data());
                close();
            },
        });
    }
};

onUnmounted(() => {
    destroyMap();
});
</script>

<style>
/* Leaflet RTL fixes */
.leaflet-container {
    z-index: 0;
}
</style>
