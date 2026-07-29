<template>
    <div class="mt-2 space-y-2">
        <!-- Hold banner (only when held) -->
        <div
            v-if="item.status === 'on_hold'"
            class="rounded-md border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-3 py-2"
        >
            <p
                class="text-[10px] font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide"
            >
                {{ labels.hold_title }}
            </p>
            <p
                v-if="item.hold_reason"
                class="text-xs text-amber-900 dark:text-amber-100 mt-0.5 line-clamp-3"
            >
                {{ item.hold_reason }}
            </p>
            <p
                v-if="item.held_at || item.held_by"
                class="text-[10px] text-amber-700/80 dark:text-amber-300/80 mt-1"
            >
                <template v-if="item.held_by">
                    {{ labels.held_by }}:
                    <strong>{{ item.held_by.name }}</strong>
                </template>
                <template v-if="item.held_at">· {{ formatDate(item.held_at) }}</template>
            </p>
        </div>

        <!-- Last quality check summary -->
        <div
            v-if="latestCheck"
            :class="[
                'rounded-md px-3 py-2 border text-[11px]',
                latestCheck.decision === 'approved'
                    ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20'
                    : 'border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-900/20',
            ]"
        >
            <p
                :class="[
                    'font-semibold uppercase tracking-wide text-[10px]',
                    latestCheck.decision === 'approved'
                        ? 'text-emerald-700 dark:text-emerald-300'
                        : 'text-rose-700 dark:text-rose-300',
                ]"
            >
                {{
                    latestCheck.decision === 'approved'
                        ? labels.last_approved
                        : labels.last_rejected
                }}
            </p>
            <p
                v-if="latestCheck.notes"
                class="text-xs mt-0.5 line-clamp-3"
                :class="
                    latestCheck.decision === 'approved'
                        ? 'text-emerald-900 dark:text-emerald-100'
                        : 'text-rose-900 dark:text-rose-100'
                "
            >
                {{ latestCheck.notes }}
            </p>
            <p class="text-[10px] mt-1 opacity-80">
                <template v-if="latestCheck.checked_by">
                    {{ labels.checked_by }}:
                    <strong>{{ latestCheck.checked_by.name }}</strong>
                </template>
                <template v-if="latestCheck.checked_at">
                    · {{ formatDate(latestCheck.checked_at) }}
                </template>
            </p>
        </div>

        <!-- Action buttons row -->
        <div v-if="!readOnly" class="flex flex-wrap items-center gap-1.5">
            <!-- Hold button: only when not held + not terminal -->
            <button
                v-if="canHold"
                type="button"
                @click="openHold"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-amber-500 hover:bg-amber-600 text-white disabled:opacity-50"
            >
                {{ labels.hold }}
            </button>

            <!-- Resume button: only when on hold -->
            <button
                v-if="canResume"
                type="button"
                @click="resume"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50"
            >
                {{ labels.resume }}
            </button>

            <!-- QC actions: only when ready_for_qc -->
            <button
                v-if="canApproveQuality"
                type="button"
                @click="approve"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50"
            >
                {{ labels.approve_quality }}
            </button>
            <button
                v-if="canApproveQuality"
                type="button"
                @click="openReject"
                :disabled="busy"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-bold bg-rose-600 hover:bg-rose-700 text-white disabled:opacity-50"
            >
                {{ labels.reject_quality }}
            </button>
        </div>

        <!-- Hold modal -->
        <div
            v-if="showHoldModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="showHoldModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-5">
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                    {{ labels.hold_title }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ labels.hold_hint }}
                </p>
                <textarea
                    v-model="holdReason"
                    rows="3"
                    class="mt-3 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm p-2 text-gray-900 dark:text-gray-100"
                    :placeholder="labels.hold_placeholder"
                ></textarea>
                <div class="mt-3 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showHoldModal = false"
                        class="px-3 py-1.5 rounded-md text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        {{ labels.cancel }}
                    </button>
                    <button
                        type="button"
                        @click="confirmHold"
                        :disabled="!holdReason.trim() || busy"
                        class="px-3 py-1.5 rounded-md text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white disabled:opacity-50"
                    >
                        {{ labels.hold }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject modal -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="showRejectModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-5">
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                    {{ labels.reject_title }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ labels.reject_hint }}
                </p>
                <textarea
                    v-model="rejectNotes"
                    rows="3"
                    class="mt-3 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm p-2 text-gray-900 dark:text-gray-100"
                    :placeholder="labels.reject_placeholder"
                ></textarea>
                <div class="mt-3 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showRejectModal = false"
                        class="px-3 py-1.5 rounded-md text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        {{ labels.cancel }}
                    </button>
                    <button
                        type="button"
                        @click="confirmReject"
                        :disabled="!rejectNotes.trim() || busy"
                        class="px-3 py-1.5 rounded-md text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white disabled:opacity-50"
                    >
                        {{ labels.reject_quality }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    workOrder: { type: Object, required: true },
    item: { type: Object, required: true },
    readOnly: { type: Boolean, default: false },
});

