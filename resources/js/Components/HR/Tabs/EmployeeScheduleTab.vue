<template>
    <div class="space-y-6">
        <!-- Default Shift Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="text-xl">⏰</span>
                {{ $t('hr.settings.shifts.title') }}
            </h3>
            
            <div class="space-y-6">
                <!-- Default Shift Dropdown -->
                <div class="max-w-md">
                    <SearchableSelect
                        v-model="defaultShiftId"
                        :label="$t('hr.employees.default_shift')"
                        :options="shifts"
                        :option-label="locale === 'ar' ? 'name_ar' : 'name_en'"
                        option-value="id"
                        :placeholder="$t('hr.employees.rest_day')"
                        @update:modelValue="updateDefaultShift"
                    />
                    <p class="mt-1 text-xs text-gray-500">{{ $t('hr.employees.default_shift_hint') }}</p>
                </div>

                <!-- OR Manual Hours -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('hr.employees.manual_times_title') }}</h4>
                    <form @submit.prevent="updateSchedule" class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('hr.employees.shift_start') }}</label>
                            <input
                                v-model="scheduleForm.shift_start"
                                type="time"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('hr.employees.shift_end') }}</label>
                            <input
                                v-model="scheduleForm.shift_end"
                                type="time"
                                class="w-full h-10 px-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                type="submit"
                                :disabled="scheduleForm.processing"
                                class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-50"
                            >
                                {{ $t('common.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Weekly Schedule Section -->
        <div v-if="shifts?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">📅</span>
                    <span class="text-xl">📅</span>
                    {{ $t('hr.employees.weekly_schedule') }}
                </h3>
                <button
                    @click="saveWeeklySchedule"
                    :disabled="savingWeekly"
                    class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-50 text-sm"
                >
                    {{ savingWeekly ? $t('hr.employees.saving_schedule') : $t('hr.employees.save_schedule') }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('hr.employees.day') }}</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('hr.employees.shift') }}</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('hr.employees.time') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(dayName, dayIndex) in dayNames" :key="dayIndex" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ dayName }}</span>
                            </td>
                            <td class="px-4 py-3">
                            <td class="px-4 py-3 min-w-[200px]">
                                <SearchableSelect
                                    v-model="weeklyForm[dayIndex]"
                                    :options="shifts"
                                    :option-label="locale === 'ar' ? 'name_ar' : 'name_en'"
                                    option-value="id"
                                    :placeholder="$t('hr.employees.rest_day')"
                                />
                            </td>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="weeklyForm[dayIndex]" class="text-sm font-mono text-gray-600 dark:text-gray-400">
                                    {{ getShiftTime(weeklyForm[dayIndex]) }}
                                </span>
                                <span v-else class="text-sm text-gray-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leaves Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-xl">🏖️</span>
                    {{ $t('hr.leaves.title') }}
                </h3>
                <button
                    @click="openLeaveModal()"
                    class="px-4 py-2 bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 rounded-lg hover:bg-violet-200 dark:hover:bg-violet-900/50 text-sm font-medium"
                >
                    + {{ $t('hr.leaves.add') }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.type') }}</th>
                            <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.duration') }}</th>
                            <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.dates') }}</th>
                            <th class="px-6 py-3 font-medium">{{ $t('hr.leaves.status') }}</th>
                            <th class="px-6 py-3 font-medium">{{ $t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $t(`hr.leaves.types.${leave.type}`) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                {{ leave.days }} {{ $t('common.days') }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 dir-ltr text-right">
                                {{ leave.start_date }} → {{ leave.end_date }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    leave.status === 'approved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' :
                                    leave.status === 'rejected' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                ]">
                                    {{ $t(`hr.leaves.statuses.${leave.status}`) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <template v-if="leave.status === 'pending'">
                                        <button @click="openLeaveModal(leave)" class="text-blue-600 hover:text-blue-800">
                                            ✏️
                                        </button>
                                        <button @click="deleteLeave(leave)" class="text-red-600 hover:text-red-800">
                                            🗑️
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="leaves.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                {{ $t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leave Modal -->
        <Modal :show="showLeaveModal" @close="closeLeaveModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ editingLeave ? $t('hr.leaves.edit') : $t('hr.leaves.add') }}
                </h3>

                <form @submit.prevent="submitLeave" class="space-y-4">
                    <!-- Leave Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.leaves.type') }}
                        </label>
                        <select
                            v-model="leaveForm.type"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                        >
                            <option value="annual">{{ $t('hr.leaves.types.annual') }}</option>
                            <option value="sick">{{ $t('hr.leaves.types.sick') }}</option>
                            <option value="unpaid">{{ $t('hr.leaves.types.unpaid') }}</option>
                            <option value="emergency">{{ $t('hr.leaves.types.emergency') }}</option>
                            <option value="other">{{ $t('hr.leaves.types.other') }}</option>
                        </select>
                        <p v-if="leaveForm.errors.type" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.type }}</p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('common.from') }}
                            </label>
                            <input
                                type="date"
                                v-model="leaveForm.start_date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                            />
                            <p v-if="leaveForm.errors.start_date" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('common.to') }}
                            </label>
                            <input
                                type="date"
                                v-model="leaveForm.end_date"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                            />
                            <p v-if="leaveForm.errors.end_date" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('hr.leaves.reason') }}
                        </label>
                        <textarea
                            v-model="leaveForm.reason"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-violet-500"
                        ></textarea>
                         <p v-if="leaveForm.errors.reason" class="text-xs text-red-500 mt-1">{{ leaveForm.errors.reason }}</p>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            @click="closeLeaveModal"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="leaveForm.processing"
                            class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:opacity-50"
                        >
                            {{ $t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Modal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    employee: Object,
    leaves: {
        type: Array,
        default: () => []
    },
    shifts: {
        type: Array,
        default: () => []
    },
    weeklySchedule: {
        type: Object,
        default: () => ({})
    }
});

