<template>
    <Head :title="$t('hr.attendance.print_report')" />

    <StandardReportLayout
        :title="$t('hr.attendance.daily_report')"
        :subtitle="date"
        :total-count="employees.length"
        :total-count-label="$t('hr.employees.total')"
        :custom-company="tenant"
        :on-print="print"
        :on-close="close"
    >
        <!-- Table -->
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-900">
                    <th class="py-2.5 px-3 text-start font-bold uppercase border border-gray-300">
                        {{ $t('hr.employees.number') }}
                    </th>
                    <th class="py-2.5 px-3 text-start font-bold uppercase border border-gray-300">
                        {{ $t('common.employee') }}
                    </th>
                    <th class="py-2.5 px-3 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.attendance.status') }}
                    </th>
                    <th class="py-2.5 px-3 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.attendance.shift_start') }}
                    </th>
                    <th class="py-2.5 px-3 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.attendance.check_in') }}
                    </th>
                    <th class="py-2.5 px-3 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.attendance.shift_end') }}
                    </th>
                    <th class="py-2.5 px-3 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.attendance.check_out') }}
                    </th>
                    <th
                        class="py-2.5 px-3 text-start font-bold uppercase border border-gray-300 w-1/4"
                    >
                        {{ $t('hr.attendance.notes') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr
                    v-for="employee in employees"
                    :key="employee.id"
                    class="break-inside-avoid hover:bg-gray-50"
                >
                    <td class="py-2.5 px-3 font-mono border border-gray-200">
                        {{ employee.employee_number }}
                    </td>
                    <td class="py-2.5 px-3 border border-gray-200">
                        <div class="font-bold">{{ employee.name_ar }}</div>
                        <div class="text-xs text-gray-500 font-mono">
                            {{ employee.job_title }} - {{ employee.department }}
                        </div>
                    </td>
                    <td class="py-2.5 px-3 text-center border border-gray-200">
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-xs font-bold border',
                                getStatusColor(employee.attendance?.status),
                            ]"
                        >
                            {{
                                employee.attendance
                                    ? $t(`hr.attendance.${employee.attendance.status}`)
                                    : '—'
                            }}
                        </span>
                    </td>
                    <td
                        class="py-2.5 px-3 text-center font-mono text-gray-500 border border-gray-200"
                    >
                        {{ employee.shift_start || '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono font-bold border border-gray-200">
                        {{ employee.attendance?.check_in || '—' }}
                    </td>
                    <td
                        class="py-2.5 px-3 text-center font-mono text-gray-500 border border-gray-200"
                    >
                        {{ employee.shift_end || '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono font-bold border border-gray-200">
                        {{ employee.attendance?.check_out || '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-xs border border-gray-200">
                        {{ employee.attendance?.notes || '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </StandardReportLayout>
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';
import { Head } from '@inertiajs/vue3';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';

const props = defineProps({
    employees: Array,
    date: String,
    tenant: Object,
});

function print() {
    printDocument();
}

function close() {
    window.close();
}

function getStatusColor(status) {
    switch (status) {
        case 'present':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'absent':
            return 'bg-red-50 text-red-700 border-red-200';
        case 'late':
            return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        case 'leave':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'holiday':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}
</script>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 1cm;
    }
}
</style>
