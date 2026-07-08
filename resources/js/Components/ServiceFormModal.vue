<template>
    <!--
        overflow-visible: prevents the modal body from clipping the SearchableSelect dropdown.
        When creating (catalog mode), the dropdown must float freely above other content.
    -->
    <BaseModal :show="show" @close="$emit('close')" size="lg" :overflow-visible="!service?.id && !selectedCatalogService">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                {{ service?.id ? $t('services_management.edit') : $t('services_management.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">

            <!-- ═══════════════════════════════════════════════════════
                 CREATE MODE — Catalog picker (shown when no service id)
                 ═══════════════════════════════════════════════════════ -->
            <template v-if="!service?.id">

                <!-- Department badge -->
                <div v-if="currentDepartment"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/30 text-sm text-indigo-700 dark:text-indigo-300 font-semibold w-fit"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    {{ currentDepartment.name_ar }}
                </div>

                <!-- Catalog selector row -->
                <div class="flex items-start gap-3">
                    <!-- SearchableSelect takes all remaining space -->
                    <div class="flex-1 min-w-0">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('services_management.select_from_other_branches') }}
                        </label>
                        <SearchableSelect
                            v-model="selectedCatalogServiceId"
                            :options="filteredCatalogServices"
                            :label="''"
                            :placeholder="$t('services_management.select_service_placeholder')"
                            :option-label="s => `${s.name_ar} — ${s.name_en}`"
                            option-value="id"
                        />
                        <p v-if="filteredCatalogServices.length === 0"
                            class="mt-1.5 text-xs text-amber-600 dark:text-amber-400"
                        >
                            {{ $t('services_management.no_services_in_dept') }}
                        </p>
                    </div>

                    <!-- Add new definition button -->
                    <div class="flex-shrink-0 pt-7">
                        <button
                            type="button"
                            @click="$emit('open-new-definition')"
                            class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400 rounded-xl font-bold text-sm shadow-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:shadow-md transition-all flex items-center gap-1.5 whitespace-nowrap"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $t('common.add') }}
                        </button>
                    </div>
                </div>

                <!-- Selected service preview card -->
                <div v-if="selectedCatalogService"
                    class="p-4 rounded-xl bg-teal-50/60 dark:bg-teal-900/10 border border-teal-200/60 dark:border-teal-800/30 space-y-2"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white text-sm">
                                {{ selectedCatalogService.name_ar }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ selectedCatalogService.name_en }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 text-xs font-semibold capitalize flex-shrink-0">
                            {{ selectedCatalogService.type }}
                        </span>
                    </div>
                    <p v-if="selectedCatalogService.description_ar"
                        class="text-xs text-gray-500 dark:text-gray-400 border-t border-teal-200/50 dark:border-teal-800/30 pt-2"
                    >
                        {{ selectedCatalogService.description_ar }}
                    </p>
                    <div class="flex flex-wrap gap-3 text-xs text-gray-400 dark:text-gray-500 font-medium">
                        <span v-if="selectedCatalogService.duration_value">
                            ⏱ {{ selectedCatalogService.duration_value }} {{ $t(`services_management.duration_units.${selectedCatalogService.duration_unit}`) }}
                        </span>
                        <span v-if="selectedCatalogService.warranty_value">
                            🛡 {{ selectedCatalogService.warranty_value }} {{ $t(`services_management.warranty_units.${selectedCatalogService.warranty_unit}`) }}
                        </span>
                    </div>
                </div>

            </template>

            <!-- ═══════════════════════════════════════════════════════
                 EDIT MODE — Full definition fields
                 ═══════════════════════════════════════════════════════ -->
            <template v-if="service?.id">
                <!-- Names -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('services_management.form.name_ar') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.name_ar" dir="rtl"
                            :placeholder="$t('services_management.form.name_ar_placeholder')"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.name_ar }"
                        />
                        <p v-if="form.errors.name_ar" class="mt-1 text-xs text-red-600">{{ form.errors.name_ar }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('services_management.form.name_en') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.name_en" dir="ltr"
                            :placeholder="$t('services_management.form.name_en_placeholder')"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.name_en }"
                        />
                        <p v-if="form.errors.name_en" class="mt-1 text-xs text-red-600">{{ form.errors.name_en }}</p>
                    </div>
                </div>

                <!-- Descriptions -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ $t('services_management.form.description_ar') }}</label>
                        <textarea v-model="form.description_ar" rows="2" dir="rtl"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none transition-all"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ $t('services_management.form.description_en') }}</label>
                        <textarea v-model="form.description_en" rows="2" dir="ltr"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent resize-none transition-all"
                        ></textarea>
                    </div>
                </div>

                <!-- Duration & Warranty -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ $t('services_management.form.estimated_duration') }}</label>
                        <div class="flex gap-2">
                            <input type="number" v-model.number="form.duration_value" inputmode="numeric" lang="en" min="1"
                                class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                            <div class="w-32">
                                <SearchableSelect v-model="form.duration_unit" label="" placeholder=""
                                    :options="[
                                        {id:'minutes', name: $t('services_management.duration_units.minutes')},
                                        {id:'hours',   name: $t('services_management.duration_units.hours')},
                                        {id:'days',    name: $t('services_management.duration_units.days')},
                                        {id:'weeks',   name: $t('services_management.duration_units.weeks')},
                                    ]" option-label="name" option-value="id"
                                />
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ $t('services_management.form.warranty') }}</label>
                        <div class="flex gap-2">
                            <input type="number" v-model.number="form.warranty_value" inputmode="numeric" lang="en" min="1"
                                class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                            <div class="w-32">
                                <SearchableSelect v-model="form.warranty_unit" label="" placeholder=""
                                    :options="[
                                        {id:'days',   name: $t('services_management.warranty_units.days')},
                                        {id:'weeks',  name: $t('services_management.warranty_units.weeks')},
                                        {id:'months', name: $t('services_management.warranty_units.months')},
                                        {id:'years',  name: $t('services_management.warranty_units.years')},
                                    ]" option-label="name" option-value="id"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $t('services_management.form.type') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label :class="['flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all', form.type === 'internal' ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300']">
                            <input type="radio" v-model="form.type" value="internal" class="sr-only"/>
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-emerald-500 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="font-medium text-sm text-gray-900 dark:text-white">{{ $t('services_management.types.internal') }}</span>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 opacity-50 cursor-not-allowed">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </div>
                            <div>
                                <span class="font-medium text-sm text-gray-900 dark:text-white">{{ $t('services_management.types.external') }}</span>
                                <span class="block text-xs text-purple-500">{{ $t('services_management.coming_soon') }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Active toggle -->
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 dark:peer-focus:ring-teal-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-teal-600"></div>
                    </label>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('services_management.form.is_active') }}</span>
                </div>
            </template>

            <!-- ═══════════════════════════════════════════════════════
                 PRICING — visible once a catalog service is selected OR in edit mode
                 ═══════════════════════════════════════════════════════ -->
            <div v-if="selectedCatalogService || service?.id"
                class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200/60 dark:border-gray-700/50 space-y-4"
            >
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $t('pricing.base_price') }}
                </h3>

                <!-- Base Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('pricing.base_price') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" v-model.number="form.base_price" inputmode="decimal" lang="en" step="0.01" min="0"
                            class="w-full px-4 py-2.5 pe-14 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.base_price }"
                        />
                        <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">ر.س</span>
                    </div>
                    <p v-if="form.errors.base_price" class="mt-1 text-xs text-red-600">{{ form.errors.base_price }}</p>
                </div>

                <!-- Discount -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('pricing.discount_type') }}</label>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="form.default_discount_type = 'none'"
                                :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all',
                                    form.default_discount_type === 'none'
                                        ? 'bg-teal-600 border-teal-600 text-white shadow-sm'
                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                {{ $t('pricing.discount_types.none') }}
                            </button>
                            <button type="button" @click="form.default_discount_type = 'fixed'"
                                :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all',
                                    form.default_discount_type === 'fixed'
                                        ? 'bg-teal-600 border-teal-600 text-white shadow-sm'
                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                {{ $t('pricing.discount_types.fixed') }}
                            </button>
                            <button type="button" @click="form.default_discount_type = 'percentage'"
                                :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all',
                                    form.default_discount_type === 'percentage'
                                        ? 'bg-teal-600 border-teal-600 text-white shadow-sm'
                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                %
                            </button>
                        </div>
                    </div>
                    <div v-if="form.default_discount_type !== 'none'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ $t('pricing.discount_value') }}</label>
                        <div class="relative">
                            <input type="number" v-model.number="form.default_discount_value" inputmode="decimal" lang="en" step="0.01" min="0"
                                class="w-full px-4 py-2.5 pe-10 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                            <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">{{ form.default_discount_type === 'percentage' ? '%' : 'ر.س' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Min Price -->
                <div class="space-y-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('pricing.min_price') }}</label>
                    <div class="relative">
                        <input type="number" v-model.number="form.min_price" inputmode="decimal" lang="en" step="0.01" min="0"
                            :max="form.base_price"
                            class="w-full px-4 py-2.5 pe-12 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.min_price || minPriceValidationError }"
                        />
                        <span class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">ر.س</span>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $t('pricing.min_price_hint') }}</p>
                    <p v-if="minPriceValidationError" class="text-xs text-red-600 dark:text-red-400">{{ minPriceValidationError }}</p>
                </div>

            </div>

            <!-- Validation Error Summary -->
            <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30 rounded-xl text-sm text-red-600 dark:text-red-400 space-y-1">
                <p class="font-bold">يرجى تصحيح الأخطاء التالية:</p>
                <ul class="list-disc list-inside text-xs">
                    <li v-for="(err, field) in form.errors" :key="field">
                        {{ field }}: {{ err }}
                    </li>
                </ul>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                @click="submitForm"
                :disabled="form.processing || (!selectedCatalogService && !service?.id) || !!minPriceValidationError"
                class="px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-semibold shadow-lg shadow-teal-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show:                    { type: Boolean, default: false },
    service:                 { type: Object,  default: null },
    departments:             { type: Array,   default: () => [] },
    currentDepartment:       { type: Object,  default: null },
    otherBranchesServices:   { type: Array,   default: () => [] },
    // ID of a service just created via NewServiceDefinitionModal — auto-selects it
    pendingCatalogServiceId: { type: [Number, String], default: null },
});

