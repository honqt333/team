<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DataTable from '@/Components/Common/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useFormatters } from '@/Composables/useFormatters';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
    attachments: {
        type: Object,
        default: null,
    },
    summary: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({ source: '', search: '' }),
    },
    canView: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
    routeName: {
        type: String,
        default: 'settings.storage',
    },
    showFullPageLink: {
        type: Boolean,
        default: true,
    },
});

const { t } = useI18n();
const { confirm } = useConfirm();
const { success } = useToast();
const { formatDateTime } = useFormatters();
const { toEnglish } = useNumberFormat();
const { can } = usePermission();

const hasViewAccess = computed(() => props.canView || can('settings.attachments.view'));
const hasDeleteAccess = computed(() => props.canDelete || can('settings.attachments.delete'));

const search = ref(props.filters?.search || '');
const selectedSource = ref(props.filters?.source || '');
const deletingUid = ref(null);
let searchTimer = null;

watch(
    () => props.filters,
    (next) => {
        search.value = next?.search || '';
        selectedSource.value = next?.source || '';
    },
    { deep: true }
);

const sourceLabelMap = computed(() => ({
    work_order_attachment: t('system_settings.attachments.sources.work_order_attachment'),
    work_order_photo: t('system_settings.attachments.sources.work_order_photo'),
    employee_document: t('system_settings.attachments.sources.employee_document'),
    purchase_invoice: t('system_settings.attachments.sources.purchase_invoice'),
    purchase_return_invoice: t('system_settings.attachments.sources.purchase_return_invoice'),
}));

const sourceBadgeClass = (source) => {
    const map = {
        work_order_attachment: 'bg-info-soft text-info',
        work_order_photo: 'bg-primary-soft text-action-primary',
        employee_document: 'bg-warning-soft text-warning',
        purchase_invoice: 'bg-success-soft text-success',
        purchase_return_invoice: 'bg-danger-soft text-danger',
    };
    return map[source] || 'bg-surface-muted text-text-secondary';
};

const sourceOptions = computed(() => [
    { value: '', label: t('system_settings.attachments.all_sources') },
    {
        value: 'work_order_attachment',
        label: sourceLabelMap.value.work_order_attachment,
    },
    {
        value: 'work_order_photo',
        label: sourceLabelMap.value.work_order_photo,
    },
    {
        value: 'employee_document',
        label: sourceLabelMap.value.employee_document,
    },
    {
        value: 'purchase_invoice',
        label: sourceLabelMap.value.purchase_invoice,
    },
    {
        value: 'purchase_return_invoice',
        label: sourceLabelMap.value.purchase_return_invoice,
    },
]);

const columns = computed(() => [
    { key: 'file_name', label: t('system_settings.attachments.file_name') },
    { key: 'source', label: t('system_settings.attachments.source') },
    { key: 'reference', label: t('system_settings.attachments.reference') },
    { key: 'file_size', label: t('system_settings.attachments.file_size'), align: 'end' },
    { key: 'created_at', label: t('system_settings.attachments.created_at') },
    { key: 'actions', label: t('system_settings.attachments.actions'), align: 'center' },
]);

