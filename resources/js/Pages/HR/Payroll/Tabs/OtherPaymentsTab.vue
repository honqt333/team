<template>
    <div class="space-y-6">
        <!-- Header with filter and button -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Center Filter -->
            <div class="w-60" v-if="$page.props.auth?.available_centers?.length > 1">
                <SearchableSelect
                    :modelValue="selectedCenter"
                    @update:modelValue="(value) => emit('center-change', value)"
                    :options="[{ id: '', name: $t('common.all') }, ...(centers || [])]"
                    option-label="name"
                    option-value="id"
                    :label="$t('common.center')"
                    :placeholder="$t('common.select')"
                />
            </div>

            <!-- Add Payment Button -->
            <button
                type="button"
                @click="openModal"
                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white rounded-xl font-bold shadow-xs transition-colors cursor-pointer ms-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M12 4v16m8-8H4"
                    />
                </svg>
                <span class="text-sm">
                    {{ $t('hr.financial.other_payments.add') }}
                </span>
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && (!payments?.data || payments.data.length === 0)" class="py-8">
            <AppEmptyState
                :title="$t('hr.financial.other_payments.title')"
                :description="$t('hr.financial.other_payments.empty_hint')"
                :action-text="$t('hr.financial.other_payments.add')"
                @action="openModal"
            />
        </div>

        <!-- Payments Table -->
        <div v-else class="space-y-4">
            <DataTable
                :data="payments?.data || payments || []"
                :columns="columns"
                :loading="loading"
            >
                <!-- Employee Column Slot -->
                <template #cell-employee="{ item }">
                    <Link
                        v-if="item.employee"
                        :href="
                            route('app.hr.employees.show', {
                                employee: item.employee_id,
                                tab: 'financial',
                            })
                        "
                        class="font-semibold text-content-primary hover:text-action-primary transition-colors"
                    >
                        {{ item.employee.name_ar || item.employee.name_en }}
                    </Link>
                    <span v-else class="text-content-muted">—</span>
                </template>

                <!-- Title Slot -->
                <template #cell-title="{ item }">
                    <span class="text-sm text-content-secondary font-medium">{{ item.title }}</span>
                </template>

                <!-- Amount Slot -->
                <template #cell-amount="{ item }">
                    <span class="font-bold font-mono text-success">
                        {{ formatCurrency(item.amount) }}
                    </span>
                </template>

                <!-- Payment Date Slot -->
                <template #cell-payment_date="{ item }">
                    <span class="text-xs text-content-muted font-mono">
                        {{ formatDate(item.payment_date) }}
                    </span>
                </template>

                <!-- Status Slot -->
                <template #cell-status="{ item }">
                    <span
                        :class="[
                            'px-2.5 py-1 rounded-full text-[11px] font-bold border shadow-2xs',
                            getStatusClass(item.status),
                        ]"
                    >
                        {{ $t(`hr.payroll.statuses.${item.status}`) }}
                    </span>
                </template>

                <!-- Actions Slot -->
                <template #cell-actions="{ item }">
                    <div class="flex items-center justify-end gap-1.5">
                        <template v-if="item.status === 'pending'">
                            <!-- Approve button -->
                            <button
                                type="button"
                                @click="approvePayment(item)"
                                class="p-2 text-content-muted hover:text-info hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                :title="$t('hr.payroll.approve')"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </button>

                            <!-- Edit button -->
                            <button
                                type="button"
                                @click="editPayment(item)"
                                class="p-2 text-content-muted hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                :title="$t('common.edit')"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                            </button>

                            <!-- Delete button -->
                            <button
                                type="button"
                                @click="deletePayment(item)"
                                class="p-2 text-content-muted hover:text-danger hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                :title="$t('common.delete')"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </button>
                        </template>

                        <template v-else-if="item.status === 'approved'">
                            <!-- Mark as Paid button -->
                            <button
                                type="button"
                                @click="payPayment(item)"
                                class="p-2 text-content-muted hover:text-success hover:bg-surface-secondary rounded-lg transition-colors cursor-pointer"
                                :title="$t('hr.payroll.mark_paid')"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </button>
                        </template>
                    </div>
                </template>
            </DataTable>

            <!-- Pagination -->
            <div
                v-if="payments?.links && payments.links.length > 3"
                class="flex justify-center pt-2"
            >
                <nav class="flex items-center gap-1.5" aria-label="Pagination">
                    <button
                        v-for="(link, idx) in payments.links"
                        :key="idx"
                        type="button"
                        :disabled="!link.url || link.active"
                        @click="handleNavigate(link.url)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                            link.active
                                ? 'bg-action-primary text-white shadow-xs'
                                : link.url
                                  ? 'bg-surface-primary dark:bg-surface-secondary/40 text-content-secondary hover:text-content-primary hover:bg-surface-secondary border border-border-subtle'
                                  : 'text-content-muted opacity-40 cursor-not-allowed',
                        ]"
                        v-safe-html="link.label"
                    />
                </nav>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <BaseModal
            :show="showModal"
            @close="closeModal"
            :title="
                editMode
                    ? $t('hr.financial.other_payments.edit')
                    : $t('hr.financial.other_payments.add')
            "
        >
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <SearchableSelect
                        v-model="form.employee_id"
                        :options="employees"
                        option-label="name_ar"
                        option-value="id"
                        :label="$t('common.employee')"
                        :placeholder="$t('common.select')"
                        :error="errors.employee_id?.[0]"
                        required
                    />
                </div>

                <div>
                    <InputLabel :value="$t('hr.financial.other_payments.payment_title')" />
                    <AppInput v-model="form.title" class="w-full" />
                    <InputError :message="errors.title?.[0]" />
                </div>

                <div>
                    <InputLabel :value="$t('common.amount')" />
                    <AppInput v-model="form.amount" type="number" step="0.01" class="w-full" />
                    <InputError :message="errors.amount?.[0]" />
                </div>

                <div>
                    <InputLabel :value="$t('hr.payroll.payment_date')" />
                    <CustomDatePicker v-model="form.payment_date" class="w-full" />
                    <InputError :message="errors.payment_date?.[0]" />
                </div>

                <div>
                    <InputLabel :value="$t('common.notes')" />
                    <textarea
                        v-model="form.notes"
                        class="w-full border-border-subtle dark:bg-surface-secondary/50 dark:text-content-primary focus:border-action-primary focus:ring-action-primary rounded-xl shadow-2xs text-sm"
                        rows="3"
                    ></textarea>
                    <InputError :message="errors.notes?.[0]" />
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-border-subtle">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </form>
        </BaseModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { formatMoney } = useNumberFormat();

