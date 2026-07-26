<template>
    <SystemLayout>
        <Head :title="isAr ? 'سجل التذكير التلقائي' : 'Dunning Log'" />

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ isAr ? 'سجل التذكير التلقائي' : 'Dunning Log' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{
                        isAr
                            ? 'كل محاولات إرسال التذكيرات التلقائية — نجح، فشل، أو تم تخطيها.'
                            : 'Every dunning send attempt — sent, failed, or skipped.'
                    }}
                </p>
            </div>

            <!-- Filters -->
            <div
                class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex-1 min-w-[150px]">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ isAr ? 'الحالة' : 'Status' }}
                    </label>
                    <select
                        v-model="filters.status"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ isAr ? 'الكل' : 'All' }}</option>
                        <option value="sent">{{ isAr ? 'مُرسلة' : 'Sent' }}</option>
                        <option value="failed">{{ isAr ? 'فشلت' : 'Failed' }}</option>
                        <option value="skipped">{{ isAr ? 'متخطاة' : 'Skipped' }}</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ isAr ? 'المستوى' : 'Tier' }}
                    </label>
                    <select
                        v-model="filters.tier"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">{{ isAr ? 'الكل' : 'All' }}</option>
                        <option v-for="(_, key) in tierDefinitions" :key="key" :value="key">
                            {{ isAr ? 'المستوى' : 'Tier' }} {{ key.replace('tier_', '') }}
                        </option>
                    </select>
                </div>
                <button
                    @click="applyFilters"
                    class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                >
                    {{ isAr ? 'تطبيق' : 'Apply' }}
                </button>
                <Link
                    :href="'/system/settings/dunning'"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    {{ isAr ? 'الإعدادات' : 'Settings' }}
                </Link>
            </div>

            <!-- Log table -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? 'التاريخ' : 'Date' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? 'المستأجر' : 'Tenant' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? 'الفاتورة' : 'Invoice' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? 'المستوى' : 'Tier' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? 'القناة' : 'Channel' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? 'الحالة' : 'Status' }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ isAr ? 'خطأ' : 'Error' }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ formatDate(log.sent_at) }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ log.tenant?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ log.invoice?.invoice_number || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ log.tier_key.replace('tier_', '') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ log.channel }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="statusClass(log.status)"
                                    class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                >
                                    {{ statusLabel(log.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-rose-600 dark:text-rose-400">
                                {{ log.error_message || '—' }}
                            </td>
                        </tr>
                        <tr v-if="!logs.data?.length">
                            <td
                                colspan="7"
                                class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ isAr ? 'لا توجد سجلات بعد' : 'No log entries yet' }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div
                    v-if="logs.last_page > 1"
                    class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-gray-700"
                >
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        {{ isAr ? 'صفحة' : 'Page' }} {{ logs.current_page }} / {{ logs.last_page }}
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in logs.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-1 text-sm',
                                link.active
                                    ? 'bg-amber-600 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
                                !link.url && 'pointer-events-none opacity-50',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    tier_definitions: { type: Object, required: true },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const filters = ref({
    status: props.filters.status || '',
    tier: props.filters.tier || '',
});

function applyFilters() {
    router.get('/system/billing/dunning-log', {
        status: filters.value.status || undefined,
        tier: filters.value.tier || undefined,
    });
}

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString(isAr.value ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusClass(s) {
    return (
        {
            sent: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
            failed: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
            skipped: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        }[s] || 'bg-gray-100 text-gray-700'
    );
}

function statusLabel(s) {
    return (
        {
            sent: isAr.value ? 'مُرسل' : 'Sent',
            failed: isAr.value ? 'فشل' : 'Failed',
            skipped: isAr.value ? 'متخطى' : 'Skipped',
        }[s] || s
    );
}
</script>
