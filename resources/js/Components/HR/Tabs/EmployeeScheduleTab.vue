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
            </div>
        </div>

        <!-- Weekly Schedule Section -->
        <div v-if="shifts?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
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
                            <td class="px-4 py-3 min-w-[200px]">
                                <SearchableSelect
                                    v-model="weeklyForm[dayIndex]"
                                    :options="shifts"
                                    :option-label="locale === 'ar' ? 'name_ar' : 'name_en'"
                                    option-value="id"
                                    :placeholder="$t('hr.employees.rest_day')"
                                />
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
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    employee: Object,
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
</script>
