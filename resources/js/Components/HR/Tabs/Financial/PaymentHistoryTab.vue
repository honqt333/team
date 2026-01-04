<template>
    <div class="space-y-6">
        <!-- Sub-tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button
                @click="activeSubTab = 'monthly'"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    activeSubTab === 'monthly' 
                        ? 'border-violet-600 text-violet-600 dark:border-violet-400 dark:text-violet-400' 
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                ]"
            >
                💵 {{ $t('hr.financial.payroll.monthly_salaries') }}
            </button>
            <button
                @click="activeSubTab = 'other'"
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    activeSubTab === 'other' 
                        ? 'border-violet-600 text-violet-600 dark:border-violet-400 dark:text-violet-400' 
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                ]"
            >
                💳 {{ $t('hr.financial.other_payments.title') }}
            </button>
        </div>

        <!-- Monthly Salaries Table -->
        <div v-show="activeSubTab === 'monthly'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.financial.payroll.period') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.financial.payroll.base_salary') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.employees.total_allowances') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.employees.total_deductions') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.financial.payroll.net_salary') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.financial.payroll.created_by') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('common.details') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(item, index) in payrollItems" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ index + 1 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">{{ item.period_label }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ parseFloat(item.base_salary).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400">+{{ parseFloat(item.total_allowances).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">-{{ parseFloat(item.total_deductions).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400">{{ parseFloat(item.net_salary).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ item.created_by?.name || '-' }}</td>
                            <td class="px-4 py-3">
                                <button
                                    @click="openPaySlip(item)"
                                    class="text-violet-600 dark:text-violet-400 hover:underline text-sm"
                                >
                                    {{ $t('common.view') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!payrollItems.length">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ $t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Other Payments Table (View Only) -->
        <div v-show="activeSubTab === 'other'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('common.type') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.financial.other_payments.payment_title') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('common.amount') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('common.notes') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('common.status') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ $t('hr.financial.payroll.created_by') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(payment, index) in otherPayments" :key="payment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ index + 1 }}</td>
                            <td class="px-4 py-3">
                                <span :class="getTypeClass(payment.type)" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ $t('hr.financial.other_payments.types.' + payment.type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ payment.title }}</td>
                            <td class="px-4 py-3 text-sm font-medium" :class="isPositiveType(payment.type) ? 'text-emerald-600' : 'text-red-600'">
                                {{ isPositiveType(payment.type) ? '+' : '-' }}{{ parseFloat(payment.amount).toLocaleString() }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ payment.notes || '-' }}</td>
                            <td class="px-4 py-3">
                                <span :class="getStatusClass(payment.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ payment.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ payment.created_by?.name || '-' }}</td>
                        </tr>
                        <tr v-if="!otherPayments.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ $t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pay Slip Modal -->
        <PaySlipModal
            v-if="selectedPayrollItem"
            :show="showPaySlip"
            :payroll-item="selectedPayrollItem"
            :employee="employee"
            @close="showPaySlip = false"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import PaySlipModal from '@/Components/HR/Modals/PaySlipModal.vue';

const props = defineProps({
    employee: Object,
    payrollItems: {
        type: Array,
        default: () => [],
    },
    otherPayments: {
        type: Array,
        default: () => [],
    },
});

const activeSubTab = ref('monthly');
const showPaySlip = ref(false);
const selectedPayrollItem = ref(null);

function openPaySlip(item) {
    selectedPayrollItem.value = item;
    showPaySlip.value = true;
}

function isPositiveType(type) {
    return ['bonus', 'compensation'].includes(type);
}

function getTypeClass(type) {
    const classes = {
        advance: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        bonus: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        compensation: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        penalty: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        other: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[type] || classes.other;
}

function getStatusClass(status) {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
    };
    return classes[status] || classes.pending;
}
</script>
