<template>
    <div class="space-y-6">
        <!-- Sub-tabs Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-1">
            <nav class="flex gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all',
                        activeTab === tab.key 
                            ? 'bg-violet-600 text-white shadow-sm' 
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                    ]"
                >
                    <span>{{ tab.icon }}</span>
                    <span>{{ $t(tab.label) }}</span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="min-h-[400px]">
            <BankInfoTab v-show="activeTab === 'bank'" :employee="employee" />
            <SalariesTab 
                v-show="activeTab === 'salaries'" 
                :employee="employee"
                :all-allowances="allAllowances"
                :all-deductions="allDeductions"
                @manageAllowances="showAllowancesModal = true"
                @manageDeductions="showDeductionsModal = true"
            />
            <PaymentHistoryTab 
                v-show="activeTab === 'history'" 
                :employee="employee"
                :payroll-items="payrollItems"
                :other-payments="otherPayments"
            />
        </div>

        <!-- Allowances Modal -->
        <Teleport to="body">
            <div v-if="showAllowancesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showAllowancesModal = false">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">💰 {{ $t('hr.financial.manage_allowances') }}</h3>
                        <button @click="showAllowancesModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Existing Allowances -->
                        <div v-for="(item, index) in selectedAllowances" :key="index" class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('hr.financial.allowance_type') }}</label>
                                    <!-- Searchable Select -->
                                    <div class="relative">
                                        <input 
                                            v-model="item.search"
                                            type="text"
                                            :placeholder="$t('common.search')"
                                            @focus="item.showDropdown = true"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                                        />
                                        <div v-if="item.id" class="absolute start-3 top-2 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-xs px-2 py-0.5 rounded">
                                            {{ getAllowanceById(item.id)?.name_ar }}
                                        </div>
                                        <!-- Dropdown -->
                                        <div v-if="item.showDropdown" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                            <div 
                                                v-for="a in getAvailableAllowances(item.id, item.search)"
                                                :key="a.id"
                                                @click="selectAllowance(index, a)"
                                                class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm"
                                            >
                                                {{ a.name_ar }}
                                                <span class="text-gray-500 text-xs ms-2">
                                                    ({{ a.is_flexible ? $t('hr.financial.flexible') : `${a.amount} ${a.type === 'percentage' ? '%' : $t('common.currency')}` }})
                                                </span>
                                            </div>
                                            <div v-if="!getAvailableAllowances(item.id, item.search).length" class="px-3 py-2 text-gray-500 text-sm">
                                                {{ $t('common.no_results') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button @click="removeAllowance(index)" class="mt-6 p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="item.id" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <!-- Period Type -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('hr.financial.period_type') }}</label>
                                    <select v-model="item.period_type" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                        <option value="indefinite">{{ $t('hr.financial.periods.indefinite') }}</option>
                                        <option value="fixed_period">{{ $t('hr.financial.periods.fixed_period') }}</option>
                                        <option value="one_time">{{ $t('hr.financial.periods.one_time') }}</option>
                                    </select>
                                </div>
                                <!-- Start Month -->
                                <div v-if="item.period_type !== 'indefinite'">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {{ $t('hr.financial.start_month') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="item.start_date" type="month" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" :class="{'border-red-500': !item.start_date && item.period_type !== 'indefinite'}" />
                                </div>
                                <!-- End Month -->
                                <div v-if="item.period_type === 'fixed_period'">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {{ $t('hr.financial.end_month') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="item.end_date" type="month" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" :class="{'border-red-500': !item.end_date && item.period_type === 'fixed_period'}" />
                                </div>
                                <!-- Custom Amount (only if flexible) -->
                                <div v-if="getAllowanceById(item.id)?.is_flexible">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {{ $t('common.amount') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="item.custom_amount" type="number" step="0.01" min="0" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" :class="{'border-red-500': !item.custom_amount}" />
                                </div>
                                <!-- Show fixed amount -->
                                <div v-else-if="getAllowanceById(item.id)">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('common.amount') }}</label>
                                    <div class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-600 dark:text-gray-400">
                                        {{ getAllowanceById(item.id)?.amount }} {{ getAllowanceById(item.id)?.type === 'percentage' ? '%' : $t('common.currency') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Button -->
                        <button @click="addAllowance" class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 hover:border-violet-400 hover:text-violet-600 transition-colors">
                            + {{ $t('hr.financial.add_allowance') }}
                        </button>
                    </div>
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button @click="showAllowancesModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $t('common.cancel') }}</button>
                        <button @click="saveAllowances" :disabled="savingAllowances" class="px-5 py-2.5 bg-violet-600 text-white rounded-xl font-medium disabled:opacity-50">
                            {{ savingAllowances ? $t('common.saving') : $t('common.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Deductions Modal -->
        <Teleport to="body">
            <div v-if="showDeductionsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showDeductionsModal = false">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">📉 {{ $t('hr.financial.manage_deductions') }}</h3>
                        <button @click="showDeductionsModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Existing Deductions -->
                        <div v-for="(item, index) in selectedDeductions" :key="index" class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('hr.financial.deduction_type') }}</label>
                                    <!-- Searchable Select -->
                                    <div class="relative">
                                        <input 
                                            v-model="item.search"
                                            type="text"
                                            :placeholder="$t('common.search')"
                                            @focus="item.showDropdown = true"
                                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                                        />
                                        <div v-if="item.id" class="absolute start-3 top-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs px-2 py-0.5 rounded">
                                            {{ getDeductionById(item.id)?.name_ar }}
                                        </div>
                                        <!-- Dropdown -->
                                        <div v-if="item.showDropdown" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                            <div 
                                                v-for="d in getAvailableDeductions(item.id, item.search)"
                                                :key="d.id"
                                                @click="selectDeduction(index, d)"
                                                class="px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm"
                                            >
                                                {{ d.name_ar }}
                                                <span class="text-gray-500 text-xs ms-2">
                                                    ({{ d.is_flexible ? $t('hr.financial.flexible') : `${d.amount} ${d.type === 'percentage' ? '%' : $t('common.currency')}` }})
                                                </span>
                                            </div>
                                            <div v-if="!getAvailableDeductions(item.id, item.search).length" class="px-3 py-2 text-gray-500 text-sm">
                                                {{ $t('common.no_results') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button @click="removeDeduction(index)" class="mt-6 p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                            <div v-if="item.id" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <!-- Period Type -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('hr.financial.period_type') }}</label>
                                    <select v-model="item.period_type" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                        <option value="indefinite">{{ $t('hr.financial.periods.indefinite') }}</option>
                                        <option value="fixed_period">{{ $t('hr.financial.periods.fixed_period') }}</option>
                                        <option value="one_time">{{ $t('hr.financial.periods.one_time') }}</option>
                                    </select>
                                </div>
                                <!-- Start Month -->
                                <div v-if="item.period_type !== 'indefinite'">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {{ $t('hr.financial.start_month') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="item.start_date" type="month" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" :class="{'border-red-500': !item.start_date && item.period_type !== 'indefinite'}" />
                                </div>
                                <!-- End Month -->
                                <div v-if="item.period_type === 'fixed_period'">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {{ $t('hr.financial.end_month') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="item.end_date" type="month" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" :class="{'border-red-500': !item.end_date && item.period_type === 'fixed_period'}" />
                                </div>
                                <!-- Custom Amount (only if flexible) -->
                                <div v-if="getDeductionById(item.id)?.is_flexible">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {{ $t('common.amount') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="item.custom_amount" type="number" step="0.01" min="0" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800" :class="{'border-red-500': !item.custom_amount}" />
                                </div>
                                <!-- Show fixed amount -->
                                <div v-else-if="getDeductionById(item.id)">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $t('common.amount') }}</label>
                                    <div class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg text-gray-600 dark:text-gray-400">
                                        {{ getDeductionById(item.id)?.amount }} {{ getDeductionById(item.id)?.type === 'percentage' ? '%' : $t('common.currency') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Button -->
                        <button @click="addDeduction" class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 hover:border-violet-400 hover:text-violet-600 transition-colors">
                            + {{ $t('hr.financial.add_deduction') }}
                        </button>
                    </div>
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button @click="showDeductionsModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $t('common.cancel') }}</button>
                        <button @click="saveDeductions" :disabled="savingDeductions" class="px-5 py-2.5 bg-violet-600 text-white rounded-xl font-medium disabled:opacity-50">
                            {{ savingDeductions ? $t('common.saving') : $t('common.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import BankInfoTab from '@/Components/HR/Tabs/Financial/BankInfoTab.vue';
import SalariesTab from '@/Components/HR/Tabs/Financial/SalariesTab.vue';
import PaymentHistoryTab from '@/Components/HR/Tabs/Financial/PaymentHistoryTab.vue';

const props = defineProps({
    employee: Object,
    allAllowances: Array,
    allDeductions: Array,
    payrollItems: { type: Array, default: () => [] },
    otherPayments: { type: Array, default: () => [] },
});

const { t } = useI18n();

const periodTypeOptions = [
    { value: 'indefinite', label: t('hr.financial.periods.indefinite') },
    { value: 'fixed_period', label: t('hr.financial.periods.fixed_period') },
    { value: 'one_time', label: t('hr.financial.periods.one_time') },
];

const { t: t_nav } = useI18n();
const tabs = [
    { key: 'bank', label: 'hr.financial.tabs.bank_info', icon: '🏦' },
    { key: 'salaries', label: 'hr.financial.tabs.salaries', icon: '💰' },
    { key: 'history', label: 'hr.financial.tabs.payment_history', icon: '📋' },
];

const activeTab = ref('salaries');

// Allowances Modal
const showAllowancesModal = ref(false);
const savingAllowances = ref(false);
const selectedAllowances = ref([]);

// Deductions Modal
const showDeductionsModal = ref(false);
const savingDeductions = ref(false);
const selectedDeductions = ref([]);

// Close dropdowns on click outside
function handleClickOutside(e) {
    if (!e.target.closest('.relative')) {
        selectedAllowances.value.forEach(item => item.showDropdown = false);
        selectedDeductions.value.forEach(item => item.showDropdown = false);
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

// Initialize from employee data
watch(() => props.employee, (emp) => {
    if (emp?.allowances) {
        selectedAllowances.value = emp.allowances.map(a => ({
            id: a.id,
            custom_amount: a.pivot?.custom_amount || null,
            period_type: a.pivot?.period_type || 'indefinite',
            start_date: a.pivot?.start_date?.slice(0, 7) || null,
            end_date: a.pivot?.end_date?.slice(0, 7) || null,
            search: '',
            showDropdown: false,
        }));
    }
    if (emp?.deductions) {
        selectedDeductions.value = emp.deductions.map(d => ({
            id: d.id,
            custom_amount: d.pivot?.custom_amount || null,
            period_type: d.pivot?.period_type || 'indefinite',
            start_date: d.pivot?.start_date?.slice(0, 7) || null,
            end_date: d.pivot?.end_date?.slice(0, 7) || null,
            search: '',
            showDropdown: false,
        }));
    }
}, { immediate: true, deep: true });

function getAllowanceById(id) {
    return props.allAllowances?.find(a => a.id === id);
}

function getDeductionById(id) {
    return props.allDeductions?.find(d => d.id === id);
}

// Get available allowances (exclude already selected)
function getAvailableAllowances(currentId, search) {
    const selectedIds = selectedAllowances.value.map(a => a.id).filter(id => id && id !== currentId);
    return props.allAllowances?.filter(a => {
        if (selectedIds.includes(a.id)) return false;
        if (search && !a.name_ar.includes(search) && !a.name_en?.includes(search)) return false;
        return true;
    }) || [];
}

function getAvailableDeductions(currentId, search) {
    const selectedIds = selectedDeductions.value.map(d => d.id).filter(id => id && id !== currentId);
    return props.allDeductions?.filter(d => {
        if (selectedIds.includes(d.id)) return false;
        if (search && !d.name_ar.includes(search) && !d.name_en?.includes(search)) return false;
        return true;
    }) || [];
}

function selectAllowance(index, allowance) {
    selectedAllowances.value[index].id = allowance.id;
    selectedAllowances.value[index].search = '';
    selectedAllowances.value[index].showDropdown = false;
}

function selectDeduction(index, deduction) {
    selectedDeductions.value[index].id = deduction.id;
    selectedDeductions.value[index].search = '';
    selectedDeductions.value[index].showDropdown = false;
}

function addAllowance() {
    selectedAllowances.value.push({
        id: '',
        custom_amount: null,
        period_type: 'indefinite',
        start_date: null,
        end_date: null,
        search: '',
        showDropdown: false,
    });
}

function removeAllowance(index) {
    selectedAllowances.value.splice(index, 1);
}

function addDeduction() {
    selectedDeductions.value.push({
        id: '',
        custom_amount: null,
        period_type: 'indefinite',
        start_date: null,
        end_date: null,
        search: '',
        showDropdown: false,
    });
}

function removeDeduction(index) {
    selectedDeductions.value.splice(index, 1);
}

function saveAllowances() {
    // Validate required fields
    const validItems = selectedAllowances.value.filter(a => a.id);
    for (const item of validItems) {
        // Validate start_date for non-indefinite periods
        if (item.period_type !== 'indefinite' && !item.start_date) {
            alert('يرجى تحديد شهر البداية');
            return;
        }
        // Validate end_date for fixed_period
        if (item.period_type === 'fixed_period' && !item.end_date) {
            alert('يرجى تحديد شهر النهاية');
            return;
        }
        // Validate custom_amount for flexible allowances
        const allowance = getAllowanceById(item.id);
        if (allowance?.is_flexible && !item.custom_amount) {
            alert('يرجى تحديد المبلغ للبدل المرن');
            return;
        }
    }

    savingAllowances.value = true;
    const data = {
        allowances: validItems.map(a => ({
            id: a.id,
            custom_amount: a.custom_amount,
            period_type: a.period_type,
            start_date: a.start_date ? a.start_date + '-01' : null,
            end_date: a.end_date ? a.end_date + '-01' : null,
        })),
    };
    router.put(route('app.hr.employees.allowances.update', props.employee.id), data, {
        preserveScroll: true,
        onFinish: () => {
            savingAllowances.value = false;
            showAllowancesModal.value = false;
        },
    });
}

function saveDeductions() {
    // Validate required fields
    const validItems = selectedDeductions.value.filter(d => d.id);
    for (const item of validItems) {
        // Validate start_date for non-indefinite periods
        if (item.period_type !== 'indefinite' && !item.start_date) {
            alert('يرجى تحديد شهر البداية');
            return;
        }
        // Validate end_date for fixed_period
        if (item.period_type === 'fixed_period' && !item.end_date) {
            alert('يرجى تحديد شهر النهاية');
            return;
        }
        // Validate custom_amount for flexible deductions
        const deduction = getDeductionById(item.id);
        if (deduction?.is_flexible && !item.custom_amount) {
            alert('يرجى تحديد المبلغ للحسم المرن');
            return;
        }
    }

    savingDeductions.value = true;
    const data = {
        deductions: validItems.map(d => ({
            id: d.id,
            custom_amount: d.custom_amount,
            period_type: d.period_type,
            start_date: d.start_date ? d.start_date + '-01' : null,
            end_date: d.end_date ? d.end_date + '-01' : null,
        })),
    };
    router.put(route('app.hr.employees.deductions.update', props.employee.id), data, {
        preserveScroll: true,
        onFinish: () => {
            savingDeductions.value = false;
            showDeductionsModal.value = false;
        },
    });
}
</script>
