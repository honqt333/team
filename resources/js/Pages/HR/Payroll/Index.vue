<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('hr.payroll.title')"
                :subtitle="$t('hr.payroll.subtitle')"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-600"
                glowFrom="from-emerald-500"
                badgeBg="bg-emerald-50/50 dark:bg-emerald-900/30"
                badgeText="text-emerald-600 dark:text-emerald-400"
                badgeBorder="border-emerald-100/50 dark:border-emerald-800/30"
                badgeDot="bg-emerald-500"
            >
                <template #back>
                    <Link :href="route('app.hr.index')"
                        class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 shadow-md transition-all border border-gray-100 dark:border-gray-700 group/back"
                        :title="$t('common.back')">
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover/back:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </template>

                <template #actions>
                    <button
                        v-if="can('hr.payroll.create') || isAnyAdmin()"
                        @click="showGenerateModal = true"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl font-black shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all group/add"
                    >
                        <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <span class="tracking-tight uppercase">{{ $t('hr.payroll.generate') }}</span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex gap-4 border-b border-gray-100 dark:border-gray-700/50">
                        <button 
                            @click="activeTab = 'payroll'"
                            class="pb-3 px-4 text-xs font-black uppercase tracking-widest transition-all relative group"
                            :class="activeTab === 'payroll' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500'"
                        >
                            {{ $t('hr.payroll.monthly_salaries') }}
                            <div v-if="activeTab === 'payroll'" class="absolute bottom-0 left-0 w-full h-1 bg-emerald-600 dark:bg-emerald-400 rounded-t-full shadow-[0_-2px_8px_rgba(16,185,129,0.4)]"></div>
                        </button>
                        <button 
                            @click="fetchOtherPayments(false)"
                            class="pb-3 px-4 text-xs font-black uppercase tracking-widest transition-all relative group"
                             :class="activeTab === 'other_payments' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500'"
                        >
                            {{ $t('hr.financial.other_payments.title') }}
                            <div v-if="activeTab === 'other_payments'" class="absolute bottom-0 left-0 w-full h-1 bg-emerald-600 dark:bg-emerald-400 rounded-t-full shadow-[0_-2px_8px_rgba(16,185,129,0.4)]"></div>
                        </button>
                    </div>
                </template>
            </PageHeader>


            <!-- Tab Content -->
            <div v-if="activeTab === 'payroll'">

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
                                        <span :class="[
                                            'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border shadow-sm transition-all',
                                            getStatusClass(run.status)
                                        ]">
                                            {{ $t(`hr.payroll.statuses.${run.status}`) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold text-gray-500">
                                                {{ run.created_by?.name?.charAt(0) }}
                                            </div>
                                            <span class="text-xs text-gray-600 dark:text-gray-400 font-bold">{{ run.created_by?.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1.5 p-1 bg-gray-50/50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700/50 w-fit ms-auto">
                                            <Link 
                                                :href="route('app.hr.payroll.show', run.id)" 
                                                class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-white dark:hover:bg-gray-800 rounded-lg transition-all shadow-sm hover:shadow-md"
                                                :title="$t('common.view')"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>
                                            <a 
                                                :href="route('app.hr.payroll.print', run.id)" 
                                                target="_blank" 
                                                class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-white dark:hover:bg-gray-800 rounded-lg transition-all shadow-sm hover:shadow-md"
                                                :title="$t('common.print')"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
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
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1 px-1">{{ $t('hr.payroll.period_start') }}</label>
                        <CustomDatePicker v-model="generateForm.period_start" class="!rounded-2xl" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1 px-1">{{ $t('hr.payroll.period_end') }}</label>
                        <CustomDatePicker v-model="generateForm.period_end" class="!rounded-2xl" />
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1 px-1">{{ $t('hr.payroll.payment_date') }}</label>
                    <CustomDatePicker v-model="generateForm.payment_date" class="!rounded-2xl" />
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
import PageHeader from '@/Components/PageHeader.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';
import { usePermission } from '@/Composables/usePermission';
import OtherPaymentsTab from './Tabs/OtherPaymentsTab.vue';
import axios from 'axios';

const { success } = useToast();
const { can, isAnyAdmin } = usePermission();
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
        case 'draft': return 'bg-orange-50 text-orange-700 border-orange-100 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800/30';
        case 'approved': return 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/30';
        case 'paid': return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/30';
        default: return 'bg-gray-50 text-gray-700 border-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600';
    }
}
</script>
