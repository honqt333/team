<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="t('system.payment_settings.title')"
                :subtitle="t('system.payment_settings.subtitle')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <PaymentSettingsForm :settings="settings" />
        </div>
    </SystemLayout>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import PaymentSettingsForm from '@/Components/System/PaymentSettingsForm.vue';

defineProps({ settings: Object });
const { t } = useI18n();
</script>
