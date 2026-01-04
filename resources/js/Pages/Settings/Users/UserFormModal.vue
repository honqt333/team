<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3'; // Import usePage
import BaseModal from '@/Components/BaseModal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue'; // Import SearchableSelect

const props = defineProps({
    show: Boolean,
    user: Object, // If provided, edit mode
    centers: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    employees: { // Add employees prop
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const { t, locale } = useI18n(); // Destructure locale

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: null,
    employee_id: null, // Add employee_id
    centers: [],
});

const isEditing = computed(() => !!props.user);

const roleOptions = computed(() => {
    return props.roles.map(role => ({
        value: role.id,
        label: locale.value === 'ar' ? (role.label_ar || role.name) : (role.label_en || role.name)
    }));
});

// Employee Options
const employeeOptions = computed(() => {
    let options = props.employees.map(emp => ({
        value: emp.id,
        label: `${emp.employee_number} - ${locale.value === 'ar' ? emp.name_ar : emp.name_en}`
    }));

     // If editing and user has an employee, ensure they are in the list (even if not in "unlinked" list passed from backend)
    // Actually, backend passes "unlinked" employees. The currently linked employee is NOT "unlinked".
    // So we need to handle this.
    // The backend ONLY passes unlinked employees.
    // But `props.user.employee` exists if user is linked.
    // So we should append `props.user.employee` to the options if it exists.
    
    if (props.user && props.user.employee) {
        // Check if already in list (unlikely based on backend logic, but safe to check)
        const exists = options.find(o => o.value === props.user.employee.id);
        if (!exists) {
            options.unshift({
                value: props.user.employee.id,
                label: `${props.user.employee.employee_number || 'EMP'} - ${locale.value === 'ar' ? props.user.employee.name_ar : props.user.employee.name_en}`
            });
        }
    }
    
    return options;
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.user) {
            form.name = props.user.name;
            form.email = props.user.email;
            form.password = '';
            form.password_confirmation = '';
            // Extract center IDs from user.centers relation
            form.centers = props.user.centers ? props.user.centers.map(c => c.id) : [];
            // Extract role ID
            form.role_id = props.user.roles && props.user.roles.length > 0 ? props.user.roles[0].id : null;
            // Extract employee ID
            form.employee_id = props.user.employee ? props.user.employee.id : null;
            form.is_active = !!props.user.is_active; // Set is_active
        } else {
            form.reset();
            form.centers = [];
            form.role_id = null;
            form.employee_id = null;
            form.is_active = true; // Default to active
        }
    }
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('settings.users.update', props.user.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('settings.users.store'), {
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <BaseModal
        :show="show"
        :title="isEditing ? t('users.edit_user', 'Edit User') : t('users.create_user', 'New User')"
        @close="emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4">
                <!-- Name -->
                <div>
                    <InputLabel :value="t('users.name', 'Name')">
                        <span class="text-red-500">*</span>
                    </InputLabel>
                    <TextInput
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div>
                    <InputLabel :value="t('users.email', 'Email')">
                        <span class="text-red-500">*</span>
                    </InputLabel>
                    <TextInput
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Password -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel :value="isEditing ? t('users.new_password', 'New Password') : t('users.password', 'Password')">
                            <span v-if="!isEditing" class="text-red-500">*</span>
                        </InputLabel>
                        <TextInput
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            :required="!isEditing"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>
                    
                    <div>
                        <InputLabel :value="t('users.confirm_password', 'Confirm Password')">
                            <span v-if="!isEditing" class="text-red-500">*</span>
                        </InputLabel>
                        <TextInput
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            :required="!isEditing || !!form.password"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel :value="t('users.role', 'Role')">
                        <span class="text-red-500">*</span>
                    </InputLabel>
                    <SearchableSelect
                        v-model="form.role_id"
                        :options="roleOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('users.select_role', 'Select Role')"
                        required
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="form.errors.role_id" />
                </div>

                <!-- Employee Selection -->
                <div>
                    <InputLabel :value="t('users.employee', 'Linked Employee')">
                         <span class="text-gray-400 text-xs font-normal">({{ t('common.optional', 'Optional') }})</span>
                    </InputLabel>
                    <SearchableSelect
                        v-model="form.employee_id"
                        :options="employeeOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('users.select_employee', 'Select Employee')"
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="form.errors.employee_id" />
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <Checkbox
                        v-model:checked="form.is_active"
                        id="is_active"
                        class="text-violet-600 focus:ring-violet-500 rounded"
                    />
                    <InputLabel for="is_active" class="cursor-pointer mb-0">
                        {{ t('users.is_active', 'Active Account') }}
                        <div class="text-xs text-gray-500 font-normal mt-0.5">
                            {{ t('users.is_active_hint', 'Allow user to login to the system') }}
                        </div>
                    </InputLabel>
                </div>

                <!-- Centers Selection -->
                <div>
                    <InputLabel :value="t('users.allowed_centers', 'Allowed Branches')">
                        <span class="text-red-500">*</span>
                    </InputLabel>
                    
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto p-1 custom-scrollbar">
                        <label 
                            v-for="center in centers" 
                            :key="center.id"
                            class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200"
                            :class="form.centers.includes(center.id) 
                                ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 ring-1 ring-violet-500' 
                                : 'border-gray-200 dark:border-gray-700'"
                        >
                            <Checkbox 
                                v-model:checked="form.centers"
                                :value="center.id"
                                class="text-violet-600 focus:ring-violet-500 rounded"
                            />
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ center.name }}</span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.centers" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                    @click="emit('close')"
                >
                    {{ t('common.cancel', 'Cancel') }}
                </button>
                <button
                    type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-violet-600 to-indigo-600 rounded-xl hover:from-violet-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 shadow-lg shadow-violet-500/30 transition-all duration-200 disabled:opacity-75 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    {{ form.processing ? t('common.saving', 'Saving...') : (isEditing ? t('common.update', 'Update') : t('common.save', 'Save')) }}
                </button>
            </div>
        </form>
    </BaseModal>
</template>
