<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between sm:flex-wrap gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-600 to-emerald-600 flex items-center justify-center shadow-lg shadow-teal-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('services_management.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ totalServices }} {{ $t('services_management.total_count') }}
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
                                :placeholder="$t('services_management.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all"
                            />
                        </div>

                        <!-- Add Department Button -->
                        <button
                            @click="openCreateDepartmentModal"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-700 border border-orange-300 dark:border-orange-600 text-orange-600 dark:text-orange-400 rounded-xl font-medium hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>{{ $t('departments.add') }}</span>
                        </button>

                        <!-- Add Service Button -->
                        <button
                            @click="openCreateModal"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl hover:shadow-teal-500/40 hover:-translate-y-0.5 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>{{ $t('services_management.add') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="departments.length === 0 && unassignedServices.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('services_management.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('services_management.empty_hint') }}</p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 hover:shadow-xl transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('services_management.add') }}
                </button>
            </div>

            <!-- Accordion by Departments -->
            <div v-else class="space-y-4">
                <!-- Department Accordions -->
                <div
                    v-for="dept in filteredDepartments"
                    :key="dept.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <!-- Department Header -->
                    <div class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                        <!-- Clickable part for expand/collapse -->
                        <button
                            @click="toggleDepartment(dept.id)"
                            class="flex items-center gap-3 flex-1"
                        >
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-white font-bold">
                                {{ getName(dept).charAt(0).toUpperCase() }}
                            </div>
                            <div class="text-start">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ getName(dept) }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ dept.services?.length || 0 }} {{ $t('services_management.services') }}</p>
                            </div>
                            <span v-if="!dept.is_active" class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                {{ $t('common.inactive') }}
                            </span>
                        </button>
                        
                        <!-- Department Actions -->
                        <div class="flex items-center gap-2 me-3">
                            <button
                                @click.stop="openEditDepartmentModal(dept)"
                                class="p-1.5 text-gray-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors"
                                :title="$t('departments.edit')"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button
                                @click.stop="handleDeleteDepartment(dept)"
                                class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                :title="$t('common.delete')"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Expand Icon -->
                        <button @click="toggleDepartment(dept.id)">
                            <svg
                                :class="['w-5 h-5 text-gray-400 transition-transform', expandedDepts.has(dept.id) ? 'rotate-180' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Services List -->
                    <div v-if="expandedDepts.has(dept.id)" class="border-t border-gray-200 dark:border-gray-700">
                        <div v-if="dept.services?.length === 0" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                            {{ $t('services_management.no_services') }}
                        </div>
                        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div
                                v-for="service in dept.services"
                                :key="service.id"
                                class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="service.type === 'internal' 
                                            ? 'bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400'
                                            : 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400'"
                                    >
                                        <svg v-if="service.type === 'internal'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ getName(service) }}</span>
                                            <span v-if="service.type === 'external'" class="px-1.5 py-0.5 text-[10px] font-semibold rounded bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                                {{ $t('services_management.coming_soon') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                            <span class="font-semibold text-teal-600 dark:text-teal-400">{{ service.base_price }} ر.س</span>
                                            <span v-if="service.duration_value" class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ service.duration_value }} {{ $t(`services_management.duration_units.${service.duration_unit}`) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click.stop="toggleServiceActive(service)"
                                        :class="service.is_active 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' 
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                        class="px-2 py-0.5 text-xs font-medium rounded-full transition-all hover:opacity-80"
                                    >
                                        {{ service.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </button>
                                    <button
                                        @click.stop="openEditModal(service)"
                                        class="p-1.5 text-gray-500 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded-lg transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click.stop="handleDelete(service)"
                                        class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unassigned Services -->
                <div v-if="unassignedServices.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button
                        @click="toggleDepartment('unassigned')"
                        class="w-full px-5 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </div>
                            <div class="text-start">
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $t('services_management.unassigned') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ unassignedServices.length }} {{ $t('services_management.services') }}</p>
                            </div>
                        </div>
                        <svg
                            :class="['w-5 h-5 text-gray-400 transition-transform', expandedDepts.has('unassigned') ? 'rotate-180' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div v-if="expandedDepts.has('unassigned')" class="border-t border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="service in unassignedServices"
                            :key="service.id"
                            class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ getName(service) }}</span>
                                    <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold text-teal-600 dark:text-teal-400">{{ service.base_price }} ر.س</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click.stop="toggleServiceActive(service)"
                                    :class="service.is_active 
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' 
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'"
                                    class="px-2 py-0.5 text-xs font-medium rounded-full transition-all hover:opacity-80"
                                >
                                    {{ service.is_active ? $t('common.active') : $t('common.inactive') }}
                                </button>
                                <button @click.stop="openEditModal(service)" class="p-1.5 text-gray-500 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button @click.stop="handleDelete(service)" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Form Modal -->
        <ServiceFormModal
            :show="showModal"
            :service="selectedService"
            :departments="departments"
            @close="closeModal"
            @saved="handleSaved"
        />

        <!-- Department Form Modal -->
        <DepartmentFormModal
            :show="showDepartmentModal"
            :department="selectedDepartment"
            @close="closeDepartmentModal"
            @saved="handleDepartmentSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import ServiceFormModal from '@/Components/ServiceFormModal.vue';
