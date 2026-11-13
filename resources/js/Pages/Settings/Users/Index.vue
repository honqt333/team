<script setup>
import { computed, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppTableSkeleton from '@/Components/Common/AppTableSkeleton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import UserFormModal from './UserFormModal.vue';
import UserManagementTabs from '@/Pages/Settings/Partials/UserManagementTabs.vue';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';
import { useConfirm } from '@/Composables/useConfirm';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    users: Object,
    filters: Object,
    centers: Array,
    roles: Array,
    userLimit: Object,
});

const { t, locale } = useI18n();
const { can } = usePermission();
const page = usePage();
const { confirm } = useConfirm();

// ─── Server Table ────────────────────────────────────────────────────────────
const { tableFilters, isLoading } = useServerTable({
    routeUrl: () => route('settings.users.index'),
    initialFilters: {
        search: props.filters?.search || '',
        role_id: props.filters?.role_id ?? null,
        center_id: props.filters?.center_id ?? null,
        status: props.filters?.status || '',
    },
    only: ['users', 'filters', 'userLimit'],
    debounceMs: 350,
});

watch(
    () => props.filters,
    (val) => {
        if (!val) return;
        if (val.search !== undefined && val.search !== tableFilters.search) {
            tableFilters.search = val.search;
        }
        if (val.role_id !== undefined && val.role_id !== tableFilters.role_id) {
            tableFilters.role_id = val.role_id;
        }
        if (val.center_id !== undefined && val.center_id !== tableFilters.center_id) {
            tableFilters.center_id = val.center_id;
        }
        if (val.status !== undefined && val.status !== tableFilters.status) {
            tableFilters.status = val.status;
        }
    }
);

const roleFilterOptions = computed(() => [
    { value: null, label: t('users.all_roles', 'جميع الأدوار') },
    ...(props.roles || []).map((role) => ({
        value: role.id,
        label: locale.value === 'ar' ? role.label_ar || role.name : role.label_en || role.name,
    })),
]);

const centerFilterOptions = computed(() => [
    { value: null, label: t('users.all_branches', 'جميع الفروع') },
    ...(props.centers || []).map((c) => ({
        value: c.id,
        label: c.name,
    })),
]);

const statusFilterOptions = computed(() => [
    { value: '', label: t('users.all_statuses', 'جميع الحالات') },
    { value: 'active', label: t('users.active', 'نشط') },
    { value: 'inactive', label: t('users.inactive', 'غير نشط') },
]);

const isPlanLimitReached = computed(
    () =>
        props.userLimit?.max_users != null &&
        props.userLimit.active_count >= props.userLimit.max_users
);

// ─── Column Definitions ───────────────────────────────────────────────────────
const columns = computed(() => [
    { key: 'name', label: t('users.name', 'الاسم'), sortable: false },
    { key: 'email', label: t('users.email', 'البريد الإلكتروني'), sortable: false },
    { key: 'centers', label: t('users.branches', 'الفروع'), sortable: false },
    { key: 'role', label: t('users.role', 'الدور'), sortable: false },
    { key: 'employee', label: t('users.employee', 'الموظف المرتبط'), sortable: false },
    { key: 'status', label: t('users.status', 'الحالة'), sortable: false },
    { key: 'actions', label: t('common.actions', 'الإجراءات'), sortable: false },
]);

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getRoleLabel = (user) => {
    if (!user.roles || user.roles.length === 0) return '-';
    const role = user.roles[0];
    return locale.value === 'ar' ? role.label_ar || role.name : role.label_en || role.name;
};

// ─── Modal: Create / Edit ─────────────────────────────────────────────────────
const showModal = ref(false);
const selectedUser = ref(null);

const openCreateModal = () => {
    selectedUser.value = null;
    showModal.value = true;
};

const openEditModal = (user) => {
    selectedUser.value = user;
    showModal.value = true;
};

// ─── Modal: Delete Confirmation ───────────────────────────────────────────────
const confirmDelete = async (user) => {
    const confirmed = await confirm({
        type: 'danger',
        title: t('common.confirm_delete_title', 'تأكيد الحذف'),
        message: t('common.confirm_delete', 'هل أنت متأكد من حذف هذا العنصر؟'),
        confirmText: t('common.delete', 'حذف'),
        cancelText: t('common.cancel', 'إلغاء'),
    });
    if (!confirmed) return;
    router.delete(route('settings.users.destroy', user.id), {
        preserveScroll: true,
    });
};

