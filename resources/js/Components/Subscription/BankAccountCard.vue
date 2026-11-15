<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { resolveBankPreset } from '@/Utils/banks';

const { t } = useI18n();

const props = defineProps({
    account: { type: Object, required: true },
});

const copiedKey = ref(null);

const preset = computed(() =>
    resolveBankPreset(props.account?.bank_code, props.account?.bank_name)
);

async function copyValue(value, key) {
    if (!value) return;
    try {
        await navigator.clipboard.writeText(String(value));
        copiedKey.value = key;
        setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = null;
            }
        }, 2000);
    } catch {
        // Clipboard permission denied
    }
}
</script>

<template>
    <div
        class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs dark:border-slate-700 dark:bg-slate-800"
    >
        <!-- Header: Bank logo + Bank Name -->
        <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-700/70">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900"
            >
                <img
                    v-if="account.logo_url"
                    :src="account.logo_url"
                    :alt="account.bank_name || preset?.name_en || 'Bank'"
                    class="h-full w-full object-contain p-1"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center text-[10px] font-black tracking-tight text-white select-none"
                    :style="{ backgroundColor: preset?.color || '#334155' }"
                >
                    {{ preset?.short || 'BANK' }}
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    {{ t('system.payment_settings.bank_transfer.bank_name') }}
                </p>
                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">
                    {{ account.bank_name || '—' }}
                </p>
            </div>
        </div>

        <!-- Labeled Details -->
        <div class="mt-2.5 space-y-2 text-xs">
            <!-- Account Holder -->
            <div
                v-if="account.account_name"
                class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 dark:bg-slate-900/50"
            >
                <div class="min-w-0 flex-1">
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400">
                        {{ t('system.payment_settings.bank_transfer.account_name') }}
                    </span>
                    <span class="block font-semibold text-slate-800 dark:text-slate-200 truncate">
                        {{ account.account_name }}
                    </span>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer transition-colors"
                    @click="copyValue(account.account_name, 'account_name')"
                >
                    {{
                        copiedKey === 'account_name'
                            ? t('subscription.copied')
                            : t('subscription.copy_iban')
                    }}
                </button>
            </div>

            <!-- IBAN -->
            <div
                v-if="account.iban"
                class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 dark:bg-slate-900/50"
            >
                <div class="min-w-0 flex-1">
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400">
                        {{ t('system.payment_settings.bank_transfer.iban') }}
                    </span>
                    <span
                        class="block font-mono font-semibold text-slate-800 dark:text-slate-200 truncate"
                        dir="ltr"
                    >
                        {{ account.iban }}
                    </span>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer transition-colors"
                    @click="copyValue(account.iban, 'iban')"
                >
                    {{
                        copiedKey === 'iban'
                            ? t('subscription.copied')
                            : t('subscription.copy_iban')
                    }}
                </button>
            </div>

            <!-- Account Number -->
            <div
                v-if="account.account_number"
                class="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 dark:bg-slate-900/50"
            >
                <div class="min-w-0 flex-1">
                    <span class="block text-[10px] font-medium text-slate-500 dark:text-slate-400">
                        {{ t('system.payment_settings.bank_transfer.account_number') }}
                    </span>
                    <span
                        class="block font-mono font-semibold text-slate-800 dark:text-slate-200 truncate"
                        dir="ltr"
                    >
                        {{ account.account_number }}
                    </span>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer transition-colors"
                    @click="copyValue(account.account_number, 'account_number')"
                >
                    {{
                        copiedKey === 'account_number'
                            ? t('subscription.copied')
                            : t('subscription.copy_iban')
                    }}
                </button>
            </div>
        </div>
    </div>
</template>
