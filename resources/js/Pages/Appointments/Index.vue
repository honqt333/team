<template>
    <AppLayout>
        <Head :title="$t('appointments.title')" />

        <div class="space-y-6">
            <PageHeader
                :title="$t('appointments.title')"
                :subtitle="$t('appointments.subtitle')"
                :total-count="toEnglish(totalAppointmentsCount)"
                :count-label="$t('appointments.total_count')"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Glass Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/80 dark:bg-surface-secondary/40 backdrop-blur-md rounded-2xl border border-border-subtle shadow-inner"
                        >
                            <Link
                                v-if="can('crm.appointments.manage')"
                                :href="route('services.index', { tab: 'bays' })"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold text-content-secondary hover:text-action-primary hover:bg-surface-primary transition-colors"
                            >
                                <svg
                                    class="w-4 h-4 text-action-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                    />
                                </svg>
                                <span>{{ $t('service_bays.title') }}</span>
                            </Link>

                            <AppPrintButton variant="icon" @click="printSchedule" />

                            <div class="w-px h-5 bg-border-subtle mx-1"></div>

                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="appointments_view_mode"
                            />

                            <button
                                type="button"
                                @click="viewMode = viewMode === 'timeline' ? 'list' : 'timeline'"
                                :title="$t('appointments.timeline_view')"
                                class="p-1.5 rounded-lg transition-colors cursor-pointer"
                                :class="
                                    viewMode === 'timeline'
                                        ? 'bg-action-primary text-white shadow-xs'
                                        : 'text-content-secondary hover:text-action-primary hover:bg-surface-primary'
                                "
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 10h16M4 14h10M4 18h6"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Outer-left CTA -->
                        <AppButton
                            v-if="can('crm.appointments.manage')"
                            @click="openCreate"
                            variant="primary"
                            size="md"
                            class="shadow-xs"
                        >
                            <template #iconLeft>
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                            </template>
                            {{ $t('appointments.new') }}
                        </AppButton>
                    </div>
                </template>

                <template #filters>
                    <AppointmentFiltersBar
                        :filters="filters"
                        :status-options="statusOptions"
                        :bay-options="bayOptions"
                        :tech-options="techOptions"
                        @reload="reload"
                        @clear="clearFilters"
                    />
                </template>
            </PageHeader>

            <!-- Empty State (List & Grid modes only; Timeline shows empty bays grid) -->
            <AppEmptyState
                v-if="!appointmentList.length && viewMode !== 'timeline'"
                :title="$t('appointments.empty')"
            >
                <template #actions>
                    <AppButton
                        v-if="can('crm.appointments.manage')"
                        @click="openCreate"
                        variant="primary"
                        size="md"
                        class="shadow-xs"
                    >
                        <template #iconLeft>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </template>
                        {{ $t('appointments.new') }}
                    </AppButton>
                </template>
            </AppEmptyState>

            <!-- Content Views -->
            <template v-else>
                <!-- Table View -->
                <DataTable v-if="viewMode === 'list'" :items="appointments" :columns="columns">
                    <template #cell-status="{ item }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold',
                                statusClass(item.status),
                            ]"
                        >
                            {{ $t(`appointments.status.${item.status}`) }}
                        </span>
                    </template>

                    <template #cell-scheduled_at="{ item }">
                        <div class="text-xs font-medium text-content-primary" dir="ltr">
                            {{ formatTime(item.scheduled_at) }} →
                            {{ formatTime(item.estimated_end_at) }}
                        </div>
                    </template>

                    <template #cell-customer="{ item }">
                        <div class="font-medium text-content-primary">
                            {{ item.customer?.name || '—' }}
                        </div>
                        <div
                            v-if="item.customer?.phone"
                            class="text-xs text-content-muted"
                            dir="ltr"
                        >
                            {{ item.customer.phone }}
                        </div>
                    </template>

                    <template #cell-vehicle="{ item }">
                        <SaudiPlateDisplay
                            v-if="item.vehicle?.plate_number"
                            :plate-number="item.vehicle.plate_number"
                            size="sm"
                        />
                        <span v-else class="text-xs text-content-muted">—</span>
                    </template>

                    <template #cell-bay_tech="{ item }">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                v-if="item.service_bay"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-info-soft text-info"
                            >
                                {{ item.service_bay.name }}
                            </span>
                            <span
                                v-if="item.assigned_user"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-action-primary/10 text-action-primary"
                            >
                                {{ item.assigned_user.name }}
                            </span>
                            <span
                                v-if="!item.service_bay && !item.assigned_user"
                                class="text-xs text-content-muted"
                            >
                                —
                            </span>
                        </div>
                    </template>

                    <template #cell-services="{ item }">
                        <div class="max-w-xs">
                            <p
                                v-if="item.services?.length"
                                class="text-xs text-content-primary truncate"
                            >
                                {{ item.services.map((s) => s.name).join('، ') }}
                            </p>
                            <p v-if="item.notes" class="text-[11px] text-content-muted truncate">
                                {{ item.notes }}
                            </p>
                            <span
                                v-if="!item.services?.length && !item.notes"
                                class="text-xs text-content-muted"
                            >
                                —
                            </span>
                        </div>
                    </template>

                    <template #cell-actions="{ item }">
                        <div
                            v-if="can('crm.appointments.manage')"
                            class="flex items-center justify-center gap-1"
                        >
                            <!-- Primary Next-Step Action -->
                            <AppButton
                                v-if="item.status === 'scheduled' || item.status === 'confirmed'"
                                type="button"
                                size="xs"
                                variant="success"
                                :disabled="busy"
                                @click="checkIn(item)"
                            >
                                {{ $t('appointments.check_in') }}
                            </AppButton>
                            <AppButton
                                v-else-if="item.status === 'checked_in'"
                                type="button"
                                size="xs"
                                variant="primary"
                                :disabled="busy"
                                @click="convert(item)"
                            >
                                {{ $t('appointments.convert') }}
                            </AppButton>

                            <!-- Secondary Icon Actions -->
                            <button
                                v-if="item.status === 'scheduled' || item.status === 'confirmed'"
                                type="button"
                                :title="$t('appointments.convert')"
                                :disabled="busy"
                                @click="convert(item)"
                                class="p-1.5 rounded-lg text-action-primary hover:bg-action-primary/10 transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                                    />
                                </svg>
                            </button>

                            <button
                                v-if="canMarkNoShow(item)"
                                type="button"
                                :title="$t('appointments.no_show')"
                                :disabled="busy"
                                @click="markNoShow(item)"
                                class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-500/10 transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </button>

                            <button
                                v-if="item.status !== 'cancelled' && item.status !== 'converted'"
                                type="button"
                                :title="$t('common.edit')"
                                :disabled="busy"
                                @click="openEdit(item)"
                                class="p-1.5 rounded-lg text-content-secondary hover:text-action-primary hover:bg-surface-secondary transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </button>

                            <button
                                v-if="item.status !== 'cancelled' && item.status !== 'converted'"
                                type="button"
                                :title="$t('appointments.cancel')"
                                :disabled="busy"
                                @click="openCancel(item)"
                                class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-500/10 transition-colors cursor-pointer disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </template>
                </DataTable>

                <!-- Timeline / Calendar View -->
                <AppointmentTimelineView
                    v-else-if="viewMode === 'timeline'"
                    :appointments="appointmentList"
                    :bays="bays"
                    :status-class="statusClass"
                    :format-time="formatTime"
                    :selected-date="filters.date || ''"
                    :can-create="can('crm.appointments.manage')"
                    @edit="openEdit"
                    @create-at="openCreateAt"
                    @range-change="onCalendarRangeChange"
                />

                <!-- Card Grid View (Grid Mode) -->
                <div v-else class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="appt in appointmentList"
                            :key="appt.id"
                        class="flex flex-col justify-between rounded-2xl border border-border-subtle bg-surface-primary p-4 shadow-2xs hover:shadow-md hover:border-border transition-all duration-200 space-y-3"
                    >
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold',
                                        statusClass(appt.status),
                                    ]"
                                >
                                    {{ $t(`appointments.status.${appt.status}`) }}
                                </span>
                                <span
                                    class="text-xs text-content-muted font-medium"
                                    dir="ltr"
                                >
                                    {{ formatTime(appt.scheduled_at) }} →
                                    {{ formatTime(appt.estimated_end_at) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div>
                                    <h4 class="text-sm font-bold text-content-primary">
                                        {{ appt.customer?.name || '—' }}
                                    </h4>
                                    <p
                                        v-if="appt.customer?.phone"
                                        class="text-xs text-content-muted"
                                        dir="ltr"
                                    >
                                        {{ appt.customer.phone }}
                                    </p>
                                </div>
                                <SaudiPlateDisplay
                                    v-if="appt.vehicle?.plate_number"
                                    :plate-number="appt.vehicle.plate_number"
                                    size="sm"
                                />
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    v-if="appt.service_bay"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-info-soft text-info"
                                >
                                    {{ appt.service_bay.name }}
                                </span>
                                <span
                                    v-if="appt.assigned_user"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-action-primary/10 text-action-primary"
                                >
                                    {{ appt.assigned_user.name }}
                                </span>
                            </div>

                            <p
                                v-if="appt.services?.length"
                                class="text-xs text-content-secondary line-clamp-2"
                            >
                                {{ appt.services.map((s) => s.name).join('، ') }}
                            </p>
                            <p
                                v-if="appt.notes"
                                class="text-xs text-content-muted line-clamp-1"
                            >
                                {{ appt.notes }}
                            </p>
                        </div>

                        <div
                            v-if="can('crm.appointments.manage')"
                            class="pt-3 border-t border-border-subtle flex flex-wrap items-center justify-end gap-1.5"
                        >
                            <AppButton
                                v-if="
                                    appt.status === 'scheduled' ||
                                    appt.status === 'confirmed'
                                "
                                type="button"
                                size="xs"
                                variant="success"
                                :disabled="busy"
                                @click="checkIn(appt)"
                            >
                                {{ $t('appointments.check_in') }}
                            </AppButton>
                            <AppButton
                                v-if="canMarkNoShow(appt)"
                                type="button"
                                size="xs"
                                variant="warning"
                                :disabled="busy"
                                @click="markNoShow(appt)"
                            >
                                {{ $t('appointments.no_show') }}
                            </AppButton>
                            <AppButton
                                v-if="
                                    appt.status !== 'cancelled' &&
                                    appt.status !== 'converted'
                                "
                                type="button"
                                size="xs"
                                variant="secondary"
                                :disabled="busy"
                                @click="openEdit(appt)"
                            >
                                {{ $t('common.edit') }}
                            </AppButton>
                            <AppButton
                                v-if="
                                    appt.status === 'scheduled' ||
                                    appt.status === 'confirmed' ||
                                    appt.status === 'checked_in'
                                "
                                type="button"
                                size="xs"
                                variant="primary"
                                :disabled="busy"
                                @click="convert(appt)"
                            >
                                {{ $t('appointments.convert') }}
                            </AppButton>
                            <AppButton
                                v-if="
                                    appt.status !== 'cancelled' &&
                                    appt.status !== 'converted'
                                "
                                type="button"
                                size="xs"
                                variant="danger"
                                :disabled="busy"
                                @click="openCancel(appt)"
                            >
                                {{ $t('appointments.cancel') }}
                            </AppButton>
                        </div>
                    </div>
                    </div>

                    <AppPagination
                        v-if="appointments?.links?.length >= 3"
                        :links="appointments.links"
                        :from="appointments.from"
                        :to="appointments.to"
                        :total="appointments.total"
                    />
                </div>
            </template>
        </div>

        <!-- Create / Edit Modal -->
        <AppointmentFormModal
            :show="showForm"
            :appointment="editingAppointment"
            :appointments="appointmentList"
            :bays="bays"
            :techs="techs"
            :services="services"
            :customers="customers"
            :prefill="createPrefill"
            @close="showForm = false"
            @saved="reloadPartial"
        />

        <!-- Cancel Modal -->
        <BaseModal
            :show="showCancel"
            size="sm"
            :title="$t('appointments.cancel_title')"
            @close="showCancel = false"
        >
            <form id="cancel-appointment-form" @submit.prevent="confirmCancel">
                <FormField v-slot="field" :label="$t('appointments.cancel_title')">
                    <AppTextarea
                        :id="field.id"
                        v-model="cancelReason"
                        :rows="3"
                        :placeholder="$t('appointments.cancel_reason_placeholder')"
                    />
                </FormField>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-2 w-full">
                    <AppButton type="button" variant="secondary" @click="showCancel = false">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="submit"
                        form="cancel-appointment-form"
                        variant="danger"
                        :loading="busy"
                    >
                        {{ $t('appointments.cancel') }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import FormField from '@/Components/Common/FormField.vue';
import BaseModal from '@/Components/BaseModal.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import AppointmentFiltersBar from '@/Components/Appointments/AppointmentFiltersBar.vue';
import AppointmentFormModal from '@/Components/Appointments/AppointmentFormModal.vue';
import AppointmentTimelineView from '@/Components/Appointments/AppointmentTimelineView.vue';
import { usePermission } from '@/Composables/usePermission';
import { useAutoOpenCreate } from '@/Composables/useAutoOpenCreate';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { canMarkNoShow } from '@/Utils/appointments';

const props = defineProps({
    appointments: { type: [Array, Object], default: () => [] },
    bays: { type: Array, default: () => [] },
    techs: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const appointmentList = computed(() =>
    Array.isArray(props.appointments) ? props.appointments : props.appointments?.data || []
);

const totalAppointmentsCount = computed(() =>
    Array.isArray(props.appointments)
        ? props.appointments.length
        : (props.appointments?.total ?? appointmentList.value.length)
);

const { t } = useI18n();
const { can } = usePermission();
const { confirm } = useConfirm();
const { success, error } = useToast();
const { toEnglish } = useNumberFormat();

const viewMode = ref('list');
const busy = ref(false);
const showForm = ref(false);
const editingAppointment = ref(null);
const createPrefill = ref(null);
const showCancel = ref(false);
const cancelReason = ref('');
const cancellingAppointment = ref(null);

useAutoOpenCreate(() => openCreate());

const columns = computed(() => [
    { key: 'status', label: t('appointments.filter_status'), class: 'w-28' },
    { key: 'scheduled_at', label: t('appointments.scheduled_at') },
    { key: 'customer', label: t('appointments.customer') },
    { key: 'vehicle', label: t('appointments.vehicle') },
    { key: 'bay_tech', label: `${t('appointments.bay')} / ${t('appointments.tech')}` },
    { key: 'services', label: t('appointments.services') },
    { key: 'actions', label: t('common.actions'), class: 'text-center' },
]);

const filters = reactive({ ...props.filters });

const statusOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.statuses.map((s) => ({ value: s, label: t(`appointments.status.${s}`) })),
]);

const bayOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.bays.map((b) => ({ value: b.id, label: b.name })),
]);

const techOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.techs.map((item) => ({ value: item.id, label: item.name })),
]);

