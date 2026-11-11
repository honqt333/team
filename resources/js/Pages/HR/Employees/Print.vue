<template>
    <Head :title="$t('hr.employees.print_report')" />

    <StandardReportLayout
        :title="$t('hr.employees.print_report')"
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
                    <th
                        class="py-2.5 px-2 text-start font-bold uppercase w-12 border border-gray-300"
                    >
                        #
                    </th>
                    <th class="py-2.5 px-2 text-start font-bold uppercase border border-gray-300">
                        {{ $t('hr.employees.number') }}
                    </th>
                    <th class="py-2.5 px-2 text-start font-bold uppercase border border-gray-300">
                        {{ $t('common.name') }}
                    </th>
                    <th class="py-2.5 px-2 text-start font-bold uppercase border border-gray-300">
                        {{ $t('hr.employees.job_title') }}
                    </th>
                    <th class="py-2.5 px-2 text-start font-bold uppercase border border-gray-300">
                        {{ $t('hr.employees.department') }}
                    </th>
                    <th class="py-2.5 px-2 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.employees.phone') }}
                    </th>
                    <th class="py-2.5 px-2 text-center font-bold uppercase border border-gray-300">
                        {{ $t('hr.employees.hire_date') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr
                    v-for="(employee, index) in employees"
                    :key="employee.id"
                    class="break-inside-avoid hover:bg-gray-50"
                >
                    <td
                        class="py-2 px-2 text-gray-500 border border-gray-200 font-mono text-center"
                    >
                        {{ index + 1 }}
                    </td>
                    <td class="py-2 px-2 font-mono border border-gray-200">
                        {{ employee.employee_number }}
                    </td>
                    <td class="py-2 px-2 border border-gray-200">
                        <div class="font-bold">{{ employee.name_ar }}</div>
                        <div class="text-xs text-gray-500 font-mono">{{ employee.name_en }}</div>
                    </td>
                    <td class="py-2 px-2 border border-gray-200">
                        {{ employee.job_title?.name_ar || '—' }}
                    </td>
                    <td class="py-2 px-2 border border-gray-200">
                        {{ employee.department?.name_ar || '—' }}
                    </td>
                    <td class="py-2 px-2 text-center font-mono border border-gray-200" dir="ltr">
                        {{ employee.phone || '—' }}
                    </td>
                    <td class="py-2 px-2 text-center font-mono border border-gray-200">
                        {{ formatDate(employee.hire_date) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Summary KPI stats -->
        <div
            v-if="stats && (stats.by_department || stats.by_job_title)"
            class="mt-6 pt-4 border-t-2 border-gray-300"
        >
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-2 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="text-xl font-black font-mono">{{ employees.length }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $t('hr.employees.total') }}</div>
                </div>
                <div
                    v-if="stats.by_department"
                    class="p-2 bg-gray-50 rounded-xl border border-gray-200"
                >
                    <div class="text-xl font-black font-mono">
                        {{ Object.keys(stats.by_department).length }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        {{ $t('hr.employees.departments') }}
                    </div>
                </div>
                <div
                    v-if="stats.by_job_title"
                    class="p-2 bg-gray-50 rounded-xl border border-gray-200"
                >
                    <div class="text-xl font-black font-mono">
                        {{ Object.keys(stats.by_job_title).length }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        {{ $t('hr.employees.job_titles') }}
                    </div>
                </div>
            </div>
        </div>
    </StandardReportLayout>
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';
import { Head } from '@inertiajs/vue3';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';

const props = defineProps({
    employees: Array,
    tenant: Object,
    center: Object,
    stats: {
        type: Object,
        default: () => ({}),
    },
});

function print() {
    printDocument();
}

function close() {
    window.close();
}

function formatDate(dateString) {
    if (!dateString) return '—';
    try {
        return new Date(dateString).toLocaleDateString('en-CA');
    } catch {
        return dateString;
    }
}
</script>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 15mm 10mm 12mm 10mm;
    }
}
</style>
