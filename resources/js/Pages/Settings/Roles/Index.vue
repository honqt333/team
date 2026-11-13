<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import UserManagementTabs from '@/Pages/Settings/Partials/UserManagementTabs.vue';
import RoleFormModal from './RoleFormModal.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTableSkeleton from '@/Components/Common/AppTableSkeleton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';

const props = defineProps({
    roles: [Array, Object],
    filters: Object,
    groupedPermissions: Object,
    permissionDescriptions: Object,
});

const { t, locale } = useI18n();
const { can } = usePermission();
const { confirm } = useConfirm();
const page = usePage();

// ─── Server Table & Filters ──────────────────────────────────────────────────
const { tableFilters, isLoading } = useServerTable({
    routeUrl: () => route('settings.roles.index'),
    initialFilters: {
        search: props.filters?.search || '',
    },
    only: ['roles', 'filters'],
    debounceMs: 350,
});

watch(
    () => props.filters?.search,
    (val) => {
        if (val !== undefined && val !== tableFilters.search) {
            tableFilters.search = val;
        }
    }
);

// ─── Columns ─────────────────────────────────────────────────────────────────
const columns = computed(() => [
    { key: 'name', label: t('roles.name', 'الدور'), sortable: false },
    {
        key: 'permissions_count',
        label: t('roles.permissions_count', 'عدد الصلاحيات'),
        sortable: false,
        align: 'center',
    },
    {
        key: 'users_count',
        label: t('roles.users_count', 'عدد المستخدمين'),
        sortable: false,
        align: 'center',
    },
    { key: 'actions', label: t('common.actions', 'الإجراءات'), sortable: false, align: 'end' },
]);

// ─── Modal State ─────────────────────────────────────────────────────────────
const showModal = ref(false);
const editingRole = ref(null);
const duplicateRoleSource = ref(null);

const openCreateModal = () => {
    editingRole.value = null;
    duplicateRoleSource.value = null;
    showModal.value = true;
};

const openEditModal = (role) => {
    editingRole.value = role;
    duplicateRoleSource.value = null;
    showModal.value = true;
};

const openDuplicateModal = (role) => {
    editingRole.value = null;
    duplicateRoleSource.value = role;
    showModal.value = true;
};

