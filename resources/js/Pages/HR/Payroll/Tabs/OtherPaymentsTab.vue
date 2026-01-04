<template>
    <div class="space-y-6">
        <!-- Header with filter and button -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Center Filter -->
            <div class="w-60" v-if="$page.props.auth.available_centers?.length > 1">
                <SearchableSelect
                    :modelValue="selectedCenter"
                    @update:modelValue="value => emit('center-change', value)"
                    :options="[{ id: '', name: $t('common.all') }, ...centers]"
                    option-label="name"
                    option-value="id"
                    :label="$t('common.center')"
                    :placeholder="$t('common.select')"
                />
            </div>
            
            <!-- Add Payment Button -->
            <button @click="openModal" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2" :class="{'ml-auto': !($page.props.auth.available_centers?.length > 1)}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ $t('hr.financial.other_payments.add') }}
            </button>
        </div>

        <!-- Payments Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ $t('common.employee') }}</th>
                            <th scope="col" class="px-6 py-3">{{ $t('hr.financial.other_payments.payment_title') }}</th>
                            <th scope="col" class="px-6 py-3">{{ $t('common.amount') }}</th>
                            <th scope="col" class="px-6 py-3">{{ $t('hr.payroll.payment_date') }}</th>
                            <th scope="col" class="px-6 py-3">{{ $t('common.status') }}</th>
                            <th scope="col" class="px-6 py-3">{{ $t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">{{ $t('common.loading') }}...</td>
                        </tr>
                        <template v-else-if="payments?.data?.length">
                            <tr v-for="payment in payments.data" :key="payment.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ payment.employee?.name_ar }}
                                </td>
                                <td class="px-6 py-4">{{ payment.title }}</td>
                                <td class="px-6 py-4 font-mono font-bold text-emerald-600">
                                    {{ formatCurrency(payment.amount) }}
                                </td>
                                <td class="px-6 py-4">{{ formatDate(payment.payment_date) }}</td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(payment.status)" class="px-2 py-1 rounded-full text-xs font-bold">
                                        {{ $t(`hr.payroll.statuses.${payment.status}`) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 flex items-center gap-2">
                                    <template v-if="payment.status === 'pending'">
                                        <button @click="approvePayment(payment)" class="text-blue-600 hover:text-blue-900" title="Approve">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                        <button @click="editPayment(payment)" class="text-blue-600 hover:text-blue-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deletePayment(payment)" class="text-red-600 hover:text-red-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template v-else-if="payment.status === 'approved'">
                                        <button @click="payPayment(payment)" class="text-emerald-600 hover:text-emerald-900" title="Mark as Paid">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                {{ $t('common.no_records') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
             <!-- Pagination -->
            <div v-if="payments?.meta" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center">
                 <div class="flex gap-2">
                    <Link
                        v-for="link in payments.meta.links"
                         :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1 rounded-md text-sm"
                        :class="[
                            link.active 
                                ? 'bg-emerald-600 text-white' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700',
                             !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <BaseModal :show="showModal" @close="closeModal" :title="editMode ? 'تعديل دفعة' : $t('hr.financial.other_payments.add')">
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
                    <TextInput v-model="form.title" class="w-full" />
                    <InputError :message="errors.title?.[0]" />
                </div>

                <div>
                    <InputLabel :value="$t('common.amount')" />
                    <TextInput v-model="form.amount" type="number" step="0.01" class="w-full" />
                    <InputError :message="errors.amount?.[0]" />
                </div>

                <div>
                    <InputLabel :value="$t('hr.payroll.payment_date')" />
                    <TextInput v-model="form.payment_date" type="date" class="w-full" />
                    <InputError :message="errors.payment_date?.[0]" />
                </div>

                <div>
                    <InputLabel :value="$t('common.notes')" />
                    <textarea v-model="form.notes" class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm" rows="3"></textarea>
                    <InputError :message="errors.notes?.[0]" />
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton @click="closeModal">{{ $t('common.cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="processing">{{ $t('common.save') }}</PrimaryButton>
                </div>
            </form>
        </BaseModal>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import BaseModal from '@/Components/BaseModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import axios from 'axios';

const props = defineProps({
    payments: Object, // Paginator
    employees: Array, // All employees from all centers
    centers: Array, // Centers list for filter dropdown
    loading: Boolean,
    selectedCenter: [Number, null], // Currently selected center filter
});

const emit = defineEmits(['refresh', 'center-change']);
const { success, error: showError } = useToast();
const { confirm } = useConfirm();
const showModal = ref(false);
const editMode = ref(false);
const editingId = ref(null);
const processing = ref(false);

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
    Object.keys(errors).forEach(key => delete errors[key]);
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

async function submit() {
    processing.value = true;
    Object.keys(errors).forEach(key => delete errors[key]);
    console.log('[DEBUG] Submit called with form:', JSON.stringify(form));
    
    try {
        if (editMode.value) {
            console.log('[DEBUG] Updating payment...');
            await axios.put(route('app.hr.payroll.other-payments.update', editingId.value), form);
            success('تم التحديث بنجاح');
        } else {
            console.log('[DEBUG] Creating new payment...');
            const response = await axios.post(route('app.hr.payroll.other-payments.store'), form);
            console.log('[DEBUG] Store response:', response.data);
            success('تم الإضافة بنجاح');
        }
        closeModal();
        console.log('[DEBUG] Emitting refresh event...');
        emit('refresh', true);
    } catch (e) {
        console.error('[DEBUG] Submit error:', e);
        if (e.response?.status === 422) {
            Object.assign(errors, e.response.data.errors);
        } else {
            showError('حدث خطأ غير متوقع');
            console.error(e);
        }
    } finally {
        processing.value = false;
    }
}

async function deletePayment(payment) {
    if (!await confirm({
        title: 'حذف الدفعة',
        message: 'هل أنت متأكد من حذف هذه الدفعة؟ لا يمكن التراجع عن هذا الإجراء.',
        confirmText: 'حذف',
        cancelText: 'إلغاء',
        type: 'danger'
    })) return;

    try {
        await axios.delete(route('app.hr.payroll.other-payments.destroy', payment.id));
        success('تم الحذف بنجاح');
        emit('refresh', true);
    } catch (e) {
        showError('حدث خطأ أثناء الحذف');
        console.error(e);
    }
}

async function approvePayment(payment) {
    if (!await confirm({
        title: 'اعتماد الدفعة',
        message: 'هل أنت متأكد من اعتماد هذه الدفعة؟ سيتم إدراجها في المسيرات القادمة.',
        confirmText: 'اعتماد',
        cancelText: 'إلغاء',
        type: 'warning'
    })) return;

    try {
        await axios.put(route('app.hr.payroll.other-payments.approve', payment.id));
        success('تم الاعتماد بنجاح');
        emit('refresh', true);
    } catch (e) {
        showError('حدث خطأ أثناء الاعتماد');
        console.error(e);
    }
}

async function payPayment(payment) {
    try {
        await axios.put(route('app.hr.payroll.other-payments.pay', payment.id));
        success('تم التسجيل كمدفوع');
        emit('refresh', true);
    } catch (e) {
        showError('حدث خطأ أثناء التسجيل');
        console.error(e);
    }
}

function isPositive(type) {
    return ['bonus', 'compensation'].includes(type);
}

function getTypeClass(type) {
    switch(type) {
        case 'bonus': return 'bg-green-100 text-green-800';
        case 'compensation': return 'bg-emerald-100 text-emerald-800';
        case 'advance': return 'bg-yellow-100 text-yellow-800';
        case 'penalty': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusClass(status) {
    switch(status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'approved': return 'bg-blue-100 text-blue-800';
        case 'paid': return 'bg-emerald-100 text-emerald-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(amount || 0);
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
}
</script>

