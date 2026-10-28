<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: { type: String, default: '' },
    validPromo: { type: Object, default: null }, // { code, type, value, description }
});
const emit = defineEmits(['update:modelValue', 'applied', 'cleared']);

const locale = computed(() => document.documentElement.lang || 'ar');
const loading = ref(false);
const error = ref(null);

async function apply() {
    if (!props.modelValue?.trim()) return;

    loading.value = true;
    error.value = null;
    try {
        const response = await fetch('/app/settings/billing/validate-promo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ code: props.modelValue }),
        });
        const data = await response.json();
        if (data.valid) {
            emit('applied', data);
            error.value = null;
        } else {
            error.value = data.message;
            emit('cleared');
        }
    } catch (e) {
        error.value = t('subscription.promo_verify_error');
    } finally {
        loading.value = false;
    }
}

function clear() {
    emit('update:modelValue', '');
    emit('cleared');
    error.value = null;
}
</script>

<template>
    <div class="space-y-2">
        <label class="text-sm font-semibold text-slate-900 dark:text-white">
            {{ $t('subscription.promo_code_label') }}
        </label>

        <!-- Applied state -->
        <div
            v-if="validPromo"
            class="flex items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800/50 dark:bg-emerald-900/20"
        >
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <svg
                    class="h-5 w-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                        {{ validPromo.description }}
                    </p>
                    <p class="text-xs text-emerald-600/80 dark:text-emerald-400/80 font-mono">
                        {{ validPromo.code }}
                    </p>
                </div>
            </div>
            <button
                type="button"
                @click="clear"
                class="flex-shrink-0 rounded p-1 text-emerald-600 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:bg-emerald-900/40"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <!-- Input state -->
        <div v-else class="flex gap-2">
            <input
                type="text"
                :value="modelValue"
                @input="emit('update:modelValue', $event.target.value.toUpperCase())"
                :placeholder="locale === 'ar' ? 'أدخل كود الخصم' : 'Enter promo code'"
                class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono uppercase text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                :disabled="loading"
            />
            <button
                type="button"
                @click="apply"
                :disabled="loading || !modelValue?.trim()"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 ”
                          8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
                <span v-else>{{ $t('common.apply') }}</span>
            </button>
        </div>

        <!-- Error message -->
        <p v-if="error" class="text-xs text-rose-600 dark:text-rose-400">
            {{ error }}
        </p>
    </div>
</template>
