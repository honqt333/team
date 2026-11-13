<template>
    <SystemLayout>
        <Head :title="$t('help.admin.title')" />

        <div class="space-y-6 pb-12">
            <PageHeader
                :title="$t('help.admin.title')"
                :subtitle="$t('help.admin.description')"
                :badge="$t('help.admin.controller_hint')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        />
                    </svg>
                </template>
            </PageHeader>

            <HelpCenterTab :settings="settings" />
        </div>
    </SystemLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import HelpCenterTab from '@/Pages/System/Support/Tabs/HelpCenterTab.vue';

defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});
</script>
