<template>
  <div class="relative" ref="container" dir="ltr">
    <div class="relative">
      <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <input
        type="text"
        readonly
        :value="formattedDate"
        @click="isOpen = !isOpen"
        class="w-full ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all cursor-pointer"
        :placeholder="placeholder"
      />
    </div>

    <!-- Calendar Dropdown -->
    <div v-if="isOpen" class="absolute z-50 mt-1 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-3"
         :class="alignRight ? 'end-0' : 'start-0'">
        
      <div class="flex items-center justify-between mb-4">
        <button type="button" @click="prevMonth" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-600 dark:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="font-semibold text-gray-800 dark:text-gray-100 text-sm">
            {{ currentMonthName }} {{ currentYear }}
        </div>
        <button type="button" @click="nextMonth" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-600 dark:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>

      <div class="grid grid-cols-7 gap-1 mb-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400">
        <div v-for="day in daysOfWeek" :key="day">{{ day }}</div>
      </div>

      <div class="grid grid-cols-7 gap-1">
        <button
            v-for="(day, index) in calendarDays"
            :key="index"
            type="button"
            @click="day.date && selectDate(day.date)"
            :disabled="!day.date"
            :class="[
                'w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-colors mx-auto',
                !day.date ? 'invisible' : '',
                isSelected(day.date) ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-500/30' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                isToday(day.date) && !isSelected(day.date) ? 'border border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : ''
            ]"
        >
            {{ day.date ? day.date.getDate() : '' }}
        </button>
      </div>
      
      <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between">
          <button type="button" @click="clearDate" class="text-xs text-red-500 hover:text-red-600 font-medium px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">Clear</button>
          <button type="button" @click="selectToday" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium px-2 py-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">Today</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Date, null],
        default: ''
    },
    placeholder: {
        type: String,
        default: 'DD/MM/YYYY'
    },
    alignRight: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const container = ref(null);
const isOpen = ref(false);

const currentDate = ref(new Date());

const daysOfWeek = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        const d = new Date(newVal);
        if (!isNaN(d.getTime())) {
            currentDate.value = new Date(d.getFullYear(), d.getMonth(), 1);
        }
    } else {
        currentDate.value = new Date();
    }
}, { immediate: true });

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

const isSelected = (date) => {
    if (!date || !props.modelValue) return false;
    const modelDate = new Date(props.modelValue);
    if (isNaN(modelDate.getTime())) return false;
    
    return date.getDate() === modelDate.getDate() &&
           date.getMonth() === modelDate.getMonth() &&
           date.getFullYear() === modelDate.getFullYear();
};

const isToday = (date) => {
    if (!date) return false;
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
};

const prevMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
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
