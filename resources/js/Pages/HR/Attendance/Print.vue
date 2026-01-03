<template>
    <div class="p-8 max-w-5xl mx-auto bg-white min-h-screen text-gray-900" dir="rtl">
        <Head :title="$t('hr.attendance.print_report')" />

        <!-- Print Controls -->
        <div class="print:hidden mb-8 flex justify-between items-center">
            <button 
                @click="print"
                class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 font-medium flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                {{ $t('common.print') }}
            </button>
            <button 
                @click="close"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium"
            >
                {{ $t('common.close') }}
            </button>
        </div>

        <!-- Report Header -->
        <div class="mb-8 border-b-2 border-gray-900 pb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold mb-2">{{ tenant?.name }}</h1>
                    <h2 class="text-xl font-semibold text-gray-600">{{ $t('hr.attendance.daily_report') }}</h2>
                </div>
                <div class="text-left">
                    <div class="text-lg font-bold">{{ date }}</div>
                    <div class="text-sm text-gray-500">{{ new Date().toLocaleTimeString('en-US') }}</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-900">
                    <th class="py-3 px-4 text-start font-bold uppercase">{{ $t('hr.employees.number') }}</th>
                    <th class="py-3 px-4 text-start font-bold uppercase">{{ $t('common.employee') }}</th>
                    <th class="py-3 px-4 text-center font-bold uppercase">{{ $t('hr.attendance.status') }}</th>
                    <th class="py-3 px-4 text-center font-bold uppercase">{{ $t('hr.attendance.shift_start') }}</th>
                    <th class="py-3 px-4 text-center font-bold uppercase">{{ $t('hr.attendance.check_in') }}</th>
                    <th class="py-3 px-4 text-center font-bold uppercase">{{ $t('hr.attendance.shift_end') }}</th>
                    <th class="py-3 px-4 text-center font-bold uppercase">{{ $t('hr.attendance.check_out') }}</th>
                    <th class="py-3 px-4 text-start font-bold uppercase w-1/4">{{ $t('hr.attendance.notes') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr v-for="employee in employees" :key="employee.id" class="break-inside-avoid">
                    <td class="py-3 px-4 font-mono">{{ employee.employee_number }}</td>
                    <td class="py-3 px-4">
                        <div class="font-bold">{{ employee.name_ar }}</div>
                        <div class="text-xs text-gray-500">{{ employee.job_title }} - {{ employee.department }}</div>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span :class="[
                            'px-2 py-1 rounded text-xs font-bold border',
                            getStatusColor(employee.attendance?.status)
                        ]">
                            {{ employee.attendance ? $t(`hr.attendance.${employee.attendance.status}`) : '—' }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center font-mono text-gray-500">{{ employee.shift_start || '—' }}</td>
                    <td class="py-3 px-4 text-center font-mono font-bold">{{ employee.attendance?.check_in || '—' }}</td>
                    <td class="py-3 px-4 text-center font-mono text-gray-500">{{ employee.shift_end || '—' }}</td>
                    <td class="py-3 px-4 text-center font-mono font-bold">{{ employee.attendance?.check_out || '—' }}</td>
                    <td class="py-3 px-4 text-xs">{{ employee.attendance?.notes || '-' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-400">
            {{ $t('common.generated_by_system') }}
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    employees: Array,
    date: String,
    tenant: Object
});

function print() {
    window.print();
}

function close() {
    window.close();
}

onMounted(() => {
    // Optional: Auto-print
    // setTimeout(() => window.print(), 500);
});

function getStatusColor(status) {
    switch (status) {
        case 'present': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'absent': return 'bg-red-50 text-red-700 border-red-200';
        case 'late': return 'bg-yellow-50 text-yellow-700 border-yellow-200';
        case 'leave': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'holiday': return 'bg-purple-50 text-purple-700 border-purple-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}
</script>

<style>
@media print {
    @page {
        size: A4;
        margin: 1cm;
    }
    body {
        background: white;
    }
}
</style>
