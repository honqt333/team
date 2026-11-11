<template>
    <BaseModal
        :show="show"
        size="max-w-xl"
        :closeable="!importing"
        :title="$t('customers.import.title')"
        @close="close"
    >
        <p class="mb-4 text-sm text-text-muted">{{ $t('customers.import.subtitle') }}</p>
        <!-- Instructions -->
        <div class="mb-6 p-4 rounded-2xl bg-selection-bg border border-action-primary/25">
            <h3 class="font-bold text-selection-text mb-3 flex items-center gap-2">
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
            <ol class="text-sm text-selection-text space-y-2 list-decimal list-inside">
                <li>{{ $t('customers.import.step_1') }}</li>
                <li>{{ $t('customers.import.step_2') }}</li>
                <li>{{ $t('customers.import.step_3') }}</li>
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
                    <strong>{{ $t('customers.columns.name') }}</strong>
                    - {{ $t('customers.import.required') }}
                </p>
                <p>
                    <strong>{{ $t('customers.columns.phone') }}</strong>
                    - {{ $t('customers.import.required_unique') }}
                </p>
            </div>
            <div class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                <strong>{{ $t('customers.columns.type') }}:</strong>
                individual, company, government
            </div>
        </div>

        <!-- Download Template Button -->
        <a
            :href="route('customers.import.template')"
            class="flex items-center justify-center gap-3 w-full px-6 py-4 mb-6 text-sm font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-2xl shadow-lg shadow-focus-ring-soft transition-all"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                />
            </svg>
            {{ $t('customers.import.download_template') }}
        </a>

        <ImportFileField
            v-model="selectedFile"
            :disabled="importing"
            @update:model-value="importResults = null"
            @invalid="toastError(t('customers.import.invalid_format'))"
        />
        <ImportResult v-if="importResults" :result="importResults" />
        <template #footer>
            <button
                @click="close"
                :disabled="importing"
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
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                {{
                    importing
                        ? $t('customers.import.importing')
                        : $t('customers.import.start_import')
                }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import ImportFileField from '@/Components/Common/ImportFileField.vue';
import ImportResult from '@/Components/Common/ImportResult.vue';
import { ref } from 'vue';
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

const selectedFile = ref(null);
const importing = ref(false);
const importResults = ref(null);

function close() {
    selectedFile.value = null;
    importResults.value = null;
    emit('close');
}

async function importFile() {
    if (!selectedFile.value || importing.value) return;

    importing.value = true;
    importResults.value = null;

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
            importResults.value = data;
            if (data.imported > 0) {
                success(t('common.import_success', { count: data.imported }));
                emit('imported');
            }
            if (!data.errors?.length) close();
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
