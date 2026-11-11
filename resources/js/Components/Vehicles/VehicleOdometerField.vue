<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import FormField from '@/Components/Common/FormField.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

defineProps({ lastVehicleOdometer: [String, Number], showOdometerWarning: Boolean, error: String });
defineEmits(['history']);
const reading = defineModel();
const allowLower = defineModel('allowLower');
const { toEnglish } = useNumberFormat();

function updateReading(event) {
    const digits = toEnglish(event.target.value).replace(/[^0-9]/g, '');
    event.target.value = digits;
    reading.value = digits;
}
</script>

<template>
    <div
        data-field="odometer"
        class="rounded-xl border border-border bg-surface-muted p-4 space-y-2"
    >
        <div class="flex items-center justify-between gap-3">
            <AppToggle
                v-if="lastVehicleOdometer > 0"
                v-model="allowLower"
                size="sm"
                color="warning"
                :label="$t('quotes.allow_lower_odometer')"
            />
            <button type="button" class="text-xs text-action-primary" @click="$emit('history')">
                {{ $t('vehicles.mileage.history') }}
            </button>
        </div>
        <FormField
            :label="$t('work_orders.form.odometer')"
            :error="error"
            v-slot="{ id, describedBy }"
        >
            <input
                :id="id"
                :aria-describedby="describedBy"
                :aria-invalid="!!error"
                :value="reading"
                name="odometer"
                type="text"
                inputmode="numeric"
                :placeholder="$t('work_orders.form.odometer_placeholder')"
                class="w-full rounded-xl border border-border bg-surface ps-4 pe-12 py-2.5 text-text-primary"
                @input="updateReading"
            />
            <span
                class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-xs text-text-muted"
            >
                {{ $t('common.km') }}
            </span>
        </FormField>
        <p v-if="showOdometerWarning" class="text-xs text-danger" role="alert">
            {{ $t('vehicles.mileage.lower_warning', { last: toEnglish(lastVehicleOdometer) }) }}
        </p>
        <p v-else-if="lastVehicleOdometer > 0" class="text-xs text-text-muted">
            {{ $t('vehicles.mileage.last_recorded') }}: {{ toEnglish(lastVehicleOdometer) }}
            {{ $t('common.km') }}
        </p>
    </div>
</template>
