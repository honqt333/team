<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('app.hr.index')" class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Link>
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('hr.payroll.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('hr.payroll.subtitle') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-4 mt-8 border-b border-gray-200 dark:border-gray-700">
                    <button 
                        @click="activeTab = 'payroll'"
                        class="pb-3 px-4 text-sm font-medium transition-colors relative"
                        :class="activeTab === 'payroll' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    >
                        {{ $t('hr.payroll.monthly_salaries') }}
                        <div v-if="activeTab === 'payroll'" class="absolute bottom-0 left-0 w-full h-0.5 bg-emerald-600 dark:bg-emerald-400 rounded-t-full"></div>
                    </button>
                    <button 
                        @click="fetchOtherPayments"
                        class="pb-3 px-4 text-sm font-medium transition-colors relative"
                         :class="activeTab === 'other_payments' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    >
                        {{ $t('hr.financial.other_payments.title') }}
                        <div v-if="activeTab === 'other_payments'" class="absolute bottom-0 left-0 w-full h-0.5 bg-emerald-600 dark:bg-emerald-400 rounded-t-full"></div>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div v-if="activeTab === 'payroll'">
                 <div class="flex justify-end mb-4">
                     <button
                        @click="showGenerateModal = true"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('hr.payroll.generate') }}
                    </button>
                 </div>

                <!-- Payroll Runs List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-4 text-start font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.period') }}</th>
                                    <th class="px-6 py-4 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.employees_count') }}</th>
                                    <th class="px-6 py-4 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.total_net') }}</th>
                                    <th class="px-6 py-4 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('hr.payroll.status') }}</th>
                                    <th class="px-6 py-4 text-center font-medium text-gray-600 dark:text-gray-300">{{ $t('common.created_by') }}</th>
                                    <th class="px-6 py-4 text-end font-medium text-gray-600 dark:text-gray-300">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="run in payrollRuns.data" :key="run.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ formatPeriod(run.period_start, run.period_end) }}
                                        </div>
                                        <div class="text-xs text-gray-500" v-if="run.payment_date">
                                            {{ $t('hr.payroll.payment_date') }}: {{ formatDate(run.payment_date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-bold text-gray-900 dark:text-white">{{ run.items_count }}</span>
                                        <span class="text-gray-500"> {{ $t('hr.employees.employee') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ formatCurrency(run.items_sum_net_salary || 0) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="getStatusClass(run.status)" class="px-3 py-1 rounded-full text-xs font-bold">
                                            {{ $t(`hr.payroll.statuses.${run.status}`) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-500">
                                        {{ run.created_by?.name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('app.hr.payroll.show', run.id)" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                                👁️
                                            </Link>
                                            <a :href="route('app.hr.payroll.print', run.id)" target="_blank" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                                🖨️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!payrollRuns.data?.length">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <span class="text-4xl block mb-4">💵</span>
                                        {{ $t('hr.payroll.no_payrolls') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Other Payments Tab Content -->
            <div v-if="activeTab === 'other_payments'">
                <OtherPaymentsTab 
                    :payments="otherPaymentsData" 
                    :employees="employees" 
                    :centers="centers"
                    :loading="loadingOtherPayments"
                    :selectedCenter="selectedCenter"
                    @refresh="fetchOtherPayments"
                    @center-change="onCenterChange"
                />
            </div>
        </div>

        <!-- Generate Modal -->
        <BaseModal :show="showGenerateModal" @close="showGenerateModal = false" :title="$t('hr.payroll.generate')">
            <form @submit.prevent="generatePayroll" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('hr.payroll.period_start') }}</label>
                        <input type="date" v-model="generateForm.period_start" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('hr.payroll.period_end') }}</label>
                        <input type="date" v-model="generateForm.period_end" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('hr.payroll.payment_date') }}</label>
                    <input type="date" v-model="generateForm.payment_date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t dark:border-gray-700">
                    <button type="button" @click="showGenerateModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                        {{ $t('common.cancel') }}
                    </button>
                    <button type="submit" :disabled="generating" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                        {{ generating ? $t('common.processing') : $t('hr.payroll.generate') }}
                    </button>
                </div>
            </form>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';
import OtherPaymentsTab from './Tabs/OtherPaymentsTab.vue';
import axios from 'axios';

const { success } = useToast();
const page = usePage();

const props = defineProps({
    payrollRuns: Object,
    employees: Array, // All employees from all centers
    centers: Array, // Centers list for filter dropdown
});

const activeTab = ref('payroll');
const showGenerateModal = ref(false);
const generating = ref(false);
const otherPaymentsData = ref({});
const loadingOtherPayments = ref(false);
const selectedCenter = ref(''); // empty string = all centers

function onCenterChange(centerId) {
    selectedCenter.value = centerId;
    fetchOtherPayments(true);
}

const generateForm = ref({
    period_start: getFirstDayOfMonth(),
    period_end: getLastDayOfMonth(),
    payment_date: '',
});

async function fetchOtherPayments(force = false) {
    console.log('[DEBUG] fetchOtherPayments called with force:', force, 'center:', selectedCenter.value);
    activeTab.value = 'other_payments';
    try {
        if (!force && otherPaymentsData.value.data) {
            console.log('[DEBUG] Skipping fetch - already loaded:', otherPaymentsData.value);
            return;
        }
        
        loadingOtherPayments.value = true;
        console.log('[DEBUG] Fetching other payments...');
        const response = await axios.get(route('app.hr.payroll.other-payments.index'), {
            params: { center_id: selectedCenter.value }
        });
        console.log('[DEBUG] Response received:', response.data);
        otherPaymentsData.value = response.data;
        console.log('[DEBUG] otherPaymentsData updated:', otherPaymentsData.value);
    } catch (e) {
        console.error("[DEBUG] Failed to fetch other payments", e);
    } finally {
        loadingOtherPayments.value = false;
    }
}

function getFirstDayOfMonth() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
}

function getLastDayOfMonth() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
}

function generatePayroll() {
    generating.value = true;
    router.post(route('app.hr.payroll.generate'), generateForm.value, {
        onSuccess: () => {
            showGenerateModal.value = false;
            success('تم إنشاء المسيّرة بنجاح');
        },
        onFinish: () => {
            generating.value = false;
        },
    });
}

function formatPeriod(start, end) {
    const startDate = new Date(start);
    return startDate.toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-CA');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount);
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
