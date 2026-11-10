<template>
    <AppLayout>
        <Head :title="$t('appointments.title')" />

        <div class="space-y-6">
            <PageHeader
                :title="$t('appointments.title')"
                :subtitle="$t('appointments.subtitle')"
                gradientFrom="from-emerald-600"
                gradientTo="to-teal-600"
                glowFrom="from-emerald-500"
                :total-count="appointments.length"
                :count-label="$t('appointments.total_count')"
            >
                <template #icon>
                    <svg
                        class="w-9 h-9 text-white"
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
                    <Link
                        v-if="can('crm.appointments.manage')"
                        :href="route('service-bays.index', { from: 'appointments' })"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow transition-all flex items-center gap-2"
                    >
                        <svg
                            class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
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
                        {{ $t('service_bays.title') }}
                    </Link>
                    <button
                        type="button"
                        @click="openCreate"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-md shadow-emerald-500/20 hover:shadow-lg hover:shadow-emerald-500/30 transition-all flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('appointments.new') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Filters -->
            <div
                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 flex flex-wrap items-end gap-3"
            >
                <div>
                    <label
                        class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('appointments.filter_date') }}
                    </label>
                    <input
                        v-model="filters.date"
                        type="date"
                        @change="reload"
                        class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-1.5 text-sm"
                    />
                </div>
                <div class="min-w-[140px]">
                    <label
                        class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('appointments.filter_status') }}
                    </label>
                    <SearchableSelect
                        v-model="filters.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.all')"
                        :label="''"
                        :compact="true"
                        @change="reload"
                    />
                </div>
                <div class="min-w-[150px]">
                    <label
                        class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('appointments.filter_bay') }}
                    </label>
                    <SearchableSelect
                        v-model="filters.service_bay_id"
                        :options="bayOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.all')"
                        :label="''"
                        :compact="true"
                        @change="reload"
                    />
                </div>
                <div class="min-w-[150px]">
                    <label
                        class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('appointments.filter_tech') }}
                    </label>
                    <SearchableSelect
                        v-model="filters.assigned_user_id"
                        :options="techOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="$t('appointments.all')"
                        :label="''"
                        :compact="true"
                        @change="reload"
                    />
                </div>
                <button
                    type="button"
                    @click="clearFilters"
                    class="px-3 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/80 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                >
                    {{ $t('appointments.clear_filters') }}
                </button>
            </div>

            <!-- Timeline list (simple grouped-by-time view) -->
            <div
                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden"
            >
                <div v-if="!appointments.length" class="p-8 text-center text-sm text-gray-400">
                    {{ $t('appointments.empty') }}
                </div>
                <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li
                        v-for="appt in appointments"
                        :key="appt.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-900/30"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold',
                                            statusClass(appt.status),
                                        ]"
                                    >
                                        {{ $t(`appointments.status.${appt.status}`) }}
                                    </span>
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                        dir="ltr"
                                    >
                                        {{ formatTime(appt.scheduled_at) }} →
                                        {{ formatTime(appt.estimated_end_at) }}
                                    </span>
                                    <span
                                        v-if="appt.service_bay"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                                    >
                                        {{ appt.service_bay.name }}
                                    </span>
                                    <span
                                        v-if="appt.assigned_user"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                    >
                                        {{ appt.assigned_user.name }}
                                    </span>
                                </div>
                                <h4
                                    class="mt-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    {{ appt.customer?.name || '—' }}
                                    <SaudiPlateDisplay
                                        v-if="appt.vehicle?.plate_number"
                                        :plate-number="appt.vehicle.plate_number"
                                        size="sm"
                                        class="ms-2 align-middle inline-flex"
                                    />
                                </h4>
                                <p
                                    v-if="appt.services?.length"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                >
                                    {{ appt.services.map((s) => s.name).join('، ') }}
                                </p>
                                <p
                                    v-if="appt.notes"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1"
                                >
                                    {{ appt.notes }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button
                                    v-if="
                                        appt.status === 'scheduled' || appt.status === 'confirmed'
                                    "
                                    type="button"
                                    @click="checkIn(appt)"
                                    :disabled="busy"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm disabled:opacity-50 transition-colors"
                                >
                                    {{ $t('appointments.check_in') }}
                                </button>
                                <button
                                    v-if="canMarkNoShow(appt)"
                                    type="button"
                                    @click="markNoShow(appt)"
                                    :disabled="busy"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white shadow-sm disabled:opacity-50 transition-colors"
                                >
                                    {{ $t('appointments.no_show') }}
                                </button>
                                <button
                                    v-if="
                                        appt.status === 'scheduled' ||
                                        appt.status === 'confirmed' ||
                                        appt.status === 'checked_in'
                                    "
                                    type="button"
                                    @click="convert(appt)"
                                    :disabled="busy"
                                    class="btn-primary !text-xs !py-1.5"
                                >
                                    {{ $t('appointments.convert') }}
                                </button>
                                <button
                                    v-if="
                                        appt.status !== 'cancelled' && appt.status !== 'converted'
                                    "
                                    type="button"
                                    @click="openCancel(appt)"
                                    :disabled="busy"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white shadow-sm disabled:opacity-50 transition-colors"
                                >
                                    {{ $t('appointments.cancel') }}
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Create / edit modal -->
        <div
            v-if="showForm"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="showForm = false"
        >
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-5 space-y-3"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                    {{ $t('appointments.new') }}
                </h3>
                <!-- Quick Vehicle/Customer Search -->
                <div class="relative">
                    <div class="relative">
                        <input
                            v-model="quickSearchQuery"
                            type="text"
                            @input="onQuickSearchInput"
                            :placeholder="$t('appointments.search_vehicle_or_customer')"
                            class="w-full px-3 py-2 ps-9 text-xs rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 focus:bg-white dark:focus:bg-gray-800 transition"
                        />
                        <svg
                            class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <span
                            v-if="searchingQuick"
                            class="absolute end-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 animate-pulse"
                        >
                            ...
                        </span>
                    </div>

                    <!-- Quick search dropdown -->
                    <div
                        v-if="quickSearchResults.length > 0"
                        class="absolute z-20 start-0 end-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-48 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700"
                    >
                        <button
                            v-for="v in quickSearchResults"
                            :key="v.id"
                            type="button"
                            @click="selectQuickVehicle(v)"
                            class="w-full text-start p-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center justify-between transition text-xs"
                        >
                            <div class="flex items-center gap-2">
                                <SaudiPlateDisplay :plate-number="v.plate_number" size="sm" />
                                <div>
                                    <div class="font-medium text-gray-800 dark:text-gray-200">
                                        {{
                                            [v.make?.name, v.model?.name, v.year]
                                                .filter(Boolean)
                                                .join(' ')
                                        }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ v.customer?.name }}
                                        {{ v.customer?.phone ? '(' + v.customer.phone + ')' : '' }}
                                    </div>
                                </div>
                            </div>
                            <span
                                class="text-[10px] text-primary-600 dark:text-primary-400 font-bold"
                            >
                                {{ $t('common.select') }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('appointments.customer') }}
                        </label>
                        <SearchableSelect
                            v-model="form.customer_id"
                            :options="customerOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('appointments.customer_placeholder')"
                            :search-placeholder="$t('common.search')"
                            :label="''"
                            @search="onCustomerSearch"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('appointments.vehicle') }}
                        </label>
                        <SearchableSelect
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
                    </div>

                    <!-- Selected Vehicle Banner -->
                    <div
                        v-if="selectedVehicle"
                        class="col-span-1 md:col-span-2 bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800/50 rounded-lg p-2.5 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <SaudiPlateDisplay
                                :plate-number="selectedVehicle.plate_number"
                                size="sm"
                            />
                            <div class="text-xs">
                                <span class="font-bold text-gray-800 dark:text-gray-200">
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
                                <span
                                    v-if="selectedCustomer"
                                    class="text-gray-500 dark:text-gray-400 ms-2"
                                >
                                    ({{ selectedCustomer.name }})
                                </span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('appointments.bay') }}
                        </label>
                        <SearchableSelect
                            v-model="form.service_bay_id"
                            :options="bayFormOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('appointments.no_bay')"
                            :label="''"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('appointments.tech') }}
                        </label>
                        <SearchableSelect
                            v-model="form.assigned_user_id"
                            :options="techFormOptions"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('appointments.no_tech')"
                            :label="''"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('appointments.scheduled_at') }}
                        </label>
                        <input
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                        >
                            {{ $t('appointments.estimated_end_at') }}
                        </label>
                        <input
                            v-model="form.estimated_end_at"
                            type="datetime-local"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                        />
                    </div>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('appointments.services') }}
                    </label>
                    <div
                        class="max-h-32 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md p-2 space-y-1"
                    >
                        <label
                            v-for="s in services"
                            :key="s.id"
                            class="flex items-center gap-2 text-xs"
                        >
                            <input
                                type="checkbox"
                                :value="s.id"
                                v-model="form.service_ids"
                                class="rounded"
                            />
                            <span class="flex-1">{{ s.name }}</span>
                            <span class="text-gray-400">{{ formatPrice(s.base_price) }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ $t('appointments.notes') }}
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                    ></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showForm = false"
                        class="px-3 py-1.5 rounded-md text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="create"
                        :disabled="
                            busy ||
                            !form.customer_id ||
                            !form.vehicle_id ||
                            !form.scheduled_at ||
                            !form.estimated_end_at
                        "
                        class="btn-primary !text-xs !py-1.5"
                    >
                        {{ $t('common.create') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Cancel modal -->
        <div
            v-if="showCancel"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            @click.self="showCancel = false"
        >
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md p-5 space-y-3"
            >
                <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                    {{ $t('appointments.cancel_title') }}
                </h3>
                <textarea
                    v-model="cancelReason"
                    rows="3"
                    :placeholder="$t('appointments.cancel_reason_placeholder')"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                ></textarea>
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showCancel = false"
                        class="px-3 py-1.5 rounded-md text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="confirmCancel"
                        :disabled="busy"
                        class="px-3 py-1.5 rounded-md text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white disabled:opacity-50"
                    >
                        {{ $t('appointments.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { usePermission } from '@/Composables/usePermission';
import { canMarkNoShow } from '@/Utils/appointments';

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    bays: { type: Array, default: () => [] },
    techs: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const { t } = useI18n();
const { can } = usePermission();
const busy = ref(false);
const showForm = ref(false);
const showCancel = ref(false);
const cancelReason = ref('');
const cancellingAppointment = ref(null);

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

const customerList = ref([...props.customers]);
const searchingCustomers = ref(false);

watch(
    () => props.customers,
    (newVal) => {
        if (Array.isArray(newVal)) {
            const existingIds = new Set(customerList.value.map((c) => c.id));
            newVal.forEach((c) => {
                if (!existingIds.has(c.id)) {
                    customerList.value.push(c);
                }
            });
        }
    },
    { immediate: true }
);

const customerOptions = computed(() => {
    return customerList.value.map((c) => ({
        value: c.id,
        label: `${c.name}${c.phone ? ' (' + c.phone + ')' : ''}`,
    }));
});

const selectedCustomer = computed(() => {
    return customerList.value.find((c) => String(c.id) === String(form.customer_id)) || null;
});

const vehicleOptions = computed(() => {
    if (!selectedCustomer.value || !Array.isArray(selectedCustomer.value.vehicles)) return [];
    return selectedCustomer.value.vehicles.map((v) => {
        const makeName = v.make?.name || '';
        const modelName = v.model?.name || '';
        const carInfo = [makeName, modelName, v.year].filter(Boolean).join(' ');
        const plate = v.plate_number || '';
        return {
            value: v.id,
            label: carInfo ? `${plate} - ${carInfo}` : plate || `#${v.id}`,
            plate: plate,
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

// Quick vehicle search
const quickSearchQuery = ref('');
const quickSearchResults = ref([]);
const searchingQuick = ref(false);
let quickSearchTimer = null;

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
            customerList.value.push({
                ...v.customer,
                vehicles: [v],
            });
        } else {
            if (!Array.isArray(existingCust.vehicles)) {
                existingCust.vehicles = [];
            }
            if (!existingCust.vehicles.some((ev) => ev.id === v.id)) {
                existingCust.vehicles.push(v);
            }
        }
        form.customer_id = v.customer.id;
    }
    form.vehicle_id = v.id;
    quickSearchQuery.value = '';
    quickSearchResults.value = [];
}

async function onCustomerSearch(query) {
    if (!query || query.length < 2) return;
    searchingCustomers.value = true;
    try {
        const res = await axios.get('/app/api/customers/search', { params: { q: query } });
        if (Array.isArray(res.data)) {
            const existingIds = new Set(customerList.value.map((c) => c.id));
            res.data.forEach((c) => {
                if (!existingIds.has(c.id)) {
                    customerList.value.push(c);
                }
            });
        }
    } catch (e) {
        console.error('Customer search error', e);
    } finally {
        searchingCustomers.value = false;
    }
}

const filters = reactive({ ...props.filters });

const statusOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.statuses.map((s) => ({ value: s, label: t(`appointments.status.${s}`) })),
]);

const bayOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.bays.map((b) => ({ value: b.id, label: b.name })),
]);

const bayFormOptions = computed(() => [
    { value: '', label: t('appointments.no_bay') },
    ...props.bays.map((b) => ({ value: b.id, label: b.name })),
]);

const techOptions = computed(() => [
    { value: '', label: t('appointments.all') },
    ...props.techs.map((t) => ({ value: t.id, label: t.name })),
]);

const techFormOptions = computed(() => [
    { value: '', label: t('appointments.no_tech') },
    ...props.techs.map((t) => ({ value: t.id, label: t.name })),
]);

function reload() {
    const params = {};
    if (filters.date) params.date = filters.date;
    if (filters.status) params.status = filters.status;
    if (filters.service_bay_id) params.service_bay_id = filters.service_bay_id;
    if (filters.assigned_user_id) params.assigned_user_id = filters.assigned_user_id;
    router.get(route('appointments.index'), params, { preserveState: true });
}

function clearFilters() {
    filters.date = '';
    filters.status = '';
    filters.service_bay_id = '';
    filters.assigned_user_id = '';
    reload();
}

function openCreate() {
    form.customer_id = '';
    form.vehicle_id = '';
    form.service_bay_id = '';
    form.assigned_user_id = '';
    form.scheduled_at = '';
    form.estimated_end_at = '';
    form.service_ids = [];
    form.notes = '';
    quickSearchQuery.value = '';
    quickSearchResults.value = [];
    showForm.value = true;
}

async function create() {
    busy.value = true;
    try {
        await axios.post(route('appointments.store'), {
            ...form,
            customer_id: Number(form.customer_id),
            vehicle_id: Number(form.vehicle_id),
            service_bay_id: form.service_bay_id ? Number(form.service_bay_id) : null,
            assigned_user_id: form.assigned_user_id ? Number(form.assigned_user_id) : null,
            service_ids: form.service_ids.map(Number),
        });
        showForm.value = false;
        router.reload();
    } catch (e) {
        if (e.response?.data?.code) {
            alert(t(`messages.${e.response.data.code}`) + ': ' + (e.response.data.error || ''));
        } else {
            alert(e.message);
        }
    } finally {
        busy.value = false;
    }
}

async function checkIn(appt) {
    busy.value = true;
    try {
        await axios.post(route('appointments.check-in', { appointment: appt.id }));
        router.reload();
    } finally {
        busy.value = false;
    }
}

async function markNoShow(appt) {
    if (!confirm(t('appointments.confirm_no_show'))) return;
    busy.value = true;
    try {
        await axios.post(route('appointments.no-show', { appointment: appt.id }));
        router.reload();
    } catch (e) {
        // The server refuses the transition from a state that is already
        // past the point of no return. Say so, instead of leaving the
        // button looking broken.
        if (e.response?.data?.code) {
            alert(t(`messages.${e.response.data.code}`) + ': ' + (e.response.data.error || ''));
        } else {
            alert(e.message);
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
            {
                reason: cancelReason.value,
            }
        );
        showCancel.value = false;
        router.reload();
    } finally {
        busy.value = false;
    }
}

async function convert(appt) {
    if (!confirm(t('appointments.confirm_convert'))) return;
    busy.value = true;
    try {
        const res = await axios.post(route('appointments.convert', { appointment: appt.id }));
        const woId = res.data?.payload?.work_order_id;
        if (woId) {
            window.location.href = route('work-orders.show', { workOrder: woId });
        } else {
            router.reload();
        }
    } catch (e) {
        alert(t(`messages.${e.response?.data?.code}`) || e.message);
    } finally {
        busy.value = false;
    }
}

function statusClass(status) {
    const map = {
        scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        confirmed: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
        checked_in: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        no_show: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
        converted: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
}

function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    try {
        return new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit' }).format(d);
    } catch (e) {
        return iso;
    }
}

function formatPrice(value) {
    if (value === null || value === undefined) return '';
    try {
        return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2 }).format(value);
    } catch (e) {
        return String(value);
    }
}
</script>
