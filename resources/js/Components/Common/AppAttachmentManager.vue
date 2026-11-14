<template>
    <div class="space-y-4">
        <!-- Header Toolbar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div
                    class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ title || $t('common.attachments') }}
                    </h3>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        {{ attachments.length }} {{ $t('common.attachments') }}
                    </p>
                </div>
            </div>

            <!-- Add / Upload Trigger Button -->
            <button
                v-if="!isReadOnly"
                type="button"
                @click="showUploadModal = true"
                class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold rounded-xl transition-all shadow-sm hover:shadow active:scale-95 shrink-0"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                <span>{{ $t('common.upload_files') }}</span>
            </button>
        </div>

        <!-- Attachments Grid -->
        <div
            v-if="attachments && attachments.length > 0"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"
        >
            <div
                v-for="attachment in attachments"
                :key="attachment.id"
                class="group relative flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200/80 dark:border-gray-700/80 hover:border-blue-300 dark:hover:border-blue-700 shadow-sm hover:shadow transition-all"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <!-- File Type Icon -->
                    <div
                        class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                        :class="fileTypeBgClass(attachment)"
                    >
                        <!-- PDF -->
                        <svg
                            v-if="isPdf(attachment)"
                            class="w-5 h-5 text-red-500"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z"
                            />
                        </svg>
                        <!-- Image -->
                        <svg
                            v-else-if="isImage(attachment)"
                            class="w-5 h-5 text-blue-500"
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
                        <!-- Other -->
                        <svg
                            v-else
                            class="w-5 h-5 text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>

                    <!-- File Info -->
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-xs font-bold text-gray-900 dark:text-white truncate"
                            :title="attachment.file_name || attachment.name"
                        >
                            {{ attachment.file_name || attachment.name }}
                        </p>
                        <p
                            class="text-[10px] text-gray-400 font-mono flex items-center gap-1.5 mt-0.5"
                        >
                            <span v-if="attachment.file_size || attachment.size">
                                {{ formatFileSize(attachment.file_size || attachment.size) }}
                            </span>
                            <span v-if="attachment.user?.name">• {{ attachment.user.name }}</span>
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1 shrink-0 ms-2">
                    <!-- View / Download -->
                    <a
                        :href="getAttachmentUrl(attachment)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                        :title="$t('common.view')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                    </a>

                    <!-- Delete -->
                    <button
                        v-if="!isReadOnly"
                        type="button"
                        @click="handleDelete(attachment)"
                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                        :title="$t('common.delete')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="text-center py-8 px-4 bg-gray-50/50 dark:bg-gray-900/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700/60"
        >
            <div
                class="w-12 h-12 mx-auto bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center text-gray-400 mb-2"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                    />
                </svg>
            </div>
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                {{ $t('common.no_attachments') }}
            </p>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                {{ $t('common.no_attachments_hint') }}
            </p>
        </div>

        <!-- Upload Modal -->
        <BaseModal :show="showUploadModal" :closeable="!isUploading" @close="closeModal" size="lg">
            <template #title>
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center text-white"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            {{ $t('common.upload_files') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('common.attachment_limits_hint') }}
                        </p>
                    </div>
                </div>
            </template>

            <div class="space-y-4">
                <AppFileUpload
                    v-model="selectedFiles"
                    :multiple="allowMultiple"
                    :accept="acceptedTypes"
                    :max-size-mb="maxSizeMb"
                    :disabled="isReadOnly || isUploading"
                    :hint="$t('common.supported_formats', { formats: formatLabel })"
                    @invalid="
                        (payload) => error(payload?.message || t('common.errors.invalid_file_type'))
                    "
                />
            </div>

            <template #footer>
                <div
                    class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-3 w-full"
                >
                    <AppButton type="button" variant="secondary" @click="closeModal">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="button"
                        variant="primary"
                        :loading="isUploading"
                        :disabled="selectedFiles.length === 0"
                        @click="handleUploadSubmit"
                    >
                        {{ isUploading ? $t('common.loading') : $t('common.upload_files') }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    attachments: { type: Array, default: () => [] },
    uploadUrl: { type: String, default: '' },
    deleteUrlTemplate: { type: Function, default: null },
    isReadOnly: { type: Boolean, default: false },
    title: { type: String, default: '' },
    maxSizeMb: { type: Number, default: 5 },
    acceptedTypes: { type: String, default: '.pdf,image/png,image/jpeg,image/webp' },
    uploadField: { type: String, default: null },
    formatLabel: { type: String, default: 'PDF, JPG, PNG, WEBP' },
    allowMultiple: { type: Boolean, default: true },
    storagePrefix: { type: String, default: '/storage/' },
});

const emit = defineEmits(['upload', 'delete', 'change', 'files-selected']);

const { t } = useI18n();
const { confirm } = useConfirm();
const { success, error } = useToast();

const showUploadModal = ref(false);
const isUploading = ref(false);
const selectedFiles = ref([]);

function closeModal() {
    if (isUploading.value) return;
    showUploadModal.value = false;
    selectedFiles.value = [];
}

function isPdf(attachment) {
    const name = attachment.file_name || attachment.name || attachment.path || '';
    const type = attachment.file_type || attachment.type || '';
    return type.includes('pdf') || name.toLowerCase().endsWith('.pdf');
}

function isImage(attachment) {
    const name = attachment.file_name || attachment.name || attachment.path || '';
    const type = attachment.file_type || attachment.type || '';
    return type.includes('image') || /\.(jpg|jpeg|png|webp|gif|svg)$/i.test(name);
}

function fileTypeBgClass(attachment) {
    if (isPdf(attachment)) return 'bg-red-50 dark:bg-red-950/30';
    if (isImage(attachment)) return 'bg-blue-50 dark:bg-blue-950/30';
    return 'bg-gray-100 dark:bg-gray-700/50';
}

function getAttachmentUrl(attachment) {
    if (attachment.url) return attachment.url;
    if (attachment.path) {
        if (attachment.path.startsWith('http') || attachment.path.startsWith('/')) {
            return attachment.path;
        }
        return `${props.storagePrefix}${attachment.path}`;
    }
    return '#';
}

async function handleDelete(attachment) {
    if (props.isReadOnly || isUploading.value) return;
    const ok = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        variant: 'danger',
    });

    if (!ok) return;

    if (props.deleteUrlTemplate) {
        const url = props.deleteUrlTemplate(attachment);
        router.delete(url, {
            preserveScroll: true,
            onSuccess: () => {
                success(t('common.attachment_deleted'));
                emit('delete', attachment);
                emit('change');
            },
            onError: (err) => {
                error(err?.message || t('common.cannot_delete_has_data'));
            },
        });
    } else {
        emit('delete', attachment);
        emit('change');
    }
}

function handleUploadSubmit() {
    if (props.isReadOnly || isUploading.value || selectedFiles.value.length === 0) return;

    if (props.uploadUrl) {
        isUploading.value = true;
        const formData = new FormData();
        selectedFiles.value.forEach((file) => {
            if (props.uploadField) {
                formData.append(props.uploadField, file);
                return;
            }
            formData.append('files[]', file);
            formData.append('attachments[]', file);
        });

        router.post(props.uploadUrl, formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                isUploading.value = false;
                success(t('common.attachments_uploaded_successfully'));
                emit('upload', selectedFiles.value);
                closeModal();
                emit('change');
            },
            onFinish: () => {
                isUploading.value = false;
            },
            onError: (err) => {
                isUploading.value = false;
                error(err?.message || Object.values(err)[0] || t('common.alert'));
            },
        });
    } else {
        emit('files-selected', selectedFiles.value);
        closeModal();
    }
}
</script>
