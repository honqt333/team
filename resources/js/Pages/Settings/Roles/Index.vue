<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
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


            <PageHeader
                :title="t('roles.title')"
                :subtitle="t('roles.page_description')"
                gradientFrom="from-pink-500"
                gradientTo="to-rose-600"
                glowFrom="from-pink-500"
                badgeBg="bg-pink-50/50 dark:bg-pink-900/30"
                badgeText="text-pink-600 dark:text-pink-400"
                badgeBorder="border-pink-100/50 dark:border-pink-800/30"
                badgeDot="bg-pink-500"
            >
                <template #back>
                    <Link :href="route('settings.index')"
                        class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center hover:bg-pink-50 dark:hover:bg-pink-900/20 text-pink-600 dark:text-pink-400 shadow-md transition-all border border-gray-100 dark:border-gray-700 group/back"
                        :title="$t('common.back')">
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover/back:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </template>

                <template #actions>
                    <button 
                        @click="openCreateModal"
                        class="group relative inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl transition-all hover:scale-[1.02] hover:shadow-lg hover:shadow-pink-500/25 active:scale-95 overflow-hidden"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="relative hidden sm:inline">{{ t('roles.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <UserManagementTabs active-tab="roles" />
                </template>
            </PageHeader>

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
