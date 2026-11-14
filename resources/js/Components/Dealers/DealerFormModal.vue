<template>
    <BaseModal :show="show" @close="handleClose" size="2xl" scroll-entire>
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center"
                >
                    <svg
                        class="w-5 h-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                        />
                    </svg>
                </div>
                {{ dealer ? $t('dealers.edit') : $t('dealers.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Name -->
                <FormField
                    v-slot="field"
                    :label="$t('customers.form.name')"
                    :error="form.errors.name"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        name="name"
                        v-model="form.name"
                        class="w-full"
                        required
                    />
                </FormField>

                <!-- Contact Person -->
                <FormField
                    v-slot="field"
                    :label="$t('customers.form.contact_name')"
                    :error="form.errors.contact_name"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        name="contact_name"
                        v-model="form.contact_name"
                        class="w-full"
                    />
                </FormField>

                <!-- Tax Number, Credit Limit & Payment Terms -->
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <FormField
                        v-slot="field"
                        :label="$t('customers.form.tax_number')"
                        :error="form.errors.tax_number"
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            name="tax_number"
                            v-model="form.tax_number"
                            :placeholder="$t('customers.form.tax_placeholder')"
                            class="w-full"
                        />
                    </FormField>

                    <FormField
                        v-slot="field"
                        :label="$t('customers.form.credit_limit')"
                        :error="form.errors.credit_limit"
                    >
                        <AppInput
                            :id="field.id"
                            :aria-describedby="field.describedBy"
                            :invalid="field.hasError"
                            type="number"
                            name="credit_limit"
                            step="0.01"
                            min="0"
                            v-model="form.credit_limit"
                            :placeholder="$t('customers.form.credit_limit_placeholder')"
                            class="w-full"
                        />
                    </FormField>

                    <FormField
                        v-slot="field"
                        :label="$t('customers.form.payment_terms')"
                        :error="form.errors.payment_terms_days"
                    >
                        <div class="relative">
                            <AppInput
                                :id="field.id"
                                :aria-describedby="field.describedBy"
                                :invalid="field.hasError"
                                type="number"
                                name="payment_terms_days"
                                min="0"
                                max="3650"
                                v-model="form.payment_terms_days"
                                placeholder="0"
                                class="w-full pe-12"
                            />
                            <span
                                class="absolute inset-y-0 end-3 flex items-center text-xs text-text-muted pointer-events-none"
                            >
                                {{ $t('common.days') }}
                            </span>
                        </div>
                    </FormField>
                </div>

                <!-- Phone -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                    >
                        {{ $t('customers.form.phone') }}
                    </label>
                    <div data-field="phone">
                        <vue-tel-input
                            v-model="form.phone"
                            @on-input="(value) => onPhoneInput('phone', value)"
                            :inputOptions="{ placeholder: '05xxxxxxxx' }"
                            mode="international"
                            :validCharactersOnly="false"
                            class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:border-transparent transition-all ltr"
                            :class="
                                form.errors.phone
                                    ? 'error-border'
                                    : 'border-gray-300 dark:border-gray-600'
                            "
                        ></vue-tel-input>
                    </div>
                    <p
                        v-if="form.errors.phone"
                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                    >
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
                            class="w-full text-sm rounded-xl bg-white dark:bg-gray-900 focus-within:ring-2 focus-within:border-transparent transition-all ltr"
                            :class="
                                form.errors.whatsapp
                                    ? 'error-border'
                                    : 'border-gray-300 dark:border-gray-600'
                            "
                        ></vue-tel-input>
                    </div>
                    <p
                        v-if="form.errors.whatsapp"
                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                    >
                        {{ form.errors.whatsapp }}
                    </p>
                </div>

                <!-- Email -->
                <FormField
                    v-slot="field"
                    class="md:col-span-2"
                    :label="$t('customers.form.email')"
                    :error="form.errors.email"
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        type="email"
                        name="email"
                        v-model="form.email"
                        class="w-full"
                    />
                </FormField>

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
                <FormField
                    class="md:col-span-2"
                    :label="$t('customers.form.notes')"
                    :error="form.errors.notes"
                >
                    <AppTextarea name="notes" v-model="form.notes" rows="2" class="resize-none" />
                </FormField>
            </div>

            <!-- Address Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
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
                        {{ $t('customers.form.address') }}
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
                            :title="$t('customers.form.clear_address')"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                            {{ $t('customers.form.clear_address') }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Map Container -->
                    <div class="order-1 lg:order-2">
                        <NationalAddressMapCanvas
                            :map-ready="mapReady"
                            height-class="h-[260px]"
                            :hint="$t('customers.map.click_hint')"
                            @container="(el) => (mapContainer = el)"
                        />
                    </div>

                    <!-- Address Fields -->
                    <div class="order-2 lg:order-1 space-y-3">
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"
                            >
                                {{ $t('customers.form.address_line') }}
                            </label>
                            <AppTextarea
                                name="address_line"
                                v-model="form.address_line"
                                rows="2"
                                :placeholder="$t('customers.form.address_placeholder')"
                                class="resize-none"
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.building_number') }}
                                </label>
                                <AppInput
                                    type="text"
                                    name="building_number"
                                    v-model="form.building_number"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.postal_code') }}
                                </label>
                                <AppInput
                                    type="text"
                                    name="postal_code"
                                    v-model="form.postal_code"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.district') }}
                                </label>
                                <AppInput type="text" name="district" v-model="form.district" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.city') }}
                                </label>
                                <AppInput type="text" name="city" v-model="form.city" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    {{ $t('customers.form.region') }}
                                </label>
                                <AppInput type="text" name="region" v-model="form.region" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                                {{ $t('customers.form.country') }}
                            </label>
                            <AppInput
                                type="text"
                                name="country"
                                v-model="form.country"
                                :placeholder="$t('customers.form.country_placeholder')"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>

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
                    <AppButton type="button" variant="secondary" @click="handleClose">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="primary"
                        :loading="form.processing"
                        @click="submitForm"
                    >
                        {{ form.processing ? $t('common.loading') : $t('customers.form.submit') }}
                    </AppButton>
                </div>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onUnmounted, nextTick } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';
import { useNationalAddressMap } from '@/Composables/useNationalAddressMap';
import NationalAddressMapCanvas from '@/Components/Common/NationalAddressMapCanvas.vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    dealer: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);
const { t, locale } = useI18n();
const { confirm } = useConfirm();
const { scrollToError } = useFormScrollToError();
const page = usePage();

const defaultLat = computed(
    () => page.props.auth?.center?.lat || page.props.tenant?.lat || 24.7136
);
const defaultLng = computed(
    () => page.props.auth?.center?.lng || page.props.tenant?.lng || 46.6753
);

const isDirty = ref(false);
const initialFormData = ref(null);
const isConfirming = ref(false);

const form = useForm({
    type: 'corporate',
    name: '',
    contact_name: '',
    phone: '',
    whatsapp: '',
    email: '',
    notes: '',
    tax_number: '',
    credit_limit: null,
    payment_terms_days: 0,
    address_line: '',
    building_number: '',
    postal_code: '',
    district: '',
    city: '',
    region: '',
    country: '',
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
    defaultLat,
    defaultLng,
    locale,
    onReady: () => {
        initialFormData.value = JSON.stringify(form.data());
        isDirty.value = false;
    },
});

watch(
    () => props.dealer,
    (d) => {
        if (!d) return;
        form.type = d.type || 'corporate';
        form.name = d.name || '';
        form.contact_name = d.contact_name || '';
        form.phone = d.phone || '';
        form.whatsapp = d.whatsapp || '';
        form.email = d.email || '';
        form.notes = d.notes || '';
        form.tax_number = d.tax_number ?? '';
        form.credit_limit =
            d.credit_limit !== undefined && d.credit_limit !== null ? d.credit_limit : null;
        form.payment_terms_days =
            d.payment_terms_days !== undefined && d.payment_terms_days !== null
                ? d.payment_terms_days
                : 0;
        form.address_line = d.address_line || '';
        form.building_number = d.building_number || '';
        form.postal_code = d.postal_code || '';
        form.district = d.district || '';
        form.city = d.city || '';
        form.region = d.region || '';
        form.country = d.country || '';
        form.is_active = d.is_active !== undefined ? Boolean(d.is_active) : true;
    },
    { immediate: true }
);

watch(
    () => props.show,
    async (open) => {
        if (open) {
            form.clearErrors();
            if (!props.dealer) {
                form.reset();
                form.type = 'corporate';
                form.payment_terms_days = 0;
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

watch(
    () => form.data(),
    (newData) => {
        if (initialFormData.value && !isLocating.value) {
            isDirty.value = JSON.stringify(newData) !== initialFormData.value;
        }
    },
    { deep: true }
);

function convertArabicToEnglish(value) {
    if (!value) return value;
    const arabic = '٠١٢٣٤٥٦٧٨٩';
    return value.replace(/[٠-٩]/g, (d) => arabic.indexOf(d));
}

function normalizePhone(p) {
    return convertArabicToEnglish(p || '').replace(/[^\d+]/g, '');
}

function onPhoneInput(type, value) {
    const normalized = convertArabicToEnglish(value).replace(/\s/g, '');
    if (value !== normalized) {
        form[type] = normalized;
    }
}

watch(
    () => form.phone,
    (newPhone, oldPhone) => {
        const cleanWhatsapp = normalizePhone(form.whatsapp);
        const cleanOldPhone = normalizePhone(oldPhone);
        if (!form.whatsapp || cleanWhatsapp === cleanOldPhone) {
            form.whatsapp = newPhone || '';
        }
        if (form.errors.phone) form.clearErrors('phone');
    }
);

watch(
    () => form.tax_number,
    (newVal) => {
        if (newVal) {
            const converted = convertArabicToEnglish(newVal).replace(/[^\d]/g, '').slice(0, 15);
            if (converted !== newVal) form.tax_number = converted;
        }
        if (form.errors.tax_number) form.clearErrors('tax_number');
    }
);

function quickCall() {
    if (form.phone) window.open(`tel:${normalizePhone(form.phone)}`, '_self');
}

function quickWhatsApp() {
    if (form.whatsapp) {
        window.open(`https://wa.me/${normalizePhone(form.whatsapp).replace(/^\+/, '')}`, '_blank');
    }
}

function quickEmail() {
    if (form.email) window.open(`mailto:${form.email}`, '_self');
}

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

    form.reset();
    isDirty.value = false;
    initialFormData.value = null;
    emit('close');
}

function submitForm() {
    if (
        form.tax_number &&
        form.tax_number.trim() !== '' &&
        !/^\d{15}$/.test(form.tax_number.trim())
    ) {
        form.setError('tax_number', t('validation.tax_number_invalid'));
        scrollToError({ tax_number: t('validation.tax_number_invalid') });
        return;
    }

    const url = props.dealer
        ? route('app.dealers.update', props.dealer.id)
        : route('app.dealers.store');

    const method = props.dealer ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            isDirty.value = false;
            initialFormData.value = null;
            form.reset();
            emit('saved');
            emit('close');
        },
        onError: (errors) => {
            scrollToError(errors);
        },
    });
}

onUnmounted(() => destroyMap());
</script>

<style>
.vue-tel-input {
    border-radius: 0.75rem !important;
    border-color: #d1d5db !important;
}
.vue-tel-input.error-border {
    border-color: #ef4444 !important;
}
.vue-tel-input.error-border:focus-within {
    box-shadow: 0 0 0 2px #ef4444 !important;
    border-color: transparent !important;
}
.dark .vue-tel-input {
    border-color: #4b5563 !important;
    background-color: #111827 !important;
}
.vue-tel-input:focus-within {
    box-shadow: 0 0 0 2px var(--color-focus-ring) !important;
    border-color: transparent !important;
}
.vti__input {
    background-color: transparent !important;
    border-radius: 0 0.75rem 0.75rem 0 !important;
}
.dark .vti__input {
    color: #f3f4f6 !important;
}
.vti__dropdown {
    border-radius: 0.75rem 0 0 0.75rem !important;
}
.dark .vti__dropdown-list {
    background-color: #1f2937 !important;
    border-color: #374151 !important;
}
.dark .vti__dropdown-item.highlighted {
    background-color: #374151 !important;
}
.dark .vti__dropdown-item strong,
.dark .vti__dropdown-item span {
    color: #f3f4f6 !important;
}
</style>
