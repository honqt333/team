<template>
    <AppLayout>
        <Head :title="$t('workshop.board.title')" />

        <div class="px-4 sm:px-6 py-6 space-y-6">
            <!-- Page Header -->
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-600 dark:from-emerald-700 dark:via-emerald-600 dark:to-teal-700 px-6 py-5 shadow-lg"
            >
                <div
                    class="absolute inset-y-0 start-0 w-1/3 bg-gradient-to-r from-white/10 to-transparent pointer-events-none"
                ></div>
                <div class="relative flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center flex-shrink-0"
                        >
                            <svg
                                class="w-7 h-7 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h6v6H4V6zm0 10h6v4H4v-4zm10-10h6v4h-6V6zm0 8h6v6h-6v-6z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">
                                {{ $t('workshop.board.title') }}
                            </h1>
                            <p class="text-sm text-emerald-50/90 mt-0.5">
                                {{ $t('workshop.board.subtitle') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur-sm text-sm font-semibold text-white"
                        >
                            <span class="w-2 h-2 rounded-full bg-emerald-200 animate-pulse"></span>
                            {{
                                $t('workshop.board.total_active', {
                                    count: toEnglish(counts.total),
                                })
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div
                    v-for="column in boardColumns"
                    :key="column.key"
                    class="flex flex-col rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40 backdrop-blur-sm min-h-[60vh]"
                >
                    <!-- Column Header -->
                    <div
                        class="flex items-center justify-between gap-2 px-4 py-3 border-b border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <span
                                :class="['w-2.5 h-2.5 rounded-full flex-shrink-0', column.dotClass]"
                            ></span>
                            <h2
                                class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate"
                            >
                                {{ column.label }}
                            </h2>
                        </div>
                        <span
                            :class="[
                                'inline-flex items-center justify-center min-w-[1.75rem] px-2 py-0.5 rounded-full text-xs font-bold',
                                column.badgeClass,
                            ]"
                        >
                            {{ toEnglish(columns[column.key].length) }}
                        </span>
                    </div>

                    <!-- Cards -->
                    <div class="flex-1 p-3 space-y-3 overflow-y-auto">
                        <Link
                            v-for="card in columns[column.key]"
                            :key="card.id"
                            :href="
                                card.work_order_id
                                    ? route('work-orders.show', {
                                          workOrder: card.work_order_id,
                                      })
                                    : '#'
                            "
                            class="block group rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-emerald-400 dark:hover:border-emerald-500 shadow-sm hover:shadow-md transition-all p-3.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <!-- Row 1: WO code + overdue badge -->
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <span
                                    class="text-[11px] font-mono font-semibold text-gray-500 dark:text-gray-400 truncate"
                                >
                                    {{ card.work_order_code || '—' }}
                                </span>
                                <span
                                    v-if="card.is_overdue"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 flex-shrink-0"
                                >
                                    <svg
                                        class="w-3 h-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"
                                        />
                                    </svg>
                                    {{ $t('workshop.board.overdue_badge') }}
                                </span>
                            </div>

                            <!-- Service title -->
                            <h3
                                class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-snug mb-1.5 line-clamp-2"
                            >
                                {{ card.service_name || card.title || '—' }}
                            </h3>
                            <p
                                v-if="
                                    card.service_name &&
                                    card.title &&
                                    card.service_name !== card.title
                                "
                                class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 line-clamp-1"
                            >
                                {{ card.title }}
                            </p>

                            <!-- Customer + plate -->
                            <div
                                class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 mb-2"
                            >
                                <svg
                                    class="w-3.5 h-3.5 flex-shrink-0 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                                <span class="truncate flex-1">
                                    {{ card.customer_name || '—' }}
                                </span>
                                <span
                                    v-if="card.vehicle_plate"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded font-mono text-[10px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 flex-shrink-0"
                                    dir="ltr"
                                >
                                    {{ card.vehicle_plate }}
                                </span>
                            </div>

                            <!-- Technicians -->
                            <div
                                class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 mb-2"
                            >
                                <svg
                                    class="w-3.5 h-3.5 flex-shrink-0 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                                    />
                                </svg>
                                <span v-if="card.technicians.length" class="truncate">
                                    {{ card.technicians.map((t) => t.name).join('، ') }}
                                </span>
                                <span v-else class="italic text-gray-400">
                                    {{ $t('workshop.board.no_technicians') }}
                                </span>
                            </div>

                            <!-- Hold reason (only when on_hold and present) -->
                            <div
                                v-if="card.status === 'on_hold' && card.work_order_hold_reason"
                                class="mb-2 p-2 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800"
                            >
                                <p
                                    class="text-[10px] font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide"
                                >
                                    {{ $t('workshop.board.hold_reason_label') }}
                                </p>
                                <p
                                    class="text-xs text-amber-900 dark:text-amber-100 line-clamp-2 mt-0.5"
                                >
                                    {{ card.work_order_hold_reason }}
                                </p>
                            </div>

                            <!-- Labor chip (only if someone is on a timer) -->
                            <div
                                v-if="card.labor && card.labor.active_user_name"
                                class="mb-2 flex items-center gap-1.5"
                            >
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold',
                                        card.labor.active_status === 'running'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            card.labor.active_status === 'running'
                                                ? 'bg-emerald-500 animate-pulse'
                                                : 'bg-amber-500',
                                        ]"
                                    ></span>
                                    {{
                                        card.labor.active_status === 'running'
                                            ? $t('workshop.board.labor_running')
                                            : $t('workshop.board.labor_paused')
                                    }}
                                </span>
                                <span class="text-[10px] text-gray-600 dark:text-gray-300 truncate">
                                    {{ card.labor.active_user_name }}
                                </span>
                                <span
                                    v-if="card.labor.total_seconds > 0"
                                    class="text-[10px] text-gray-400 dark:text-gray-500 font-mono"
                                    dir="ltr"
                                >
                                    · {{ formatHms(card.labor.total_seconds) }}
                                </span>
                            </div>

                            <!-- Footer: started + due -->
                            <div
                                class="flex items-center justify-between gap-2 pt-2 mt-1 border-t border-gray-100 dark:border-gray-700 text-[11px] text-gray-500 dark:text-gray-400"
                            >
                                <span class="truncate">
                                    <template v-if="card.started_at">
                                        {{ $t('workshop.board.started_at') }}:
                                        {{ formatDate(card.started_at) }}
                                    </template>
                                    <template v-else>
                                        {{ $t('workshop.board.not_started') }}
                                    </template>
                                </span>
                                <span
                                    v-if="card.due_date"
                                    :class="[
                                        'font-mono flex-shrink-0',
                                        card.is_overdue
                                            ? 'text-red-600 dark:text-red-400 font-semibold'
                                            : '',
                                    ]"
                                >
                                    {{ formatDate(card.due_date) }}
                                </span>
                            </div>
                        </Link>

                        <!-- Empty state -->
                        <div
                            v-if="columns[column.key].length === 0"
                            class="flex flex-col items-center justify-center py-10 text-center"
                        >
                            <div
                                class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center mb-2"
                            >
                                <svg
                                    class="w-5 h-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                    />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $t('workshop.board.empty_column') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const props = defineProps({
    columns: { type: Object, required: true },
    statuses: { type: Array, required: true },
    counts: { type: Object, required: true },
});

