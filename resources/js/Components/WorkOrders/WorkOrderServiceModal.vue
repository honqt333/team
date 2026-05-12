<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                {{ item ? getName(item.service) : $t('work_orders.show.add_service') }}
            </div>
        </template>

        <!-- Tabs -->
        <div class="mb-6 p-1.5 bg-gray-100/80 dark:bg-gray-800/80 rounded-2xl flex gap-1.5 overflow-x-auto no-scrollbar">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'flex-1 py-2.5 px-4 text-sm font-bold rounded-xl transition-all duration-300 whitespace-nowrap flex items-center justify-center gap-2',
                    activeTab === tab.id
                        ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-200/50 dark:hover:bg-gray-700/50'
                ]"
            >
                {{ tab.name }}
                <span v-if="tab.id === 'parts' && (linkedParts.length > 0 || pendingParts.length > 0)" class="ms-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800">
                    {{ linkedParts.length + pendingParts.length }}
                </span>
                <span v-if="tab.id === 'technicians' && (localTechnicians.length > 0 || pendingTechnicians.length > 0)" class="ms-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800">
                    {{ localTechnicians.length + pendingTechnicians.length }}
                </span>
                <span v-if="tab.id === 'notes' && (localNotes.length > 0 || pendingNotes.length > 0)" class="ms-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800">
                    {{ localNotes.length + pendingNotes.length }}
                </span>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="min-h-[300px]">
            <!-- Service Tab -->
            <div v-show="activeTab === 'service'" class="space-y-4">
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Service Select (Read-only for edit, editable for add) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('quotes.service_modal.service') }}
                        </label>
                        <SearchableSelect
                            v-if="!item && !props.readOnly"
                            v-model="form.service_id"
                            :options="serviceOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('common.choose')"
                            class="w-full"
                        />
                        <div 
                            v-else 
                            class="px-4 py-3 bg-gray-100 dark:bg-gray-900 rounded-xl text-gray-900 dark:text-white"
                        >
                            {{ getName(item?.service) }}
                        </div>
                    </div>

                    <!-- Title/Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('quotes.service_modal.description') }}
                        </label>
                        <textarea
                            v-model="form.title"
                            rows="2"
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                            :placeholder="$t('quotes.service_modal.description_placeholder')"
                        ></textarea>
                    </div>

                    <!-- Price & Quantity Row -->
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Price -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $t('work_orders.item.price') }}
                                </label>
                            </div>
                            <div class="relative">
                                <input type="text" inputmode="decimal" v-model="form.unit_price" dir="ltr"
                                    @input="form.unit_price = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                                    :disabled="isPriceLocked || props.readOnly" :class="[
                                        'w-full py-3 pl-4 pr-16 border rounded-xl font-mono text-right focus:ring-2 focus:border-indigo-500',
                                        isPriceLocked || props.readOnly
                                            ? 'bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border-gray-200 dark:border-gray-700'
                                            : isPriceBelowMinimum
                                                ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-red-300 dark:border-red-700 focus:ring-red-500'
                                                : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-200 dark:border-gray-700 focus:ring-indigo-500'
                                    ]" required />
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                    <span class="text-gray-500">{{ $t('common.currency') }}</span>
                                </div>
                            </div>
                            <!-- Min price warning -->
                            <p v-if="isPriceBelowMinimum"
                                class="mt-1 text-[11px] text-red-600 dark:text-red-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ $t('quotes.min_price_warning', { min: formatCurrency(selectedServiceMinPrice) }) }}
                            </p>
                        </div>
                    </div>

                    <!-- Discount Section -->
                    <div v-if="!props.readOnly" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('quotes.service_modal.discount_method') }}
                            </label>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="form.discount_type = 'none'"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                                        form.discount_type === 'none'
                                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    {{ $t('quotes.service_modal.no_discount') }}
                                </button>
                                <button
                                    type="button"
                                    @click="form.discount_type = 'fixed'"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                                        form.discount_type === 'fixed'
                                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    {{ $t('quotes.service_modal.fixed') }}
                                </button>
                                <button
                                    type="button"
                                    @click="form.discount_type = 'percentage'"
                                    :class="[
                                        'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                                        form.discount_type === 'percentage'
                                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    %
                                </button>
                            </div>
                        </div>

                        <!-- Discount Value -->
                        <div v-if="form.discount_type !== 'none'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('quotes.service_modal.discount_value') }}
                            </label>
                            <input type="text" inputmode="decimal" v-model="form.discount_value" dir="ltr"
                                @input="form.discount_value = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                                :class="[
                                'w-full px-4 py-3 border rounded-xl font-mono text-end focus:ring-2',
                                isPriceBelowMinimum
                                    ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 focus:ring-red-500 focus:border-red-500'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 focus:ring-indigo-500 focus:border-indigo-500'
                            ]" />

                            <!-- Max allowed discount hint -->
                            <p v-if="selectedServiceMinPrice > 0" :class="[
                                'mt-1 text-[11px] flex items-center gap-1',
                                isPriceBelowMinimum ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'
                            ]">
                                <svg v-if="isPriceBelowMinimum" class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span v-if="form.discount_type === 'fixed'">
                                    {{ $t('quotes.max_discount_fixed', { max: formatCurrency(maxAllowedFixedDiscount) }) }}
                                </span>
                                <span v-else-if="form.discount_type === 'percentage'">
                                    {{ $t('quotes.max_discount_percentage', { max: maxAllowedPercentageDiscount.toFixed(1) }) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Calculated Total -->
                    <div :class="[
                        'p-4 rounded-xl border',
                        isPriceBelowMinimum
                            ? 'bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-900/20 border-red-300 dark:border-red-700'
                            : 'bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border-indigo-200 dark:border-indigo-800'
                    ]">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $t('quotes.service_modal.total_cost') }}
                            </span>
                            <div class="flex items-baseline gap-1.5 whitespace-nowrap">
                                <span :class="[
                                    'text-2xl font-black font-mono',
                                    isPriceBelowMinimum ? 'text-red-600 dark:text-red-400' : 'text-indigo-600 dark:text-indigo-400'
                                ]">
                                    {{ formatCurrency(calculatedTotal) }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('common.currency') }}
                                </span>
                            </div>
                        </div>
                        <!-- Min price warning - more visible here -->
                        <div v-if="isPriceBelowMinimum"
                            class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="font-medium">
                                {{ $t('quotes.min_price_warning', { min: formatCurrency(selectedServiceMinPrice) }) }}
                            </span>
                        </div>
                    </div>

                    <!-- Status Selection for Edit mode -->
                    <div v-if="item && !props.readOnly" class="mt-4">
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
                                    'px-4 py-2 text-sm rounded-lg transition-all flex items-center gap-2 border',
                                    form.status === status.value
                                        ? status.activeClass
                                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                ]"
                            >
                                <span v-html="status.icon"></span>
                                {{ status.label }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Parts Tab -->
            <div v-show="activeTab === 'parts'" class="space-y-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                        {{ $t('quotes.show.tabs.linked_parts') }}
                    </h4>
                    <button v-if="!isReadOnly && !props.readOnly" type="button" @click="openPartModal()"
                        class="px-3 py-1.5 text-xs bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all flex items-center gap-1 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('work_orders.item.add_part') }}
                    </button>
                </div>
                <PartsDisplay 
                    :parts="allParts" 
                    :read-only="props.readOnly" 
                    :show-vat="workOrder.is_taxed" 
                    :show-service="false"
                    :compact-grid="true" 
                    :pending-check="part => !part.id" 
                    storage-key="workorder_service_modal_parts_view"
                    :empty-message="$t('work_orders.item.no_parts')" 
                    :add-button-text="$t('work_orders.item.add_part')"
                    @edit="handlePartEdit" 
                    @delete="handlePartDelete" 
                    @add="openPartModal()" 
                />

                <!-- Parts Total -->
                <div v-if="allParts.length > 0"
                    class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <span class="text-sm font-medium text-green-700 dark:text-green-300">
                        {{ $t('work_orders.item.parts_cost') }}
                    </span>
                    <span class="font-bold text-green-600 dark:text-green-400 font-mono" dir="ltr">
                        {{ formatCurrency(partsTotalCost) }} {{ $t('common.currency') }}
                    </span>
                </div>
            </div>

            <!-- Part Modal -->
            <WorkOrderPartModal 
                :show="showLinkedPartModal" 
                :workOrder="workOrder" 
                :part="editingLinkedPart" 
                :workOrderItemId="item?.id"
                :units="inventoryUnits" 
                :warehouses="warehouses"
                :show-service-select="false" 
                :show-toggles="true" 
                :pending-mode="!item"
                @close="closePartModal" 
                @saved="onPartSaved" 
            />

            <!-- Technicians Tab -->
            <div v-show="activeTab === 'technicians'" class="space-y-4">
                <div v-if="!props.readOnly" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('work_orders.item.assign_technician') }}</h4>
                    <div class="flex gap-2">
                        <SearchableSelect
                            v-model="technicianForm.user_id"
                            :options="availableTechnicians"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('common.choose')"
                            class="flex-1"
                        />
                        <button type="button" @click="assignTechnician" :disabled="techniciansLoading || !technicianForm.user_id" class="px-4 py-2 bg-indigo-500 text-white text-sm rounded-lg hover:bg-indigo-600 disabled:opacity-50 transition-colors">
                            {{ $t('common.add') }}
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <div v-for="tech in (item ? localTechnicians : pendingTechnicians)" :key="tech.id || tech.user_id" class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-900 dark:text-white">{{ tech.name }}</span>
                        <button v-if="!props.readOnly" type="button" @click="removeTechnician(tech)" class="text-red-500 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notes Tab -->
            <div v-show="activeTab === 'notes'" class="space-y-4">
                <!-- Add Note -->
                <div v-if="!props.readOnly" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
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
                        v-for="note in (item ? localNotes : pendingNotes)"
                        :key="note.id || note.created_at"
                        class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ note.content }}</div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ note.user?.name }} {{ note.created_at ? '• ' + formatDate(note.created_at) : '' }}
                                </div>
                            </div>
                            <button
                                v-if="!props.readOnly"
                                type="button"
                                @click="removeNote(note)"
                                class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
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
                v-if="!props.readOnly"
                type="button"
                @click="submitForm"
                :disabled="saving || isPriceBelowMinimum"
                class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 transition-all"
            >
                {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import PartsDisplay from '@/Components/Common/PartsDisplay.vue';
import WorkOrderPartModal from '@/Components/WorkOrders/WorkOrderPartModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { success: toastSuccess } = useToast();

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    item: Object,
    departmentId: Number,
    services: Array,
    technicians: { type: Array, default: () => [] },
    inventoryUnits: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency, toEnglish } = useNumberFormat();

