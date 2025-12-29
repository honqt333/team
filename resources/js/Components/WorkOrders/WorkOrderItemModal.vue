<template>
    <BaseModal :show="show" @close="$emit('close')" size="xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span>{{ item?.service?.name || item?.title }}</span>
            </div>
        </template>

        <!-- Tabs -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-4 -mb-px" aria-label="Tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'py-3 px-4 text-sm font-medium border-b-2 transition-all',
                        activeTab === tab.id
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                    ]"
                >
                    {{ tab.name }}
                    <span v-if="tab.count > 0" class="ms-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800">
                        {{ tab.count }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="min-h-[300px]">
            <!-- Service Tab -->
            <div v-show="activeTab === 'service'" class="space-y-4">
                <!-- Service Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('work_orders.item.description') }}
                    </label>
                    <textarea
                        v-model="form.title"
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                        :placeholder="$t('work_orders.item.description_placeholder')"
                    ></textarea>
                </div>

                <!-- Price Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('work_orders.item.price') }}
                        </label>
                        <input
                            type="number"
                            v-model="form.unit_price"
                            step="0.01"
                            min="0"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('work_orders.item.discount') }}
                        </label>
                        <input
                            type="number"
                            v-model="form.discount_value"
                            step="0.01"
                            min="0"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                </div>

                <!-- Total -->
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('work_orders.item.total_cost') }}
                    </span>
                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ formatPrice(item?.line_total || 0) }}
                    </span>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $t('work_orders.item.status') }}
                    </label>
                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="status in statuses"
                            :key="status.value"
                            type="button"
                            @click="changeStatus(status.value)"
                                :class="[
                                'px-4 py-2 text-sm rounded-lg transition-all flex items-center gap-2',
                                form.status === status.value
                                    ? status.activeClass
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                            ]"
                        >
                            <span v-html="status.icon"></span>
                            {{ status.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Parts Tab -->
            <div v-show="activeTab === 'parts'" class="space-y-4">
                <!-- Add Part Form -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('work_orders.item.add_part') }}</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <input
                            v-model="partForm.name"
                            type="text"
                            :placeholder="$t('work_orders.item.part_name')"
                            class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        />
                        <select
                            v-model="partForm.source"
                            class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        >
                            <option value="warehouse">{{ $t('work_orders.item.part_source.warehouse') }}</option>
                            <option value="external">{{ $t('work_orders.item.part_source.external') }}</option>
                            <option value="customer">{{ $t('work_orders.item.part_source.customer') }}</option>
                        </select>
                        <input
                            v-model="partForm.qty"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :placeholder="$t('work_orders.item.qty')"
                            class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        />
                        <input
                            v-model="partForm.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            :placeholder="$t('work_orders.item.price')"
                            class="px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        />
                    </div>
                    <button
                        type="button"
                        @click="addPart"
                        :disabled="partsLoading || !partForm.name"
                        class="mt-3 px-4 py-2 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 disabled:opacity-50 transition-colors"
                    >
                        {{ $t('common.add') }}
                    </button>
                </div>

                <!-- Parts List -->
                <div class="space-y-2">
                    <div
                        v-for="part in localParts"
                        :key="part.id"
                        class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex-1">
                            <span class="font-medium text-gray-900 dark:text-white">{{ part.name }}</span>
                            <span class="ms-2 text-xs px-2 py-0.5 rounded-full" :class="getSourceBadgeClass(part.source)">
                                {{ $t(`work_orders.item.part_source.${part.source}`) }}
                            </span>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ part.qty }} × {{ formatPrice(part.unit_price) }} = {{ formatPrice(part.total) }}
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="deletePart(part)"
                            class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <div v-if="localParts.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        {{ $t('work_orders.item.no_parts') }}
                    </div>
                </div>
            </div>

            <!-- Technicians Tab -->
            <div v-show="activeTab === 'technicians'" class="space-y-4">
                <!-- Add Technician -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('work_orders.item.assign_technician') }}</h4>
                    <div class="flex gap-3">
                        <select
                            v-model="technicianForm.user_id"
                            class="flex-1 px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        >
                            <option value="">{{ $t('common.choose') }}</option>
                            <option v-for="tech in availableTechnicians" :key="tech.id" :value="tech.id">
                                {{ tech.name }}
                            </option>
                        </select>
                        <button
                            type="button"
                            @click="assignTechnician"
                            :disabled="techniciansLoading || !technicianForm.user_id"
                            class="px-4 py-2 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 disabled:opacity-50 transition-colors"
                        >
                            {{ $t('common.add') }}
                        </button>
                    </div>
                </div>

                <!-- Technicians List -->
                <div class="space-y-2">
                    <div
                        v-for="tech in localTechnicians"
                        :key="tech.id"
                        class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-medium">
                                {{ tech.name?.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ tech.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $t('work_orders.item.assigned_at') }}: {{ formatDate(tech.pivot?.assigned_at) }}
                                </div>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="removeTechnician(tech)"
                            class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div v-if="localTechnicians.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        {{ $t('work_orders.item.no_technicians') }}
                    </div>
                </div>
            </div>

            <!-- Notes Tab -->
            <div v-show="activeTab === 'notes'" class="space-y-4">
                <!-- Add Note -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('work_orders.item.add_note') }}</h4>
                    <textarea
                        v-model="noteForm.content"
                        rows="3"
                        :placeholder="$t('work_orders.item.note_placeholder')"
                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm resize-none"
                    ></textarea>
                    <button
                        type="button"
                        @click="addNote"
                        :disabled="notesLoading || !noteForm.content"
                        class="mt-3 px-4 py-2 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 disabled:opacity-50 transition-colors"
                    >
                        {{ $t('common.add') }}
                    </button>
                </div>

                <!-- Notes List -->
                <div class="space-y-2">
                    <div
                        v-for="note in localNotes"
                        :key="note.id"
                        class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ note.content }}</div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ note.user?.name }} • {{ formatDate(note.created_at) }}
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="deleteNote(note)"
                                class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div v-if="localNotes.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        {{ $t('work_orders.item.no_notes') }}
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                {{ $t('common.close') }}
            </button>
            <button
                type="button"
                @click="saveService"
                :disabled="saving"
                class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 transition-all"
            >
                {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    item: Object,
    technicians: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { formatCurrency } = useNumberFormat();

// State
const activeTab = ref('service');
const saving = ref(false);
// const statusLoading = ref(false); // Removed
const partsLoading = ref(false);
const techniciansLoading = ref(false);
const notesLoading = ref(false);

// Local data (to show changes immediately)
const localParts = ref([]);
const localTechnicians = ref([]);
const localNotes = ref([]);

// Forms
const form = ref({
    title: '',
    unit_price: 0,
    discount_value: 0,
    discount_type: 'none',
});

const partForm = ref({
    name: '',
    source: 'external',
    qty: 1,
    unit_price: 0,
});

const technicianForm = ref({
    user_id: '',
});

const noteForm = ref({
    content: '',
});

// Computed
const tabs = computed(() => [
    { id: 'service', name: t('work_orders.item.tab_service'), count: 0 },
    { id: 'parts', name: t('work_orders.item.tab_parts'), count: localParts.value.length },
    { id: 'technicians', name: t('work_orders.item.tab_technicians'), count: localTechnicians.value.length },
    { id: 'notes', name: t('work_orders.item.tab_notes'), count: localNotes.value.length },
]);

const statuses = computed(() => [
    { value: 'pending', label: t('work_orders.item.status_pending'), icon: '⏳', activeClass: 'bg-gray-500 text-white' },
    { value: 'in_progress', label: t('work_orders.item.status_in_progress'), icon: '🔧', activeClass: 'bg-blue-500 text-white' },
    { value: 'completed', label: t('work_orders.item.status_completed'), icon: '✅', activeClass: 'bg-green-500 text-white' },
    { value: 'on_hold', label: t('work_orders.item.status_on_hold'), icon: '⏸️', activeClass: 'bg-yellow-500 text-white' },
    { value: 'cancelled', label: t('work_orders.item.status_cancelled'), icon: '❌', activeClass: 'bg-red-500 text-white' },
]);

const availableTechnicians = computed(() => {
    const assignedIds = localTechnicians.value.map(t => t.id);
    return props.technicians.filter(t => !assignedIds.includes(t.id));
});

// Methods
function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
}

