<template>
    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                :title="$t('settings.print.signatures.title')"
                :subtitle="$t('settings.print.signatures.subtitle')"
            >
                <template #actions>
                    <BackButton :href="route('settings.print')" />
                </template>
            </PageHeader>

            <!-- Empty state -->
            <div
                v-if="!grouped.length"
                class="py-12 text-center bg-gray-50 dark:bg-gray-700/30 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700"
            >
                <div
                    class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4"
                >
                    <svg
                        class="w-8 h-8 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                        />
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ $t('settings.print.signatures.no_signatures') }}
                </p>
            </div>

            <!-- Groups, one per document type -->
            <div
                v-for="group in grouped"
                :key="group.documentType"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div
                    class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                >
                    <h2 class="font-bold text-gray-900 dark:text-white">
                        {{
                            $te(`print_settings.${group.documentType}`)
                                ? $t(`print_settings.${group.documentType}`)
                                : $t(
                                      `settings.print.documents.${group.documentType}.title`,
                                      group.documentType
                                  )
                        }}
                    </h2>
                    <span
                        class="px-3 py-1 text-xs font-bold rounded-full bg-selection-bg text-selection-text"
                    >
                        {{ group.signatures.length }}
                    </span>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div
                        v-for="signature in group.signatures"
                        :key="signature.id"
                        class="flex items-center gap-4 px-6 py-4"
                    >
                        <div
                            class="w-16 h-16 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex items-center justify-center shrink-0 overflow-hidden"
                        >
                            <img
                                v-if="signature.url"
                                :src="signature.url"
                                :alt="signature.name_ar || signature.name_en"
                                class="max-w-full max-h-full object-contain"
                            />
                            <svg
                                v-else
                                class="w-6 h-6 text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-gray-900 dark:text-white truncate">
                                {{ signature.name_ar || signature.name_en || signature.id }}
                            </p>
                            <p
                                v-if="signature.name_ar && signature.name_en"
                                class="text-sm text-gray-500 dark:text-gray-400 truncate"
                                dir="ltr"
                            >
                                {{ signature.name_en }}
                            </p>
                        </div>

                        <span
                            v-if="signature.updated_at"
                            class="text-xs text-gray-400 whitespace-nowrap hidden sm:block"
                        >
                            {{ signature.updated_at }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';

const props = defineProps({
    signatures: {
        type: Array,
        default: () => [],
    },
});

/**
 * Group the flat signature list by document type.
 *
 * The controller already flattens the tenant.print_settings JSON
 * tree and annotates each entry with `document_type`; all that is
 * left is to bucket them and give each bucket a stable key.
 */
const grouped = computed(() => {
    const buckets = new Map();

    for (const signature of props.signatures || []) {
        const key = signature.document_type || 'other';

        if (!buckets.has(key)) {
            buckets.set(key, { documentType: key, signatures: [] });
        }

        buckets.get(key).signatures.push(signature);
    }

    return Array.from(buckets.values());
});
</script>
