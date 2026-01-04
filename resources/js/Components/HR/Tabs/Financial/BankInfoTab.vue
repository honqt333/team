<template>
    <div class="space-y-4">
        <form @submit.prevent="submit">
            <!-- Bank Name - Full Width -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('hr.financial.bank.name') }}
                </label>
                <input
                    v-model="form.bank_name"
                    type="text"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500"
                    :placeholder="$t('hr.financial.bank.name')"
                />
            </div>

            <!-- IBAN and Account Number - Side by Side -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- IBAN -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('hr.financial.bank.iban') }}
                    </label>
                    <input
                        v-model="form.bank_iban"
                        type="text"
                        dir="ltr"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500"
                        placeholder="SA0000000000000000000000"
                    />
                </div>

                <!-- Account Number -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('hr.financial.bank.account_number') }}
                    </label>
                    <input
                        v-model="form.bank_account_number"
                        type="text"
                        dir="ltr"
                        class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500"
                    />
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('hr.financial.bank.notes') }}
                </label>
                <textarea
                    v-model="form.bank_notes"
                    rows="2"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500"
                ></textarea>
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-medium shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    employee: Object,
});

const form = useForm({
    bank_name: props.employee.bank_name || '',
    bank_iban: props.employee.bank_iban || '',
    bank_account_number: props.employee.bank_account_number || '',
    bank_notes: props.employee.bank_notes || '',
});

watch(() => props.employee, (newVal) => {
    form.bank_name = newVal.bank_name || '';
    form.bank_iban = newVal.bank_iban || '';
    form.bank_account_number = newVal.bank_account_number || '';
    form.bank_notes = newVal.bank_notes || '';
}, { deep: true });

function submit() {
    form.put(route('app.hr.employees.bank-info.update', props.employee.id), {
        preserveScroll: true,
    });
}
</script>
