<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                {{ $t('services_management.add_new_definition') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Names Row -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name_ar" dir="rtl"
                        :placeholder="$t('services_management.form.name_ar_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.name_ar }"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_ar }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_en') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" v-model="form.name_en" dir="ltr"
                        :placeholder="$t('services_management.form.name_en_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.name_en }"
                    />
                    <p v-if="form.errors.name_en" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_en }}</p>
                </div>
            </div>

            <!-- Descriptions Row -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.description_ar') }}
                    </label>
                    <textarea v-model="form.description_ar" rows="2" dir="rtl"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-all"
                    ></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.description_en') }}
                    </label>
                    <textarea v-model="form.description_en" rows="2" dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none transition-all"
                    ></textarea>
                </div>
            </div>

            <!-- Duration & Warranty Row -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.estimated_duration') }}
                    </label>
                    <div class="flex gap-2">
                        <input type="number" v-model.number="form.duration_value" inputmode="numeric" lang="en" min="1" placeholder="30"
                            class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        />
                        <div class="w-32">
                            <SearchableSelect v-model="form.duration_unit"
                                :options="[
                                    {id: 'minutes', name: $t('services_management.duration_units.minutes')},
                                    {id: 'hours', name: $t('services_management.duration_units.hours')},
                                    {id: 'days', name: $t('services_management.duration_units.days')},
                                    {id: 'weeks', name: $t('services_management.duration_units.weeks')}
                                ]"
                                option-label="name" option-value="id" placeholder="" :label="''"
                            />
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.warranty') }}
                    </label>
                    <div class="flex gap-2">
                        <input type="number" v-model.number="form.warranty_value" inputmode="numeric" lang="en" min="1" placeholder="1"
                            class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                        />
                        <div class="w-32">
                            <SearchableSelect v-model="form.warranty_unit"
                                :options="[
                                    {id: 'days', name: $t('services_management.warranty_units.days')},
                                    {id: 'weeks', name: $t('services_management.warranty_units.weeks')},
                                    {id: 'months', name: $t('services_management.warranty_units.months')},
                                    {id: 'years', name: $t('services_management.warranty_units.years')}
                                ]"
                                option-label="name" option-value="id" placeholder="" :label="''"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    {{ $t('services_management.form.type') }}
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label :class="['relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all', form.type === 'internal' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300']">
                        <input type="radio" v-model="form.type" value="internal" class="sr-only" />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $t('services_management.types.internal') }}</span>
                    </label>
                    <label :class="['relative flex items-center gap-3 p-4 rounded-xl border-2 transition-all', form.type === 'external' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-700 opacity-60 cursor-not-allowed']">
                        <input type="radio" v-model="form.type" value="external" class="sr-only" disabled />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </div>
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $t('services_management.types.external') }}</span>
                            <span class="block text-xs text-purple-600 dark:text-purple-400">{{ $t('services_management.coming_soon') }}</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                </label>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('services_management.form.is_active') }}</span>
            </div>

            <!-- Allow Price Override Toggle -->
            <div class="flex items-start gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                    <input type="checkbox" v-model="form.allow_price_override" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                </label>
                <div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('pricing.allow_price_override') }}</span>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $t('pricing.allow_price_override_hint') }}</p>
                </div>
            </div>

            <!-- Notice: Price will be set separately -->
            <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200/50 dark:border-amber-700/30">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    {{ $t('services_management.definition_price_notice') }}
                </p>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button @click="submitForm" :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    // The department_id is passed silently; not shown in the form UI
    departmentId: { type: [String, Number], default: null },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    department_id: '',
    name_ar: '',
    name_en: '',
    description_ar: '',
    description_en: '',
    base_price: 0,
    min_price: 0,
    default_discount_type: 'none',
    default_discount_value: null,
    allow_price_override: false,
    duration_value: null,
    duration_unit: 'minutes',
    warranty_value: null,
    warranty_unit: 'months',
    type: 'internal',
    is_active: true,
});

watch([() => props.show, () => props.departmentId], ([open]) => {
    if (open) {
        form.reset();
        form.department_id = props.departmentId || '';
        form.is_active = true;
        form.type = 'internal';
        form.duration_unit = 'minutes';
        form.warranty_unit = 'months';
        form.allow_price_override = false;
        form.base_price = 0;
        form.min_price = 0;
        form.default_discount_type = 'none';
        form.default_discount_value = null;
    }
});

function submitForm() {
    form.post('/app/services', {
        preserveScroll: true,
        onSuccess: () => {
            // Read the newly created service ID from Inertia flash data
            const newServiceId = usePage().props.flash?.new_service_id ?? null;
            form.reset();
            emit('saved', newServiceId);
        },
    });
}
</script>
