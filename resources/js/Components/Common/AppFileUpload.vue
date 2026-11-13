<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFormatters } from '@/Composables/useFormatters';

/**
 * Unified file & attachment upload component.
 *
 * Supports two visual modes:
 *  - `dropzone`: Drag-and-drop / click area for single or multiple data files & attachments.
 *  - `image`: Compact preview card for logos, favicons, stamps, and avatars.
 */
const props = defineProps({
    variant: {
        type: String,
        default: 'dropzone',
        validator: (v) => ['dropzone', 'image'].includes(v),
    },
    accept: { type: String, default: '' },
    maxSizeMb: { type: Number, default: 10 },
    multiple: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    label: { type: String, default: '' },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
    existingUrl: { type: String, default: '' },
    fallbackUrl: { type: String, default: '' },
    defaultBadge: { type: String, default: '' },
    uploadButtonText: { type: String, default: '' },
    resetButtonText: { type: String, default: '' },
    compactPreview: { type: Boolean, default: false },
});

const model = defineModel({ default: null });
const emit = defineEmits(['select', 'invalid', 'remove-existing']);

const { t } = useI18n();
const { formatFileSize } = useFormatters();

const inputRef = ref(null);
const dragging = ref(false);
const localError = ref('');
const objectUrl = ref(null);

const isBusy = computed(() => props.disabled || props.loading);
const activeError = computed(() => props.error || localError.value);

const selectedFiles = computed(() => {
    if (!model.value) return [];
    return Array.isArray(model.value) ? model.value : [model.value];
});

const imagePreviewSrc = computed(() => {
    if (objectUrl.value) return objectUrl.value;
    if (props.existingUrl) return props.existingUrl;
    return props.fallbackUrl || '';
});

function revokeObjectUrl() {
    if (objectUrl.value) {
        URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = null;
    }
}

watch(
    () => selectedFiles.value[0],
    (file) => {
        revokeObjectUrl();
        if (
            props.variant === 'image' &&
            file instanceof File &&
            typeof URL.createObjectURL === 'function'
        ) {
            objectUrl.value = URL.createObjectURL(file);
        }
    }
);

onUnmounted(revokeObjectUrl);

function matchesAccept(file) {
    if (!props.accept) return true;
    const tokens = props.accept
        .split(',')
        .map((s) => s.trim().toLowerCase())
        .filter(Boolean);

    if (tokens.length === 0) return true;

    const fileName = (file.name || '').toLowerCase();
    const fileType = (file.type || '').toLowerCase();

    return tokens.some((token) => {
        if (token.startsWith('.')) {
            return fileName.endsWith(token);
        }
        if (token.endsWith('/*')) {
            const prefix = token.slice(0, -1);
            return fileType.startsWith(prefix);
        }
        return fileType === token;
    });
}

function validateFile(file) {
    if (!matchesAccept(file)) {
        const message = t('common.file_upload.invalid_type');
        localError.value = message;
        emit('invalid', { reason: 'type', message, file });
        return false;
    }

    const maxBytes = props.maxSizeMb * 1024 * 1024;
    if (props.maxSizeMb > 0 && file.size > maxBytes) {
        const message = t('common.file_upload.max_size_exceeded', { max: props.maxSizeMb });
        localError.value = message;
        emit('invalid', { reason: 'size', message, file });
        return false;
    }

    return true;
}

function processFiles(fileList) {
    dragging.value = false;
    if (isBusy.value || !fileList || fileList.length === 0) return;

    localError.value = '';
    const candidates = Array.from(fileList);

    if (!props.multiple) {
        const file = candidates[0];
        if (!validateFile(file)) return;
        model.value = file;
        emit('select', file);
        return;
    }

    const valid = candidates.filter((file) => validateFile(file));
    if (valid.length === 0) return;

    const merged = [...selectedFiles.value, ...valid];
    model.value = merged;
    emit('select', valid);
}

function onBrowse(event) {
    processFiles(event.target.files);
    event.target.value = '';
}

function removeFile(index) {
    if (isBusy.value) return;
    localError.value = '';
    if (props.multiple) {
        const next = selectedFiles.value.filter((_, i) => i !== index);
        model.value = next;
    } else {
        model.value = null;
    }
}

function openPicker() {
    if (!isBusy.value) {
        inputRef.value?.click();
    }
}
</script>

