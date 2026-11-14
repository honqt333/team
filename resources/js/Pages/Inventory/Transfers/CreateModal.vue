<template>
    <BaseModal :show="!!show" size="md" @close="close">
        <template #title>
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-2xl bg-selection-bg flex items-center justify-center text-action-primary"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                        />
                    </svg>
                </div>
                <div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $t('inventory.transfers.new') }}
                    </div>
                    <p class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        {{ $t('inventory.transfers.subtitle') }}
                    </p>
                </div>
            </div>
        </template>

        <!-- Form -->
        <form id="create-transfer-form" @submit.prevent="submit" class="space-y-6">
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
                    <AppTextarea
                        v-model="form.notes"
                        rows="3"
                        :placeholder="$t('common.notes_placeholder')"
                        :error="form.errors.notes"
                    />
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" @click="close">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    form="create-transfer-form"
                    variant="action"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

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
    return props.warehouses.filter((wh) => wh.id !== form.from_warehouse_id);
});

const close = () => {
    form.reset();
    form.clearErrors();
    emit('close');
};

const { scrollToError } = useFormScrollToError();

const submit = () => {
    form.post(route('app.inventory.transfers.store'), {
        onSuccess: () => {
            close();
        },
        onError: (errors) => scrollToError(errors),
    });
};
</script>
