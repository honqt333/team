<template>
    <div class="space-y-6">
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">
                    {{ $t('system.tenants.actions.security_settings') }}
                </h3>

                <div
                    class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700"
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

                    <label class="relative inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            :checked="form.security_2fa_sms_enabled"
                            @change="
                                updateSetting('security.2fa_sms_enabled', $event.target.checked)
                            "
                            class="sr-only peer"
                        />
                        <div
                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"
                        ></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const form = useForm({
    security_2fa_sms_enabled: props.settings?.security_2fa_sms_enabled || false,
});

const updateSetting = (key, value) => {
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
            },
            onError: () => {
                form.processing = false;
            },
        }
    );
};
</script>
