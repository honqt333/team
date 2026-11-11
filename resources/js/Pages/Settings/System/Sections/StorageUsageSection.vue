<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    storageUsage: {
        type: Object,
        default: null,
    },
});

const { t } = useI18n();
const { toEnglish } = useNumberFormat();

const isUnlimited = computed(() => Boolean(props.storageUsage?.is_unlimited));
const isExceeded = computed(() => Boolean(props.storageUsage?.is_exceeded));
const usagePercentage = computed(() => Number(props.storageUsage?.usage_percentage ?? 0));
const usedMb = computed(() => Number(props.storageUsage?.used_mb ?? 0));
const usedGb = computed(() => Number(props.storageUsage?.used_gb ?? 0));
const allocatedGb = computed(() => Number(props.storageUsage?.allocated_gb ?? 0));

const progressWidth = computed(() => {
    if (isUnlimited.value) return '5%';
    if (!props.storageUsage) return '0%';
    return `${Math.min(100, Math.max(usagePercentage.value > 0 ? 1 : 0, usagePercentage.value))}%`;
});

const usedLabel = computed(() =>
    usedMb.value > 1024 ? `${toEnglish(usedGb.value)} GB` : `${toEnglish(usedMb.value)} MB`
);

const allocatedLabel = computed(() =>
    isUnlimited.value ? t('system.billing.storage.unlimited') : `${toEnglish(allocatedGb.value)} GB`
);
</script>

<template>
    <div class="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-action-primary/10 text-action-primary shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-text-primary">
                        {{ t('system.billing.storage.title') }}
                    </h3>
                    <p class="text-xs text-text-secondary mt-0.5">
                        {{ t('system.billing.storage.subtitle') }}
                    </p>
                </div>
            </div>

            <div v-if="storageUsage" class="flex items-center gap-2">
                <span
                    class="text-xs font-semibold px-2.5 py-1 rounded-full"
                    :class="
                        isExceeded
                            ? 'bg-danger-soft text-danger'
                            : usagePercentage > 80
                              ? 'bg-warning-soft text-warning'
                              : 'bg-success-soft text-success'
                    "
                >
                    {{
                        isUnlimited
                            ? t('system.billing.storage.unlimited')
                            : `${toEnglish(usagePercentage)}% ${t('system.billing.storage.used')}`
                    }}
                </span>
            </div>
        </div>

        <div v-if="storageUsage" class="space-y-2">
            <div class="w-full h-3 rounded-full bg-surface-muted overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="
                        isExceeded
                            ? 'bg-danger'
                            : usagePercentage > 80
                              ? 'bg-warning'
                              : 'bg-action-primary'
                    "
                    :style="{ width: progressWidth }"
                ></div>
            </div>

            <div class="flex items-center justify-between text-xs text-text-secondary pt-1">
                <span>
                    {{ t('system.billing.storage.used_space') }}:
                    <strong class="font-mono text-text-primary">
                        {{ usedLabel }}
                    </strong>
                </span>
                <span>
                    {{ t('system.billing.storage.allocated_limit') }}:
                    <strong class="font-mono text-text-primary">
                        {{ allocatedLabel }}
                    </strong>
                </span>
            </div>
        </div>

        <p v-else class="text-sm text-text-muted">
            {{ t('common.no_data') }}
        </p>
    </div>
</template>
