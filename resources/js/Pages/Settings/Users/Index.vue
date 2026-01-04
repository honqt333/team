<script setup>
import { ref, watch } from 'vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import UserManagementTabs from '@/Pages/Settings/Partials/UserManagementTabs.vue';
import UserFormModal from './UserFormModal.vue';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    users: Object,
    filters: Object,
    centers: Array,
    roles: Array,
    employees: Array, // Add employees prop
});

const { t, locale } = useI18n(); // Destructure locale
const { can } = usePermission();

const getRoleLabel = (user) => {
    if (!user.roles || user.roles.length === 0) return '-';
    // Assuming single role per user for display
    const role = user.roles[0];
    return locale.value === 'ar' ? (role.label_ar || role.name) : (role.label_en || role.name);
};

const search = ref(props.filters.search || '');
const showModal = ref(false);
const selectedUser = ref(null);

watch(search, (value) => {
    router.get(
        route('settings.users.index'),
        { search: value },
        { 
            preserveState: true, 
            preserveScroll: true, 
            replace: true 
        }
    );
});

const openCreateModal = () => {
    selectedUser.value = null;
    showModal.value = true;
};

const openEditModal = (user) => {
    selectedUser.value = user;
    showModal.value = true;
};

const deleteUser = (user) => {
    if (confirm(t('common.confirm_delete', 'Are you sure you want to delete this item?'))) {
        router.delete(route('settings.users.destroy', user.id));
    }
};

const toggleUserActive = (user) => {
    router.patch(route('settings.users.toggle', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Optimistic update handled by Inertia reload
        }
    });
};
// Actually, I should use the dedicated method "toggleActive" I added to controller. 
// But I need to register it in routes.
// Let's check routes/web.php again.
// I haven't added the route. I should add a route for toggle active.
// For now, I'll use the 'update' method since I modified it to accept is_active.
// However, 'update' requires validation of other fields too (name, email...).
// Sending just is_active might fail validation if I don't send everything.
// The update request validates name/email/roles.
// So simpler to just add a specific route.

</script>

<template>
    <Head :title="t('users.title', 'Users Management')" />

    <AppLayout>
        <div class="space-y-6">
            <!-- Header & Tabs -->
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-center gap-4">

                         <Link :href="route('settings.index')" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('users.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('users.description') }}</p>
                        </div>
                    </div>
                    
                    <button 
                        v-if="can('users.create')"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ t('users.create_new') }}
                    </button>
                </div>

                <UserManagementTabs active-tab="users" class="mt-6" />
            </div>

            <!-- Filters & Content -->
            <div class="space-y-6">
                <!-- Filters & Search -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-1">
                    <div class="p-3">
                        <div class="relative max-w-md">
                            <input 
                                v-model="search"
                                type="text" 
                                :placeholder="t('common.search', 'Search...')" 
                                class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors"
                            >
                            <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">{{ t('users.name', 'Name') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ t('users.email', 'Email') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ t('users.branches', 'Branches') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ t('users.role', 'Role') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ t('users.employee', 'Employee') }}</th>
                                    <th scope="col" class="px-6 py-3 text-center">{{ t('users.status', 'Status') }}</th>
                                    <th scope="col" class="px-6 py-3 text-center">{{ t('common.actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr 
                                    v-for="user in users.data" 
                                    :key="user.id" 
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/50 flex items-center justify-center text-violet-600 dark:text-violet-400 font-bold text-xs uppercase">
                                                {{ user.name.charAt(0) }}
                                            </div>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ user.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ user.email }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span 
                                                v-for="center in user.centers" 
                                                :key="center.id"
                                                class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                                            >
                                                {{ center.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="user.roles && user.roles.length > 0" class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                            {{ getRoleLabel(user) }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs italic">-</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="user.employee" class="text-emerald-600 dark:text-emerald-400 text-xs font-medium px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-900/30">
                                            {{ user.employee.name_ar || user.employee.name_en }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs italic">{{ t('users.no_employee', '-') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <button 
                                            v-if="can('users.update') && user.id !== $page.props.auth.user.id"
                                            @click="toggleUserActive(user)"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-violet-600 focus:ring-offset-2"
                                            :class="[user.is_active ? 'bg-violet-600' : 'bg-gray-200 dark:bg-gray-700']"
                                            :title="user.is_active ? t('users.active', 'Active') : t('users.inactive', 'Inactive')"
                                        >
                                            <span class="sr-only">{{ t('users.toggle_status') }}</span>
                                            <span
                                                aria-hidden="true"
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="[user.is_active ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0']"
                                            />
                                        </button>
                                        <span v-else class="text-xs" :class="user.is_active ? 'text-emerald-600' : 'text-red-600'">
                                            {{ user.is_active ? t('users.active', 'Active') : t('users.inactive', 'Inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <button 
                                                v-if="can('users.update')"
                                                @click="openEditModal(user)"
                                                class="p-2 text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/30 rounded-lg transition-colors"
                                                :title="t('common.edit')"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button 
                                                v-if="can('users.delete') && user.id !== $page.props.auth.user.id"
                                                @click="deleteUser(user)"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                                :title="t('common.delete')"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <p>{{ t('common.no_results', 'No results found') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="users.links.length > 3" class="px-6 py-4 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <!-- Mobile pagination buttons could go here -->
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ t('common.showing', 'Showing') }}
                                    <span class="font-medium">{{ users.from }}</span>
                                    {{ t('common.to', 'to') }}
                                    <span class="font-medium">{{ users.to }}</span>
                                    {{ t('common.of', 'of') }}
                                    <span class="font-medium">{{ users.total }}</span>
                                    {{ t('common.results', 'results') }}
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <!-- Use a proper Pagination component in real app, simplified here -->
                                    <template v-for="(link, i) in users.links" :key="i">
                                        <component
                                            :is="link.url ? 'Link' : 'span'"
                                            v-if="link.url || link.label === '...'"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                            :class="[
                                                link.active 
                                                    ? 'z-10 bg-violet-50 border-violet-500 text-violet-600' 
                                                    : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700',
                                                i === 0 ? 'rounded-l-md' : '',
                                                i === users.links.length - 1 ? 'rounded-r-md' : '',
                                                !link.url ? 'cursor-default' : ''
                                            ]"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <UserFormModal
            :show="showModal"
            :user="selectedUser"
            :centers="centers"
            :roles="roles"
            :employees="employees"
            @close="showModal = false"
        />
    </AppLayout>
</template>
