<template>
    <div class="p-8 max-w-6xl mx-auto bg-white min-h-screen text-gray-900" dir="rtl">
        <Head :title="$t('customers.print_title')" />

        <!-- Print Controls -->
        <div
            class="print:hidden mb-6 flex justify-between items-center bg-gray-50 p-4 rounded-2xl border border-gray-200"
        >
            <div class="flex items-center gap-2">
                <button
                    @click="handlePrint"
                    class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-bold flex items-center gap-2 shadow-md hover:from-indigo-700 hover:to-purple-700 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                        />
                    </svg>
                    <span>{{ $t('common.print') }}</span>
                </button>
            </div>
            <button
                @click="handleBack"
                class="px-4 py-2 bg-white text-gray-700 border border-gray-200 rounded-xl hover:bg-gray-100 font-bold transition-all"
            >
                {{ $t('common.close') }}
            </button>
        </div>

        <!-- AUDIT_STANDARDS.md Section 5: Standard Official 3-Column Header -->
        <div class="mb-6 border-b-2 border-gray-900 pb-4">
            <div class="grid grid-cols-3 items-center text-xs">
                <!-- Column 1: Company Info -->
                <div class="text-start space-y-1">
                    <h1 class="text-base font-black text-black">{{ companyInfo.name }}</h1>
                    <p v-if="companyInfo.center_name" class="font-bold text-gray-700">
                        {{ $t('common.branch_label') }} {{ companyInfo.center_name }}
                    </p>
                    <p v-if="companyInfo.phone" class="text-gray-600">
                        {{ $t('common.phone_label') }}
                        <span class="font-mono" dir="ltr">{{ companyInfo.phone }}</span>
                    </p>
                    <p v-if="companyInfo.vat_number" class="text-gray-600">
                        {{ $t('common.tax_number_label') }}
                        <span class="font-mono font-bold">{{ companyInfo.vat_number }}</span>
                    </p>
                    <p v-if="companyInfo.cr_number" class="text-gray-600">
                        {{ $t('common.commercial_registration_label') }}
                        <span class="font-mono">{{ companyInfo.cr_number }}</span>
                    </p>
                </div>

                <!-- Column 2 (Center): Logo & Title -->
                <div class="text-center flex flex-col items-center justify-center">
                    <div v-if="companyInfo.logo_path" class="w-16 h-16 mb-1">
                        <img
                            :src="'/storage/' + companyInfo.logo_path"
                            class="w-full h-full object-contain"
                        />
                    </div>
                    <div v-else class="text-center py-1">
                        <span class="text-base font-black tracking-wide text-black">
                            {{ companyInfo.name }}
                        </span>
                    </div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-black">
                        {{ $t('customers.print.title') }}
                    </h2>
                </div>

                <!-- Column 3: Metadata -->
                <div class="text-end space-y-1 text-xs text-gray-600">
                    <p class="font-bold text-black">
                        {{ $t('customers.print.total_customers') }}
                        <span class="font-mono font-black text-sm">{{ customers.length }}</span>
                    </p>
                    <p>
                        {{ $t('customers.print.report_date') }}
                        <span class="font-mono">{{ reportDateFormatted }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-xs text-center border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b-2 border-gray-800 text-gray-700">
                    <th class="py-2.5 px-2 font-bold w-10 text-center">#</th>
                    <th class="py-2.5 px-3 font-bold text-center">
                        {{ $t('customers.columns.name') }}
                    </th>
                    <th class="py-2.5 px-2 font-bold text-center">{{ $t('common.type') }}</th>
                    <th class="py-2.5 px-3 font-bold text-center">
                        {{ $t('customers.print.contact_person') }}
                    </th>
                    <th class="py-2.5 px-3 font-bold text-center">
                        {{ $t('system.admin_users.form.phone') }}
                    </th>
                    <th class="py-2.5 px-2 font-bold text-center">
                        {{ $t('customers.vehicles') }}
                    </th>
                    <th class="py-2.5 px-2 font-bold text-center">{{ $t('work_orders.title') }}</th>
                    <th class="py-2.5 px-3 font-bold text-center">
                        {{ $t('customers.balance_due') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <tr
                    v-for="(customer, index) in customers"
                    :key="customer.id"
                    class="break-inside-avoid"
                >
                    <td class="py-2 px-2 font-mono text-gray-500 text-center">{{ index + 1 }}</td>
                    <td class="py-2 px-3 font-bold text-gray-900 text-center">
                        {{ customer.name }}
                    </td>
                    <td class="py-2 px-2 text-center">
                        <span
                            class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                            :class="
                                customer.type === 'company'
                                    ? 'bg-blue-100 text-blue-800'
                                    : 'bg-emerald-100 text-emerald-800'
                            "
                        >
                            {{
                                customer.type === 'company'
                                    ? $t('customers.type.company')
                                    : $t('customers.form.individual')
                            }}
                        </span>
                    </td>
                    <td class="py-2 px-3 text-center text-gray-600">
                        {{ customer.contact_name || '—' }}
                    </td>
                    <td class="py-2 px-3 font-mono text-center" dir="ltr">{{ customer.phone }}</td>
                    <td class="py-2 px-2 font-mono text-center font-bold">
                        {{ customer.vehicles_count || 0 }}
                    </td>
                    <td class="py-2 px-2 font-mono text-center font-bold">
                        {{ customer.work_orders_count || 0 }}
                    </td>
                    <td
                        class="py-2 px-3 font-mono font-bold text-center"
                        :class="customer.balance > 0 ? 'text-red-700' : 'text-gray-900'"
                    >
                        {{ formatMoney(customer.balance || 0) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Official Print Footer (AUDIT_STANDARDS.md Section 5) -->
        <div class="mt-8 pt-4 border-t border-gray-400 text-center text-xs text-gray-600">
            <p>
                Prepared by:
                <span class="font-bold text-black">
                    {{ $page.props.auth?.user?.name || 'Administrator' }}
                </span>
                | Printed on:
                <span class="font-mono">{{ reportDateFormatted }}</span>
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
    },
    companyInfo: {
        type: Object,
        default: () => ({
            name: '',
            vat_number: '',
            cr_number: '',
            logo_path: '',
            center_name: '',
            phone: '',
        }),
    },
});

const reportDateFormatted = computed(() => {
    return new Date().toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const formatMoney = (val) => {
    const num = Number(val) || 0;
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ' SAR'
    );
};

const handlePrint = () => {
    window.print();
};

const handleBack = () => {
    window.close();
};

onMounted(() => {
    // Auto-trigger print when opened in new tab/window if requested
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
        setTimeout(() => {
            window.print();
        }, 500);
    }
});
</script>

<style scoped>
@media print {
    body {
        background: white !important;
        color: black !important;
        font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }
    th,
    td {
        border: 1px solid #9ca3af !important;
        padding: 6px 4px !important;
        text-align: center !important;
    }
    th {
        background-color: #f3f4f6 !important;
        font-weight: bold !important;
    }
}
</style>