// Column definitions — single source of truth for the visual order,
// labels and the dot/header color of each status column.
const STATUS_META = {
    pending: {
        key: 'pending',
        labelKey: 'workshop.board.departments.pending',
        dotClass: 'bg-slate-400',
        badgeClass: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
    in_progress: {
        key: 'in_progress',
        labelKey: 'workshop.board.departments.in_progress',
        dotClass: 'bg-blue-500',
        badgeClass: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    },
    on_hold: {
        key: 'on_hold',
        labelKey: 'workshop.board.departments.on_hold',
        dotClass: 'bg-amber-500',
        badgeClass: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    ready_for_qc: {
        key: 'ready_for_qc',
        labelKey: 'workshop.board.departments.ready_for_qc',
        dotClass: 'bg-emerald-500',
        badgeClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
};

const boardColumns = computed(() =>
    props.statuses.map((status) => ({
        ...STATUS_META[status],
        label: t(STATUS_META[status].labelKey),
    }))
);

// Format seconds as a compact HH:MM:SS / MM:SS string for the
// per-card labor summary on the board.
function formatHms(total) {
    if (!total || total < 0) return '00:00';
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
}

// Format a date string (ISO / Y-m-d) using the browser locale — kept simple
// and dependency-free to avoid RangeError if the i18n key is ever missing.
function formatDate(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    try {
        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(d);
    } catch (e) {
        return value;
    }
}
</script>
