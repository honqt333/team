<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="$emit('close')" class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 px-8 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">التوقيعات الرسمية</h3>
                            <p class="text-xs text-gray-400">{{ $t('print_settings.' + docKey) }}</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-8 py-6 space-y-3 max-h-[60vh] overflow-y-auto custom-scrollbar">

                    <!-- Column headers -->
                    <div class="grid grid-cols-[1.5rem_1fr_1fr_2rem_2rem] gap-2 px-1 text-[9px] font-black text-gray-400 uppercase tracking-widest">
                        <span></span>
                        <span>الاسم (عربي)</span>
                        <span>Name (English)</span>
                        <span class="text-center">ظهور</span>
                        <span></span>
                    </div>

                    <!-- Signatures List -->
                    <div
                        v-for="(sig, index) in localSignatures"
                        :key="index"
                        class="grid grid-cols-[1.5rem_1fr_1fr_2rem_2rem] items-center gap-2 p-2.5 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-900/30 group hover:border-indigo-200 dark:hover:border-indigo-800 transition-all"
                    >
                        <span class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 text-[10px] font-black flex items-center justify-center">{{ index + 1 }}</span>

                        <input
                            v-model="sig.name_ar"
                            type="text"
                            placeholder="الاسم بالعربي"
                            class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 outline-none text-end w-full"
                        />

                        <input
                            v-model="sig.name_en"
                            type="text"
                            placeholder="English name"
                            class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400 outline-none w-full"
                            dir="ltr"
                        />

                        <label class="relative inline-flex items-center justify-center cursor-pointer" :title="sig.show ? 'مرئي' : 'مخفي'">
                            <input type="checkbox" v-model="sig.show" class="sr-only peer">
                            <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>

                        <button
                            @click="deleteSignature(index)"
                            class="w-6 h-6 rounded-lg text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 hover:text-red-600 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100"
                            title="حذف"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Empty state -->
                    <div v-if="localSignatures.length === 0" class="py-8 flex flex-col items-center gap-2 text-gray-400">
                        <svg class="w-10 h-10 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        <span class="text-sm">لا توجد توقيعات بعد</span>
                    </div>

                    <!-- Add Button -->
                    <button
                        @click="addSignature"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 border-2 border-dashed border-indigo-200 dark:border-indigo-800 rounded-xl text-indigo-600 dark:text-indigo-400 text-xs font-bold hover:bg-indigo-50 dark:hover:bg-indigo-950/20 hover:border-indigo-400 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        إضافة توقيع جديد
                    </button>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-900/50 px-8 py-5 flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-700">
                    <button
                        @click="$emit('close')"
                        class="px-6 py-2.5 rounded-2xl bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-100 dark:hover:bg-gray-600 transition-all border border-gray-200 dark:border-gray-600 text-sm"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        @click="handleSave"
                        class="px-8 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-bold transition-all shadow-lg shadow-indigo-500/20 hover:scale-[1.02] active:scale-[0.98] text-sm"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const DEFAULT_SIGNATURES = [
    { name_ar: 'توقيع المدير', name_en: 'Manager Signature', order: 1, show: true },
    { name_ar: 'توقيع العميل', name_en: 'Customer Signature', order: 2, show: true },
];

const props = defineProps({
    show: Boolean,
    document: Object,
    docKey: String
});

const emit = defineEmits(['close', 'save']);

function initSignatures(doc) {
    const sigs = doc?.signatures ?? [];
    if (sigs.length === 0) {
        return DEFAULT_SIGNATURES.map(s => ({ ...s }));
    }
    return sigs.map(s => ({ ...s }));
}

const localSignatures = ref(initSignatures(props.document));

watch(() => props.document, (newVal) => {
    localSignatures.value = initSignatures(newVal);
}, { deep: true });

function addSignature() {
    localSignatures.value.push({
        name_ar: '',
        name_en: '',
        order: localSignatures.value.length + 1,
        show: true
    });
}

function deleteSignature(index) {
    localSignatures.value.splice(index, 1);
    localSignatures.value.forEach((s, i) => { s.order = i + 1; });
}

function handleSave() {
    emit('save', {
        ...props.document,
        signatures: [...localSignatures.value]
    });
    emit('close');
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
