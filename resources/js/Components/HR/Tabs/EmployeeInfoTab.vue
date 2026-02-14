<template>
    <div class="space-y-4">
        <!-- Sub-tabs -->
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 pb-2 overflow-x-auto">
            <button v-for="tab in subTabs" :key="tab.key" @click="activeSubTab = tab.key" :class="[
                'px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors',
                activeSubTab === tab.key
                    ? 'bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
            ]">
                {{ tab.icon }} {{ tab.label }}
            </button>
        </div>

        <form @submit.prevent="save">
            <!-- Basic Info Tab -->
            <div v-show="activeSubTab === 'basic'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Photo -->
                <div class="lg:col-span-3 flex items-center gap-4">
                    <div
                        class="w-24 h-24 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                        <img v-if="employee.photo_path" :src="`/storage/${employee.photo_path}`"
                            class="w-full h-full object-cover" />
                        <svg v-else class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <input type="file" @change="handlePhotoUpload" accept="image/*" class="text-sm text-gray-500" />
                </div>

                <!-- Name AR -->
                <div>
                    <label :class="labelClass">{{ $t('common.name') }} (AR) <span class="text-red-500">*</span></label>
                    <input v-model="form.name_ar" type="text" required :class="[
                        'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500',
                        form.errors.name_ar ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500'
                    ]" />
                    <p v-if="form.errors.name_ar" class="mt-1 text-xs text-red-500">{{ form.errors.name_ar }}</p>
                </div>

                <!-- Name EN -->
                <div>
                    <label :class="labelClass">{{ $t('common.name') }} (EN)</label>
                    <input v-model="form.name_en" type="text" :class="inputClass" />
                </div>

                <!-- Phone -->
                <!-- Phone -->
                <div>
                    <label :class="labelClass">{{ $t('common.phone') }}</label>
                    <input v-model="form.phone" type="tel" dir="ltr" :class="[
                        'w-full px-4 py-2.5 text-sm border rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500',
                        form.errors.phone ? 'border-red-500 focus:border-red-500' : 'border-gray-300 dark:border-gray-600 focus:border-violet-500'
                    ]" />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label :class="labelClass">
                        {{ $t('common.email') }} <span class="text-red-500">*</span>
                    </label>
                    <input v-model="form.email" type="email" dir="ltr" :class="inputClass" required />
                </div>

                <!-- Gender -->
                <div>
                    <SearchableSelect v-model="form.gender" :label="$t('hr.employees.gender')"
                        :options="[{ id: 'male', name: $t('hr.employees.genders.male') }, { id: 'female', name: $t('hr.employees.genders.female') }]"
                        option-label="name" option-value="id" />
                </div>

                <!-- Marital Status -->
                <div>
                    <SearchableSelect v-model="form.marital_status" :label="$t('hr.employees.marital_status')"
                        :options="[{ id: 'single', name: $t('hr.employees.marital_statuses.single') }, { id: 'married', name: $t('hr.employees.marital_statuses.married') }]"
                        option-label="name" option-value="id" />
                </div>

                <!-- Birth Date -->
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.birth_date') }}</label>
                    <input v-model="form.birth_date" type="date" :class="inputClass" />
                </div>
            </div>

            <!-- Address Tab -->
            <div v-show="activeSubTab === 'address'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.city') }}</label>
                    <input v-model="form.city" type="text" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.district') }}</label>
                    <input v-model="form.district" type="text" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.street') }}</label>
                    <input v-model="form.street" type="text" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.building_number') }}</label>
                    <input v-model="form.building_number" type="text" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.address.postal_code') }}</label>
                    <input v-model="form.postal_code" type="text" :class="inputClass" />
                </div>
            </div>

            <!-- Job Info Tab -->
            <div v-show="activeSubTab === 'job'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <SearchableSelect v-model="form.job_title_id" :label="$t('hr.employees.job_title')"
                        :options="jobTitles" option-label="name_ar" option-value="id" />
                </div>
                <div>
                    <SearchableSelect v-model="form.employee_type_id" :label="$t('hr.employees.employee_type')"
                        :options="employeeTypes" option-label="name_ar" option-value="id" />
                </div>
                <div>
                    <SearchableSelect v-model="form.department_id" :label="$t('common.department')"
                        :options="departments" option-label="name_ar" option-value="id" />
                </div>
                <div v-if="centers && centers.length > 1">
                    <SearchableSelect v-model="form.center_id" :label="$t('hr.employees.branch')" :options="centers"
                        option-label="name" option-value="id" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.hire_date') }}</label>
                    <input v-model="form.hire_date" type="date" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.contract_end_date') }}</label>
                    <input v-model="form.contract_end_date" type="date" :class="inputClass" />
                </div>
                <div>
                    <!-- Existing User Link (Super Admin only or if already linked) -->
                    <SearchableSelect v-if="form.user_id" v-model="form.user_id" :label="$t('hr.employees.link_user')"
                        :options="users" option-label="name" option-value="id"
                        :placeholder="$t('hr.employees.no_user_link')" disabled
                        :hint="$t('hr.employees.user_already_linked')" />

                    <!-- Grant Access Toggle (If no user linked) -->
                    <div v-else
                        class="bg-violet-50 dark:bg-violet-900/20 p-4 rounded-xl border border-violet-100 dark:border-violet-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{
                                $t('hr.employees.grant_system_access') }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.grant_system_access" class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600">
                                </div>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ form.grant_system_access ? $t('hr.employees.system_access_info') :
                                $t('hr.employees.grant_system_access_hint') }}
                        </p>
                    </div>
                </div>
                <div>
                    <SearchableSelect v-model="form.status" :label="$t('common.status')"
                        :options="[{ id: 'active', name: $t('hr.employees.status.active') }, { id: 'inactive', name: $t('hr.employees.status.inactive') }, { id: 'terminated', name: $t('hr.employees.status.terminated') }]"
                        option-label="name" option-value="id" />
                </div>
                <div class="lg:col-span-3">
                    <label :class="labelClass">{{ $t('common.notes') }}</label>
                    <textarea v-model="form.notes" rows="3" :class="inputClass"></textarea>
                </div>
            </div>

            <!-- Identity Tab -->
            <div v-show="activeSubTab === 'identity'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <SearchableSelect v-model="form.nationality_id" :label="$t('hr.employees.identity.nationality')"
                        :options="nationalities"
                        :option-label="(opt) => locale === 'ar' ? opt.name_ar : opt.name_en || opt.name_ar"
                        option-value="id" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.national_id') }}</label>
                    <input v-model="form.national_id" type="text" dir="ltr" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.national_id_expiry') }}</label>
                    <input v-model="form.national_id_expiry" type="date" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.passport_number') }}</label>
                    <input v-model="form.passport_number" type="text" dir="ltr" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.passport_expiry') }}</label>
                    <input v-model="form.passport_expiry" type="date" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.border_entry_number') }}</label>
                    <input v-model="form.border_entry_number" type="text" dir="ltr" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.border_port') }}</label>
                    <input v-model="form.border_port" type="text" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.sponsor_name') }}</label>
                    <input v-model="form.sponsor_name" type="text" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.identity.profession_on_id') }}</label>
                    <input v-model="form.profession_on_id" type="text" :class="inputClass" />
                </div>
            </div>

            <!-- Insurance Tab -->
            <div v-show="activeSubTab === 'insurance'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.insurance.company') }}</label>
                    <input v-model="form.insurance_company" type="text" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.insurance.card_number') }}</label>
                    <input v-model="form.insurance_card_number" type="text" dir="ltr" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.insurance.policy_number') }}</label>
                    <input v-model="form.insurance_policy_number" type="text" dir="ltr" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.insurance.expiry') }}</label>
                    <input v-model="form.insurance_expiry" type="date" :class="inputClass" />
                </div>
                <div>
                    <label :class="labelClass">{{ $t('hr.employees.insurance.classification') }}</label>
                    <input v-model="form.insurance_classification" type="text" :class="inputClass" />
                </div>
                <div class="lg:col-span-3">
                    <label :class="labelClass">{{ $t('hr.employees.insurance.details') }}</label>
                    <textarea v-model="form.insurance_details" rows="3" :class="inputClass"></textarea>
                </div>
            </div>

            <!-- Save Button -->
            <div v-if="['basic', 'address', 'job', 'identity', 'insurance'].includes(activeSubTab)"
                class="flex justify-end mt-6">
                <button type="submit" :disabled="form.processing"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-violet-600 rounded-xl hover:bg-violet-700 disabled:opacity-50 shadow-lg">
                    {{ form.processing ? $t('common.saving') : $t('common.save_changes') }}
                </button>
            </div>
        </form>

        <!-- Documents Tab -->
        <DocumentsTab v-show="activeSubTab === 'documents'" :employee="employee" />

        <!-- Contracts Tab -->
        <ContractsTab v-show="activeSubTab === 'contracts'" :employee="employee" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DocumentsTab from '@/Components/HR/Tabs/DocumentsTab.vue';
import ContractsTab from '@/Components/HR/Tabs/ContractsTab.vue';
import { useToast } from '@/Composables/useToast';

const { t, locale } = useI18n();
const { success } = useToast();

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

const subTabs = [
    { key: 'basic', label: t('hr.employees.tabs.basic'), icon: '👤' },
    { key: 'address', label: t('hr.employees.tabs.address'), icon: '📍' },
    { key: 'job', label: t('hr.employees.tabs.job'), icon: '💼' },
    { key: 'identity', label: t('hr.employees.tabs.identity'), icon: '🪪' },
    { key: 'insurance', label: t('hr.employees.tabs.insurance'), icon: '🏥' },
    { key: 'documents', label: t('hr.employees.tabs.documents'), icon: '📂' },
    { key: 'contracts', label: t('hr.employees.tabs.contracts'), icon: '📄' },
];

const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
const inputClass = 'w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500';

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
    });
}
</script>
