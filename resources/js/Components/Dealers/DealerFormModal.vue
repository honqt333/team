<template>
    <BaseModal :show="show" @close="handleClose" size="xl" scroll-entire>
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shadow-md shadow-indigo-500/20"
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
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </div>
                <span>{{ dealer ? $t('dealers.edit') : $t('dealers.add') }}</span>
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Basic Info -->
            <div
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700 space-y-4"
            >
                <h4
                    class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                >
                    {{ $t('dealers.sections.basic_info') }}
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.name') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.name_en') }}
                        </label>
                        <input
                            v-model="form.name_en"
                            type="text"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                        <p
                            v-if="form.errors.name_en"
                            class="mt-1 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.name_en }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.type') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="form.type"
                            :options="typeOptions"
                            option-label="label"
                            option-value="id"
                            :placeholder="$t('common.choose')"
                            :error="form.errors.type"
                            teleport
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.tax_number') }}
                        </label>
                        <input
                            v-model="form.tax_number"
                            type="text"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm font-mono rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                        <p
                            v-if="form.errors.tax_number"
                            class="mt-1 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.tax_number }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contact & Billing -->
            <div
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700 space-y-4"
            >
                <h4
                    class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                >
                    {{ $t('dealers.sections.contact_info') }}
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.contact_name') }}
                        </label>
                        <input
                            v-model="form.contact_name"
                            type="text"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.phone') }}
                        </label>
                        <input
                            v-model="form.phone"
                            type="text"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm font-mono rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.whatsapp') }}
                        </label>
                        <input
                            v-model="form.whatsapp"
                            type="text"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm font-mono rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.email') }}
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.credit_limit') }}
                        </label>
                        <input
                            v-model="form.credit_limit"
                            type="number"
                            min="0"
                            step="0.01"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm font-mono rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.payment_terms_days') }}
                        </label>
                        <input
                            v-model="form.payment_terms_days"
                            type="number"
                            min="0"
                            max="3650"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm font-mono rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                </div>
            </div>

            <!-- National Address -->
            <div
                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700 space-y-4"
            >
                <h4
                    class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                >
                    {{ $t('dealers.sections.address_info') }}
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.city') }}
                        </label>
                        <input
                            v-model="form.city"
                            type="text"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.district') }}
                        </label>
                        <input
                            v-model="form.district"
                            type="text"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.building_number') }}
                        </label>
                        <input
                            v-model="form.building_number"
                            type="text"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm font-mono rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.address_line') }}
                        </label>
                        <input
                            v-model="form.address_line"
                            type="text"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('dealers.fields.postal_code') }}
                        </label>
                        <input
                            v-model="form.postal_code"
                            type="text"
                            dir="ltr"
                            class="w-full px-3.5 py-2.5 text-sm font-mono rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                    >
                        {{ $t('dealers.fields.notes') }}
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                    ></textarea>
                </div>

                <div class="pt-2">
                    <AppToggle
                        v-model="form.is_active"
                        :label="$t('dealers.fields.is_active')"
                        :description="$t('dealers.fields.is_active_hint')"
                        color="success"
                    />
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700"
            >
                <button
                    type="button"
                    @click="handleClose"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-action-primary hover:bg-action-primary-hover shadow-lg shadow-action-primary/25 transition-all disabled:opacity-50 cursor-pointer"
                >
                    {{ $t('common.save') }}
                </button>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';

const { t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    dealer: {
        type: Object,
        default: null,
    },
    types: {
        type: Array,
        default: () => ['insurance', 'fleet', 'rental', 'corporate'],
    },
});

const emit = defineEmits(['close', 'saved']);

const typeOptions = computed(() =>
    props.types.map((typeKey) => ({
        id: typeKey,
        label: t(`dealers.types.${typeKey}`),
        name: t(`dealers.types.${typeKey}`),
    }))
);

const form = useForm({
    name: '',
    name_en: '',
    type: 'corporate',
    contact_name: '',
    phone: '',
    whatsapp: '',
    email: '',
    tax_number: '',
    credit_limit: '',
    payment_terms_days: 30,
    address_line: '',
    building_number: '',
    postal_code: '',
    district: '',
    city: '',
    region: '',
    country: 'Saudi Arabia',
    notes: '',
    is_active: true,
});

watch(
    () => [props.show, props.dealer],
    ([isOpen, d]) => {
        if (!isOpen) return;
        form.clearErrors();
        if (d) {
            form.name = d.name || '';
            form.name_en = d.name_en || '';
            form.type = d.type || 'corporate';
            form.contact_name = d.contact_name || '';
            form.phone = d.phone || '';
            form.whatsapp = d.whatsapp || '';
            form.email = d.email || '';
            form.tax_number = d.tax_number ?? '';
            form.credit_limit = d.credit_limit ?? '';
            form.payment_terms_days = d.payment_terms_days ?? 30;
            form.address_line = d.address_line || '';
            form.building_number = d.building_number || '';
            form.postal_code = d.postal_code || '';
            form.district = d.district || '';
            form.city = d.city || '';
            form.region = d.region || '';
            form.country = d.country || 'Saudi Arabia';
            form.notes = d.notes || '';
            form.is_active = d.is_active !== undefined ? Boolean(d.is_active) : true;
        } else {
            form.reset();
            form.type = 'corporate';
            form.payment_terms_days = 30;
            form.country = 'Saudi Arabia';
            form.is_active = true;
        }
    },
    { immediate: true }
);

const handleClose = () => {
    emit('close');
};

const submitForm = () => {
    if (props.dealer) {
        form.put(route('app.dealers.update', props.dealer.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
            },
        });
    } else {
        form.post(route('app.dealers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
            },
        });
    }
};
</script>