<template>
    <div class="w-full">
        <!-- Image / Brand Identity Variant -->
        <div
            v-if="variant === 'image'"
            :class="[
                'flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl transition-colors',
                dragging ? 'ring-2 ring-action-primary bg-selection-bg/40 p-2 -m-2' : '',
            ]"
            @dragover.prevent="dragging = !isBusy"
            @dragleave.prevent="dragging = false"
            @drop.prevent="processFiles($event.dataTransfer?.files)"
        >
            <div class="flex items-center gap-4">
                <div
                    class="w-16 h-16 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-center overflow-hidden shrink-0"
                    :class="compactPreview ? 'p-2.5' : 'p-1.5'"
                >
                    <img
                        v-if="imagePreviewSrc"
                        :src="imagePreviewSrc"
                        :alt="label || $t('common.app_name')"
                        :class="
                            compactPreview
                                ? 'w-8 h-8 object-contain'
                                : 'w-full h-full object-contain'
                        "
                    />
                    <svg
                        v-else
                        class="w-7 h-7 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.75"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>

                <div class="max-w-xl">
                    <div v-if="label || defaultBadge" class="flex items-center gap-2 flex-wrap">
                        <h3 v-if="label" class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ label }}
                        </h3>
                        <span
                            v-if="defaultBadge && !existingUrl && selectedFiles.length === 0"
                            class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300"
                        >
                            {{ defaultBadge }}
                        </span>
                    </div>
                    <p v-if="hint" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ hint }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <input
                    ref="inputRef"
                    type="file"
                    :accept="accept"
                    :disabled="isBusy"
                    class="hidden"
                    @change="onBrowse"
                />
                <button
                    type="button"
                    :disabled="isBusy"
                    @click="openPicker"
                    class="px-4 py-2 rounded-xl bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold transition-colors disabled:opacity-50 cursor-pointer"
                >
                    {{ uploadButtonText || $t('common.file_upload.choose_file') }}
                </button>
                <button
                    v-if="existingUrl && resetButtonText"
                    type="button"
                    :disabled="isBusy"
                    @click="$emit('remove-existing')"
                    class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-medium transition-colors disabled:opacity-50 cursor-pointer"
                >
                    {{ resetButtonText }}
                </button>
            </div>
        </div>

        <!-- Dropzone Variant (Data & Attachments) -->
        <div v-else>
            <label
                v-if="label"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
            >
                {{ label }}
            </label>

            <div
                :class="[
                    'relative rounded-2xl border-2 border-dashed p-6 text-center transition-colors',
                    dragging
                        ? 'border-action-primary bg-selection-bg/50'
                        : activeError
                          ? 'border-red-400 dark:border-red-500'
                          : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500',
                    isBusy ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                ]"
                @click="openPicker"
                @dragover.prevent="dragging = !isBusy"
                @dragleave.prevent="dragging = false"
                @drop.prevent="processFiles($event.dataTransfer?.files)"
            >
                <input
                    ref="inputRef"
                    type="file"
                    :accept="accept"
                    :multiple="multiple"
                    :disabled="isBusy"
                    class="hidden"
                    @change="onBrowse"
                />

                <div class="flex flex-col items-center gap-2">
                    <div
                        class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 flex items-center justify-center"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                            />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{
                            multiple
                                ? $t('common.file_upload.drag_drop_multiple')
                                : $t('common.file_upload.drag_drop')
                        }}
                    </p>
                    <p v-if="hint" class="text-xs text-gray-500 dark:text-gray-400">
                        {{ hint }}
                    </p>
                </div>
            </div>

            <!-- Selected File(s) List -->
            <ul v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
                <li
                    v-for="(item, idx) in selectedFiles"
                    :key="`${item.name}-${idx}`"
                    class="flex items-center justify-between gap-3 px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="truncate font-medium text-gray-800 dark:text-gray-100">
                            {{ item.name }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">
                            ({{ formatFileSize(item.size) }})
                        </span>
                    </div>
                    <button
                        type="button"
                        :disabled="isBusy"
                        @click.stop="removeFile(idx)"
                        class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 shrink-0 cursor-pointer"
                    >
                        {{ $t('common.file_upload.remove') }}
                    </button>
                </li>
            </ul>
        </div>

        <p v-if="activeError" class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">
            {{ activeError }}
        </p>
    </div>
</template>
