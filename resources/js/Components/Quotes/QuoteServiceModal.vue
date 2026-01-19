<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                {{ line ? getName(line.service) : $t('quotes.show.add_service') }}
            </div>
        </template>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
            <nav class="flex gap-4 -mb-px">
                <button @click="activeTab = 'service'" :class="[
                    'pb-2 text-sm font-medium border-b-2 transition-colors',
                    activeTab === 'service'
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                ]">
                    {{ $t('quotes.show.tabs.service_info') }}
                </button>
                <!-- Parts tab always visible -->
                <button @click="activeTab = 'parts'" :class="[
                    'pb-2 text-sm font-medium border-b-2 transition-colors',
                    activeTab === 'parts'
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                ]">
                    {{ $t('quotes.show.tabs.linked_parts') }}
                    <span v-if="allParts.length > 0"
                        class="ms-1 px-1.5 py-0.5 text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                        {{ allParts.length }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Service Tab -->
        <form v-show="activeTab === 'service'" @submit.prevent="submitForm" class="space-y-4">
            <!-- Service Select (Read-only for edit, editable for add) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('quotes.service_modal.service') }}
                </label>
                <select v-if="!line" v-model="form.service_id"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required>
                    <option value="">{{ $t('common.choose') }}</option>
                    <option v-for="service in services" :key="service.id" :value="service.id">
                        {{ getName(service) }}
                    </option>
                </select>
                <div v-else class="px-4 py-3 bg-gray-100 dark:bg-gray-900 rounded-xl text-gray-900 dark:text-white">
                    {{ getName(line.service) }}
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('quotes.service_modal.description') }}
                </label>
                <textarea v-model="form.description" rows="3"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                    :placeholder="$t('quotes.service_modal.description_placeholder')"></textarea>
            </div>

            <!-- Price (Qty is hidden/fixed to 1) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('quotes.service_modal.price') }}
                </label>
                <div class="relative">
                    <input type="number" v-model="form.unit_price" step="0.01" min="0" dir="ltr"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-end"
                        required />
                    <div class="absolute inset-y-0 start-0 ps-4 flex items-center pointer-events-none">
                        <span class="text-gray-500">{{ $t('common.currency') }}</span>
                    </div>
                </div>
            </div>

            <!-- Discount Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('quotes.service_modal.discount_method') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="form.discount_type = 'none'" :class="[
                            'px-3 py-1 text-sm rounded-lg transition-colors',
                            form.discount_type === 'none'
                                ? 'bg-gray-600 text-white'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                        ]">
                            {{ $t('quotes.service_modal.no_discount') }}
                        </button>
                        <button type="button" @click="form.discount_type = 'fixed'" :class="[
                            'px-3 py-1 text-sm rounded-lg transition-colors',
                            form.discount_type === 'fixed'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                        ]">
                            {{ $t('quotes.service_modal.fixed') }}
                        </button>
                        <button type="button" @click="form.discount_type = 'percentage'" :class="[
                            'px-3 py-1 text-sm rounded-lg transition-colors',
                            form.discount_type === 'percentage'
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                        ]">
                            %
                        </button>
                    </div>
                </div>

                <!-- Discount Value -->
                <div v-if="form.discount_type !== 'none'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('quotes.service_modal.discount_value') }}
                    </label>
                    <input type="number" v-model="form.discount_value" step="0.01" min="0" dir="ltr"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-end" />
                </div>
            </div>

            <!-- Calculated Total -->
            <div
                class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('quotes.service_modal.total_cost') }}
                </span>
                <span class="text-xl font-bold text-blue-600 dark:text-blue-400 font-mono">
                    {{ formatPrice(calculatedTotal) }}
                </span>
            </div>
        </form>

        <!-- Parts Tab (Linked Parts List) -->
        <div v-show="activeTab === 'parts'" class="space-y-4">
            <!-- Add Part Button -->
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ $t('quotes.show.tabs.linked_parts') }}
                </h4>
                <button type="button" @click="openPartModal"
                    class="px-3 py-1.5 text-sm bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('work_orders.item.add_part') }}
                </button>
            </div>

            <!-- Parts List -->
            <div v-if="allParts.length > 0" class="space-y-2">
                <div v-for="(part, index) in allParts" :key="part.id || `pending-${index}`"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900 dark:text-white">{{ part.name }}</span>
                            <span :class="getSourceBadgeClass(part.source)" class="text-xs px-2 py-0.5 rounded-full">
                                {{ $t('quotes.part_source.' + part.source) }}
                            </span>
                            <span v-if="!part.id"
                                class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                {{ $t('common.pending') }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500 flex items-center gap-3 mt-1" dir="ltr">
                            <span>{{ part.qty }} × {{ formatCurrency(part.unit_price) }}</span>
                            <span v-if="part.discount > 0" class="text-red-500">-{{ formatCurrency(part.discount)
                                }}</span>
                            <span class="font-bold text-green-600">= {{ formatCurrency(part.total ||
                                calculatePartTotal(part))
                                }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <!-- Edit button (only for pending parts) -->
                        <button v-if="!part.id" type="button" @click="editPendingPart(index)"
                            class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <!-- Delete button -->
                        <button type="button" @click="removePart(part, index)"
                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Parts Total -->
                <div
                    class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <span class="text-sm font-medium text-green-700 dark:text-green-300">{{
                        $t('work_orders.item.parts_cost')
                    }}</span>
                    <span class="font-bold text-green-600 dark:text-green-400 font-mono" dir="ltr">{{
                        formatCurrency(partsTotalCost) }} {{ $t('common.currency') }}</span>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="text-gray-400 text-sm mb-3">{{ $t('work_orders.item.no_parts') }}</p>
                <button type="button" @click="openPartModal"
                    class="px-4 py-2 text-sm bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                    {{ $t('work_orders.item.add_part') }}
                </button>
            </div>
        </div>

        <!-- Part Modal -->
        <QuotePartModal :show="showLinkedPartModal" :quote="quote" :part="editingLinkedPart" :quote-line-id="line?.id"
            :units="inventoryUnits" :show-service-select="false" :show-toggles="true" :pending-mode="!line"
            @close="closePartModal" @saved="onPartSaved" />

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button v-if="activeTab === 'service'" type="button" @click="submitForm" :disabled="form.processing"
                class="px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg hover:from-blue-600 hover:to-indigo-600 disabled:opacity-50 transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
            <button v-else type="button" disabled
                class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed rounded-lg">
                {{ $t('common.next') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import QuotePartModal from '@/Components/Quotes/QuotePartModal.vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    show: Boolean,
    quote: Object,
    line: Object,
    departmentId: Number,
    services: Array,
    inventoryUnits: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();

const { confirm } = useConfirm();

const activeTab = ref('service');
const showLinkedPartModal = ref(false);
const editingLinkedPart = ref(null);
const pendingParts = ref([]); // Parts added during new service creation
const editingPendingPartIndex = ref(null); // Track which pending part is being edited

// Form
const form = useForm({
    service_id: props.line?.service_id || '',
    description: props.line?.description || '',
    qty: 1, // Fixed to 1 as per requirements
    unit_price: props.line?.unit_price || 0,
    discount_type: props.line?.discount_type || 'none',
    discount_value: props.line?.discount_value || 0,
});

// Computed
const calculatedTotal = computed(() => {
    const price = parseFloat(form.unit_price) || 0;
    const qty = 1; // Always 1
    let discount = 0;

    if (form.discount_type === 'fixed') {
        discount = parseFloat(form.discount_value) || 0;
    } else if (form.discount_type === 'percentage') {
        discount = (price * (parseFloat(form.discount_value) || 0)) / 100;
    }

    return Math.max(0, (price - discount) * qty);
});

// Linked parts for this service line (saved)
const linkedParts = computed(() => {
    if (!props.line || !props.quote?.parts) return [];
    return props.quote.parts.filter(p => p.quote_line_id === props.line.id);
});

// All parts (saved + pending)
const allParts = computed(() => {
    return [...linkedParts.value, ...pendingParts.value];
});

const partsTotalCost = computed(() => {
    return allParts.value.reduce((sum, p) => sum + parseFloat(p.total || calculatePartTotal(p)), 0);
});

// Methods
function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

function submitForm() {
    // Include pending parts in form data
    const formData = {
        ...form.data(),
        pending_parts: pendingParts.value
    };

    if (props.line) {
        // Update existing line
        router.put(route('app.quotes.services.update', { quote: props.quote.id, line: props.line.id }), formData, {
            onSuccess: () => {
                form.reset();
                pendingParts.value = [];
                emit('saved');
            },
        });
    } else {
        // Add new line with pending parts
        router.post(route('app.quotes.services.store', { quote: props.quote.id }), formData, {
            onSuccess: () => {
                form.reset();
                pendingParts.value = [];
                emit('saved');
            },
        });
    }
}

function calculatePartTotal(part) {
    const price = parseFloat(part.unit_price) || 0;
    const qty = parseFloat(part.qty) || 0;
    const discount = parseFloat(part.discount) || 0;
    return Math.max(0, (price * qty) - discount);
}

function openPartModal(part = null) {
    editingLinkedPart.value = part;
    editingPendingPartIndex.value = null;
    showLinkedPartModal.value = true;
}

function editPendingPart(index) {
    // Get the pending part by its index in the pendingParts array
    const pendingIndex = index - linkedParts.value.length;
    const part = pendingParts.value[pendingIndex];
    editingLinkedPart.value = part;
    editingPendingPartIndex.value = pendingIndex;
    showLinkedPartModal.value = true;
}

function closePartModal() {
    showLinkedPartModal.value = false;
    editingLinkedPart.value = null;
    editingPendingPartIndex.value = null;
}

function onPartSaved(partData) {
    // Store the index BEFORE closing the modal (which resets it)
    const pendingIndex = editingPendingPartIndex.value;
    closePartModal();

    if (props.line) {
        // Editing existing service - reload to get saved parts
        router.reload();
    } else {
        // Adding new service - handle pending parts
        if (partData) {
            if (pendingIndex !== null) {
                // Update existing pending part
                pendingParts.value[pendingIndex] = partData;
            } else {
                // Add new pending part
                pendingParts.value.push(partData);
            }
        }
    }
}

async function removePart(part, index) {
    if (part.id) {
        // Saved part - delete from backend
        const confirmed = await confirm({
            title: t('quotes.messages.confirm_delete_part_title'),
            message: t('quotes.messages.confirm_delete_part'),
            confirmText: t('common.delete'),
            cancelText: t('common.cancel'),
            variant: 'danger'
        });
        if (confirmed) {
            router.delete(route('app.quotes.parts.destroy', { quote: props.quote.id, quotePart: part.id }));
        }
    } else {
        // Pending part - remove from local array
        pendingParts.value.splice(index - linkedParts.value.length, 1);
    }
}

function getSourceBadgeClass(source) {
    const classes = {
        warehouse: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        external: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        customer: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return classes[source] || 'bg-gray-100 text-gray-700';
}

// Watch for service selection to auto-fill price
watch(() => form.service_id, (serviceId) => {
    if (serviceId && !props.line) {
        const service = props.services.find(s => s.id === serviceId);
        if (service) {
            form.unit_price = service.base_price || 0;
            form.description = getName(service);
        }
    }
});

// Reset form when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        activeTab.value = 'service';
        pendingParts.value = []; // Reset pending parts
        if (props.line) {
            form.service_id = props.line.service_id;
            form.description = props.line.description;
            form.qty = 1;
            form.unit_price = props.line.unit_price;
            form.discount_type = props.line.discount_type || 'none';
            form.discount_value = props.line.discount_value || 0;
        } else {
            form.reset();
            form.qty = 1;
        }
    }
});
</script>
