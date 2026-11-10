<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Common/StatusBadge.vue';

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

<template>
    <Badge
        :label="label"
        :show-dot="showDot"
        :dot-color="palette.fg"
        class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full gap-1"
        :style="{ backgroundColor: palette.bg, color: palette.fg }"
    />
</template>
