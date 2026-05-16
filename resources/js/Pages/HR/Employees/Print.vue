<template>
    <div class="p-8 max-w-5xl mx-auto bg-white min-h-screen text-gray-900" dir="rtl">
        <Head :title="$t('hr.employees.print_report')" />

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
        <div class="mb-8 border-b-2 border-gray-900 pb-6">
            <div class="grid grid-cols-3 items-start">
                <!-- Column 1: Center Info -->
                <div class="text-right">
                    <h1 class="text-xl font-bold">{{ tenant?.name }}</h1>
                    <p class="text-sm text-gray-500">{{ center?.name }}</p>
                    <div class="mt-2 text-xs text-gray-500">
                        {{ $t('hr.employees.total') }}: {{ employees.length }}
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
                </div>
            </div>

            <div class="mt-6 text-center">
                <h2 class="text-xl font-bold uppercase tracking-widest">{{ $t('hr.employees.print_report') }}</h2>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-900">
                    <th class="py-3 px-2 text-start font-bold uppercase w-12">#</th>
                    <th class="py-3 px-2 text-start font-bold uppercase">{{ $t('hr.employees.number') }}</th>
                    <th class="py-3 px-2 text-start font-bold uppercase">{{ $t('common.name') }}</th>
                    <th class="py-3 px-2 text-start font-bold uppercase">{{ $t('hr.employees.job_title') }}</th>
                    <th class="py-3 px-2 text-start font-bold uppercase">{{ $t('hr.employees.department') }}</th>
                    <th class="py-3 px-2 text-center font-bold uppercase">{{ $t('hr.employees.phone') }}</th>
                    <th class="py-3 px-2 text-center font-bold uppercase">{{ $t('hr.employees.hire_date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr v-for="(employee, index) in employees" :key="employee.id" class="break-inside-avoid">
                    <td class="py-3 px-2 text-gray-500">{{ index + 1 }}</td>
                    <td class="py-3 px-2 font-mono">{{ employee.employee_number }}</td>
                    <td class="py-3 px-2">
                        <div class="font-bold">{{ employee.name_ar }}</div>
                        <div class="text-xs text-gray-500">{{ employee.name_en }}</div>
                    </td>
                    <td class="py-3 px-2">{{ employee.job_title?.name_ar || '—' }}</td>
                    <td class="py-3 px-2">{{ employee.department?.name_ar || '—' }}</td>
                    <td class="py-3 px-2 text-center font-mono" dir="ltr">{{ employee.phone || '—' }}</td>
                    <td class="py-3 px-2 text-center">{{ formatDate(employee.hire_date) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="mt-8 pt-4 border-t-2 border-gray-900">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold">{{ employees.length }}</div>
                    <div class="text-sm text-gray-500">{{ $t('hr.employees.total') }}</div>
                </div>
                <div v-if="stats.by_department">
                    <div class="text-lg font-bold">{{ Object.keys(stats.by_department).length }}</div>
                    <div class="text-sm text-gray-500">{{ $t('hr.employees.departments') }}</div>
                </div>
                <div v-if="stats.by_job_title">
                    <div class="text-lg font-bold">{{ Object.keys(stats.by_job_title).length }}</div>
                    <div class="text-sm text-gray-500">{{ $t('hr.employees.job_titles') }}</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-400">
            {{ $t('common.generated_by_system') }}
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    employees: Array,
    tenant: Object,
    center: Object,
    stats: {
        type: Object,
        default: () => ({})
    }
});

const today = new Date().toLocaleDateString('en-CA');

function print() {
    window.print();
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
        margin: 1cm;
    }
    body {
        background: white;
    }
}
</style>
