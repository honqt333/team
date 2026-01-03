<template>
    <AppLayout :title="transfer?.code || $t('inventory.transfers.new')">
        <div class="p-6 max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ transfer ? $t('inventory.transfers.edit') : $t('inventory.transfers.new') }}
                </h1>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('common.notes') }}
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <a
                        :href="route('app.inventory.transfers.index')"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
                    </a>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    warehouses: Array,
    transfer: Object,
});

const form = useForm({
    from_warehouse_id: props.transfer?.from_warehouse_id || '',
    to_warehouse_id: props.transfer?.to_warehouse_id || '',
    notes: props.transfer?.notes || '',
});

const fromWarehouseOptions = computed(() => {
    return props.warehouses.map(wh => ({
        value: wh.id,
        label: wh.name
    }));
});

const toWarehouseOptions = computed(() => {
    return props.warehouses
        .filter(wh => wh.id !== form.from_warehouse_id)
        .map(wh => ({
            value: wh.id,
            label: wh.name
        }));
});

const submit = () => {
    form.post(route('app.inventory.transfers.store'));
};
</script>