const emit = defineEmits(['close', 'saved', 'open-new-definition']);
const { t } = useI18n();

// ─── Catalog filtering ────────────────────────────────────────────────────────
const selectedCatalogServiceId = ref('');

/**
 * Show only services whose department name matches the currently open department.
 * Matching is done by name (Arabic or English) because department IDs differ per branch.
 */
const filteredCatalogServices = computed(() => {
    if (!props.currentDepartment) return props.otherBranchesServices;
    const arName = props.currentDepartment.name_ar?.trim().toLowerCase();
    const enName = props.currentDepartment.name_en?.trim().toLowerCase();
    return props.otherBranchesServices.filter(s => {
        const sar = s.department_name_ar?.trim().toLowerCase();
        const sen = s.department_name_en?.trim().toLowerCase();
        return (arName && sar === arName) || (enName && sen === enName);
    });
});

const selectedCatalogService = computed(() =>
    filteredCatalogServices.value.find(s => s.id === selectedCatalogServiceId.value) ?? null
);

// Auto-select a newly-created service when pendingCatalogServiceId arrives
// (triggered after NewServiceDefinitionModal saves + page reloads)
watch(
    () => [props.pendingCatalogServiceId, props.otherBranchesServices],
    ([pendingId]) => {
        if (pendingId && !props.service?.id) {
            // Find the service in the full list (not filtered, in case dept name doesn't match yet)
            const found = props.otherBranchesServices.find(s => s.id === pendingId);
            if (found) {
                selectedCatalogServiceId.value = pendingId;
            }
        }
    },
    { deep: false }
);