function reload() {
    const params = {};
    if (viewMode.value === 'timeline') params.view = 'timeline';
    if (filters.search) params.search = filters.search;
    if (filters.date) params.date = filters.date;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    if (filters.status) params.status = filters.status;
    if (filters.service_bay_id) params.service_bay_id = filters.service_bay_id;
    if (filters.assigned_user_id) params.assigned_user_id = filters.assigned_user_id;
    router.get(route('appointments.index'), params, {
        preserveState: true,
        only: ['appointments', 'filters'],
    });
}

watch(viewMode, (newMode, oldMode) => {
    if (newMode === 'timeline' || oldMode === 'timeline') {
        reload();
    }
});

function reloadPartial() {
    router.reload({ only: ['appointments'] });
}

function clearFilters() {
    filters.search = '';
    filters.date = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.status = '';
    filters.service_bay_id = '';
    filters.assigned_user_id = '';
    reload();
}

function printSchedule() {
    window.print();
}

function openCreate() {
    editingAppointment.value = null;
    createPrefill.value = null;
    showForm.value = true;
}

function openCreateAt(payload) {
    editingAppointment.value = null;
    createPrefill.value = payload || null;
    showForm.value = true;
}

function onCalendarRangeChange(range) {
    filters.date = range.date || '';
    filters.date_from = range.date_from || '';
    filters.date_to = range.date_to || '';
    reload();
}

