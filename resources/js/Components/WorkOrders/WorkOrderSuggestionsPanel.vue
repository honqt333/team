<template>
    <section
        class="wo-suggestions px-6 py-4 border-b border-gray-200 dark:border-gray-700"
        :aria-busy="isLoading || undefined"
        :aria-label="$t('work_orders.suggestions.panel_title')"
    >
        <!-- ─── Header row ─────────────────────────────────────────────── -->
        <header class="flex items-center justify-between gap-3 mb-3">
            <div class="flex items-center gap-2 min-w-0">
                <span class="wo-suggestions__icon" aria-hidden="true">
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M12 2l2.39 4.84 5.34.78-3.86 3.77.91 5.32L12 14.27 7.22 16.71l.91-5.32L4.27 7.62l5.34-.78L12 2z"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">
                        {{ $t('work_orders.suggestions.panel_title') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ $t('work_orders.suggestions.panel_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span
                    v-if="providerLabel"
                    class="wo-suggestions__provider-badge"
                    :title="$t('work_orders.suggestions.powered_by_ai')"
                >
                    {{ providerLabel }}
                </span>
                <AppButton
                    variant="secondary"
                    size="sm"
                    :loading="isLoading"
                    :disabled="isLoading"
                    :iconLeftPath="'M4 4v6h6M20 20v-6h-6M20 8a8 8 0 0 0-14.94-2M4 16a8 8 0 0 0 14.94 2'"
                    @click="onRefresh"
                >
                    {{ $t('work_orders.suggestions.refresh') }}
                </AppButton>
            </div>
        </header>

        <!-- ─── Body ───────────────────────────────────────────────────── -->
        <div class="wo-suggestions__body" role="region" aria-live="polite" aria-atomic="false">
            <!-- Loading skeleton -->
            <div
                v-if="isLoading && suggestions.length === 0"
                class="space-y-2"
                data-testid="wo-suggestions-skeleton"
            >
                <div v-for="n in 3" :key="n" class="wo-suggestions__skeleton h-16" />
            </div>

            <!-- Error alert -->
            <div
                v-else-if="error"
                class="wo-suggestions__alert"
                role="alert"
                data-testid="wo-suggestions-error"
            >
                <div class="flex items-start gap-2">
                    <svg
                        class="w-4 h-4 mt-0.5 shrink-0"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <span class="text-sm">{{ error }}</span>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-else-if="!isLoading && suggestions.length === 0"
                class="wo-suggestions__empty"
                data-testid="wo-suggestions-empty"
            >
                <span class="text-sm">{{ $t('work_orders.suggestions.empty') }}</span>
            </div>

            <!-- Suggestion cards -->
            <ul
                v-else
                class="grid grid-cols-1 md:grid-cols-2 gap-3"
                data-testid="wo-suggestions-list"
            >
                <li
                    v-for="s in suggestions"
                    :key="s.item_type + ':' + s.item_id"
                    class="wo-suggestions__card"
                >
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <div class="min-w-0">
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate"
                            >
                                {{ s.name }}
                            </p>
                            <p
                                v-if="s.name_en && s.name_en !== s.name"
                                class="text-xs text-gray-500 dark:text-gray-400 truncate"
                            >
                                {{ s.name_en }}
                            </p>
                        </div>
                        <span
                            class="wo-suggestions__pill shrink-0"
                            :class="`wo-suggestions__pill--${confidenceLevel(s.confidence)}`"
                            :title="confidenceTitle(s.confidence)"
                        >
                            {{ confidenceLabel(s.confidence) }}
                        </span>
                    </div>

                    <div
                        class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2"
                    >
                        <span class="wo-suggestions__dept-badge">{{ s.department_name }}</span>
                        <span aria-hidden="true">•</span>
                        <span class="wo-suggestions__type-badge">{{ typeLabel(s.item_type) }}</span>
                    </div>

                    <p
                        v-if="s.reason"
                        class="text-xs text-gray-600 dark:text-gray-300 mb-3 leading-relaxed"
                    >
                        {{ s.reason }}
                    </p>

                    <div class="flex items-center justify-between gap-2">
                        <span class="wo-suggestions__price font-mono" dir="ltr">
                            {{ formatPrice(s) }}
                        </span>
                        <AppButton
                            variant="primary"
                            size="sm"
                            :data-testid="`wo-suggestion-add-${s.item_type}-${s.item_id}`"
                            @click="onAdd(s)"
                        >
                            {{ $t('work_orders.suggestions.add') }}
                        </AppButton>
                    </div>
                </li>
            </ul>
        </div>
        <!-- /wo-suggestions__body -->

        <!-- ─── Footer row ────────────────────────────────────────────── -->
        <footer
            v-if="meta && meta.provider"
            class="mt-3 flex items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400"
        >
            <span>
                {{ $t('work_orders.suggestions.provided_by', { provider: meta.provider }) }}
            </span>
            <a href="#" class="wo-suggestions__settings-link" @click.prevent>
                {{ $t('work_orders.suggestions.settings') }}
            </a>
        </footer>
    </section>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AppButton from '@/Components/App/AppButton.vue';
import { useWorkOrderSuggestions } from '@/Composables/useWorkOrderSuggestions';

const props = defineProps({
    workOrder: { type: Object, required: true },
    endpoint: { type: String, default: null },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['add', 'error']);

const { t } = useI18n();

const { suggestions, isLoading, error, meta, refresh } = useWorkOrderSuggestions({
    workOrder: () => props.workOrder,
    endpoint: props.endpoint,
});

// Re-emit errors so the parent page can toaster them too.
watch(error, (msg) => {
    if (msg) emit('error', { message: msg });
});

// ─── Formatting ──────────────────────────────────────────────────────
/**
 * Convert cents + currency into a localized display string. We do this
 * inline (rather than via useFormatters) because the suggestion payload
 * already gives us cents and a per-item currency code.
 */
function formatPrice(suggestion) {
    const cents = Number(suggestion.estimated_unit_price_cents ?? 0);
    const amount = cents / 100;
    const currency = suggestion.currency || 'SAR';
    try {
        return new Intl.NumberFormat(document.documentElement.lang || 'ar', {
            style: 'currency',
            currency,
            maximumFractionDigits: 2,
        }).format(amount);
    } catch (_e) {
        return `${amount.toFixed(2)} ${currency}`;
    }
}

// ─── Confidence pill ─────────────────────────────────────────────────
function confidenceLevel(confidence) {
    const c = Number(confidence) || 0;
    if (c >= 0.7) return 'high';
    if (c >= 0.4) return 'medium';
    return 'low';
}

function confidenceLabel(confidence) {
    const level = confidenceLevel(confidence);
    return t(`work_orders.suggestions.confidence_${level}`);
}

function confidenceTitle(confidence) {
    const c = Math.round((Number(confidence) || 0) * 100);
    return `${c}%`;
}

// ─── Item type pill ──────────────────────────────────────────────────
function typeLabel(itemType) {
    if (itemType === 'service') return t('work_orders.suggestions.type_service');
    if (itemType === 'part') return t('work_orders.suggestions.type_part');
    return itemType;
}

// ─── Provider badge ──────────────────────────────────────────────────
const providerLabel = computed(() => {
    const provider = meta.value?.provider;
    return provider || null;
});

// ─── Handlers ────────────────────────────────────────────────────────
function onRefresh() {
    refresh();
}

function onAdd(suggestion) {
    emit('add', {
        itemType: suggestion.item_type,
        itemId: Number(suggestion.item_id),
        name: suggestion.name,
        qty: Number(suggestion.qty ?? 1),
        departmentId: Number(suggestion.department_id),
        unitPrice: Number(suggestion.estimated_unit_price_cents ?? 0) / 100,
    });
}
</script>

<style scoped>
.wo-suggestions {
    background: linear-gradient(
        180deg,
        color-mix(in srgb, var(--color-brand-primary-soft) 60%, transparent) 0%,
        transparent 100%
    );
}

.wo-suggestions__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: var(--radius-lg);
    background: var(--color-brand-primary-soft);
    color: var(--color-brand-primary-active);
}

.wo-suggestions__provider-badge {
    display: inline-flex;
    align-items: center;
    padding: 0 var(--space-2);
    height: 1.5rem;
    border-radius: var(--radius-full);
    background: var(--color-surface-muted);
    color: var(--color-text-secondary);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    text-transform: lowercase;
    letter-spacing: 0.02em;
}

.wo-suggestions__skeleton {
    border-radius: var(--radius-lg);
    background: linear-gradient(
        90deg,
        var(--color-surface-muted) 0%,
        color-mix(in srgb, var(--color-surface-muted) 60%, var(--color-surface)) 50%,
        var(--color-surface-muted) 100%
    );
    background-size: 200% 100%;
    animation: wo-suggestions-shimmer 1.4s ease-in-out infinite;
}

@keyframes wo-suggestions-shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.wo-suggestions__alert {
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-lg);
    background: var(--color-danger-soft);
    color: var(--color-danger);
    border: 1px solid color-mix(in srgb, var(--color-danger) 25%, transparent);
}

.wo-suggestions__empty {
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    background: var(--color-surface-muted);
    color: var(--color-text-secondary);
    text-align: center;
}

.wo-suggestions__card {
    padding: var(--space-4);
    border-radius: var(--radius-xl);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    box-shadow: var(--shadow-xs);
    transition:
        box-shadow var(--motion-duration-fast) var(--motion-ease-standard),
        transform var(--motion-duration-fast) var(--motion-ease-standard);
}

.wo-suggestions__card:hover {
    box-shadow: var(--shadow-sm);
}

.wo-suggestions__pill {
    display: inline-flex;
    align-items: center;
    padding: 0 var(--space-2);
    height: 1.5rem;
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    line-height: 1;
}
.wo-suggestions__pill--high {
    background: var(--color-success-soft);
    color: var(--color-success);
}
.wo-suggestions__pill--medium {
    background: var(--color-warning-soft);
    color: var(--color-warning);
}
.wo-suggestions__pill--low {
    background: var(--color-surface-muted);
    color: var(--color-text-muted);
}

.wo-suggestions__dept-badge,
.wo-suggestions__type-badge {
    display: inline-flex;
    align-items: center;
    padding: 0 var(--space-2);
    height: 1.25rem;
    border-radius: var(--radius-full);
    background: var(--color-surface-muted);
    color: var(--color-text-secondary);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
}

.wo-suggestions__price {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-bold);
    color: var(--color-text-primary);
}

.wo-suggestions__settings-link {
    color: var(--color-text-secondary);
    text-decoration: underline;
    text-underline-offset: 2px;
}
.wo-suggestions__settings-link:hover {
    color: var(--color-brand-primary-active);
}
</style>
