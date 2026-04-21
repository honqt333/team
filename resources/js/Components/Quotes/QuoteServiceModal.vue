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
                {{ line ? toEnglish(getName(line.service)) : $t('quotes.show.add_service') }}
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
                        {{ toEnglish(allParts.length) }}
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
                        {{ toEnglish(getName(service)) }}
                    </option>
                </select>
                <div v-else class="px-4 py-3 bg-gray-100 dark:bg-gray-900 rounded-xl text-gray-900 dark:text-white">
                    {{ toEnglish(getName(line.service)) }}
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('quotes.service_modal.description') }}
                </label>
                <textarea v-model="form.description" rows="3"
                    :disabled="isReadOnly"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:text-gray-500"
                    :placeholder="$t('quotes.service_modal.description_placeholder')"></textarea>
            </div>

            <!-- Price (Qty is hidden/fixed to 1) -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('quotes.service_modal.price') }}
                    </label>
                    <!-- Lock indicator when price override not allowed -->
                    <span v-if="isPriceLocked"
                        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        {{ $t('pricing.price_locked') }}
                    </span>
                </div>
                <div class="relative">
                    <input type="text" inputmode="decimal" v-model="form.unit_price" dir="ltr"
                        @input="form.unit_price = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                        :disabled="isPriceLocked || isReadOnly" :class="[
                            'w-full py-3 pl-4 pr-16 border rounded-xl font-mono text-right focus:ring-2 focus:border-blue-500',
                            (isPriceLocked || isReadOnly)
                                ? 'bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border-gray-200 dark:border-gray-700'
                                : isPriceBelowMinimum
                                    ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-red-300 dark:border-red-700 focus:ring-red-500'
                                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-200 dark:border-gray-700 focus:ring-blue-500'
                        ]" required />
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <span class="text-gray-500">{{ $t('common.currency') }}</span>
                    </div>
                </div>
                <!-- Min price warning -->
                <p v-if="isPriceBelowMinimum"
                    class="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ $t('quotes.min_price_warning', { min: formatCurrency(selectedServiceMinPrice) }) }}
                </p>
            </div>

            <!-- Discount Section -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('quotes.service_modal.discount_method') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" 
                            @click="form.discount_type = 'none'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                                form.discount_type === 'none'
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                                isReadOnly ? 'cursor-not-allowed opacity-50' : ''
                            ]">
                            {{ $t('quotes.service_modal.no_discount') }}
                        </button>
                        <button type="button" 
                            @click="form.discount_type = 'fixed'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                                form.discount_type === 'fixed'
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                                isReadOnly ? 'cursor-not-allowed opacity-50' : ''
                            ]">
                            {{ $t('quotes.service_modal.fixed') }}
                        </button>
                        <button type="button" 
                            @click="form.discount_type = 'percentage'"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded-lg border transition-all',
                                form.discount_type === 'percentage'
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                                isReadOnly ? 'cursor-not-allowed opacity-50' : ''
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
                    <input type="text" inputmode="decimal" v-model="form.discount_value" dir="ltr"
                        :disabled="isReadOnly"
                        @input="form.discount_value = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                        :class="[
                        'w-full px-4 py-3 border rounded-xl font-mono text-end focus:ring-2',
                        isReadOnly
                            ? 'bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border-gray-200 dark:border-gray-700'
                            : isPriceBelowMinimum
                                ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 focus:ring-red-500 focus:border-red-500'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 focus:ring-blue-500 focus:border-blue-500'
                    ]" />

                    <!-- Max allowed discount hint -->
                    <p v-if="selectedServiceMinPrice > 0" :class="[
                        'mt-1 text-sm flex items-center gap-1',
                        isPriceBelowMinimum ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'
                    ]">
                        <svg v-if="isPriceBelowMinimum" class="w-4 h-4" fill="none" stroke="currentColor"
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
                    : 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-blue-200 dark:border-blue-800'
            ]">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        {{ $t('quotes.service_modal.total_cost') }}
                    </span>
                    <div class="flex items-baseline gap-1.5 whitespace-nowrap">
                        <span :class="[
                            'text-2xl font-black font-mono',
                            isPriceBelowMinimum ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400'
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
        </form>

        <!-- Parts Tab (Linked Parts List) -->
        <div v-show="activeTab === 'parts'" class="space-y-4">
            <!-- Add Part Button -->
            <div class="flex justify-between items-center">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ $t('quotes.show.tabs.linked_parts') }}
                </h4>
                <button v-if="!isReadOnly" type="button" @click="openPartModal()"
                    class="px-3 py-1.5 text-sm bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('work_orders.item.add_part') }}
                </button>
            </div>

            <!-- Parts Display Component -->
            <PartsDisplay :parts="allParts" :read-only="isReadOnly" :show-vat="quote.is_taxed" :show-service="false"
                :compact-grid="true" :pending-check="part => !part.id" storage-key="quote_service_modal_parts_view"
                :empty-message="$t('work_orders.item.no_parts')" :add-button-text="$t('work_orders.item.add_part')"
                @edit="handlePartEdit" @delete="handlePartDelete" @add="openPartModal()" />

            <!-- Parts Total -->
            <div v-if="allParts.length > 0"
                class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                <span class="text-sm font-medium text-green-700 dark:text-green-300">{{
                    $t('work_orders.item.parts_cost')
                }}</span>
                <span class="font-bold text-green-600 dark:text-green-400 font-mono" dir="ltr">{{
                    formatCurrency(partsTotalCost) }} {{ $t('common.currency') }}</span>
            </div>
        </div>

        <!-- Part Modal -->
        <QuotePartModal :show="showLinkedPartModal" :quote="quote" :part="editingLinkedPart" :quote-line-id="line?.id"
            :units="inventoryUnits" :show-service-select="false" :show-toggles="true" :pending-mode="!line"
            @close="closePartModal" @saved="onPartSaved" />

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                {{ isReadOnly ? $t('common.close') : $t('common.cancel') }}
            </button>
            <button v-if="!isReadOnly" type="button" @click="submitForm"
                :disabled="form.processing || isPriceBelowMinimum"
                class="px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg hover:from-blue-600 hover:to-indigo-600 disabled:opacity-50 transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
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
import PartsDisplay from '@/Components/Common/PartsDisplay.vue';
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
const { formatCurrency, toEnglish } = useNumberFormat();

