<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Toggle from '@/Components/Toggle.vue';
import InputLabel from '@/Components/InputLabel.vue';
import LoadingSpinner from '@/Components/Common/LoadingSpinner.vue';

const props = defineProps({
    employee: Object,
});

const { t, locale } = useI18n();
const { success, error } = useToast();

const loading = ref(true);
const hasUser = ref(false);
const availableRoles = ref([]);
const groupedPermissions = ref({});
const permissionDescriptions = ref({});
const searchQuery = ref('');

const form = useForm({
    roles: [],
    permissions: [],
});

const activeModules = ref([]); 

const fetchPermissions = async () => {
    try {
        const response = await axios.get(route('app.hr.employees.permissions.index', props.employee.id));
        hasUser.value = response.data.has_user;
        
        if (hasUser.value) {
            form.roles = response.data.current_roles;
            form.permissions = response.data.current_permissions;
            availableRoles.value = response.data.available_roles;
            groupedPermissions.value = response.data.grouped_permissions;
            permissionDescriptions.value = response.data.permission_descriptions;
            
            // Open all modules by default for better visibility or just the first
            const modules = Object.keys(response.data.grouped_permissions);
            if (modules.length > 0) activeModules.value = modules;
        }
    } catch (e) {
        console.error(e);
        error(t('common.error_loading_data'));
    } finally {
        loading.value = false;
    }
};

const toggleModule = (moduleName) => {
    if (activeModules.value.includes(moduleName)) {
        activeModules.value = activeModules.value.filter(m => m !== moduleName);
    } else {
        activeModules.value.push(moduleName);
    }
};

// Search Logic
const filteredGroupedPermissions = computed(() => {
    if (!searchQuery.value) return groupedPermissions.value;
    
    const query = searchQuery.value.toLowerCase();
    const result = {};
    
    for (const [module, perms] of Object.entries(groupedPermissions.value)) {
        const filteredPerms = perms.filter(p => {
            const desc = (permissionDescriptions.value[p] || p).toLowerCase();
            return desc.includes(query);
        });
        
        if (filteredPerms.length > 0) {
            result[module] = filteredPerms;
            // Also ensure module is expanded when searching
            if (!activeModules.value.includes(module)) {
                activeModules.value.push(module);
            }
        }
    }
    return result;
});

// Computed: permissions inherited from selected roles
const rolePermissions = computed(() => {
    const perms = new Set();
    form.roles.forEach(roleName => {
        const role = availableRoles.value.find(r => r.name === roleName);
        if (role && role.permissions) {
            role.permissions.forEach(p => perms.add(p.name));
        }
    });
    return Array.from(perms);
});

// Check if a permission is inherited from a role (read-only)
const isPermissionFromRole = (permission) => {
    return rolePermissions.value.includes(permission);
};

// Check if permission is active (either direct or from role)
const isPermissionActive = (permission) => {
    return form.permissions.includes(permission) || rolePermissions.value.includes(permission);
};

// Computed: total unique active permissions (direct + from roles)
const totalActivePermissions = computed(() => {
    const allPerms = new Set([...form.permissions, ...rolePermissions.value]);
    return allPerms.size;
});

// Count active permissions in a module
const getModuleActiveCount = (moduleName) => {
    const perms = groupedPermissions.value[moduleName] || [];
    return perms.filter(p => isPermissionActive(p)).length;
};

// "Select All" Logic per Module
const isModuleFullySelected = (moduleName) => {
    const perms = groupedPermissions.value[moduleName] || [];
    if (perms.length === 0) return false;
    return perms.every(p => form.permissions.includes(p));
};

const toggleAllModulePermissions = (moduleName) => {
    const perms = groupedPermissions.value[moduleName] || [];
    const allSelected = isModuleFullySelected(moduleName);
    
    if (allSelected) {
        // Unselect all
        form.permissions = form.permissions.filter(p => !perms.includes(p));
    } else {
        // Select all
        const newPerms = new Set([...form.permissions, ...perms]);
        form.permissions = Array.from(newPerms);
    }
};

const submit = () => {
    form.put(route('app.hr.employees.permissions.update', props.employee.id), {
        onSuccess: () => {
            success(t('common.saved_success'));
        },
        onError: () => {
            error(t('common.error_saving_data'));
        }
    });
};

const createUser = () => {
    router.visit(route('settings.users.index'));
};

onMounted(() => {
    fetchPermissions();
});
</script>

