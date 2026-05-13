<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('hr.employees.title')"
                :subtitle="$t('hr.employees.subtitle')"
                :totalCount="counts.active + counts.inactive"
                :countLabel="$t('hr.employees.title')"
                gradientFrom="from-violet-600"
                gradientTo="to-purple-700"
                glowFrom="from-violet-500"
                badgeBg="bg-violet-50/50 dark:bg-violet-900/30"
                badgeText="text-violet-600 dark:text-violet-400"
                badgeBorder="border-violet-100/50 dark:border-violet-800/30"
                badgeDot="bg-violet-500"
                backUrl="app.hr.index"
            >
                <template #back>
                    <Link :href="route('app.hr.index')"
                        class="w-10 h-10 rounded-xl bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-center hover:bg-white dark:hover:bg-gray-800 text-gray-400 hover:text-violet-600 shadow-sm transition-all border border-gray-100/50 dark:border-gray-700/50 group/back"
                        :title="$t('common.back')">
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover/back:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                </template>
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <!-- Print Button -->
                        <a :href="route('app.hr.employees.print')" target="_blank"
                            class="p-2.5 text-gray-500 hover:text-violet-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md"
                            :title="$t('common.print')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </a>

                        <!-- Divider -->
                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- View Toggle Group -->
                        <div class="flex items-center gap-1">
                            <button 
                                @click="viewMode = 'grid'"
                                :title="$t('common.grid_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'grid'
                                        ? 'bg-violet-600 text-white shadow-violet-500/20'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button 
                                @click="viewMode = 'list'"
                                :title="$t('common.list_view')"
                                :class="[
                                    'p-2.5 rounded-xl transition-all shadow-sm',
                                    viewMode === 'list'
                                        ? 'bg-violet-600 text-white shadow-violet-500/20'
                                        : 'text-gray-400 hover:text-gray-600 hover:bg-white dark:hover:bg-gray-800'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Add Employee Button -->
                    <button
                        v-if="can('hr.employees.create') || isAnyAdmin()"
                        @click="showAddModal = true"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-violet-600 to-purple-700 text-white rounded-2xl font-black shadow-lg shadow-violet-500/25 hover:shadow-violet-500/40 hover:-translate-y-0.5 transition-all group/add"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">{{ $t('hr.employees.add') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Status Segmented Control -->
                        <div class="flex p-1.5 bg-gray-100/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner w-full md:w-auto">
                            <button
                                v-for="status in ['active', 'inactive']"
                                :key="status"
                                @click="changeStatus(status)"
                                :class="[
                                    'flex-1 md:flex-none px-6 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300',
                                    currentStatus === status
                                        ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-md ring-1 ring-gray-200/50 dark:ring-gray-700/50'
                                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
                                ]"
                            >
                                {{ $t(`common.${status}`) }}
                                <span :class="[
                                    'ms-2 px-2 py-0.5 rounded-lg text-[9px]',
                                    currentStatus === status
                                        ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700/50 dark:text-gray-500'
                                ]">
                                    {{ counts[status] }}
                                </span>
                            </button>
                        </div>

                        <!-- Center Filter -->
                        <div class="w-full md:w-56" v-if="centers.length > 1">
                            <SearchableSelect
                                v-model="selectedCenter"
                                :options="[{ id: '', name: $t('common.all_centers') }, ...centers]"
                                option-label="name"
                                option-value="id"
                                :placeholder="$t('common.all_centers')"
                                :label="''"
                                @update:modelValue="search"
                            />
                        </div>

                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-gray-400 group-focus-within:text-violet-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="$t('common.search')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all outline-none shadow-sm placeholder-gray-400"
                                @keyup.enter="search"
                            />
                        </div>

                        <!-- Reset Button -->
                        <button 
                            @click="resetFilters"
                            class="p-3.5 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all shadow-sm"
                            :title="$t('common.reset')"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </template>
            </PageHeader>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid'"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="employee in employees.data" :key="employee.id"
                        class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow group relative overflow-hidden">
                        <div class="absolute top-0 end-0 p-4">
                            <span :class="[
                                'px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border shadow-sm transition-all',
                                employee.status === 'active'
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/30'
                                    : 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800/30 dark:text-gray-400 dark:border-gray-700/30'
                            ]">
                                {{ $t(`hr.employees.status.${employee.status}`) }}
                            </span>
                        </div>

                        <div class="flex flex-col items-center text-center">
                            <div
                                class="w-20 h-20 mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden border-2 border-white dark:border-gray-600 shadow-sm">
                                <img v-if="employee.photo_path" :src="`/storage/${employee.photo_path}`"
                                    class="w-full h-full object-cover" />
                                <div v-else
                                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-violet-500 to-purple-600 text-white text-2xl font-bold">
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
                                    <span class="font-medium text-gray-900 dark:text-white">{{ employee.department?.name
                                        || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm" v-if="employee.center">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $t('hr.employees.branch')
                                        }}</span>
                                    <span class="font-medium text-violet-600 dark:text-violet-400">{{
                                        employee.center?.name }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $t('common.phone') }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ employee.phone
                                        || '-' }}</span>
                                </div>
                            </div>

                            <Link :href="route('app.hr.employees.show', employee.id)"
                                class="mt-6 w-full py-2 px-4 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl hover:bg-violet-50 dark:hover:bg-gray-600 hover:text-violet-700 dark:hover:text-violet-300 transition-colors font-medium">
                                {{ $t('common.view') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div v-else
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">{{ $t('hr.employees.number') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.name') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('hr.employees.job_title') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('hr.employees.branch') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.department') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('hr.employees.base_salary') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.phone') }}</th>
                                    <th scope="col" class="px-6 py-3">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="employee in employees.data" :key="employee.id"
                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-mono text-xs text-gray-600 dark:text-gray-400">
                                        {{ employee.employee_number }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                                <img v-if="employee.photo_path" :src="`/storage/${employee.photo_path}`"
                                                    class="w-full h-full object-cover" />
                                                <span v-else class="text-xs font-bold">{{ employee.name_ar?.charAt(0)
                                                    }}</span>
                                            </div>
                                            <div>
                                                <Link :href="route('app.hr.employees.show', employee.id)"
                                                    class="font-medium text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400">
                                                    {{ employee.name_ar }}
                                                </Link>
                                                <p v-if="employee.name_en" class="text-xs text-gray-500">{{
                                                    employee.name_en }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ employee.job_title?.name_ar || '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ employee.center?.name || '-' }}
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
                                        <Link :href="route('app.hr.employees.show', employee.id)"
                                            class="text-violet-600 dark:text-violet-400 hover:underline font-medium">
                                            {{ $t('common.view') }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!employees.data?.length">
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div
                                            class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                            <span class="text-2xl">👥</span>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400">{{ $t('hr.employees.no_employees')
                                            }}</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links?.length > 3" class="mt-6">
                    <nav class="flex justify-center gap-1">
                        <Link v-for="link in employees.links" :key="link.label" :href="link.url || '#'" :class="[
                            'px-3 py-1 rounded text-sm',
                            link.active
                                ? 'bg-violet-600 text-white'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]" v-html="link.label" />
                    </nav>
                </div>
            </div>

        <!-- Add Employee Modal -->
        <EmployeeFormModal :show="showAddModal" :job-titles="jobTitles" :nationalities="nationalities"
            :centers="centers" :shifts="shifts" @close="showAddModal = false" @saved="showAddModal = false" />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmployeeFormModal from '@/Components/HR/EmployeeFormModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { usePermission } from '@/Composables/usePermission';

const { can, isAnyAdmin } = usePermission();

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
const selectedCenter = ref(props.filters?.center_id || '');
const viewMode = ref(localStorage.getItem('employees-view-mode') || 'grid');

watch(viewMode, (newVal) => {
    localStorage.setItem('employees-view-mode', newVal);
});

const currentStatus = computed(() => props.filters?.status || 'active');

function changeStatus(status) {
    router.get(route('app.hr.employees.index'), {
        status,
        search: searchQuery.value,
        center_id: selectedCenter.value
    }, { preserveState: true });
}

function search() {
    router.get(route('app.hr.employees.index'), {
        status: currentStatus.value,
        search: searchQuery.value,
        center_id: selectedCenter.value
    }, { preserveState: true });
}

const resetFilters = () => {
    searchQuery.value = '';
    selectedCenter.value = '';
    changeStatus('active');
};
</script>
