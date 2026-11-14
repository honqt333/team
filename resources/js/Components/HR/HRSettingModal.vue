<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg">
        <template #title>
            {{ item ? $t('common.edit') : $t('common.add') }} {{ typeLabel }}
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Name AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.name') }} (AR)
                    <span class="text-red-500">*</span>
                </label>
                <AppInput v-model="form.name_ar" type="text" required />
            </div>

            <!-- Name EN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('common.name') }} (EN)
                    <span class="text-red-500">*</span>
                </label>
                <AppInput
                    v-model="form.name_en"
                    type="text"
                    required
                    :error="form.errors.name_en"
                />
            </div>

            <!-- Department (for job_title only) -->
            <div v-if="type === 'job_title'">
                <SearchableSelect
                    v-model="form.department_id"
                    :options="departmentOptions"
                    option-label="name_ar"
                    option-value="id"
                    :label="$t('common.department')"
                    :placeholder="$t('common.none')"
                />
            </div>

            <!-- Type & Amount (for allowance/deduction only) -->
            <div v-if="type === 'allowance' || type === 'deduction'" class="space-y-4">
                <!-- Is Flexible (custom amount per employee) - FIRST -->
                <div
                    class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-700"
                >
                    <AppToggle
                        v-model="form.is_flexible"
                        color="warning"
                        :label="$t('hr.financial.flexible')"
                        :description="$t('hr.settings.flexible_hint')"
                    />
                </div>

                <!-- Type & Amount - Hidden when flexible -->
                <div v-if="!form.is_flexible" class="grid grid-cols-2 gap-4">
                    <div>
                        <SearchableSelect
                            v-model="form.type"
                            :options="typeOptions"
                            option-label="label"
                            option-value="value"
                            :label="$t('common.type')"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-1">
                            {{ $t('common.amount') }} {{ form.type === 'percentage' ? '(%)' : '' }}
                        </label>
                        <AppInput v-model.number="form.amount" type="number" step="0.01" min="0" />
                    </div>
                </div>

                <!-- Calculation Base (only for percentage and not flexible) -->
                <div v-if="form.type === 'percentage' && !form.is_flexible">
                    <SearchableSelect
                        v-model="form.calculation_base"
                        :options="calculationBaseOptions"
                        option-label="label"
                        option-value="value"
                        :label="$t('hr.settings.calculation_base')"
                    />
                </div>
            </div>

            <!-- Is Active -->
            <div>
                <AppToggle v-model="form.is_active" :label="$t('common.active')" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <AppButton type="button" variant="secondary" @click="$emit('close')">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    type="button"
                    variant="primary"
                    @click="submit"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
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

// SearchableSelect options
const departmentOptions = computed(() => props.departments || []);

const typeOptions = computed(() => [
    { value: 'fixed', label: t('hr.settings.types.fixed') },
    { value: 'percentage', label: t('hr.settings.types.percentage') },
]);

const calculationBaseOptions = computed(() => [
    { value: 'base_salary', label: t('hr.settings.calculation_bases.base_salary') },
    {
        value: 'monthly_contribution',
        label: t('hr.settings.calculation_bases.monthly_contribution'),
    },
]);

const form = useForm({
    name_ar: '',
    name_en: '',
    department_id: null,
    type: 'fixed',
    amount: 0,
    calculation_base: 'base_salary',
    is_flexible: true,
    is_active: true,
});

function resetForm() {
    form.name_ar = '';
    form.name_en = '';
    form.department_id = null;
    form.type = 'fixed';
    form.amount = 0;
    form.calculation_base = 'base_salary';
    form.is_flexible = true;
    form.is_active = true;
}

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            if (props.item) {
                form.name_ar = props.item.name_ar || '';
                form.name_en = props.item.name_en || '';
                form.department_id = props.item.department_id || null;
                form.type = props.item.type || 'fixed';
                form.amount = props.item.amount || 0;
                form.calculation_base = props.item.calculation_base || 'base_salary';
                form.is_flexible = props.item.is_flexible ?? false;
                form.is_active = props.item.is_active ?? true;
            } else {
                resetForm();
            }
        }
    }
);

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
