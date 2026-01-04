<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('app.hr.payroll.index')" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg">
                            💵
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('hr.payroll.details') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatPeriod(payrollRun.period_start) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Other Payments Link -->
                        <Link :href="route('app.hr.employees.index')" class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                            {{ $t('hr.financial.other_payments.title') }} ↗
                        </Link>
                        
                        <!-- Status Badge -->
                        <span :class="getStatusClass(payrollRun.status)" class="px-4 py-2 rounded-full text-sm font-bold">
                            {{ $t(`hr.payroll.statuses.${payrollRun.status}`) }}
                        </span>
                        
                        <!-- Actions -->
                        <div v-if="payrollRun.status === 'draft'" class="flex gap-2">
                             <button @click="regeneratePayroll" :disabled="processing"
                                class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 flex items-center gap-2">
                                🔄 {{ $t('hr.payroll.regenerate') }}
                            </button>
                            <button @click="approvePayroll" :disabled="processing"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                ✅ {{ $t('hr.payroll.approve') }}
                            </button>
                        </div>
                        <button v-if="payrollRun.status === 'approved'" @click="markAsPaid" :disabled="processing"
                            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                            💵 {{ $t('hr.payroll.mark_paid') }}
                        </button>
                        <a :href="route('app.hr.payroll.print', payrollRun.id)" target="_blank"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200">
                            🖨️ {{ $t('common.print') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">{{ $t('hr.payroll.employees_count') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ payrollRun.items?.length || 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">{{ $t('hr.payroll.total_salaries') }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totalSalaries) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">{{ $t('hr.payroll.total_deductions') }}</div>
                    <div class="text-2xl font-bold text-red-600">{{ formatCurrency(totalDeductions) }}</div>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-900/30 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
                    <div class="text-sm text-emerald-600 dark:text-emerald-400 mb-1">{{ $t('hr.payroll.total_net') }}</div>
                    <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ formatCurrency(totalNet) }}</div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-white">{{ $t('hr.payroll.items') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium text-gray-600 dark:text-gray-300">{{ $t('common.employee') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.base_salary') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.allowances') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.deductions') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.gosi') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-emerald-600 dark:text-emerald-400">{{ $t('hr.payroll.net_salary') }}</th>
                                <th class="px-4 py-3 text-end font-medium text-gray-600 dark:text-gray-300 w-16" v-if="payrollRun.status === 'draft'"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="item in payrollRun.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 group">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ item.employee?.name_ar }}</div>
                                    <div class="text-xs text-gray-500">{{ item.employee?.job_title?.name_ar }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono">{{ formatCurrency(item.base_salary) }}</td>
                                <td class="px-4 py-3 text-center font-mono text-emerald-600">
                                    +{{ formatCurrency(item.total_allowances) }}
                                    <div v-if="item.allowances_breakdown?.other > 0" class="text-[10px] text-gray-400">
                                        {{ $t('hr.financial.other_payments.title') }}: {{ formatCurrency(item.allowances_breakdown.other) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-red-600">
                                    -{{ formatCurrency(item.total_deductions) }}
                                    <div v-if="item.deductions_breakdown?.other > 0" class="text-[10px] text-gray-400">
                                        {{ $t('hr.financial.other_payments.title') }}: {{ formatCurrency(item.deductions_breakdown.other) }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-orange-600">-{{ formatCurrency(item.gosi_amount) }}</td>
                                <td class="px-4 py-3 text-center font-bold text-emerald-700 dark:text-emerald-400">{{ formatCurrency(item.net_salary) }}</td>
                                <td class="px-4 py-3 text-end" v-if="payrollRun.status === 'draft'">
                                    <button @click="deleteItem(item)" class="text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all p-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from '@/Composables/useToast';

const { success, error } = useToast();
const processing = ref(false);

const props = defineProps({
    payrollRun: Object,
});

const totalSalaries = computed(() => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.base_salary || 0), 0) || 0);
const totalDeductions = computed(() => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.total_deductions || 0), 0) || 0);
const totalNet = computed(() => props.payrollRun.items?.reduce((sum, i) => sum + parseFloat(i.net_salary || 0), 0) || 0);

function regeneratePayroll() {
    if (!confirm('هل أنت متأكد من إعادة إنشاء المسيّرة؟ سيتم حذف جميع البيانات الحالية وإعادة حسابها.')) return;
    
    processing.value = true;
    router.put(route('app.hr.payroll.regenerate', props.payrollRun.id), {}, {
        onSuccess: () => success('تم إعادة إنشاء المسيّرة بنجاح'),
        onFinish: () => processing.value = false,
    });
}

function approvePayroll() {
    processing.value = true;
    router.put(route('app.hr.payroll.approve', props.payrollRun.id), {}, {
        onSuccess: () => success('تمت الموافقة على المسيّرة'),
        onFinish: () => processing.value = false,
    });
}

function deleteItem(item) {
    if (!confirm('هل أنت متأكد من استبعاد هذا الموظف من المسيّرة؟')) return;
    
    router.delete(route('app.hr.payroll.items.destroy', [props.payrollRun.id, item.id]), {
        onSuccess: () => success('تم استبعاد الموظف بنجاح'),
    });
}

function markAsPaid() {
    processing.value = true;
    router.put(route('app.hr.payroll.mark-paid', props.payrollRun.id), {}, {
        onSuccess: () => success('تم تسجيل المسيّرة كمدفوعة'),
        onFinish: () => processing.value = false,
    });
}

function formatPeriod(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
}

function getStatusClass(status) {
    switch (status) {
        case 'draft': return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'approved': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'paid': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        default: return 'bg-gray-100 text-gray-600';
    }
}
</script>
