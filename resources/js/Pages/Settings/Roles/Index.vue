<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import UserManagementTabs from '@/Pages/Settings/Partials/UserManagementTabs.vue';
import RoleFormModal from './RoleFormModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    roles: Array,
    groupedPermissions: Object,
    permissionDescriptions: Object,
});

const { t } = useI18n();
const { success, error } = useToast();

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingRole = ref(null);
const deletingRole = ref(null);

const breadcrumbs = [
    { label: t('nav.dashboard'), route: 'dashboard' },
    { label: t('settings.title'), route: 'settings.index' },
    { label: t('roles.title') },
];

const openCreateModal = () => {
    editingRole.value = null;
    showModal.value = true;
};

const openEditModal = (role) => {
    editingRole.value = role;
    showModal.value = true;
};

const confirmDelete = (role) => {
    deletingRole.value = role;
    showDeleteModal.value = true;
};

const deleteRole = () => {
    if (!deletingRole.value) return;

    router.delete(route('settings.roles.destroy', deletingRole.value.id), {
        onSuccess: () => {
            success(t('messages.deleted_success'));
            showDeleteModal.value = false;
            deletingRole.value = null;
        },
        onError: () => {
            error(t('common.error_occurred'));
        }
    });
};
</script>

<template>
    <Head :title="t('roles.title')" />

    <AppLayout>
        <div class="space-y-6">


            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-center gap-4">

                         <Link :href="route('settings.index')" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center shadow-lg shadow-pink-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('roles.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('roles.page_description') }}</p>
                        </div>
                    </div>
                    
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5 rtl:ml-2 rtl:mr-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        {{ t('roles.add') }}
                    </button>
                </div>
                
                <UserManagementTabs active-tab="roles" class="mt-6" />
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('roles.name') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('roles.users_count') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $page.props.locale === 'ar' ? (role.label_ar || role.name) : (role.label_en || role.name) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ role.description }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ role.users_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                <button
                                    @click="openEditModal(role)"
                                    class="text-violet-600 dark:text-violet-400 hover:text-violet-900 dark:hover:text-violet-300 mx-2"
                                >
                                    {{ t('common.edit') }}
                                </button>
                                <button
                                    @click="confirmDelete(role)"
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 mx-2"
                                    :disabled="role.users_count > 0"
                                    :class="{ 'opacity-50 cursor-not-allowed': role.users_count > 0 }"
                                >
                                    {{ t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="roles.length === 0">
                            <td colspan="3" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                {{ t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <RoleFormModal
                :show="showModal"
                :role="editingRole"
                :grouped-permissions="groupedPermissions"
                :permission-descriptions="permissionDescriptions"
                @close="showModal = false"
            />

            <ConfirmModal
                :show="showDeleteModal"
                title="roles.delete_confirm_title"
                message="roles.delete_confirm_message"
                @close="showDeleteModal = false"
                @confirm="deleteRole"
            />
        </div>
    </AppLayout>
</template>