const { t } = useI18n();
const busy = ref(false);
const showHoldModal = ref(false);
const holdReason = ref('');
const showRejectModal = ref(false);
const rejectNotes = ref('');

const labels = {
    hold: t('work_orders.status_controls.hold'),
    hold_title: t('work_orders.status_controls.hold_title'),
    hold_hint: t('work_orders.status_controls.hold_hint'),
    hold_placeholder: t('work_orders.status_controls.hold_placeholder'),
    resume: t('work_orders.status_controls.resume'),
    approve_quality: t('work_orders.status_controls.approve_quality'),
    reject_quality: t('work_orders.status_controls.reject_quality'),
    reject_title: t('work_orders.status_controls.reject_title'),
    reject_hint: t('work_orders.status_controls.reject_hint'),
    reject_placeholder: t('work_orders.status_controls.reject_placeholder'),
    cancel: t('common.cancel'),
    held_by: t('work_orders.status_controls.held_by'),
    checked_by: t('work_orders.status_controls.checked_by'),
    last_approved: t('work_orders.status_controls.last_approved'),
    last_rejected: t('work_orders.status_controls.last_rejected'),
};

const TERMINAL = ['completed', 'cancelled'];
const isTerminal = computed(() => TERMINAL.includes(props.item.status));

const canHold = computed(() => !isTerminal.value && props.item.status !== 'on_hold');
const canResume = computed(() => props.item.status === 'on_hold');
const canApproveQuality = computed(() => props.item.status === 'ready_for_qc');

const latestCheck = computed(() => {
    const checks = props.item.quality_checks;
    if (!checks || !checks.length) return null;
    // Controller already returns the latest first; double-check just in case.
    return checks[0];
});

function formatDate(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    try {
        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(d);
    } catch (e) {
        return value;
    }
}

function openHold() {
    holdReason.value = '';
    showHoldModal.value = true;
}

function openReject() {
    rejectNotes.value = '';
    showRejectModal.value = true;
}

async function confirmHold() {
    const reason = holdReason.value.trim();
    if (!reason) return;
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.items.hold', {
                work_order: props.workOrder.id,
                item: props.item.id,
            }),
            { reason }
        );
        showHoldModal.value = false;
        router.reload({ only: ['workOrder'] });
    } finally {
        busy.value = false;
    }
}

async function resume() {
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.items.resume', {
                work_order: props.workOrder.id,
                item: props.item.id,
            })
        );
        router.reload({ only: ['workOrder'] });
    } finally {
        busy.value = false;
    }
}

async function approve() {
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.items.quality.approve', {
                work_order: props.workOrder.id,
                item: props.item.id,
            })
        );
        router.reload({ only: ['workOrder'] });
    } finally {
        busy.value = false;
    }
}

async function confirmReject() {
    const notes = rejectNotes.value.trim();
    if (!notes) return;
    busy.value = true;
    try {
        await axios.post(
            route('work-orders.items.quality.reject', {
                work_order: props.workOrder.id,
                item: props.item.id,
            }),
            { notes }
        );
        showRejectModal.value = false;
        router.reload({ only: ['workOrder'] });
    } finally {
        busy.value = false;
    }
}
</script>
