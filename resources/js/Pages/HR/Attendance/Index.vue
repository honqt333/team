<template>
    <AppLayout>
        <Head :title="$t('hr.attendance.title')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <Link href="/app/hr" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                             <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg hover:shadow-xl transition-shadow">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('hr.attendance.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.attendance.subtitle') }}</p>
                        </div>
                    </div>

                    <!-- Date Filter -->
                    <div class="flex items-center gap-2">
                        <button @click="changeDate(-1)" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <input 
                            type="date" 
                            v-model="selectedDate"
                            class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                        <button @click="changeDate(1)" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    <button
                        @click="() => window.open(route('app.hr.attendance.print', { date: selectedDate }), '_blank')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                        :title="$t('common.print')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('common.print') }}</span>
                    </button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('common.total') }}</p>
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('hr.attendance.present') }}</p>
                    <span class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.present }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('hr.attendance.absent') }}</p>
                    <span class="text-3xl font-bold text-red-600 dark:text-red-400">{{ stats.absent }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('hr.attendance.leave') }}</p>
                    <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.leave }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $t('hr.attendance.late') }}</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ stats.late_count || 0 }}</span>
                        <span v-if="stats.total_late_minutes" class="text-sm text-gray-500">({{ stats.total_late_minutes }} {{ $t('common.minutes') }})</span>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-4 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.employee') }}
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('hr.attendance.status') }}
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('hr.attendance.check_in') }}
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('hr.attendance.check_out') }}
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('hr.attendance.late') }}
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="employee in employees" :key="employee.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                            <img v-if="employee.photo_url" :src="employee.photo_url" class="w-full h-full object-cover">
                                            <span v-else class="text-sm font-bold text-gray-500 dark:text-gray-400">
                                                {{ employee.name_ar?.charAt(0) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white leading-none mb-1">
                                                {{ employee.name_ar }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ employee.employee_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="employee.attendance" :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-medium',
                                        getStatusColor(employee.attendance.status)
                                    ]">
                                        {{ $t(`hr.attendance.${employee.attendance.status}`) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">
                                        —
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center font-mono text-sm">
                                    {{ employee.attendance?.check_in || '—' }}
                                </td>
                                <td class="px-6 py-4 text-center font-mono text-sm">
                                    {{ employee.attendance?.check_out || '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <template v-if="employee.attendance?.late_minutes > 0">
                                        <span class="px-2 py-1 rounded-lg text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                                            {{ employee.attendance.late_minutes }} {{ $t('common.minutes') }}
                                        </span>
                                    </template>
                                    <template v-else-if="employee.attendance?.overtime_minutes > 0">
                                        <span class="px-2 py-1 rounded-lg text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            +{{ employee.attendance.overtime_minutes }} {{ $t('common.minutes') }}
                                        </span>
                                    </template>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            v-if="!employee.attendance"
                                            @click="checkIn(employee)"
                                            class="px-3 py-1.5 text-xs font-medium bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors"
                                        >
                                            {{ $t('hr.attendance.check_in') }}
                                        </button>
                                        
                                        <button 
                                            v-if="employee.attendance && !employee.attendance.check_out && employee.attendance.status === 'present'"
                                            @click="checkOut(employee)"
                                            class="px-3 py-1.5 text-xs font-medium bg-orange-50 text-orange-600 hover:bg-orange-100 rounded-lg border border-orange-200 transition-colors"
                                        >
                                            {{ $t('hr.attendance.check_out') }}
                                        </button>

                                        <button 
                                            v-if="!employee.attendance"
                                            @click="markAbsent(employee)"
                                            class="px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 rounded-lg border border-red-200 transition-colors"
                                        >
                                            {{ $t('hr.attendance.absent') }}
                                        </button>

                                        <button 
                                            v-if="employee.attendance"
                                            @click="editAttendance(employee)"
                                            class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                            :title="$t('common.edit')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!employees.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('common.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Attendance Modal -->
        <BaseModal :show="editingAttendance !== null" @close="editingAttendance = null" size="md">
            <template #title>
                {{ $t('hr.attendance.update_status') }}
            </template>
            
            <div v-if="editingAttendance" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('hr.attendance.status') }}
                    </label>
                    <SearchableSelect
                        v-model="editForm.status"
                        :options="attendanceStatusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="{{ $t('common.choose') }}"
                        class="w-full"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.attendance.check_in') }}
                        </label>
                        <input 
                            v-model="editForm.check_in"
                            type="time"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.attendance.check_out') }}
                        </label>
                        <input 
                            v-model="editForm.check_out"
                            type="time"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                        >
                    </div>
                </div>

                 <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('hr.attendance.notes') }}
                    </label>
                    <textarea 
                        v-model="editForm.notes"
                        rows="3"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                    ></textarea>
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button 
                        @click="editingAttendance = null"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button 
                        @click="updateAttendance"
                        class="px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, watch, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    employees: Array,
    filters: Object,
    stats: Object
});

const { t } = useI18n();
const { success, error } = useToast();

const attendanceStatusOptions = [
    { value: 'present', label: t('hr.attendance.present') },
    { value: 'absent', label: t('hr.attendance.absent') },
    { value: 'late', label: t('hr.attendance.late') },
    { value: 'leave', label: t('hr.attendance.leave') },
    { value: 'holiday', label: t('hr.attendance.holiday') },
];

const selectedDate = ref(props.filters.date);
const editingAttendance = ref(null);
const editForm = reactive({
    id: null,
    status: 'present',
    check_in: null,
    check_out: null,
    notes: ''
});

watch(selectedDate, (newDate) => {
    router.get(route('app.hr.attendance.index'), { date: newDate }, { preserveState: true, preserveScroll: true });
});

function changeDate(days) {
    const date = new Date(selectedDate.value);
    date.setDate(date.getDate() + days);
    selectedDate.value = date.toISOString().split('T')[0];
}

function getStatusColor(status) {
    switch (status) {
        case 'present': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'absent': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'late': return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'leave': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    }
}

function checkIn(employee) {
    const time = new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
    
    router.post(route('app.hr.attendance.store'), {
        employee_id: employee.id,
        date: selectedDate.value,
        status: 'present',
        check_in: time
    }, {
        preserveScroll: true,
        onError: () => error(t('common.error_occurred'))
    });
}

function checkOut(employee) {
    const time = new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
    
    router.put(route('app.hr.attendance.update', employee.attendance.id), {
        status: 'present',
        check_out: time
    }, {
        preserveScroll: true,
        onError: () => error(t('common.error_occurred'))
    });
}

function markAbsent(employee) {
    router.post(route('app.hr.attendance.store'), {
        employee_id: employee.id,
        date: selectedDate.value,
        status: 'absent'
    }, {
        preserveScroll: true,
        onError: () => error(t('common.error_occurred'))
    });
}

function editAttendance(employee) {
    editingAttendance.value = employee;
    editForm.id = employee.attendance.id;
    editForm.status = employee.attendance.status;
    editForm.check_in = employee.attendance.check_in;
    editForm.check_out = employee.attendance.check_out;
    editForm.notes = employee.attendance.notes;
}

function updateAttendance() {
    router.put(route('app.hr.attendance.update', editForm.id), {
        status: editForm.status,
        check_in: editForm.check_in,
        check_out: editForm.check_out,
        notes: editForm.notes
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingAttendance.value = null;
            success(t('common.saved_success'));
        },
        onError: () => error(t('common.error_occurred'))
    });
}
</script>