// State
const activeTab = ref('service');
const saving = ref(false);
const techniciansLoading = ref(false);
const notesLoading = ref(false);
const isEdit = computed(() => !!props.item);

// Local data
const linkedParts = ref([]);
const pendingParts = ref([]);
const localTechnicians = ref([]);
const pendingTechnicians = ref([]);
const localNotes = ref([]);
const pendingNotes = ref([]);

// Modals
const showLinkedPartModal = ref(false);
const editingLinkedPart = ref(null);

// Form
const form = useForm({
    service_id: props.item?.service_id || '',
    title: props.item?.title || '',
    qty: props.item?.qty || 1,
    unit_price: props.item?.unit_price || 0,
    discount_type: props.item?.discount_type || 'none',
    discount_value: props.item?.discount_value || 0,
    status: props.item?.status || 'pending',
});

const technicianForm = ref({ user_id: '' });
const noteForm = ref({ content: '' });

// Computed
const allParts = computed(() => [...linkedParts.value, ...pendingParts.value]);
const partsTotalCost = computed(() => allParts.value.reduce((sum, p) => sum + parseFloat(p.total || calculatePartTotal(p)), 0));

const tabs = computed(() => [
    { id: 'service', name: t('work_orders.item.tab_service') },
    { id: 'parts', name: t('work_orders.item.tab_parts') },
    { id: 'technicians', name: t('work_orders.item.tab_technicians') },
    { id: 'notes', name: t('work_orders.item.tab_notes') },
]);

