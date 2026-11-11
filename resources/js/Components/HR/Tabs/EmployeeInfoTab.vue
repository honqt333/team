<template>
    <div class="space-y-4">
        <!-- Sub-tabs -->
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 pb-2 overflow-x-auto">
            <button
                v-for="tab in subTabs"
                :key="tab.key"
                type="button"
                @click="activeSubTab = tab.key"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition-all',
                    activeSubTab === tab.key
                        ? 'bg-action-primary text-white shadow-md shadow-focus-ring-soft font-bold'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60',
                ]"
            >
                <span>{{ tab.icon }}</span>
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.errorCount > 0"
                    class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"
                >
                    {{ tab.errorCount }}
                </span>
            </button>
        </div>

        <form @submit.prevent="save">
            <!-- Basic Info Tab -->
            <div
                v-show="activeSubTab === 'basic'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <!-- Photo -->
                <div class="lg:col-span-3 flex items-center gap-4">
                    <div
                        class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center overflow-hidden"
                    >
                        <img
                            v-if="employee.photo_path"
                            :src="`/storage/${employee.photo_path}`"
                            class="w-full h-full object-cover"
                        />
                        <svg
                            v-else
                            class="w-12 h-12 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>
                    <input
                        type="file"
                        @change="handlePhotoUpload"
                        accept="image/*"
                        class="text-sm text-gray-500"
                    />
                </div>

                <!-- Name AR -->
                <div>
                    <label :class="labelClass">
                        {{ $t('common.name') }} (AR)
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name_ar"
                        name="name_ar"
                        type="text"
                        required
                        :class="[
                            'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring',
                            form.errors.name_ar
                                ? 'border-red-500 focus:border-danger'
                                : 'border-gray-300 dark:border-gray-600 focus:border-action-primary',
                        ]"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1 text-xs text-red-500">
                        {{ form.errors.name_ar }}
                    </p>
                </div>

                <!-- Name EN -->
                <div>
                    <label :class="labelClass">
                        {{ $t('common.name') }} (EN)
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name_en"
                        name="name_en"
                        type="text"
                        required
                        :class="[
                            inputClass,
                            form.errors.name_en ? 'border-red-500 focus:border-danger' : '',
                        ]"
                    />
                    <p v-if="form.errors.name_en" class="mt-1 text-xs text-red-500">
                        {{ form.errors.name_en }}
                    </p>
                </div>

                <!-- Phone -->
                <div>
                    <label :class="labelClass">{{ $t('common.phone') }}</label>
                    <input
                        v-model="form.phone"
                        name="phone"
                        type="tel"
                        dir="ltr"
                        :class="[
                            'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring',
                            form.errors.phone
                                ? 'border-red-500 focus:border-danger'
                                : 'border-gray-300 dark:border-gray-600 focus:border-action-primary',
                        ]"
                    />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">
                        {{ form.errors.phone }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label :class="labelClass">
                        {{ $t('common.email') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        name="email"
                        type="email"
                        dir="ltr"
                        :class="inputClass"
                        required
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Gender -->
                <div data-field-container="gender">
                    <SearchableSelect
                        v-model="form.gender"
                        :label="$t('hr.employees.gender')"
                        :options="[
                            { id: 'male', name: $t('hr.employees.genders.male') },
                            { id: 'female', name: $t('hr.employees.genders.female') },
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                    <p v-if="form.errors.gender" class="mt-1 text-xs text-red-500">
                        {{ form.errors.gender }}
                    </p>
                </div>

                <!-- Marital Status -->
                <div data-field-container="marital_status">
                    <SearchableSelect
                        v-model="form.marital_status"
                        :label="$t('hr.employees.marital_status')"
                        :options="[
                            { id: 'single', name: $t('hr.employees.marital_statuses.single') },
                            { id: 'married', name: $t('hr.employees.marital_statuses.married') },
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                    <p v-if="form.errors.marital_status" class="mt-1 text-xs text-red-500">
                        {{ form.errors.marital_status }}
                    </p>
                </div>

                <!-- Birth Date -->
                <div data-field-container="birth_date">
                    <label :class="labelClass">{{ $t('hr.employees.birth_date') }}</label>
                    <CustomDatePicker v-model="form.birth_date" name="birth_date" />
                    <p v-if="form.errors.birth_date" class="mt-1 text-xs text-red-500">
                        {{ form.errors.birth_date }}
                    </p>
                </div>
            </div>

            <!-- Address Tab -->
            <div
                v-show="activeSubTab === 'address'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.city') }}</label>
                    <input v-model="form.city" name="city" type="text" :class="inputClass" />
                    <p v-if="form.errors.city" class="mt-1 text-xs text-red-500">
                        {{ form.errors.city }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.district') }}</label>
                    <input
                        v-model="form.district"
                        name="district"
                        type="text"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.district" class="mt-1 text-xs text-red-500">
                        {{ form.errors.district }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.street') }}</label>
                    <input v-model="form.street" name="street" type="text" :class="inputClass" />
                    <p v-if="form.errors.street" class="mt-1 text-xs text-red-500">
                        {{ form.errors.street }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.address.building_number') }}
                    </label>
                    <input
                        v-model="form.building_number"
                        name="building_number"
                        type="text"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.building_number" class="mt-1 text-xs text-red-500">
                        {{ form.errors.building_number }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.postal_code') }}</label>
                    <input
                        v-model="form.postal_code"
                        name="postal_code"
                        type="text"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.postal_code" class="mt-1 text-xs text-red-500">
                        {{ form.errors.postal_code }}
                    </p>
                </div>
            </div>

            <!-- Job Info Tab -->
            <div
                v-show="activeSubTab === 'job'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <div data-field-container="job_title_id">
                    <SearchableSelect
                        v-model="form.job_title_id"
                        :label="$t('hr.employees.job_title')"
                        :options="jobTitles"
                        option-label="name_ar"
                        option-value="id"
                    />
                    <p v-if="form.errors.job_title_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.job_title_id }}
                    </p>
                </div>
                <div data-field-container="employee_type_id">
                    <SearchableSelect
                        v-model="form.employee_type_id"
                        :label="$t('hr.employees.employee_type')"
                        :options="employeeTypes"
                        option-label="name_ar"
                        option-value="id"
                    />
                    <p v-if="form.errors.employee_type_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.employee_type_id }}
                    </p>
                </div>
                <div data-field-container="department_id">
                    <SearchableSelect
                        v-model="form.department_id"
                        :label="$t('common.department')"
                        :options="departments"
                        option-label="name_ar"
                        option-value="id"
                    />
                    <p v-if="form.errors.department_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.department_id }}
                    </p>
                </div>
                <div v-if="centers && centers.length > 1" data-field-container="center_id">
                    <SearchableSelect
                        v-model="form.center_id"
                        :label="$t('hr.employees.branch')"
                        :options="centers"
                        option-label="name"
                        option-value="id"
                    />
                    <p v-if="form.errors.center_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.center_id }}
                    </p>
                </div>
                <div data-field-container="hire_date">
                    <label :class="labelClass">{{ $t('hr.employees.hire_date') }}</label>
                    <CustomDatePicker v-model="form.hire_date" name="hire_date" />
                    <p v-if="form.errors.hire_date" class="mt-1 text-xs text-red-500">
                        {{ form.errors.hire_date }}
                    </p>
                </div>
                <div data-field-container="contract_end_date">
                    <label :class="labelClass">{{ $t('hr.employees.contract_end_date') }}</label>
                    <CustomDatePicker v-model="form.contract_end_date" name="contract_end_date" />
                    <p v-if="form.errors.contract_end_date" class="mt-1 text-xs text-red-500">
                        {{ form.errors.contract_end_date }}
                    </p>
                </div>
                <div>
                    <!-- Existing User Link (Super Admin only or if already linked) -->
                    <SearchableSelect
                        v-if="form.user_id"
                        v-model="form.user_id"
                        :label="$t('hr.employees.link_user')"
                        :options="users"
                        option-label="name"
                        option-value="id"
                        :placeholder="$t('hr.employees.no_user_link')"
                        disabled
                        :hint="$t('hr.employees.user_already_linked')"
                    />

                    <!-- Grant Access Toggle (If no user linked) -->
                    <div
                        v-else
                        class="bg-selection-bg p-4 rounded-xl border border-action-primary/25"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $t('hr.employees.grant_system_access') }}
                            </span>
                            <AppToggle v-model="form.grant_system_access" color="violet" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{
                                form.grant_system_access
                                    ? $t('hr.employees.system_access_info')
                                    : $t('hr.employees.grant_system_access_hint')
                            }}
                        </p>
                    </div>
                </div>
                <div data-field-container="status">
                    <SearchableSelect
                        v-model="form.status"
                        :label="$t('common.status')"
                        :options="[
                            { id: 'active', name: $t('hr.employees.status.active') },
                            { id: 'inactive', name: $t('hr.employees.status.inactive') },
                            { id: 'terminated', name: $t('hr.employees.status.terminated') },
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                    <p v-if="form.errors.status" class="mt-1 text-xs text-red-500">
                        {{ form.errors.status }}
                    </p>
                </div>
                <div class="lg:col-span-3">
                    <label :class="labelClass">{{ $t('common.notes') }}</label>
                    <textarea
                        v-model="form.notes"
                        name="notes"
                        rows="3"
                        :class="inputClass"
                    ></textarea>
                    <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">
                        {{ form.errors.notes }}
                    </p>
                </div>
            </div>

            <!-- Identity Tab -->
            <div
                v-show="activeSubTab === 'identity'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <div data-field-container="nationality_id">
                    <SearchableSelect
                        v-model="form.nationality_id"
                        :label="$t('hr.employees.identity.nationality')"
                        :options="nationalities"
                        :option-label="
                            (opt) => (locale === 'ar' ? opt.name_ar : opt.name_en || opt.name_ar)
                        "
                        option-value="id"
                    />
                    <p v-if="form.errors.nationality_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.nationality_id }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.national_id') }}</label>
                    <input
                        v-model="form.national_id"
                        name="national_id"
                        type="text"
                        dir="ltr"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.national_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.national_id }}
                    </p>
                </div>
                <div data-field-container="national_id_expiry">
                    <label :class="labelClass">
                        {{ $t('hr.employees.identity.national_id_expiry') }}
                    </label>
                    <CustomDatePicker v-model="form.national_id_expiry" name="national_id_expiry" />
                    <p v-if="form.errors.national_id_expiry" class="mt-1 text-xs text-red-500">
                        {{ form.errors.national_id_expiry }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.identity.passport_number') }}
                    </label>
                    <input
                        v-model="form.passport_number"
                        name="passport_number"
                        type="text"
                        dir="ltr"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.passport_number" class="mt-1 text-xs text-red-500">
                        {{ form.errors.passport_number }}
                    </p>
                </div>
                <div data-field-container="passport_expiry">
                    <label :class="labelClass">
                        {{ $t('hr.employees.identity.passport_expiry') }}
                    </label>
                    <CustomDatePicker v-model="form.passport_expiry" name="passport_expiry" />
                    <p v-if="form.errors.passport_expiry" class="mt-1 text-xs text-red-500">
                        {{ form.errors.passport_expiry }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.identity.border_entry_number') }}
                    </label>
                    <input
                        v-model="form.border_entry_number"
                        name="border_entry_number"
                        type="text"
                        dir="ltr"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.border_entry_number" class="mt-1 text-xs text-red-500">
                        {{ form.errors.border_entry_number }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.border_port') }}</label>
                    <input
                        v-model="form.border_port"
                        name="border_port"
                        type="text"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.border_port" class="mt-1 text-xs text-red-500">
                        {{ form.errors.border_port }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.identity.sponsor_name') }}
                    </label>
                    <input
                        v-model="form.sponsor_name"
                        name="sponsor_name"
                        type="text"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.sponsor_name" class="mt-1 text-xs text-red-500">
                        {{ form.errors.sponsor_name }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.identity.profession_on_id') }}
                    </label>
                    <input
                        v-model="form.profession_on_id"
                        name="profession_on_id"
                        type="text"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.profession_on_id" class="mt-1 text-xs text-red-500">
                        {{ form.errors.profession_on_id }}
                    </p>
                </div>
            </div>

            <!-- Insurance Tab -->
            <div
                v-show="activeSubTab === 'insurance'"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.insurance.company') }}</label>
                    <input
                        v-model="form.insurance_company"
                        name="insurance_company"
                        type="text"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.insurance_company" class="mt-1 text-xs text-red-500">
                        {{ form.errors.insurance_company }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.insurance.card_number') }}
                    </label>
                    <input
                        v-model="form.insurance_card_number"
                        name="insurance_card_number"
                        type="text"
                        dir="ltr"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.insurance_card_number" class="mt-1 text-xs text-red-500">
                        {{ form.errors.insurance_card_number }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.insurance.policy_number') }}
                    </label>
                    <input
                        v-model="form.insurance_policy_number"
                        name="insurance_policy_number"
                        type="text"
                        dir="ltr"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.insurance_policy_number" class="mt-1 text-xs text-red-500">
                        {{ form.errors.insurance_policy_number }}
                    </p>
                </div>
                <div data-field-container="insurance_expiry">
                    <label :class="labelClass">{{ $t('hr.employees.insurance.expiry') }}</label>
                    <CustomDatePicker v-model="form.insurance_expiry" name="insurance_expiry" />
                    <p v-if="form.errors.insurance_expiry" class="mt-1 text-xs text-red-500">
                        {{ form.errors.insurance_expiry }}
                    </p>
                </div>
                <div>
                    <label :class="labelClass">
                        {{ $t('hr.employees.insurance.classification') }}
                    </label>
                    <input
                        v-model="form.insurance_classification"
                        name="insurance_classification"
                        type="text"
                        :class="inputClass"
                    />
                    <p
                        v-if="form.errors.insurance_classification"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.insurance_classification }}
                    </p>
                </div>
                <div class="lg:col-span-3">
                    <label :class="labelClass">{{ $t('hr.employees.insurance.details') }}</label>
                    <textarea
                        v-model="form.insurance_details"
                        name="insurance_details"
                        rows="3"
                        :class="inputClass"
                    ></textarea>
                    <p v-if="form.errors.insurance_details" class="mt-1 text-xs text-red-500">
                        {{ form.errors.insurance_details }}
                    </p>
                </div>
            </div>

            <!-- Sticky Action Bar with Dual Save Button & Error Alert -->
            <div
                v-if="['basic', 'address', 'job', 'identity', 'insurance'].includes(activeSubTab)"
                class="sticky bottom-0 z-20 mt-8 -mx-6 -mb-6 p-4 bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-700 rounded-b-2xl shadow-xl flex items-center justify-between gap-4 flex-wrap"
            >
                <div class="flex items-center gap-3">
                    <span
                        v-if="totalErrorsCount > 0"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50"
                    >
                        <svg
                            class="w-4 h-4 text-red-500 animate-pulse"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        {{ $t('common.form_has_errors', { count: totalErrorsCount }) }}
                    </span>
                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $t('common.unsaved_changes') }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="form.reset()"
                        :disabled="form.processing || !form.isDirty"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all disabled:opacity-40"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl transition-all shadow-md shadow-focus-ring-soft active:scale-95 disabled:opacity-50"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        {{ form.processing ? $t('common.saving') : $t('common.save_changes') }}
                    </button>
                </div>
            </div>
        </form>

        <!-- Documents Tab -->
        <DocumentsTab v-show="activeSubTab === 'documents'" :employee="employee" />

        <!-- Contracts Tab -->
        <ContractsTab v-show="activeSubTab === 'contracts'" :employee="employee" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import DocumentsTab from '@/Components/HR/Tabs/DocumentsTab.vue';
import ContractsTab from '@/Components/HR/Tabs/ContractsTab.vue';
import { useToast } from '@/Composables/useToast';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const { t, locale } = useI18n();
const { success } = useToast();
const { scrollToError } = useFormScrollToError();

const props = defineProps({
    employee: Object,
    jobTitles: Array,
    employeeTypes: Array,
    departments: Array,
    users: Array,
    nationalities: Array,
    centers: Array,
});

const activeSubTab = ref('basic');

const fieldToTabMap = {
    name_ar: 'basic',
    name_en: 'basic',
    phone: 'basic',
    email: 'basic',
    gender: 'basic',
    marital_status: 'basic',
    birth_date: 'basic',

    city: 'address',
    district: 'address',
    street: 'address',
    building_number: 'address',
    postal_code: 'address',

    job_title_id: 'job',
    employee_type_id: 'job',
    department_id: 'job',
    center_id: 'job',
    hire_date: 'job',
    contract_end_date: 'job',
    user_id: 'job',
    grant_system_access: 'job',
    status: 'job',
    notes: 'job',

    nationality_id: 'identity',
    national_id: 'identity',
    national_id_expiry: 'identity',
    passport_number: 'identity',
    passport_expiry: 'identity',
    border_entry_number: 'identity',
    border_port: 'identity',
    sponsor_name: 'identity',
    profession_on_id: 'identity',

    insurance_company: 'insurance',
    insurance_card_number: 'insurance',
    insurance_policy_number: 'insurance',
    insurance_expiry: 'insurance',
    insurance_classification: 'insurance',
    insurance_details: 'insurance',
};

const tabErrorCount = (tabKey) => {
    return Object.keys(form.errors).filter((field) => fieldToTabMap[field] === tabKey).length;
};

const totalErrorsCount = computed(() => Object.keys(form.errors).length);

const subTabs = computed(() => [
    {
        key: 'basic',
        label: t('hr.employees.tabs.basic'),
        icon: '👤',
        errorCount: tabErrorCount('basic'),
    },
    {
        key: 'address',
        label: t('hr.employees.tabs.address'),
        icon: '📍',
        errorCount: tabErrorCount('address'),
    },
    { key: 'job', label: t('hr.employees.tabs.job'), icon: '💼', errorCount: tabErrorCount('job') },
    {
        key: 'identity',
        label: t('hr.employees.tabs.identity'),
        icon: '🪪',
        errorCount: tabErrorCount('identity'),
    },
    {
        key: 'insurance',
        label: t('hr.employees.tabs.insurance'),
        icon: '🏥',
        errorCount: tabErrorCount('insurance'),
    },
    { key: 'documents', label: t('hr.employees.tabs.documents'), icon: '📂', errorCount: 0 },
    { key: 'contracts', label: t('hr.employees.tabs.contracts'), icon: '📄', errorCount: 0 },
]);

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass =
    'w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-action-primary';

const form = useForm({
    // Basic Info
    name_ar: props.employee.name_ar || '',
    name_en: props.employee.name_en || '',
    phone: props.employee.phone || '',
    email: props.employee.email || '',
    gender: props.employee.gender || null,
    marital_status: props.employee.marital_status || null,
    birth_date: props.employee.birth_date || '',
    // Address
    city: props.employee.city || '',
    district: props.employee.district || '',
    street: props.employee.street || '',
    building_number: props.employee.building_number || '',
    postal_code: props.employee.postal_code || '',
    // Job Info
    job_title_id: props.employee.job_title_id || null,
    employee_type_id: props.employee.employee_type_id || null,
    department_id: props.employee.department_id || null,
    center_id: props.employee.center_id || null,
    hire_date: props.employee.hire_date || '',
    contract_end_date: props.employee.contract_end_date || '',
    user_id: props.employee.user_id || null,
    grant_system_access: false,
    status: props.employee.status || 'active',
    notes: props.employee.notes || '',
    // Identity
    nationality_id: props.employee.nationality_id || null,
    national_id: props.employee.national_id || '',
    national_id_expiry: props.employee.national_id_expiry || '',
    passport_number: props.employee.passport_number || '',
    passport_expiry: props.employee.passport_expiry || '',
    border_entry_number: props.employee.border_entry_number || '',
    border_port: props.employee.border_port || '',
    sponsor_name: props.employee.sponsor_name || '',
    profession_on_id: props.employee.profession_on_id || '',
    // Insurance
    insurance_company: props.employee.insurance_company || '',
    insurance_card_number: props.employee.insurance_card_number || '',
    insurance_policy_number: props.employee.insurance_policy_number || '',
    insurance_expiry: props.employee.insurance_expiry || '',
    insurance_classification: props.employee.insurance_classification || '',
    insurance_details: props.employee.insurance_details || '',
});

function handlePhotoUpload(event) {
    const file = event.target.files[0];
    if (file) {
        const formData = new FormData();
        formData.append('photo', file);
        router.post(route('app.hr.employees.upload-photo', props.employee.id), formData, {
            preserveScroll: true,
            onSuccess: () => success(t('common.saved_success')),
        });
    }
}

function save() {
    form.put(route('app.hr.employees.update', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => success(t('common.saved_success')),
        onError: (errors) => {
            scrollToError(errors, {
                tabMap: fieldToTabMap,
                onSwitchTab: (tabKey) => {
                    activeSubTab.value = tabKey;
                },
            });
        },
    });
}
</script>
