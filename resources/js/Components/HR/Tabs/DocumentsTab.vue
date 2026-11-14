<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $t('hr.documents.title') }}
            </h3>
            <button
                @click="showUploadModal = true"
                class="flex items-center gap-2 px-4 py-2 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-medium transition-colors shadow-sm shadow-focus-ring-soft dark:shadow-none"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                {{ $t('hr.documents.add') }}
            </button>
        </div>

        <!-- Documents List -->
        <div
            v-if="employee.documents?.length"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
            <div
                v-for="doc in employee.documents"
                :key="doc.id"
                class="bg-gray-50 dark:bg-gray-700/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow group"
            >
                <div class="flex items-start justify-between mb-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-selection-bg flex items-center justify-center text-action-primary"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <div
                        class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <a
                            :href="route('app.hr.documents.download', doc.id)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="p-2 text-gray-400 hover:text-action-primary"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
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
                        <button
                            @click="deleteDocument(doc.id)"
                            class="p-2 text-gray-400 hover:text-red-600"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
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
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white truncate" :title="doc.title">
                        {{ doc.title }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t(`hr.documents.types.${doc.type}`) }}
                    </p>
                    <div v-if="doc.expiry_date" class="mt-3 flex items-center gap-1.5 text-xs">
                        <span class="text-gray-400">{{ $t('hr.documents.expiry') }}:</span>
                        <span
                            :class="[
                                'font-medium',
                                isExpired(doc.expiry_date)
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-gray-600 dark:text-gray-300',
                            ]"
                        >
                            {{ doc.expiry_date }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
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
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('hr.documents.no_documents') }}</p>
        </div>

        <!-- Upload Modal -->
        <BaseModal
            :show="showUploadModal"
            :title="$t('hr.documents.add')"
            size="md"
            @close="showUploadModal = false"
        >
            <form id="document-upload-form" @submit.prevent="submit" class="space-y-4">
                <AppInput
                    v-model="form.title"
                    :label="$t('hr.documents.title_label')"
                    :error="form.errors.title"
                    required
                />

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <SearchableSelect
                            v-model="form.type"
                            :options="typeOptions"
                            option-label="label"
                            option-value="value"
                            :label="$t('hr.documents.type')"
                            required
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('hr.documents.expiry') }}
                        </label>
                        <CustomDatePicker
                            v-model="form.expiry_date"
                            :placeholder="$t('hr.documents.expiry')"
                        />
                    </div>
                </div>

                <AppFileUpload
                    v-model="form.file"
                    :label="$t('hr.documents.file')"
                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                    :max-size-mb="10"
                    :error="form.errors.file"
                />
            </form>

            <template #footer>
                <div class="flex justify-end gap-3 w-full">
                    <AppButton type="button" variant="secondary" @click="showUploadModal = false">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="submit"
                        form="document-upload-form"
                        variant="primary"
                        :loading="form.processing"
                    >
                        {{ $t('common.save') }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';

const props = defineProps({
    employee: Object,
});

const { t } = useI18n();
const { success, error } = useToast();
const { confirm } = useConfirm();
const showUploadModal = ref(false);

const typeOptions = computed(() => [
    { value: 'id', label: t('hr.documents.types.id') },
    { value: 'passport', label: t('hr.documents.types.passport') },
    { value: 'license', label: t('hr.documents.types.license') },
    { value: 'certificate', label: t('hr.documents.types.certificate') },
    { value: 'contract', label: t('hr.documents.types.contract') },
    { value: 'other', label: t('hr.documents.types.other') },
]);

const form = useForm({
    title: '',
    type: 'id',
    file: null,
    expiry_date: '',
    notes: '',
});

function submit() {
    form.post(route('app.hr.employees.documents.store', props.employee.id), {
        onSuccess: () => {
            showUploadModal.value = false;
            form.reset();
            success();
        },
    });
}

async function deleteDocument(id) {
    if (await confirm(t('hr.documents.delete_confirm'))) {
        router.delete(route('app.hr.documents.destroy', id), {
            onSuccess: () => success(),
        });
    }
}

function isExpired(date) {
    if (!date) return false;
    return new Date(date) < new Date();
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
