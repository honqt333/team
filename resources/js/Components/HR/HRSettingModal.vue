<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            {{ item ? $t('common.edit') : $t('common.add') }} {{ typeLabel }}
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Name AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.name') }} (AR) <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.name_ar"
                    type="text"
                    required
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                />
            </div>

            <!-- Name EN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.name') }} (EN)
                </label>
                <input
                    v-model="form.name_en"
                    type="text"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                />
            </div>

            <!-- Department (for job_title only) -->
            <div v-if="type === 'job_title'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.department') }}
                </label>
                <select
                    v-model="form.department_id"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                >
                    <option :value="null">{{ $t('common.none') }}</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                        {{ dept.name_ar }}
                    </option>
                </select>
            </div>

            <!-- Type & Amount (for allowance/deduction only) -->
            <div v-if="type === 'allowance' || type === 'deduction'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('common.type') }}
                        </label>
                        <select
                            v-model="form.type"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                        >
                            <option value="fixed">{{ $t('hr.settings.types.fixed') }}</option>
                            <option value="percentage">{{ $t('hr.settings.types.percentage') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('common.amount') }} {{ form.type === 'percentage' ? '(%)' : '' }}
                        </label>
                        <input
                            v-model.number="form.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                        />
                    </div>
                </div>

                <!-- Calculation Base (only for percentage) -->
                <div v-if="form.type === 'percentage'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('hr.settings.calculation_base') }}
                    </label>
                    <select
                        v-model="form.calculation_base"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                    >
                        <option value="base_salary">{{ $t('hr.settings.calculation_bases.base_salary') }}</option>
                        <option value="monthly_contribution">{{ $t('hr.settings.calculation_bases.monthly_contribution') }}</option>
                    </select>
                </div>
            </div>

            <!-- Is Active -->
            <div class="flex items-center gap-2">
                <input
                    v-model="form.is_active"
                    type="checkbox"
                    id="is_active"
                    class="w-4 h-4 text-violet-600 bg-gray-100 border-gray-300 rounded focus:ring-violet-500 dark:focus:ring-violet-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                />
                <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('common.active') }}
                </label>
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-violet-600 rounded-lg hover:bg-violet-700 disabled:opacity-50"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    show: Boolean,
    type: String, // employee_type, job_title, allowance, deduction
    item: Object,
    departments: Array,
});

const emit = defineEmits(['close', 'saved']);

const typeLabel = computed(() => {
    const labels = {
        employee_type: t('hr.settings.employee_types.singular'),
        job_title: t('hr.settings.job_titles.singular'),
        allowance: t('hr.settings.allowances.singular'),
        deduction: t('hr.settings.deductions.singular'),
    };
    return labels[props.type] || '';
});

const form = useForm({
    name_ar: '',
    name_en: '',
    department_id: null,
    type: 'fixed',
    amount: 0,
    calculation_base: 'base_salary',
    is_active: true,
});

function resetForm() {
    form.name_ar = '';
    form.name_en = '';
    form.department_id = null;
    form.type = 'fixed';
    form.amount = 0;
    form.calculation_base = 'base_salary';
    form.is_active = true;
}

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.item) {
            form.name_ar = props.item.name_ar || '';
            form.name_en = props.item.name_en || '';
            form.department_id = props.item.department_id || null;
            form.type = props.item.type || 'fixed';
            form.amount = props.item.amount || 0;
            form.calculation_base = props.item.calculation_base || 'base_salary';
            form.is_active = props.item.is_active ?? true;
        } else {
            resetForm();
        }
    }
});

function submit() {
    const routes = {
        employee_type: {
            store: 'app.hr.settings.employee-types.store',
            update: 'app.hr.settings.employee-types.update',
        },
        job_title: {
            store: 'app.hr.settings.job-titles.store',
            update: 'app.hr.settings.job-titles.update',
        },
        allowance: {
            store: 'app.hr.settings.allowances.store',
            update: 'app.hr.settings.allowances.update',
        },
        deduction: {
            store: 'app.hr.settings.deductions.store',
            update: 'app.hr.settings.deductions.update',
        },
    };

    const routeConfig = routes[props.type];
    if (!routeConfig) return;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            success(t('common.saved_success'));
            resetForm();
            emit('saved');
        },
    };

    if (props.item) {
        form.put(route(routeConfig.update, props.item.id), options);
    } else {
        form.post(route(routeConfig.store), options);
    }
}
</script>

