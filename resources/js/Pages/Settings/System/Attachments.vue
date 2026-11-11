<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import StorageUsageSection from './Sections/StorageUsageSection.vue';
import AttachmentsManagerSection from './Sections/AttachmentsManagerSection.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

defineProps({
    storage_usage: {
        type: Object,
        default: null,
    },
    attachments: {
        type: Object,
        default: null,
    },
    attachmentSummary: {
        type: Object,
        default: null,
    },
    attachmentFilters: {
        type: Object,
        default: () => ({ source: '', search: '' }),
    },
    canDeleteAttachments: {
        type: Boolean,
        default: false,
    },
});

const { t } = useI18n();
const { toEnglish } = useNumberFormat();
</script>

<template>
    <AppLayout>
        <Head :title="t('system_settings.attachments.title')" />

        <div class="space-y-6">
            <PageHeader
                :title="t('system_settings.attachments.title')"
                :subtitle="t('system_settings.attachments.subtitle')"
                :total-count="
                    attachmentSummary ? toEnglish(attachmentSummary.total_count ?? 0) : undefined
                "
            >
                <template #back>
                    <BackButton :href="route('settings.storage')" />
                </template>

                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                        />
                    </svg>
                </template>
            </PageHeader>

            <StorageUsageSection :storage-usage="storage_usage" />

            <AttachmentsManagerSection
                :attachments="attachments"
                :summary="attachmentSummary"
                :filters="attachmentFilters"
                :can-view="true"
                :can-delete="canDeleteAttachments"
                route-name="settings.attachments.index"
                :show-full-page-link="false"
            />
        </div>
    </AppLayout>
</template>
