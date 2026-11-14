<script setup>
import { computed } from 'vue';

const props = defineProps({
    phone: { type: String, default: '' },
    whatsapp: { type: String, default: '' },
    workOrderCode: { type: String, default: '' },
    plateNumber: { type: String, default: '' },
    compact: { type: Boolean, default: true },
});

const telHref = computed(() => {
    const raw = (props.phone || props.whatsapp || '').trim();
    if (!raw) return null;
    const cleaned = raw.replace(/[^\d+]/g, '');
    return cleaned ? `tel:${cleaned}` : null;
});

const whatsappHref = computed(() => {
    const raw = (props.whatsapp || props.phone || '').trim();
    if (!raw) return null;
    const digits = raw.replace(/\D+/g, '');
    if (!digits) return null;

    const parts = [props.workOrderCode, props.plateNumber].filter(Boolean);
    if (!parts.length) {
        return `https://wa.me/${digits}`;
    }

    return `https://wa.me/${digits}?text=${encodeURIComponent(parts.join(' - '))}`;
});
</script>

<template>
    <div v-if="telHref || whatsappHref" class="flex shrink-0 items-center gap-1.5" @click.stop>
        <a
            v-if="telHref"
            :href="telHref"
            :title="phone || whatsapp"
            :aria-label="$t('common.call')"
            class="inline-flex items-center gap-1 rounded-xl bg-blue-50 px-2.5 py-1.5 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-300 dark:hover:bg-blue-900/60"
        >
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                />
            </svg>
            <span v-if="!compact">{{ $t('common.call') }}</span>
        </a>

        <a
            v-if="whatsappHref"
            :href="whatsappHref"
            target="_blank"
            rel="noopener noreferrer"
            :title="whatsapp || phone"
            :aria-label="$t('customers.quick.whatsapp')"
            class="inline-flex items-center gap-1 rounded-xl bg-emerald-50 px-2.5 py-1.5 text-xs font-bold text-emerald-700 transition-colors hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60"
        >
            <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.79 14.07c-.24.68-1.41 1.3-1.95 1.38-.5.07-1.13.1-1.82-.12-.42-.14-.96-.32-1.65-.62-2.9-1.25-4.79-4.18-4.94-4.37-.14-.19-1.18-1.57-1.18-3 0-1.43.75-2.13 1.01-2.42.27-.29.58-.36.78-.36.19 0 .39 0 .56.01.18.01.42-.07.66.5.24.58.82 2 .89 2.15.07.14.12.31.02.5-.1.19-.14.31-.29.48-.14.17-.3.38-.43.51-.14.14-.29.3-.12.59.17.29.75 1.24 1.61 2.01 1.11.99 2.04 1.3 2.33 1.44.29.14.46.12.63-.07.17-.19.72-.84.91-1.13.19-.29.39-.24.65-.14.27.1 1.69.8 1.98.94.29.14.48.22.55.34.07.12.07.7-.17 1.38z"
                />
            </svg>
            <span v-if="!compact">{{ $t('customers.quick.whatsapp') }}</span>
        </a>
    </div>
</template>