const statuses = computed(() => [
    { value: 'pending', label: t('work_orders.item.status_pending'), icon: '⏳', activeClass: 'bg-gray-500 border-gray-600 text-white' },
    { value: 'in_progress', label: t('work_orders.item.status_in_progress'), icon: '🔧', activeClass: 'bg-blue-500 border-blue-600 text-white' },
    { value: 'completed', label: t('work_orders.item.status_completed'), icon: '✅', activeClass: 'bg-green-500 border-green-600 text-white' },
    { value: 'on_hold', label: t('work_orders.item.status_on_hold'), icon: '⏸️', activeClass: 'bg-amber-500 border-amber-600 text-white' },
    { value: 'cancelled', label: t('work_orders.item.status_cancelled'), icon: '❌', activeClass: 'bg-red-500 border-red-600 text-white' },
]);

const availableTechnicians = computed(() => {
    const assignedIds = (props.item ? localTechnicians.value : pendingTechnicians.value).map(t => t.id || t.user_id);
    return props.technicians.filter(t => !assignedIds.includes(t.id));
});

const calculatedTotal = computed(() => {
    const price = effectivePrice.value;
    const qty = 1; // Always 1
    let discount = 0;

    if (form.discount_type === 'fixed') {
        discount = parseFloat(toEnglish(form.discount_value)) || 0;
    } else if (form.discount_type === 'percentage') {
        discount = (price * (parseFloat(toEnglish(form.discount_value)) || 0)) / 100;
    }

    return Math.max(0, (price - discount) * qty);
});

