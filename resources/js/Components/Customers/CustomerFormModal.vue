<template>
    <BaseModal :show="show" @close="handleClose" size="xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                {{ customer ? $t('customers.edit.title') : $t('customers.create.title') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Type Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ $t('customers.form.type') }}
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <label
                        :class="[
                            'relative flex flex-col items-center p-3 rounded-xl border-2 cursor-pointer transition-all text-center',
                            form.type === 'individual'
                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        ]"
                    >
                        <input type="radio" v-model="form.type" value="individual" class="sr-only" />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center flex-shrink-0 mb-2">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-900 dark:text-white truncate w-full">{{ $t('customers.type.individual') }}</span>
                    </label>
                    <label
                        :class="[
                            'relative flex flex-col items-center p-3 rounded-xl border-2 cursor-pointer transition-all text-center',
                            form.type === 'company'
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        ]"
                    >
                        <input type="radio" v-model="form.type" value="company" class="sr-only" />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center flex-shrink-0 mb-2">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-900 dark:text-white truncate w-full">{{ $t('customers.type.company') }}</span>
                    </label>
                    <label
                        :class="[
                            'relative flex flex-col items-center p-3 rounded-xl border-2 cursor-pointer transition-all text-center',
                            form.type === 'government'
                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        ]"
                    >
                        <input type="radio" v-model="form.type" value="government" class="sr-only" />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-lime-500 flex items-center justify-center flex-shrink-0 mb-2">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-900 dark:text-white truncate w-full">{{ $t('customers.type.government') }}</span>
                    </label>
                    <label
                        :class="[
                            'relative flex flex-col items-center p-3 rounded-xl border-2 cursor-pointer transition-all text-center',
                            form.type === 'vip'
                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        ]"
                    >
                        <input type="radio" v-model="form.type" value="vip" class="sr-only" />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-yellow-500 flex items-center justify-center flex-shrink-0 mb-2">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-gray-900 dark:text-white truncate w-full">{{ $t('customers.type.vip') }}</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Name -->
                <div :class="['company', 'government'].includes(form.type) ? '' : 'md:col-span-2'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('customers.form.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.name"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                    />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                </div>

                <!-- Contact Name (Companies & Government) -->
                <div v-if="['company', 'government'].includes(form.type)">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('customers.form.contact_name') }}
                    </label>
                    <input 
                        type="text" 
                        v-model="form.contact_name"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                    />
                    <p v-if="form.errors.contact_name" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.contact_name }}</p>
                </div>

                <!-- Tax Number (Companies Only - Optional) -->
                <div v-if="form.type === 'company'" class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('customers.form.tax_number') }}
                    </label>
                    <input 
                        type="text" 
                        v-model="form.tax_number"
                        :placeholder="$t('customers.form.tax_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                    />
                    <p v-if="form.errors.tax_number" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.tax_number }}</p>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('customers.form.phone') }} <span class="text-red-500">*</span>
                    </label>
                    <vue-tel-input
                        v-model="form.phone"
                        @on-input="(value) => onPhoneInput('phone', value)"
                        :inputOptions="{ placeholder: '05xxxxxxxx' }"
                        mode="international"
                        :validCharactersOnly="false"
                        class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent transition-all ltr"
                    ></vue-tel-input>
                    <p v-if="form.errors.phone" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.phone }}</p>
                </div>

                <!-- WhatsApp -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $t('customers.form.whatsapp') }}
                        </label>
                        <button
                            v-if="form.phone && normalizePhone(form.phone) !== normalizePhone(form.whatsapp)"
                            type="button"
                            @click="form.whatsapp = form.phone"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                        >
                            {{ $t('customers.form.copy_from_phone') }}
                        </button>
                    </div>
                    <vue-tel-input
                        v-model="form.whatsapp"
                        @on-input="(value) => onPhoneInput('whatsapp', value)"
                        :inputOptions="{ placeholder: '05xxxxxxxx' }"
                        mode="international"
                        :validCharactersOnly="false"
                        class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent transition-all ltr"
                    ></vue-tel-input>
                    <p v-if="form.errors.whatsapp" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.whatsapp }}</p>
                </div>

                <!-- Email -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('customers.form.email') }}
                    </label>
                    <input 
                        type="email" 
                        v-model="form.email"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                    />
                    <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>

                <!-- Quick Actions -->
                <div class="md:col-span-2">
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!form.phone"
                            @click="quickCall"
                        >
                            <span>📞</span>
                            <span>{{ $t('customers.quick.call') }}</span>
                        </button>

                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!form.whatsapp"
                            @click="quickWhatsApp"
                        >
                            <span>💬</span>
                            <span>{{ $t('customers.quick.whatsapp') }}</span>
                        </button>

                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :disabled="!form.email"
                            @click="quickEmail"
                        >
                            <span>✉️</span>
                            <span>{{ $t('customers.quick.email') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('customers.form.notes') }}
                    </label>
                    <textarea 
                        v-model="form.notes"
                        rows="2"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-all"
                    ></textarea>
                </div>
            </div>

            <!-- Address Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $t('customers.form.address') }}
                    </h3>
                    <button
                        type="button"
                        @click="clearAddressFields"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        :title="$t('customers.form.clear_address')"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ $t('customers.form.clear_address') }}
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
                                <svg class="w-8 h-8 text-gray-400 mb-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                            {{ $t('customers.map.click_hint') }}
                        </p>
                    </div>

                    <!-- Address Fields -->
                    <div class="order-2 lg:order-1 space-y-3">
                        <!-- Address Line -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                {{ $t('customers.form.address_line') }}
                            </label>
                            <textarea 
                                v-model="form.address_line"
                                rows="2"
                                :placeholder="$t('customers.form.address_placeholder')"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-all"
                            ></textarea>
                        </div>

                        <!-- Building + Postal + Zip -->
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.building_number') }}
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.building_number"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.postal_code') }}
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.postal_code"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.district') }}
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.district"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>

                        <!-- City + Region -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.city') }}
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.city"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.region') }}
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.region"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>

                        <!-- Country -->
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ $t('customers.form.country') }}
                            </label>
                            <input 
                                type="text" 
                                v-model="form.country"
                                :placeholder="$t('customers.form.country_placeholder')"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            />
                        </div>

                        <!-- Lat/Lng (Hidden but editable on demand) -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.lat') }}
                                </label>
                                <input 
                                    type="number" 
                                    step="any"
                                    v-model="form.lat"
                                    @change="updateMarkerFromInputs"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.lng') }}
                                </label>
                                <input 
                                    type="number" 
                                    step="any"
                                    v-model="form.lng"
                                    @change="updateMarkerFromInputs"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                />
                            </div>
                        </div>
                    </div>
                </div>
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
                {{ form.processing ? $t('common.loading') : $t('customers.form.submit') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import BaseModal from '@/Components/BaseModal.vue';
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
    show: {
        type: Boolean,
        default: false,
    },
    customer: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);
const { t, locale } = useI18n(); // Access t function and current locale

const mapContainer = ref(null);
const mapReady = ref(false);
const geocodingLoading = ref(false);

let map = null;
let marker = null;

const defaultLat = 24.7136;
const defaultLng = 46.6753;

const { confirm } = useConfirm();
const isDirty = ref(false);
const initialFormData = ref(null);

const form = useForm({
    type: 'individual',
    name: '',
    contact_name: '',
    phone: '',
    whatsapp: '',
    email: '',
    notes: '',
    tax_number: '',
    address_line: '',
    building_number: '',
    postal_code: '',
    district: '',
    city: '',
    region: '',
    country: '',
    lat: null,
    lng: null,
});

// Initialize form when customer prop changes (for edit mode)
watch(() => props.customer, (newCustomer) => {
    if (newCustomer) {
        form.type = newCustomer.type || 'individual';
        form.name = newCustomer.name || '';
        form.contact_name = newCustomer.contact_name || '';
        form.phone = newCustomer.phone || '';
        form.whatsapp = newCustomer.whatsapp || '';
        form.email = newCustomer.email || '';
        form.notes = newCustomer.notes || '';
        form.tax_number = newCustomer.tax_number || '';
        form.address_line = newCustomer.address_line || '';
        form.building_number = newCustomer.building_number || '';
        form.postal_code = newCustomer.postal_code || '';
        form.district = newCustomer.district || '';
        form.city = newCustomer.city || '';
        form.region = newCustomer.region || '';
        form.country = newCustomer.country || '';
        form.lat = newCustomer.lat || null;
        form.lng = newCustomer.lng || null;
    }
}, { immediate: true });

// Handle modal open/close
watch(() => props.show, async (open) => {
    if (open) {
        if (!props.customer) {
            form.reset();
        }
        await nextTick();
        initMap();
        
        // Fix map size after modal animation
        setTimeout(() => {
            if (map) map.invalidateSize();
            // Snapshot initial form data after map init
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
        const isChanged = currentData !== initialFormData.value;
        if (isChanged !== isDirty.value) {
            console.log('[CustomerFormModal] isDirty changed to:', isChanged);
        }
        isDirty.value = isChanged;
    }
}, { deep: true });

// Auto-copy phone to whatsapp
watch(() => form.phone, (newPhone, oldPhone) => {
    const cleanWhatsapp = normalizePhone(form.whatsapp);
    const cleanOldPhone = normalizePhone(oldPhone);
    
    // Sync if whatsapp is empty OR if it matched the previous phone number
    if (!form.whatsapp || cleanWhatsapp === cleanOldPhone) {
        form.whatsapp = newPhone || '';
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

    // Add initial marker if we have coordinates
    if (form.lat && form.lng) {
        marker = L.marker([form.lat, form.lng], { draggable: true }).addTo(map);
        marker.on('dragend', onMarkerDrag);
    }

    // Handle map clicks
    map.on('click', onMapClick);

    mapReady.value = true;
}

function resetForm() {
    console.log('[CustomerFormModal] resetForm called');
    form.reset();
    isDirty.value = false;
    initialFormData.value = null;
}

async function handleClose() {
    console.log('[CustomerFormModal] handleClose called, isDirty:', isDirty.value);
    console.log('[CustomerFormModal] initialFormData:', initialFormData.value ? 'SET' : 'NULL');
    console.log('[CustomerFormModal] current form:', JSON.stringify(form.data()));
    
    if (isDirty.value) {
        console.log('[CustomerFormModal] Showing confirmation dialog...');
        const confirmed = await confirm({
            title: t('common.unsaved_changes'),
            message: t('common.unsaved_changes_message'),
            confirmText: t('common.yes_close'),
            cancelText: t('common.cancel'),
            type: 'warning',
        });
        
        if (!confirmed) {
            console.log('[CustomerFormModal] User cancelled close');
            return;
        }
        console.log('[CustomerFormModal] User confirmed close');
    }
    
    resetForm();
    emit('close');
}

function destroyMap() {
    if (map) {
        map.off(); // Remove all event listeners
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
    
    // Reverse geocode to fill address fields
    reverseGeocode(form.lat, form.lng);
}

// Reverse geocoding using OpenStreetMap Nominatim
async function reverseGeocode(lat, lng) {
    if (geocodingLoading.value) return;
    
    geocodingLoading.value = true;
    
    try {
        const lang = locale.value === 'ar' ? 'ar' : 'en';
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=${lang}`,
            {
                headers: {
                    'User-Agent': 'Carag-App/1.0'
                }
            }
        );
        
        if (!response.ok) throw new Error('Geocoding failed');
        
        const data = await response.json();
        
        if (data && data.address) {
            const addr = data.address;
            
            // Fill form fields from geocoding response
            form.country = addr.country || '';
            form.region = addr.state || addr.region || '';
            form.city = addr.city || addr.town || addr.village || addr.municipality || '';
            form.district = addr.suburb || addr.neighbourhood || addr.quarter || addr.district || '';
            form.postal_code = addr.postcode || '';
            
            // Building number
            form.building_number = addr.house_number || '';
            
            // Build address line from available info
            const addressParts = [];
            if (addr.road) addressParts.push(addr.road);
            if (addr.neighbourhood && addr.neighbourhood !== form.district) addressParts.push(addr.neighbourhood);
            
            // Always update address_line with formatted address
            form.address_line = addressParts.length > 0 ? addressParts.join('، ') : (data.display_name?.split(',').slice(0, 2).join('،') || '');
        }
    } catch (error) {
        console.warn('Reverse geocoding failed:', error);
    } finally {
        geocodingLoading.value = false;
    }
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

// Clear all address fields
function clearAddressFields() {
    form.address_line = '';
    form.building_number = '';
    form.postal_code = '';
    form.district = '';
    form.city = '';
    form.region = '';
    form.country = '';
    form.lat = null;
    form.lng = null;
    
    // Remove marker from map
    if (marker && map) {
        map.removeLayer(marker);
        marker = null;
    }
}

function submitForm() {
    const url = props.customer 
        ? `/app/customers/${props.customer.id}` 
        : '/app/customers';
    
    const method = props.customer ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: (page) => {
            // Laravel sends the customer via with('customer', $customer)
            // which Inertia makes available in page.props
            const savedCustomer = page.props.customer || props.customer || form.data();
            
            console.log('[CustomerFormModal] onSuccess page.props:', page.props);
            console.log('[CustomerFormModal] Customer saved successfully:', savedCustomer);
            
            form.reset();
            emit('saved', savedCustomer);
            emit('close');
        },
    });
}

// Helper to convert Arabic numerals to English
function convertArabicToEnglish(value) {
    if (!value) return value;
    const arabic = '٠١٢٣٤٥٦٧٨٩';
    return value.replace(/[٠-٩]/g, d => arabic.indexOf(d));
}

// Quick actions
function normalizePhone(p) {
    return convertArabicToEnglish(p || '').replace(/[^\d+]/g, '');
}

// Handle phone input to convert numerals real-time and remove spaces
function onPhoneInput(type, value) {
    const converted = convertArabicToEnglish(value);
    // Remove spaces that vue-tel-input adds
    const normalized = converted.replace(/\s/g, '');
    
    if (value !== normalized) {
        form[type] = normalized;
    }
}

function quickCall() {
    if (!form.phone) return;
    window.open(`tel:${normalizePhone(form.phone)}`, '_self');
}

function quickWhatsApp() {
    if (!form.whatsapp) return;
    const phone = normalizePhone(form.whatsapp).replace(/^\+/, '');
    window.open(`https://wa.me/${phone}`, '_blank');
}

function quickEmail() {
    if (!form.email) return;
    window.open(`mailto:${form.email}`, '_self');
}

onUnmounted(() => {
    destroyMap();
});
</script>

<style>
/* Leaflet RTL fixes */
.leaflet-container {
    z-index: 0;
}

/* Vue Tel Input Customization */
.vue-tel-input {
    border-radius: 0.75rem !important;
    border-color: #d1d5db !important; /* gray-300 */
}
.dark .vue-tel-input {
    border-color: #4b5563 !important; /* gray-600 */
    background-color: #111827 !important; /* gray-900 */
}
.vue-tel-input:focus-within {
    box-shadow: 0 0 0 2px #6366f1 !important; /* ring-indigo-500 */
    border-color: transparent !important;
}
.vti__input {
    background-color: transparent !important;
    border-radius: 0 0.75rem 0.75rem 0 !important;
}
.dark .vti__input {
    color: #f3f4f6 !important; /* gray-100 */
}
.vti__dropdown {
    border-radius: 0.75rem 0 0 0.75rem !important;
}
.dark .vti__dropdown-list {
    background-color: #1f2937 !important; /* gray-800 */
    border-color: #374151 !important; /* gray-700 */
}
.dark .vti__dropdown-item.highlighted {
    background-color: #374151 !important; /* gray-700 */
}
.dark .vti__dropdown-item strong,
.dark .vti__dropdown-item span {
    color: #f3f4f6 !important; /* gray-100 */
}
</style>