const formatSize = (bytes) => {
    const val = Number(bytes || 0);
    if (val <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const idx = Math.min(units.length - 1, Math.floor(Math.log(val) / Math.log(1024)));
    const scaled = parseFloat((val / Math.pow(1024, idx)).toFixed(2));
    return `${toEnglish(scaled)} ${units[idx]}`;
};

const applyFilters = () => {
    const params = {};
    if (search.value.trim()) params.search = search.value.trim();
    if (selectedSource.value) params.source = selectedSource.value;

    router.get(route(props.routeName), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSearchInput = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 300);
};

const handleDelete = async (item) => {
    if (!hasDeleteAccess.value) return;

    const confirmed = await confirm({
        title: t('system_settings.attachments.confirm_delete_title'),
        message: t('system_settings.attachments.confirm_delete_message', {
            name: item.file_name,
        }),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (!confirmed) return;

    deletingUid.value = item.uid;
    router.delete(route('settings.attachments.destroy', { source: item.source, id: item.id }), {
        preserveScroll: true,
        onSuccess: () => {
            success(t('system_settings.attachments.delete_success'));
        },
        onFinish: () => {
            deletingUid.value = null;
        },
    });
};
</script>

<template>
    <div class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-5">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-action-primary/10 text-action-primary shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-text-primary">
                        {{ t('system_settings.attachments.title') }}
                    </h3>
                    <p class="text-xs text-text-secondary mt-0.5">
                        {{ t('system_settings.attachments.subtitle') }}
                    </p>
                </div>
            </div>

            <div v-if="hasViewAccess && showFullPageLink" class="flex items-center gap-2">
                <Link
                    :href="route('settings.attachments.index')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl border border-border bg-surface-secondary hover:bg-surface-muted text-text-primary transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                        />
                    </svg>
                    <span>{{ t('system_settings.attachments.open_full_page') }}</span>
                </Link>
            </div>
        </div>

        <!-- Permission Guard -->
        <div
            v-if="!hasViewAccess"
            class="rounded-xl border border-border bg-surface-secondary/60 p-6 text-center space-y-2"
        >
            <div
                class="w-11 h-11 mx-auto rounded-xl bg-warning-soft text-warning flex items-center justify-center"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    />
                </svg>
            </div>
            <p class="text-sm font-semibold text-text-primary">
                {{ t('system_settings.attachments.permission_required_title') }}
            </p>
            <p class="text-xs text-text-secondary max-w-md mx-auto">
                {{ t('system_settings.attachments.permission_required_description') }}
            </p>
        </div>

        <!-- Authorized Attachments Manager -->
        <template v-else>
            <!-- Summary Strip -->
            <div v-if="summary" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-xl border border-border bg-surface-secondary/50 p-3.5">
                    <p class="text-xs text-text-secondary">
                        {{ t('system_settings.attachments.summary_files') }}
                    </p>
                    <p class="text-lg font-bold text-text-primary mt-1 font-mono">
                        {{ toEnglish(summary.total_count ?? 0) }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-surface-secondary/50 p-3.5">
                    <p class="text-xs text-text-secondary">
                        {{ t('system_settings.attachments.summary_size') }}
                    </p>
                    <p class="text-lg font-bold text-text-primary mt-1 font-mono">
                        {{ formatSize(summary.matched_bytes ?? 0) }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-surface-secondary/50 p-3.5">
                    <p class="text-xs text-text-secondary">
                        {{ sourceLabelMap.work_order_attachment }}
                    </p>
                    <p class="text-lg font-bold text-text-primary mt-1 font-mono">
                        {{
                            toEnglish(
                                (summary.by_source?.work_order_attachment ?? 0) +
                                    (summary.by_source?.work_order_photo ?? 0)
                            )
                        }}
                    </p>
                </div>
                <div class="rounded-xl border border-border bg-surface-secondary/50 p-3.5">
                    <p class="text-xs text-text-secondary">
                        {{ sourceLabelMap.employee_document }}
                    </p>
                    <p class="text-lg font-bold text-text-primary mt-1 font-mono">
                        {{ toEnglish(summary.by_source?.employee_document ?? 0) }}
                    </p>
                </div>
            </div>

            <!-- Table -->
            <DataTable
                :data="attachments"
                :columns="columns"
                row-key="uid"
                :empty-title="t('system_settings.attachments.empty_title')"
                :empty-description="t('system_settings.attachments.empty_description')"
            >
                <template #header>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="relative flex-1 sm:max-w-xs">
                            <div
                                class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                            >
                                <svg
                                    class="w-4 h-4 text-content-muted"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="search"
                                type="text"
                                :placeholder="t('system_settings.attachments.search_placeholder')"
                                class="w-full ps-10 pe-4 py-2 text-sm border border-border rounded-xl bg-surface-secondary text-content-primary placeholder-content-muted focus:ring-2 focus:ring-action-primary/30 focus:border-action-primary transition-colors"
                                @input="handleSearchInput"
                            />
                        </div>

                        <div class="w-full sm:w-64">
                            <SearchableSelect
                                v-model="selectedSource"
                                :options="sourceOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="t('system_settings.attachments.all_sources')"
                                compact
                                @change="applyFilters"
                            />
                        </div>
                    </div>
                </template>

                <template #cell-file_name="{ item }">
                    <div class="flex flex-col">
                        <span class="font-medium text-text-primary break-all">
                            {{ item.file_name }}
                        </span>
                        <span v-if="!item.exists" class="text-xs text-danger mt-0.5">
                            {{ t('system_settings.attachments.missing_file') }}
                        </span>
                        <span
                            v-else-if="item.uploaded_by"
                            class="text-xs text-text-secondary mt-0.5"
                        >
                            {{ t('system_settings.attachments.uploaded_by') }}:
                            {{ item.uploaded_by }}
                        </span>
                    </div>
                </template>

                <template #cell-source="{ item }">
                    <span
                        class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full"
                        :class="sourceBadgeClass(item.source)"
                    >
                        {{ sourceLabelMap[item.source] || item.source }}
                    </span>
                </template>

                <template #cell-reference="{ item }">
                    <Link
                        v-if="item.reference_url"
                        :href="item.reference_url"
                        class="text-sm font-medium text-action-primary hover:underline font-mono"
                    >
                        {{ item.reference_label }}
                    </Link>
                    <span v-else class="text-sm text-text-secondary font-mono">
                        {{ item.reference_label || '—' }}
                    </span>
                </template>

                <template #cell-file_size="{ item }">
                    <span class="font-mono text-xs font-semibold text-text-primary">
                        {{ formatSize(item.file_size) }}
                    </span>
                </template>

                <template #cell-created_at="{ item }">
                    <span class="font-mono text-xs text-text-secondary">
                        {{ item.created_at ? toEnglish(formatDateTime(item.created_at)) : '—' }}
                    </span>
                </template>

                <template #cell-actions="{ item }">
                    <div class="flex items-center justify-center gap-1.5">
                        <a
                            v-if="item.exists"
                            :href="item.download_url"
                            :title="t('system_settings.attachments.download')"
                            class="p-2 text-content-secondary hover:text-action-primary hover:bg-action-primary/10 rounded-lg transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                        </a>

                        <button
                            v-if="hasDeleteAccess"
                            type="button"
                            :disabled="deletingUid === item.uid"
                            :title="t('common.delete')"
                            class="p-2 text-content-secondary hover:text-danger hover:bg-danger-soft rounded-lg transition-colors cursor-pointer disabled:opacity-50"
                            @click="handleDelete(item)"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>
                </template>
            </DataTable>
        </template>
    </div>
</template>
