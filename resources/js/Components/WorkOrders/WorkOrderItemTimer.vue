<template>
    <div
        class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/30 px-2.5 py-1.5"
    >
        <div class="flex items-center gap-2 min-w-0">
            <span
                v-if="state === 'running'"
                class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ labels.running }}
            </span>
            <span
                v-else-if="state === 'paused'"
                class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                {{ labels.paused }}
            </span>
            <span
                v-else
                class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                {{ labels.idle }}
            </span>

            <span
                class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100"
                dir="ltr"
            >
                {{ displayTime }}
            </span>

            <span
                v-if="totalSeconds > 0 && state !== 'running'"
                class="text-[10px] text-gray-400 dark:text-gray-500"
                dir="ltr"
            >
                · {{ labels.total }} {{ totalDisplay }}
            </span>
        </div>

        <div class="flex items-center gap-1 flex-shrink-0">
            <button
                v-if="state === 'idle' && canStart && !readOnly"
                type="button"
                @click="start"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50"
                :title="labels.start"
            >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
                {{ labels.start }}
            </button>

            <button
                v-if="state === 'running' && isOwn && !readOnly"
                type="button"
                @click="pause"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-amber-500 hover:bg-amber-600 text-white disabled:opacity-50"
                :title="labels.pause"
            >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 5h4v14H6zM14 5h4v14h-4z" />
                </svg>
                {{ labels.pause }}
            </button>

            <button
                v-if="state === 'paused' && isOwn && !readOnly"
                type="button"
                @click="resume"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50"
                :title="labels.resume"
            >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
                {{ labels.resume }}
            </button>

            <button
                v-if="(state === 'running' || state === 'paused') && isOwn && !readOnly"
                type="button"
                @click="end"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-gray-600 hover:bg-gray-700 text-white disabled:opacity-50"
                :title="labels.end"
            >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M6 6h12v12H6z" />
                </svg>
                {{ labels.end }}
            </button>

            <span
                v-if="state !== 'idle' && !isOwn"
                class="text-[10px] italic text-gray-500 dark:text-gray-400"
            >
                {{ labels.otherTech }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    workOrder: { type: Object, required: true },
    item: { type: Object, required: true },
    // All labor entries for THIS item, regardless of user.
    laborEntries: { type: Array, default: () => [] },
    readOnly: { type: Boolean, default: false },
});

const { t } = useI18n();
const page = usePage();
const currentUserId = computed(() => page.props?.auth?.user?.id ?? null);

const busy = ref(false);
const now = ref(Date.now());
let tickHandle = null;

const labels = {
    start: t('work_orders.labor.start'),
    pause: t('work_orders.labor.pause'),
    resume: t('work_orders.labor.resume'),
    end: t('work_orders.labor.end'),
    running: t('work_orders.labor.running'),
    paused: t('work_orders.labor.paused'),
    idle: t('work_orders.labor.idle'),
    total: t('work_orders.labor.total'),
    otherTech: t('work_orders.labor.other_tech'),
};

const ACTIVE_STATUSES = ['running', 'paused'];
const itemIsClosed = computed(() => ['completed', 'cancelled'].includes(props.item.status));

const myEntries = computed(() =>
    props.laborEntries.filter((e) => e.user_id === currentUserId.value)
);

const otherEntries = computed(() =>
    props.laborEntries.filter((e) => e.user_id !== currentUserId.value)
);

// My currently open session (if any) — drives the buttons.
const myActive = computed(
    () => myEntries.value.find((e) => ACTIVE_STATUSES.includes(e.status)) || null
);

// Anyone else's open session — we show a "running" hint but no controls.
const otherActive = computed(
    () => otherEntries.value.find((e) => ACTIVE_STATUSES.includes(e.status)) || null
);

const state = computed(() => {
    if (myActive.value) return myActive.value.status; // 'running' or 'paused'
    if (otherActive.value) return otherActive.value.status;
    return 'idle';
});

const isOwn = computed(() => !!myActive.value);
// P1 revision: an assigned technician can start their own timer on
// this item even when another technician is already on a session
// here. The only things that block Start are:
//   1. the item is completed/cancelled
//   2. the current user already has their own open session
// We do NOT block on otherActive.value — multiple technicians can
// share a service item, each with their own session.
const canStart = computed(() => !itemIsClosed.value && !myActive.value);

// Total accumulated effective seconds across all entries for this item.
const totalSeconds = computed(() => {
    return props.laborEntries.reduce((acc, e) => acc + (e.effective_duration_seconds || 0), 0);
});

// Live-ticking time for the currently-active entry.
const liveSeconds = computed(() => {
    const active = myActive.value || otherActive.value;
    if (!active) return 0;
    const startedMs = new Date(active.started_at).getTime();
    const pausedTotal = (active.paused_duration_seconds || 0) * 1000;
    let liveMs = now.value - startedMs - pausedTotal;
    if (active.status === 'paused' && active.paused_at) {
        liveMs -= now.value - new Date(active.paused_at).getTime();
    }
    return Math.max(0, Math.floor(liveMs / 1000));
});

const displayTime = computed(() => {
    const seconds = state.value === 'idle' ? totalSeconds.value : liveSeconds.value;
    return formatHms(seconds);
});

const totalDisplay = computed(() => formatHms(totalSeconds.value));

function formatHms(total) {
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60);
    const s = total % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
}

// Local 1-second tick so the running timer updates between server
// round-trips. No polling or websocket — we just increment `now`.
// (The reload on each action resets it anyway.)
if (!tickHandle) {
    tickHandle = setInterval(() => {
        if (state.value === 'running') {
            now.value = Date.now();
        }
    }, 1000);
}

onBeforeUnmount(() => {
    if (tickHandle) {
        clearInterval(tickHandle);
        tickHandle = null;
    }
});

// ==================== API calls ====================

async function start() {
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.items.labor.start', {
                work_order: props.workOrder.id,
                item: props.item.id,
            })
        );
        router.reload({ only: ['workOrder', 'laborEntries'] });
    } finally {
        busy.value = false;
    }
}

async function pause() {
    if (!myActive.value) return;
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.labor.pause', {
                work_order: props.workOrder.id,
                entry: myActive.value.id,
            })
        );
        router.reload({ only: ['workOrder', 'laborEntries'] });
    } finally {
        busy.value = false;
    }
}

async function resume() {
    if (!myActive.value) return;
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.labor.resume', {
                work_order: props.workOrder.id,
                entry: myActive.value.id,
            })
        );
        router.reload({ only: ['workOrder', 'laborEntries'] });
    } finally {
        busy.value = false;
    }
}

async function end() {
    if (!myActive.value) return;
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.labor.end', {
                work_order: props.workOrder.id,
                entry: myActive.value.id,
            })
        );
        router.reload({ only: ['workOrder', 'laborEntries'] });
    } finally {
        busy.value = false;
    }
}
</script>
