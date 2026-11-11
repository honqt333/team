<script setup>
import { computed } from 'vue';
import SortIcon from './SortIcon.vue';
import AppEmptyState from './AppEmptyState.vue';
import AppTableSkeleton from './AppTableSkeleton.vue';
import AppPagination from './AppPagination.vue';

const props = defineProps({
    // Preserve caller-defined grouped headers, totals and editable cells.
    structured: Boolean,
    columns: {
        type: Array,
        default: () => [],
        // Column shape: { key, label, sortable, align: 'start'|'center'|'end', width, class, headerClass }
    },
    data: {
        type: [Array, Object],
        default: null,
        // Array of items OR Inertia paginator object { data: [], links: [], from, to, total }
    },
    items: {
        type: [Array, Object],
        default: null,
        // Alias for data (supports both :data and :items)
    },
    loading: {
        type: Boolean,
        default: false,
    },
    rowKey: {
        type: String,
        default: 'id',
    },
    sortColumn: {
        type: String,
        default: '',
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
    striped: {
        type: Boolean,
        default: false,
    },
    hoverable: {
        type: Boolean,
        default: true,
    },
    emptyTitle: {
        type: String,
        default: '',
    },
    emptyDescription: {
        type: String,
        default: '',
    },
    emptyActionText: {
        type: String,
        default: '',
    },
    emptyActionHref: {
        type: String,
        default: '',
    },
    showPagination: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['sort', 'rowClick', 'emptyAction']);

// Resolve source payload (supports either :data or :items)
const sourceData = computed(() => props.data ?? props.items);

// Resolve actual rows list
const items = computed(() => {
    const raw = sourceData.value;
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    if (Array.isArray(raw.data)) return raw.data;
    return [];
});

// Resolve paginator meta (if props.data or props.items is an Inertia paginator)
const isPaginated = computed(() => {
    const raw = sourceData.value;
    return (
        raw && !Array.isArray(raw) && Array.isArray(raw.links) && raw.links.length >= 3 // at least [prev, 1, next]
    );
});

const paginationLinks = computed(() => (isPaginated.value ? sourceData.value.links : []));
const paginationFrom = computed(() => (isPaginated.value ? sourceData.value.from : null));
const paginationTo = computed(() => (isPaginated.value ? sourceData.value.to : null));
const paginationTotal = computed(() => (isPaginated.value ? sourceData.value.total : null));

const handleSort = (column) => {
    if (!column.sortable) return;
    emit('sort', column.key);
};

const handleRowClick = (item) => {
    emit('rowClick', item);
};

const getAlignmentClass = (align) => {
    switch (align) {
        case 'center':
            return 'text-center justify-center';
        case 'end':
            return 'text-end justify-end';
        case 'start':
        default:
            return 'text-start justify-start';
    }
};
</script>

<template>
    <table v-if="structured" class="w-full text-start border-collapse">
        <slot />
    </table>
    <div
        v-else
        class="w-full bg-surface rounded-2xl border border-border shadow-xs overflow-hidden transition-all flex flex-col"
    >
        <!-- Optional Top Header Slot -->
        <div v-if="$slots.header" class="p-4 sm:p-5 border-b border-border bg-surface">
            <slot name="header" />
        </div>

        <!-- Top Loading Indicator Bar -->
        <div v-if="loading" class="h-1 w-full bg-surface-muted overflow-hidden relative">
            <div class="h-full bg-action-primary animate-pulse w-full"></div>
        </div>

        <!-- Initial Load Skeleton (only if empty AND loading) -->
        <div v-if="loading && items.length === 0" class="p-4">
            <AppTableSkeleton
                :rows="5"
                :cols="columns.length"
                :show-header="false"
                :show-pagination="false"
            />
        </div>

        <!-- Table View -->
        <div v-else class="overflow-x-auto min-w-full relative">
            <table
                class="w-full text-start border-collapse transition-opacity duration-200"
                :class="{ 'opacity-60 pointer-events-none select-none': loading }"
            >
                <!-- Table Head -->
                <thead class="bg-surface-muted/75 border-b border-border text-xs">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-4 py-3.5 font-bold uppercase tracking-wider text-text-secondary align-middle transition-colors',
                                col.sortable
                                    ? 'cursor-pointer hover:bg-surface-raised hover:text-text-primary select-none'
                                    : '',
                                col.width ? col.width : '',
                                col.headerClass || '',
                                getAlignmentClass(col.align),
                            ]"
                            @click="handleSort(col)"
                        >
                            <div
                                :class="[
                                    'inline-flex items-center gap-1.5 w-full',
                                    getAlignmentClass(col.align),
                                ]"
                            >
                                <slot :name="`header-${col.key}`" :column="col">
                                    <span>{{ col.label }}</span>
                                </slot>

                                <SortIcon
                                    v-if="col.sortable"
                                    :active="sortColumn === col.key"
                                    :direction="sortDirection"
                                    class="shrink-0"
                                />
                            </div>
                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="divide-y divide-border/60 bg-surface">
                    <tr
                        v-for="(item, index) in items"
                        :key="item[rowKey] ?? index"
                        :class="[
                            'group transition-colors',
                            striped && index % 2 === 1 ? 'bg-surface-muted/30' : 'bg-surface',
                            hoverable ? 'hover:bg-surface-muted/60' : '',
                            $attrs.onRowClick ? 'cursor-pointer' : '',
                        ]"
                        @click="handleRowClick(item)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-4 py-3.5 text-sm text-text-primary align-middle',
                                col.class || '',
                                getAlignmentClass(col.align),
                            ]"
                        >
                            <!-- Scoped cell slot for specific column -->
                            <slot
                                :name="`cell-${col.key}`"
                                :item="item"
                                :index="index"
                                :column="col"
                                :value="item[col.key]"
                            >
                                <!-- General cell slot fallback -->
                                <slot
                                    name="cell"
                                    :item="item"
                                    :index="index"
                                    :column="col"
                                    :value="item[col.key]"
                                >
                                    {{ item[col.key] ?? '-' }}
                                </slot>
                            </slot>
                        </td>
                    </tr>

                    <!-- Empty State Row -->
                    <tr v-if="!items.length">
                        <td :colspan="columns.length" class="p-8 text-center align-middle">
                            <slot name="empty">
                                <AppEmptyState
                                    :title="
                                        emptyTitle || $t('common.no_data', 'لا توجد بيانات متاحة')
                                    "
                                    :description="
                                        emptyDescription ||
                                        $t(
                                            'common.no_records_found',
                                            'لم يتم العثور على أي سجلات مطابقة'
                                        )
                                    "
                                    :action-text="emptyActionText"
                                    :action-href="emptyActionHref"
                                    @action="$emit('emptyAction')"
                                />
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Optional Footer Slot -->
        <div v-if="$slots.footer" class="border-t border-border bg-surface p-4">
            <slot name="footer" />
        </div>

        <!-- Unified Pagination -->
        <slot
            name="pagination"
            :links="paginationLinks"
            :from="paginationFrom"
            :to="paginationTo"
            :total="paginationTotal"
        >
            <AppPagination
                v-if="showPagination && isPaginated"
                :links="paginationLinks"
                :from="paginationFrom"
                :to="paginationTo"
                :total="paginationTotal"
            />
        </slot>
    </div>
</template>
