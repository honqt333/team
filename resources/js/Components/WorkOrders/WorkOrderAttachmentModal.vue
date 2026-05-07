<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </div>
                {{ $t('work_orders.show.tabs.attachments') }}
            </div>
        </template>

        <div class="space-y-4">
            <div
                class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors"
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
                    accept=".pdf,image/png,image/jpeg"
                    class="hidden"
                    @change="handleFileSelect"
                />
                
                <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <div class="space-y-1">
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
                            PDF, PNG, JPG ({{ $t('common.max') }} 1MB)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Selected Files List -->
            <div v-if="selectedFiles.length > 0" class="space-y-2">
                <div
                    v-for="(file, index) in selectedFiles"
                    :key="index"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
                            <svg v-if="file.type === 'application/pdf'" class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z"/>
                            </svg>
                            <svg v-else class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ file.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(file.size) }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="removeFile(index)"
                        class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button type="button" @click="submit" :disabled="form.processing || selectedFiles.length === 0"
                class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 transition-all flex items-center gap-2">
                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
});

const emit = defineEmits(['close', 'saved']);
const toast = useToast();
const { t } = useI18n();

const fileInput = ref(null);
const isDragging = ref(false);
const selectedFiles = ref([]);

const form = useForm({
    attachments: [],
});

function handleFileSelect(event) {
    const files = event.target.files;
    if (files) {
        addFiles(Array.from(files));
    }
    fileInput.value.value = '';
}

function handleDrop(event) {
    isDragging.value = false;
    const files = event.dataTransfer?.files;
    if (files) {
        addFiles(Array.from(files));
    }
}

function addFiles(files) {
    const validFiles = files.filter(file => {
        const isValidType = ['application/pdf', 'image/png', 'image/jpeg'].includes(file.type);
        const isValidSize = file.size <= 1024 * 1024; // 1MB

        if (!isValidType) {
            toast.error(t('messages.invalid_file_type', { file: file.name }));
            return false;
        }
        if (!isValidSize) {
            toast.error(t('messages.file_too_large', { file: file.name, size: '1MB' }));
            return false;
        }
        return true;
    });

    selectedFiles.value.push(...validFiles);
}

function removeFile(index) {
    selectedFiles.value.splice(index, 1);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function submit() {
    form.attachments = selectedFiles.value;
    form.post(route('work-orders.attachments.store', props.workOrder.id), {
        onSuccess: () => {
            selectedFiles.value = [];
            emit('saved');
            emit('close');
        },
        forceFormData: true,
    });
}
</script>
