<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-action-primary hover:bg-action-primary-hover flex items-center justify-center"
                >
                    <svg
                        class="w-5 h-5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>
                {{ $t('work_orders.photos.title') }}
            </div>
        </template>

        <div class="space-y-4">
            <VehiclePhotoUploader v-model:photos="form.photos" />
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    variant="primary"
                    :loading="form.processing"
                    :disabled="form.photos.length === 0"
                    @click="submit"
                >
                    {{ $t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import VehiclePhotoUploader from '@/Components/WorkOrders/VehiclePhotoUploader.vue';

const props = defineProps({
    show: Boolean,
    workOrder: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    photos: [],
});

function submit() {
    form.post(route('work-orders.photos.store', props.workOrder.id), {
        onSuccess: () => {
            form.reset();
            emit('saved');
            emit('close');
        },
        forceFormData: true,
    });
}
</script>
