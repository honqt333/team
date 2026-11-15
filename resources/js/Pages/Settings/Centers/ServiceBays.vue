<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppButton from '@/Components/App/AppButton.vue';
import ServiceBaysTab from '@/Components/Services/ServiceBaysTab.vue';

defineProps({
    bays: {
        type: Array,
        default: () => [],
    },
});

const serviceBaysTabRef = ref(null);
const openCreateBayModal = () => {
    serviceBaysTabRef.value?.openCreateModal();
};
</script>

<template>
    <Head :title="$t('service_bays.title')" />

    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <PageHeader
                :title="$t('service_bays.title')"
                :subtitle="$t('service_bays.subtitle')"
            >
                <template #icon>
                    <BackButton :fallback-route="route('services.index', { tab: 'bays' })" />
                </template>
                <template #actions>
                    <AppButton
                        @click="openCreateBayModal"
                        variant="primary"
                        size="md"
                        class="shadow-xs"
                    >
                        <template #iconLeft>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </template>
                        {{ $t('service_bays.add_bay') }}
                    </AppButton>
                </template>
            </PageHeader>

            <ServiceBaysTab ref="serviceBaysTabRef" :bays="bays" />
        </div>
    </AppLayout>
</template>
