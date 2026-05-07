<template>
    <BaseModal :show="show" @close="$emit('close')" size="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                {{ $t('work_orders.photos.title') }}
            </div>
        </template>

        <div class="space-y-4">
            <VehiclePhotoUploader v-model:photos="form.photos" />
        </div>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button type="button" @click="submit" :disabled="form.processing || form.photos.length === 0"
                class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 transition-all flex items-center gap-2">
                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
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
