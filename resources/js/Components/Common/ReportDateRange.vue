<script setup>
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
const start = defineModel('start');
const end = defineModel('end');
defineEmits(['change']);
</script>

<template>
    <div class="flex items-center gap-2">
        <CustomDatePicker
            v-model="start"
            :placeholder="$t('reports.payments_report.date_from')"
            class="w-36 text-xs"
            @change="$emit('change')"
        />
        <span class="text-gray-400 text-xs">—</span>
        <CustomDatePicker
            v-model="end"
            :placeholder="$t('reports.payments_report.date_to')"
            class="w-36 text-xs"
            @change="$emit('change')"
        />
    </div>
</template>