// Get the currently selected service
const selectedService = computed(() => {
    // In edit mode, use the line's service directly
    if (props.item?.service) {
        return props.item.service;
    }
    // In add mode, find from services list
    if (!form.service_id) return null;
    const serviceId = parseInt(form.service_id);
    return props.services?.find(s => s.id === serviceId) || null;
});

// Check if price is locked (allow_price_override = false)
const isPriceLocked = computed(() => {
    if (!selectedService.value) return false;
    return selectedService.value.allow_price_override === false;
});

// Get min price for selected service
const selectedServiceMinPrice = computed(() => {
    if (!selectedService.value) return 0;
    return parseFloat(selectedService.value.min_price) || 0;
});

// Calculate the effective price (base_price for locked, unit_price for unlocked)
const effectivePrice = computed(() => {
    if (isPriceLocked.value && selectedService.value) {
        return parseFloat(selectedService.value.base_price) || 0;
    }
    return parseFloat(toEnglish(form.unit_price)) || 0;
});

// Max allowed fixed discount (price - min_price)
const maxAllowedFixedDiscount = computed(() => {
    const minPrice = selectedServiceMinPrice.value;
    if (minPrice <= 0) return effectivePrice.value; // No limit
    return Math.max(0, effectivePrice.value - minPrice);
});

// Max allowed percentage discount ((price - min_price) / price * 100)
const maxAllowedPercentageDiscount = computed(() => {
    const minPrice = selectedServiceMinPrice.value;
    const price = effectivePrice.value;
    if (minPrice <= 0 || price <= 0) return 100; // No limit
    return Math.max(0, ((price - minPrice) / price) * 100);
});

