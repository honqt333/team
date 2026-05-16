<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="close"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-xl transform overflow-hidden rounded-3xl bg-white dark:bg-gray-800 p-8 shadow-2xl transition-all border border-gray-100 dark:border-gray-700">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $t('inventory.transfers.new') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.transfers.subtitle') }}</p>
                        </div>
                    </div>
                    <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- From Warehouse -->
                        <div>
                            <SearchableSelect
                                v-model="form.from_warehouse_id"
                                :options="fromWarehouseOptions"
                                :label="$t('inventory.transfers.from')"
                                :placeholder="$t('common.select')"
                                required
                                :error="form.errors.from_warehouse_id"
                            />
                        </div>

                        <!-- To Warehouse -->
                        <div>
                            <SearchableSelect
                                v-model="form.to_warehouse_id"
                                :options="toWarehouseOptions"
                                :label="$t('inventory.transfers.to')"
                                :placeholder="$t('common.select')"
                                required
                                :error="form.errors.to_warehouse_id"
                            />
                        </div>

                        <!-- Notes -->
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300 px-1">
                                {{ $t('common.notes') }}
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                :placeholder="$t('common.notes_placeholder') || '...'"
                                class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-0 ring-1 ring-gray-200 dark:ring-gray-700 rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-900 dark:text-white"
                            ></textarea>
                            <p v-if="form.errors.notes" class="text-xs text-red-500 px-1">{{ form.errors.notes }}</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="close"
                            class="px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-all"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-2xl font-black shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 disabled:opacity-50 disabled:translate-y-0 transition-all"
                        >
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ $t('common.saving') }}
                            </span>
                            <span v-else>{{ $t('common.save') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    show: Boolean,
    warehouses: Array,
});

const emit = defineEmits(['close']);

const form = useForm({
    from_warehouse_id: '',
    to_warehouse_id: '',
    notes: '',
});

const fromWarehouseOptions = computed(() => props.warehouses);

const toWarehouseOptions = computed(() => {
    return props.warehouses.filter(wh => wh.id !== form.from_warehouse_id);
});

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const submit = () => {
    form.post(route('app.inventory.transfers.store'), {
        onSuccess: () => {
            close();
        },
    });
};
</script>
