<template>
    <BaseModal :show="show" @close="handleClose" size="max-w-2xl">
        <template #title>
            {{ form.id ? $t('purchasing.suppliers.edit') : $t('purchasing.suppliers.add') }}
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Type -->
            <div class="md:col-span-2" data-field="type">
                <SearchableSelect
                    v-model="form.type"
                    :options="supplierTypeOptions"
                    :label="$t('purchasing.suppliers.type')"
                    required
                    :error="form.errors.type"
                />
            </div>

            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.name') }}
                    <span class="text-red-500">*</span>
                </label>
                <AppInput name="name" v-model="form.name" type="text" :error="form.errors.name" />
            </div>

            <!-- Contact Person -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.contact') }}
                    <span class="text-red-500">*</span>
                </label>
                <AppInput
                    name="contact_person"
                    v-model="form.contact_person"
                    type="text"
                    :error="form.errors.contact_person"
                />
            </div>

            <!-- Phone -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.phone') }}
                </label>
                <div data-field="phone">
                    <vue-tel-input
                        v-model="form.phone"
                        @on-input="(value) => onPhoneInput('phone', value)"
                        :inputOptions="{ placeholder: '05xxxxxxxx' }"
                        mode="international"
                        :validCharactersOnly="false"
                        class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus-within:ring-2 focus-within:ring-focus-ring focus-within:border-transparent transition-all ltr"
                    ></vue-tel-input>
                </div>
                <p v-if="form.errors.phone" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.phone }}
                </p>
            </div>

            <!-- WhatsApp -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('customers.form.whatsapp') }}
                    </label>
                    <button
                        v-if="
                            form.phone &&
                            normalizePhone(form.phone) !== normalizePhone(form.whatsapp)
                        "
                        type="button"
                        @click="form.whatsapp = form.phone"
                        class="text-xs text-action-primary hover:text-selection-text transition-colors"
                    >
                        {{ $t('customers.form.copy_from_phone') }}
                    </button>
                </div>
                <div data-field="whatsapp">
                    <vue-tel-input
                        v-model="form.whatsapp"
                        @on-input="(value) => onPhoneInput('whatsapp', value)"
                        :inputOptions="{ placeholder: '05xxxxxxxx' }"
                        mode="international"
                        :validCharactersOnly="false"
                        class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus-within:ring-2 focus-within:ring-focus-ring focus-within:border-transparent transition-all ltr"
                    ></vue-tel-input>
                </div>
                <p
                    v-if="form.errors.whatsapp"
                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                >
                    {{ form.errors.whatsapp }}
                </p>
            </div>

            <!-- Tax Number -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.tax_number') }}
                </label>
                <AppInput
                    name="tax_number"
                    v-model="form.tax_number"
                    type="text"
                    dir="ltr"
                    maxlength="15"
                    :error="form.errors.tax_number"
                />
            </div>

            <!-- CR Number -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.cr_number') }}
                </label>
                <AppInput
                    name="cr_number"
                    v-model="form.cr_number"
                    type="text"
                    dir="ltr"
                    :error="form.errors.cr_number"
                />
            </div>

            <!-- Email -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.email') }}
                </label>
                <AppInput
                    name="email"
                    v-model="form.email"
                    type="email"
                    dir="ltr"
                    :error="form.errors.email"
                />
            </div>

            <!-- Address Details (md:col-span-2) -->
            <div class="md:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-5 mt-2">
                <div class="flex items-center justify-between mb-4">
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <svg
                            class="w-4 h-4 text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ $t('purchasing.suppliers.address_details') }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="fetchLocation"
                            :disabled="isLocating"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-action-primary hover:bg-selection-bg rounded-lg transition-colors disabled:opacity-50"
                            :title="$t('customers.form.locate_me')"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                :class="{ 'animate-spin': isLocating }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                />
                            </svg>
                            {{ isLocating ? $t('common.loading') : $t('customers.form.locate_me') }}
                        </button>
                        <button
                            type="button"
                            @click="clearAddressFields"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        >
                            {{ $t('purchasing.suppliers.clear_address') }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Map Container -->
                    <div class="order-1 lg:order-2">
                        <NationalAddressMapCanvas
                            :map-ready="mapReady"
                            height-class="h-[220px]"
                            :loading-text="$t('common.loading_map')"
                            :hint="$t('purchasing.suppliers.map.click_hint')"
                            @container="(el) => (mapContainer = el)"
                        />
                    </div>

                    <!-- Address Fields -->
                    <div class="order-2 lg:order-1 space-y-3">
                        <!-- Address Line -->
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                            >
                                {{ $t('purchasing.suppliers.address_line') }}
                            </label>
                            <AppTextarea
                                name="address"
                                v-model="form.address"
                                rows="2"
                                class="resize-none"
                            />
                        </div>

                        <!-- Building + Postal + District -->
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                >
                                    {{ $t('purchasing.suppliers.building_number') }}
                                </label>
                                <AppInput
                                    name="building_number"
                                    v-model="form.building_number"
                                    type="text"
                                    class="px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                >
                                    {{ $t('purchasing.suppliers.postal_code') }}
                                </label>
                                <AppInput
                                    name="postal_code"
                                    v-model="form.postal_code"
                                    type="text"
                                    class="px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                >
                                    {{ $t('purchasing.suppliers.district') }}
                                </label>
                                <AppInput
                                    name="district"
                                    v-model="form.district"
                                    type="text"
                                    class="px-3 py-2 text-sm"
                                />
                            </div>
                        </div>

                        <!-- City + Region + Country -->
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                >
                                    {{ $t('purchasing.suppliers.city') }}
                                </label>
                                <AppInput
                                    name="city"
                                    v-model="form.city"
                                    type="text"
                                    class="px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                >
                                    {{ $t('purchasing.suppliers.region') }}
                                </label>
                                <AppInput
                                    name="region"
                                    v-model="form.region"
                                    type="text"
                                    class="px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                                >
                                    {{ $t('purchasing.suppliers.country') }}
                                </label>
                                <AppInput
                                    name="country"
                                    v-model="form.country"
                                    type="text"
                                    class="px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Section (md:col-span-2) -->
            <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                    {{ $t('purchasing.suppliers.bank_info') }}
                </h3>
            </div>

            <!-- Bank Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.bank_name') }}
                </label>
                <AppInput name="bank_name" v-model="form.bank_name" type="text" />
            </div>

            <!-- IBAN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('purchasing.suppliers.iban') }}
                </label>
                <AppInput
                    name="iban"
                    v-model="form.iban"
                    type="text"
                    dir="ltr"
                    placeholder="SA..."
                />
            </div>

            <!-- Notes (md:col-span-2) -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('common.notes') }}
                </label>
                <AppTextarea name="notes" v-model="form.notes" rows="2" class="resize-none" />
            </div>

            <!-- Active Status (md:col-span-2) -->
            <div class="md:col-span-2">
                <div
                    class="flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30"
                >
                    <AppToggle
                        v-model="form.is_active"
                        :label="form.is_active ? $t('common.active') : $t('common.inactive')"
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div>
                    <span
                        v-if="Object.keys(form.errors).length > 0"
                        class="text-xs text-red-500 font-medium inline-flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{
                            $t('common.form_has_errors', { count: Object.keys(form.errors).length })
                        }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <AppButton variant="secondary" @click="handleClose">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        variant="action-xl"
                        @click="submit"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        {{ $t('common.save') }}
                    </AppButton>
                </div>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { ref, watch, nextTick, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';
import { useNationalAddressMap } from '@/Composables/useNationalAddressMap';
import NationalAddressMapCanvas from '@/Components/Common/NationalAddressMapCanvas.vue';

const props = defineProps({
    show: Boolean,
    supplier: Object,
    defaultType: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);
const { locale, t } = useI18n();
const { confirm } = useConfirm();
const { scrollToError } = useFormScrollToError();

const supplierTypeOptions = computed(() => [
    { id: 'parts', name: t('purchasing.suppliers.type_parts') },
    { id: 'services', name: t('purchasing.suppliers.type_services') },
]);

const isDirty = ref(false);
const initialFormData = ref(null);
const isConfirming = ref(false);

const defaultLat = 24.7136;
const defaultLng = 46.6753;

const form = useForm({
    id: null,
    name: '',
    type: 'parts',
    code: '',
    contact_person: '',
    phone: '',
    whatsapp: '',
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

const {
    mapContainer,
    mapReady,
    isLocating,
    mountWithRetry,
    fetchLocation,
    clearAddressFields,
    destroyMap,
} = useNationalAddressMap({
    form,
    fieldMap: { addressLine: 'address' },
    defaultLat,
    defaultLng,
    locale,
    onReady: () => {
        initialFormData.value = JSON.stringify(form.data());
        isDirty.value = false;
    },
});

watch(
    () => props.supplier,
    (supplier) => {
        if (supplier) {
            form.id = supplier.id;
            form.name = supplier.name;
            form.type = supplier.type || 'parts';
            form.code = supplier.code;
            form.contact_person = supplier.contact_person;
            form.phone = supplier.phone;
            form.whatsapp = supplier.whatsapp || '';
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
            form.type = props.defaultType || 'parts';
            form.is_active = true;
        }
    },
    { immediate: true }
);

// Auto-copy phone to whatsapp
watch(
    () => form.phone,
    (newPhone, oldPhone) => {
        const cleanOldPhone = normalizePhone(oldPhone);
        const cleanWhatsapp = normalizePhone(form.whatsapp);
        if (!form.whatsapp || cleanWhatsapp === cleanOldPhone) {
            form.whatsapp = newPhone || '';
        }
    }
);

watch(
    () => form.tax_number,
    (newVal) => {
        if (newVal) {
            const converted = convertArabicToEnglish(newVal).replace(/[^\d]/g, '').slice(0, 15);
            if (converted !== newVal) {
                form.tax_number = converted;
            }
        }
        if (form.errors.tax_number) form.clearErrors('tax_number');
    }
);

watch(
    () => props.show,
    async (open) => {
        if (open) {
            if (!props.supplier) {
                form.reset();
                form.type = 'parts';
                form.is_active = true;
                fetchLocation();
            }
            await nextTick();
            destroyMap();
            setTimeout(() => mountWithRetry(), 300);
        } else {
            destroyMap();
        }
    }
);

// Track form changes
watch(
    () => form.data(),
    (newData) => {
        if (initialFormData.value) {
            const currentData = JSON.stringify(newData);
            isDirty.value = currentData !== initialFormData.value;
        }
    },
    { deep: true }
);

import { onMounted } from 'vue';

onMounted(async () => {
    if (props.show) {
        await nextTick();
        setTimeout(() => mountWithRetry(), 300);
    }
});

function initMap() {
    if (!mapContainer.value || map) return;
    if (!value) return value;
    const arabic = '٠١٢٣٤٥٦٧٨٩';
    return value.replace(/[٠-٩]/g, (d) => arabic.indexOf(d));
}

// Quick actions
function normalizePhone(p) {
    return convertArabicToEnglish(p || '').replace(/[^\d+]/g, '');
}

// Handle phone input to convert numerals real-time and remove spaces
function onPhoneInput(type, value) {
    const converted = convertArabicToEnglish(value);
    const normalized = converted.replace(/\s/g, '');

    if (value !== normalized) {
        form[type] = normalized;
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
    if (isConfirming.value) return;

    if (isDirty.value) {
        isConfirming.value = true;
        try {
            const confirmed = await confirm({
                title: t('common.unsaved_changes'),
                message: t('common.unsaved_changes_message'),
                confirmText: t('common.yes_close'),
                cancelText: t('common.cancel'),
                type: 'warning',
            });
            if (!confirmed) return;
        } finally {
            isConfirming.value = false;
        }
    }
    close();
}

const submit = () => {
    if (form.id) {
        form.put(route('app.purchasing.suppliers.update', form.id), {
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (errors) => scrollToError(errors),
        });
    } else {
        form.post(route('app.purchasing.suppliers.store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                emit('saved', page.props.flash?.data || form.data());
                close();
            },
            onError: (errors) => scrollToError(errors),
        });
    }
};
</script>
