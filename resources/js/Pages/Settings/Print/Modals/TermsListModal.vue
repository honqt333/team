<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="$emit('close')" class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-start overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200 dark:border-gray-700">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 px-8 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $t('print_settings.terms_and_conditions') }} - {{ $t('print_settings.' + docKey) }}
                        </h3>
                    </div>
                    <button @click="$emit('close')" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-8 py-8 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <!-- Search and Add -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="relative flex-1 max-w-sm">
                            <span class="absolute inset-y-0 start-0 flex items-center ps-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input 
                                v-model="search" 
                                type="text" 
                                :placeholder="$t('common.search')" 
                                class="w-full ps-10 pe-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all text-sm"
                            />
                        </div>
                        <button @click="openAddTerm" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 font-bold hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-all text-sm border border-amber-200/50 dark:border-amber-700/50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            {{ $t('common.add') }}
                        </button>
                    </div>

                    <!-- Terms Table -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm">
                        <table class="w-full text-start border-collapse">
                            <thead class="bg-gray-50/50 dark:bg-gray-800/50">
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center w-16">{{ $t('print_settings.columns.id') }}</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $t('print_settings.terms_and_conditions') }}</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center w-40">{{ $t('print_settings.columns.updated_at') }}</th>
                                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-center w-24">{{ $t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                <tr v-for="(term, index) in filteredTerms" :key="index" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-all group">
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 text-center font-medium">{{ index + 1 }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <p class="text-sm font-bold text-gray-700 dark:text-gray-200 line-clamp-2" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
                                                {{ $i18n.locale === 'ar' ? term.text_ar : (term.text_en || term.text_ar) }}
                                            </p>
                                            <span class="text-[10px] text-gray-400">{{ $t('print_settings.order_by') }}: {{ term.order }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                                            </div>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ term.updated_at || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="editTerm(index)" class="p-2 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button @click="deleteTerm(index)" class="p-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredTerms.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2 text-gray-400">
                                            <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-sm italic">{{ $t('common.no_records') }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-900/50 px-8 py-6 flex items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700">
                    <button class="p-3 rounded-2xl bg-white dark:bg-gray-800 text-gray-500 hover:text-amber-600 transition-all border border-gray-200 dark:border-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                    <div class="flex gap-3">
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
        </div>

        <!-- Edit Modal -->
        <TermEditModal 
            v-if="showingEditModal"
            :show="true"
            :term="editingTermIndex !== null ? localTerms[editingTermIndex] : undefined"
            @close="closeEditModal"
            @save="saveTerm"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import TermEditModal from './TermEditModal.vue';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    document: Object,
    docKey: String
});

const emit = defineEmits(['close', 'save']);

const localTerms = ref([...(props.document.terms || [])]);
const search = ref('');
const showingEditModal = ref(false);
const editingTermIndex = ref(null);

const filteredTerms = computed(() => {
    let terms = [...localTerms.value].sort((a, b) => a.order - b.order);
    if (search.value) {
        const s = search.value.toLowerCase();
        terms = terms.filter(t => 
            (t.text_ar && t.text_ar.toLowerCase().includes(s)) || 
            (t.text_en && t.text_en.toLowerCase().includes(s))
        );
    }
    return terms;
});

watch(() => props.document.terms, (newTerms) => {
    localTerms.value = [...(newTerms || [])];
}, { deep: true });

function openAddTerm() {
    editingTermIndex.value = null;
    showingEditModal.value = true;
}

function editTerm(index) {
    editingTermIndex.value = index;
    showingEditModal.value = true;
}

function closeEditModal() {
    showingEditModal.value = false;
    editingTermIndex.value = null;
}

function saveTerm(term) {
    if (editingTermIndex.value !== null) {
        localTerms.value[editingTermIndex.value] = { 
            ...term,
            updated_at: new Date().toLocaleString(),
            updated_by: 'Current User' // Should be passed from props or auth
        };
    } else {
        localTerms.value.push({
            ...term,
            updated_at: new Date().toLocaleString(),
            updated_by: 'Current User'
        });
    }
}

function deleteTerm(index) {
    if (confirm(t('common.unsaved_changes_message'))) {
        localTerms.value.splice(index, 1);
    }
}

function handleSave() {
    emit('save', { ...props.document, terms: [...localTerms.value] });
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
