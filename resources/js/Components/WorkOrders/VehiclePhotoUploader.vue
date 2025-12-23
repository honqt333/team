<template>
    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t('work_orders.photos.title') }}
            </h4>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ photos.length }} / {{ maxPhotos }} {{ $t('work_orders.photos.images') }}
            </span>
        </div>

        <!-- Photo Type Selector -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button
                v-for="type in photoTypes"
                :key="type.value"
                type="button"
                @click="selectedType = type.value"
                :class="[
                    'px-3 py-1.5 text-sm font-medium rounded-lg border transition-all',
                    selectedType === type.value
                        ? 'bg-indigo-100 dark:bg-indigo-900/30 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300'
                        : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'
                ]"
            >
                {{ type.label }}
            </button>
        </div>

        <!-- Upload Area -->
        <div
            class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors"
            :class="[
                isDragging 
                    ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' 
                    : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-600'
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
        >
            <input
                ref="fileInput"
                type="file"
                multiple
                accept="image/*"
                class="hidden"
                @change="handleFileSelect"
            />
            
            <div class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('work_orders.photos.drag_or') }}
                    <button
                        type="button"
                        @click="$refs.fileInput.click()"
                        class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline"
                    >
                        {{ $t('work_orders.photos.browse') }}
                    </button>
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $t('work_orders.photos.formats') }}
                </p>
            </div>
        </div>

        <!-- Photos Grid -->
        <div v-if="photos.length > 0" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <div
                v-for="(photo, index) in photos"
                :key="index"
                class="relative group rounded-lg overflow-hidden aspect-square bg-gray-200 dark:bg-gray-700"
            >
                <img
                    :src="photo.preview || photo.url"
                    :alt="photo.caption || `Photo ${index + 1}`"
                    class="w-full h-full object-cover"
                />
                
                <!-- Type Badge -->
                <span
                    class="absolute top-2 start-2 px-2 py-0.5 text-xs font-medium rounded-full"
                    :class="getTypeBadgeClass(photo.type)"
                >
                    {{ getTypeLabel(photo.type) }}
                </span>
                
                <!-- Overlay with actions -->
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="removePhoto(index)"
                        class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

                <!-- Caption Input -->
                <div class="absolute bottom-0 start-0 end-0 p-2 bg-gradient-to-t from-black/70 to-transparent">
                    <input
                        v-model="photo.caption"
                        type="text"
                        :placeholder="$t('work_orders.photos.add_caption')"
                        class="w-full px-2 py-1 text-xs bg-white/90 dark:bg-gray-800/90 rounded text-gray-900 dark:text-white placeholder-gray-500"
                    />
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm">{{ $t('work_orders.photos.no_photos') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const photos = defineModel('photos', { default: () => [] });

const fileInput = ref(null);
const isDragging = ref(false);
const selectedType = ref('general');
const maxPhotos = 20;

const photoTypes = [
    { value: 'general', label: t('work_orders.photos.types.general') },
    { value: 'before', label: t('work_orders.photos.types.before') },
    { value: 'after', label: t('work_orders.photos.types.after') },
    { value: 'damage', label: t('work_orders.photos.types.damage') },
];

function getTypeBadgeClass(type) {
    const classes = {
        general: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        before: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        after: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        damage: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    };
    return classes[type] || classes.general;
}

function getTypeLabel(type) {
    const typeOption = photoTypes.find(t => t.value === type);
    return typeOption?.label || type;
}

function handleFileSelect(event) {
    const files = event.target.files;
    if (files) {
        addFiles(Array.from(files));
    }
    // Reset input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function handleDrop(event) {
    isDragging.value = false;
    const files = event.dataTransfer?.files;
    if (files) {
        addFiles(Array.from(files));
    }
}

function addFiles(files) {
    const imageFiles = files.filter(file => file.type.startsWith('image/'));
    const remaining = maxPhotos - photos.value.length;
    const filesToAdd = imageFiles.slice(0, remaining);
    
    filesToAdd.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            photos.value.push({
                file: file,
                preview: e.target?.result,
                type: selectedType.value,
                caption: '',
            });
        };
        reader.readAsDataURL(file);
    });
}

function removePhoto(index) {
    photos.value.splice(index, 1);
}
</script>
