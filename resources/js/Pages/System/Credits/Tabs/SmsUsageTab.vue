<template>
    <div class="space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ stats?.total_sent?.toLocaleString() || 0 }}
                </p>
                <p class="text-sm text-gray-500">{{ $t('system.credits.total_messages') }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    {{ stats?.delivered?.toLocaleString() || 0 }}
                </p>
                <p class="text-sm text-gray-500">{{ $t('system.credits.delivered') }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ stats?.failed?.toLocaleString() || 0 }}
                </p>
                <p class="text-sm text-gray-500">{{ $t('system.credits.failed_2') }}</p>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
            >
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {{ stats?.pending?.toLocaleString() || 0 }}
                </p>
                <p class="text-sm text-gray-500">{{ $t('system.credits.pending') }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-4">
            <div class="w-64">
                <SearchableSelect
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('system.credits.all_statuses')"
                    compact
                    @update:model-value="applyFilters"
                />
            </div>
        </div>

        <!-- Table -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.workshop') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.recipient') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.message_type') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.credits') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.status') }}
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                        >
                            {{ $t('system.credits.date') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="item in logs?.data || []" :key="item.id">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{
                                    item.tenant?.name ||
                                    $t('system.credits.tenant_label', { id: item.tenant_id })
                                }}
                            </div>
                        </td>
                        <td
                            class="px-6 py-4 font-mono text-sm text-gray-700 dark:text-gray-300"
                            dir="ltr"
                        >
                            {{ item.recipient_phone }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                            {{ getTypeLabel(item.message_type) }}
                        </td>
                        <td class="px-6 py-4 font-bold text-red-600 dark:text-red-400">
                            -{{ item.credits_used }}
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-full text-xs font-medium',
                                    getStatusClass(item.status),
                                ]"
                            >
                                {{ getStatusLabel(item.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(item.created_at) }}
                        </td>
                    </tr>
                    <tr v-if="!logs?.data?.length">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            {{ $t('system.credits.no_messages_recorded') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    logs: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const status = ref(props.filters?.status || '');

const statusOptions = computed(() => [
    { value: '', label: t('system.credits.all_statuses') },
    { value: 'sent', label: t('system.credits.sent') },
    { value: 'delivered', label: t('system.credits.delivered') },
    { value: 'failed', label: t('system.credits.failed_2') },
    { value: 'pending', label: t('system.credits.pending') },
]);

const applyFilters = () => {
    router.get(
        '/system/sms',
        { tab: 'usage', status: status.value || undefined },
        { preserveState: true }
    );
};

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '-');

const getTypeLabel = (type) =>
    ({
        reminder: t('system.credits.reminder'),
        notification: t('system.credits.notification'),
        otp: t('system.credits.verification_code'),
        marketing: t('system.credits.marketing'),
        invoice: t('system.credits.invoice'),
        appointment: t('system.credits.appointment'),
    })[type] || type;

const getStatusLabel = (s) =>
    ({
        sent: t('system.credits.sent'),
        delivered: t('system.credits.delivered'),
        failed: t('system.credits.failed_2'),
        pending: t('system.credits.pending'),
    })[s] || s;

const getStatusClass = (s) =>
    ({
        sent: 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
        delivered: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
    })[s] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
</script>