// Check if FINAL price (after discount) is below minimum
const isPriceBelowMinimum = computed(() => {
    if (!selectedService.value) return false;
    const minPrice = selectedServiceMinPrice.value;
    if (minPrice <= 0) return false;

    // Calculate the final total using effective price
    const price = effectivePrice.value;
    let discount = 0;
    if (form.discount_type === 'fixed') {
        discount = parseFloat(toEnglish(form.discount_value)) || 0;
    } else if (form.discount_type === 'percentage') {
        discount = (price * (parseFloat(toEnglish(form.discount_value)) || 0)) / 100;
    }
    const finalPrice = Math.max(0, price - discount);

    return finalPrice < minPrice;
});

const serviceOptions = computed(() => {
    let filteredServices = props.services;
    if (props.departmentId) {
        filteredServices = props.services.filter(s => s.department_id === props.departmentId);
    }
    return filteredServices.map(s => ({
        value: s.id,
        label: getName(s)
    }));
});

// Methods
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
}

function calculatePartTotal(part) {
    const qty = parseFloat(part.qty) || 0;
    const price = parseFloat(part.unit_price) || 0;
    const discount = parseFloat(part.discount) || 0;
    return Math.max(0, (qty * price) - discount);
}

function openPartModal(part = null) {
    editingLinkedPart.value = part;
    showLinkedPartModal.value = true;
}

function closePartModal() {
    showLinkedPartModal.value = false;
    setTimeout(() => { editingLinkedPart.value = null; }, 300);
}

function onPartSaved(savedPart, options = {}) {
    if (savedPart.isPending || !props.item) {
        if (savedPart.tempId) {
            const index = pendingParts.value.findIndex(p => p.tempId === savedPart.tempId);
            if (index !== -1) {
                pendingParts.value[index] = { ...savedPart, total: calculatePartTotal(savedPart) };
            } else {
                pendingParts.value.push({ ...savedPart, total: calculatePartTotal(savedPart) });
            }
        } else {
            const newTempId = Date.now();
            pendingParts.value.push({ ...savedPart, total: calculatePartTotal(savedPart), tempId: newTempId });
        }
    } else {
        if (editingLinkedPart.value && editingLinkedPart.value.id) {
            const index = linkedParts.value.findIndex(p => p.id === savedPart.id);
            if (index !== -1) linkedParts.value[index] = savedPart;
        } else {
            linkedParts.value.push(savedPart);
        }
        // In edit mode, we should refresh from backend to get fresh data
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    }
    
    toastSuccess(t('common.saved_success'));
    
    if (options.close !== false) {
        closePartModal();
    }
}

function handlePartEdit(part) { openPartModal(part); }

function handlePartDelete(part) {
    if (!part.id) {
        pendingParts.value = pendingParts.value.filter(p => p.tempId !== part.tempId);
        return;
    }
    if (confirm(t('common.confirm_delete'))) {
        router.delete(route('work-orders.parts.destroy', { workOrderPart: part.id }), {
            onSuccess: () => { 
                linkedParts.value = linkedParts.value.filter(p => p.id !== part.id);
                router.reload({ only: ['workOrder', 'itemsByDepartment'] });
            }
        });
    }
}

function changeStatus(newStatus) { form.status = newStatus; }

async function assignTechnician() {
    if (!technicianForm.value.user_id) return;
    if (!props.item) {
        const tech = props.technicians.find(t => t.id == technicianForm.value.user_id);
        if (tech && !pendingTechnicians.value.find(t => t.user_id == tech.id)) {
            pendingTechnicians.value.push({ user_id: tech.id, name: tech.name });
        }
        technicianForm.value.user_id = '';
        return;
    }
    techniciansLoading.value = true;
    try {
        const response = await axios.post(route('work-orders.items.technicians.store', { work_order: props.workOrder.id, item: props.item.id }), technicianForm.value);
        localTechnicians.value = response.data.technicians || [];
        technicianForm.value.user_id = '';
    } finally { techniciansLoading.value = false; }
}

async function removeTechnician(tech) {
    if (!props.item) {
        pendingTechnicians.value = pendingTechnicians.value.filter(t => t.user_id !== tech.user_id);
        return;
    }
    techniciansLoading.value = true;
    try {
        await axios.delete(route('work-orders.items.technicians.destroy', { work_order: props.workOrder.id, item: props.item.id, user: tech.id }));
        localTechnicians.value = localTechnicians.value.filter(t => t.id !== tech.id);
    } finally { techniciansLoading.value = false; }
}