// ─── Toggle Active ────────────────────────────────────────────────────────────
const toggleUserActive = (user) => {
    router.patch(
        route('settings.users.toggle', user.id),
        {},
        {
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head :title="t('users.title', 'إدارة المستخدمين')" />

    <AppLayout>
        <div class="space-y-6">
            <!-- ─── Header ──────────────────────────────────────────────── -->
            <PageHeader
                :title="t('users.title')"
                :subtitle="t('users.description')"
                gradientFrom="from-action-primary"
                gradientTo="to-purple-700"
                glowFrom="from-action-primary"
                badgeBg="bg-selection-bg"
                badgeText="text-action-primary"
                badgeBorder="border-action-primary/25"
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
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <button
                        v-if="can('users.create')"
                        @click="openCreateModal"
                        class="group relative inline-flex items-center gap-2 px-6 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white font-bold rounded-xl transition-all hover:scale-[1.02] hover:shadow-lg hover:shadow-focus-ring-soft active:scale-95 overflow-hidden"
                    >
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"
                        ></div>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span class="relative hidden sm:inline">{{ t('users.create_new') }}</span>
                    </button>
                </template>

                <template #filters>
                    <UserManagementTabs active-tab="users" />
                </template>
            </PageHeader>

            <!-- ─── Search & Filters Bar ─────────────────────────────────── -->
            <div class="bg-surface rounded-xl shadow-sm border border-border p-4 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-center">
                    <div class="relative">
                        <AppInput
                            v-model="tableFilters.search"
                            type="text"
                            :placeholder="
                                t('users.search_placeholder', 'ابحث بالاسم، البريد، أو الموظف...')
                            "
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

                    <SearchableSelect
                        v-model="tableFilters.role_id"
                        :options="roleFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('users.all_roles', 'جميع الأدوار')"
                    />

                    <SearchableSelect
                        v-model="tableFilters.center_id"
                        :options="centerFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('users.all_branches', 'جميع الفروع')"
                    />

                    <SearchableSelect
                        v-model="tableFilters.status"
                        :options="statusFilterOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('users.all_statuses', 'جميع الحالات')"
                    />
                </div>

                <div
                    v-if="userLimit?.max_users"
                    class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-border text-xs"
                >
                    <div class="flex items-center gap-2 text-text-secondary">
                        <span>{{ t('users.plan_usage', 'المستخدمون النشطون') }}:</span>
                        <span
                            class="px-2 py-0.5 rounded-full font-bold border"
                            :class="
                                isPlanLimitReached
                                    ? 'bg-danger/10 text-danger border-danger/30'
                                    : 'bg-selection-bg text-action-primary border-selection-ring/30'
                            "
                        >
                            {{ userLimit.active_count }} / {{ userLimit.max_users }}
                        </span>
                    </div>
                    <span v-if="isPlanLimitReached" class="text-danger font-semibold">
                        {{ t('users.plan_limit_reached_badge', 'تم بلوغ الحد الأقصى للباقة') }}
                    </span>
                </div>
            </div>

            <!-- ─── Table ───────────────────────────────────────────────── -->
            <DataTable :columns="columns" :data="users" :loading="isLoading">
                <!-- Name cell -->
                <template #cell-name="{ item }">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-full bg-selection-bg flex items-center justify-center text-action-primary font-bold text-xs uppercase shrink-0"
                        >
                            {{ (item.name || '?').charAt(0) }}
                        </div>
                        <span class="font-medium text-text-primary">{{ item.name }}</span>
                    </div>
                </template>

                <!-- Email cell -->
                <template #cell-email="{ item }">
                    <span class="text-text-secondary text-sm">{{ item.email }}</span>
                </template>

                <!-- Centers cell -->
                <template #cell-centers="{ item }">
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="center in item.centers"
                            :key="center.id"
                            class="px-2 py-0.5 rounded text-xs font-medium bg-surface-muted text-text-secondary border border-border"
                        >
                            {{ center.name }}
                        </span>
                        <span
                            v-if="!item.centers || item.centers.length === 0"
                            class="text-text-muted text-xs italic"
                        >
                            -
                        </span>
                    </div>
                </template>

                <!-- Role cell -->
                <template #cell-role="{ item }">
                    <span
                        v-if="item.roles && item.roles.length > 0"
                        class="px-2 py-0.5 rounded text-xs font-medium bg-selection-bg text-action-primary border border-selection-ring/30"
                    >
                        {{ getRoleLabel(item) }}
                    </span>
                    <span v-else class="text-text-muted text-xs italic">-</span>
                </template>

                <!-- Employee cell -->
                <template #cell-employee="{ item }">
                    <span
                        v-if="item.employee"
                        class="text-success text-xs font-medium px-2 py-0.5 rounded bg-success/10"
                    >
                        {{ item.employee.name_ar || item.employee.name_en }}
                    </span>
                    <span v-else class="text-text-muted text-xs italic">
                        {{ t('users.no_employee', '-') }}
                    </span>
                </template>

                <!-- Status cell -->
                <template #cell-status="{ item }">
                    <div class="flex justify-center">
                        <AppToggle
                            v-if="can('users.update') && item.id !== $page.props.auth.user.id"
                            :model-value="item.is_active"
                            color="success"
                            :title="
                                item.is_active
                                    ? t('users.active', 'نشط')
                                    : t('users.inactive', 'غير نشط')
                            "
                            @change="toggleUserActive(item)"
                        />
                        <span
                            v-else
                            class="text-xs font-medium"
                            :class="item.is_active ? 'text-success' : 'text-danger'"
                        >
                            {{
                                item.is_active
                                    ? t('users.active', 'نشط')
                                    : t('users.inactive', 'غير نشط')
                            }}
                        </span>
                    </div>
                </template>

                <!-- Actions cell -->
                <template #cell-actions="{ item }">
                    <div class="flex items-center justify-center gap-1">
                        <button
                            v-if="can('users.update')"
                            @click="openEditModal(item)"
                            class="p-1.5 rounded-lg text-text-muted hover:text-action-primary hover:bg-selection-bg transition-colors"
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
                            v-if="can('users.delete') && item.id !== $page.props.auth.user.id"
                            @click="confirmDelete(item)"
                            class="p-1.5 rounded-lg text-text-muted hover:text-danger hover:bg-danger/10 transition-colors"
                            :title="t('common.delete', 'حذف')"
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
                                ? t('common.no_results_search', 'لا توجد مستخدمين تطابق بحثك')
                                : t('users.empty_description', 'لم يتم إضافة أي مستخدمين بعد')
                        "
                        :action-text="can('users.create') ? t('users.create_new') : ''"
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
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </template>
                    </AppEmptyState>
                </template>
            </DataTable>
        </div>

        <!-- ─── Modals ─────────────────────────────────────────────────── -->
        <UserFormModal
            :show="showModal"
            :user="selectedUser"
            :centers="centers"
            :roles="roles"
            @close="showModal = false"
        />
    </AppLayout>
</template>
