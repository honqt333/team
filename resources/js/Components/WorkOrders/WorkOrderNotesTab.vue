<template>
    <div class="space-y-6">
        <!-- Toolbar: Title & Add Button next to it, Search/Filter on the left (end) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">📝</span>
                    {{ $t('work_orders.show.tabs.notes') }}
                </h3>

                <!-- Add Button next to Title -->
                <button
                    v-if="!isReadOnly"
                    type="button"
                    @click="emit('open-add-note')"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-100 dark:shadow-none"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('work_orders.item.add_note') }}
                </button>
            </div>

            <!-- Search and view mode toggle on the left (end of row in RTL, i.e., at the end) -->
            <div class="flex items-center gap-3 self-end sm:self-auto">
                <div class="flex items-center bg-gray-100 dark:bg-gray-900 p-1 rounded-xl">
                    <button type="button" @click="viewMode = 'list'" :class="['p-1.5 rounded-lg transition-all', viewMode === 'list' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300']">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <button type="button" @click="viewMode = 'grid'" :class="['p-1.5 rounded-lg transition-all', viewMode === 'grid' ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300']">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                </div>

                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        :placeholder="$t('work_orders.search') + '...'"
                        class="w-full ps-9 pe-3 py-1.5 text-sm border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-sm"
                    />
                </div>
            </div>
        </div>

        <!-- Notes list (list/grid) -->
        <div v-if="filteredNotes.length > 0" :class="[viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4' : 'space-y-3']">
            <div v-for="note in filteredNotes" :key="note.id"
                :class="[
                    'bg-[#fffbeb] dark:bg-amber-950/10 border border-amber-200/60 dark:border-amber-900/30 p-4 sm:p-5 rounded-2xl shadow-sm relative group hover:shadow-md transition-all duration-200',
                    viewMode === 'list' ? 'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4' : 'flex flex-col justify-between h-full min-h-[160px]'
                ]">

                <!-- LIST VIEW LAYOUT -->
                <template v-if="viewMode === 'list'">
                    <div class="flex items-start gap-4 flex-1">
                        <div class="flex-shrink-0">
                            <img v-if="note.user?.photo_url" :src="note.user.photo_url" class="w-10 h-10 rounded-full object-cover">
                            <div v-else class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                                {{ note.user?.name?.charAt(0) || 'U' }}
                            </div>
                        </div>
                        <div class="space-y-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                    {{ note.user?.name || $t('common.system') }}
                                </h4>
                                <span class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                                    {{ getUserRoleName(note.user) }}
                                </span>
                            </div>
                            <button v-if="note.item_id" type="button" @click="emit('open-service-notes', note.item_id)"
                                class="text-xs font-semibold text-teal-600 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300 hover:underline text-right block">
                                {{ note.service_title_formatted }}
                            </button>
                            <p v-else class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{ note.service_title_formatted }}
                            </p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap mt-2 leading-relaxed">
                                {{ note.content }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:flex-col sm:items-end gap-2 text-right sm:border-l border-amber-200/30 sm:pl-4">
                        <div class="text-[11px] text-gray-400 dark:text-gray-500 font-mono leading-tight font-bold">
                            <div>{{ getNoteDate(note.created_at) }}</div>
                            <div class="text-gray-300 dark:text-gray-600">{{ getNoteTime(note.created_at) }}</div>
                        </div>
                        <button v-if="!isReadOnly" type="button" @click="emit('delete-note', note.item_id, note.id)"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl transition-colors sm:opacity-0 sm:group-hover:opacity-100 focus:opacity-100"
                            :title="$t('common.delete')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- GRID VIEW LAYOUT -->
                <template v-else>
                    <div class="flex flex-col h-full justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3 pb-3 border-b border-amber-200/30 dark:border-amber-900/10">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <img v-if="note.user?.photo_url" :src="note.user.photo_url" class="w-9 h-9 rounded-full object-cover">
                                        <div v-else class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                            {{ note.user?.name?.charAt(0) || 'U' }}
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate leading-tight">
                                            {{ note.user?.name || $t('common.system') }}
                                        </h4>
                                        <span class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider block">
                                            {{ getUserRoleName(note.user) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-[10px] text-gray-400 dark:text-gray-500 font-mono text-right font-bold leading-tight">
                                    <div>{{ getNoteDate(note.created_at) }}</div>
                                    <div class="text-gray-300 dark:text-gray-600">{{ getNoteTime(note.created_at) }}</div>
                                </div>
                            </div>

                            <button v-if="note.item_id" type="button" @click="emit('open-service-notes', note.item_id)"
                                class="text-xs font-semibold text-teal-600 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300 hover:underline text-right block mb-2">
                                {{ note.service_title_formatted }}
                            </button>
                            <p v-else class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">
                                {{ note.service_title_formatted }}
                            </p>

                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
                                {{ note.content }}
                            </p>
                        </div>

                        <div v-if="!isReadOnly" class="flex justify-end mt-4 pt-3 border-t border-amber-200/20 dark:border-amber-900/10">
                            <button type="button" @click="emit('delete-note', note.item_id, note.id)"
                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-colors"
                                :title="$t('common.delete')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <span class="text-2xl">📝</span>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.item.no_notes') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFormatters } from '@/Composables/useFormatters';

const props = defineProps({
    /** Raw notes from workOrder.generalNotes. Each note may carry an
     *  `item_id` and `service_title_formatted` if it was authored from a
     *  service item. */
    notes: { type: Array, default: () => [] },
    isReadOnly: { type: Boolean, default: false },
});

const emit = defineEmits([
    'open-service-notes',
    'delete-note',
    'open-add-note',
]);

// localStorage key for the user's preferred view mode. Wrapped in a
// try/catch because Safari private mode and quota-exceeded errors can
// throw — falling back to session-local storage is acceptable UX.
const NOTES_VIEW_MODE_KEY = 'work_orders_notes_view_mode';

function readViewMode() {
    try { return localStorage.getItem(NOTES_VIEW_MODE_KEY) || 'list'; }
    catch { return 'list'; }
}
function writeViewMode(value) {
    try { localStorage.setItem(NOTES_VIEW_MODE_KEY, value); }
    catch { /* ignore — storage unavailable */ }
}

const viewMode = ref(readViewMode());
watch(viewMode, (v) => writeViewMode(v));

const searchQuery = ref('');
const showAddNoteModal = ref(false);

const { t } = useI18n();
const { getUserRoleName, getNoteDate, getNoteTime } = useFormatters();

const filteredNotes = computed(() => {
    let list = props.notes;
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(note =>
            note.content.toLowerCase().includes(q) ||
            note.service_title_formatted.toLowerCase().includes(q) ||
            (note.user?.name && note.user.name.toLowerCase().includes(q))
        );
    }
    return list;
});
</script>