const { t, locale } = useI18n();
const { success, error } = useToast();

// Days of week names (Arabic)
const dayNames = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

// Default shift
const defaultShiftId = ref(props.employee.default_shift_id);

// Weekly schedule form (0-6 for days)
const weeklyForm = reactive({
    0: props.weeklySchedule?.[0] || null,
    1: props.weeklySchedule?.[1] || null,
    2: props.weeklySchedule?.[2] || null,
    3: props.weeklySchedule?.[3] || null,
    4: props.weeklySchedule?.[4] || null,
    5: props.weeklySchedule?.[5] || null,
    6: props.weeklySchedule?.[6] || null,
});

const savingWeekly = ref(false);

// Get shift time display
function getShiftTime(shiftId) {
    const shift = props.shifts.find(s => s.id === shiftId);
    if (!shift) return '';
    return `${shift.start_time?.slice(0,5)} - ${shift.end_time?.slice(0,5)}`;
}

// Update default shift
function updateDefaultShift() {
    router.put(route('app.hr.employees.default-shift.update', props.employee.id), {
        default_shift_id: defaultShiftId.value
    }, {
        preserveScroll: true,
        onSuccess: () => success(t('common.saved_success')),
        onError: () => error(t('common.error_occurred'))
    });
}

// Save weekly schedule
function saveWeeklySchedule() {
    savingWeekly.value = true;
    router.put(route('app.hr.employees.weekly-schedule.update', props.employee.id), {
        schedule: weeklyForm
    }, {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
            savingWeekly.value = false;
        },
        onError: () => {
            error(t('common.error_occurred'));
            savingWeekly.value = false;
        }
    });
}

// Leave management
const showLeaveModal = ref(false);
const editingLeave = ref(null);

const scheduleForm = useForm({
    name_ar: props.employee.name_ar,
    status: props.employee.status,
    shift_start: props.employee.shift_start || '',
    shift_end: props.employee.shift_end || '',
});

const leaveForm = useForm({
    type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
    employee_id: props.employee.id
});

function updateSchedule() {
    scheduleForm.transform(data => ({
        name_ar: props.employee.name_ar,
        status: props.employee.status,
        shift_start: data.shift_start,
        shift_end: data.shift_end,
    })).put(route('app.hr.employees.update', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => success(t('common.saved_success')),
        onError: () => error(t('common.error_occurred'))
    });
}

function openLeaveModal(leave = null) {
    editingLeave.value = leave;
    if (leave) {
        leaveForm.type = leave.type;
        leaveForm.start_date = leave.start_date;
        leaveForm.end_date = leave.end_date;
        leaveForm.reason = leave.reason;
    } else {
        leaveForm.reset();
        leaveForm.type = 'annual';
        leaveForm.employee_id = props.employee.id;
    }
    showLeaveModal.value = true;
}

function closeLeaveModal() {
    showLeaveModal.value = false;
    leaveForm.reset();
    editingLeave.value = null;
}

function submitLeave() {
    const url = editingLeave.value 
        ? route('app.hr.leaves.update', editingLeave.value.id)
        : route('app.hr.leaves.store');
    
    const method = editingLeave.value ? 'put' : 'post';

    leaveForm[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            closeLeaveModal();
            success(t('common.saved_success'));
        },
        onError: () => error(t('common.error_occurred'))
    });
}

function deleteLeave(leave) {
    if (!confirm(t('common.confirm_delete'))) return;
    
    router.delete(route('app.hr.leaves.destroy', leave.id), {
        preserveScroll: true,
        onSuccess: () => success(t('common.deleted_success')),
        onError: () => error(t('common.error_occurred'))
    });
}
</script>

