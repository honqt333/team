<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    workOrder: {
        type: Object,
        required: true
    },
    readOnly: {
        type: Boolean,
        default: false
    }
});

const { t } = useI18n();
const { success, error: toastError } = useToast();

const items = ref([]);
const inspections = ref(props.workOrder.inspections || []);
const loading = ref(false);
const showNewInspectionModal = ref(false);
const inspectionForm = ref({
    results: []
});

const fetchItems = async () => {
    try {
        const response = await axios.get(route('work-orders.inspections.templates', props.workOrder.id));
        items.value = response.data;
    } catch (e) {
        console.error('Error fetching items', e);
    }
};

onMounted(() => {
    fetchItems();
});

const startInspection = () => {
    inspectionForm.value.results = items.value.map(item => ({
        item_id: item.id,
        status: 'good',
        notes: ''
    }));
    showNewInspectionModal.value = true;
};

const saveInspection = () => {
    loading.value = true;
    router.post(route('work-orders.inspections.store', props.workOrder.id), inspectionForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showNewInspectionModal.value = false;
            success(t('messages.inspection_saved'));
        },
        onFinish: () => {
            loading.value = false;
        }
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'good': return 'text-green-500';
        case 'warning': return 'text-yellow-500';
        case 'danger': return 'text-red-500';
        default: return 'text-gray-400';
    }
};

const getStatusLabel = (status) => {
    return t(`work_orders.systematic.result_${status}`);
};

const selectedInspection = ref(null);
const showViewModal = ref(false);

const viewInspection = (inspection) => {
    selectedInspection.value = inspection;
    showViewModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                {{ $t('work_orders.systematic.title') }}
            </h3>
            <div v-if="!readOnly" class="flex gap-2">
                <PrimaryButton @click="startInspection">
                    {{ $t('work_orders.systematic.start_inspection') }}
                </PrimaryButton>
            </div>
        </div>

        <!-- Inspections List -->
        <div v-if="workOrder.inspections?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
                v-for="inspection in workOrder.inspections" 
                :key="inspection.id"
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                @click="viewInspection(inspection)"
            >
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $t('work_orders.systematic.title') }}
                    </span>
                    <span class="text-[10px] text-gray-400 font-mono">
                        {{ new Date(inspection.performed_at).toLocaleDateString($i18n.locale) }}
                    </span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <span>👤 {{ inspection.performed_by?.name || inspection.performed_user?.name }}</span>
                </div>
                
                <div class="flex gap-2">
                    <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden flex">
                        <div 
                            v-for="status in ['good', 'warning', 'danger']" 
                            :key="status"
                            :style="{ width: (inspection.results.filter(r => r.status === status).length / (inspection.results.length || 1) * 100) + '%' }"
                            :class="{
                                'bg-green-500': status === 'good',
                                'bg-yellow-500': status === 'warning',
                                'bg-red-500': status === 'danger'
                            }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
            <div class="text-4xl mb-4">🔍</div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.systematic.no_inspections') }}</p>
        </div>

        <!-- New Inspection Modal -->
        <Modal :show="showNewInspectionModal" @close="showNewInspectionModal = false" max-width="4xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $t('work_orders.systematic.title') }}
                    </h2>
                    <button @click="showNewInspectionModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-8 max-h-[70vh] overflow-y-auto px-2">
                    <div v-for="(group, category) in Object.groupBy(items || [], i => i[`category_${$i18n.locale}`] || i.category_ar || 'Other')" :key="category">
                        <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                            {{ category }}
                        </h4>
                        <div class="space-y-4">
                            <div v-for="item in group" :key="item.id" class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-900 dark:text-white">{{ item[`name_${$i18n.locale}`] || item.name_ar }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label 
                                            v-for="status in ['good', 'warning', 'danger', 'na']" 
                                            :key="status"
                                            class="flex-1 md:flex-none"
                                        >
                                            <input 
                                                type="radio" 
                                                :name="'status-' + item.id" 
                                                v-model="inspectionForm.results.find(r => r.item_id === item.id).status" 
                                                :value="status"
                                                class="hidden peer"
                                            >
                                            <div class="cursor-pointer px-3 py-2 rounded-lg text-xs font-bold text-center transition-all border-2 border-transparent"
                                                :class="{
                                                    'bg-green-50 text-green-600 peer-checked:border-green-500 dark:bg-green-900/20 dark:text-green-400': status === 'good',
                                                    'bg-yellow-50 text-yellow-600 peer-checked:border-yellow-500 dark:bg-yellow-900/20 dark:text-yellow-400': status === 'warning',
                                                    'bg-red-50 text-red-600 peer-checked:border-red-500 dark:bg-red-900/20 dark:text-red-400': status === 'danger',
                                                    'bg-gray-100 text-gray-600 peer-checked:border-gray-400 dark:bg-gray-800 dark:text-gray-400': status === 'na'
                                                }"
                                            >
                                                {{ getStatusLabel(status) }}
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <textarea 
                                        v-model="inspectionForm.results.find(r => r.item_id === item.id).notes"
                                        class="w-full text-sm bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-lg focus:ring-indigo-500"
                                        rows="1"
                                        :placeholder="$t('common.notes')"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <SecondaryButton @click="showNewInspectionModal = false">{{ $t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton @click="saveInspection" :loading="loading">{{ $t('work_orders.systematic.save_inspection') }}</PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- View Inspection Modal -->
        <Modal :show="showViewModal" @close="showViewModal = false" max-width="4xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $t('work_orders.systematic.title') }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $t('work_orders.systematic.performed_by') }}: {{ selectedInspection?.performed_by?.name || selectedInspection?.performed_user?.name }} 
                            • {{ $t('work_orders.systematic.performed_at') }}: {{ new Date(selectedInspection?.performed_at).toLocaleString($i18n.locale) }}
                        </p>
                    </div>
                    <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-6 max-h-[70vh] overflow-y-auto px-2">
                    <div v-for="result in selectedInspection?.results" :key="result.item_id" class="flex items-start justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-800">
                        <div class="flex-1">
                            <p class="font-bold text-gray-900 dark:text-white">
                                {{ selectedInspection.items?.find(i => i.id === result.item_id)?.[`name_${$i18n.locale}`] || selectedInspection.items?.find(i => i.id === result.item_id)?.name_ar }}
                            </p>
                            <p v-if="result.notes" class="text-sm text-gray-600 dark:text-gray-400 mt-1 italic">"{{ result.notes }}"</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span :class="getStatusColor(result.status)" class="text-xs font-bold uppercase tracking-wider">
                                {{ getStatusLabel(result.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <SecondaryButton @click="showViewModal = false">{{ $t('common.close') }}</SecondaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
