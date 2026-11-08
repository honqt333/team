<template>
    <div
        class="standard-report-footer mt-6 pt-3 border-t border-gray-300 text-[10px] text-gray-500"
    >
        <div class="flex items-center justify-between flex-wrap gap-2">
            <!-- Prepared by (User) -->
            <div class="flex items-center gap-1.5">
                <span class="text-gray-400">
                    {{ preparedByLabel || $t('common.prepared_by') || 'تمت الطباعة بواسطة' }}:
                </span>
                <span class="font-bold text-gray-700">{{ userName }}</span>
            </div>

            <!-- Optional Center Note or Summary -->
            <div class="text-center font-mono text-[9px] text-gray-400">
                <slot name="center-note">
                    <span>{{ systemName }}</span>
                </slot>
            </div>

            <!-- Date & Time of Print -->
            <div class="flex items-center gap-1.5 font-mono" dir="ltr">
                <span class="text-gray-400">{{ $t('work_orders.print_view.date') }}:</span>
                <span class="text-gray-700">{{ printTimestamp }}</span>
            </div>
        </div>

        <slot />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    preparedBy: {
        type: String,
        default: '',
    },
    preparedByLabel: {
        type: String,
        default: '',
    },
});

const page = usePage();

const userName = computed(() => {
    return props.preparedBy || page.props.auth?.user?.name || 'Admin';
});

const systemName = computed(() => {
    return page.props.tenant?.name || 'Khidmah Pro';
});

const printTimestamp = computed(() => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
});
</script>

<style scoped>
@media print {
    .standard-report-footer {
        border-top: 1px solid #d1d5db !important;
    }
}
</style>