import DepartmentFormModal from '@/Components/DepartmentFormModal.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useLocalized } from '@/Composables/useLocalized';

const { t } = useI18n();
const { confirm } = useConfirm();
const { success } = useToast();
const { getName } = useLocalized();

const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
    unassignedServices: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const selectedService = ref(null);
const searchQuery = ref('');
const expandedDepts = ref(new Set());

// Department modal state
const showDepartmentModal = ref(false);
const selectedDepartment = ref(null);

const totalServices = computed(() => {
    let count = props.unassignedServices?.length || 0;
    props.departments.forEach(dept => {
        count += dept.services?.length || 0;
    });
    return count;
});

const filteredDepartments = computed(() => {
    if (!searchQuery.value) return props.departments;
    
    const query = searchQuery.value.toLowerCase();
    return props.departments.filter(dept => 
        dept.name_ar?.toLowerCase().includes(query) ||
        dept.name_en?.toLowerCase().includes(query) ||
        dept.services?.some(s => 
            s.name_ar?.toLowerCase().includes(query) ||
            s.name_en?.toLowerCase().includes(query)
        )
    );
});

function toggleDepartment(id) {
    if (expandedDepts.value.has(id)) {
        expandedDepts.value.delete(id);
    } else {
        expandedDepts.value.add(id);
    }
}

function openCreateModal() {
    selectedService.value = null;
    showModal.value = true;
}

function openEditModal(service) {
    selectedService.value = service;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedService.value = null;
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments', 'unassignedServices'] });
}

function toggleServiceActive(service) {
    router.patch(`/app/services/${service.id}/toggle-active`, {}, {
        preserveScroll: true,
    });
}

async function handleDelete(service) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(service)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    
    if (!confirmed) return;
    
    router.delete(`/app/services/${service.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
        onError: (errors) => {
            console.error('Failed to delete:', errors);
        },
    });
}

// Department handlers
function openCreateDepartmentModal() {
    selectedDepartment.value = null;
    showDepartmentModal.value = true;
}

function openEditDepartmentModal(department) {
    selectedDepartment.value = department;
    showDepartmentModal.value = true;
}

function closeDepartmentModal() {
    showDepartmentModal.value = false;
    selectedDepartment.value = null;
}

function handleDepartmentSaved() {
    closeDepartmentModal();
    success(t('common.saved_success'));
    router.reload({ only: ['departments', 'unassignedServices'] });
}

async function handleDeleteDepartment(department) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(department)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    
    if (!confirmed) return;
    
    router.delete(`/app/departments/${department.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
        onError: (errors) => {
            if (errors.delete) {
                // Show error for cannot delete department with services
                confirm({
                    title: t('common.error'),
                    message: errors.delete,
                    confirmText: t('nav.close'),
                    type: 'danger',
                });
            }
        },
    });
}
</script>