function getSourceBadgeClass(source) {
    const classes = {
        warehouse: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        external: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        customer: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return classes[source] || classes.external;
}

function changeStatus(newStatus) {
    form.value.status = newStatus;
}

async function addPart() {
    if (!partForm.value.name || partsLoading.value) return;
    
    partsLoading.value = true;
    try {
        const response = await axios.post(route('work-orders.items.parts.store', {
            work_order: props.workOrder.id,
            item: props.item.id
        }), partForm.value);
        
        localParts.value.push(response.data.part);
        partForm.value = { name: '', source: 'external', qty: 1, unit_price: 0 };
        emit('saved');
    } catch (error) {
        console.error('Add part failed:', error);
    } finally {
        partsLoading.value = false;
    }
}

async function deletePart(part) {
    if (partsLoading.value) return;
    
    partsLoading.value = true;
    try {
        await axios.delete(route('work-orders.items.parts.destroy', {
            work_order: props.workOrder.id,
            item: props.item.id,
            part: part.id
        }));
        
        localParts.value = localParts.value.filter(p => p.id !== part.id);
        emit('saved');
    } catch (error) {
        console.error('Delete part failed:', error);
    } finally {
        partsLoading.value = false;
    }
}

async function assignTechnician() {
    if (!technicianForm.value.user_id || techniciansLoading.value) return;
    
    techniciansLoading.value = true;
    try {
        const response = await axios.post(route('work-orders.items.technicians.store', {
            work_order: props.workOrder.id,
            item: props.item.id
        }), technicianForm.value);
        
        localTechnicians.value = response.data.technicians || [];
        technicianForm.value.user_id = '';
        emit('saved');
    } catch (error) {
        console.error('Assign technician failed:', error);
    } finally {
        techniciansLoading.value = false;
    }
}

async function removeTechnician(tech) {
    if (techniciansLoading.value) return;
    
    techniciansLoading.value = true;
    try {
        await axios.delete(route('work-orders.items.technicians.destroy', {
            work_order: props.workOrder.id,
            item: props.item.id,
            user: tech.id
        }));
        
        localTechnicians.value = localTechnicians.value.filter(t => t.id !== tech.id);
        emit('saved');
    } catch (error) {
        console.error('Remove technician failed:', error);
    } finally {
        techniciansLoading.value = false;
    }
}

async function addNote() {
    if (!noteForm.value.content || notesLoading.value) return;
    
    notesLoading.value = true;
    try {
        const response = await axios.post(route('work-orders.items.notes.store', {
            work_order: props.workOrder.id,
            item: props.item.id
        }), noteForm.value);
        
        localNotes.value.unshift(response.data.note);
        noteForm.value.content = '';
        emit('saved');
    } catch (error) {
        console.error('Add note failed:', error);
    } finally {
        notesLoading.value = false;
    }
}

async function deleteNote(note) {
    if (notesLoading.value) return;
    
    notesLoading.value = true;
    try {
        await axios.delete(route('work-orders.items.notes.destroy', {
            work_order: props.workOrder.id,
            item: props.item.id,
            note: note.id
        }));
        
        localNotes.value = localNotes.value.filter(n => n.id !== note.id);
        emit('saved');
    } catch (error) {
        console.error('Delete note failed:', error);
    } finally {
        notesLoading.value = false;
    }
}

function saveService() {
    if (!props.item || saving.value) return;
    
    console.log('Saving item...', form.value);
    saving.value = true;
    router.put(route('work-orders.items.update', { 
        work_order: props.workOrder.id, 
        item: props.item.id 
    }), form.value, {
        onSuccess: () => {
            emit('saved');
            emit('close');
        },
        onFinish: () => {
            saving.value = false;
        }
    });
}

// Initialize data when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen && props.item) {
        activeTab.value = 'service';
        form.value = {
            title: props.item.title || '',
            unit_price: props.item.unit_price || 0,
            discount_value: props.item.discount_value || 0,
            discount_type: props.item.discount_type || 'none',
            status: props.item.status || 'pending',
        };
        localParts.value = props.item.parts || [];
        localTechnicians.value = props.item.technicians || [];
        localNotes.value = props.item.item_notes || [];
    }
}, { immediate: true });
</script>
