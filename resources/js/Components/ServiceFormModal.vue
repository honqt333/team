<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                {{ service ? $t('services_management.edit') : $t('services_management.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Department -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('services_management.form.department') }} <span class="text-red-500">*</span>
                </label>
                <SearchableSelect
                    v-model="form.department_id"
                    :options="departments"
                    :label="''"
                    :placeholder="$t('common.choose')"
                    option-label="name"
                    option-value="id"
                    :error="form.errors.department_id"
                />
                <p v-if="form.errors.department_id" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.department_id }}</p>
            </div>

            <!-- Names Row -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Name Arabic -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        v-model="form.name_ar"
                        dir="rtl"
                        :placeholder="$t('services_management.form.name_ar_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        :class="{ 'border-red-500': form.errors.name_ar }"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_ar }}</p>
                </div>

                <!-- Name English -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.name_en') }}
                    </label>
                    <input 
                        type="text" 
                        v-model="form.name_en"
                        dir="ltr"
                        :placeholder="$t('services_management.form.name_en_placeholder')"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                    />
                </div>
            </div>

            <!-- Descriptions Row -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Description Arabic -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.description_ar') }}
                    </label>
                    <textarea 
                        v-model="form.description_ar"
                        rows="2"
                        dir="rtl"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none transition-all"
                    ></textarea>
                </div>

                <!-- Description English -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.description_en') }}
                    </label>
                    <textarea 
                        v-model="form.description_en"
                        rows="2"
                        dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none transition-all"
                    ></textarea>
                </div>
            </div>

            <!-- Pricing Section -->
            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $t('pricing.base_price') }}
                </h3>

                <!-- Base Price & Min Price Row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Base Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('pricing.base_price') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                v-model.number="form.base_price"
                                step="0.01"
                                min="0"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                :class="{ 'border-red-500': form.errors.base_price }"
                            />
                            <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">ر.س</span>
                        </div>
                        <p v-if="form.errors.base_price" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.base_price }}</p>
                    </div>

                    <!-- Min Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('pricing.min_price') }}
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                v-model.number="form.min_price"
                                step="0.01"
                                min="0"
                                :max="form.base_price"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                                :class="{ 'border-red-500': form.errors.min_price }"
                            />
                            <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">ر.س</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('pricing.min_price_hint') }}</p>
                        <p v-if="form.errors.min_price" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.min_price }}</p>
                    </div>
                </div>

                <!-- Default Discount -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('pricing.discount_type') }}
                        </label>
                        <SearchableSelect
                            v-model="form.default_discount_type"
                            :options="[
                                {id: 'none', name: $t('pricing.discount_types.none')},
                                {id: 'percentage', name: $t('pricing.discount_types.percentage')},
                                {id: 'fixed', name: $t('pricing.discount_types.fixed')}
                            ]"
                            option-label="name"
                            option-value="id"
                            placeholder=""
                            :label="''"
                        />
                    </div>

                    <div v-if="form.default_discount_type !== 'none'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('pricing.discount_value') }}
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                v-model.number="form.default_discount_value"
                                step="0.01"
                                min="0"
                                :max="form.default_discount_type === 'percentage' ? 100 : form.base_price"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                            <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm">
                                {{ form.default_discount_type === 'percentage' ? '%' : 'ر.س' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Allow Price Override -->
                <div class="flex items-center gap-3 pt-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.allow_price_override" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 dark:peer-focus:ring-teal-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-teal-600"></div>
                    </label>
                    <div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('pricing.allow_price_override') }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('pricing.allow_price_override_hint') }}</p>
                    </div>
                </div>
            </div>

            <!-- Duration & Warranty Row -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Service Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.estimated_duration') }}
                    </label>
                    <div class="flex gap-2">
                        <input 
                            type="number" 
                            v-model.number="form.duration_value"
                            min="1"
                            placeholder="30"
                            class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        />
                        <div class="w-32">
                            <SearchableSelect
                                v-model="form.duration_unit"
                                :options="[
                                    {id: 'minutes', name: $t('services_management.duration_units.minutes')},
                                    {id: 'hours', name: $t('services_management.duration_units.hours')},
                                    {id: 'days', name: $t('services_management.duration_units.days')},
                                    {id: 'weeks', name: $t('services_management.duration_units.weeks')}
                                ]"
                                option-label="name"
                                option-value="id"
                                placeholder=""
                                :label="''"
                            />
                        </div>
                    </div>
                </div>

                <!-- Warranty -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('services_management.form.warranty') }}
                    </label>
                    <div class="flex gap-2">
                        <input 
                            type="number" 
                            v-model.number="form.warranty_value"
                            min="1"
                            placeholder="1"
                            class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                        />
                        <div class="w-32">
                            <SearchableSelect
                                v-model="form.warranty_unit"
                                :options="[
                                    {id: 'days', name: $t('services_management.warranty_units.days')},
                                    {id: 'weeks', name: $t('services_management.warranty_units.weeks')},
                                    {id: 'months', name: $t('services_management.warranty_units.months')},
                                    {id: 'years', name: $t('services_management.warranty_units.years')}
                                ]"
                                option-label="name"
                                option-value="id"
                                placeholder=""
                                :label="''"
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
                    <label
                        :class="[
                            'relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                            form.type === 'internal'
                                ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                        ]"
                    >
                        <input type="radio" v-model="form.type" value="internal" class="sr-only" />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $t('services_management.types.internal') }}</span>
                    </label>
                    <label
                        :class="[
                            'relative flex items-center gap-3 p-4 rounded-xl border-2 transition-all',
                            form.type === 'external'
                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                : 'border-gray-200 dark:border-gray-700 opacity-60 cursor-not-allowed'
                        ]"
                    >
                        <input type="radio" v-model="form.type" value="external" class="sr-only" disabled />
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
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
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 dark:peer-focus:ring-teal-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-teal-600"></div>
                </label>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('services_management.form.is_active') }}</span>
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
                class="px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    service: {
        type: Object,
        default: null,
    },
    departments: {
        type: Array,
        default: () => [],
    },
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

// Initialize form when service prop changes (for edit mode)
watch(() => props.service, (newService) => {
    if (newService) {
        form.department_id = newService.department_id || '';
        form.name_ar = newService.name_ar || '';
        form.name_en = newService.name_en || '';
        form.description_ar = newService.description_ar || '';
        form.description_en = newService.description_en || '';
        form.base_price = parseFloat(newService.base_price) || 0;
        form.min_price = parseFloat(newService.min_price) || 0;
        form.default_discount_type = newService.default_discount_type || 'none';
        form.default_discount_value = newService.default_discount_value ? parseFloat(newService.default_discount_value) : null;
        form.allow_price_override = newService.allow_price_override ?? false;
        form.duration_value = newService.duration_value || null;
        form.duration_unit = newService.duration_unit || 'minutes';
        form.warranty_value = newService.warranty_value || null;
        form.warranty_unit = newService.warranty_unit || 'months';
        form.type = newService.type || 'internal';
        form.is_active = newService.is_active ?? true;
    }
}, { immediate: true });

// Reset form when modal opens for create
watch(() => props.show, (open) => {
    if (open && !props.service) {
        form.reset();
        form.is_active = true;
        form.type = 'internal';
        form.duration_unit = 'minutes';
        form.warranty_unit = 'months';
    }
});

function submitForm() {
    const url = props.service 
        ? `/app/services/${props.service.id}` 
        : '/app/services';
    
    const method = props.service ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('saved');
            emit('close');
        },
    });
}
</script>
