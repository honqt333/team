<template>
    <AppLayout>
        <Head :title="$t('communications.appointment_messages.title')" />

        <div class="space-y-6">
            <PageHeader
                :title="$t('communications.appointment_messages.title')"
                :subtitle="$t('communications.appointment_messages.subtitle')"
                :total-count="toEnglish(pagination.total)"
                :count-label="
                    $t('communications.appointment_messages.results_count', {
                        count: toEnglish(pagination.total),
                    })
                "
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.39-1.03L3 20l1.39-3.94A7.96 7.96 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div
                        class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                    >
                        <AppViewModeToggle
                            v-model="viewMode"
                            storage-key="appointment_messages_view_mode"
                        />
                    </div>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div
                class="bg-surface-primary rounded-2xl shadow-2xs border border-border-subtle p-4 sm:p-5"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
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
                        <label class="block text-xs font-bold text-content-muted mb-1">
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
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('communications.appointment_messages.filter_date_from') }}
                        </label>
                        <CustomDatePicker v-model="filters.date_from" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('communications.appointment_messages.filter_date_to') }}
                        </label>
                        <CustomDatePicker v-model="filters.date_to" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-content-muted mb-1">
                            {{ $t('communications.appointment_messages.filter_search') }}
                        </label>
                        <input
                            v-model="filters.search"
                            type="text"
                            :placeholder="$t('common.search')"
                            class="w-full rounded-xl border border-border-subtle bg-surface-secondary text-content-primary px-3 py-2 text-sm focus:ring-2 focus:ring-action-primary/20 focus:border-action-primary transition-colors"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>
                <div class="mt-3 flex gap-2 justify-end">
                    <button
                        type="button"
                        @click="clearFilters"
                        class="px-3.5 py-2 text-xs font-bold rounded-xl text-content-secondary bg-surface-secondary hover:bg-surface-tertiary border border-border-subtle transition-colors cursor-pointer"
                    >
                        {{ $t('communications.appointment_messages.clear_filters') }}
                    </button>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-4 py-2 text-xs font-bold rounded-xl text-white bg-action-primary hover:bg-action-primary-hover shadow-xs transition-colors cursor-pointer"
                    >
                        {{ $t('common.apply') }}
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <AppEmptyState
                v-if="rows.length === 0"
                :title="$t('communications.appointment_messages.empty')"
            />

            <!-- Content Views -->
            <template v-else>
                <!-- Table View -->
                <DataTable v-if="viewMode === 'list'" :items="rows" :columns="columns">
                    <template #cell-queued_at="{ item }">
                        <span class="text-xs text-content-secondary whitespace-nowrap">
                            {{ formatDate(item.queued_at) }}
                        </span>
                    </template>

                    <template #cell-attempted_at="{ item }">
                        <span class="text-xs text-content-secondary whitespace-nowrap">
                            {{ formatDate(item.attempted_at) }}
                        </span>
                    </template>

                    <template #cell-message_type="{ item }">
                        <span class="text-xs font-semibold text-content-primary">
                            {{
                                $t(
                                    `communications.appointment_messages.message_types.${item.message_type}`
                                )
                            }}
                        </span>
                    </template>

                    <template #cell-status="{ item }">
                        <span
                            :class="statusBadgeClass(item.status)"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                        >
                            {{ $t(`communications.appointment_messages.statuses.${item.status}`) }}
                        </span>
                    </template>

                    <template #cell-customer="{ item }">
                        <span class="text-xs font-medium text-content-primary">
                            {{ item.customer?.name ?? '—' }}
                        </span>
                    </template>

                    <template #cell-appointment="{ item }">
                        <span
                            v-if="item.appointment"
                            class="text-xs font-mono text-action-primary font-bold"
                        >
                            #{{ toEnglish(item.appointment.id) }}
                        </span>
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <template #cell-scheduled_at="{ item }">
                        <span
                            v-if="item.appointment?.scheduled_at"
                            class="text-xs text-content-secondary whitespace-nowrap"
                        >
                            {{ formatDate(item.appointment.scheduled_at) }}
                        </span>
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <template #cell-provider="{ item }">
                        <div class="text-xs text-content-secondary">
                            <div v-if="item.provider" class="font-medium">{{ item.provider }}</div>
                            <div
                                v-if="item.provider_message_id"
                                class="text-[10px] text-content-muted font-mono"
                            >
                                {{ item.provider_message_id }}
                            </div>
                            <span v-if="!item.provider && !item.provider_message_id">—</span>
                        </div>
                    </template>

                    <template #cell-error_code="{ item }">
                        <span v-if="item.error_code" class="text-xs text-danger font-medium">
                            {{
                                $t(
                                    `communications.appointment_messages.error_codes.${item.error_code}`,
                                    item.error_code
                                )
                            }}
                        </span>
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <template #cell-actions="{ item }">
                        <div class="flex items-center justify-center">
                            <button
                                v-if="item.is_retryable && canRetry"
                                type="button"
                                @click="confirmRetry(item)"
                                :aria-label="
                                    $t('communications.appointment_messages.actions.retry_aria', {
                                        id: item.id,
                                    })
                                "
                                class="px-2.5 py-1 rounded-lg text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover transition-colors cursor-pointer"
                            >
                                {{ $t('communications.appointment_messages.actions.retry') }}
                            </button>
                            <span v-else class="text-xs text-content-muted">—</span>
                        </div>
                    </template>

                    <template #pagination>
                        <div
                            v-if="pagination.last_page > 1"
                            class="px-4 py-3 flex items-center justify-between border-t border-border-subtle bg-surface-secondary/40"
                        >
                            <button
                                type="button"
                                @click="goToPage(pagination.page - 1)"
                                :disabled="pagination.page <= 1"
                                class="px-3 py-1.5 text-sm font-bold rounded-xl text-content-secondary bg-surface-primary border border-border-subtle hover:bg-surface-secondary disabled:opacity-40 transition-colors cursor-pointer"
                            >
                                ‹
                            </button>
                            <span class="text-xs font-bold text-content-muted">
                                {{ toEnglish(pagination.page) }} /
                                {{ toEnglish(pagination.last_page) }}
                            </span>
                            <button
                                type="button"
                                @click="goToPage(pagination.page + 1)"
                                :disabled="pagination.page >= pagination.last_page"
                                class="px-3 py-1.5 text-sm font-bold rounded-xl text-content-secondary bg-surface-primary border border-border-subtle hover:bg-surface-secondary disabled:opacity-40 transition-colors cursor-pointer"
                            >
                                ›
                            </button>
                        </div>
                    </template>
                </DataTable>

                <!-- Grid View -->
                <div v-else class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="row in rows"
                            :key="row.id"
                            class="bg-surface-primary rounded-2xl border border-border-subtle p-5 shadow-2xs flex flex-col justify-between gap-3"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-xs font-bold text-content-primary block">
                                        {{
                                            $t(
                                                `communications.appointment_messages.message_types.${row.message_type}`
                                            )
                                        }}
                                    </span>
                                    <span class="text-xs text-content-muted">
                                        {{ row.customer?.name ?? '—' }}
                                    </span>
                                </div>
                                <span
                                    :class="statusBadgeClass(row.status)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold shrink-0"
                                >
                                    {{
                                        $t(
                                            `communications.appointment_messages.statuses.${row.status}`
                                        )
                                    }}
                                </span>
                            </div>

                            <div
                                class="text-xs text-content-secondary space-y-1 bg-surface-secondary/50 p-3 rounded-xl border border-border-subtle"
                            >
                                <div v-if="row.appointment" class="flex justify-between">
                                    <span class="text-content-muted">
                                        {{
                                            $t(
                                                'communications.appointment_messages.table.appointment'
                                            )
                                        }}:
                                    </span>
                                    <span class="font-mono font-bold text-action-primary">
                                        #{{ toEnglish(row.appointment.id) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-content-muted">
                                        {{
                                            $t(
                                                'communications.appointment_messages.table.queued_at'
                                            )
                                        }}:
                                    </span>
                                    <span>{{ formatDate(row.queued_at) }}</span>
                                </div>
                                <div
                                    v-if="row.error_code"
                                    class="flex justify-between text-danger font-medium"
                                >
                                    <span>
                                        {{
                                            $t(
                                                'communications.appointment_messages.table.error_code'
                                            )
                                        }}:
                                    </span>
                                    <span>
                                        {{
                                            $t(
                                                `communications.appointment_messages.error_codes.${row.error_code}`,
                                                row.error_code
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="row.is_retryable && canRetry" class="flex justify-end pt-1">
                                <button
                                    type="button"
                                    @click="confirmRetry(row)"
                                    :aria-label="
                                        $t(
                                            'communications.appointment_messages.actions.retry_aria',
                                            {
                                                id: row.id,
                                            }
                                        )
                                    "
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover transition-colors cursor-pointer"
                                >
                                    {{ $t('communications.appointment_messages.actions.retry') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Pagination -->
                    <div
                        v-if="pagination.last_page > 1"
                        class="px-4 py-3 flex items-center justify-between rounded-2xl border border-border-subtle bg-surface-primary shadow-2xs"
                    >
                        <button
                            type="button"
                            @click="goToPage(pagination.page - 1)"
                            :disabled="pagination.page <= 1"
                            class="px-3 py-1.5 text-sm font-bold rounded-xl text-content-secondary bg-surface-secondary border border-border-subtle hover:bg-surface-tertiary disabled:opacity-40 transition-colors cursor-pointer"
                        >
                            ‹
                        </button>
                        <span class="text-xs font-bold text-content-muted">
                            {{ toEnglish(pagination.page) }} / {{ toEnglish(pagination.last_page) }}
                        </span>
                        <button
                            type="button"
                            @click="goToPage(pagination.page + 1)"
                            :disabled="pagination.page >= pagination.last_page"
                            class="px-3 py-1.5 text-sm font-bold rounded-xl text-content-secondary bg-surface-secondary border border-border-subtle hover:bg-surface-tertiary disabled:opacity-40 transition-colors cursor-pointer"
                        >
                            ›
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t: $t } = useI18n();
const { confirm: openConfirm } = useConfirm();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    rows: { type: Array, required: true },
    pagination: { type: Object, required: true },
    filters: { type: Object, required: true },
    messageTypes: { type: Array, required: true },
    statuses: { type: Array, required: true },
    canRetry: { type: Boolean, required: true },
});

const viewMode = ref('list');

const columns = computed(() => [
    { key: 'queued_at', label: $t('communications.appointment_messages.table.queued_at') },
    { key: 'attempted_at', label: $t('communications.appointment_messages.table.attempted_at') },
    { key: 'message_type', label: $t('communications.appointment_messages.table.message_type') },
    { key: 'status', label: $t('communications.appointment_messages.table.status') },
    { key: 'customer', label: $t('communications.appointment_messages.table.customer') },
    { key: 'appointment', label: $t('communications.appointment_messages.table.appointment') },
    { key: 'scheduled_at', label: $t('communications.appointment_messages.table.scheduled_at') },
    { key: 'provider', label: $t('communications.appointment_messages.table.provider') },
    { key: 'error_code', label: $t('communications.appointment_messages.table.error_code') },
    {
        key: 'actions',
        label: $t('communications.appointment_messages.table.actions'),
        align: 'center',
    },
]);

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
            return 'bg-success-soft text-success';
        case 'failed':
            return 'bg-danger-soft text-danger';
        case 'skipped':
            return 'bg-warning-soft text-warning';
        case 'sending':
            return 'bg-info-soft text-info';
        case 'queued':
        default:
            return 'bg-surface-tertiary text-content-secondary';
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
