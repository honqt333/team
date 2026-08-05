<template>
    <!--
        StatusBadge — a small pill that shows a friendly
        status label. The internal codes from the backend
        (e.g. "checked_in", "ready_for_qc") are translated
        here into words the customer can understand.

        The mapping lives in a computed `palette` and a
        `friendlyLabel` function so the dashboard does not
        leak raw status keys to the customer.
    -->
    <span
        class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1"
        :style="{
            backgroundColor: palette.bg,
            color: palette.fg,
        }"
    >
        <span
            v-if="showDot"
            class="w-1.5 h-1.5 rounded-full"
            :style="{ backgroundColor: palette.fg }"
        />
        <span>{{ label }}</span>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    // Mapping from internal status code to the human-readable
    // label. The parent passes the already-translated label
    // string (e.g. "في الورشة الآن") so we do not have to
    // route the i18n call through the badge.
    label: { type: String, required: true },
    showDot: { type: Boolean, default: false },
});

const palette = computed(() => {
    switch (props.status) {
        case 'scheduled':
        case 'open':
        case 'draft':
            return { bg: '#e0e7ff', fg: '#3730a3' };
        case 'confirmed':
            return { bg: '#dcfce7', fg: '#166534' };
        case 'checked_in':
        case 'in_progress':
            return { bg: '#fef3c7', fg: '#92400e' };
        case 'ready_for_qc':
            return { bg: '#ede9fe', fg: '#5b21b6' };
        case 'on_hold':
            return { bg: '#fee2e2', fg: '#991b1b' };
        case 'done':
            return { bg: '#d1fae5', fg: '#065f46' };
        case 'cancelled':
            return { bg: '#f1f5f9', fg: '#475569' };
        default:
            return { bg: '#e2e8f0', fg: '#334155' };
    }
});
</script>
