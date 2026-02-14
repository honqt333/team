<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('hr.contracts.title') }}</h3>
            <button @click="createNewContract"
                class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors shadow-sm shadow-emerald-200 dark:shadow-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ $t('hr.contracts.add') }}
            </button>
        </div>

        <!-- Contracts List -->
        <div v-if="employee.contracts?.length" class="space-y-4">
            <div v-for="contract in employee.contracts" :key="contract.id"
                class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center justify-between group">
                <div class="flex items-center gap-4">
                    <div :class="[
                        'w-12 h-12 rounded-xl flex items-center justify-center',
                        contract.status === 'active' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'
                    ]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-gray-900 dark:text-white">{{ contract.contract_number }}</h4>
                            <span :class="[
                                'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                statusClasses[contract.status]
                            ]">
                                {{ $t(`hr.contracts.status.${contract.status}`) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ contract.start_date }} - {{ contract.end_date || $t('common.na') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                    <button @click="editContract(contract)"
                        class="p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                    <button @click="deleteContract(contract.id)"
                        class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else
            class="py-12 text-center bg-gray-50 dark:bg-gray-700/30 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
            <div
                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ $t('hr.contracts.no_contracts') }}</p>
        </div>

        <!-- Contract Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-4xl shadow-xl border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ editingId ? $t('common.update') : $t('hr.contracts.add') }}
                            </h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l18 18" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Sidebar -->
                            <div class="lg:col-span-1 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                        $t('hr.contracts.number') }}</label>
                                    <input v-model="form.contract_number" type="text" placeholder="CONT-2024-XXXX"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required />
                                    <p v-if="form.errors.contract_number" class="text-xs text-red-500 mt-1">{{
                                        form.errors.contract_number }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                            $t('hr.contracts.start_date') }}</label>
                                        <input v-model="form.start_date" type="date"
                                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            required />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                            $t('hr.contracts.end_date') }}</label>
                                        <input v-model="form.end_date" type="date"
                                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                        $t('common.status') }}</label>
                                    <select v-model="form.status"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required>
                                        <option value="draft">{{ $t('hr.contracts.status.draft') }}</option>
                                        <option value="active">{{ $t('hr.contracts.status.active') }}</option>
                                        <option value="expired">{{ $t('hr.contracts.status.expired') }}</option>
                                        <option value="cancelled">{{ $t('hr.contracts.status.cancelled') }}</option>
                                    </select>
                                </div>

                                <div
                                    class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                                    <h5 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-wider">{{
                                        $t('hr.employees.base_salary') }}</h5>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{
                                            employee.base_salary }}</span>
                                        <span class="text-xs text-gray-500">{{ $t('common.currency_sar') }}</span>
                                    </div>
                                </div>

                                <button type="button" @click="generateDraft"
                                    class="w-full py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                                    {{ $t('hr.contracts.draft_contract') }}
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="lg:col-span-2 flex flex-col h-full">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{
                                    $t('hr.contracts.content') }}</label>
                                <textarea v-model="form.content"
                                    class="flex-1 w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm p-4 min-h-[400px]"
                                    placeholder="اكتب بنود العقد هنا..."></textarea>

                                <div class="flex justify-end gap-3 mt-6">
                                    <button type="button" @click="showModal = false"
                                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl">
                                        {{ $t('common.cancel') }}
                                    </button>
                                    <button type="submit" :disabled="form.processing"
                                        class="px-8 py-2 bg-emerald-600 text-white rounded-xl font-medium disabled:opacity-50 shadow-sm shadow-emerald-200 dark:shadow-none">
                                        {{ $t('common.save') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    employee: Object,
});

const { success } = useToast();
const showModal = ref(false);
const editingId = ref(null);

const form = useForm({
    contract_number: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
    status: 'draft',
    content: '',
    salary_details: null,
});

const statusClasses = {
    draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    expired: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

function createNewContract() {
    editingId.value = null;
    form.reset();
    form.contract_number = `CONT-${new Date().getFullYear()}-${Math.floor(1000 + Math.random() * 9000)}`;
    showModal.value = true;
}

function editContract(contract) {
    editingId.value = contract.id;
    form.contract_number = contract.contract_number;
    form.start_date = contract.start_date;
    form.end_date = contract.end_date;
    form.status = contract.status;
    form.content = contract.content;
    showModal.value = true;
}

function submit() {
    if (editingId.value) {
        form.put(route('app.hr.contracts.update', editingId.value), {
            onSuccess: () => {
                showModal.value = false;
                success();
            },
        });
    } else {
        form.post(route('app.hr.employees.contracts.store', props.employee.id), {
            onSuccess: () => {
                showModal.value = false;
                success();
            },
        });
    }
}

function deleteContract(id) {
    if (confirm('هل أنت متأكد من حذف هذا العقد؟')) {
        router.delete(route('app.hr.contracts.destroy', id), {
            onSuccess: () => success(),
        });
    }
}

function generateDraft() {
    const today = new Date().toLocaleDateString('ar-SA');
    form.content = `عقد عمل

إنه في يوم ${today}، تم الاتفاق بين:
1- شركة كراج (طرف أول)
2- السيد/ ${props.employee.name_ar} (طرف ثان)

البند الأول: طبيعة العمل
يوافق الطرف الثاني على العمل لدى الطرف الأول بمهنة (${props.employee.job_title?.name_ar || '______'}).

البند الثاني: الراتب
يدفع الطرف الأول للطرف الثاني راتباً شهرياً وقدره (${props.employee.base_salary}) ريال سعودي.

البند الثالث: مدة العقد
يبدأ هذا العقد من تاريخ الاستلام ويستمر لمدة (سنة واحدة) يتجدد تلقائياً ما لم يشعر أحد الطرفين الآخر برغبته في عدم التجديد.

والله ولي التوفيق.`;
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
