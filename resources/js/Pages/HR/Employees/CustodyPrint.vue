<template>
    <Head :title="$t('hr.custody.print_title')" />

    <StandardReportLayout
        :title="$t('hr.custody.print_title')"
        :custom-company="tenant"
        :on-print="print"
        default-orientation="portrait"
        back-route="app.hr.employees.show"
        :back-params="{ employee: employee.id, tab: 'custody' }"
    >
        <div class="space-y-6 text-sm text-gray-900">
            <!-- Employee Info Box -->
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
                        <span class="text-gray-500">{{ $t('common.department') }}:</span>
                        <span class="ms-2">
                            {{ employee.department ? getName(employee.department) : '—' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Custody Details Table -->
            <table class="w-full border-collapse border border-gray-300">
                <tbody>
                    <tr class="border-b border-gray-200">
                        <th
                            class="py-2.5 px-3 bg-gray-100 text-start w-1/3 border-e border-gray-300"
                        >
                            {{ $t('hr.custody.asset_name') }}
                        </th>
                        <td class="py-2.5 px-3 font-bold">{{ custody.asset_name }}</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('hr.custody.category') }}
                        </th>
                        <td class="py-2.5 px-3">
                            {{ $t(`hr.custody.categories.${custody.category}`) }}
                        </td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('hr.custody.serial_number') }}
                        </th>
                        <td class="py-2.5 px-3 font-mono">{{ custody.serial_number || '—' }}</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('hr.custody.estimated_value') }}
                        </th>
                        <td class="py-2.5 px-3 font-mono">
                            {{ formatMoney(custody.estimated_value || 0) }}
                        </td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('hr.custody.issued_date') }}
                        </th>
                        <td class="py-2.5 px-3 font-mono">{{ custody.issued_date }}</td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('hr.custody.condition') }}
                        </th>
                        <td class="py-2.5 px-3">
                            {{ $t(`hr.custody.conditions.${custody.condition_on_issue}`) }}
                        </td>
                    </tr>
                    <tr class="border-b border-gray-200">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('common.status') }}
                        </th>
                        <td class="py-2.5 px-3 font-bold">
                            {{ $t(`hr.custody.statuses.${custody.status}`) }}
                        </td>
                    </tr>
                    <tr v-if="custody.returned_date" class="border-b border-gray-200">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('hr.custody.returned_date') }}
                        </th>
                        <td class="py-2.5 px-3 font-mono">{{ custody.returned_date }}</td>
                    </tr>
                    <tr v-if="custody.notes">
                        <th class="py-2.5 px-3 bg-gray-100 text-start border-e border-gray-300">
                            {{ $t('common.notes') }}
                        </th>
                        <td class="py-2.5 px-3">{{ custody.notes }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Declaration -->
            <p
                class="text-xs leading-relaxed text-gray-700 border border-gray-200 rounded-lg p-3 bg-gray-50"
            >
                {{ $t('hr.custody.receipt_declaration') }}
            </p>

            <!-- Signatures -->
            <div class="grid grid-cols-2 gap-6 pt-4">
                <div class="border border-gray-300 rounded-xl p-4 text-center space-y-2">
                    <p class="font-bold text-xs text-gray-700">
                        {{ $t('hr.custody.issue_signature') }}
                    </p>
                    <img
                        v-if="custody.issue_signature"
                        :src="custody.issue_signature"
                        alt="Issue Signature"
                        class="h-24 mx-auto object-contain"
                    />
                    <p v-else class="text-xs text-gray-400 py-8">
                        {{ $t('hr.custody.no_signature') }}
                    </p>
                    <p v-if="custody.creator?.name" class="text-[11px] text-gray-500">
                        {{ $t('hr.custody.issued_by') }}: {{ custody.creator.name }}
                    </p>
                </div>

                <div class="border border-gray-300 rounded-xl p-4 text-center space-y-2">
                    <p class="font-bold text-xs text-gray-700">
                        {{ $t('hr.custody.return_signature') }}
                    </p>
                    <img
                        v-if="custody.return_signature"
                        :src="custody.return_signature"
                        alt="Return Signature"
                        class="h-24 mx-auto object-contain"
                    />
                    <p v-else class="text-xs text-gray-400 py-8">
                        {{ $t('hr.custody.no_signature') }}
                    </p>
                    <p v-if="custody.returner?.name" class="text-[11px] text-gray-500">
                        {{ $t('hr.custody.returned_by') }}: {{ custody.returner.name }}
                    </p>
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
    employee: {
        type: Object,
        required: true,
    },
    custody: {
        type: Object,
        required: true,
    },
    tenant: {
        type: Object,
        default: null,
    },
});

const { formatMoney } = useNumberFormat();
const { getName } = useLocalized();

function print() {
    window.print();
}
</script>
