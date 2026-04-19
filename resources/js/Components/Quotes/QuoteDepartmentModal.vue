<template>
    <BaseModal :show="show" @close="$emit('close')" max-width="md">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                {{ $t('quotes.show.add_department') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-4">
            <div class="space-y-3 max-h-96 overflow-y-auto p-1">
                <div v-if="departments.length === 0" class="text-center py-6 text-gray-500">
                    {{ $t('quotes.show.all_departments_added') }}
                </div>

                <label v-for="dept in departments" :key="dept.id"
                    class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-700 transition-all"
                    :class="[
                        hasServices(dept.id)
                            ? 'bg-gray-50 dark:bg-gray-800 cursor-not-allowed opacity-60'
                            : 'hover:border-blue-500 dark:hover:border-blue-400 cursor-pointer',
                        { 'bg-blue-50 dark:bg-blue-900/10 border-blue-500 ring-1 ring-blue-500': form.departments.includes(dept.id) && !hasServices(dept.id) }
                    ]">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-900 dark:text-white">{{ getName(dept) }}</span>
                        <!-- Lock icon for departments with services -->
                        <span v-if="hasServices(dept.id)" 
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                            :title="$t('quotes.department_has_services')">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            {{ getServicesCount(dept.id) }}
                        </span>
                    </div>
                    <div class="relative flex items-center">
                        <input type="checkbox" :value="dept.id" v-model="form.departments"
                            :disabled="hasServices(dept.id)"
                            class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed" />
                    </div>
                </label>
            </div>
        </form>

        <template #footer>
            <button type="button" @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                {{ $t('common.cancel') }}
            </button>
            <button type="button" @click="submitForm" :disabled="form.processing || form.departments.length === 0"
                class="px-6 py-2 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-lg hover:from-blue-600 hover:to-indigo-600 disabled:opacity-50 transition-all">
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useLocalized } from '@/Composables/useLocalized';
import BaseModal from '@/Components/BaseModal.vue';

const props = defineProps({
    show: Boolean,
    quote: Object,
    availableDepartments: Array,
    linesByDepartment: [Object, Array], // Lines grouped by department_id
});

const emit = defineEmits(['close', 'saved']);
const { getName } = useLocalized();

// Form
const form = useForm({
    customer_id: props.quote?.customer_id,
    vehicle_id: props.quote?.vehicle_id,
    notes: props.quote?.notes,
    customer_complaint: props.quote?.customer_complaint,
    initial_assessment: props.quote?.initial_assessment,
    odometer: props.quote?.odometer,
    departments: [],
});

// Departments to show
const departments = computed(() => {
    return props.availableDepartments || [];
});

// Check if a department has services in the quote
function hasServices(deptId) {
    if (!props.linesByDepartment) return false;
    const lines = props.linesByDepartment[deptId];
    return Array.isArray(lines) && lines.length > 0;
}

// Get services count for a department
function getServicesCount(deptId) {
    if (!props.linesByDepartment) return 0;
    const lines = props.linesByDepartment[deptId];
    return Array.isArray(lines) ? lines.length : 0;
}

function submitForm() {
    form.put(route('app.quotes.update', props.quote.id), {
        onSuccess: () => {
            emit('saved');
        },
    });
}

// Watch for show to reset
watch(() => props.show, (isOpen) => {
    if (isOpen && props.quote) {
        // Reset form to current quote state
        form.customer_id = props.quote.customer_id;
        form.vehicle_id = props.quote.vehicle_id;
        form.departments = props.quote.departments ? props.quote.departments.map(d => d.id) : [];
        form.notes = props.quote.notes;
        form.customer_complaint = props.quote.customer_complaint;
        form.initial_assessment = props.quote.initial_assessment;
        form.odometer = props.quote.odometer;
    }
});
</script>

