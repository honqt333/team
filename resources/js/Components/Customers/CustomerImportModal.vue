<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div 
                class="relative w-full max-w-xl transform rounded-3xl bg-white dark:bg-gray-800 p-6 shadow-2xl transition-all"
                @click.stop
            >
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('customers.import.title') }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('customers.import.subtitle') }}</p>
                        </div>
                    </div>
                    <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Instructions -->
                <div class="mb-6 p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/30">
                    <h3 class="font-bold text-indigo-900 dark:text-indigo-300 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $t('customers.import.instructions_title') }}
                    </h3>
                    <ol class="text-sm text-indigo-800 dark:text-indigo-200 space-y-2 list-decimal list-inside">
                        <li>{{ $t('customers.import.step_1') }}</li>
                        <li>{{ $t('customers.import.step_2') }}</li>
                        <li>{{ $t('customers.import.step_3') }}</li>
                    </ol>
                </div>

                <!-- Required Fields Info -->
                <div class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/30">
                    <h4 class="font-bold text-amber-900 dark:text-amber-300 mb-2 text-sm">{{ $t('customers.import.required_fields') }}</h4>
                    <div class="text-sm text-amber-800 dark:text-amber-200 space-y-1">
                        <p><strong>{{ $t('customers.columns.name') }}</strong> - {{ $t('customers.import.required') }}</p>
                        <p><strong>{{ $t('customers.columns.phone') }}</strong> - {{ $t('customers.import.required_unique') }}</p>
                    </div>
                    <div class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                        <strong>{{ $t('customers.columns.type') }}:</strong> individual, company, government, vip
                    </div>
                </div>

                <!-- Download Template Button -->
                <a
                    :href="route('customers.import.template')"
                    class="flex items-center justify-center gap-3 w-full px-6 py-4 mb-6 text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl hover:from-indigo-700 hover:to-purple-700 shadow-lg shadow-indigo-500/30 transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    {{ $t('customers.import.download_template') }}
                </a>

                <!-- File Upload Area -->
                <div
                    class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all"
                    :class="isDragging 
                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' 
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="handleDrop"
                >
                    <input
                        type="file"
                        ref="fileInput"
                        accept=".xlsx,.xls,.csv"
                        @change="handleFileSelect"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    />
                    
                    <div v-if="!selectedFile" class="space-y-3">
                        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">{{ $t('customers.import.drag_drop') }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $t('customers.import.supported_formats') }}</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="font-bold text-gray-900 dark:text-white">{{ selectedFile.name }}</p>
                        <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                        <button @click.prevent="clearFile" class="text-sm text-red-600 hover:text-red-700">
                            {{ $t('common.cancel') }}
                        </button>
                    </div>
                </div>

                <!-- Error Messages -->
                <div v-if="errors.length > 0" class="mt-4 p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30">
                    <h4 class="font-bold text-red-900 dark:text-red-300 mb-2 text-sm">{{ $t('customers.import.errors') }}</h4>
                    <ul class="text-sm text-red-700 dark:text-red-300 space-y-1 max-h-32 overflow-y-auto">
                        <li v-for="(err, idx) in errors" :key="idx">{{ err }}</li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="close"
                        class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        @click="importFile"
                        :disabled="!selectedFile || importing"
                        class="px-6 py-3 text-sm font-bold text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl hover:from-green-700 hover:to-emerald-700 shadow-lg shadow-green-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg v-if="importing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ importing ? $t('customers.import.importing') : $t('customers.import.start_import') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'imported']);

const { t } = useI18n();
const { success, error: toastError } = useToast();

const fileInput = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const importing = ref(false);
const errors = ref([]);

function close() {
    selectedFile.value = null;
    errors.value = [];
    emit('close');
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        errors.value = [];
    }
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls', 'csv'].includes(ext)) {
            selectedFile.value = file;
            errors.value = [];
        } else {
            toastError(t('customers.import.invalid_format'));
        }
    }
}

function clearFile() {
    selectedFile.value = null;
    errors.value = [];
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

async function importFile() {
    if (!selectedFile.value) return;

    importing.value = true;
    errors.value = [];

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await fetch(route('customers.import'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();

        if (response.ok) {
            success(t('common.import_success', { count: data.imported || 0 }));
            
            if (data.errors && data.errors.length > 0) {
                errors.value = data.errors;
            } else {
                emit('imported');
                close();
            }
            
            router.reload({ only: ['customers'] });
        } else {
            toastError(data.message || t('common.import_failed'));
        }
    } catch (e) {
        console.error('Import error:', e);
        toastError(t('common.import_failed') + ': ' + e.message);
    } finally {
        importing.value = false;
    }
}
</script>
