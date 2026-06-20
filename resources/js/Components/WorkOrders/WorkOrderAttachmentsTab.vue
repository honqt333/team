<template>
    <div class="space-y-4">
        <!-- Toolbar: Title & Add Button next to it -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">📎</span>
                    {{ $t('work_orders.show.tabs.attachments') }}
                </h3>
            </div>

            <button v-if="!isReadOnly" @click="$emit('add')"
                class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-100 dark:shadow-none">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('common.add') }}
            </button>
        </div>

        <div v-if="attachments?.length"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="attachment in attachments" :key="attachment.id"
                class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="w-12 h-12 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center flex-shrink-0">
                        <svg v-if="attachment.file_type === 'pdf'" class="w-6 h-6 text-red-500"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z" />
                        </svg>
                        <svg v-else class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate"
                            :title="attachment.file_name">{{ attachment.file_name }}</p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                            {{ formatFileSize(attachment.file_size) }} • {{ attachment.user?.name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <a :href="`/storage/${attachment.path}`" target="_blank"
                        class="p-2 text-gray-400 hover:text-indigo-500 transition-colors"
                        :title="$t('common.view')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </a>
                    <button v-if="!isReadOnly" type="button" @click="$emit('delete', attachment)"
                        class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                        :title="$t('common.delete')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-12">
            <div
                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <span class="text-2xl">📎</span>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.no_data') }}</p>
        </div>
    </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';

defineProps({
    attachments: { type: Array, default: () => [] },
    isReadOnly: { type: Boolean, default: false },
});

defineEmits(['add', 'delete']);

const { formatFileSize } = useFormatters();
</script>
