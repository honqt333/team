<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">مشتريات WhatsApp</h1>
                <p class="text-gray-500 dark:text-gray-400">سجل شراء رصيد رسائل واتساب</p>
            </div>

            <!-- Navigation Tabs (mirrors WhatsappUsage) -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <a
                    href="/system/whatsapp/packages"
                    class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                >
                    الباقات
                </a>
                <a
                    href="/system/whatsapp/balances"
                    class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                >
                    الأرصدة
                </a>
                <a
                    href="/system/whatsapp/purchases"
                    class="px-4 py-2 border-b-2 border-emerald-600 text-emerald-600 font-medium"
                >
                    المشتريات
                </a>
                <a
                    href="/system/whatsapp/usage"
                    class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                >
                    سجل الاستخدام
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">مدفوع</p>
                    <p class="text-2xl font-bold text-emerald-600">
                        {{ stats.paid?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">معلق</p>
                    <p class="text-2xl font-bold text-amber-600">
                        {{ stats.pending?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">فشل</p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ stats.failed?.toLocaleString() }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">إيرادات</p>
                    <p class="text-2xl font-bold text-indigo-600">
                        {{ formatCurrency(stats.total_revenue) }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">رصيد مبيع</p>
                    <p class="text-2xl font-bold text-purple-600">
                        {{ stats.total_credits_sold?.toLocaleString() }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="بحث بالمستأجر أو رقم العملية..."
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <SearchableSelect
                        v-model="status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="كل الحالات"
                        compact
                    />
                </div>
            </div>

            <!-- Purchases Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    المستأجر
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    الباقة
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    الرصيد
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    المبلغ
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    الحالة
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    طريقة الدفع
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    رقم العملية
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400"
                                >
                                    التاريخ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="purchase in purchases.data"
                                :key="purchase.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">
                                    {{ purchase.tenant?.trade_name || '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ purchase.package?.name_ar || 'مخصص' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-indigo-600 dark:text-indigo-400 font-semibold"
                                >
                                    {{ purchase.credits?.toLocaleString() }}
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ formatCurrency(purchase.amount) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="getStatusClass(purchase.status)"
                                        class="px-2 py-1 rounded-full text-xs"
                                    >
                                        {{ getStatusLabel(purchase.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">
                                    {{ purchase.payment_gateway || '-' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs font-mono"
                                    dir="ltr"
                                >
                                    {{ purchase.payment_reference || '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
                                    {{ formatDate(purchase.created_at) }}
                                </td>
                            </tr>
                            <tr v-if="!purchases.data?.length">
                                <td
                                    colspan="8"
                                    class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                                >
                                    لا توجد مشتريات
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="purchases.links"
                    class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        عرض {{ purchases.from || 0 }} - {{ purchases.to || 0 }} من
                        {{ purchases.total || 0 }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in purchases.links" :key="link.label">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200',
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1 rounded text-sm bg-gray-50 dark:bg-gray-800 text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const statusOptions = [
    { value: 'all', label: 'كل الحالات' },
    { value: 'pending', label: 'معلق' },
    { value: 'paid', label: 'مدفوع' },
    { value: 'failed', label: 'فشل' },
    { value: 'refunded', label: 'مسترد' },
];

const props = defineProps({
    purchases: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');

// Auto-apply filters when status changes; debounce search
let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
watch(status, () => applyFilters());

const applyFilters = () => {
    router.get(
        '/system/whatsapp/purchases',
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
        },
        { preserveState: true }
    );
};

const formatCurrency = (amount) =>
    new Intl.NumberFormat('ar-SA-u-nu-latn', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount || 0);

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getStatusLabel = (status) =>
    ({
        pending: 'معلق',
        paid: 'مدفوع',
        failed: 'فشل',
        refunded: 'مسترد',
    })[status] || status;

const getStatusClass = (status) =>
    ({
        pending: 'bg-amber-100 text-amber-700',
        paid: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
        refunded: 'bg-gray-100 text-gray-700',
    })[status] || 'bg-gray-100 text-gray-700';
</script>
