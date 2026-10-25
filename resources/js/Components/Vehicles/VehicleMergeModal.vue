<template>
    <BaseModal :show="show" @close="$emit('close')">
        <template #title>
            <div class="flex items-center gap-2">
                <svg
                    class="w-6 h-6 text-amber-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                    />
                </svg>
                <span>{{ $t('vehicles.merge.title') }}</span>
            </div>
        </template>

        <!-- Default Slot for Content -->
        <div class="space-y-6">
            <!-- Error Message -->
            <div
                v-if="errorMessage"
                class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400"
            >
                <p class="font-bold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    {{ $t('common.error') }}
                </p>
                <p class="mt-1 text-sm">{{ errorMessage }}</p>
            </div>

            <!-- Loading State -->
            <div v-else-if="loading" class="text-center py-12">
                <svg
                    class="w-10 h-10 mx-auto text-amber-500 animate-spin"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <p class="mt-3 text-gray-500 font-medium">{{ $t('common.loading') }}</p>
            </div>

            <!-- Step 1: Select Target Vehicle -->
            <div v-else-if="!selectedTarget" class="space-y-4">
                <!-- Source Vehicle Summary Card -->
                <div
                    class="bg-amber-50/70 dark:bg-amber-950/20 p-3.5 rounded-2xl border border-amber-200/60 dark:border-amber-800/40 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <SaudiPlateDisplay :plate-number="source.plate_number" size="sm" />
                        <div>
                            <p class="text-xs font-black text-gray-900 dark:text-white">
                                {{ source.make_name }} {{ source.model_name }}
                                {{ source.year ? `(${source.year})` : '' }}
                            </p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                {{ source.customer_name }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="text-xs px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 font-bold"
                    >
                        {{ $t('vehicles.merge.source_label') }}
                    </span>
                </div>

                <!-- Notice that merge must be same customer and same make -->
                <div
                    class="p-3 rounded-xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/30 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2"
                >
                    <svg
                        class="w-4 h-4 shrink-0 text-amber-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <span>{{ $t('vehicles.merge.same_customer_and_make_notice') }}</span>
                </div>

                <!-- Search -->
                <div class="relative">
                    <div
                        class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                    >
                        <svg
                            class="w-4 h-4 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                    <input
                        type="text"
                        v-model="searchQuery"
                        :placeholder="$t('vehicles.merge.search_target_placeholder')"
                        class="w-full ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500"
                    />
                </div>

                <!-- Vehicles Grid -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[360px] overflow-y-auto p-1"
                >
                    <div
                        v-for="v in filteredTargets"
                        :key="v.id"
                        @click="v.is_same_make ? selectTarget(v) : null"
                        :class="[
                            'group rounded-xl p-3.5 border-2 transition-all space-y-2',
                            v.is_same_make
                                ? 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-md cursor-pointer'
                                : 'bg-gray-50/70 dark:bg-gray-800/40 border-dashed border-gray-200 dark:border-gray-700/60 opacity-60 cursor-not-allowed',
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <SaudiPlateDisplay :plate-number="v.plate_number" size="xs" />
                            <div class="flex items-center gap-1.5">
                                <span
                                    v-if="v.is_same_make"
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800"
                                >
                                    {{ $t('vehicles.merge.same_make_badge') }}
                                </span>
                                <span
                                    v-else
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 font-bold border border-rose-200 dark:border-rose-800"
                                >
                                    {{ $t('vehicles.merge.different_make_badge') }}
                                </span>
                            </div>
                        </div>

                        <div class="text-xs">
                            <p
                                class="font-bold text-gray-900 dark:text-white truncate"
                                :class="{ 'group-hover:text-amber-600': v.is_same_make }"
                            >
                                {{ v.make_name }} {{ v.model_name }}
                                {{ v.year ? `(${v.year})` : '' }}
                            </p>
                            <p class="text-gray-500 dark:text-gray-400 truncate">
                                {{ v.customer_name }}
                            </p>
                        </div>

                        <div
                            v-if="!v.is_same_make"
                            class="pt-1.5 text-[11px] text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1"
                        >
                            <svg
                                class="w-3.5 h-3.5 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            <span>{{ $t('vehicles.merge.cannot_merge_different_make') }}</span>
                        </div>

                        <div
                            v-else
                            class="pt-2 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-[11px] text-gray-500"
                        >
                            <span>
                                {{ $t('work_orders.title') }}:
                                <strong class="text-gray-800 dark:text-gray-200">
                                    {{ v.work_orders_count }}
                                </strong>
                            </span>
                            <span>
                                {{ $t('quotes.title') }}:
                                <strong class="text-gray-800 dark:text-gray-200">
                                    {{ v.quotes_count }}
                                </strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredTargets.length === 0"
                    class="text-center py-8 text-gray-500 text-sm"
                >
                    {{ $t('vehicles.merge.no_targets_for_customer') }}
                </div>
            </div>

            <!-- Step 2: Confirm Merge -->
            <div v-else class="space-y-6">
                <!-- Comparison Preview -->
                <div class="flex flex-col md:flex-row items-center gap-4 w-full">
                    <!-- Source (Will be Deleted) -->
                    <div
                        class="flex-1 w-full bg-rose-50/50 dark:bg-rose-950/20 rounded-2xl p-4 border-2 border-rose-200 dark:border-rose-900/40"
                    >
                        <p
                            class="text-xs font-black text-rose-600 dark:text-rose-400 mb-2 text-center uppercase tracking-wider"
                        >
                            {{ $t('vehicles.merge.source_deleted_title') }}
                        </p>
                        <div class="flex flex-col items-center text-center space-y-1.5">
                            <SaudiPlateDisplay :plate-number="source.plate_number" size="sm" />
                            <p class="font-bold text-sm text-gray-900 dark:text-white">
                                {{ source.make_name }} {{ source.model_name }}
                            </p>
                            <p class="text-xs text-gray-500">{{ source.customer_name }}</p>
                        </div>
                        <div
                            class="mt-3 pt-3 border-t border-rose-200 dark:border-rose-800/50 space-y-1 text-xs"
                        >
                            <p class="flex justify-between">
                                <span class="text-gray-500">{{ $t('work_orders.title') }}:</span>
                                <strong class="text-gray-800 dark:text-gray-200">
                                    {{ source.work_orders_count }}
                                </strong>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-500">{{ $t('quotes.title') }}:</span>
                                <strong class="text-gray-800 dark:text-gray-200">
                                    {{ source.quotes_count }}
                                </strong>
                            </p>
                        </div>
                    </div>

                    <!-- Arrow -->
                    <div class="flex-shrink-0">
                        <svg
                            class="w-8 h-8 text-amber-500 transform rotate-90 md:rotate-0 rtl:md:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            />
                        </svg>
                    </div>

                    <!-- Target (Will Remain) -->
                    <div
                        class="flex-1 w-full bg-emerald-50/50 dark:bg-emerald-950/20 rounded-2xl p-4 border-2 border-emerald-300 dark:border-emerald-800"
                    >
                        <p
                            class="text-xs font-black text-emerald-600 dark:text-emerald-400 mb-2 text-center uppercase tracking-wider"
                        >
                            {{ $t('vehicles.merge.target_remains_title') }}
                        </p>
                        <div class="flex flex-col items-center text-center space-y-1.5">
                            <SaudiPlateDisplay
                                :plate-number="selectedTarget.plate_number"
                                size="sm"
                            />
                            <p class="font-bold text-sm text-gray-900 dark:text-white">
                                {{ selectedTarget.make_name }} {{ selectedTarget.model_name }}
                            </p>
                            <p class="text-xs text-gray-500">{{ selectedTarget.customer_name }}</p>
                        </div>
                        <div
                            class="mt-3 pt-3 border-t border-emerald-200 dark:border-emerald-800/50 space-y-1 text-xs"
                        >
                            <p class="flex justify-between">
                                <span class="text-gray-500">{{ $t('work_orders.title') }}:</span>
                                <strong class="text-gray-800 dark:text-gray-200">
                                    {{ selectedTarget.work_orders_count }}
                                </strong>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-500">{{ $t('quotes.title') }}:</span>
                                <strong class="text-gray-800 dark:text-gray-200">
                                    {{ selectedTarget.quotes_count }}
                                </strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- After Merge Impact -->
                <div
                    class="bg-amber-50 dark:bg-amber-950/30 rounded-2xl p-4 border border-amber-200 dark:border-amber-800/50 space-y-2"
                >
                    <p class="text-xs font-bold text-amber-800 dark:text-amber-300">
                        {{ $t('vehicles.merge.warning_notice') }}
                    </p>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div>
                            <p class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                                {{
                                    (source.work_orders_count || 0) +
                                    (selectedTarget.work_orders_count || 0)
                                }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $t('vehicles.merge.total_work_orders') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xl font-black text-amber-600 dark:text-amber-400">
                                {{
                                    (source.quotes_count || 0) + (selectedTarget.quotes_count || 0)
                                }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $t('vehicles.merge.total_quotes') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button
                    v-if="selectedTarget"
                    type="button"
                    @click="selectedTarget = null"
                    class="px-4 py-2 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                >
                    {{ $t('common.back') }}
                </button>
                <div v-else></div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        v-if="selectedTarget"
                        type="button"
                        @click="executeMerge"
                        :disabled="submitting"
                        class="px-5 py-2 text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 disabled:opacity-50 rounded-xl shadow-lg shadow-amber-600/30 flex items-center gap-2 transition-all"
                    >
                        <svg
                            v-if="submitting"
                            class="w-4 h-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span>
                            {{
                                submitting
                                    ? $t('vehicles.merge.merging')
                                    : $t('vehicles.merge.confirm_button')
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    vehicle: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const loading = ref(true);
const submitting = ref(false);
const errorMessage = ref('');
const searchQuery = ref('');
const source = ref({});
const targets = ref([]);
const selectedTarget = ref(null);

onMounted(async () => {
    try {
        const response = await axios.get(`/app/vehicles/${props.vehicle.id}/merge`);
        source.value = response.data.source;
        targets.value = response.data.targets;
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to load merge data';
    } finally {
        loading.value = false;
    }
});

const filteredTargets = computed(() => {
    return targets.value.filter((t) => {
        if (!searchQuery.value) return true;

        const q = searchQuery.value.toLowerCase();
        return (
            t.plate_number?.toLowerCase().includes(q) ||
            t.make_name?.toLowerCase().includes(q) ||
            t.model_name?.toLowerCase().includes(q) ||
            t.vin?.toLowerCase().includes(q)
        );
    });
});

function selectTarget(target) {
    if (!target.is_same_make) return;
    selectedTarget.value = target;
}

function executeMerge() {
    if (!selectedTarget.value || submitting.value) return;

    submitting.value = true;
    router.post(
        `/app/vehicles/${source.value.id}/merge/${selectedTarget.value.id}`,
        {},
        {
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                errorMessage.value = Object.values(errors).flat().join(', ');
                submitting.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        }
    );
}
</script>
