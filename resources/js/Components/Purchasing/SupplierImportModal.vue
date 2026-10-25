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
                        <div
                            class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg shadow-orange-500/30"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{
                                    $t('purchasing.suppliers.import_title') ||
                                    'استيراد الموردين من إكسل'
                                }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{
                                    $t('purchasing.suppliers.import_subtitle') ||
                                    'إضافة قائمة موردين جماعية عبر ملف XLSX / CSV'
                                }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="close"
                        :title="$t('common.close')"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Instructions -->
                <div
                    class="mb-6 p-4 rounded-2xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800/30"
                >
                    <h3
                        class="font-bold text-orange-900 dark:text-orange-300 mb-3 flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ $t('customers.import.instructions_title') || 'تعليمات الاستيراد' }}
                    </h3>
                    <ol
                        class="text-sm text-orange-800 dark:text-orange-200 space-y-1.5 list-decimal list-inside"
                    >
                        <li>
                            {{
                                $t('customers.import.step_1') ||
                                'قم بتحميل النموذج المخصص وتعبئة بيانات الموردين'
                            }}
                        </li>
                        <li>
                            {{
                                $t('customers.import.step_2') ||
                                'تأكد من إدخال اسم المورد والشخص المسؤول بشكل صحيح'
                            }}
                        </li>
                        <li>
                            {{
                                $t('customers.import.step_3') ||
                                'احفظ الملف بصيغة XLSX ثم ارفعه هنا للبدء في الاستيراد'
                            }}
                        </li>
                    </ol>
                </div>

                <!-- Required Fields Info -->
                <div
                    class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/30"
                >
                    <h4 class="font-bold text-amber-900 dark:text-amber-300 mb-2 text-sm">
                        {{ $t('customers.import.required_fields') || 'الحقول الأساسية' }}
                    </h4>
                    <div class="text-sm text-amber-800 dark:text-amber-200 space-y-1">
                        <p>
                            <strong>{{ $t('purchasing.suppliers.name') || 'اسم المورد' }}</strong>
                            - {{ $t('customers.import.required') || 'مطلوب وإلزامي' }}
                        </p>
                        <p>
                            <strong>{{ $t('purchasing.suppliers.type') || 'النوع' }}</strong>
                            - parts (قطع غيار) أو services (خدمات)
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
                    {{ $t('purchasing.suppliers.download_template') || 'تحميل نموذج إكسل الفارغ' }}
                </a>

                <!-- File Upload Area -->
                <div
                    class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all"
                    :class="
                        isDragging
                            ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20'
                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                    "
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
                        <div
                            class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center"
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
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                />
                            </svg>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{
                                $t('customers.import.drag_drop') ||
                                'اسحب وأفلت الملف هنا، أو انقر للاختيار'
                            }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{
                                $t('customers.import.supported_formats') ||
                                'الصيغ المدعومة: XLSX, XLS, CSV'
                            }}
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            class="w-16 h-16 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center"
                        >
                            <svg
                                class="w-8 h-8 text-emerald-600 dark:text-emerald-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <p class="font-bold text-gray-900 dark:text-white">
                            {{ selectedFile.name }}
                        </p>
                        <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                        <button
                            type="button"
                            @click.stop="removeFile"
                            class="text-xs text-red-500 hover:text-red-700 font-bold"
                        >
                            {{ $t('common.delete') || 'إزالة الملف' }}
                        </button>
                    </div>
                </div>

                <!-- Errors / Results -->
                <div v-if="importResults" class="mt-6 space-y-4">
                    <div
                        v-if="importResults.imported > 0"
                        class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30"
                    >
                        <p
                            class="text-emerald-800 dark:text-emerald-300 font-bold flex items-center gap-2"
                        >
                            <svg
                                class="w-5 h-5 text-emerald-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            تم استيراد {{ importResults.imported }} مورد بنجاح!
                        </p>
                    </div>

                    <div
                        v-if="importResults.errors?.length > 0"
                        class="p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 max-h-40 overflow-y-auto"
                    >
                        <p class="text-red-800 dark:text-red-300 font-bold mb-2">
                            تنبيهات وأخطاء أثناء الاستيراد:
                        </p>
                        <ul
                            class="text-xs text-red-700 dark:text-red-300 space-y-1 list-disc list-inside"
                        >
                            <li v-for="(err, idx) in importResults.errors" :key="idx">{{ err }}</li>
                        </ul>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="close"
                        class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-bold transition-all"
                    >
                        {{ $t('common.cancel') || 'إلغاء' }}
                    </button>
                    <button
                        type="button"
                        @click="uploadFile"
                        :disabled="!selectedFile || uploading"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-red-600 text-white text-sm font-bold hover:from-orange-700 hover:to-red-700 shadow-md shadow-orange-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
                    >
                        <svg
                            v-if="uploading"
                            class="w-4 h-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
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
                        <span>
                            {{
                                uploading
                                    ? $t('common.uploading') || 'جاري الرفع...'
                                    : $t('customers.import.start_import') || 'بدء الاستيراد'
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'imported']);
const { showSuccess, showError } = useToast();

const fileInput = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const uploading = ref(false);
const importResults = ref(null);

const handleFileSelect = (event) => {
    const files = event.target.files;
    if (files.length > 0) {
        selectedFile.value = files[0];
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        selectedFile.value = files[0];
    }
};

const removeFile = () => {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    importResults.value = null;
};

const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

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
            showSuccess(`تم استيراد ${response.data.imported} مورد بنجاح`);
            emit('imported');
        } else if (response.data.errors?.length > 0) {
            showError('لم يتم استيراد أي مورد، يرجى مراجعة الأخطاء');
        }
    } catch (error) {
        showError(error.response?.data?.message || 'حدث خطأ أثناء رفع واستيراد الملف');
    } finally {
        uploading.value = false;
    }
};

const close = () => {
    removeFile();
    emit('close');
};
</script>