function openEdit(appt) {
    editingAppointment.value = appt;
    createPrefill.value = null;
    showForm.value = true;
}

async function checkIn(appt) {
    busy.value = true;
    try {
        await axios.post(route('appointments.check-in', { appointment: appt.id }));
        success(t('common.updated_success'));
        reloadPartial();
    } catch (e) {
        error(e.response?.data?.error || e.message);
    } finally {
        busy.value = false;
    }
}

async function markNoShow(appt) {
    const confirmed = await confirm({
        title: t('appointments.no_show'),
        message: t('appointments.confirm_no_show'),
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });
    if (!confirmed) return;

    busy.value = true;
    try {
        await axios.post(route('appointments.no-show', { appointment: appt.id }));
        success(t('common.updated_success'));
        reloadPartial();
    } catch (e) {
        if (e.response?.data?.code) {
            error(
                t(`messages.${e.response.data.code}`) +
                    (e.response.data.error ? ': ' + e.response.data.error : '')
            );
        } else {
            error(e.message);
        }
    } finally {
        busy.value = false;
    }
}

function openCancel(appt) {
    cancellingAppointment.value = appt;
    cancelReason.value = '';
    showCancel.value = true;
}

async function confirmCancel() {
    if (!cancellingAppointment.value) return;
    busy.value = true;
    try {
        await axios.post(
            route('appointments.cancel', { appointment: cancellingAppointment.value.id }),
            { reason: cancelReason.value }
        );
        showCancel.value = false;
        success(t('common.updated_success'));
        reloadPartial();
    } catch (e) {
        error(e.response?.data?.error || e.message);
    } finally {
        busy.value = false;
    }
}

async function convert(appt) {
    const confirmed = await confirm({
        title: t('appointments.convert'),
        message: t('appointments.confirm_convert'),
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
        type: 'primary',
    });
    if (!confirmed) return;

    busy.value = true;
    try {
        const res = await axios.post(route('appointments.convert', { appointment: appt.id }));
        const woId = res.data?.payload?.work_order_id;
        if (woId) {
            router.visit(route('work-orders.show', { workOrder: woId }));
        } else {
            reloadPartial();
        }
    } catch (e) {
        const code = e.response?.data?.code;
        error(code ? t(`messages.${code}`) : e.message);
    } finally {
        busy.value = false;
    }
}

function statusClass(status) {
    const map = {
        scheduled: 'bg-info-soft text-info',
        confirmed: 'bg-action-primary/10 text-action-primary',
        checked_in: 'bg-success-soft text-success',
        no_show: 'bg-warning-soft text-warning',
        cancelled: 'bg-danger-soft text-danger',
        converted: 'bg-surface-tertiary text-content-secondary',
    };
    return map[status] || 'bg-surface-tertiary text-content-secondary';
}

function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    try {
        return new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' }).format(d);
    } catch {
        return iso;
    }
}
</script>
