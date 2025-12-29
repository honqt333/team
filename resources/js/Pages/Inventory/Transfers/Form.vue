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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('inventory.transfers.from') }} *
                        </label>
                        <select
                            v-model="form.from_warehouse_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            required
                        >
                            <option value="">-- {{ $t('common.select') }} --</option>
                            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                {{ wh.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.from_warehouse_id" class="mt-1 text-sm text-red-600">{{ form.errors.from_warehouse_id }}</p>
                    </div>

                    <!-- To Warehouse -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('inventory.transfers.to') }} *
                        </label>
                        <select
                            v-model="form.to_warehouse_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            required
                        >
                            <option value="">-- {{ $t('common.select') }} --</option>
                            <option v-for="wh in availableToWarehouses" :key="wh.id" :value="wh.id">
                                {{ wh.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.to_warehouse_id" class="mt-1 text-sm text-red-600">{{ form.errors.to_warehouse_id }}</p>
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

const props = defineProps({
    warehouses: Array,
    transfer: Object,
});

const form = useForm({
    from_warehouse_id: props.transfer?.from_warehouse_id || '',
    to_warehouse_id: props.transfer?.to_warehouse_id || '',
    notes: props.transfer?.notes || '',
});

const availableToWarehouses = computed(() => {
    return props.warehouses.filter(wh => wh.id !== form.from_warehouse_id);
});

const submit = () => {
    form.post(route('app.inventory.transfers.store'));
};
</script>