const { confirm } = useConfirm();

const activeTab = ref('service');
const showLinkedPartModal = ref(false);
const isPopulating = ref(false);
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
const isReadOnly = computed(() => {
    return ['approved', 'rejected', 'converted'].includes(props.quote.status);
});

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

// Get the currently selected service
const selectedService = computed(() => {
    // In edit mode, use the line's service directly
    if (props.line?.service) {
        return props.line.service;
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
    return parseFloat(form.unit_price) || 0;
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
        discount = parseFloat(form.discount_value) || 0;
    } else if (form.discount_type === 'percentage') {
        discount = (price * (parseFloat(form.discount_value) || 0)) / 100;
    }
    const finalPrice = Math.max(0, price - discount);

    return finalPrice < minPrice;
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

// Handler for PartsDisplay edit event
function handlePartEdit(part) {
    // Find the index of this part in allParts
    const index = allParts.value.findIndex(p => {
        if (part.id) return p.id === part.id;
        // For pending parts, compare by reference or unique properties
        return p === part || (p.name === part.name && p.unit_price === part.unit_price && !p.id);
    });

    if (part.id) {
        // Saved part - open for editing
        openPartModal(part);
    } else if (index >= linkedParts.value.length) {
        // Pending part - use editPendingPart with the allParts index
        editPendingPart(index);
    }
}

// Handler for PartsDisplay delete event
async function handlePartDelete(part) {
    // Find the index of this part in allParts
    const index = allParts.value.findIndex(p => {
        if (part.id) return p.id === part.id;
        return p === part || (p.name === part.name && p.unit_price === part.unit_price && !p.id);
    });

    await removePart(part, index);
}

// Watch for service selection to auto-fill price
watch(() => form.service_id, (serviceId) => {
    if (isPopulating.value) return; // Skip if we are just loading data
    if (serviceId && !props.line) {
        const service = props.services.find(s => s.id === serviceId);
        if (service) {
            form.unit_price = service.base_price || 0;
            form.description = getName(service);
        }
    }
});

// Reset form when modal opens
// Enhanced Watcher for robust data population
watch([() => props.show, () => props.line], ([isOpen, line]) => {
    if (isOpen) {
        isPopulating.value = true;
        activeTab.value = 'service';
        pendingParts.value = []; // Reset pending parts
        
        if (line) {
            form.service_id = line.service_id;
            form.description = line.description || '';
            form.qty = line.qty || 1;
            form.unit_price = line.unit_price || 0;
            form.discount_type = line.discount_type || 'none';
            form.discount_value = line.discount_value || 0;
        } else {
            form.service_id = '';
            form.description = '';
            form.qty = 1;
            form.unit_price = 0;
            form.discount_type = 'none';
            form.discount_value = 0;
            form.reset();
        }

        // Use timeout to ensure reactivity settles before re-enabling watchers
        setTimeout(() => {
            isPopulating.value = false;
        }, 100);
    }
}, { immediate: true });
</script>
