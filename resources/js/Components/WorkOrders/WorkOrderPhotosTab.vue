<template>
    <div class="space-y-4">
        <!-- Toolbar: Title & Add Button next to it -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">📸</span>
                    {{ $t('work_orders.show.tabs.photos') }}
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

        <div v-if="photos?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="photo in photos" :key="photo.id"
                class="relative group aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                <img :src="`/storage/${photo.path}`" class="w-full h-full object-cover" />

                <!-- Hover Overlay with Actions -->
                <div
                    class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center gap-3">
                    <!-- View Button -->
                    <a :href="`/storage/${photo.path}`" target="_blank"
                        class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-all"
                        :title="$t('common.view')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </a>
                    <!-- Download Button -->
                    <a :href="`/storage/${photo.path}`" :download="photo.path.split('/').pop()"
                        class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-all"
                        :title="$t('common.download')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>
                    <!-- Delete Button -->
                    <button v-if="!isReadOnly" type="button" @click="$emit('delete', photo)"
                        class="w-10 h-10 rounded-full bg-red-500/80 hover:bg-red-600 flex items-center justify-center text-white transition-all"
                        :title="$t('common.delete')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

                <!-- Photo Info Overlay -->
                <div
                    class="absolute inset-x-0 bottom-0 bg-black/60 p-2 text-white text-xs backdrop-blur-sm">
                    <p class="font-bold uppercase">{{ $t(`work_orders.photos.types.${photo.type}`) }}
                    </p>
                    <p v-if="photo.caption" class="truncate">{{ photo.caption }}</p>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-12">
            <div
                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <span class="text-2xl">📸</span>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.photos.no_photos') }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    photos: { type: Array, default: () => [] },
    isReadOnly: { type: Boolean, default: false },
});

defineEmits(['add', 'delete']);
</script>
