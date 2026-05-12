<template>
    <div class="print-container bg-white min-h-screen p-8 text-gray-900" :dir="isRtl ? 'rtl' : 'ltr'">
        <Head :title="$t('hr.employees.print_report')" />

        <!-- Reusable Print Header -->
        <PrintHeader 
            :title="$t('hr.employees.print_report')"
            :subtitle="center?.name || ''"
        />

        <!-- Stats Summary Section -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 flex flex-col items-center justify-center text-center">
                <span class="text-gray-500 text-xs mb-1 uppercase tracking-wider">{{ $t('hr.employees.total') }}</span>
                <span class="text-2xl font-bold text-violet-600">{{ employees.length }}</span>
            </div>
            <div v-if="stats.by_department" class="p-4 bg-gray-50 rounded-xl border border-gray-100 flex flex-col items-center justify-center text-center">
                <span class="text-gray-500 text-xs mb-1 uppercase tracking-wider">{{ $t('hr.employees.departments') }}</span>
                <span class="text-2xl font-bold text-indigo-600">{{ Object.keys(stats.by_department).length }}</span>
            </div>
            <div v-if="stats.by_job_title" class="p-4 bg-gray-50 rounded-xl border border-gray-100 flex flex-col items-center justify-center text-center">
                <span class="text-gray-500 text-xs mb-1 uppercase tracking-wider">{{ $t('hr.employees.job_titles') }}</span>
                <span class="text-2xl font-bold text-purple-600">{{ Object.keys(stats.by_job_title).length }}</span>
            </div>
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 flex flex-col items-center justify-center text-center">
                <span class="text-gray-500 text-xs mb-1 uppercase tracking-wider">{{ isRtl ? 'تاريخ التقرير' : 'Report Date' }}</span>
                <span class="text-lg font-bold text-gray-700">{{ today }}</span>
            </div>
        </div>

        <!-- Table Section -->
        <div class="overflow-hidden border border-gray-200 rounded-xl">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-3 text-start font-bold text-gray-700 w-10">#</th>
                        <th class="py-3 px-3 text-start font-bold text-gray-700">{{ $t('hr.employees.number') }}</th>
                        <th class="py-3 px-3 text-start font-bold text-gray-700">{{ $t('common.name') }}</th>
                        <th class="py-3 px-3 text-start font-bold text-gray-700">{{ $t('hr.employees.job_title') }}</th>
                        <th class="py-3 px-3 text-start font-bold text-gray-700">{{ $t('hr.employees.department') }}</th>
                        <th class="py-3 px-3 text-center font-bold text-gray-700">{{ $t('common.phone') }}</th>
                        <th class="py-3 px-3 text-center font-bold text-gray-700">{{ $t('hr.employees.hire_date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="(employee, index) in employees" :key="employee.id" class="break-inside-avoid hover:bg-gray-50/50">
                        <td class="py-3 px-3 text-gray-400">{{ index + 1 }}</td>
                        <td class="py-3 px-3 font-mono text-xs text-gray-600">{{ employee.employee_number }}</td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-gray-900">{{ employee.name_ar }}</div>
                            <div v-if="employee.name_en" class="text-[10px] text-gray-400 uppercase tracking-tight">{{ employee.name_en }}</div>
                        </td>
                        <td class="py-3 px-3 text-gray-700">{{ employee.job_title?.name_ar || employee.job_title?.name || '—' }}</td>
                        <td class="py-3 px-3 text-gray-600">{{ employee.department?.name || employee.department?.name_ar || '—' }}</td>
                        <td class="py-3 px-3 text-center font-mono text-gray-600" dir="ltr">{{ employee.phone || '—' }}</td>
                        <td class="py-3 px-3 text-center text-gray-600">{{ formatDate(employee.hire_date) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Signatures -->
        <div class="mt-12 pt-8 border-t border-gray-100">
            <div class="grid grid-cols-3 gap-8">
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-8 uppercase tracking-widest">{{ isRtl ? 'توقيع شؤون الموظفين' : 'HR Signature' }}</p>
                    <div class="w-32 mx-auto border-b border-gray-300"></div>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-8 uppercase tracking-widest">{{ isRtl ? 'الختم الرسمي' : 'Official Stamp' }}</p>
                    <div class="w-24 h-24 mx-auto border border-dashed border-gray-200 rounded-full flex items-center justify-center">
                        <span class="text-[10px] text-gray-200 uppercase tracking-tighter">{{ isRtl ? 'ختم هنا' : 'Stamp Here' }}</span>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-8 uppercase tracking-widest">{{ isRtl ? 'توقيع المدير العام' : 'General Manager' }}</p>
                    <div class="w-32 mx-auto border-b border-gray-300"></div>
                </div>
            </div>
        </div>

        <!-- System Footer -->
        <div class="mt-12 pt-4 border-t border-gray-100 text-center">
            <p class="text-[10px] text-gray-400 uppercase tracking-[0.2em]">
                {{ $t('common.generated_by_system') }} • {{ today }}
            </p>
        </div>

        <!-- Floating Print Controls -->
        <div class="fixed bottom-6 left-6 flex items-center gap-3 print:hidden">
            <button 
                @click="close"
                class="px-4 py-3 bg-white text-gray-700 rounded-xl shadow-xl border border-gray-200 hover:bg-gray-50 font-medium transition-all flex items-center gap-2"
            >
                {{ $t('common.close') }}
            </button>
            <button 
                @click="printPage"
                class="px-6 py-3 bg-violet-600 text-white rounded-xl shadow-xl hover:bg-violet-700 font-medium flex items-center gap-2 transform hover:-translate-y-1 transition-all"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                {{ $t('common.print') }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PrintHeader from '@/Components/Print/PrintHeader.vue';

const props = defineProps({
    employees: Array,
    tenant: Object,
    center: Object,
    stats: {
        type: Object,
        default: () => ({})
    }
});

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const today = computed(() => {
    return new Date().toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-CA');
});

function printPage() {
    window.print();
}

function close() {
    window.close();
}

function formatDate(dateString) {
    if (!dateString) return '—';
    try {
        return new Date(dateString).toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-CA');
    } catch {
        return dateString;
    }
}
</script>

<style>
@media print {
    @page {
        size: A4;
        margin: 1cm;
    }
    body, html {
        background: white !important;
        height: auto !important;
        overflow: visible !important;
    }
    #app {
        height: auto !important;
        overflow: visible !important;
    }
    .print-container {
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
        background: white !important;
    }
    .print\:hidden {
        display: none !important;
    }
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
