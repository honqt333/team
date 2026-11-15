<template>
    <AppLayout :title="transfer?.code || $t('inventory.transfers.new')">
        <div class="space-y-6 max-w-3xl mx-auto">
            <!-- Header -->
            <PageHeader
                :title="transfer ? $t('inventory.transfers.edit') : $t('inventory.transfers.new')"
            >
                <template #back>
                    <BackButton :href="route('app.inventory.transfers.index')" />
                </template>
            </PageHeader>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6"
                >
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
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                        >
                            {{ $t('common.notes') }}
                        </label>
                        <AppTextarea
                            v-model="form.notes"
                            :rows="3"
                        />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <AppButton as="link" variant="secondary" :href="route('app.inventory.transfers.index')">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton type="submit" :loading="form.processing" :disabled="form.processing">
                        {{ $t('common.save') }}
                    </AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
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
    return props.warehouses.map((wh) => ({
        value: wh.id,
        label: wh.name,
    }));
});

const toWarehouseOptions = computed(() => {
    return props.warehouses
        .filter((wh) => wh.id !== form.from_warehouse_id)
        .map((wh) => ({
            value: wh.id,
            label: wh.name,
        }));
});

const submit = () => {
    form.post(route('app.inventory.transfers.store'));
};
</script>
