<template>
    <BaseModal
        :show="show"
        size="lg"
        :title="isEditing ? $t('appointments.edit') : $t('appointments.new')"
        @close="emit('close')"
    >
        <form id="appointment-form" @submit.prevent="submit" class="space-y-4">
            <!-- Quick Vehicle/Customer Search (Create mode only) -->
            <div v-if="!isEditing" class="relative">
                <div class="relative">
                    <AppInput
                        v-model="quickSearchQuery"
                        type="text"
                        size="sm"
                        @input="onQuickSearchInput"
                        :placeholder="$t('appointments.search_vehicle_or_customer')"
                    />
                    <span
                        v-if="searchingQuick"
                        class="absolute end-3 top-1/2 -translate-y-1/2 text-xs text-content-muted animate-pulse"
                    >
                        ...
                    </span>
                </div>

                <div
                    v-if="quickSearchResults.length > 0"
                    class="absolute z-20 start-0 end-0 mt-1 bg-surface-primary border border-border-subtle rounded-xl shadow-xl max-h-48 overflow-y-auto divide-y divide-border-subtle"
                >
                    <button
                        v-for="v in quickSearchResults"
                        :key="v.id"
                        type="button"
                        @click="selectQuickVehicle(v)"
                        class="w-full text-start p-2.5 hover:bg-surface-secondary/50 flex items-center justify-between transition text-xs cursor-pointer"
                    >
                        <div class="flex items-center gap-2">
                            <SaudiPlateDisplay :plate-number="v.plate_number" size="sm" />
                            <div>
                                <div class="font-medium text-content-primary">
                                    {{
                                        [v.make?.name, v.model?.name, v.year]
                                            .filter(Boolean)
                                            .join(' ')
                                    }}
                                </div>
                                <div class="text-[11px] text-content-muted">
                                    {{ v.customer?.name }}
                                    {{ v.customer?.phone ? '(' + v.customer.phone + ')' : '' }}
                                </div>
                            </div>
                        </div>
                        <span class="text-[10px] text-action-primary font-bold">
                            {{ $t('common.select') }}
                        </span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Customer & Vehicle (Editable in Create, Read-only summary in Edit) -->
                <template v-if="!isEditing">
                    <FormField
                        v-slot="field"
                        :label="$t('appointments.customer')"
                        :error="errors.customer_id?.[0]"
                        required
                    >
                        <SearchableSelect
                            :id="field.id"
                            v-model="form.customer_id"
                            :options="customerOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('appointments.customer_placeholder')"
                            :search-placeholder="$t('common.search')"
                            :label="''"
                            @search="onCustomerSearch"
                        />
                    </FormField>

                    <FormField
                        v-slot="field"
                        :label="$t('appointments.vehicle')"
                        :error="errors.vehicle_id?.[0]"
                        required
                    >
                        <SearchableSelect
                            :id="field.id"
                            v-model="form.vehicle_id"
                            :options="vehicleOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="
                                form.customer_id
                                    ? vehicleOptions.length
                                        ? $t('appointments.vehicle_placeholder')
                                        : $t('appointments.no_vehicles')
                                    : $t('appointments.select_customer_first')
                            "
                            :search-placeholder="$t('common.search')"
                            :disabled="!form.customer_id || vehicleOptions.length === 0"
                            :label="''"
                        />
                    </FormField>

                    <div
                        v-if="selectedVehicle"
                        class="col-span-1 md:col-span-2 bg-action-primary/5 border border-action-primary/20 rounded-xl p-2.5 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <SaudiPlateDisplay
                                :plate-number="selectedVehicle.plate_number"
                                size="sm"
                            />
                            <div class="text-xs">
                                <span class="font-bold text-content-primary">
                                    {{
                                        [
                                            selectedVehicle.make?.name,
                                            selectedVehicle.model?.name,
                                            selectedVehicle.year,
                                        ]
                                            .filter(Boolean)
                                            .join(' ')
                                    }}
                                </span>
                                <span v-if="selectedCustomer" class="text-content-muted ms-2">
                                    ({{ selectedCustomer.name }})
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="col-span-1 md:col-span-2 bg-surface-secondary/60 border border-border-subtle rounded-xl p-3 flex flex-wrap items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-3">
                        <SaudiPlateDisplay
                            v-if="appointment?.vehicle?.plate_number"
                            :plate-number="appointment.vehicle.plate_number"
                            size="sm"
                        />
                        <div class="text-xs">
                            <div class="font-bold text-content-primary">
                                {{ appointment?.customer?.name || '—' }}
                            </div>
                            <div v-if="appointment?.customer?.phone" class="text-content-muted" dir="ltr">
                                {{ appointment.customer.phone }}
                            </div>
                        </div>
                    </div>
                </div>

                <FormField
                    v-slot="field"
                    :label="$t('appointments.bay')"
                    :error="errors.service_bay_id?.[0]"
                >
                    <SearchableSelect
                        :id="field.id"
                        v-model="form.service_bay_id"
                        :options="bayFormOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.no_bay')"
                        :label="''"
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    :label="$t('appointments.tech')"
                    :error="errors.assigned_user_id?.[0]"
                >
                    <SearchableSelect
                        :id="field.id"
                        v-model="form.assigned_user_id"
                        :options="techFormOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.no_tech')"
                        :label="''"
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    :label="$t('appointments.scheduled_at')"
                    :error="errors.scheduled_at?.[0]"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.scheduled_at"
                        type="datetime-local"
                        required
                    />
                </FormField>

                <FormField
                    v-slot="field"
                    :label="$t('appointments.estimated_end_at')"
                    :error="errors.estimated_end_at?.[0]"
                    required
                >
                    <AppInput
                        :id="field.id"
                        :aria-describedby="field.describedBy"
                        :invalid="field.hasError"
                        v-model="form.estimated_end_at"
                        type="datetime-local"
                        required
                        @input="manualEndEdited = true"
                    />
                </FormField>
            </div>

            <!-- Auto-duration hint & Conflict Warning -->
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <span class="text-content-muted">
                    {{ $t('appointments.auto_end_hint') }}
                </span>
                <span
                    v-if="estimatedDurationMinutes > 0"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-info-soft text-info font-bold"
                >
                    {{ $t('appointments.estimated_duration') }}: {{ estimatedDurationMinutes }}
                    {{ $t('appointments.minutes_short') }}
                </span>
            </div>

            <div
                v-if="hasTimeConflict"
                class="rounded-xl border border-warning/40 bg-warning-soft px-3.5 py-2.5 text-xs font-bold text-warning"
            >
                {{ $t('appointments.conflict_warning') }}
            </div>

            <!-- Services Selection -->
            <FormField
                :label="$t('appointments.services')"
                :error="errors.service_ids?.[0]"
            >
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 gap-2 border border-border-subtle bg-surface-secondary/50 rounded-xl p-3"
                >
                    <div
                        v-for="s in services"
                        :key="s.id"
                        class="flex items-center justify-between gap-2 text-xs"
                    >
                        <AppToggle :value="s.id" v-model="form.service_ids" :label="s.name" />
                        <span class="text-content-muted font-medium shrink-0">
                            {{ formatPrice(s.base_price) }}
                        </span>
                    </div>
                </div>
            </FormField>

            <FormField
                v-slot="field"
                :label="$t('appointments.notes')"
                :error="errors.notes?.[0]"
            >
                <AppTextarea
                    :id="field.id"
                    :aria-describedby="field.describedBy"
                    :invalid="field.hasError"
                    v-model="form.notes"
                    :rows="2"
                />
            </FormField>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-2 w-full">
                <AppButton type="button" variant="secondary" @click="emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="submit"
                    form="appointment-form"
                    variant="primary"
                    :loading="busy"
                    :disabled="
                        busy ||
                        (!isEditing && (!form.customer_id || !form.vehicle_id)) ||
                        !form.scheduled_at ||
                        !form.estimated_end_at
                    "
                >
                    {{ isEditing ? $t('common.save') : $t('common.create') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { useToast } from '@/Composables/useToast';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const props = defineProps({
    show: { type: Boolean, default: false },
    appointment: { type: Object, default: null },
    appointments: { type: Array, default: () => [] },
    bays: { type: Array, default: () => [] },
    techs: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    prefill: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { success, error } = useToast();
const { scrollToError } = useFormScrollToError();

const busy = ref(false);
const errors = ref({});
const manualEndEdited = ref(false);
const customerList = ref([...props.customers]);
const quickSearchQuery = ref('');
const quickSearchResults = ref([]);
const searchingQuick = ref(false);
let quickSearchTimer = null;

const isEditing = computed(() => Boolean(props.appointment?.id));

const form = reactive({
    customer_id: '',
    vehicle_id: '',
    service_bay_id: '',
    assigned_user_id: '',
    scheduled_at: '',
    estimated_end_at: '',
    service_ids: [],
    notes: '',
});

const DURATION_UNIT_MINUTES = {
    minute: 1,
    minutes: 1,
    hour: 60,
    hours: 60,
    day: 1440,
    days: 1440,
};

function toDateTimeLocal(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

watch(
    () => props.customers,
    (newVal) => {
        if (!Array.isArray(newVal)) return;
        const existingIds = new Set(customerList.value.map((c) => c.id));
        newVal.forEach((c) => {
            if (!existingIds.has(c.id)) customerList.value.push(c);
        });
    },
    { immediate: true }
);

watch(
    () => props.show,
    (open) => {
        if (!open) return;
        manualEndEdited.value = false;
        quickSearchQuery.value = '';
        quickSearchResults.value = [];

        if (props.appointment) {
            form.customer_id = props.appointment.customer?.id || '';
            form.vehicle_id = props.appointment.vehicle?.id || '';
            form.service_bay_id = props.appointment.service_bay?.id || '';
            form.assigned_user_id = props.appointment.assigned_user?.id || '';
            form.scheduled_at = toDateTimeLocal(props.appointment.scheduled_at);
            form.estimated_end_at = toDateTimeLocal(props.appointment.estimated_end_at);
            form.service_ids = Array.isArray(props.appointment.services)
                ? props.appointment.services.map((s) => s.id)
                : [];
            form.notes = props.appointment.notes || '';
        } else {
            form.customer_id = '';
            form.vehicle_id = '';
            form.service_bay_id = props.prefill?.service_bay_id || '';
            form.assigned_user_id = '';
            form.scheduled_at = props.prefill?.scheduled_at || '';
            form.estimated_end_at = '';
            form.service_ids = [];
            form.notes = '';
        }
    }
);

const estimatedDurationMinutes = computed(() => {
    if (!form.service_ids.length) return 0;
    const selectedSet = new Set(form.service_ids.map(Number));
    let total = 0;
    props.services.forEach((s) => {
        if (!selectedSet.has(Number(s.id))) return;
        const val = Number(s.duration_value || 0);
        if (val <= 0) return;
        const mult = DURATION_UNIT_MINUTES[String(s.duration_unit || 'minutes').toLowerCase()] || 1;
        total += val * mult;
    });
    return total > 0 ? total : 60;
});

watch(
    [() => form.scheduled_at, estimatedDurationMinutes],
    ([startVal, mins]) => {
        if (!startVal || manualEndEdited.value) return;
        const start = new Date(startVal);
        if (Number.isNaN(start.getTime())) return;
        const duration = mins > 0 ? mins : 60;
        const end = new Date(start.getTime() + duration * 60000);
        form.estimated_end_at = toDateTimeLocal(end.toISOString());
    }
);

const hasTimeConflict = computed(() => {
    if (!form.scheduled_at || !form.estimated_end_at) return false;
    if (!form.service_bay_id && !form.assigned_user_id) return false;
    const startMs = new Date(form.scheduled_at).getTime();
    const endMs = new Date(form.estimated_end_at).getTime();
    if (Number.isNaN(startMs) || Number.isNaN(endMs) || endMs <= startMs) return false;

    const inactive = new Set(['cancelled', 'no_show', 'converted']);
    return props.appointments.some((appt) => {
        if (props.appointment?.id && Number(appt.id) === Number(props.appointment.id)) return false;
        if (inactive.has(appt.status)) return false;

        const sameBay =
            form.service_bay_id &&
            appt.service_bay?.id &&
            Number(appt.service_bay.id) === Number(form.service_bay_id);
        const sameTech =
            form.assigned_user_id &&
            appt.assigned_user?.id &&
            Number(appt.assigned_user.id) === Number(form.assigned_user_id);

        if (!sameBay && !sameTech) return false;

        const aStart = new Date(appt.scheduled_at).getTime();
        const aEnd = new Date(appt.estimated_end_at).getTime();
        return startMs < aEnd && endMs > aStart;
    });
});

const customerOptions = computed(() =>
    customerList.value.map((c) => ({
        value: c.id,
        label: `${c.name}${c.phone ? ' (' + c.phone + ')' : ''}`,
    }))
);

const selectedCustomer = computed(
    () => customerList.value.find((c) => String(c.id) === String(form.customer_id)) || null
);

const vehicleOptions = computed(() => {
    if (!selectedCustomer.value || !Array.isArray(selectedCustomer.value.vehicles)) return [];
    return selectedCustomer.value.vehicles.map((v) => {
        const carInfo = [v.make?.name, v.model?.name, v.year].filter(Boolean).join(' ');
        const plate = v.plate_number || '';
        return {
            value: v.id,
            label: carInfo ? `${plate} - ${carInfo}` : plate || `#${v.id}`,
            plate,
            makeModel: carInfo,
            raw: v,
        };
    });
});

const selectedVehicle = computed(() => {
    if (!form.vehicle_id || !selectedCustomer.value?.vehicles) return null;
    return (
        selectedCustomer.value.vehicles.find((v) => String(v.id) === String(form.vehicle_id)) ||
        null
    );
});

watch(
    () => form.customer_id,
    (newVal) => {
        if (isEditing.value) return;
        if (!newVal) {
            form.vehicle_id = '';
            return;
        }
        const cust = customerList.value.find((c) => String(c.id) === String(newVal));
        if (cust?.vehicles?.length === 1) {
            form.vehicle_id = cust.vehicles[0].id;
        } else if (!cust?.vehicles?.some((v) => String(v.id) === String(form.vehicle_id))) {
            form.vehicle_id = '';
        }
    }
);

const bayFormOptions = computed(() => [
    { value: '', label: t('appointments.no_bay') },
    ...props.bays.map((b) => ({ value: b.id, label: b.name })),
]);

const techFormOptions = computed(() => [
    { value: '', label: t('appointments.no_tech') },
    ...props.techs.map((item) => ({ value: item.id, label: item.name })),
]);

function onQuickSearchInput() {
    clearTimeout(quickSearchTimer);
    const q = quickSearchQuery.value?.trim();
    if (!q || q.length < 2) {
        quickSearchResults.value = [];
        return;
    }
    quickSearchTimer = setTimeout(async () => {
        searchingQuick.value = true;
        try {
            const res = await axios.get('/app/api/vehicles/search', { params: { q } });
            quickSearchResults.value = Array.isArray(res.data) ? res.data : [];
        } catch (e) {
            console.error('Quick vehicle search error', e);
        } finally {
            searchingQuick.value = false;
        }
    }, 300);
}

function selectQuickVehicle(v) {
    if (v.customer) {
        const existingCust = customerList.value.find((c) => c.id === v.customer.id);
        if (!existingCust) {
            customerList.value.push({ ...v.customer, vehicles: [v] });
        } else {
            if (!Array.isArray(existingCust.vehicles)) existingCust.vehicles = [];
            if (!existingCust.vehicles.some((ev) => ev.id === v.id)) existingCust.vehicles.push(v);
        }
        form.customer_id = v.customer.id;
    }
    form.vehicle_id = v.id;
    quickSearchQuery.value = '';
    quickSearchResults.value = [];
}

async function onCustomerSearch(query) {
    if (!query || query.length < 2) return;
    try {
        const res = await axios.get('/app/api/customers/search', { params: { q: query } });
        if (Array.isArray(res.data)) {
            const existingIds = new Set(customerList.value.map((c) => c.id));
            res.data.forEach((c) => {
                if (!existingIds.has(c.id)) customerList.value.push(c);
            });
        }
    } catch (e) {
        console.error('Customer search error', e);
    }
}

function formatPrice(value) {
    if (value === null || value === undefined) return '';
    try {
        return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2 }).format(value);
    } catch {
        return String(value);
    }
}

async function submit() {
    busy.value = true;
    errors.value = {};
    try {
        if (isEditing.value) {
            await axios.put(route('appointments.update', { appointment: props.appointment.id }), {
                service_bay_id: form.service_bay_id ? Number(form.service_bay_id) : null,
                assigned_user_id: form.assigned_user_id ? Number(form.assigned_user_id) : null,
                scheduled_at: form.scheduled_at,
                estimated_end_at: form.estimated_end_at,
                service_ids: form.service_ids.map(Number),
                notes: form.notes || null,
            });
            success(t('common.updated_success'));
        } else {
            await axios.post(route('appointments.store'), {
                ...form,
                customer_id: Number(form.customer_id),
                vehicle_id: Number(form.vehicle_id),
                service_bay_id: form.service_bay_id ? Number(form.service_bay_id) : null,
                assigned_user_id: form.assigned_user_id ? Number(form.assigned_user_id) : null,
                service_ids: form.service_ids.map(Number),
            });
            success(t('common.saved_success'));
        }
        emit('saved');
        emit('close');
    } catch (e) {
        if (e.response?.status === 422 && e.response?.data?.errors) {
            errors.value = e.response.data.errors;
            scrollToError(errors.value);
        }
        if (e.response?.data?.code) {
            error(
                t(`messages.${e.response.data.code}`) +
                    (e.response.data.error ? ': ' + e.response.data.error : '')
            );
        } else {
            error(e.response?.data?.message || e.message);
        }
    } finally {
        busy.value = false;
    }
}
</script>
