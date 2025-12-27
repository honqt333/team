<template>
    <BaseModal :show="show" @close="$emit('close')">
        <template #title>
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>{{ $t('customers.merge.title') }} "{{ source.name }}" {{ $t('customers.merge.with') }} ...</span>
            </div>
        </template>

        <!-- Default Slot for Content -->
        <div class="space-y-6">
            <!-- Error Message -->
            <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400">
                <p class="font-bold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $t('common.error') }}
                </p>
                <p class="mt-1 text-sm">{{ errorMessage }}</p>
            </div>

            <!-- Loading State -->
            <div v-else-if="loading" class="text-center py-12">
                <svg class="w-10 h-10 mx-auto text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-gray-500 font-medium">{{ $t('common.loading') }}</p>
            </div>

            <!-- Step 1: Select Target Customer -->
            <div v-else-if="!selectedTarget">
                <!-- Search & Filter -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="searchQuery"
                            :placeholder="$t('customers.search')"
                            class="w-full ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500"
                        />
                    </div>
                </div>

                <!-- Customers Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 max-h-[400px] overflow-y-auto">
                    <div
                        v-for="customer in filteredCustomers"
                        :key="customer.id"
                        @click="selectTarget(customer)"
                        class="group bg-white dark:bg-gray-800 rounded-xl p-4 border-2 border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 cursor-pointer transition-all"
                    >
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center">
                                <span class="text-sm font-bold text-white">{{ customer.name?.charAt(0)?.toUpperCase() }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white truncate group-hover:text-amber-600">{{ customer.name }}</p>
                            </div>
                        </div>
                        <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <p dir="ltr" class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ customer.phone }}
                            </p>
                            <p v-if="customer.whatsapp" dir="ltr" class="flex items-center gap-1 text-green-500">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                {{ customer.whatsapp }}
                            </p>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 grid grid-cols-3 gap-1 text-xs">
                            <div class="text-center">
                                <p class="font-bold text-teal-600">{{ customer.vehicles_count }}</p>
                                <p class="text-gray-400">{{ $t('customers.vehicles') }}</p>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-indigo-600">{{ customer.work_orders_count }}</p>
                                <p class="text-gray-400">{{ $t('customers.work_orders') }}</p>
                            </div>
                            <div class="text-center">
                                <p class="font-bold text-amber-600">{{ customer.quotes_count }}</p>
                                <p class="text-gray-400">{{ $t('customers.quotes') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredCustomers.length === 0" class="text-center py-8 text-gray-500">
                    {{ $t('customers.merge.no_customers') }}
                </div>
            </div>

            <!-- Step 2: Confirm Merge -->
            <div v-else class="space-y-6">
                <!-- Merge Preview -->
                <div class="flex items-center gap-4">
                    <!-- Source (Right - From) -->
                    <div class="flex-1 bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2 text-center">{{ $t('customers.merge.from') }}</p>
                        <div class="text-center">
                            <div class="w-12 h-12 mx-auto rounded-lg bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center mb-2">
                                <span class="text-lg font-bold text-white">{{ source.name?.charAt(0)?.toUpperCase() }}</span>
                            </div>
                            <p class="font-bold text-gray-900 dark:text-white">{{ source.name }}</p>
                            <p class="text-sm text-gray-500" dir="ltr">{{ source.phone }}</p>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 space-y-1 text-sm">
                            <p class="flex justify-between"><span class="text-gray-500">{{ $t('customers.vehicles') }}:</span><span class="font-medium">{{ source.vehicles_count }}</span></p>
                            <p class="flex justify-between"><span class="text-gray-500">{{ $t('customers.work_orders') }}:</span><span class="font-medium">{{ source.work_orders_count }}</span></p>
                            <p class="flex justify-between"><span class="text-gray-500">{{ $t('customers.quotes') }}:</span><span class="font-medium">{{ source.quotes_count }}</span></p>
                        </div>
                    </div>

                    <!-- Arrow -->
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-amber-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </div>

                    <!-- Target (Left - To) -->
                    <div class="flex-1 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border-2 border-emerald-300 dark:border-emerald-700">
                        <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400 mb-2 text-center">{{ $t('customers.merge.to') }}</p>
                        <div class="text-center">
                            <div class="w-12 h-12 mx-auto rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center mb-2">
                                <span class="text-lg font-bold text-white">{{ selectedTarget.name?.charAt(0)?.toUpperCase() }}</span>
                            </div>
                            <p class="font-bold text-gray-900 dark:text-white">{{ selectedTarget.name }}</p>
                            <p class="text-sm text-gray-500" dir="ltr">{{ selectedTarget.phone }}</p>
                        </div>
                        <div class="mt-3 pt-3 border-t border-emerald-200 dark:border-emerald-700 space-y-1 text-sm">
                            <p class="flex justify-between"><span class="text-gray-500">{{ $t('customers.vehicles') }}:</span><span class="font-medium">{{ selectedTarget.vehicles_count }}</span></p>
                            <p class="flex justify-between"><span class="text-gray-500">{{ $t('customers.work_orders') }}:</span><span class="font-medium">{{ selectedTarget.work_orders_count }}</span></p>
                            <p class="flex justify-between"><span class="text-gray-500">{{ $t('customers.quotes') }}:</span><span class="font-medium">{{ selectedTarget.quotes_count }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- After Merge Preview -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-700">
                    <p class="text-sm font-medium text-amber-700 dark:text-amber-400 mb-2">{{ $t('customers.merge.after_merge') }}</p>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-teal-600">{{ source.vehicles_count + selectedTarget.vehicles_count }}</p>
                            <p class="text-sm text-gray-500">{{ $t('customers.vehicles') }}</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-indigo-600">{{ source.work_orders_count + selectedTarget.work_orders_count }}</p>
                            <p class="text-sm text-gray-500">{{ $t('customers.work_orders') }}</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-amber-600">{{ source.quotes_count + selectedTarget.quotes_count }}</p>
                            <p class="text-sm text-gray-500">{{ $t('customers.quotes') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Confirmations -->
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <input type="checkbox" v-model="confirmMerge" class="w-5 h-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $t('customers.merge.confirm_message', { source: source.name, target: selectedTarget.name }) }}
                        </span>
                    </label>
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-red-200 dark:border-red-700 cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20">
                        <input type="checkbox" v-model="confirmNoUndo" class="w-5 h-5 rounded border-red-300 text-red-600 focus:ring-red-500">
                        <span class="text-sm text-red-700 dark:text-red-400">
                            {{ $t('customers.merge.confirm_no_undo') }}
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center gap-3">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    v-if="selectedTarget"
                    @click="selectedTarget = null"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    {{ $t('customers.merge.change_target') }}
                </button>
                <button
                    v-if="selectedTarget"
                    @click="executeMerge"
                    :disabled="!canMerge || processing"
                    :class="[
                        'px-6 py-2 rounded-lg font-medium transition-all',
                        canMerge && !processing
                            ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg hover:shadow-xl'
                            : 'bg-gray-300 dark:bg-gray-600 text-gray-500 cursor-not-allowed'
                    ]"
                >
                    {{ processing ? $t('common.loading') : $t('customers.merge.execute') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: Boolean,
    customer: Object,
    counts: Object,
});

const emit = defineEmits(['close']);

const { t } = useI18n();

const loading = ref(true);
const processing = ref(false);
const errorMessage = ref(null);
const searchQuery = ref('');

// Initialize source with data passed from parent for instant rendering
const source = ref({
    ...props.customer,
    vehicles_count: props.counts?.vehicles || 0,
    quotes_count: props.counts?.quotes || 0,
    work_orders_count: props.counts?.workOrders || 0,
});

const targets = ref([]);
const selectedTarget = ref(null);
const confirmMerge = ref(false);
const confirmNoUndo = ref(false);

const filteredCustomers = computed(() => {
    if (!searchQuery.value) return targets.value;
    const q = searchQuery.value.toLowerCase();
    return targets.value.filter(c => 
        c.name?.toLowerCase().includes(q) || 
        c.phone?.includes(q)
    );
});

const canMerge = computed(() => confirmMerge.value && confirmNoUndo.value);

onMounted(async () => {
    try {
        const response = await axios.get(route('customers.merge-data', props.customer.id));
        const data = response.data;
        // Optionally update source with fresh data from API, but keep existing as fallback
        if (data.source) {
            source.value = data.source;
        }
        targets.value = data.targets;
    } catch (error) {
        console.error('Failed to load merge data:', error);
        errorMessage.value = error.response?.data?.message || error.message || t('common.error_occurred');
    } finally {
        loading.value = false;
    }
});

function selectTarget(customer) {
    selectedTarget.value = customer;
    confirmMerge.value = false;
    confirmNoUndo.value = false;
}

function executeMerge() {
    if (!canMerge.value) return;
    
    processing.value = true;
    router.post(route('customers.execute-merge', { source: source.value.id, target: selectedTarget.value.id }), {}, {
        onSuccess: () => {
            emit('close');
        },
        onFinish: () => {
            processing.value = false;
        }
    });
}
</script>
