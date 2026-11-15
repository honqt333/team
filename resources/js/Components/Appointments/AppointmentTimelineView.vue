<template>
    <div class="space-y-4">
        <!-- Calendar Sub-toolbar -->
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border-subtle bg-surface-primary p-3 shadow-2xs"
        >
            <!-- Date Navigation -->
            <div class="flex flex-wrap items-center gap-2">
                <AppButton type="button" size="xs" variant="secondary" @click="shiftAnchor(-1)">
                    {{ $t('appointments.prev') }}
                </AppButton>
                <span
                    class="px-3 py-1.5 rounded-xl bg-surface-secondary/70 border border-border-subtle text-xs font-bold text-content-primary"
                >
                    {{ headerDateLabel }}
                </span>
                <AppButton type="button" size="xs" variant="secondary" @click="shiftAnchor(1)">
                    {{ $t('appointments.next') }}
                </AppButton>
                <AppButton type="button" size="xs" variant="ghost" @click="goToday">
                    {{ $t('appointments.today') }}
                </AppButton>
            </div>

            <!-- Calendar Mode Switcher (Day / Week / Month) -->
            <div
                class="inline-flex items-center gap-1 p-1 rounded-xl border border-border-subtle bg-surface-secondary/60"
            >
                <button
                    v-for="mode in calendarModes"
                    :key="mode.key"
                    type="button"
                    @click="setMode(mode.key)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                    :class="
                        calendarMode === mode.key
                            ? 'bg-action-primary text-white shadow-xs'
                            : 'text-content-secondary hover:text-content-primary'
                    "
                >
                    {{ mode.label }}
                </button>
            </div>
        </div>

        <!-- 1. DAY / BAYS TIME-GRID VIEW -->
        <div
            v-if="calendarMode === 'day'"
            class="rounded-2xl border border-border-subtle bg-surface-primary shadow-2xs overflow-hidden"
        >
            <div class="overflow-x-auto">
                <div class="min-w-[780px]">
                    <!-- Bays Header Row -->
                    <div
                        class="grid border-b border-border-subtle bg-surface-secondary/60"
                        :style="dayGridStyle"
                    >
                        <div
                            class="p-3 text-xs font-bold text-content-muted text-center border-e border-border-subtle"
                        >
                            {{ $t('appointments.hour') }}
                        </div>
                        <div
                            v-for="col in dayColumns"
                            :key="col.key"
                            class="p-3 border-e border-border-subtle last:border-e-0 flex items-center justify-between gap-2"
                        >
                            <span class="text-xs font-bold text-content-primary truncate">
                                {{ col.name }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface-primary text-content-secondary border border-border-subtle shrink-0"
                            >
                                {{ toEnglish(col.items.length) }}
                            </span>
                        </div>
                    </div>

                    <!-- Time Grid Body -->
                    <div class="relative grid" :style="dayGridStyle">
                        <!-- Current Time Indicator Line -->
                        <div
                            v-if="isAnchorToday && currentTimeOffsetPx !== null"
                            class="pointer-events-none absolute start-[76px] end-0 z-20 flex items-center"
                            :style="{ top: `${currentTimeOffsetPx}px` }"
                        >
                            <span class="w-2.5 h-2.5 -ms-1.5 rounded-full bg-rose-500"></span>
                            <div class="flex-1 border-t-2 border-rose-500/80"></div>
                        </div>

                        <!-- Hours Column -->
                        <div class="border-e border-border-subtle bg-surface-secondary/30">
                            <div
                                v-for="hour in HOURS"
                                :key="hour"
                                class="h-[64px] border-b border-border-subtle last:border-b-0 flex items-start justify-center pt-1.5 text-[11px] font-bold text-content-muted"
                                dir="ltr"
                            >
                                {{ hour }}
                            </div>
                        </div>

                        <!-- Bay Columns -->
                        <div
                            v-for="col in dayColumns"
                            :key="col.key"
                            class="relative border-e border-border-subtle last:border-e-0"
                        >
                            <!-- Empty Clickable Hour Slots -->
                            <div
                                v-for="hour in HOURS"
                                :key="hour"
                                @click="onSlotClick(col.bayId, hour)"
                                class="group h-[64px] border-b border-border-subtle last:border-b-0 transition-colors flex items-center justify-center"
                                :class="
                                    canCreate
                                        ? 'hover:bg-action-primary/5 cursor-pointer'
                                        : ''
                                "
                            >
                                <span
                                    v-if="canCreate"
                                    class="opacity-0 group-hover:opacity-100 text-[11px] font-bold text-action-primary transition-opacity"
                                >
                                    + {{ hour }}
                                </span>
                            </div>

                            <!-- Appointment Blocks -->
                            <button
                                v-for="appt in col.items"
                                :key="appt.id"
                                type="button"
                                @click.stop="emit('edit', appt)"
                                class="absolute start-1.5 end-1.5 z-10 rounded-xl border border-border-subtle p-2 text-start shadow-xs hover:shadow-md hover:border-action-primary/50 transition-all cursor-pointer overflow-hidden"
                                :class="statusClass(appt.status)"
                                :style="appointmentBlockStyle(appt)"
                            >
                                <div class="flex items-center justify-between gap-1">
                                    <span class="text-[11px] font-bold truncate">
                                        {{ appt.customer?.name || '—' }}
                                    </span>
                                    <span
                                        class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-surface-primary/70 text-content-primary shrink-0"
                                        dir="ltr"
                                    >
                                        {{ formatTime(appt.scheduled_at) }}
                                    </span>
                                </div>
                                <div
                                    class="mt-0.5 flex items-center justify-between gap-1 text-[10px] opacity-90"
                                >
                                    <span class="truncate">
                                        {{
                                            appt.services?.length
                                                ? appt.services.map((s) => s.name).join('، ')
                                                : appt.assigned_user?.name || '—'
                                        }}
                                    </span>
                                    <SaudiPlateDisplay
                                        v-if="appt.vehicle?.plate_number"
                                        :plate-number="appt.vehicle.plate_number"
                                        size="sm"
                                        class="shrink-0 scale-90 origin-end"
                                    />
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. WEEK VIEW -->
        <div
            v-else-if="calendarMode === 'week'"
            class="rounded-2xl border border-border-subtle bg-surface-primary shadow-2xs overflow-hidden"
        >
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 divide-y sm:divide-y-0 sm:divide-x sm:divide-x-reverse divide-border-subtle"
            >
                <div
                    v-for="day in weekDays"
                    :key="day.ymd"
                    class="min-h-[260px] p-3 space-y-2"
                    :class="day.isToday ? 'bg-action-primary/5' : ''"
                >
                    <button
                        type="button"
                        @click="jumpToDay(day.ymd)"
                        class="w-full flex items-center justify-between pb-2 border-b border-border-subtle hover:text-action-primary transition-colors cursor-pointer"
                    >
                        <span
                            class="text-xs font-bold"
                            :class="day.isToday ? 'text-action-primary' : 'text-content-primary'"
                        >
                            {{ day.label }}
                        </span>
                        <span
                            class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-surface-secondary text-content-secondary"
                        >
                            {{ toEnglish(day.items.length) }}
                        </span>
                    </button>

                    <div v-if="!day.items.length" class="text-[11px] text-content-muted text-center py-6">
                        {{ $t('appointments.no_appointments_in_bay') }}
                    </div>

                    <div v-else class="space-y-2">
                        <button
                            v-for="appt in day.items"
                            :key="appt.id"
                            type="button"
                            @click="emit('edit', appt)"
                            class="w-full text-start p-2 rounded-xl border border-border-subtle text-[11px] space-y-1 cursor-pointer hover:opacity-90 transition"
                            :class="statusClass(appt.status)"
                        >
                            <div class="font-bold flex items-center justify-between gap-1">
                                <span class="truncate">{{ appt.customer?.name || '—' }}</span>
                                <span dir="ltr" class="shrink-0">{{ formatTime(appt.scheduled_at) }}</span>
                            </div>
                            <div class="text-[10px] opacity-85 truncate">
                                {{ appt.service_bay?.name || $t('appointments.unassigned_bay') }}
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. MONTH VIEW -->
        <div
            v-else
            class="rounded-2xl border border-border-subtle bg-surface-primary shadow-2xs overflow-hidden"
        >
            <div class="grid grid-cols-7 border-b border-border-subtle bg-surface-secondary/60">
                <div
                    v-for="dayName in weekDayHeaders"
                    :key="dayName"
                    class="p-2.5 text-center text-xs font-bold text-content-muted"
                >
                    {{ dayName }}
                </div>
            </div>

            <div class="grid grid-cols-7 divide-x divide-x-reverse divide-y divide-border-subtle">
                <div
                    v-for="cell in monthCells"
                    :key="cell.ymd"
                    @click="jumpToDay(cell.ymd)"
                    class="min-h-[96px] p-2 hover:bg-surface-secondary/40 transition cursor-pointer space-y-1"
                    :class="[
                        !cell.inMonth ? 'opacity-45 bg-surface-secondary/20' : '',
                        cell.isToday ? 'bg-action-primary/5' : '',
                    ]"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold"
                            :class="
                                cell.isToday
                                    ? 'px-1.5 py-0.5 rounded-full bg-action-primary text-white'
                                    : 'text-content-primary'
                            "
                        >
                            {{ toEnglish(cell.dayNumber) }}
                        </span>
                        <span
                            v-if="cell.items.length"
                            class="text-[10px] font-bold text-action-primary"
                        >
                            {{ toEnglish(cell.items.length) }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <button
                            v-for="appt in cell.items.slice(0, 2)"
                            :key="appt.id"
                            type="button"
                            @click.stop="emit('edit', appt)"
                            class="w-full text-start truncate text-[10px] px-1.5 py-0.5 rounded font-medium cursor-pointer"
                            :class="statusClass(appt.status)"
                        >
                            <span dir="ltr">{{ formatTime(appt.scheduled_at) }}</span>
                            {{ appt.customer?.name || '—' }}
                        </button>
                        <div
                            v-if="cell.items.length > 2"
                            class="text-[10px] text-content-muted font-medium"
                        >
                            {{ $t('appointments.more_count', { count: toEnglish(cell.items.length - 2) }) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AppButton from '@/Components/App/AppButton.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    bays: { type: Array, default: () => [] },
    statusClass: { type: Function, required: true },
    formatTime: { type: Function, required: true },
    selectedDate: { type: String, default: '' },
    canCreate: { type: Boolean, default: false },
});

const emit = defineEmits(['edit', 'create-at', 'range-change']);
const { t, locale } = useI18n();
const { toEnglish } = useNumberFormat();

const START_HOUR = 8;
const END_HOUR = 21;
const ROW_HEIGHT = 64;
const HOURS = Array.from({ length: END_HOUR - START_HOUR + 1 }, (_, i) =>
    `${String(START_HOUR + i).padStart(2, '0')}:00`
);

const calendarMode = ref('day');
const anchorYmd = ref(props.selectedDate || formatYmd(new Date()));

watch(
    () => props.selectedDate,
    (val) => {
        if (val) {
            anchorYmd.value = val;
        }
    }
);

const calendarModes = computed(() => [
    { key: 'day', label: t('appointments.calendar_day_bays') },
    { key: 'week', label: t('appointments.calendar_week') },
    { key: 'month', label: t('appointments.calendar_month') },
]);

function formatYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function parseYmd(ymd) {
    const [y, m, d] = (ymd || formatYmd(new Date())).split('-').map(Number);
    return new Date(y, m - 1, d);
}

function apptDateYmd(appt) {
    if (!appt?.scheduled_at) return '';
    return String(appt.scheduled_at).slice(0, 10);
}

const todayYmd = computed(() => formatYmd(new Date()));
const isAnchorToday = computed(() => anchorYmd.value === todayYmd.value);

const currentTimeOffsetPx = computed(() => {
    const now = new Date();
    const minutes = now.getHours() * 60 + now.getMinutes();
    const startMin = START_HOUR * 60;
    const endMin = (END_HOUR + 1) * 60;
    if (minutes < startMin || minutes > endMin) return null;
    return ((minutes - startMin) / 60) * ROW_HEIGHT;
});

const headerDateLabel = computed(() => {
    const d = parseYmd(anchorYmd.value);
    const loc = locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US';
    if (calendarMode.value === 'month') {
        return d.toLocaleDateString(loc, { year: 'numeric', month: 'long' });
    }
    return d.toLocaleDateString(loc, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
});

// Day / Bays columns
const dayAppointments = computed(() =>
    props.appointments.filter((a) => apptDateYmd(a) === anchorYmd.value)
);

const dayColumns = computed(() => {
    const cols = props.bays.map((bay) => ({
        key: `bay-${bay.id}`,
        bayId: bay.id,
        name: bay.name,
        items: dayAppointments.value.filter((a) => a.service_bay?.id === bay.id),
    }));

    const unassigned = dayAppointments.value.filter((a) => !a.service_bay?.id);
    if (unassigned.length > 0 || cols.length === 0) {
        cols.push({
            key: 'unassigned',
            bayId: '',
            name: t('appointments.unassigned_bay'),
            items: unassigned,
        });
    }

    return cols;
});

const dayGridStyle = computed(() => ({
    gridTemplateColumns: `76px repeat(${dayColumns.value.length}, minmax(160px, 1fr))`,
}));

function appointmentBlockStyle(appt) {
    const start = new Date(appt.scheduled_at);
    const end = appt.estimated_end_at ? new Date(appt.estimated_end_at) : null;
    const startMin = start.getHours() * 60 + start.getMinutes();
    const gridStartMin = START_HOUR * 60;
    const clampedStart = Math.max(startMin, gridStartMin);
    const durationMin = end
        ? Math.max((end.getTime() - start.getTime()) / 60000, 30)
        : 45;

    const top = ((clampedStart - gridStartMin) / 60) * ROW_HEIGHT + 2;
    const height = Math.max((durationMin / 60) * ROW_HEIGHT - 4, 42);

    return {
        top: `${top}px`,
        height: `${height}px`,
    };
}

// Week days (Saturday -> Friday)
function startOfWeekSaturday(date) {
    const d = new Date(date);
    const day = d.getDay(); // 0 Sun .. 6 Sat
    const diff = (day + 1) % 7;
    d.setDate(d.getDate() - diff);
    return d;
}

const weekDays = computed(() => {
    const start = startOfWeekSaturday(parseYmd(anchorYmd.value));
    const loc = locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US';
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        const ymd = formatYmd(d);
        return {
            ymd,
            isToday: ymd === todayYmd.value,
            label: d.toLocaleDateString(loc, { weekday: 'short', day: 'numeric', month: 'numeric' }),
            items: props.appointments.filter((a) => apptDateYmd(a) === ymd),
        };
    });
});

