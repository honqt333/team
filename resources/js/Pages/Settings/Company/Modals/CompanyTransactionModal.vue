<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')"></div>

        <!-- Modal -->
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ transaction ? $t('company_profile.transactions.edit') : $t('company_profile.transactions.add') }}
                </h3>
                <button
                    @click="$emit('close')"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('company_profile.transactions.form.title') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                        :placeholder="$t('company_profile.transactions.form.title_placeholder')"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Transaction Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('company_profile.transactions.form.date') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.transaction_date"
                            type="date"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                        />
                        <p v-if="form.errors.transaction_date" class="mt-1 text-sm text-red-500">{{ form.errors.transaction_date }}</p>
                    </div>

                    <!-- Transaction Type -->
                    <div>
                        <SearchableSelect
                            v-model="form.transaction_type"
                            :options="transactionTypeOptions"
                            option-label="label"
                            option-value="value"
                            :label="$t('company_profile.transactions.form.type')"
                            :placeholder="$t('common.choose')"
                            required
                            :error="form.errors.transaction_type"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Income Category -->
                    <div>
                        <SearchableSelect
                            v-model="form.income_category_id"
                            :options="filteredCategoryOptions"
                            option-label="label"
                            option-value="value"
                            :label="$t('company_profile.transactions.form.category')"
                            :placeholder="$t('common.choose')"
                            required
                            :error="form.errors.income_category_id"
                        />
                    </div>

                    <!-- Linked to (Contacts SearchableSelect) -->
                    <div>
                        <SearchableSelect
                            v-model="selectedContactKey"
                            :options="searchResults"
                            :async-search="true"
                            @search="handleAsyncSearch"
                            option-label="label"
                            option-value="value"
                            :label="$t('company_profile.transactions.form.linked_to')"
                            :placeholder="$t('company_profile.transactions.form.linked_to_placeholder')"
                            :error="form.errors.contact_id"
                        />
                    </div>
                </div>

                <!-- Value/Amount -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('company_profile.transactions.form.amount') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono"
                        placeholder="0.00"
                    />
                    <p v-if="form.errors.amount" class="mt-1 text-sm text-red-500">{{ form.errors.amount }}</p>
                </div>

                <!-- Is Taxable Toggle (Only visible if tax is enabled) -->
                <div v-if="vatEnabled" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ $t('company_profile.transactions.form.is_taxable') }}
                    </label>
                    <button
                        type="button"
                        @click="form.is_taxable = !form.is_taxable"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
                            form.is_taxable ? 'bg-emerald-600' : 'bg-gray-300 dark:bg-gray-600'
                        ]"
                        role="switch"
                        :aria-checked="form.is_taxable"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                form.is_taxable ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>

                <!-- Tax Details (Visible if taxable is checked) -->
                <div v-if="vatEnabled && form.is_taxable" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-emerald-500/5 border border-emerald-500/10 rounded-lg">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">
                            {{ $t('company_profile.transactions.form.vat_amount') }} (15%)
                        </label>
                        <input
                            v-model="form.tax_amount"
                            type="text"
                            readonly
                            class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-mono focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1">
                            {{ $t('company_profile.transactions.form.total_amount') }}
                        </label>
                        <input
                            v-model="form.total_amount"
                            type="text"
                            readonly
                            class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-mono focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Notice warning about auto-generated invoices on approval -->
                <div v-if="form.is_taxable" class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-700 dark:text-amber-400 flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>
                        {{ form.transaction_type === 'revenue' 
                            ? $t('company_profile.transactions.form.sales_invoice_notice') 
                            : $t('company_profile.transactions.form.purchase_invoice_notice') }}
                    </span>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('company_profile.transactions.form.notes') }}
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                    ></textarea>
                    <p v-if="form.errors.notes" class="mt-1 text-sm text-red-500">{{ form.errors.notes }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 rounded-lg transition-all disabled:opacity-50"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    transaction: Object,
    incomeCategories: Array,
    vatEnabled: Boolean
});

const emit = defineEmits(['close', 'saved']);

