<template>
    <div class="space-y-3">
        <!-- Quick Date Presets -->
        <div class="flex flex-wrap items-center gap-2">
            <AppButton
                v-for="preset in datePresets"
                :key="preset.key"
                type="button"
                size="xs"
                :variant="activePreset === preset.key ? 'primary' : 'secondary'"
                @click="applyDatePreset(preset.key)"
            >
                {{ preset.label }}
            </AppButton>
        </div>

        <!-- Detailed Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <FormField v-slot="field" :label="$t('common.search')">
                <AppInput
                    :id="field.id"
                    v-model="filters.search"
                    type="text"
                    size="sm"
                    :placeholder="$t('appointments.search_placeholder')"
                    @input="onSearchInput"
                />
            </FormField>

            <FormField v-slot="field" :label="$t('appointments.filter_date')">
                <CustomDatePicker
                    :id="field.id"
                    v-model="filters.date"
                    @change="onCustomDateChange"
                />
            </FormField>

            <FormField v-slot="field" :label="$t('appointments.filter_status')">
                <SearchableSelect
                    :id="field.id"
                    v-model="filters.status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('appointments.all')"
                    :label="''"
                    :compact="true"
                    @change="emit('reload')"
                />
            </FormField>

            <FormField v-slot="field" :label="$t('appointments.filter_bay')">
                <SearchableSelect
                    :id="field.id"
                    v-model="filters.service_bay_id"
                    :options="bayOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('appointments.all')"
                    :label="''"
                    :compact="true"
                    @change="emit('reload')"
                />
            </FormField>

            <FormField v-slot="field" :label="$t('appointments.filter_tech')">
                <SearchableSelect
                    :id="field.id"
                    v-model="filters.assigned_user_id"
                    :options="techOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="$t('appointments.all')"
                    :label="''"
                    :compact="true"
                    @change="emit('reload')"
                />
            </FormField>

            <div class="flex justify-end lg:justify-start">
                <AppButton
                    type="button"
                    size="sm"
                    variant="secondary"
                    @click="emit('clear')"
                >
                    {{ $t('appointments.clear_filters') }}
                </AppButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import FormField from '@/Components/Common/FormField.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    filters: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    bayOptions: { type: Array, default: () => [] },
    techOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['reload', 'clear']);
const { t } = useI18n();

function formatYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

const todayStr = computed(() => formatYmd(new Date()));

const tomorrowStr = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return formatYmd(d);
});

const weekRange = computed(() => {
    const start = new Date();
    const end = new Date();
    end.setDate(start.getDate() + 6);
    return { from: formatYmd(start), to: formatYmd(end) };
});

const datePresets = computed(() => [
    { key: 'all', label: t('appointments.all_dates') },
    { key: 'today', label: t('appointments.today') },
    { key: 'tomorrow', label: t('appointments.tomorrow') },
    { key: 'week', label: t('appointments.this_week') },
]);

const activePreset = computed(() => {
    if (props.filters.date === todayStr.value) return 'today';
    if (props.filters.date === tomorrowStr.value) return 'tomorrow';
    if (
        !props.filters.date &&
        props.filters.date_from === weekRange.value.from &&
        props.filters.date_to === weekRange.value.to
    ) {
        return 'week';
    }
    if (!props.filters.date && !props.filters.date_from && !props.filters.date_to) {
        return 'all';
    }
    return '';
});

let searchTimer = null;
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => emit('reload'), 350);
}

function applyDatePreset(key) {
    if (key === 'all') {
        props.filters.date = '';
        props.filters.date_from = '';
        props.filters.date_to = '';
    } else if (key === 'today') {
        props.filters.date = todayStr.value;
        props.filters.date_from = '';
        props.filters.date_to = '';
    } else if (key === 'tomorrow') {
        props.filters.date = tomorrowStr.value;
        props.filters.date_from = '';
        props.filters.date_to = '';
    } else if (key === 'week') {
        props.filters.date = '';
        props.filters.date_from = weekRange.value.from;
        props.filters.date_to = weekRange.value.to;
    }
    emit('reload');
}

function onCustomDateChange() {
    props.filters.date_from = '';
    props.filters.date_to = '';
    emit('reload');
}
</script>
