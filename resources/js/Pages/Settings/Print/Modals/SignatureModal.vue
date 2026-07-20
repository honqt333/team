<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[60] overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
        >
            <div
                @click="$emit('close')"
                class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity backdrop-blur-sm"
                aria-hidden="true"
            ></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">
                &#8203;
            </span>

            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-200 dark:border-gray-700"
            >
                <!-- Header -->
                <div
                    class="bg-white dark:bg-gray-800 px-8 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6 text-amber-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ $t('print_settings.add_signature') }}
                            </h3>
                            <p v-if="docKey" class="text-xs text-gray-400">
                                {{ $t('print_settings.' + docKey) }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all"
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

                <!-- Tabs -->
                <div class="px-8 pt-5 border-b border-gray-100 dark:border-gray-700">
                    <div
                        class="flex items-center gap-1 bg-gray-50/60 dark:bg-gray-900/40 p-1 rounded-xl"
                        role="tablist"
                    >
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            role="tab"
                            :aria-selected="activeTab === tab.key"
                            @click="switchTab(tab.key)"
                            :disabled="uploading"
                            class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-white dark:bg-gray-800 text-amber-600 dark:text-amber-400 shadow-sm'
                                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                            "
                        >
                            <span v-safe-html="tab.icon" class="w-4 h-4"></span>
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Tab Body -->
                <div class="px-8 py-6 space-y-5 max-h-[60vh] overflow-y-auto custom-scrollbar">
                    <!-- Shared: name fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest"
                            >
                                {{ $t('print_settings.name_ar') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_ar"
                                type="text"
                                :placeholder="
                                    isRtl ? 'مثال: توقيع المدير' : 'e.g. Manager Signature'
                                "
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-amber-400 focus:border-amber-400 outline-none text-end text-sm"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <label
                                class="block text-[10px] font-black text-gray-400 uppercase tracking-widest"
                            >
                                {{ $t('print_settings.name_en') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_en"
                                type="text"
                                :placeholder="
                                    isRtl ? 'مثال: Manager Signature' : 'e.g. Manager Signature'
                                "
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-white focus:ring-1 focus:ring-amber-400 focus:border-amber-400 outline-none text-sm"
                                dir="ltr"
                            />
                        </div>
                    </div>

                    <!-- ── Draw tab ───────────────────────────────────────── -->
                    <div v-show="activeTab === 'draw'" class="space-y-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('print_settings.canvas_hint') }}
                        </p>

                        <!-- Toolbar -->
                        <div
                            class="flex items-center justify-between gap-3 p-2.5 rounded-xl bg-gray-50/60 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700"
                        >
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <label
                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest whitespace-nowrap"
                                >
                                    {{ $t('print_settings.brush_size') }}
                                </label>
                                <input
                                    v-model.number="brushSize"
                                    type="range"
                                    min="1"
                                    max="10"
                                    step="1"
                                    class="flex-1 h-1.5 accent-amber-600"
                                />
                                <span
                                    class="text-xs font-mono text-gray-600 dark:text-gray-300 w-6 text-center"
                                >
                                    {{ brushSize }}
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="clearCanvas"
                                class="text-[10px] font-bold text-red-500 hover:text-red-600 px-2.5 py-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-all flex items-center gap-1.5"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
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
                                {{ $t('print_settings.clear_canvas') }}
                            </button>
                        </div>

                        <!-- Canvas -->
                        <div
                            class="rounded-2xl border-2 border-dashed border-amber-200 dark:border-amber-800/50 bg-white dark:bg-gray-900/30 p-2 flex items-center justify-center"
                        >
                            <canvas
                                ref="canvasEl"
                                width="640"
                                height="240"
                                class="w-full h-auto max-h-48 bg-white touch-none cursor-crosshair rounded-xl"
                                :class="{ 'opacity-50': uploading }"
                                :style="{ aspectRatio: '8 / 3' }"
                                @mousedown="onPointerDown"
                                @mousemove="onPointerMove"
                                @mouseup="onPointerUp"
                                @mouseleave="onPointerUp"
                                @touchstart.prevent="onPointerDown"
                                @touchmove.prevent="onPointerMove"
                                @touchend.prevent="onPointerUp"
                            ></canvas>
                        </div>
                        <p v-if="canvasError" class="text-[10px] text-red-500 font-bold">
                            {{ canvasError }}
                        </p>
                    </div>

                    <!-- ── Upload tab ─────────────────────────────────────── -->
                    <div v-show="activeTab === 'upload'" class="space-y-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('print_settings.upload_hint') }}
                        </p>

                        <label
                            class="block cursor-pointer rounded-2xl border-2 border-dashed border-indigo-200 dark:border-indigo-800/50 hover:border-indigo-400 dark:hover:border-indigo-500 bg-indigo-50/30 dark:bg-indigo-950/20 p-6 text-center transition-all"
                            :class="{ 'pointer-events-none opacity-50': uploading }"
                        >
                            <input
                                ref="fileInputEl"
                                type="file"
                                accept="image/png,image/jpeg,image/svg+xml,.png,.jpg,.jpeg,.svg"
                                class="sr-only"
                                @change="onFileSelected"
                            />
                            <svg
                                class="w-10 h-10 mx-auto text-indigo-400 mb-2"
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
                            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                {{
                                    uploadFile
                                        ? uploadFile.name
                                        : isRtl
                                          ? 'اضغط لاختيار ملف'
                                          : 'Click to choose a file'
                                }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-1 font-mono">
                                PNG · JPG · SVG — max 2 MB
                            </p>
                        </label>

                        <div
                            v-if="uploadPreviewUrl"
                            class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/30 p-3 flex items-center justify-center"
                        >
                            <img
                                :src="uploadPreviewUrl"
                                alt="preview"
                                class="max-h-32 max-w-full object-contain"
                            />
                        </div>

                        <p v-if="uploadError" class="text-[10px] text-red-500 font-bold">
                            {{ uploadError }}
                        </p>
                    </div>

                    <!-- ── Library tab ────────────────────────────────────── -->
                    <div v-show="activeTab === 'library'" class="space-y-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $t('print_settings.library_select') }}
                        </p>

                        <div
                            v-if="librarySignatures.length === 0"
                            class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-900/30 p-8 flex flex-col items-center gap-2 text-gray-400"
                        >
                            <svg
                                class="w-10 h-10 opacity-30"
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
                            <span class="text-xs font-bold">
                                {{ $t('print_settings.library_empty') }}
                            </span>
                        </div>

                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <button
                                v-for="sig in librarySignatures"
                                :key="sig.id"
                                type="button"
                                @click="selectLibrarySig(sig)"
                                :disabled="uploading"
                                class="group relative rounded-2xl border-2 transition-all overflow-hidden bg-white dark:bg-gray-900/30 p-2 flex flex-col gap-1.5 disabled:opacity-50"
                                :class="
                                    selectedLibraryId === sig.id
                                        ? 'border-emerald-500 ring-2 ring-emerald-500/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-700'
                                "
                            >
                                <div
                                    class="aspect-[8/3] bg-white flex items-center justify-center overflow-hidden rounded-xl"
                                >
                                    <img
                                        :src="sig.url"
                                        :alt="sig.name_ar || sig.name_en"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                </div>
                                <p
                                    class="text-[10px] font-bold text-gray-700 dark:text-gray-300 truncate text-start"
                                >
                                    {{
                                        isRtl
                                            ? sig.name_ar || sig.name_en
                                            : sig.name_en || sig.name_ar
                                    }}
                                </p>
                                <div
                                    v-if="selectedLibraryId === sig.id"
                                    class="absolute top-1.5 end-1.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center"
                                >
                                    <svg
                                        class="w-3 h-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="3"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Submission error -->
                    <p
                        v-if="
                            form.errors &&
                            (form.errors.signature ||
                                form.errors.error ||
                                form.errors.name_ar ||
                                form.errors.name_en)
                        "
                        class="text-[10px] text-red-500 font-bold"
                    >
                        {{
                            form.errors.signature ||
                            form.errors.error ||
                            form.errors.name_ar ||
                            form.errors.name_en
                        }}
                    </p>
                </div>

                <!-- Footer -->
                <div
                    class="bg-gray-50 dark:bg-gray-900/50 px-8 py-5 flex items-center gap-3 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        type="button"
                        @click="$emit('close')"
                        :disabled="uploading"
                        class="flex-1 py-2.5 rounded-2xl bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-100 dark:hover:bg-gray-600 transition-all border border-gray-200 dark:border-gray-600 text-sm disabled:opacity-50"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="handleSave"
                        :disabled="uploading"
                        class="flex-[2] py-2.5 rounded-2xl bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold transition-all shadow-lg shadow-amber-500/20 hover:scale-[1.02] active:scale-[0.98] text-sm flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                    >
                        <svg v-if="uploading" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                                fill="none"
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
                                    ? $t('print_settings.signature_uploading')
                                    : $t('print_settings.save_signature')
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, usePage } from '@inertiajs/vue3';

const { locale, t: i18nT } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

// Shorthand: t falls back to the key string if the i18n bundle is missing it
function t(key) {
    try {
        const v = i18nT(key);
        return typeof v === 'string' && v.length > 0 ? v : key;
    } catch (e) {
        return key;
    }
}

const props = defineProps({
    show: Boolean,
    document: {
        type: Object,
        default: () => ({}),
    },
    docKey: {
        type: String,
        default: '',
    },
    /**
     * Optional pre-selected library signature id (skip the library tab).
     */
    initialLibraryId: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'signature-saved']);

const page = usePage();

// Library signatures — pulled from the page props the backend already attaches
// to `print_settings.documents[docKey].signatures[]` (per the
// PrintSettingsSignatureController contract). Falls back to a top-level
// `signatures` prop if provided.
const librarySignatures = computed(() => {
    const fromDoc = props.document?.signatures ?? [];
    if (fromDoc.length > 0) return fromDoc;
    return page.props?.print_settings?.signatures ?? page.props?.signatures ?? [];
});

// ── Tabs ───────────────────────────────────────────────────────────────
const tabs = computed(() => [
    { key: 'draw', label: t('print_settings.tab_draw'), icon: drawIcon() },
    { key: 'upload', label: t('print_settings.tab_upload'), icon: uploadIcon() },
    { key: 'library', label: t('print_settings.tab_library'), icon: libraryIcon() },
]);

const activeTab = ref('draw');

function switchTab(key) {
    if (uploading.value) return;
    activeTab.value = key;
    // reset per-tab state when leaving
    if (key !== 'upload') clearUploadSelection();
    if (key !== 'draw') {
        canvasError.value = '';
    }
}

function drawIcon() {
    return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>';
}
function uploadIcon() {
    return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>';
}
function libraryIcon() {
    return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-4H5m14 8H5m14 4H5"/></svg>';
}

// ── Form (used for the upload path so Inertia handles CSRF + flash) ────
const form = useForm({
    signature: null,
    name_ar: '',
    name_en: '',
    document_type: '',
});

const uploading = computed(() => form.processing);

// ── Canvas state ───────────────────────────────────────────────────────
const canvasEl = ref(null);
const brushSize = ref(3);
const isDrawing = ref(false);
const canvasError = ref('');
let lastPoint = null;
let ctxCache = null;

function getCtx() {
    if (!canvasEl.value) return null;
    if (ctxCache) return ctxCache;
    ctxCache = canvasEl.value.getContext('2d');
    if (ctxCache) {
        ctxCache.lineCap = 'round';
        ctxCache.lineJoin = 'round';
        ctxCache.strokeStyle = '#0f172a';
    }
    return ctxCache;
}

function clearCanvas() {
    const canvas = canvasEl.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    canvasError.value = '';
    lastPoint = null;
}

function isCanvasBlank() {
    const canvas = canvasEl.value;
    if (!canvas) return true;
    const ctx = canvas.getContext('2d');
    if (!ctx) return true;
    const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    for (let i = 3; i < data.length; i += 4) {
        if (data[i] !== 0) return false;
    }
    return true;
}

function getCanvasPoint(e) {
    const canvas = canvasEl.value;
    if (!canvas) return null;
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const touch = e.touches?.[0] ?? e.changedTouches?.[0];
    const clientX = touch ? touch.clientX : e.clientX;
    const clientY = touch ? touch.clientY : e.clientY;
    return {
        x: (clientX - rect.left) * scaleX,
        y: (clientY - rect.top) * scaleY,
    };
}

function onPointerDown(e) {
    if (uploading.value) return;
    isDrawing.value = true;
    const ctx = getCtx();
    if (!ctx) return;
    ctx.lineWidth = brushSize.value;
    const point = getCanvasPoint(e);
    lastPoint = point;
    // dot for click without drag
    if (point) {
        ctx.beginPath();
        ctx.arc(point.x, point.y, brushSize.value / 2, 0, Math.PI * 2);
        ctx.fillStyle = ctx.strokeStyle;
        ctx.fill();
    }
}

function onPointerMove(e) {
    if (!isDrawing.value) return;
    const ctx = getCtx();
    if (!ctx) return;
    const point = getCanvasPoint(e);
    if (!point || !lastPoint) {
        lastPoint = point;
        return;
    }
    ctx.beginPath();
    ctx.moveTo(lastPoint.x, lastPoint.y);
    ctx.lineTo(point.x, point.y);
    ctx.lineWidth = brushSize.value;
    ctx.stroke();
    lastPoint = point;
}

function onPointerUp() {
    isDrawing.value = false;
    lastPoint = null;
}

function canvasToBlob() {
    return new Promise((resolve, reject) => {
        const canvas = canvasEl.value;
        if (!canvas) return reject(new Error('no canvas'));
        canvas.toBlob((blob) => {
            if (!blob) return reject(new Error('toBlob returned null'));
            resolve(blob);
        }, 'image/png');
    });
}

// ── Upload tab state ──────────────────────────────────────────────────
const fileInputEl = ref(null);
const uploadFile = ref(null);
const uploadPreviewUrl = ref('');
const uploadError = ref('');

function clearUploadSelection() {
    uploadFile.value = null;
    if (uploadPreviewUrl.value) {
        URL.revokeObjectURL(uploadPreviewUrl.value);
        uploadPreviewUrl.value = '';
    }
    uploadError.value = '';
    if (fileInputEl.value) fileInputEl.value.value = '';
}

const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/svg+xml'];
const ALLOWED_EXT = ['png', 'jpg', 'jpeg', 'svg'];
const MAX_BYTES = 2 * 1024 * 1024; // 2 MB

function onFileSelected(e) {
    uploadError.value = '';
    const file = e.target.files?.[0];
    if (!file) return;

    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (!ALLOWED_MIME.includes(file.type) && !ALLOWED_EXT.includes(ext)) {
        uploadError.value = t('print_settings.signature_invalid_file_type');
        clearUploadSelection();
        return;
    }
    if (file.size > MAX_BYTES) {
        uploadError.value = t('print_settings.upload_failed');
        clearUploadSelection();
        return;
    }

    uploadFile.value = file;
    if (uploadPreviewUrl.value) URL.revokeObjectURL(uploadPreviewUrl.value);
    uploadPreviewUrl.value = URL.createObjectURL(file);
}

// ── Library state ─────────────────────────────────────────────────────
const selectedLibraryId = ref('');

function selectLibrarySig(sig) {
    selectedLibraryId.value = selectedLibraryId.value === sig.id ? '' : sig.id;
}

// ── Lifecycle ─────────────────────────────────────────────────────────
onMounted(() => {
    nextTick(() => {
        clearCanvas();
        if (props.initialLibraryId) {
            activeTab.value = 'library';
            selectedLibraryId.value = props.initialLibraryId;
        }
    });
});

onBeforeUnmount(() => {
    if (uploadPreviewUrl.value) URL.revokeObjectURL(uploadPreviewUrl.value);
});

// Reset state each time the modal opens
watch(
    () => props.show,
    (visible) => {
        if (visible) {
            activeTab.value = 'draw';
            form.reset();
            form.signature = null;
            form.document_type = props.docKey || '';
            form.errors = {};
            selectedLibraryId.value = '';
            clearUploadSelection();
            canvasError.value = '';
            nextTick(() => clearCanvas());
        }
    }
);

// ── Save flow ─────────────────────────────────────────────────────────
async function handleSave() {
    if (uploading.value) return;

    if (!form.name_ar || !form.name_en) {
        form.setError('name_ar', !form.name_ar ? 'name_ar required' : null);
        form.setError('name_en', !form.name_en ? 'name_en required' : null);
        return;
    }

    if (activeTab.value === 'draw') {
        if (isCanvasBlank()) {
            canvasError.value = t('print_settings.drawing_required');
            return;
        }
        try {
            const blob = await canvasToBlob();
            const file = new File([blob], `signature-${Date.now()}.png`, { type: 'image/png' });
            form.signature = file;
            form.document_type = props.docKey || '';
            submitForm();
        } catch (err) {
            canvasError.value = t('print_settings.upload_failed');
        }
        return;
    }

    if (activeTab.value === 'upload') {
        if (!uploadFile.value) {
            uploadError.value = t('print_settings.upload_required');
            return;
        }
        form.signature = uploadFile.value;
        form.document_type = props.docKey || '';
        submitForm();
        return;
    }

    if (activeTab.value === 'library') {
        if (!selectedLibraryId.value) {
            form.setError('error', t('print_settings.selection_required'));
            return;
        }
        const sig = librarySignatures.value.find((s) => s.id === selectedLibraryId.value);
        if (sig) {
            emit('signature-saved', { ...sig, document_type: props.docKey || '' });
            emit('close');
        }
        return;
    }
}

function submitForm() {
    // Inertia form post: backend returns JSON {id, url, name, uploaded_at, ...}
    form.post('/app/settings/print/signatures', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props?.flash?.signature ?? page.props?.signature;
            const saved = flash ?? {
                id: `local-${Date.now()}`,
                name: form.name_en || form.name_ar,
                name_ar: form.name_ar,
                name_en: form.name_en,
                url: null,
                document_type: props.docKey || '',
                uploaded_at: new Date().toISOString(),
            };
            emit('signature-saved', saved);
            emit('close');
        },
        onError: () => {
            // Keep modal open; errors render in body
        },
    });
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
