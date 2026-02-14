<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.documents.title') }}</h3>
            <button @click="showUploadModal = true"
                class="flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl font-medium transition-colors shadow-sm shadow-violet-200 dark:shadow-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('hr.documents.add') }}
            </button>
        </div>

        <!-- Documents List -->
        <div v-if="employee.documents?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="doc in employee.documents" :key="doc.id"
                class="bg-gray-50 dark:bg-gray-700/30 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow group">
                <div class="flex items-start justify-between mb-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a :href="`/storage/${doc.file_path}`" target="_blank"
                            class="p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </a>
                        <button @click="deleteDocument(doc.id)" class="p-2 text-gray-400 hover:text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white truncate" :title="doc.title">{{ doc.title }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $t(`hr.documents.types.${doc.type}`) }}
                    </p>
                    <div v-if="doc.expiry_date" class="mt-3 flex items-center gap-1.5 text-xs">
                        <span class="text-gray-400">{{ $t('hr.documents.expiry') }}:</span>
                        <span :class="[
                            'font-medium',
                            isExpired(doc.expiry_date) ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-300'
                        ]">
                            {{ doc.expiry_date }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else
            class="py-12 text-center bg-gray-50 dark:bg-gray-700/30 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
            <div
                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('hr.documents.no_documents') }}</p>
        </div>

        <!-- Upload Modal -->
        <Transition name="modal">
            <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showUploadModal = false"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-lg shadow-xl border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('hr.documents.add') }}
                            </h3>
                            <button @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l18 18" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('hr.documents.title_label') }}</label>
                                <input v-model="form.title" type="text"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    required />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                        $t('hr.documents.type') }}</label>
                                    <select v-model="form.type"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required>
                                        <option value="id">{{ $t('hr.documents.types.id') }}</option>
                                        <option value="passport">{{ $t('hr.documents.types.passport') }}</option>
                                        <option value="license">{{ $t('hr.documents.types.license') }}</option>
                                        <option value="certificate">{{ $t('hr.documents.types.certificate') }}</option>
                                        <option value="contract">{{ $t('hr.documents.types.contract') }}</option>
                                        <option value="other">{{ $t('hr.documents.types.other') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                        $t('hr.documents.expiry') }}</label>
                                    <input v-model="form.expiry_date" type="date"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('hr.documents.file') }}</label>
                                <input type="file" @input="form.file = $event.target.files[0]"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"
                                    required />
                            </div>

                            <div class="flex justify-end gap-3 mt-6">
                                <button type="button" @click="showUploadModal = false"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl">
                                    {{ $t('common.cancel') }}
                                </button>
                                <button type="submit" :disabled="form.processing"
                                    class="px-6 py-2 bg-violet-600 text-white rounded-xl font-medium disabled:opacity-50">
                                    {{ $t('common.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    employee: Object,
});

const { success, error } = useToast();
const showUploadModal = ref(false);

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

function deleteDocument(id) {
    if (confirm('هل أنت متأكد من حذف هذه الوثيقة؟')) {
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