// ─── Delete Role Confirmation ────────────────────────────────────────────────
const deleteRole = async (role) => {
    if (role.users_count > 0 || role.name === 'super_admin') return;

    const confirmed = await confirm({
        type: 'danger',
        title: t('roles.delete_confirm_title', 'تأكيد حذف الدور'),
        message: t(
            'roles.delete_confirm_message',
            'هل أنت متأكد من حذف هذا الدور؟ لا يمكن التراجع عن هذا الإجراء.'
        ),
        confirmText: t('common.delete', 'حذف'),
        cancelText: t('common.cancel', 'إلغاء'),
    });

    if (!confirmed) return;

    router.delete(route('settings.roles.destroy', role.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="t('roles.title', 'إدارة الأدوار')" />

    <AppLayout>
        <div class="space-y-6">
            <!-- ─── Header ──────────────────────────────────────────────── -->
            <PageHeader
                :title="t('roles.title')"
                :subtitle="t('roles.page_description')"
                gradientFrom="from-rose-600"
                gradientTo="to-pink-600"
                glowFrom="from-rose-500"
                badgeBg="bg-selection-bg"
                badgeText="text-action-primary"
                badgeBorder="border-selection-ring/30"
                badgeDot="bg-action-primary"
            >
                <template #back>
                    <BackButton :href="route('settings.index')" />
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <button
                        v-if="can('users.create')"
                        type="button"
                        @click="openCreateModal"
                        class="group relative inline-flex items-center gap-2 px-6 py-2.5 bg-action-primary hover:bg-action-primary-hover text-action-on font-bold rounded-xl transition-all hover:scale-[1.02] shadow-xs hover:shadow-md active:scale-95 cursor-pointer"
                    >
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                fill-rule="evenodd"
                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span class="relative hidden sm:inline">
                            {{ t('roles.add', 'إضافة دور') }}
                        </span>
                    </button>
                </template>

                <template #filters>
                    <UserManagementTabs active-tab="roles" />
                </template>
            </PageHeader>

            <!-- ─── Search Bar ─────────────────────────────────────────── -->
            <div class="bg-surface rounded-xl shadow-xs border border-border p-4">
                <div class="relative max-w-md">
                    <AppInput
                        v-model="tableFilters.search"
                        type="text"
                        :placeholder="t('common.search', 'بحث...')"
                        class="ps-10"
                    />
                    <svg
                        class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted pointer-events-none"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </div>
            </div>

            <!-- ─── Table ───────────────────────────────────────────────── -->
            <DataTable :columns="columns" :data="roles" :loading="isLoading">
                <!-- Role Name cell -->
                <template #cell-name="{ item }">
                    <div class="py-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-text-primary text-sm sm:text-base">
                                {{
                                    locale === 'ar'
                                        ? item.label_ar || item.name
                                        : item.label_en || item.name
                                }}
                            </span>
                            <code
                                class="text-xs px-2 py-0.5 rounded-md bg-surface-muted text-text-muted font-mono border border-border"
                            >
                                {{ item.name }}
                            </code>
                        </div>
                        <p
                            v-if="item.description"
                            class="text-xs text-text-secondary mt-1 max-w-xl line-clamp-2"
                        >
                            {{ item.description }}
                        </p>
                    </div>
                </template>

                <!-- Permissions Count cell -->
                <template #cell-permissions_count="{ item }">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-surface-muted text-text-secondary border border-border"
                    >
                        {{ item.permissions_count ?? item.permissions?.length ?? 0 }}
                    </span>
                </template>

                <!-- Users Count cell -->
                <template #cell-users_count="{ item }">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-selection-bg text-action-primary border border-selection-ring/30"
                    >
                        {{ item.users_count ?? 0 }}
                    </span>
                </template>

                <!-- Actions cell -->
                <template #cell-actions="{ item }">
                    <div class="flex items-center justify-end gap-1">
                        <button
                            v-if="can('users.create')"
                            type="button"
                            @click="openDuplicateModal(item)"
                            class="p-1.5 rounded-lg text-text-muted hover:text-action-primary hover:bg-selection-bg transition-colors cursor-pointer"
                            :title="t('roles.duplicate', 'نسخ الدور')"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                        </button>
                        <button
                            v-if="can('users.update') && item.name !== 'super_admin'"
                            type="button"
                            @click="openEditModal(item)"
                            class="p-1.5 rounded-lg text-text-muted hover:text-action-primary hover:bg-selection-bg transition-colors cursor-pointer"
                            :title="t('common.edit', 'تعديل')"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </button>
                        <button
                            v-if="can('users.delete')"
                            type="button"
                            @click="deleteRole(item)"
                            :disabled="item.users_count > 0 || item.name === 'super_admin'"
                            class="p-1.5 rounded-lg text-text-muted transition-colors"
                            :class="[
                                item.users_count > 0 || item.name === 'super_admin'
                                    ? 'opacity-30 cursor-not-allowed'
                                    : 'hover:text-danger hover:bg-danger/10 cursor-pointer',
                            ]"
                            :title="
                                item.users_count > 0
                                    ? t(
                                          'roles.cannot_delete_has_users',
                                          'لا يمكن حذف دور مرتبط بمستخدمين'
                                      )
                                    : t('common.delete', 'حذف')
                            "
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template #empty>
                    <AppEmptyState
                        :title="t('common.no_results', 'لا توجد نتائج')"
                        :description="
                            tableFilters.search
                                ? t(
                                      'common.no_results_search',
                                      'لم يتم العثور على أي أدوار مطابقة للبحث'
                                  )
                                : t('roles.empty_description', 'لم يتم إضافة أي أدوار بعد')
                        "
                        :action-text="can('users.create') ? t('roles.add', 'إضافة دور جديد') : ''"
                        @action="openCreateModal"
                    >
                        <template #icon>
                            <svg
                                class="w-7 h-7"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.75"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </template>
                    </AppEmptyState>
                </template>
            </DataTable>
        </div>

        <!-- ─── Role Form Modal ─────────────────────────────────────────── -->
        <RoleFormModal
            :show="showModal"
            :role="editingRole"
            :duplicate-from="duplicateRoleSource"
            :grouped-permissions="groupedPermissions"
            :permission-descriptions="permissionDescriptions"
            @close="showModal = false"
        />
    </AppLayout>
</template>