const searchResults = ref([]);
const selectedContactKey = ref(null);

const form = useForm({
    title: '',
    transaction_date: new Date().toISOString().substr(0, 10),
    transaction_type: 'revenue',
    income_category_id: '',
    amount: '',
    is_taxable: false,
    tax_amount: '0.00',
    total_amount: '0.00',
    contact_type: null,
    contact_id: null,
    notes: '',
});

const transactionTypeOptions = [
    { value: 'revenue', label: t('company_profile.transactions.form.type_revenue') },
    { value: 'expense', label: t('company_profile.transactions.form.type_expense') },
];

// Filter categories based on transaction type
const filteredCategories = computed(() => {
    return props.incomeCategories.filter(cat => cat.transaction_type === form.transaction_type);
});

const filteredCategoryOptions = computed(() => {
    return filteredCategories.value.map(cat => ({
        value: cat.id,
        label: cat.name
    }));
});

// Initialize in edit mode
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.transaction) {
            form.title = props.transaction.title;
            form.transaction_date = props.transaction.transaction_date;
            form.transaction_type = props.transaction.transaction_type;
            form.income_category_id = props.transaction.income_category_id;
            form.amount = props.transaction.amount;
            form.is_taxable = !!props.transaction.is_taxable;
            form.tax_amount = props.transaction.tax_amount;
            form.total_amount = props.transaction.total_amount;
            form.contact_type = props.transaction.contact_type;
            form.contact_id = props.transaction.contact_id;
            form.notes = props.transaction.notes || '';

            if (props.transaction.contact) {
                const contact = props.transaction.contact;
                const type = props.transaction.contact_type;
                const key = `${type}-${contact.id}`;
                const label = `${contact.name} (${contact.phone || ''}) - ${type === 'customer' ? 'عميل' : 'مورد'}`;
                
                searchResults.value = [{
                    value: key,
                    label,
                    type,
                    id: contact.id
                }];
                selectedContactKey.value = key;
            } else {
                selectedContactKey.value = null;
            }
        } else {
            form.reset();
            form.transaction_date = new Date().toISOString().substr(0, 10);
            selectedContactKey.value = null;
            searchResults.value = [];
        }
    }
});

// Reset income category if transaction type changes
watch(() => form.transaction_type, () => {
    form.income_category_id = '';
});

// Watch to calculate VAT and Total
watch([() => form.amount, () => form.is_taxable], () => {
    const amt = parseFloat(form.amount) || 0;
    if (form.is_taxable) {
        form.tax_amount = (amt * 0.15).toFixed(2);
        form.total_amount = (amt * 1.15).toFixed(2);
    } else {
        form.tax_amount = '0.00';
        form.total_amount = amt.toFixed(2);
    }
});

// Watch selectedContactKey to map back to form type & ID
watch(selectedContactKey, (val) => {
    if (val) {
        const [type, id] = val.split('-');
        form.contact_type = type;
        form.contact_id = parseInt(id);
    } else {
        form.contact_type = null;
        form.contact_id = null;
    }
});

// Debounce helper
function debounce(fn, delay) {
    let timer = null;
    return function (...args) {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => {
            fn.apply(this, args);
        }, delay);
    };
}

// Search contacts dynamically
const handleAsyncSearch = debounce(async (query) => {
    if (!query) return;
    try {
        const response = await axios.get('/app/settings/company/contacts/search', {
            params: { search: query }
        });
        searchResults.value = response.data;
    } catch (e) {
        searchResults.value = [];
    }
}, 300);

function submit() {
    form.clearErrors();

    if (form.is_taxable && !form.contact_id) {
        form.setError('contact_id', 'يجب تحديد العميل/المورد عند تفعيل خاضع للضريبة لضمان التوافق مع الفوترة الضريبية');
        // Scroll to error if needed
        return;
    }

    const url = props.transaction 
        ? `/app/settings/company/transactions/${props.transaction.id}` 
        : '/app/settings/company/transactions';
    
    const method = props.transaction ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            emit('saved');
        },
    });
}
</script>
