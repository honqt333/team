<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-600 to-amber-600 flex items-center justify-center shadow-lg shadow-orange-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('departments.title') }}</h1>
                            <p v-if="departments" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ departments.total }} {{ $t('departments.total_count') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('departments.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                            />
                        </div>

                        <!-- Add Button -->
                        <button
                            v-if="can('departments.create')"
                            @click="openCreateModal"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-xl font-medium shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/40 hover:-translate-y-0.5 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>{{ $t('departments.add') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!departments" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-orange-200 dark:border-orange-900 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-12 h-12 border-4 border-orange-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredDepartments.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('departments.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('departments.empty_hint') }}</p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-xl font-medium shadow-lg shadow-orange-500/30 hover:shadow-xl transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('departments.add') }}
                </button>
            </div>

            <!-- Table View -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('departments.columns.name') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('departments.columns.description') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('departments.columns.services_count') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('departments.columns.status') }}</th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="dept in filteredDepartments"
                                :key="dept.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold text-white flex-shrink-0 bg-gradient-to-br from-orange-500 to-amber-500">
                                            {{ getName(dept).charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ getName(dept) }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                    {{ dept.description || '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ dept.services_count }} {{ $t('departments.services') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <button
                                        @click="toggleActive(dept)"
                                        :disabled="togglingId === dept.id"
                                        :class="dept.is_active 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' 
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                        class="px-3 py-1 text-xs font-medium rounded-full transition-all hover:opacity-80 disabled:opacity-50"
                                    >
                                        {{ dept.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </button>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="can('departments.edit')"
                                            @click="openEditModal(dept)"
                                            class="p-2 text-gray-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button
                                            v-if="can('departments.delete')"
                                            @click="handleDelete(dept)"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="departments && departments.data.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t('customers.showing') }} 
                <span class="font-medium text-gray-900 dark:text-white">{{ departments.from }}</span>-<span class="font-medium text-gray-900 dark:text-white">{{ departments.to }}</span>
                {{ $t('customers.of') }}
                <span class="font-medium text-gray-900 dark:text-white">{{ departments.total }}</span>
            </div>
        </div>

        <!-- Department Form Modal -->
        <DepartmentFormModal
            :show="showModal"
            :department="selectedDepartment"
            @close="closeModal"
            @saved="handleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import DepartmentFormModal from '@/Components/DepartmentFormModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useLocalized } from '@/Composables/useLocalized';
import { usePermission } from '@/Composables/usePermission';

const { t } = useI18n();
const { confirm } = useConfirm();
const { success } = useToast();
const { getName } = useLocalized();
const { can } = usePermission();

const props = defineProps({
    departments: {
        type: Object,
        default: null,
    },
});

const showModal = ref(false);
const selectedDepartment = ref(null);
const searchQuery = ref('');
const togglingId = ref(null);

const filteredDepartments = computed(() => {
    const list = props.departments?.data || [];
    if (!searchQuery.value) return list;
    
    const query = searchQuery.value.toLowerCase();
    return list.filter(dept => 
        dept.name_ar?.toLowerCase().includes(query) ||
        dept.name_en?.toLowerCase().includes(query) ||
        dept.description?.toLowerCase().includes(query)
    );
});

function openCreateModal() {
    selectedDepartment.value = null;
    showModal.value = true;
}

function openEditModal(dept) {
    selectedDepartment.value = dept;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedDepartment.value = null;
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments'] });
}

async function toggleActive(dept) {
    togglingId.value = dept.id;
    router.patch(`/app/departments/${dept.id}/toggle-active`, {}, {
        preserveScroll: true,
        onFinish: () => {
            togglingId.value = null;
        },
    });
}

async function handleDelete(dept) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(dept)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    
    if (!confirmed) return;
    
    router.delete(`/app/departments/${dept.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
        onError: (errors) => {
            console.error('Failed to delete:', errors);
        },
    });
}
</script>
