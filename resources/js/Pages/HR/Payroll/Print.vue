<template>
    <div class="p-8 max-w-5xl mx-auto bg-white min-h-screen text-gray-900" dir="rtl">
        <Head :title="$t('hr.payroll.print_report')" />

        <!-- Print Controls -->
        <div class="print:hidden mb-8 flex justify-between items-center">
            <button 
                @click="print"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium flex items-center gap-2"
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
        <div class="mb-8 border-b-2 border-gray-900 pb-6">
            <div class="grid grid-cols-3 items-start">
                <!-- Column 1: Center Info -->
                <div class="text-right">
                    <h1 class="text-xl font-bold">{{ tenant?.name }}</h1>
                    <p class="text-sm text-gray-500">{{ payrollRun.center?.name }}</p>
                    <div class="mt-2 text-xs text-gray-500">
                        {{ payrollRun.items?.length }} {{ $t('hr.employees.employee') }}
                    </div>
                </div>

                <!-- Column 2: Logo Placeholder (Centers the layout) -->
                <div class="flex justify-center">
                    <div v-if="tenant?.logo_url" class="w-20 h-20">
                        <img :src="tenant.logo_url" class="w-full h-full object-contain" />
                    </div>
                </div>

                <!-- Column 3: Date (Far Left in Arabic) -->
                <div class="text-left">
                    <p class="text-xs text-gray-400 font-bold" dir="ltr">
                        {{ new Date().toLocaleDateString('en-GB').split('/').reverse().join('/') }}
                    </p>
                    <div class="mt-1 text-sm font-bold text-gray-900">
                        {{ formatPeriod(payrollRun.period_start) }}
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <h2 class="text-xl font-bold uppercase tracking-widest">{{ $t('hr.payroll.print_title') }}</h2>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-900">
                    <th class="py-3 px-2 text-start font-bold uppercase w-8">#</th>
                    <th class="py-3 px-2 text-start font-bold uppercase">{{ $t('common.employee') }}</th>
                    <th class="py-3 px-2 text-center font-bold uppercase">{{ $t('hr.payroll.base_salary') }}</th>
                    <th class="py-3 px-2 text-center font-bold uppercase">{{ $t('hr.payroll.allowances') }}</th>
                    <th class="py-3 px-2 text-center font-bold uppercase">{{ $t('hr.payroll.deductions') }}</th>
                    <th class="py-3 px-2 text-center font-bold uppercase">{{ $t('hr.payroll.net_salary') }}</th>
                    <th class="py-3 px-2 text-center font-bold uppercase w-24">{{ $t('common.signature') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr v-for="(item, index) in payrollRun.items" :key="item.id" class="break-inside-avoid">
                    <td class="py-3 px-2 text-gray-500">{{ index + 1 }}</td>
                    <td class="py-3 px-2">
                        <div class="font-bold">{{ item.employee?.name_ar }}</div>
                        <div class="text-xs text-gray-500">{{ item.employee?.job_title?.name_ar }}</div>
                    </td>
                    <td class="py-3 px-2 text-center font-mono">{{ formatAmount(item.base_salary) }}</td>
                    <td class="py-3 px-2 text-center font-mono text-emerald-600">{{ formatAmount(item.total_allowances) }}</td>
                    <td class="py-3 px-2 text-center font-mono text-red-600">{{ formatAmount(item.total_deductions) }}</td>
                    <td class="py-3 px-2 text-center font-bold">{{ formatAmount(item.net_salary) }}</td>
                    <td class="py-3 px-2 text-center border-r border-gray-300">__________</td>
                </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-900 font-bold">
                <tr>
                    <td colspan="2" class="py-3 px-2">{{ $t('common.total') }}</td>
                    <td class="py-3 px-2 text-center">{{ formatAmount(totalSalaries) }}</td>
                    <td class="py-3 px-2 text-center text-emerald-600">{{ formatAmount(totalAllowances) }}</td>
                    <td class="py-3 px-2 text-center text-red-600">{{ formatAmount(totalDeductions) }}</td>
                    <td class="py-3 px-2 text-center text-lg">{{ formatAmount(totalNet) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="mt-12 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-3 gap-8 text-center">
                <div>
                    <p class="text-sm text-gray-500 mb-8">أعده</p>
                    <p class="border-t border-gray-400 pt-2">{{ payrollRun.created_by?.name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-8">اعتمده</p>
                    <p class="border-t border-gray-400 pt-2">{{ payrollRun.approved_by?.name || '__________' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-8">المدير المالي</p>
                    <p class="border-t border-gray-400 pt-2">__________</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    payrollRun: Object,
    tenant: Object,
});

const totalSalaries = computed(() => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.base_salary || 0), 0) || 0);
const totalAllowances = computed(() => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.total_allowances || 0), 0) || 0);
const totalDeductions = computed(() => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.total_deductions || 0), 0) || 0);
const totalNet = computed(() => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.net_salary || 0), 0) || 0);

function print() {
    window.print();
}

function close() {
    window.close();
}

function formatPeriod(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });
}

function formatAmount(amount) {
    return new Intl.NumberFormat('ar-SA').format(amount || 0);
}
</script>

<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 1cm;
    }
    body {
        background: white;
    }
}
</style>
