<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { useForm, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    settings: Object,
});

const { success } = useToast();

const form = useForm({
    security_2fa_sms_enabled: props.settings.security_2fa_sms_enabled,
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

<template>
    <SystemLayout :title="$t('system.settings.general_title')">
        <div class="space-y-6">
            <PageHeader
                :title="$t('permissions_modules.settings_general')"
                :subtitle="$t('system.settings.general_title')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>
                </template>
            </PageHeader>

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
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600 rtl:peer-checked:after:-translate-x-full"
                            ></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>
