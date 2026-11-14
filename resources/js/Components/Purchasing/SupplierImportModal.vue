<template>
    <BaseModal
        :show="show"
        size="max-w-xl"
        :closeable="!uploading"
        :title="$t('purchasing.suppliers.import_title')"
        @close="close"
    >
        <p class="mb-4 text-sm text-text-muted">{{ $t('purchasing.suppliers.import_subtitle') }}</p>
        <!-- Instructions -->
        <div
            class="mb-6 p-4 rounded-2xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800/30"
        >
            <h3 class="font-bold text-orange-900 dark:text-orange-300 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                {{ $t('customers.import.instructions_title') }}
            </h3>
            <ol
                class="text-sm text-orange-800 dark:text-orange-200 space-y-1.5 list-decimal list-inside"
            >
                <li>
                    {{ $t('customers.import.step_1') }}
                </li>
                <li>
                    {{ $t('customers.import.step_2') }}
                </li>
                <li>
                    {{ $t('customers.import.step_3') }}
                </li>
            </ol>
        </div>

        <!-- Required Fields Info -->
        <div
            class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/30"
        >
            <h4 class="font-bold text-amber-900 dark:text-amber-300 mb-2 text-sm">
                {{ $t('customers.import.required_fields') }}
            </h4>
            <div class="text-sm text-amber-800 dark:text-amber-200 space-y-1">
                <p>
                    <strong>{{ $t('purchasing.suppliers.name') }}</strong>
                    - {{ $t('customers.import.required') }}
                </p>
                <p>
                    <strong>{{ $t('purchasing.suppliers.type') }}</strong>
                    {{ $t('purchasing.import.file_hint') }}
                </p>
            </div>
        </div>

        <!-- Download Template Button -->
        <a
            :href="route('app.purchasing.suppliers.import.template')"
            class="flex items-center justify-center gap-3 w-full px-6 py-3.5 mb-6 text-sm font-bold text-white bg-gradient-to-r from-orange-600 to-red-600 rounded-2xl hover:from-orange-700 hover:to-red-700 shadow-lg shadow-orange-500/25 transition-all"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                />
            </svg>
            {{ $t('purchasing.suppliers.download_template') }}
        </a>

        <ImportFileField
            v-model="selectedFile"
            :disabled="uploading"
            @update:model-value="importResults = null"
            @invalid="showError(t('customers.import.invalid_format'))"
        />
        <ImportResult v-if="importResults" :result="importResults" />
        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" :disabled="uploading" @click="close">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    variant="primary"
                    :loading="uploading"
                    :disabled="!selectedFile"
                    @click="uploadFile"
                >
                    {{ uploading ? $t('common.uploading') : $t('customers.import.start_import') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import ImportFileField from '@/Components/Common/ImportFileField.vue';
import ImportResult from '@/Components/Common/ImportResult.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'imported']);
const { success: showSuccess, error: showError } = useToast();

const selectedFile = ref(null);
const uploading = ref(false);
const importResults = ref(null);

const uploadFile = async () => {
    if (!selectedFile.value || uploading.value) return;

    uploading.value = true;
    importResults.value = null;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await axios.post('/app/purchasing/suppliers/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        importResults.value = response.data;
        if (response.data.imported > 0) {
            showSuccess(t('purchasing.import.success_prefix'));
            emit('imported');
        } else if (response.data.errors?.length > 0) {
            showError(t('purchasing.import.nothing_imported'));
        }
    } catch (error) {
        showError(error.response?.data?.message || t('purchasing.import.upload_error'));
    } finally {
        uploading.value = false;
    }
};

const close = () => {
    selectedFile.value = null;
    importResults.value = null;
    emit('close');
};
</script>