async function addNote() {
    if (!noteForm.value.content) return;
    if (!props.item) {
        pendingNotes.value.push({ content: noteForm.value.content, created_at: new Date().toISOString() });
        noteForm.value.content = '';
        return;
    }
    notesLoading.value = true;
    try {
        const response = await axios.post(route('work-orders.items.notes.store', { work_order: props.workOrder.id, item: props.item.id }), noteForm.value);
        localNotes.value = response.data.notes || [];
        noteForm.value.content = '';
    } finally { notesLoading.value = false; }
}

async function removeNote(note) {
    if (!props.item) {
        pendingNotes.value = pendingNotes.value.filter(n => n !== note);
        return;
    }
    notesLoading.value = true;
    try {
        await axios.delete(route('work-orders.items.notes.destroy', { work_order: props.workOrder.id, item: props.item.id, note: note.id }));
        localNotes.value = localNotes.value.filter(n => n.id !== note.id);
    } finally { notesLoading.value = false; }
}

function submitForm() {
    if (saving.value) return;
    saving.value = true;
    
    // Normalize data before sending
    form.qty = toEnglish(form.qty);
    form.unit_price = toEnglish(form.unit_price);
    form.discount_value = toEnglish(form.discount_value);

    if (isPriceBelowMinimum.value) {
        return;
    }

    const formData = {
        ...form.data(),
        pending_parts: pendingParts.value,
        pending_technicians: pendingTechnicians.value,
        pending_notes: pendingNotes.value
    };

    if (props.item) {
        // Update existing item
        router.put(route('work-orders.items.update', { work_order: props.workOrder.id, item: props.item.id }), formData, {
            onSuccess: () => {
                emit('saved');
                emit('close');
            },
            onFinish: () => {
                saving.value = false;
            }
        });
    } else {
        // Add new item
        router.post(route('work-orders.items.store', { work_order: props.workOrder.id }), formData, {
            onSuccess: () => {
                emit('saved');
                emit('close');
            },
            onFinish: () => {
                saving.value = false;
            }
        });
    }
}

// Watch for service selection to auto-fill price
watch(() => form.service_id, (serviceId) => {
    if (serviceId && !props.item) {
        const service = props.services.find(s => s.id === serviceId);
        if (service) {
            form.unit_price = service.base_price || 0;
            form.title = getName(service);
        }
    }
});

// Watch for item changes to sync local data
watch(() => props.item, (newItem) => {
    if (newItem) {
        form.service_id = newItem.service_id;
        form.title = newItem.title;
        form.qty = newItem.qty;
        form.unit_price = newItem.unit_price;
        form.discount_type = newItem.discount_type || 'none';
        form.discount_value = newItem.discount_value || 0;
        form.status = newItem.status || 'pending';
        
        linkedParts.value = newItem.parts || [];
        localTechnicians.value = newItem.technicians || [];
        localNotes.value = newItem.item_notes || [];
    }
}, { deep: true });

// Reset form when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        activeTab.value = 'service';
        if (props.item) {
            form.service_id = props.item.service_id;
            form.title = props.item.title;
            form.qty = props.item.qty;
            form.unit_price = props.item.unit_price;
            form.discount_type = props.item.discount_type || 'none';
            form.discount_value = props.item.discount_value || 0;
            form.status = props.item.status || 'pending';
            
            linkedParts.value = props.item.parts || [];
            pendingParts.value = [];
            localTechnicians.value = props.item.technicians || [];
            localNotes.value = props.item.item_notes || [];
        } else {
            form.reset();
            form.qty = 1;
            linkedParts.value = [];
            pendingParts.value = [];
            localTechnicians.value = [];
            localNotes.value = [];
        }
    }
}, { immediate: true });
</script>
