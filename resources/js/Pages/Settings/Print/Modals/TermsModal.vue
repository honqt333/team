<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="$emit('close')" class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-200 dark:border-gray-700">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 px-8 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ document.name }}
                        </h3>
                    </div>
                    <button @click="$emit('close')" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-8 py-8 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <!-- Names Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('print_settings.name_ar') }}
                            </label>
                            <input 
                                v-model="localDoc.title_ar" 
                                type="text" 
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all text-end"
                                :placeholder="document.name"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">
                                {{ $t('print_settings.name_en') }}
                            </label>
                            <input 
                                v-model="localDoc.title_en" 
                                type="text" 
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all"
                                :placeholder="document.name"
                            />
                        </div>
                    </div>

                    <!-- Toggles Section -->
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Print Terms Toggle -->
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $t('print_settings.columns.print_terms') }}</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="localDoc.print_terms" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                                </label>
                            </div>

                            <!-- Print Terms on First Page Toggle (Conditional) -->
                            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                <div v-if="localDoc.print_terms" class="flex items-center justify-between p-4 rounded-2xl bg-amber-50/30 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30">
                                    <span class="text-sm font-bold text-amber-700 dark:text-amber-400">{{ $t('print_settings.columns.terms_first_page') }}</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="localDoc.terms_first_page" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                                    </label>
                                </div>
                            </transition>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Show Stamp Toggle -->
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $t('print_settings.columns.show_stamp') }}</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="localDoc.show_stamp" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                                </label>
                            </div>

                            <!-- Show IBAN Toggle -->
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-700">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $t('print_settings.columns.show_iban') }}</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="localDoc.show_iban" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="w-full h-px bg-gray-100 dark:bg-gray-700"></div>

                    <!-- Signatures Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                {{ $t('print_settings.signatures') }}
                            </h3>
                            <button @click="openAddSignature" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 font-bold hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-all text-sm border border-amber-200/50 dark:border-amber-700/50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm">
                            <table class="w-full text-start border-collapse">
                                <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                                    <tr class="border-b border-gray-100 dark:border-gray-700">
                                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                            {{ $t('print_settings.signature_name') }}
                                        </th>
                                        <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center w-24">
                                            {{ $t('print_settings.order_by') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                    <tr v-for="(sig, index) in sortedSignatures" :key="index" @click="editSignature(index)" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 cursor-pointer transition-all">
                                        <td class="px-6 py-4 text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ $i18n.locale === 'ar' ? sig.name_ar : sig.name_en }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 text-center font-medium">
                                            {{ sig.order }}
                                        </td>
                                    </tr>
                                    <tr v-if="!localDoc.signatures || localDoc.signatures.length === 0">
                                        <td colspan="2" class="px-6 py-10 text-center">
                                            <div class="flex flex-col items-center gap-2 text-gray-400">
                                                <svg class="w-8 h-8 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                <span class="text-sm italic">{{ $t('common.no_records') }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-900/50 px-8 py-6 flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-700">
                    <button 
                        @click="$emit('close')"
                        class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-100 dark:hover:bg-gray-600 transition-all border border-gray-200 dark:border-gray-600"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button 
                        @click="handleSave"
                        class="px-10 py-3 rounded-2xl bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold transition-all shadow-lg shadow-amber-500/20 hover:scale-[1.02] active:scale-[0.98]"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Signature Modal -->
        <SignatureModal 
            v-if="showingSignatureModal"
            :show="true"
            :signature="editingSignature !== null ? localDoc.signatures[editingSignature] : undefined"
            @close="closeSignatureModal"
            @save="saveSignature"
        />
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import SignatureModal from './SignatureModal.vue';

const props = defineProps({
    show: Boolean,
    document: Object
});

const emit = defineEmits(['close', 'save']);

const localDoc = ref({ 
    ...props.document,
    signatures: props.document.signatures ? [...props.document.signatures] : []
});

const showingSignatureModal = ref(false);
const editingSignature = ref(null);

const sortedSignatures = computed(() => {
    return [...localDoc.value.signatures].sort((a, b) => a.order - b.order);
});

watch(() => props.document, (newVal) => {
    localDoc.value = { 
        ...newVal,
        signatures: newVal.signatures ? [...newVal.signatures] : []
    };
}, { deep: true });

function openAddSignature() {
    editingSignature.value = null;
    showingSignatureModal.value = true;
}

function editSignature(index) {
    editingSignature.value = index;
    showingSignatureModal.value = true;
}

function closeSignatureModal() {
    showingSignatureModal.value = false;
    editingSignature.value = null;
}

function saveSignature(signature) {
    if (editingSignature.value !== null) {
        localDoc.value.signatures[editingSignature.value] = signature;
    } else {
        localDoc.value.signatures.push(signature);
    }
}

function handleSave() {
    emit('save', { ...localDoc.value });
    emit('close');
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
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
