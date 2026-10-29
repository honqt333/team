<template>
    <AppLayout>
        <Head :title="$t('communications.appointment_messages.title')" />

        <div class="space-y-6">
            <PageHeader
                :title="$t('communications.appointment_messages.title')"
                :subtitle="$t('communications.appointment_messages.subtitle')"
                gradientFrom="from-sky-600"
                gradientTo="to-indigo-600"
                glowFrom="from-sky-500"
                :total-count="pagination.total"
                :count-label="
                    $t('communications.appointment_messages.results_count', {
                        count: pagination.total,
                    })
                "
            >
                <template #icon>
                    <svg
                        class="w-9 h-9 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.39-1.03L3 20l1.39-3.94A7.96 7.96 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/60 p-4 sm:p-5"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('communications.appointment_messages.filter_status') }}
                        </label>
                        <SearchableSelect
                            v-model="filters.status"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('communications.appointment_messages.filter_status')"
                            :label="''"
                            :compact="true"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('communications.appointment_messages.filter_message_type') }}
                        </label>
                        <SearchableSelect
                            v-model="filters.message_type"
                            :options="messageTypeOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="
                                $t('communications.appointment_messages.filter_message_type')
                            "
                            :label="''"
                            :compact="true"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('communications.appointment_messages.filter_date_from') }}
                        </label>
                        <input
                            v-model="filters.date_from"
                            type="date"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('communications.appointment_messages.filter_date_to') }}
                        </label>
                        <input
                            v-model="filters.date_to"
                            type="date"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('communications.appointment_messages.filter_search') }}
                        </label>
                        <input
                            v-model="filters.search"
                            type="text"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>
                <div class="mt-3 flex gap-2 justify-end">
                    <button
                        type="button"
                        @click="clearFilters"
                        class="px-3 py-1.5 text-sm rounded-lg text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ $t('communications.appointment_messages.clear_filters') }}
                    </button>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-3 py-1.5 text-sm rounded-lg text-white bg-sky-600 hover:bg-sky-700"
                    >
                        {{ $t('communications.appointment_messages.filter_status') }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700/60 overflow-hidden"
            >
                <div class="overflow-x-auto" dir="rtl">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{ $t('communications.appointment_messages.table.queued_at') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{
                                        $t('communications.appointment_messages.table.attempted_at')
                                    }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{
                                        $t('communications.appointment_messages.table.message_type')
                                    }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{ $t('communications.appointment_messages.table.status') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{ $t('communications.appointment_messages.table.customer') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{
                                        $t('communications.appointment_messages.table.appointment')
                                    }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{
                                        $t('communications.appointment_messages.table.scheduled_at')
                                    }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{ $t('communications.appointment_messages.table.provider') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{ $t('communications.appointment_messages.table.error_code') }}
                                </th>
                                <th
                                    class="px-3 py-2 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{ $t('communications.appointment_messages.table.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                        >
                            <tr v-if="rows.length === 0">
                                <td
                                    colspan="10"
                                    class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ $t('communications.appointment_messages.empty') }}
                                </td>
                            </tr>
                            <tr
                                v-for="row in rows"
                                :key="row.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/30"
                            >
                                <td
                                    class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                >
                                    {{ formatDate(row.queued_at) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                >
                                    {{ formatDate(row.attempted_at) }}
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-900 dark:text-gray-100">
                                    {{
                                        $t(
                                            `communications.appointment_messages.message_types.${row.message_type}`
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <span
                                        :class="statusBadgeClass(row.status)"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium"
                                    >
                                        {{
                                            $t(
                                                `communications.appointment_messages.statuses.${row.status}`
                                            )
                                        }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    {{ row.customer?.name ?? '—' }}
                                </td>
                                <td
                                    class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                >
                                    <span v-if="row.appointment">#{{ row.appointment.id }}</span>
                                    <span v-else>—</span>
                                </td>
                                <td
                                    class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                >
                                    <span v-if="row.appointment?.scheduled_at">
                                        {{ formatDate(row.appointment.scheduled_at) }}
                                    </span>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    <div v-if="row.provider">{{ row.provider }}</div>
                                    <div
                                        v-if="row.provider_message_id"
                                        class="text-[10px] text-gray-500 dark:text-gray-400 font-mono"
                                    >
                                        {{ row.provider_message_id }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                                    <span v-if="row.error_code">
                                        {{
                                            $t(
                                                `communications.appointment_messages.error_codes.${row.error_code}`,
                                                row.error_code
                                            )
                                        }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <button
                                        v-if="row.is_retryable && canRetry"
                                        type="button"
                                        @click="confirmRetry(row)"
                                        :aria-label="
                                            $t(
                                                'communications.appointment_messages.actions.retry_aria',
                                                { id: row.id }
                                            )
                                        "
                                        class="px-2.5 py-1 rounded-md text-white bg-emerald-600 hover:bg-emerald-700"
                                    >
                                        {{
                                            $t('communications.appointment_messages.actions.retry')
                                        }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="pagination.last_page > 1"
                    class="px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30"
                >
                    <button
                        type="button"
                        @click="goToPage(pagination.page - 1)"
                        :disabled="pagination.page <= 1"
                        class="px-3 py-1.5 text-sm rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 disabled:opacity-40"
                    >
                        ‹
                    </button>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ pagination.page }} / {{ pagination.last_page }}
                    </span>
                    <button
                        type="button"
                        @click="goToPage(pagination.page + 1)"
                        :disabled="pagination.page >= pagination.last_page"
                        class="px-3 py-1.5 text-sm rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 disabled:opacity-40"
                    >
                        ›
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { t: $t } = useI18n();
const { confirm: openConfirm } = useConfirm();

const props = defineProps({
    rows: { type: Array, required: true },
    pagination: { type: Object, required: true },
    filters: { type: Object, required: true },
    messageTypes: { type: Array, required: true },
    statuses: { type: Array, required: true },
    /**
     * Top-level capability bit from the
     * server. When false, the retry column
     * is hidden entirely; when true, the
     * per-row `is_retryable` flag decides
     * whether the button is shown for that
     * specific row.
     */
    canRetry: { type: Boolean, required: true },
});

const filters = reactive({
    status: props.filters.status ?? '',
    message_type: props.filters.message_type ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    search: props.filters.search ?? '',
});

const statusOptions = computed(() => [
    { value: '', label: $t('communications.appointment_messages.filter_status') },
    ...props.statuses.map((s) => ({
        value: s,
        label: $t(`communications.appointment_messages.statuses.${s}`),
    })),
]);

const messageTypeOptions = computed(() => [
    { value: '', label: $t('communications.appointment_messages.filter_message_type') },
    ...props.messageTypes.map((m) => ({
        value: m,
        label: $t(`communications.appointment_messages.message_types.${m}`),
    })),
]);

/**
 * canRetry is a PROP from the server now
 * (props.canRetry). The previous local ref
 * was a placeholder that always returned
 * true; the new prop reflects the real
 * server-side capability check
 * (`$user->can(Permissions::APPOINTMENTS_MANAGE)`).
 * We read it from props at render time
 * rather than copying it into a ref so the
 * server is the single source of truth.
 */
const canRetry = computed(() => props.canRetry);

function formatDate(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString();
    } catch {
        return iso;
    }
}

function statusBadgeClass(status) {
    switch (status) {
        case 'sent':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
        case 'failed':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200';
        case 'skipped':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200';
        case 'sending':
            return 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200';
        case 'queued':
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
}

function applyFilters() {
    router.get(
        route('communications.appointment-messages.index'),
        { ...filters, page: 1 },
        { preserveState: true, replace: true }
    );
}

function clearFilters() {
    filters.status = '';
    filters.message_type = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.search = '';
    applyFilters();
}

function goToPage(page) {
    if (page < 1 || page > props.pagination.last_page) return;
    router.get(
        route('communications.appointment-messages.index'),
        { ...filters, page },
        { preserveState: true, replace: true }
    );
}

/**
 * Open the confirmation modal. The modal
 * shows ONLY the message type and the
 * appointment's scheduled time. We
 * deliberately do not show the customer
 * name, phone, or any provider identifier
 * here — the operator already saw those
 * in the row.
 *
 * On confirm we POST to the retry endpoint
 * with a `force` flag to bypass any CSRF /
 * in-flight token. Inertia's `router.post`
 * does the right thing for form posts.
 */
async function confirmRetry(row) {
    const ok = await openConfirm({
        title: $t('communications.appointment_messages.confirm.title'),
        message: $t('communications.appointment_messages.confirm.body'),
        confirmText: $t('communications.appointment_messages.confirm.confirm_button'),
        cancelText: $t('communications.appointment_messages.confirm.cancel_button'),
        type: 'warning',
    });
    if (!ok) return;
    router.post(
        route('communications.appointment-messages.retry', { delivery: row.id }),
        {},
        { preserveScroll: true }
    );
}
</script>