// Copy catalog definition into form fields when selected
watch(selectedCatalogServiceId, id => {
    const svc = filteredCatalogServices.value.find(s => s.id === id);
    if (svc) {
        form.name_ar        = svc.name_ar;
        form.name_en        = svc.name_en;
        form.description_ar = svc.description_ar || '';
        form.description_en = svc.description_en || '';
        form.duration_value = svc.duration_value;
        form.duration_unit  = svc.duration_unit  || 'minutes';
        form.warranty_value = svc.warranty_value;
        form.warranty_unit  = svc.warranty_unit  || 'months';
        form.type           = svc.type           || 'internal';
    } else {
        form.name_ar = ''; form.name_en = '';
        form.description_ar = ''; form.description_en = '';
        form.duration_value = null; form.duration_unit = 'minutes';
        form.warranty_value = null; form.warranty_unit = 'months';
        form.type = 'internal';
    }
});

// ─── Min Price ────────────────────────────────────────────────────────────────
const form = useForm({
    department_id: '',
    name_ar: '', name_en: '',
    description_ar: '', description_en: '',
    base_price: 0, min_price: 0,
    default_discount_type: 'none', default_discount_value: null,
    allow_price_override: false,
    duration_value: null, duration_unit: 'minutes',
    warranty_value: null, warranty_unit: 'months',
    type: 'internal', is_active: true,
});