<template>
    <div v-if="loading" class="flex justify-center py-12">
        <LoadingSpinner />
    </div>

    <!-- No User State -->
    <div v-else-if="!hasUser" class="text-center py-12">
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-8 max-w-lg mx-auto border border-dashed border-gray-300 dark:border-gray-700">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-violet-100 dark:bg-violet-900/30 mb-6">
                <svg class="h-8 w-8 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                {{ t('hr.employees.no_user_account') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-8">
                {{ t('hr.employees.create_user_account_msg') }}
            </p>
            <button
                @click="createUser"
                class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl shadow-lg text-white bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 transition-all transform hover:scale-105"
            >
                <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                {{ t('users.create_new') }}
            </button>
        </div>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-8 animate-fade-in-up">
        
        <!-- Roles Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ t('users.roles') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t('users.select_roles_desc', 'Select roles to assign predefined permission sets') }}
                    </p>
                </div>
            </div>
            
            <div v-if="availableRoles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <label 
                    v-for="role in availableRoles" 
                    :key="role.id"
                    class="relative flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 group"
                    :class="form.roles.includes(role.name) 
                        ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-900/10' 
                        : 'border-gray-100 dark:border-gray-700 hover:border-violet-200 dark:hover:border-violet-800 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                >
                    <div class="flex-shrink-0 mt-0.5">
                        <Checkbox 
                            v-model:checked="form.roles" 
                            :value="role.name"
                            class="w-5 h-5 text-violet-600 border-gray-300 focus:ring-violet-500 rounded"
                        />
                    </div>
                    <div class="flex-1">
                        <span class="block text-base font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-violet-700 dark:group-hover:text-violet-300 transition-colors">
                            {{ role.label_ar || role.name }}
                        </span>
                        <span v-if="role.permissions && role.permissions.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
                            {{ role.permissions.length }} {{ t('permissions.count', 'صلاحية') }}
                        </span>
                    </div>
                    <div v-if="form.roles.includes(role.name)" class="absolute top-4 end-4 text-violet-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </label>
            </div>
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                {{ t('common.no_data_available') }}
            </div>
        </div>

        <!-- Permissions Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <!-- Header & Search -->
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ t('users.permissions_all', 'الصلاحيات') }}
                                </h3>
                                <span class="px-2 py-0.5 text-xs font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full">
                                    {{ totalActivePermissions }} {{ t('permissions.active', 'مفعّلة') }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <span class="text-violet-600 dark:text-violet-400 font-medium">{{ rolePermissions.length }}</span> {{ t('permissions.from_roles', 'من الأدوار') }}
                                •
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ form.permissions.length }}</span> {{ t('users.direct_permissions', 'مباشرة') }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="relative w-full md:w-64">
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="$t('common.search_permissions', 'Search permissions...')"
                            class="w-full ps-10 pe-4 py-2 text-sm border-gray-300 dark:border-gray-600 rounded-xl focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm"
                        />
                        <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="(perms, moduleName) in filteredGroupedPermissions" :key="moduleName" class="group">
                    <!-- Module Header -->
                    <div 
                        class="w-full flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                        @click="toggleModule(moduleName)"
                    >
                        <div class="flex items-center gap-3">
                            <button 
                                type="button"
                                class="p-1 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                <svg 
                                    class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': activeModules.includes(moduleName) }"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <span class="text-base font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wide">
                                {{ t('permissions_modules.' + moduleName, moduleName) }}
                            </span>
                            <span 
                                class="px-2 py-0.5 text-xs font-medium rounded-full"
                                :class="getModuleActiveCount(moduleName) > 0 
                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' 
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                            >
                                {{ getModuleActiveCount(moduleName) }}/{{ perms.length }}
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-3" @click.stop>
                            <span class="text-xs font-medium text-gray-500 uppercase">{{ t('common.select_all', 'Select All') }}</span>
                            <Toggle 
                                :checked="isModuleFullySelected(moduleName)"
                                @update:checked="toggleAllModulePermissions(moduleName)"
                            />
                        </div>
                    </div>
                    
                    <!-- Permissions Grid -->
                    <div 
                        v-show="activeModules.includes(moduleName)"
                        class="p-6 bg-gray-50/30 dark:bg-gray-900/10 border-t border-gray-100 dark:border-gray-700"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="permission in perms" 
                                :key="permission"
                                class="flex items-center justify-between p-3 rounded-xl border transition-colors"
                                :class="[
                                    isPermissionFromRole(permission) 
                                        ? 'bg-violet-50 dark:bg-violet-900/20 border-violet-200 dark:border-violet-800' 
                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-700'
                                ]"
                            >
                                <div class="flex flex-col pe-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                            {{ permissionDescriptions[permission] || permission }}
                                        </span>
                                        <span 
                                            v-if="isPermissionFromRole(permission)" 
                                            class="px-1.5 py-0.5 text-[10px] font-medium bg-violet-100 dark:bg-violet-800 text-violet-700 dark:text-violet-300 rounded"
                                        >
                                            {{ t('permissions.from_role', 'من الدور') }}
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-mono mt-0.5 truncate max-w-[200px]" :title="permission">
                                        {{ permission }}
                                    </span>
                                </div>
                                <Toggle 
                                    v-if="!isPermissionFromRole(permission)"
                                    v-model:checked="form.permissions" 
                                    :value="permission"
                                />
                                <div v-else class="flex items-center gap-1 text-violet-600 dark:text-violet-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State for Search -->
                <div v-if="Object.keys(filteredGroupedPermissions).length === 0" class="p-8 text-center text-gray-500">
                    {{ t('common.no_results_found') }}
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="sticky bottom-0 z-10 bg-gray-50/95 dark:bg-gray-900/95 backdrop-blur-sm p-4 -mx-4 -mb-4 flex justify-end border-t border-gray-200 dark:border-gray-700">
            <button
                type="submit"
                class="px-8 py-3 text-sm font-bold text-white bg-gradient-to-r from-violet-600 to-indigo-600 rounded-xl hover:from-violet-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 shadow-lg shadow-violet-500/30 transition-all duration-200 disabled:opacity-75 disabled:cursor-not-allowed transform hover:-translate-y-0.5"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ t('common.saving') }}
                </span>
                <span v-else>{{ t('common.save_changes') }}</span>
            </button>
        </div>
    </form>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
