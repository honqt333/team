<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('settings.cards.print.title')"
                :subtitle="$t('settings.cards.print.description')"
                gradientFrom="from-amber-500"
                gradientTo="to-orange-600"
                glowFrom="from-amber-500"
                badgeBg="bg-amber-50/50 dark:bg-amber-900/30"
                badgeText="text-amber-600 dark:text-amber-400"
                badgeBorder="border-amber-100/50 dark:border-amber-800/30"
                badgeDot="bg-amber-500"
            >
                <template #back>
                    <Link href="/app/settings"
                        class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center hover:bg-amber-50 dark:hover:bg-amber-900/20 text-amber-600 dark:text-amber-400 shadow-md transition-all border border-gray-100 dark:border-gray-700 group/back"
                        :title="$t('common.back')">
                        <svg class="w-5 h-5 rtl:rotate-180 group-hover/back:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                </template>

                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </template>

                <template #actions>
                    <button @click="save()" :disabled="form.processing"
                        class="group relative inline-flex items-center gap-2 px-8 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold rounded-xl transition-all hover:scale-[1.02] hover:shadow-lg hover:shadow-amber-500/25 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                        <svg v-if="form.processing" class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="relative">{{ form.processing ? $t('common.loading') : $t('common.save') }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Documents Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-start border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center w-16">
                                    {{ $t('print_settings.columns.id') }}
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('print_settings.columns.name') }}
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">
                                    {{ $t('print_settings.columns.print_terms') }}
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">
                                    {{ $t('print_settings.columns.terms_first_page') }}
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">
                                    {{ $t('print_settings.columns.show_stamp') }}
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">
                                    {{ $t('print_settings.columns.show_iban') }}
                                </th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">
                                    {{ $t('print_settings.columns.updated_at') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(doc, key, index) in form.documents" :key="key" class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-medium text-center">
                                    {{ index + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <button @click="openEditModal(key)" class="text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:underline text-start">
                                            {{ $t('print_settings.' + key) }}
                                        </button>
                                        <button @click="openTermsList(key)" class="text-[10px] text-gray-400 hover:text-amber-500 transition-colors text-start">
                                            {{ $t('print_settings.terms_and_conditions') }}
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" v-model="doc.print_terms" class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500 transition-all cursor-pointer" />
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" v-model="doc.terms_first_page" class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500 transition-all cursor-pointer" />
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" v-model="doc.show_stamp" class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500 transition-all cursor-pointer" />
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" v-model="doc.show_iban" class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500 transition-all cursor-pointer" />
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="doc.updated_at" class="flex flex-col items-center gap-1">
                                        <div class="w-6 h-6 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 1.944A11.947 11.947 0 012.183 5c.1 3.144.694 6.228 1.713 9.146.164.47.41.912.726 1.304l2.583 3.23a.75.75 0 001.188 0l2.583-3.23a4.506 4.506 0 00.726-1.304c1.02-2.918 1.613-6.002 1.713-9.146A11.947 11.947 0 0110 1.944zM10 18a.75.75 0 01-.75-.75V2.75a.75.75 0 011.5 0v14.5A.75.75 0 0110 18z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <span class="text-[10px] text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ doc.updated_at }}</span>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Note at Bottom -->
                <div class="p-4 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400">
                        {{ $t('print_settings.note') }}
                    </p>
                </div>
            </div>

            <!-- Visual Settings (Old section kept below for logo/stamp/etc) -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $t('print_settings.visual_preferences') }}
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/30">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('print_settings.show_logo') }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.visual.show_logo" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/30">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('print_settings.show_stamp') }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.visual.show_stamp" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/30">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('print_settings.show_qr_code') }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.visual.show_qr_code" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600"></div>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/30">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $t('print_settings.primary_color') }}</label>
                            <div class="flex items-center gap-4">
                                <input v-model="form.visual.primary_color" type="color" class="w-12 h-12 rounded-lg border-0 p-0 overflow-hidden cursor-pointer bg-transparent" />
                                <input v-model="form.visual.primary_color" type="text" class="flex-1 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm" dir="ltr" />
                            </div>
                        </div>
                        <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/30 dark:bg-gray-900/30">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $t('print_settings.footer_text') }}</label>
                            <textarea v-model="form.visual.footer_text" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" :placeholder="$t('print_settings.footer_placeholder')"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms Modal -->
        <TermsModal 
            v-if="editingDoc" 
            :show="true" 
            :document="form.documents[editingDoc]" 
            :doc-key="editingDoc"
            @close="editingDoc = null"
            @save="handleDocSave"
        />

        <!-- Terms List Modal -->
        <TermsListModal 
            v-if="editingTermsDoc" 
            :show="true" 
            :document="form.documents[editingTermsDoc]" 
            :doc-key="editingTermsDoc"
            @close="editingTermsDoc = null"
            @save="handleDocSave"
        />
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';
import TermsModal from './Modals/TermsModal.vue';
import TermsListModal from './Modals/TermsListModal.vue';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    print_settings: Object
});

const form = useForm({
    section: 'print',
    documents: props.print_settings?.documents ?? {},
    visual: props.print_settings?.visual ?? {
        show_logo: true,
        show_stamp: true,
        show_qr_code: true,
        primary_color: '#fbbf24',
        footer_text: '',
    }
});

const editingDoc = ref(null);
const editingTermsDoc = ref(null);

function openEditModal(key) {
    editingDoc.value = key;
}

function openTermsList(key) {
    editingTermsDoc.value = key;
}

function handleDocSave(newDoc) {
    if (editingDoc.value) {
        form.documents[editingDoc.value] = { ...newDoc };
    } else if (editingTermsDoc.value) {
        form.documents[editingTermsDoc.value] = { ...newDoc };
    }
}

function save() {
    form.put('/app/settings/system', {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
        },
    });
}
</script>