import DataTable from '@/Components/Common/DataTable.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import BaseModal from '@/Components/BaseModal.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import InputLabel from '@/Components/InputLabel.vue';
import AppInput from '@/Components/App/AppInput.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const { t } = useI18n();
const { success, error: showError } = useToast();
const { confirm } = useConfirm();

const props = defineProps({
    payments: Object,
    employees: Array,
    centers: Array,
    loading: Boolean,
    selectedCenter: [Number, String, null],
});

const emit = defineEmits(['refresh', 'center-change', 'page-update']);

const showModal = ref(false);
const editMode = ref(false);
const editingId = ref(null);
const processing = ref(false);

const columns = computed(() => [
    { key: 'employee', label: t('common.employee') },
    { key: 'title', label: t('hr.financial.other_payments.payment_title') },
    { key: 'amount', label: t('common.amount'), align: 'center' },
    { key: 'payment_date', label: t('hr.payroll.payment_date'), align: 'center' },
    { key: 'status', label: t('common.status'), align: 'center' },
    { key: 'actions', label: t('common.actions'), align: 'end' },
]);

const form = reactive({
    employee_id: '',
    title: '',
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const errors = reactive({});

function resetForm() {
    form.employee_id = '';
    form.title = '';
    form.amount = '';
    form.payment_date = new Date().toISOString().split('T')[0];
    form.notes = '';
    Object.keys(errors).forEach((key) => delete errors[key]);
}

function openModal() {
    editMode.value = false;
    resetForm();
    showModal.value = true;
}

function editPayment(payment) {
    editMode.value = true;
    editingId.value = payment.id;
    form.employee_id = payment.employee_id;
    form.title = payment.title;
    form.amount = payment.amount;
    form.payment_date = payment.payment_date;
    form.notes = payment.notes;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    resetForm();
}

async function handleNavigate(url) {
    if (!url) return;
    try {
        const response = await axios.get(url, {
            params: { center_id: props.selectedCenter },
        });
        emit('page-update', response.data);
    } catch (e) {
        console.error('[DEBUG] Failed to navigate page', e);
    }
}

async function submit() {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        if (editMode.value) {
            await axios.put(route('app.hr.payroll.other-payments.update', editingId.value), form);
            success(t('common.updated_success'));
        } else {
            await axios.post(route('app.hr.payroll.other-payments.store'), form);
            success(t('common.created_success'));
        }
        closeModal();
        emit('refresh', true);
    } catch (e) {
        console.error('[DEBUG] Submit error:', e);
        if (e.response?.status === 422) {
            Object.assign(errors, e.response.data.errors);
        } else {
            showError(t('common.error'));
        }
    } finally {
        processing.value = false;
    }
}

async function deletePayment(payment) {
    if (
        !(await confirm({
            title: t('hr.financial.other_payments.delete_confirm_title'),
            message: t('hr.financial.other_payments.delete_confirm_message'),
            confirmText: t('common.delete'),
            cancelText: t('common.cancel'),
            type: 'danger',
        }))
    )
        return;

    try {
        await axios.delete(route('app.hr.payroll.other-payments.destroy', payment.id));
        success(t('common.deleted_success'));
        emit('refresh', true);
    } catch (e) {
        showError(t('common.error'));
        console.error(e);
    }
}

async function approvePayment(payment) {
    if (
        !(await confirm({
            title: t('hr.financial.other_payments.approve_confirm_title'),
            message: t('hr.financial.other_payments.approve_confirm_message'),
            confirmText: t('hr.payroll.approve'),
            cancelText: t('common.cancel'),
            type: 'warning',
        }))
    )
        return;

    try {
        await axios.put(route('app.hr.payroll.other-payments.approve', payment.id));
        success(t('hr.payroll.approved_successfully'));
        emit('refresh', true);
    } catch (e) {
        showError(t('common.error'));
        console.error(e);
    }
}

async function payPayment(payment) {
    try {
        await axios.put(route('app.hr.payroll.other-payments.pay', payment.id));
        success(t('hr.payroll.marked_paid'));
        emit('refresh', true);
    } catch (e) {
        showError(t('common.error'));
        console.error(e);
    }
}

function getStatusClass(status) {
    switch (status) {
        case 'pending':
            return 'bg-warning-soft text-warning border-warning/20';
        case 'approved':
            return 'bg-info-soft text-info border-info/20';
        case 'paid':
            return 'bg-success-soft text-success border-success/20';
        default:
            return 'bg-surface-secondary text-content-muted border-border-subtle';
    }
}

function formatCurrency(amount) {
    return formatMoney(amount);
}

function formatDate(date) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn');
}
</script>
