<template>
    <SystemLayout>
        <Head :title="$t('system.admin_users.title')" />

        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('system.admin_users.title')"
                :subtitle="$t('system.admin_users.subtitle')"
                :totalCount="admins?.total != null ? toEnglish(admins.total) : null"
                :countLabel="$t('system.admin_users.title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div
                        class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner"
                    >
                        <Link
                            :href="route('system.activity-log')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm"
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span>{{ $t('system.admin_users.activity_log') }}</span>
                        </Link>
                    </div>

                    <button
                        v-if="isSuperAdmin"
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-indigo-500/25 active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.admin_users.create') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div class="flex gap-4">
                <div class="relative w-64">
                    <svg
                        class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
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
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('system.admin_users.search_placeholder')"
                        @keyup.enter="applyFilters"
                        class="w-full px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
                <SearchableSelect
                    v-model="role"
                    :options="roleOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('system.admin_users.all_roles')"
                    compact
                />
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <DataTable structured class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.admin_users.col.user') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ $t('system.admin_users.col.role') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ $t('system.tenants.status') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle"
                            >
                                {{ $t('system.admin_users.col.last_login') }}
                            </th>
                            <th
                                class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider align-middle text-center"
                            >
                                {{ $t('system.backups.col.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="admin in admins.data"
                            :key="admin.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-300 font-bold"
                                    >
                                        {{ admin.name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ admin.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ admin.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                <span
                                    :class="getRoleClass(admin.role)"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                                    {{ getRoleLabel(admin.role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                <span
                                    :class="
                                        admin.is_active
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    "
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                                    {{
                                        $t(
                                            admin.is_active
                                                ? 'system.status.active'
                                                : 'system.admin_users.status.inactive'
                                        )
                                    }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 align-middle"
                            >
                                {{
                                    admin.last_login_at
                                        ? formatDate(admin.last_login_at)
                                        : $t('system.admin_users.never_logged_in')
                                }}
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        v-if="isSuperAdmin"
                                        @click="openEditModal(admin)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
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
                                        {{ $t('system.actions.edit') }}
                                    </button>
                                    <button
                                        v-if="isSuperAdmin"
                                        @click="deleteAdmin(admin)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
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
                                        {{ $t('system.actions.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!admins.data?.length">
                            <td
                                colspan="5"
                                class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('system.admin_users.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="closeModal"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-2xl shadow-xl max-h-[90vh] overflow-y-auto"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{
                                    editingAdmin
                                        ? $t('system.admin_users.edit_title')
                                        : $t('system.admin_users.create_title')
                                }}
                            </h3>
                            <button
                                @click="closeModal"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                            >
                                <svg
                                    class="w-6 h-6"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.admin_users.form.name') }} *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.admin_users.form.email') }} *
                                    </label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        dir="ltr"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">
                                        {{ form.errors.email }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.admin_users.form.phone') }}
                                    </label>
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        dir="ltr"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.admin_users.form.password') }}
                                        {{
                                            editingAdmin
                                                ? $t('system.admin_users.form.password_keep')
                                                : ' *'
                                        }}
                                    </label>
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        :required="!editingAdmin"
                                        dir="ltr"
                                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Role -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    {{ $t('system.admin_users.form.role') }} *
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <SelectableCard
                                        type="radio"
                                        name="form.role"
                                        v-model="form.role"
                                        :value="key"
                                        v-for="(label, key) in roleLabels"
                                        :key="key"
                                        :class="[
                                            'border rounded-lg p-3 cursor-pointer transition',
                                            form.role === key
                                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                                : 'border-gray-200 dark:border-gray-700',
                                        ]"
                                    >
                                        <p
                                            class="font-medium text-gray-900 dark:text-white text-sm"
                                        >
                                            {{ label }}
                                        </p>
                                    </SelectableCard>
                                </div>
                            </div>

                            <!-- Permissions (if not super_admin) -->
                            <div v-if="form.role !== 'super_admin'">
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    {{ $t('system.admin_users.form.permissions') }}
                                </label>
                                <!-- The list is an OVERRIDE: leaving it empty
                                     keeps the role's own abilities, and
                                     selecting any replaces them. The hint
                                     says so, because the difference is
                                     invisible otherwise. -->
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                    {{ $t('system.admin_users.form.permissions_hint') }}
                                </p>
                                <div class="space-y-4">
                                    <div
                                        v-for="(perms, group) in permissions"
                                        :key="group"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                                    >
                                        <p class="font-medium text-gray-900 dark:text-white mb-2">
                                            {{ groupLabels[group] }}
                                        </p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <label
                                                v-for="(label, key) in perms"
                                                :key="key"
                                                class="flex items-center gap-2 cursor-pointer"
                                            >
                                                <AppToggle
                                                    :value="key"
                                                    v-model="form.permissions"
                                                />
                                                <span
                                                    class="text-sm text-gray-700 dark:text-gray-300"
                                                >
                                                    {{ label }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <AppToggle v-model="form.is_active" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $t('system.admin_users.form.is_active') }}
                                    </span>
                                </label>
                            </div>

                            <div
                                class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700"
                            >
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                                >
                                    {{
                                        form.processing
                                            ? $t('system.admin_users.form.saving')
                                            : editingAdmin
                                              ? $t('system.actions.save')
                                              : $t('system.admin_users.form.create_action')
                                    }}
                                </button>
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                >
                                    {{ $t('system.actions.cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import SelectableCard from '@/Components/Common/SelectableCard.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const roleOptions = computed(() => [
    { value: 'super_admin', label: t('system.admin_users.roles.super_admin') },
    { value: 'admin', label: t('system.admin_users.roles.admin') },
    { value: 'support', label: t('system.admin_users.roles.support') },
]);

const roleLabels = computed(() => ({
    super_admin: t('system.admin_users.roles.super_admin'),
    admin: t('system.admin_users.roles.admin'),
    support: t('system.admin_users.roles.support'),
}));

// The groups come straight from the server-side permission registry
// (App\Support\SystemPermissions), so a new group shows up here as
// soon as it is registered — no front-end change needed.
const groupLabels = computed(() => ({
    tenants: t('system.admin_users.permission_groups.tenants'),
    billing: t('system.admin_users.permission_groups.billing'),
    settings: t('system.admin_users.permission_groups.settings'),
    support: t('system.admin_users.permission_groups.support'),
    credits: t('system.admin_users.permission_groups.credits'),
    panel: t('system.admin_users.permission_groups.panel'),
    self: t('system.admin_users.permission_groups.self'),
}));

const props = defineProps({
    admins: Object,
    filters: Object,
    permissions: Object,
});

// Creating, editing and deleting system-admin accounts is restricted
// to `super_admin` on the server (the `super_admin` route middleware
// plus the controller guard in AdminUsersController). The same flag is
// used here so a support admin is not offered buttons that would only
// answer 403.
//
// `usePage()` is used rather than the `$page` template global: inside a
// `<script setup>` body `$page` is not in scope, and referencing it
// there is a runtime ReferenceError.
const page = usePage();
const isSuperAdmin = computed(() => !!page.props.auth?.is_super_admin);

const search = ref(props.filters?.search || '');
const role = ref(props.filters?.role || '');
const showModal = ref(false);
const editingAdmin = ref(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: 'admin',
    permissions: [],
    is_active: true,
});

const openCreateModal = () => {
    editingAdmin.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (admin) => {
    editingAdmin.value = admin;
    form.name = admin.name;
    form.email = admin.email;
    form.phone = admin.phone || '';
    form.password = '';
    form.role = admin.role;
    form.permissions = admin.permissions || [];
    form.is_active = admin.is_active;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingAdmin.value = null;
};

const submitForm = () => {
    if (editingAdmin.value) {
        form.put(`/system/admin-users/${editingAdmin.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/system/admin-users', {
            onSuccess: () => closeModal(),
        });
    }
};

const applyFilters = () => {
    router.get(
        '/system/admin-users',
        {
            search: search.value || undefined,
            role: role.value || undefined,
        },
        { preserveState: true }
    );
};

const formatDate = (date) => {
    if (!date) return t('common.na');
    return new Date(date).toLocaleString('ar-SA-u-nu-latn');
};

const getRoleLabel = (role) =>
    ({
        super_admin: t('system.admin_users.roles.super_admin'),
        admin: t('system.admin_users.roles.admin'),
        support: t('system.admin_users.roles.support'),
    })[role] || role;

const getRoleClass = (role) =>
    ({
        super_admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
        admin: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        support: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    })[role] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';

const deleteAdmin = (admin) => {
    if (confirm(t('system.admin_users.confirm_delete', { name: admin.name }))) {
        router.delete(`/system/admin-users/${admin.id}`);
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
