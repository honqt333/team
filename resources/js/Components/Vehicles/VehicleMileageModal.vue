<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center"
                >
                    <svg
                        class="w-5 h-5 text-blue-600 dark:text-blue-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z"
                        />
                    </svg>
                </div>
                {{ $t('vehicles.mileage.history') }} - {{ vehicle?.plate_number }}
            </div>
        </template>

        <VehicleMileageList v-if="vehicle?.id" :vehicle-id="vehicle.id" />

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" @click="$emit('close')">
                    {{ $t('common.close') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import VehicleMileageList from '@/Components/Vehicles/VehicleMileageList.vue';

const props = defineProps({
    show: Boolean,
    vehicle: Object,
});

const emit = defineEmits(['close']);
</script>
