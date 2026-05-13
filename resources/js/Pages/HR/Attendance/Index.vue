<template>
    <AppLayout>
        <Head :title="$t('hr.attendance.title')" />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('hr.attendance.title')"
                :subtitle="$t('hr.attendance.subtitle')"
                gradientFrom="from-orange-500"
                gradientTo="to-amber-600"
                glowFrom="from-orange-400"
                badgeBg="bg-orange-50/50 dark:bg-orange-900/30"
                badgeText="text-orange-600 dark:text-orange-400"
                badgeBorder="border-orange-100/50 dark:border-orange-800/30"
                badgeDot="bg-orange-500"
            >
                <template #back>
                    <Link href="/app/hr"
                        class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center hover:bg-orange-50 dark:hover:bg-orange-900/20 text-orange-600 dark:text-orange-400 shadow-md transition-all border border-gray-100 dark:border-gray-700 group/back"
                        :title="$t('common.back')">
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover/back:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-1.5 p-1.5 bg-gray-50/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
                        <!-- Print Button -->
                        <a 
                            :href="route('app.hr.attendance.print', { date: selectedDate })" 
                            target="_blank"
                            class="p-2.5 text-gray-500 hover:text-orange-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm hover:shadow-md"
                            :title="$t('common.print')"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </a>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Date Navigation -->
                        <div class="flex items-center gap-2 p-1.5 bg-gray-100/50 dark:bg-gray-900/50 backdrop-blur-md rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-inner w-full md:w-auto">
                            <button @click="changeDate(-1)" class="p-2.5 text-gray-500 hover:text-orange-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm">
                                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            
                            <div class="lg:w-48">
                                <CustomDatePicker
                                    v-model="selectedDate"
                                    class="!rounded-2xl"
                                />
                            </div>

                            <button @click="changeDate(1)" class="p-2.5 text-gray-500 hover:text-orange-600 hover:bg-white dark:hover:bg-gray-800 rounded-xl transition-all shadow-sm">
                                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Spacer for alignment -->
                        <div class="flex-1"></div>
                    </div>
                </template>
            </PageHeader>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border-t-4 border-gray-400 dark:border-gray-600 shadow-xl shadow-gray-200/10 dark:shadow-none">
                    <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">{{ $t('common.total') }}</p>
                    <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ stats.total }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border-t-4 border-emerald-500 dark:border-emerald-700 shadow-xl shadow-emerald-200/10 dark:shadow-none">
                    <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">{{ $t('hr.attendance.present') }}</p>
                    <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ stats.present }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border-t-4 border-red-500 dark:border-red-700 shadow-xl shadow-red-200/10 dark:shadow-none">
                    <p class="text-[10px] font-black text-red-600 dark:text-red-400 uppercase tracking-widest mb-1">{{ $t('hr.attendance.absent') }}</p>
                    <span class="text-3xl font-black text-red-600 dark:text-red-400 tracking-tight">{{ stats.absent }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border-t-4 border-blue-500 dark:border-blue-700 shadow-xl shadow-blue-200/10 dark:shadow-none">
                    <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1">{{ $t('hr.attendance.leave') }}</p>
                    <span class="text-3xl font-black text-blue-600 dark:text-blue-400 tracking-tight">{{ stats.leave }}</span>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border-t-4 border-orange-500 dark:border-orange-700 shadow-xl shadow-orange-200/10 dark:shadow-none">
                    <p class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-widest mb-1">{{ $t('hr.attendance.late') }}</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-orange-600 dark:text-orange-400 tracking-tight">{{ stats.late_count || 0 }}</span>
                        <span v-if="stats.total_late_minutes" class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">({{ stats.total_late_minutes }} {{ $t('common.minutes') }})</span>
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
                                        'px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border shadow-sm transition-all',
                                        getStatusColor(employee.attendance.status)
                                    ]">
                                        {{ $t(`hr.attendance.${employee.attendance.status}`) }}
                                    </span>
                                    <span v-else class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        {{ $t('hr.attendance.not_marked') }}
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
                    <select 
                        v-model="editForm.status"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                    >
                        <option value="present">{{ $t('hr.attendance.present') }}</option>
                        <option value="absent">{{ $t('hr.attendance.absent') }}</option>
                        <option value="late">{{ $t('hr.attendance.late') }}</option>
                        <option value="leave">{{ $t('hr.attendance.leave') }}</option>
                        <option value="holiday">{{ $t('hr.attendance.holiday') }}</option>
                    </select>
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
import PageHeader from '@/Components/PageHeader.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    employees: Array,
    filters: Object,
    stats: Object
});

const { t } = useI18n();
const { success, error } = useToast();

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
        case 'present': return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/30';
        case 'absent': return 'bg-red-50 text-red-700 border-red-100 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800/30';
        case 'late': return 'bg-orange-50 text-orange-700 border-orange-100 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800/30';
        case 'leave': return 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/30';
        default: return 'bg-gray-50 text-gray-700 border-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600';
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