const minPriceValidationError = computed(() => {
    if (form.min_price > 0 && form.base_price > 0 && form.min_price > form.base_price)
        return t('pricing.errors.min_price_exceeds_base', { price: form.base_price, currency: t('common.currency_sar') });
    return null;
});

const isInitializing = ref(false);

// Automatically calculate min_price based on base_price and discount
watch(
    () => [form.base_price, form.default_discount_type, form.default_discount_value],
    ([basePrice, discountType, discountValue]) => {
        if (isInitializing.value) return;

        const bp = parseFloat(basePrice) || 0;
        const dv = parseFloat(discountValue) || 0;

        if (discountType === 'none') {
            form.min_price = bp;
        } else if (discountType === 'fixed') {
            form.min_price = Math.max(0, bp - dv);
        } else if (discountType === 'percentage') {
            form.min_price = Math.max(0, bp - (bp * dv / 100));
        }
    }
);

watch(() => props.service, svc => {
    if (!svc) return;
    isInitializing.value = true;
    Object.assign(form, {
        department_id:         svc.department_id          || '',
        name_ar:               svc.name_ar                || '',
        name_en:               svc.name_en                || '',
        description_ar:        svc.description_ar         || '',
        description_en:        svc.description_en         || '',
        base_price:            parseFloat(svc.base_price) || 0,
        min_price:             parseFloat(svc.min_price)  || 0,
        default_discount_type: svc.default_discount_type  || 'none',
        default_discount_value:svc.default_discount_value ? parseFloat(svc.default_discount_value) : null,
        allow_price_override:  Boolean(svc.allow_price_override),
        duration_value:        svc.duration_value || null,
        duration_unit:         svc.duration_unit  || 'minutes',
        warranty_value:        svc.warranty_value || null,
        warranty_unit:         svc.warranty_unit  || 'months',
        type:                  svc.type           || 'internal',
        is_active:             svc.is_active      ?? true,
    });
    nextTick(() => {
        isInitializing.value = false;
    });
}, { immediate: true });

watch(() => props.show, open => {
    if (open && !props.service?.id) {
        isInitializing.value = true;
        form.reset();
        form.department_id    = props.service?.department_id ?? '';
        form.is_active        = true;
        form.type             = 'internal';
        form.duration_unit    = 'minutes';
        form.warranty_unit    = 'months';
        form.allow_price_override = false;
        selectedCatalogServiceId.value = '';
        nextTick(() => {
            isInitializing.value = false;
        });
    }
});

// ─── Submit ───────────────────────────────────────────────────────────────────
function submitForm() {
    if (minPriceValidationError.value) return;
    const isEdit = !!props.service?.id;
    
    // Sanitize optional numeric values to avoid empty string validation failures
    if (form.duration_value === '' || form.duration_value === undefined) form.duration_value = null;
    if (form.warranty_value === '' || form.warranty_value === undefined) form.warranty_value = null;
    if (form.min_price === '' || form.min_price === undefined || form.min_price === null) form.min_price = 0;
    if (form.default_discount_value === '' || form.default_discount_value === undefined) form.default_discount_value = null;

    form[isEdit ? 'put' : 'post'](
        isEdit ? `/app/services/${props.service.id}` : '/app/services',
        {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                selectedCatalogServiceId.value = '';
                emit('saved');
                emit('close');
            },
        }
    );
}
</script>
