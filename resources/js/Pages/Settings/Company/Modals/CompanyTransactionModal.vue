<template>
    <Modal :show="!!show" unstyled @close="$emit('close')">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                @click="$emit('close')"
            ></div>

            <!-- Modal Window -->
            <div
                class="relative bg-white dark:bg-gray-850 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100 dark:border-gray-800 transition-all flex flex-col max-h-[92vh]"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                {{
                                    transaction
                                        ? $t('company_profile.transactions.edit')
                                        : $t('company_profile.transactions.add')
                                }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $t('company_profile.transactions.subtitle') }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="$emit('close')"
                        type="button"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submit" class="p-6 space-y-4 overflow-y-auto flex-1">
                    <!-- Title -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('company_profile.transactions.form.title') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition-all"
                            :placeholder="$t('company_profile.transactions.form.title_placeholder')"
                        />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <!-- Date & Type -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Transaction Date -->
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                            >
                                {{ $t('company_profile.transactions.form.date') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <CustomDatePicker v-model="form.transaction_date" required />
                            <p
                                v-if="form.errors.transaction_date"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.transaction_date }}
                            </p>
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

                    <!-- Category & Linked Partner -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                :options="contactOptions"
                                :async-search="true"
                                @search="handleAsyncSearch"
                                option-label="label"
                                option-value="value"
                                :label="$t('company_profile.transactions.form.linked_to')"
                                :placeholder="
                                    $t('company_profile.transactions.form.linked_to_placeholder')
                                "
                                :error="form.errors.contact_id"
                            />
                            <!-- Tax Number status indicator for selected contact -->
                            <div
                                v-if="selectedContact"
                                class="mt-1 text-2xs flex items-center gap-1.5"
                            >
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{
                                        $t('company_profile.transactions.form.supplier_tax_number')
                                    }}:
                                </span>
                                <span
                                    v-if="selectedContact.tax_number"
                                    class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ selectedContact.tax_number }}
                                </span>
                                <span v-else class="text-rose-500 dark:text-rose-400 font-medium">
                                    {{ $t('company_profile.transactions.form.no_tax_number') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Input -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                {{
                                    form.is_taxable && form.is_vat_inclusive
                                        ? $t('company_profile.transactions.form.amount_inclusive')
                                        : $t('company_profile.transactions.form.amount_before_tax')
                                }}
                                <span class="text-red-500">*</span>
                            </label>
                            <span
                                v-if="form.is_taxable"
                                class="text-2xs font-semibold px-2 py-0.5 rounded-full"
                                :class="
                                    form.is_vat_inclusive
                                        ? 'bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400'
                                        : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400'
                                "
                            >
                                {{
                                    form.is_vat_inclusive
                                        ? $t(
                                              'company_profile.transactions.table.vat_inclusive_badge'
                                          )
                                        : $t(
                                              'company_profile.transactions.table.vat_exclusive_badge'
                                          )
                                }}
                            </span>
                        </div>
                        <div class="relative">
                            <input
                                v-model="enteredAmount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono text-sm"
                                placeholder="0.00"
                            />
                        </div>
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">
                            {{ form.errors.amount }}
                        </p>
                    </div>

                    <!-- Tax Configuration Section (If VAT Enabled) -->
                    <div
                        v-if="vatEnabled"
                        class="p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200/70 dark:border-gray-700/60 rounded-xl space-y-3"
                    >
                        <!-- Is Taxable Toggle -->
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                    {{ $t('company_profile.transactions.form.is_taxable') }}
                                </span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $t('company_profile.transactions.taxable_partner_hint') }}
                                </p>
                            </div>
                            <AppToggle
                                v-model="form.is_taxable"
                                :disabled="!canBeTaxable"
                                color="success"
                            />
                        </div>

                        <!-- Warning for Expense when Supplier lacks VAT number -->
                        <div
                            v-if="form.transaction_type === 'expense' && !canBeTaxable"
                            class="p-2.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 rounded-lg text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2"
                        >
                            <svg
                                class="w-4 h-4 shrink-0 mt-0.5 text-rose-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            <span>
                                {{
                                    $t(
                                        'company_profile.transactions.expense_tax_requires_supplier_vat'
                                    )
                                }}
                            </span>
                        </div>

                        <!-- Is VAT Inclusive Toggle (Visible when is_taxable is TRUE) -->
                        <div
                            v-if="form.is_taxable"
                            class="pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between"
                        >
                            <div>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $t('company_profile.transactions.form.is_vat_inclusive') }}
                                </span>
                                <p class="text-2xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{
                                        $t(
                                            'company_profile.transactions.form.is_vat_inclusive_hint'
                                        )
                                    }}
                                </p>
                            </div>
                            <AppToggle v-model="form.is_vat_inclusive" color="blue" />
                        </div>

                        <!-- Calculated Tax Breakdown Summary Tiles -->
                        <div v-if="form.is_taxable" class="grid grid-cols-3 gap-2 pt-2">
                            <div
                                class="p-2.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-center"
                            >
                                <span
                                    class="block text-2xs text-gray-500 dark:text-gray-400 font-semibold mb-1"
                                >
                                    {{ $t('company_profile.transactions.form.amount_before_tax') }}
                                </span>
                                <span
                                    class="text-xs font-black font-mono text-gray-900 dark:text-white"
                                >
                                    {{ form.amount || '0.00' }}
                                </span>
                            </div>
                            <div
                                class="p-2.5 bg-emerald-500/10 rounded-lg border border-emerald-500/20 text-center"
                            >
                                <span
                                    class="block text-2xs text-emerald-700 dark:text-emerald-400 font-semibold mb-1"
                                >
                                    {{ $t('company_profile.transactions.form.vat_amount') }} ({{
                                        vatRate
                                    }}%)
                                </span>
                                <span
                                    class="text-xs font-black font-mono text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ form.tax_amount || '0.00' }}
                                </span>
                            </div>
                            <div
                                class="p-2.5 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-center"
                            >
                                <span
                                    class="block text-2xs text-gray-500 dark:text-gray-400 font-semibold mb-1"
                                >
                                    {{ $t('company_profile.transactions.form.total_amount') }}
                                </span>
                                <span
                                    class="text-xs font-black font-mono text-gray-900 dark:text-white"
                                >
                                    {{ form.total_amount || '0.00' }}
                                </span>
                            </div>
                        </div>

                        <!-- Notice about auto-generated invoices on approval -->
                        <div
                            v-if="form.is_taxable"
                            class="p-2.5 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-700 dark:text-amber-400 flex items-start gap-2"
                        >
                            <svg
                                class="w-4 h-4 shrink-0 mt-0.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <span>
                                {{
                                    form.transaction_type === 'revenue'
                                        ? $t(
                                              'company_profile.transactions.form.sales_invoice_notice'
                                          )
                                        : $t(
                                              'company_profile.transactions.form.purchase_invoice_notice'
                                          )
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Attachment Upload -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('company_profile.transactions.form.attachment') }}
                        </label>

                        <!-- Existing Attachment Preview if available -->
                        <div
                            v-if="transaction?.attachment_url && !form.remove_attachment"
                            class="mb-2 p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2 overflow-hidden">
                                <svg
                                    class="w-5 h-5 text-emerald-500 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                                    />
                                </svg>
                                <span
                                    class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate"
                                >
                                    {{ $t('company_profile.transactions.form.current_attachment') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="transaction.attachment_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-2.5 py-1 text-2xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 rounded-lg transition-colors"
                                >
                                    {{ $t('company_profile.transactions.table.view_attachment') }}
                                </a>
                                <button
                                    type="button"
                                    @click="form.remove_attachment = true"
                                    class="p-1 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/60 transition-colors"
                                    :title="
                                        $t('company_profile.transactions.form.remove_attachment')
                                    "
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
                            </div>
                        </div>

                        <!-- File Input / Drag & Drop -->
                        <div class="relative">
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                class="hidden"
                                @change="handleFileSelected"
                            />
                            <div
                                @click="$refs.fileInputRef?.click()"
                                class="border-2 border-dashed border-gray-300 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-500 rounded-xl p-4 text-center cursor-pointer transition-all hover:bg-emerald-50/20 dark:hover:bg-emerald-950/10"
                            >
                                <div
                                    v-if="selectedFileName"
                                    class="flex items-center justify-center gap-2 text-xs font-bold text-emerald-600 dark:text-emerald-400"
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
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span class="truncate max-w-xs">{{ selectedFileName }}</span>
                                    <button
                                        type="button"
                                        @click.stop="clearSelectedFile"
                                        class="text-rose-500 hover:text-rose-700 p-1"
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
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <div v-else class="space-y-1">
                                    <svg
                                        class="w-7 h-7 mx-auto text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                        />
                                    </svg>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                        {{ $t('company_profile.transactions.form.drop_file') }}
                                    </p>
                                    <p class="text-2xs text-gray-400 dark:text-gray-500">
                                        {{
                                            $t('company_profile.transactions.form.attachment_hint')
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.attachment" class="mt-1 text-xs text-red-500">
                            {{ form.errors.attachment }}
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5"
                        >
                            {{ $t('company_profile.transactions.form.notes') }}
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm transition-all"
                        ></textarea>
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-red-500">
                            {{ form.errors.notes }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div
                        class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800"
                    >
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-5 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors"
                        >
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 rounded-xl shadow-md hover:shadow-lg transition-all disabled:opacity-50 flex items-center gap-2"
                        >
                            <svg
                                v-if="form.processing"
                                class="w-4 h-4 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v8H4z"
                                ></path>
                            </svg>
                            <span>{{ $t('common.save') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    transaction: Object,
    incomeCategories: {
        type: Array,
        default: () => [],
    },
    vatEnabled: {
        type: Boolean,
        default: true,
    },
    vatRate: {
        type: [Number, String],
        default: 15,
    },
    centerId: [Number, String],
});

const emit = defineEmits(['close', 'saved']);

const searchResults = ref([]);
const selectedContactKey = ref(null);
const initialContact = ref(null);
const enteredAmount = ref('');
const fileInputRef = ref(null);
const selectedFileName = ref('');

const form = useForm({
    title: '',
    transaction_date: new Date().toISOString().substring(0, 10),
    transaction_type: 'revenue',
    income_category_id: '',
    amount: '',
    is_taxable: false,
    is_vat_inclusive: false,
    tax_amount: '0.00',
    total_amount: '0.00',
    contact_type: null,
    contact_id: null,
    notes: '',
    attachment: null,
    remove_attachment: false,
    center_id: null,
});

const transactionTypeOptions = computed(() => [
    { value: 'revenue', label: t('company_profile.transactions.form.type_revenue') },
    { value: 'expense', label: t('company_profile.transactions.form.type_expense') },
]);

// Filter categories based on transaction type
const filteredCategories = computed(() => {
    return (props.incomeCategories || []).filter(
        (cat) => cat.transaction_type === form.transaction_type
    );
});

const filteredCategoryOptions = computed(() => {
    return filteredCategories.value.map((cat) => ({
        value: cat.id,
        label: cat.name || cat.name_ar || cat.name_en,
        is_taxable: Boolean(cat.is_taxable),
    }));
});

// Currently selected contact details
const selectedContact = computed(() => {
    if (!selectedContactKey.value) return null;
    const found = searchResults.value.find((c) => c.value === selectedContactKey.value);
    if (found) return found;
    if (initialContact.value && initialContact.value.value === selectedContactKey.value) {
        return initialContact.value;
    }
    return null;
});

// Merged contact options for SearchableSelect
const contactOptions = computed(() => {
    const list = [...searchResults.value];
    if (initialContact.value && !list.some((c) => c.value === initialContact.value.value)) {
        list.unshift(initialContact.value);
    }
    return list;
});

// Smart rule: when is taxable allowed?
const canBeTaxable = computed(() => {
    if (!props.vatEnabled) return false;
    if (form.transaction_type === 'expense') {
        // Must select a supplier and that supplier must have a tax number
        if (form.contact_type !== 'supplier' || !form.contact_id) return false;
        return Boolean(selectedContact.value?.tax_number);
    }
    if (form.transaction_type === 'revenue') {
        // Can be taxable if customer is selected
        return Boolean(form.contact_id);
    }
    return false;
});

// Watch canBeTaxable - disable taxable if conditions no longer met
watch(canBeTaxable, (allowed) => {
    if (!allowed && form.is_taxable) {
        form.is_taxable = false;
    }
});

// Watch category selection - auto enable taxable if category has is_taxable: true
watch(
    () => form.income_category_id,
    (catId) => {
        if (!catId) return;
        const cat = props.incomeCategories.find((c) => c.id == catId);
        if (cat?.is_taxable && canBeTaxable.value) {
            form.is_taxable = true;
        }
    }
);

// Recalculate VAT and amounts
function calculateAmounts() {
    const val = parseFloat(enteredAmount.value) || 0;
    const rate = parseFloat(props.vatRate || 15);

    if (!form.is_taxable) {
        form.amount = val > 0 ? val.toFixed(2) : '';
        form.tax_amount = '0.00';
        form.total_amount = val > 0 ? val.toFixed(2) : '0.00';
        return;
    }

    if (form.is_vat_inclusive) {
        // User entered gross amount including VAT
        const tax = Math.round((val - val / (1 + rate / 100)) * 100) / 100;
        const net = Math.round((val - tax) * 100) / 100;
        form.amount = net.toFixed(2);
        form.tax_amount = tax.toFixed(2);
        form.total_amount = val.toFixed(2);
    } else {
        // User entered net amount before VAT
        const tax = Math.round(val * (rate / 100) * 100) / 100;
        const tot = Math.round((val + tax) * 100) / 100;
        form.amount = val.toFixed(2);
        form.tax_amount = tax.toFixed(2);
        form.total_amount = tot.toFixed(2);
    }
}

watch(
    [
        () => enteredAmount.value,
        () => form.is_taxable,
        () => form.is_vat_inclusive,
        () => props.vatRate,
    ],
    () => {
        calculateAmounts();
    }
);

// Watch selectedContactKey to map back to form contact_type & contact_id
watch(selectedContactKey, (val) => {
    if (val) {
        const parts = val.split('-');
        form.contact_type = parts[0];
        form.contact_id = parseInt(parts[1], 10);

        // If the selected category is taxable and now tax is allowed, activate it
        const cat = props.incomeCategories.find((c) => c.id == form.income_category_id);
        if (cat?.is_taxable && canBeTaxable.value) {
            form.is_taxable = true;
        }
    } else {
        form.contact_type = null;
        form.contact_id = null;
    }
});

// Reset category if transaction type changes
watch(
    () => form.transaction_type,
    () => {
        form.income_category_id = '';
        selectedContactKey.value = null;
    }
);

// Initialize form on modal open
watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) return;

        form.clearErrors();
        form.attachment = null;
        form.remove_attachment = false;
        selectedFileName.value = '';

        if (props.transaction) {
            form.title = props.transaction.title || '';
            form.transaction_date =
                props.transaction.transaction_date || new Date().toISOString().substring(0, 10);
            form.transaction_type = props.transaction.transaction_type || 'revenue';
            form.income_category_id = props.transaction.income_category_id || '';
            form.is_taxable = Boolean(props.transaction.is_taxable);
            form.is_vat_inclusive = Boolean(props.transaction.is_vat_inclusive);
            form.contact_type = props.transaction.contact_type || null;
            form.contact_id = props.transaction.contact_id || null;
            form.notes = props.transaction.notes || '';
            form.center_id = props.transaction.center_id || props.centerId || null;

            // Initialize enteredAmount
            if (props.transaction.is_vat_inclusive) {
                enteredAmount.value = props.transaction.total_amount || '';
            } else {
                enteredAmount.value = props.transaction.amount || '';
            }

            form.amount = props.transaction.amount || '0.00';
            form.tax_amount = props.transaction.tax_amount || '0.00';
            form.total_amount = props.transaction.total_amount || '0.00';

            if (props.transaction.contact) {
                const c = props.transaction.contact;
                const type = props.transaction.contact_type || 'customer';
                const key = `${type}-${c.id}`;
                const roleLabel =
                    type === 'supplier'
                        ? t('company_profile.transactions.supplier_role')
                        : t('common.customer');
                const label = `${c.name} (${c.phone || ''}) - ${roleLabel}`;

                initialContact.value = {
                    value: key,
                    label,
                    type,
                    id: c.id,
                    tax_number: c.tax_number || null,
                };
                selectedContactKey.value = key;
            } else {
                initialContact.value = null;
                selectedContactKey.value = null;
            }
        } else {
            form.reset();
            form.transaction_date = new Date().toISOString().substring(0, 10);
            form.center_id = props.centerId || null;
            enteredAmount.value = '';
            selectedContactKey.value = null;
            initialContact.value = null;
            searchResults.value = [];
        }
    }
);

// File upload handling
function handleFileSelected(event) {
    const file = event.target.files?.[0];
    if (file) {
        form.attachment = file;
        selectedFileName.value = `${file.name} (${(file.size / 1024).toFixed(0)} KB)`;
        form.remove_attachment = false;
    }
}

function clearSelectedFile() {
    form.attachment = null;
    selectedFileName.value = '';
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

// Debounced async contact search
function debounce(fn, delay) {
    let timer = null;
    return function (...args) {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => {
            fn.apply(this, args);
        }, delay);
    };
}

const handleAsyncSearch = debounce(async (query) => {
    if (!query) return;
    try {
        const response = await axios.get('/app/settings/company/contacts/search', {
            params: { search: query },
        });
        searchResults.value = response.data || [];
    } catch (e) {
        searchResults.value = [];
    }
}, 300);

function submit() {
    form.clearErrors();

    if (form.is_taxable && !form.contact_id) {
        form.setError('contact_id', t('company_profile.transactions.taxable_partner_hint'));
        return;
    }

    if (form.is_taxable && form.transaction_type === 'expense' && !canBeTaxable.value) {
        form.setError(
            'is_taxable',
            t('company_profile.transactions.expense_tax_requires_supplier_vat')
        );
        return;
    }

    const url = props.transaction
        ? `/app/settings/company/transactions/${props.transaction.id}`
        : '/app/settings/company/transactions';

    if (props.transaction) {
        // If updating with attachment, use POST with _method PUT for multipart
        if (form.attachment instanceof File) {
            form.transform((data) => ({
                ...data,
                _method: 'PUT',
            })).post(url, {
                onSuccess: () => emit('saved'),
            });
        } else {
            form.put(url, {
                onSuccess: () => emit('saved'),
            });
        }
    } else {
        form.post(url, {
            onSuccess: () => emit('saved'),
        });
    }
}
</script>
