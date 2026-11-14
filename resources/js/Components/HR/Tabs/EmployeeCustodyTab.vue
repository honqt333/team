<template>
    <div class="space-y-6">
        <!-- Summary & Action Bar -->
        <div
            class="bg-surface rounded-2xl border border-border p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4"
        >
            <div class="flex flex-wrap items-center gap-3">
                <div class="px-3.5 py-2 rounded-xl bg-surface-muted border border-border text-xs">
                    <span class="text-text-muted">{{ $t('hr.custody.issued_count') }}:</span>
                    <span class="ms-1.5 font-mono font-bold text-text-primary">
                        {{ toEnglish(activeCustodies.length) }}
                    </span>
                </div>
                <div class="px-3.5 py-2 rounded-xl bg-surface-muted border border-border text-xs">
                    <span class="text-text-muted">{{ $t('hr.custody.total_issued_value') }}:</span>
                    <span class="ms-1.5 font-mono font-bold text-action-primary">
                        {{ formatMoney(activeCustodiesValue) }}
                    </span>
                </div>
            </div>

            <AppButton variant="primary" size="sm" @click="openIssueModal">
                + {{ $t('hr.custody.add') }}
            </AppButton>
        </div>

        <!-- Custody Records Table -->
        <DataTable
            :columns="columns"
            :items="custodies"
            :empty-title="$t('hr.custody.empty')"
            striped
        >
            <template #cell-asset_name="{ item }">
                <div class="space-y-0.5">
                    <p class="font-semibold text-sm text-text-primary">
                        {{ item.asset_name }}
                    </p>
                    <p v-if="item.serial_number" class="text-xs font-mono text-text-muted">
                        SN: {{ item.serial_number }}
                    </p>
                </div>
            </template>

            <template #cell-category="{ item }">
                <span class="text-xs text-text-secondary">
                    {{ $t(`hr.custody.categories.${item.category}`) }}
                </span>
            </template>

            <template #cell-estimated_value="{ item }">
                <span class="font-mono text-xs text-text-primary">
                    {{ formatMoney(item.estimated_value || 0) }}
                </span>
            </template>

            <template #cell-issued_date="{ item }">
                <span class="font-mono text-xs text-text-secondary">{{ item.issued_date }}</span>
            </template>

            <template #cell-status="{ item }">
                <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                    :class="statusClass(item.status)"
                >
                    {{ $t(`hr.custody.statuses.${item.status}`) }}
                </span>
            </template>

            <template #cell-actions="{ item }">
                <div class="flex items-center justify-end gap-2">
                    <AppButton
                        v-if="item.status === 'issued'"
                        variant="secondary"
                        size="xs"
                        @click="openReturnModal(item)"
                    >
                        {{ $t('hr.custody.return_action') }}
                    </AppButton>
                    <AppButton variant="danger" size="xs" @click="deleteCustody(item)">
                        {{ $t('common.delete') }}
                    </AppButton>
                </div>
            </template>
        </DataTable>

        <!-- Issue Custody Modal -->
        <BaseModal
            :show="showIssueModal"
            :title="$t('hr.custody.add')"
            size="lg"
            @close="showIssueModal = false"
        >
            <form id="custody-issue-form" @submit.prevent="submitIssue" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField
                        :label="$t('hr.custody.asset_name')"
                        :error="issueForm.errors.asset_name"
                        required
                    >
                        <AppInput
                            v-model="issueForm.asset_name"
                            :error="issueForm.errors.asset_name"
                            required
                        />
                    </FormField>

                    <FormField
                        :label="$t('hr.custody.category')"
                        :error="issueForm.errors.category"
                        required
                    >
                        <SearchableSelect v-model="issueForm.category" :options="categoryOptions" />
                    </FormField>

                    <FormField
                        :label="$t('hr.custody.serial_number')"
                        :error="issueForm.errors.serial_number"
                    >
                        <AppInput
                            v-model="issueForm.serial_number"
                            :error="issueForm.errors.serial_number"
                        />
                    </FormField>

                    <FormField
                        :label="$t('hr.custody.estimated_value')"
                        :error="issueForm.errors.estimated_value"
                    >
                        <AppInput
                            v-model="issueForm.estimated_value"
                            type="number"
                            step="0.01"
                            min="0"
                            :error="issueForm.errors.estimated_value"
                        />
                    </FormField>

                    <FormField
                        :label="$t('hr.custody.condition')"
                        :error="issueForm.errors.condition_on_issue"
                        required
                    >
                        <SearchableSelect
                            v-model="issueForm.condition_on_issue"
                            :options="issueConditionOptions"
                        />
                    </FormField>

                    <FormField
                        :label="$t('hr.custody.issued_date')"
                        :error="issueForm.errors.issued_date"
                        required
                    >
                        <CustomDatePicker v-model="issueForm.issued_date" />
                    </FormField>
                </div>

                <FormField :label="$t('common.notes')" :error="issueForm.errors.notes">
                    <AppTextarea v-model="issueForm.notes" rows="2" />
                </FormField>

                <FormField
                    :label="$t('hr.custody.issue_signature')"
                    :error="issueForm.errors.issue_signature"
                >
                    <SignaturePad v-model="issueForm.issue_signature" />
                </FormField>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton type="button" variant="secondary" @click="showIssueModal = false">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="submit"
                        form="custody-issue-form"
                        variant="primary"
                        :loading="issueForm.processing"
                    >
                        {{ $t('common.save') }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>

        <!-- Return Custody Modal -->
        <BaseModal
            :show="showReturnModal"
            :title="$t('hr.custody.return_modal_title')"
            size="md"
            @close="showReturnModal = false"
        >
            <form id="custody-return-form" @submit.prevent="submitReturn" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField
                        :label="$t('common.status')"
                        :error="returnForm.errors.status"
                        required
                    >
                        <SearchableSelect
                            v-model="returnForm.status"
                            :options="returnStatusOptions"
                        />
                    </FormField>

                    <FormField
                        :label="$t('hr.custody.returned_date')"
                        :error="returnForm.errors.returned_date"
                        required
                    >
                        <CustomDatePicker v-model="returnForm.returned_date" />
                    </FormField>
                </div>

                <FormField :label="$t('common.notes')" :error="returnForm.errors.notes">
                    <AppTextarea v-model="returnForm.notes" rows="2" />
                </FormField>

                <FormField
                    :label="$t('hr.custody.return_signature')"
                    :error="returnForm.errors.return_signature"
                >
                    <SignaturePad v-model="returnForm.return_signature" />
                </FormField>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton type="button" variant="secondary" @click="showReturnModal = false">
                        {{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="submit"
                        form="custody-return-form"
                        variant="primary"
                        :loading="returnForm.processing"
                    >
                        {{ $t('common.save') }}
                    </AppButton>
                </div>
            </template>
        </BaseModal>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import DataTable from '@/Components/Common/DataTable.vue';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppButton from '@/Components/App/AppButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import SignaturePad from '@/Components/Common/SignaturePad.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    custodies: {
        type: Array,
        default: () => [],
    },
});

const { t } = useI18n();
const { confirm } = useConfirm();
const { success } = useToast();
const { formatMoney, toEnglish } = useNumberFormat();
const { scrollToError } = useFormScrollToError();

const showIssueModal = ref(false);
const showReturnModal = ref(false);
const selectedCustody = ref(null);

const activeCustodies = computed(() => props.custodies.filter((c) => c.status === 'issued'));

const activeCustodiesValue = computed(() =>
    activeCustodies.value.reduce((sum, c) => sum + Number(c.estimated_value || 0), 0)
);

const columns = computed(() => [
    { key: 'asset_name', label: t('hr.custody.asset_name') },
    { key: 'category', label: t('hr.custody.category') },
    { key: 'estimated_value', label: t('hr.custody.estimated_value') },
    { key: 'issued_date', label: t('hr.custody.issued_date') },
    { key: 'status', label: t('common.status') },
    { key: 'actions', label: t('common.actions'), align: 'end' },
]);

const categoryOptions = computed(() => [
    { value: 'tool_kit', label: t('hr.custody.categories.tool_kit') },
    { value: 'diagnostic_device', label: t('hr.custody.categories.diagnostic_device') },
    { value: 'vehicle', label: t('hr.custody.categories.vehicle') },
    { value: 'laptop', label: t('hr.custody.categories.laptop') },
    { value: 'sim_card', label: t('hr.custody.categories.sim_card') },
    { value: 'uniform', label: t('hr.custody.categories.uniform') },
    { value: 'other', label: t('hr.custody.categories.other') },
]);

const issueConditionOptions = computed(() => [
    { value: 'new', label: t('hr.custody.conditions.new') },
    { value: 'good', label: t('hr.custody.conditions.good') },
    { value: 'fair', label: t('hr.custody.conditions.fair') },
]);

const returnStatusOptions = computed(() => [
    { value: 'returned', label: t('hr.custody.statuses.returned') },
    { value: 'damaged', label: t('hr.custody.statuses.damaged') },
    { value: 'lost', label: t('hr.custody.statuses.lost') },
]);

const issueForm = useForm({
    asset_name: '',
    category: 'tool_kit',
    serial_number: '',
    estimated_value: '',
    condition_on_issue: 'good',
    issued_date: new Date().toISOString().slice(0, 10),
    issue_signature: null,
    notes: '',
});

const returnForm = useForm({
    status: 'returned',
    returned_date: new Date().toISOString().slice(0, 10),
    condition_on_return: 'good',
    return_signature: null,
    notes: '',
});

function openIssueModal() {
    issueForm.reset();
    issueForm.issued_date = new Date().toISOString().slice(0, 10);
    issueForm.clearErrors();
    showIssueModal.value = true;
}

function submitIssue() {
    issueForm.post(route('app.hr.employees.custodies.store', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => {
            showIssueModal.value = false;
            success(t('common.saved_success'));
        },
        onError: () => scrollToError(),
    });
}

function openReturnModal(custody) {
    selectedCustody.value = custody;
    returnForm.reset();
    returnForm.returned_date = new Date().toISOString().slice(0, 10);
    returnForm.clearErrors();
    showReturnModal.value = true;
}

function submitReturn() {
    if (!selectedCustody.value) return;
    returnForm.put(
        route('app.hr.employees.custodies.return', {
            employee: props.employee.id,
            custody: selectedCustody.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showReturnModal.value = false;
                success(t('common.saved_success'));
            },
            onError: () => scrollToError(),
        }
    );
}

async function deleteCustody(custody) {
    const confirmed = await confirm({
        title: t('common.delete'),
        message: t('hr.custody.delete_confirm'),
        type: 'danger',
    });
    if (!confirmed) return;

    router.delete(
        route('app.hr.employees.custodies.destroy', {
            employee: props.employee.id,
            custody: custody.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => success(t('common.deleted_success')),
        }
    );
}

function statusClass(status) {
    switch (status) {
        case 'issued':
            return 'bg-info-soft text-info';
        case 'returned':
            return 'bg-success-soft text-success';
        case 'damaged':
            return 'bg-warning-soft text-warning';
        case 'lost':
            return 'bg-danger-soft text-danger';
        default:
            return 'bg-surface-muted text-text-muted';
    }
}
</script>
