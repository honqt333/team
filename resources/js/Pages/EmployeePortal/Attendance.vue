<template>
    <EmployeePortalLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Title -->
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-600 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/30"
                        >
                            <svg
                                class="w-6 h-6 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $t('employee_portal.my_attendance') }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ formatMonthName(month) }}
                            </p>
                        </div>
                    </div>

                    <!-- Month Selector -->
                    <input
                        type="month"
                        v-model="selectedMonth"
                        @change="changeMonth"
                        class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent transition-all"
                    />
                </div>
            </div>

            <!-- Smart Check-In / Check-Out & Correction Request Bar -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
            >
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $t('hr.attendance.today_status') }}:
                        </span>
                        <span
                            class="font-mono text-xs px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                        >
                            {{ $t('hr.attendance.check_in') }}:
                            {{ todayAttendance?.check_in || '—' }}
                        </span>
                        <span
                            class="font-mono text-xs px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200"
                        >
                            {{ $t('hr.attendance.check_out') }}:
                            {{ todayAttendance?.check_out || '—' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{
                            geofence?.enabled
                                ? $t('hr.attendance.geofence_active', {
                                      radius: geofence.radius_meters,
                                  })
                                : $t('hr.attendance.geofence_disabled')
                        }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <AppButton
                        v-if="!todayAttendance?.check_in"
                        variant="success"
                        size="sm"
                        :loading="clocking"
                        @click="submitClock('in')"
                    >
                        {{ $t('hr.attendance.clock_in_now') }}
                    </AppButton>

                    <AppButton
                        v-else-if="!todayAttendance?.check_out"
                        variant="warning"
                        size="sm"
                        :loading="clocking"
                        @click="submitClock('out')"
                    >
                        {{ $t('hr.attendance.clock_out_now') }}
                    </AppButton>

                    <AppButton variant="secondary" size="sm" @click="showCorrectionModal = true">
                        {{ $t('hr.attendance.request_correction') }}
                    </AppButton>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-green-100 dark:bg-green-900/30 rounded-xl">
                            <svg
                                class="w-5 h-5 text-green-600 dark:text-green-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('hr.attendance.present') }}
                            </p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">
                                {{ summary?.present || 0 }}
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-red-100 dark:bg-red-900/30 rounded-xl">
                            <svg
                                class="w-5 h-5 text-red-600 dark:text-red-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('hr.attendance.absent') }}
                            </p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">
                                {{ summary?.absent || 0 }}
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                            <svg
                                class="w-5 h-5 text-yellow-600 dark:text-yellow-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('hr.attendance.late') }}
                            </p>
                            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">
                                {{ summary?.late || 0 }}
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-orange-100 dark:bg-orange-900/30 rounded-xl">
                            <svg
                                class="w-5 h-5 text-orange-600 dark:text-orange-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('hr.attendance.early_leave') }}
                            </p>
                            <p class="text-xl font-bold text-orange-600 dark:text-orange-400">
                                {{ summary?.early_leave || 0 }}
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-selection-bg rounded-xl">
                            <svg
                                class="w-5 h-5 text-action-primary"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('employee_portal.total_hours') }}
                            </p>
                            <p class="text-xl font-bold text-action-primary">
                                {{ summary?.total_hours || 0 }}h
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <DataTable
                        structured
                        class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('common.date') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('hr.attendance.check_in') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('hr.attendance.check_out') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('common.status') }}
                                </th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >
                                    {{ $t('hr.settings.shifts.shift') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="record in attendance || []"
                                :key="record.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <td
                                    class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    {{ formatDate(record.date) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-mono"
                                    dir="ltr"
                                >
                                    {{ record.check_in || '-' }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-mono"
                                    dir="ltr"
                                >
                                    {{ record.check_out || '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 text-xs font-bold rounded-full"
                                        :class="getStatusClass(record.status)"
                                    >
                                        {{ $t('hr.attendance.' + record.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ record.shift?.name_ar || '-' }}
                                </td>
                            </tr>
                            <tr v-if="!attendance?.length">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4"
                                        >
                                            <svg
                                                class="w-8 h-8 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400">
                                            {{ $t('common.no_data') }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
            </div>
            <!-- Correction Requests History -->
            <div
                v-if="correctionRequests?.length"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $t('hr.attendance.correction_requests') }}
                    </h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div
                        v-for="req in correctionRequests"
                        :key="req.id"
                        class="px-6 py-4 flex flex-wrap items-center justify-between gap-3 text-sm"
                    >
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white font-mono">
                                {{ req.date }}
                                <span
                                    v-if="req.requested_check_in"
                                    class="ms-2 text-xs text-gray-500"
                                >
                                    {{ $t('hr.attendance.check_in') }}: {{ req.requested_check_in }}
                                </span>
                                <span
                                    v-if="req.requested_check_out"
                                    class="ms-2 text-xs text-gray-500"
                                >
                                    {{ $t('hr.attendance.check_out') }}:
                                    {{ req.requested_check_out }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ req.reason }}
                            </p>
                        </div>
                        <span
                            :class="[
                                'px-2.5 py-1 rounded-full text-xs font-semibold',
                                req.status === 'approved'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : req.status === 'rejected'
                                      ? 'bg-red-100 text-red-700'
                                      : 'bg-amber-100 text-amber-700',
                            ]"
                        >
                            {{ $t(`hr.attendance.correction_status_${req.status}`) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Correction Request Modal -->
        <BaseModal
            :show="showCorrectionModal"
            :title="$t('hr.attendance.correction_modal_title')"
            size="md"
            @close="showCorrectionModal = false"
        >
            <div class="space-y-4">
                <FormField
                    :label="$t('hr.attendance.date')"
                    :error="correctionForm.errors.date"
                    required
                >
                    <CustomDatePicker v-model="correctionForm.date" />
                </FormField>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <FormField
                        :label="$t('hr.attendance.requested_check_in')"
                        :error="correctionForm.errors.requested_check_in"
                    >
                        <AppInput v-model="correctionForm.requested_check_in" type="time" />
                    </FormField>
                    <FormField
                        :label="$t('hr.attendance.requested_check_out')"
                        :error="correctionForm.errors.requested_check_out"
                    >
                        <AppInput v-model="correctionForm.requested_check_out" type="time" />
                    </FormField>
                </div>

                <FormField
                    :label="$t('hr.attendance.correction_reason')"
                    :error="correctionForm.errors.reason"
                    required
                >
                    <AppTextarea v-model="correctionForm.reason" :rows="3" />
                </FormField>
            </div>

            <template #footer>
                <AppButton variant="secondary" @click="showCorrectionModal = false">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    variant="primary"
                    :loading="correctionForm.processing"
                    @click="submitCorrection"
                >
                    {{ $t('common.submit') }}
                </AppButton>
            </template>
        </BaseModal>
    </EmployeePortalLayout>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/Composables/useToast';
import { ref } from 'vue';

const { t } = useI18n();
const { error: toastError } = useToast();

const props = defineProps({
    attendance: Array,
    summary: Object,
    month: String,
    employee: Object,
    todayAttendance: { type: Object, default: null },
    geofence: {
        type: Object,
        default: () => ({ enabled: false, radius_meters: 150, has_coordinates: false }),
    },
    correctionRequests: { type: Array, default: () => [] },
});

const selectedMonth = ref(props.month);
const clocking = ref(false);
const showCorrectionModal = ref(false);

const correctionForm = useForm({
    date: new Date().toISOString().slice(0, 10),
    requested_check_in: '',
    requested_check_out: '',
    reason: '',
});

const submitClock = (direction) => {
    const routeName =
        direction === 'in' ? 'employee.attendance.clock-in' : 'employee.attendance.clock-out';

    const sendRequest = (coords = {}) => {
        clocking.value = true;
        router.post(route(routeName), coords, {
            preserveScroll: true,
            onFinish: () => {
                clocking.value = false;
            },
        });
    };

    if (props.geofence?.enabled && props.geofence?.has_coordinates) {
        if (!navigator.geolocation) {
            toastError(t('hr.attendance.location_denied'));
            return;
        }
        clocking.value = true;
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                sendRequest({
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                });
            },
            () => {
                clocking.value = false;
                toastError(t('hr.attendance.location_denied'));
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
        return;
    }

    sendRequest({});
};

const submitCorrection = () => {
    correctionForm.post(route('employee.attendance.corrections.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCorrectionModal.value = false;
            correctionForm.reset('requested_check_in', 'requested_check_out', 'reason');
        },
    });
};

function changeMonth() {
    router.get(
        route('employee.attendance'),
        { month: selectedMonth.value },
        { preserveState: true }
    );
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

function formatMonthName(month) {
    if (!month) return '';
    return new Date(month + '-01').toLocaleDateString('ar-SA-u-nu-latn', {
        year: 'numeric',
        month: 'long',
    });
}

function getStatusClass(status) {
    const classes = {
        present: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        absent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        late: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        early_leave: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}
</script>
