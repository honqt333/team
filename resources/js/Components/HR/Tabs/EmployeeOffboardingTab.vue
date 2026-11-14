<template>
    <div class="space-y-6">
        <!-- Top Status & Action Header -->
        <div
            class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-border-subtle bg-surface-primary p-4 shadow-2xs"
        >
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-base font-bold text-content-primary">
                        {{ $t('hr.offboarding.title') }}
                    </h3>
                    <span
                        v-if="latestOffboarding"
                        :class="[
                            'px-2.5 py-0.5 rounded-full text-xs font-semibold',
                            isFinalized
                                ? 'bg-success-soft text-success'
                                : 'bg-warning-soft text-warning',
                        ]"
                    >
                        {{
                            isFinalized
                                ? $t('hr.offboarding.status_finalized')
                                : $t('hr.offboarding.status_draft')
                        }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-content-muted">
                    {{ $t('hr.offboarding.subtitle') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <AppPrintButton
                    v-if="latestOffboarding"
                    variant="button"
                    size="sm"
                    :label="$t('hr.offboarding.print_action')"
                    :href="
                        route('app.hr.employees.offboarding.print', {
                            employee: employee.id,
                            offboarding: latestOffboarding.id,
                        })
                    "
                />
            </div>
        </div>

        <!-- Unreturned Custody Alert -->
        <div
            v-if="unreturnedCustodiesCount > 0 && !isFinalized"
            class="rounded-2xl border border-warning/40 bg-warning-soft p-4 text-xs font-semibold text-warning"
        >
            {{
                $t('hr.offboarding.custody_warning', { count: toEnglish(unreturnedCustodiesCount) })
            }}
        </div>
        <div
            v-else-if="unreturnedCustodiesCount === 0"
            class="rounded-2xl border border-success/30 bg-success-soft p-3 text-xs font-semibold text-success"
        >
            {{ $t('hr.offboarding.custody_all_clear') }}
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Left 2 Columns: Calculator Inputs & Clearance Checklist -->
            <div class="xl:col-span-2 space-y-6">
                <FormSection :title="$t('hr.offboarding.settlement_summary')">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <FormField
                            :label="$t('hr.offboarding.termination_type')"
                            :error="form.errors.termination_type"
                            required
                        >
                            <SearchableSelect
                                v-model="form.termination_type"
                                :options="terminationTypeOptions"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.hire_date')"
                            :error="form.errors.hire_date"
                            required
                        >
                            <CustomDatePicker v-model="form.hire_date" :disabled="isReadOnly" />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.termination_date')"
                            :error="form.errors.termination_date"
                            required
                        >
                            <CustomDatePicker
                                v-model="form.termination_date"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.base_salary')"
                            :error="form.errors.base_salary"
                            required
                        >
                            <AppInput
                                v-model="form.base_salary"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.allowances_amount')"
                            :error="form.errors.allowances_amount"
                        >
                            <AppInput
                                v-model="form.allowances_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.unused_leave_days')"
                            :error="form.errors.unused_leave_days"
                        >
                            <AppInput
                                v-model="form.unused_leave_days"
                                type="number"
                                step="0.5"
                                min="0"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.advances_deduction')"
                            :error="form.errors.advances_deduction"
                        >
                            <AppInput
                                v-model="form.advances_deduction"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.custody_deduction')"
                            :error="form.errors.custody_deduction"
                        >
                            <AppInput
                                v-model="form.custody_deduction"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.other_additions')"
                            :error="form.errors.other_additions"
                        >
                            <AppInput
                                v-model="form.other_additions"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isReadOnly"
                            />
                        </FormField>

                        <FormField
                            :label="$t('hr.offboarding.other_deductions')"
                            :error="form.errors.other_deductions"
                        >
                            <AppInput
                                v-model="form.other_deductions"
                                type="number"
                                step="0.01"
                                min="0"
                                :disabled="isReadOnly"
                            />
                        </FormField>
                    </div>

                    <div class="mt-4">
                        <FormField :label="$t('hr.offboarding.notes')" :error="form.errors.notes">
                            <AppTextarea v-model="form.notes" :rows="2" :disabled="isReadOnly" />
                        </FormField>
                    </div>
                </FormSection>

                <FormSection :title="$t('hr.offboarding.clearance_checklist')">
                    <div class="space-y-4">
                        <AppToggle
                            v-model="form.custody_cleared"
                            :label="$t('hr.offboarding.custody_cleared')"
                            color="success"
                            :disabled="isReadOnly"
                        />
                        <AppToggle
                            v-model="form.finance_cleared"
                            :label="$t('hr.offboarding.finance_cleared')"
                            color="success"
                            :disabled="isReadOnly"
                        />
                        <AppToggle
                            v-model="form.it_access_revoked"
                            :label="$t('hr.offboarding.it_access_revoked')"
                            color="success"
                            :disabled="isReadOnly"
                        />
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="mb-2 text-xs font-semibold text-content-secondary">
                                {{ $t('hr.offboarding.employee_signature') }}
                            </p>
                            <img
                                v-if="isReadOnly && form.employee_signature"
                                :src="form.employee_signature"
                                class="h-28 w-full rounded-xl border border-border-subtle bg-white object-contain p-2"
                            />
                            <SignaturePad
                                v-else-if="!isReadOnly"
                                v-model="form.employee_signature"
                                :height="120"
                            />
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-semibold text-content-secondary">
                                {{ $t('hr.offboarding.hr_signature') }}
                            </p>
                            <img
                                v-if="isReadOnly && form.hr_signature"
                                :src="form.hr_signature"
                                class="h-28 w-full rounded-xl border border-border-subtle bg-white object-contain p-2"
                            />
                            <SignaturePad
                                v-else-if="!isReadOnly"
                                v-model="form.hr_signature"
                                :height="120"
                            />
                        </div>
                    </div>
                </FormSection>
            </div>

            <!-- Right Column: Live Settlement Summary Card -->
            <div class="space-y-4">
                <div
                    class="rounded-2xl border border-border-subtle bg-surface-primary p-5 shadow-2xs space-y-4"
                >
                    <h4
                        class="text-sm font-bold text-content-primary border-b border-border-subtle pb-3"
                    >
                        {{ $t('hr.offboarding.settlement_summary') }}
                    </h4>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.service_duration') }}
                            </span>
                            <span class="font-mono font-bold text-content-primary">
                                {{ toEnglish(preview.serviceYears.toFixed(2)) }}
                                {{ $t('hr.offboarding.years_unit') }}
                                ({{ toEnglish(preview.serviceDays) }}
                                {{ $t('hr.offboarding.days_unit') }})
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.calculation_salary') }}
                            </span>
                            <span class="font-mono font-semibold text-content-primary">
                                {{ formatMoney(preview.calculationSalary) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.full_eosb') }}
                            </span>
                            <span class="font-mono text-content-secondary">
                                {{ formatMoney(preview.fullEosb) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.multiplier') }}
                            </span>
                            <span class="font-mono font-semibold text-action-primary">
                                {{ toEnglish(Math.round(preview.multiplier * 100)) }}%
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between border-t border-border-subtle pt-2.5"
                        >
                            <span class="font-semibold text-content-primary">
                                {{ $t('hr.offboarding.eosb_amount') }}
                            </span>
                            <span class="font-mono font-bold text-success">
                                + {{ formatMoney(preview.eosbAmount) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.unused_leave_payout') }}
                            </span>
                            <span class="font-mono font-semibold text-success">
                                + {{ formatMoney(preview.unusedLeavePayout) }}
                            </span>
                        </div>

                        <div
                            v-if="preview.otherAdditions > 0"
                            class="flex items-center justify-between"
                        >
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.other_additions') }}
                            </span>
                            <span class="font-mono font-semibold text-success">
                                + {{ formatMoney(preview.otherAdditions) }}
                            </span>
                        </div>

                        <div
                            v-if="preview.advancesDeduction > 0"
                            class="flex items-center justify-between"
                        >
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.advances_deduction') }}
                            </span>
                            <span class="font-mono font-semibold text-danger">
                                - {{ formatMoney(preview.advancesDeduction) }}
                            </span>
                        </div>

                        <div
                            v-if="preview.custodyDeduction > 0"
                            class="flex items-center justify-between"
                        >
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.custody_deduction') }}
                            </span>
                            <span class="font-mono font-semibold text-danger">
                                - {{ formatMoney(preview.custodyDeduction) }}
                            </span>
                        </div>

                        <div
                            v-if="preview.otherDeductions > 0"
                            class="flex items-center justify-between"
                        >
                            <span class="text-content-muted">
                                {{ $t('hr.offboarding.other_deductions') }}
                            </span>
                            <span class="font-mono font-semibold text-danger">
                                - {{ formatMoney(preview.otherDeductions) }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between border-t border-border-subtle pt-3 text-sm"
                        >
                            <span class="font-bold text-content-primary">
                                {{ $t('hr.offboarding.net_settlement') }}
                            </span>
                            <span class="font-mono text-lg font-black text-action-primary">
                                {{ formatMoney(preview.netSettlement) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="canManageOffboarding && !isFinalized" class="space-y-2.5 pt-2">
                        <AppButton
                            variant="secondary"
                            class="w-full justify-center"
                            :loading="form.processing"
                            @click="saveDraft"
                        >
                            {{ $t('hr.offboarding.save_draft') }}
                        </AppButton>

                        <AppButton
                            v-if="latestOffboarding"
                            variant="danger"
                            class="w-full justify-center"
                            :loading="finalizeForm.processing"
                            @click="confirmFinalizeOpen = true"
                        >
                            {{ $t('hr.offboarding.finalize_action') }}
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="confirmFinalizeOpen"
            :title="$t('hr.offboarding.finalize_confirm_title')"
            :message="$t('hr.offboarding.finalize_confirm_message')"
            :confirm-text="$t('hr.offboarding.finalize_action')"
            :cancel-text="$t('common.cancel')"
            variant="danger"
            :loading="finalizeForm.processing"
            @confirm="finalizeClearance"
            @cancel="confirmFinalizeOpen = false"
        />
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import FormSection from '@/Components/Common/FormSection.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SignaturePad from '@/Components/Common/SignaturePad.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const page = usePage();
const { formatMoney, toEnglish } = useNumberFormat();
const { error: toastError } = useToast();

const props = defineProps({
    employee: { type: Object, required: true },
    latestOffboarding: { type: Object, default: null },
    offboardingDefaults: {
        type: Object,
        default: () => ({ allowances_amount: 0, unpaid_advances: 0 }),
    },
    custodies: { type: Array, default: () => [] },
});

const canManageOffboarding = computed(() => {
    const auth = page.props.auth || {};
    return Boolean(auth.is_super_admin || (auth.permissions || []).includes('hr.employees.update'));
});

const isFinalized = computed(() => props.latestOffboarding?.status === 'finalized');
const isReadOnly = computed(() => isFinalized.value || !canManageOffboarding.value);

const unreturnedCustodiesCount = computed(
    () => (props.custodies || []).filter((c) => c.status === 'issued').length
);

const todayStr = new Date().toISOString().slice(0, 10);

const form = useForm({
    termination_type: props.latestOffboarding?.termination_type || 'employer_termination',
    hire_date: props.latestOffboarding?.hire_date || props.employee.hire_date || todayStr,
    termination_date:
        props.latestOffboarding?.termination_date || props.employee.termination_date || todayStr,
    base_salary: props.latestOffboarding?.base_salary ?? props.employee.base_salary ?? 0,
    allowances_amount:
        props.latestOffboarding?.allowances_amount ??
        props.offboardingDefaults?.allowances_amount ??
        0,
    unused_leave_days: props.latestOffboarding?.unused_leave_days ?? 0,
    advances_deduction:
        props.latestOffboarding?.advances_deduction ??
        props.offboardingDefaults?.unpaid_advances ??
        0,
    custody_deduction: props.latestOffboarding?.custody_deduction ?? 0,
    other_additions: props.latestOffboarding?.other_additions ?? 0,
    other_deductions: props.latestOffboarding?.other_deductions ?? 0,
    custody_cleared: Boolean(props.latestOffboarding?.custody_cleared ?? false),
    finance_cleared: Boolean(props.latestOffboarding?.finance_cleared ?? false),
    it_access_revoked: Boolean(props.latestOffboarding?.it_access_revoked ?? false),
    notes: props.latestOffboarding?.notes || '',
    employee_signature: props.latestOffboarding?.employee_signature || '',
    hr_signature: props.latestOffboarding?.hr_signature || '',
});

const finalizeForm = useForm({
    employee_signature: '',
    hr_signature: '',
    finance_cleared: true,
    it_access_revoked: true,
});

const confirmFinalizeOpen = ref(false);

const terminationTypeOptions = computed(() => [
    {
        value: 'employer_termination',
        label: t('hr.offboarding.types.employer_termination'),
    },
    { value: 'resignation', label: t('hr.offboarding.types.resignation') },
    { value: 'article_81', label: t('hr.offboarding.types.article_81') },
    { value: 'article_80', label: t('hr.offboarding.types.article_80') },
]);

const preview = computed(() => {
    const hire = form.hire_date ? new Date(form.hire_date) : null;
    const term = form.termination_date ? new Date(form.termination_date) : null;
    let serviceDays = 0;
    if (
        hire &&
        term &&
        !Number.isNaN(hire.getTime()) &&
        !Number.isNaN(term.getTime()) &&
        term >= hire
    ) {
        serviceDays = Math.floor((term.getTime() - hire.getTime()) / (1000 * 60 * 60 * 24));
    }

    const serviceYears = serviceDays / 365.0;
    const baseSalary = Math.max(0, Number(form.base_salary) || 0);
    const allowancesAmount = Math.max(0, Number(form.allowances_amount) || 0);
    const calculationSalary = baseSalary + allowancesAmount;

    const firstFive = Math.min(5, serviceYears);
    const afterFive = Math.max(0, serviceYears - 5);
    const fullEosb = firstFive * (calculationSalary * 0.5) + afterFive * calculationSalary;

    let multiplier = 1.0;
    if (form.termination_type === 'article_80') {
        multiplier = 0.0;
    } else if (form.termination_type === 'resignation') {
        if (serviceYears < 2) {
            multiplier = 0.0;
        } else if (serviceYears < 5) {
            multiplier = 1 / 3;
        } else if (serviceYears < 10) {
            multiplier = 2 / 3;
        } else {
            multiplier = 1.0;
        }
    }

    const eosbAmount = fullEosb * multiplier;
    const unusedLeaveDays = Math.max(0, Number(form.unused_leave_days) || 0);
    const unusedLeavePayout = (calculationSalary / 30.0) * unusedLeaveDays;

    const advancesDeduction = Math.max(0, Number(form.advances_deduction) || 0);
    const custodyDeduction = Math.max(0, Number(form.custody_deduction) || 0);
    const otherAdditions = Math.max(0, Number(form.other_additions) || 0);
    const otherDeductions = Math.max(0, Number(form.other_deductions) || 0);

    const netSettlement =
        eosbAmount +
        unusedLeavePayout +
        otherAdditions -
        advancesDeduction -
        custodyDeduction -
        otherDeductions;

    return {
        serviceDays,
        serviceYears,
        calculationSalary,
        fullEosb,
        multiplier,
        eosbAmount,
        unusedLeavePayout,
        advancesDeduction,
        custodyDeduction,
        otherAdditions,
        otherDeductions,
        netSettlement,
    };
});

const saveDraft = () => {
    form.post(route('app.hr.employees.offboarding.store', { employee: props.employee.id }), {
        preserveScroll: true,
        onError: () => {
            const firstErr = Object.values(form.errors)[0];
            if (firstErr) {
                toastError(firstErr);
            }
        },
    });
};

const finalizeClearance = () => {
    if (!props.latestOffboarding) {
        return;
    }

    finalizeForm.employee_signature = form.employee_signature;
    finalizeForm.hr_signature = form.hr_signature;
    finalizeForm.finance_cleared = Boolean(form.finance_cleared);
    finalizeForm.it_access_revoked = Boolean(form.it_access_revoked);

    finalizeForm.post(
        route('app.hr.employees.offboarding.finalize', {
            employee: props.employee.id,
            offboarding: props.latestOffboarding.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmFinalizeOpen.value = false;
            },
            onError: () => {
                confirmFinalizeOpen.value = false;
                const firstErr = Object.values(finalizeForm.errors)[0];
                if (firstErr) {
                    toastError(firstErr);
                }
            },
        }
    );
};
</script>
