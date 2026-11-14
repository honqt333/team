<template>
    <Head :title="$t('hr.offboarding.print_title')" />

    <StandardReportLayout
        :title="$t('hr.offboarding.print_title')"
        :custom-company="tenant"
        :on-print="print"
        default-orientation="portrait"
        back-route="app.hr.employees.show"
        :back-params="{ employee: employee.id, tab: 'offboarding' }"
    >
        <div class="space-y-6 text-sm text-gray-900">
            <!-- Employee Information -->
            <div class="border border-gray-300 rounded-xl p-4 bg-gray-50">
                <h2 class="font-bold text-base mb-3 border-b border-gray-200 pb-2">
                    {{ $t('hr.custody.employee_info') }}
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <span class="text-gray-500">{{ $t('common.name') }}:</span>
                        <span class="ms-2 font-bold">
                            {{ getName(employee) || employee.display_name || employee.name_ar }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">{{ $t('hr.employees.number') }}:</span>
                        <span class="ms-2 font-mono font-bold">{{ employee.employee_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">{{ $t('hr.employees.job_title') }}:</span>
                        <span class="ms-2">
                            {{ employee.job_title ? getName(employee.job_title) : '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">{{ $t('hr.offboarding.hire_date') }}:</span>
                        <span class="ms-2 font-mono">{{ offboarding.hire_date }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">
                            {{ $t('hr.offboarding.termination_date') }}:
                        </span>
                        <span class="ms-2 font-mono">{{ offboarding.termination_date }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">
                            {{ $t('hr.offboarding.termination_type') }}:
                        </span>
                        <span class="ms-2 font-semibold">
                            {{ $t(`hr.offboarding.types.${offboarding.termination_type}`) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financial Settlement Table -->
            <table class="w-full border-collapse border border-gray-300">
                <tbody>
                    <tr>
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start w-1/2">
                            {{ $t('hr.offboarding.service_duration') }}
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono">
                            {{ toEnglish(Number(offboarding.service_years).toFixed(2)) }}
                            {{ $t('hr.offboarding.years_unit') }}
                            ({{ toEnglish(offboarding.service_days) }}
                            {{ $t('hr.offboarding.days_unit') }})
                        </td>
                    </tr>
                    <tr>
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start">
                            {{ $t('hr.offboarding.calculation_salary') }}
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono">
                            {{ formatMoney(offboarding.calculation_salary) }}
                        </td>
                    </tr>
                    <tr>
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start">
                            {{ $t('hr.offboarding.eosb_amount') }}
                            ({{ toEnglish(Math.round(offboarding.eosb_multiplier * 100)) }}%)
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono font-bold">
                            + {{ formatMoney(offboarding.eosb_amount) }}
                        </td>
                    </tr>
                    <tr>
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start">
                            {{ $t('hr.offboarding.unused_leave_payout') }}
                            ({{ toEnglish(offboarding.unused_leave_days) }}
                            {{ $t('hr.offboarding.days_unit') }})
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono">
                            + {{ formatMoney(offboarding.unused_leave_payout) }}
                        </td>
                    </tr>
                    <tr v-if="offboarding.other_additions > 0">
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start">
                            {{ $t('hr.offboarding.other_additions') }}
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono">
                            + {{ formatMoney(offboarding.other_additions) }}
                        </td>
                    </tr>
                    <tr v-if="offboarding.advances_deduction > 0">
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start">
                            {{ $t('hr.offboarding.advances_deduction') }}
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono">
                            - {{ formatMoney(offboarding.advances_deduction) }}
                        </td>
                    </tr>
                    <tr v-if="offboarding.custody_deduction > 0">
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start">
                            {{ $t('hr.offboarding.custody_deduction') }}
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono">
                            - {{ formatMoney(offboarding.custody_deduction) }}
                        </td>
                    </tr>
                    <tr v-if="offboarding.other_deductions > 0">
                        <th class="border border-gray-300 bg-gray-100 p-2.5 text-start">
                            {{ $t('hr.offboarding.other_deductions') }}
                        </th>
                        <td class="border border-gray-300 p-2.5 font-mono">
                            - {{ formatMoney(offboarding.other_deductions) }}
                        </td>
                    </tr>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 p-3 text-start text-base font-black">
                            {{ $t('hr.offboarding.net_settlement') }}
                        </th>
                        <td class="border border-gray-300 p-3 font-mono text-base font-black">
                            {{ formatMoney(offboarding.net_settlement) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Declaration -->
            <div class="border border-gray-300 rounded-xl p-4 bg-gray-50 text-xs leading-relaxed">
                {{ $t('hr.offboarding.clearance_declaration') }}
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-2 gap-6 pt-4">
                <div class="border border-gray-300 rounded-xl p-4 text-center">
                    <p class="font-bold mb-2">{{ $t('hr.offboarding.employee_signature') }}</p>
                    <div class="h-24 flex items-center justify-center">
                        <img
                            v-if="offboarding.employee_signature"
                            :src="offboarding.employee_signature"
                            class="max-h-20 object-contain"
                        />
                        <span v-else class="text-xs text-gray-400">__________________</span>
                    </div>
                </div>
                <div class="border border-gray-300 rounded-xl p-4 text-center">
                    <p class="font-bold mb-2">{{ $t('hr.offboarding.hr_signature') }}</p>
                    <div class="h-24 flex items-center justify-center">
                        <img
                            v-if="offboarding.hr_signature"
                            :src="offboarding.hr_signature"
                            class="max-h-20 object-contain"
                        />
                        <span v-else class="text-xs text-gray-400">__________________</span>
                    </div>
                </div>
            </div>
        </div>
    </StandardReportLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import StandardReportLayout from '@/Components/Print/StandardReportLayout.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useLocalized } from '@/Composables/useLocalized';

defineProps({
    employee: { type: Object, required: true },
    offboarding: { type: Object, required: true },
    tenant: { type: Object, default: null },
});

const { formatMoney, toEnglish } = useNumberFormat();
const { getName } = useLocalized();

const print = () => {
    window.print();
};
</script>
