<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('app.hr.index')" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('hr.employees.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.employees.subtitle') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- View Toggle -->
                        <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'p-2 rounded-md transition-all',
                                    viewMode === 'grid'
                                        ? 'bg-white dark:bg-gray-600 text-violet-600 dark:text-violet-400 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'p-2 rounded-md transition-all',
                                    viewMode === 'list'
                                        ? 'bg-white dark:bg-gray-600 text-violet-600 dark:text-violet-400 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>
                        
                        <button
                            @click="showAddModal = true"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $t('hr.employees.add') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters & Content -->
            <div class="space-y-6">
                <!-- Tabs & Search -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex flex-col md:flex-row justify-between items-center border-b border-gray-200 dark:border-gray-700">
                        <!-- Tabs -->
                        <div class="w-full md:w-auto px-4">
                            <nav class="flex gap-4">
                                <button
                                    @click="changeStatus('active')"
                                    :class="[
                                        'py-4 px-2 text-sm font-medium border-b-2 transition-colors',
                                        currentStatus === 'active'
                                            ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                                    ]"
                                >
                                    {{ $t('common.active') }}
                                    <span class="ms-2 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        {{ counts.active }}
                                    </span>
                                </button>
                                <button
                                    @click="changeStatus('inactive')"
                                    :class="[
                                        'py-4 px-2 text-sm font-medium border-b-2 transition-colors',
                                        currentStatus !== 'active'
                                            ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                                    ]"
                                >
                                    {{ $t('common.inactive') }}
                                    <span class="ms-2 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        {{ counts.inactive }}
                                    </span>
                                </button>
                            </nav>
                        </div>
                        
                        <!-- Search -->
                        <div class="w-full md:w-auto p-3">
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    :placeholder="$t('common.search')"
                                    class="w-full md:w-64 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white"
                                    @keyup.enter="search"
                                />
                                <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div 
                        v-for="employee in employees.data" 
                        :key="employee.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow group relative overflow-hidden"
                    >
                        <div class="absolute top-0 end-0 p-4">
                            <span :class="[
                                'px-2 py-1 rounded-lg text-xs font-semibold',
                                employee.status === 'active' 
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                            ]">
                                {{ $t(`hr.employees.status.${employee.status}`) }}
                            </span>
                        </div>
                        
                        <div class="flex flex-col items-center text-center">
                            <div class="w-20 h-20 mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden border-2 border-white dark:border-gray-600 shadow-sm">
                                <img v-if="employee.photo_path" :src="`/storage/${employee.photo_path}`" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-violet-500 to-purple-600 text-white text-2xl font-bold">
                                    {{ employee.name_ar?.charAt(0) }}
                                </div>
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1 line-clamp-1">
                                {{ employee.name_ar }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-1">
                                {{ employee.job_title?.name_ar || $t('hr.employees.no_job_title') }}
                            </p>
                            
                            <div class="w-full pt-4 border-t border-gray-100 dark:border-gray-700 space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $t('common.department') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ employee.department?.name || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $t('common.phone') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ employee.phone || '-' }}</span>
                                </div>
                            </div>
                            
                            <Link 
                                :href="route('app.hr.employees.show', employee.id)"
                                class="mt-6 w-full py-2 px-4 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl hover:bg-violet-50 dark:hover:bg-gray-600 hover:text-violet-700 dark:hover:text-violet-300 transition-colors font-medium"
                            >
                                {{ $t('common.view') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">{{ $t('hr.employees.number') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.name') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('hr.employees.job_title') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.department') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('hr.employees.base_salary') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.phone') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="employee in employees.data" 
                                    :key="employee.id" 
                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    <td class="px-6 py-4 font-mono text-xs text-gray-600 dark:text-gray-400">
                                        {{ employee.employee_number }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                                <img v-if="employee.photo_path" :src="`/storage/${employee.photo_path}`" class="w-full h-full object-cover" />
                                                <span v-else class="text-xs font-bold">{{ employee.name_ar?.charAt(0) }}</span>
                                            </div>
                                            <div>
                                                <Link 
                                                    :href="route('app.hr.employees.show', employee.id)"
                                                    class="font-medium text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400"
                                                >
                                                    {{ employee.name_ar }}
                                                </Link>
                                                <p v-if="employee.name_en" class="text-xs text-gray-500">{{ employee.name_en }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ employee.job_title?.name_ar || '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ employee.department?.name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-mono">
                                        {{ employee.base_salary?.toLocaleString() || '0' }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs" dir="ltr">
                                        {{ employee.phone || '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <Link 
                                            :href="route('app.hr.employees.show', employee.id)"
                                            class="text-violet-600 dark:text-violet-400 hover:underline font-medium"
                                        >
                                            {{ $t('common.view') }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!employees.data?.length">
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                            <span class="text-2xl">👥</span>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400">{{ $t('hr.employees.no_employees') }}</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links?.length > 3" class="mt-6">
                    <nav class="flex justify-center gap-1">
                        <Link
                            v-for="link in employees.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded text-sm',
                                link.active
                                    ? 'bg-violet-600 text-white'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <!-- Add Employee Modal -->
        <EmployeeFormModal
            :show="showAddModal"
            :job-titles="jobTitles"
            :nationalities="nationalities"
            :centers="centers"
            :shifts="shifts"
            @close="showAddModal = false"
            @saved="showAddModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmployeeFormModal from '@/Components/HR/EmployeeFormModal.vue';

const props = defineProps({
    employees: Object,
    filters: Object,
    counts: Object,
    jobTitles: Array,
    nationalities: Array,
    centers: Array,
    shifts: Array,
});

const showAddModal = ref(false);
const searchQuery = ref(props.filters?.search || '');
const viewMode = ref('grid'); // Default view mode

const currentStatus = computed(() => props.filters?.status || 'active');

function changeStatus(status) {
    router.get(route('app.hr.employees.index'), { status }, { preserveState: true });
}

function search() {
    router.get(route('app.hr.employees.index'), { 
        status: currentStatus.value,
        search: searchQuery.value 
    }, { preserveState: true });
}
</script>