// Month grid (Saturday -> Friday)
const weekDayHeaders = computed(() => {
    const start = startOfWeekSaturday(new Date(2026, 0, 3)); // Saturday
    const loc = locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US';
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        return d.toLocaleDateString(loc, { weekday: 'short' });
    });
});

const monthCells = computed(() => {
    const anchor = parseYmd(anchorYmd.value);
    const firstOfMonth = new Date(anchor.getFullYear(), anchor.getMonth(), 1);
    const gridStart = startOfWeekSaturday(firstOfMonth);
    const totalCells = 42;

    return Array.from({ length: totalCells }, (_, i) => {
        const d = new Date(gridStart);
        d.setDate(gridStart.getDate() + i);
        const ymd = formatYmd(d);
        return {
            ymd,
            dayNumber: d.getDate(),
            inMonth: d.getMonth() === anchor.getMonth(),
            isToday: ymd === todayYmd.value,
            items: props.appointments.filter((a) => apptDateYmd(a) === ymd),
        };
    });
});

function emitRangeChange() {
    if (calendarMode.value === 'day') {
        emit('range-change', { date: anchorYmd.value, date_from: '', date_to: '' });
    } else if (calendarMode.value === 'week') {
        const start = startOfWeekSaturday(parseYmd(anchorYmd.value));
        const end = new Date(start);
        end.setDate(start.getDate() + 6);
        emit('range-change', { date: '', date_from: formatYmd(start), date_to: formatYmd(end) });
    } else {
        const anchor = parseYmd(anchorYmd.value);
        const first = new Date(anchor.getFullYear(), anchor.getMonth(), 1);
        const last = new Date(anchor.getFullYear(), anchor.getMonth() + 1, 0);
        emit('range-change', { date: '', date_from: formatYmd(first), date_to: formatYmd(last) });
    }
}

function setMode(mode) {
    calendarMode.value = mode;
    emitRangeChange();
}

function shiftAnchor(delta) {
    const d = parseYmd(anchorYmd.value);
    if (calendarMode.value === 'day') {
        d.setDate(d.getDate() + delta);
    } else if (calendarMode.value === 'week') {
        d.setDate(d.getDate() + delta * 7);
    } else {
        d.setMonth(d.getMonth() + delta);
    }
    anchorYmd.value = formatYmd(d);
    emitRangeChange();
}

function goToday() {
    anchorYmd.value = todayYmd.value;
    emitRangeChange();
}

function jumpToDay(ymd) {
    anchorYmd.value = ymd;
    calendarMode.value = 'day';
    emitRangeChange();
}

function onSlotClick(bayId, hour) {
    if (!props.canCreate) return;
    emit('create-at', {
        service_bay_id: bayId || '',
        scheduled_at: `${anchorYmd.value}T${hour}`,
    });
}
</script>
