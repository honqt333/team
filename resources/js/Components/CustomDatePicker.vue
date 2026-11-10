<template>
    <div class="relative" ref="container" dir="ltr">
        <div class="relative">
            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                <svg
                    class="w-4 h-4 text-text-muted"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
            </div>
            <input
                type="text"
                :readonly="!disabled"
                :disabled="disabled"
                :value="formattedDate"
                @click="!disabled && (isOpen = !isOpen)"
                :class="[
                    'w-full ps-10 pe-4 py-2.5 text-sm border rounded-xl placeholder:text-text-muted transition-all',
                    disabled
                        ? 'bg-surface-muted text-text-muted cursor-not-allowed border-border'
                        : 'bg-surface text-text-primary cursor-pointer border-border focus:ring-2 focus:ring-focus-ring focus:border-action-primary',
                ]"
                :placeholder="placeholder"
            />
        </div>

        <!-- Calendar Dropdown -->
        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 w-64 bg-surface rounded-xl shadow-lg border border-border p-3"
            :class="alignRight ? 'end-0' : 'start-0'"
        >
            <div class="flex items-center justify-between mb-4">
                <button
                    type="button"
                    @click="prevMonth"
                    class="p-1 hover:bg-surface-muted rounded-lg text-text-secondary hover:text-text-primary cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </button>
                <div class="font-semibold text-text-primary text-sm">
                    {{ currentMonthName }} {{ currentYear }}
                </div>
                <button
                    type="button"
                    @click="nextMonth"
                    class="p-1 hover:bg-surface-muted rounded-lg text-text-secondary hover:text-text-primary cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </button>
            </div>

            <div
                class="grid grid-cols-7 gap-1 mb-2 text-center text-xs font-medium text-text-muted"
            >
                <div v-for="day in daysOfWeek" :key="day">{{ day }}</div>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <button
                    v-for="(day, index) in calendarDays"
                    :key="index"
                    type="button"
                    @click="day.date && !isDayDisabled(day.date) && selectDate(day.date)"
                    :disabled="!day.date || isDayDisabled(day.date)"
                    :class="[
                        'w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-colors mx-auto',
                        !day.date ? 'invisible' : '',
                        isDayDisabled(day.date)
                            ? 'opacity-30 cursor-not-allowed text-text-muted'
                            : isSelected(day.date)
                              ? 'bg-action-primary text-action-on font-bold shadow-xs'
                              : 'text-text-primary hover:bg-surface-muted cursor-pointer',
                        isToday(day.date) && !isSelected(day.date) && !isDayDisabled(day.date)
                            ? 'border border-action-primary text-action-primary font-bold'
                            : '',
                    ]"
                >
                    {{ day.date ? day.date.getDate() : '' }}
                </button>
            </div>

            <div class="mt-3 pt-3 border-t border-border flex justify-between">
                <button
                    type="button"
                    @click="clearDate"
                    class="text-xs text-danger hover:underline font-medium px-2 py-1 rounded hover:bg-danger/10 transition-colors cursor-pointer"
                >
                    Clear
                </button>
                <button
                    type="button"
                    @click="selectToday"
                    class="text-xs text-action-primary hover:underline font-medium px-2 py-1 rounded hover:bg-selection-bg transition-colors cursor-pointer"
                >
                    Today
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Date, null],
        default: '',
    },
    placeholder: {
        type: String,
        default: 'DD/MM/YYYY',
    },
    alignRight: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    minDate: {
        type: [String, Date, null],
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const container = ref(null);
const isOpen = ref(false);

const currentDate = ref(new Date());

const daysOfWeek = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const monthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal) {
            const d = new Date(newVal);
            if (!isNaN(d.getTime())) {
                currentDate.value = new Date(d.getFullYear(), d.getMonth(), 1);
            }
        } else {
            currentDate.value = new Date();
        }
    },
    { immediate: true }
);

const currentMonthName = computed(() => monthNames[currentDate.value.getMonth()]);
const currentYear = computed(() => currentDate.value.getFullYear());

const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const days = [];

    for (let i = 0; i < firstDay; i++) {
        days.push({ date: null });
    }

    for (let i = 1; i <= daysInMonth; i++) {
        days.push({ date: new Date(year, month, i) });
    }

    return days;
});

const formattedDate = computed(() => {
    if (!props.modelValue) return '';
    const d = new Date(props.modelValue);
    if (isNaN(d.getTime())) return '';

    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();

    return `${day}/${month}/${year}`;
});

const isDayDisabled = (date) => {
    if (!date) return true;
    if (!props.minDate) return false;

    const minD = new Date(props.minDate);
    minD.setHours(0, 0, 0, 0);

    const d = new Date(date);
    d.setHours(0, 0, 0, 0);

    return d.getTime() < minD.getTime();
};

const isSelected = (date) => {
    if (!date || !props.modelValue) return false;
    const modelDate = new Date(props.modelValue);
    if (isNaN(modelDate.getTime())) return false;

    return (
        date.getDate() === modelDate.getDate() &&
        date.getMonth() === modelDate.getMonth() &&
        date.getFullYear() === modelDate.getFullYear()
    );
};

const isToday = (date) => {
    if (!date) return false;
    const today = new Date();
    return (
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear()
    );
};

const prevMonth = () => {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() - 1,
        1
    );
};

const nextMonth = () => {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + 1,
        1
    );
};

const selectDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const dateStr = `${year}-${month}-${day}`;

    emit('update:modelValue', dateStr);
    emit('change', dateStr);
    isOpen.value = false;
};

const clearDate = () => {
    emit('update:modelValue', '');
    emit('change', '');
    isOpen.value = false;
};

const selectToday = () => {
    selectDate(new Date());
};

const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
