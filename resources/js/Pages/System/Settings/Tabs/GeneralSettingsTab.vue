<template>
    <div class="space-y-6">
        <!-- Security Settings -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">
                    {{ $t('system.tenants.actions.security_settings') }}
                </h3>

                <div
                    class="flex items-center justify-between py-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">
                            {{ $t('system.settings.enable_sms_2fa') }}
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $t('system.settings.enable_sms_2fa_hint') }}
                            <br />
                            <span class="text-xs text-amber-500">
                                {{ $t('system.settings.enable_sms_2fa_warning') }}
                            </span>
                        </p>
                    </div>

                    <AppToggle
                        :model-value="form.security_2fa_sms_enabled"
                        @change="updateSetting('security.2fa_sms_enabled', $event)"
                    />
                </div>
            </div>
        </div>

        <!-- Subscription Grace Period Settings -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="max-w-xl">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('system.settings.grace_period_days') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $t('system.settings.grace_period_days_hint') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <input
                            v-model.number="form.subscription_grace_period_days"
                            type="number"
                            min="0"
                            max="365"
                            class="w-24 px-3 py-2 text-center rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-action-primary focus:border-transparent"
                        />
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('common.days') }}
                        </span>
                        <button
                            type="button"
                            :disabled="savingKey === 'subscription.grace_period_days'"
                            @click="
                                updateSetting(
                                    'subscription.grace_period_days',
                                    form.subscription_grace_period_days
                                )
                            "
                            class="px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold transition-colors disabled:opacity-50 cursor-pointer"
                        >
                            {{ $t('common.save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import AppToggle from '@/Components/Common/AppToggle.vue';

const { t } = useI18n();
const { success, error } = useToast();

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const savingKey = ref(null);

const form = useForm({
    security_2fa_sms_enabled: props.settings?.security_2fa_sms_enabled || false,
    subscription_grace_period_days: props.settings?.subscription_grace_period_days ?? 7,
});

watch(
    () => props.settings,
    (next) => {
        if (!next) return;
        form.security_2fa_sms_enabled = Boolean(next.security_2fa_sms_enabled);
        form.subscription_grace_period_days = next.subscription_grace_period_days ?? 7;
    },
    { deep: true }
);

const updateSetting = (key, value) => {
    savingKey.value = key;
    form.processing = true;
    router.put(
        route('system.settings.general.update'),
        {
            key: key,
            value: value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                success(t('system.settings.saved'));
                form.processing = false;
                savingKey.value = null;
            },
            onError: () => {
                error(t('common.error_occurred'));
                form.processing = false;
                savingKey.value = null;
            },
        }
    );
};
</script>
