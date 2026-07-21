<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    subscription: { type: Object, default: null },
    paymentSettings: { type: Object, default: () => ({}) },
    vatSettings: { type: Object, default: () => ({ enabled: false, rate: 0, inclusive: false }) },
});

const emit = defineEmits(['success', 'close']);

const locale = computed(() => document.documentElement.lang || 'ar');

const form = ref({
    receipt: null,
    transfer_amount: '',
    transfer_date: new Date().toISOString().slice(0, 10),
    transfer_reference: '',
    bank_name: '',
    sender_account_name: '',
    sender_account_number: '',
});

const fileInput = ref(null);
const errors = ref({});
const error = ref(null);
const processing = ref(false);

const maxFileSize = 5; // MB
const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

const defaultAmount = computed(() => {
    if (!props.subscription?.price) return 0;
    const price = parseFloat(props.subscription.price);
    if (props.vatSettings?.enabled && props.vatSettings.rate > 0) {
        const rate = props.vatSettings.rate / 100;
        if (props.vatSettings.inclusive) {
            // VAT is inside the price — display price as-is
            return price.toFixed(2);
        }
        // Exclusive — add VAT on top
        return (price * (1 + rate)).toFixed(2);
    }
    return price.toFixed(2);
});

function pickFile() {
    fileInput.value?.click();
}

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > maxFileSize * 1024 * 1024) {
        errors.value.receipt =
            locale.value === 'ar'
                ? `حجم الملف يتجاوز ${maxFileSize} ميجابايت`
                : `File size exceeds ${maxFileSize} MB`;
        return;
    }
    if (!acceptedTypes.includes(file.type)) {
        errors.value.receipt =
            locale.value === 'ar'
                ? 'نوع الملف غير مدعوم (JPG, PNG, WEBP, PDF فقط)'
                : 'Unsupported file type (JPG, PNG, WEBP, PDF only)';
        return;
    }

    form.value.receipt = file;
    errors.value.receipt = null;
}

function removeFile() {
    form.value.receipt = null;
    if (fileInput.value) fileInput.value.value = '';
}

async function submit() {
    if (!form.value.receipt) {
        errors.value.receipt =
            locale.value === 'ar' ? 'يرجى رفع الإيصال' : 'Please upload the receipt';
        return;
    }
    if (!form.value.transfer_amount) {
        errors.value.transfer_amount =
            locale.value === 'ar' ? 'يرجى إدخال المبلغ' : 'Please enter the amount';
        return;
    }
    if (!form.value.transfer_date) {
        errors.value.transfer_date =
            locale.value === 'ar' ? 'يرجى إدخال تاريخ التحويل' : 'Please enter transfer date';
        return;
    }

    processing.value = true;
    error.value = null;
    errors.value = {};

    try {
        const data = new FormData();
        data.append('receipt', form.value.receipt);
        data.append('transfer_amount', form.value.transfer_amount);
        data.append('transfer_date', form.value.transfer_date);
        if (form.value.transfer_reference)
            data.append('transfer_reference', form.value.transfer_reference);
        if (form.value.bank_name) data.append('bank_name', form.value.bank_name);
        if (form.value.sender_account_name)
            data.append('sender_account_name', form.value.sender_account_name);
        if (form.value.sender_account_number)
            data.append('sender_account_number', form.value.sender_account_number);

        const response = await fetch(
            `/app/settings/company/subscription/receipt/${props.subscription.id}`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: data,
            }
        );

        const result = await response.json();
        if (response.ok) {
            emit('success', result);
        } else {
            if (result.errors) {
                errors.value = Object.fromEntries(
                    Object.entries(result.errors).map(([k, v]) => [k, v[0]])
                );
            }
            error.value =
                result.message || (locale.value === 'ar' ? 'فشل رفع الإيصال' : 'Upload failed');
        }
    } catch (e) {
        error.value = locale.value === 'ar' ? 'حدث خطأ' : 'Error occurred';
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header info -->
        <div
            class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800/50 dark:bg-indigo-900/10"
        >
            <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                {{ locale === 'ar' ? 'تعليمات رفع الإيصال' : 'Receipt upload instructions' }}
            </p>
            <p
                v-if="paymentSettings.bank_transfer_receipt_instructions"
                class="mt-1 whitespace-pre-line text-xs text-indigo-600/80 dark:text-indigo-400/80"
            >
                {{ paymentSettings.bank_transfer_receipt_instructions }}
            </p>
            <p v-else class="mt-1 text-xs text-indigo-600/80 dark:text-indigo-400/80">
                {{
                    locale === 'ar'
                        ? 'يرجى رفع إيصال التحويل البنكي (صورة أو PDF، حتى 5 ميجابايت).'
                        : 'Please upload your bank transfer receipt (image or PDF, up to 5 MB).'
                }}
            </p>
        </div>

        <!-- File upload area -->
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                {{ locale === 'ar' ? 'الإيصال *' : 'Receipt *' }}
            </label>
            <div
                v-if="!form.receipt"
                @click="pickFile"
                class="cursor-pointer rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition-colors hover:border-indigo-400 hover:bg-indigo-50/50 dark:border-slate-600 dark:bg-slate-800/50 dark:hover:border-indigo-500"
            >
                <svg
                    class="mx-auto h-10 w-10 text-slate-400"
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
                <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                    {{ locale === 'ar' ? 'اضغط لرفع الإيصال' : 'Click to upload' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{
                        locale === 'ar'
                            ? 'JPG, PNG, WEBP, PDF — حتى 5 ميجابايت'
                            : 'JPG, PNG, WEBP, PDF — up to 5 MB'
                    }}
                </p>
            </div>
            <div
                v-else
                class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <svg
                        class="h-5 w-5 flex-shrink-0 text-emerald-500"
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
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">
                            {{ form.receipt.name }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ (form.receipt.size / 1024).toFixed(1) }} KB
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="removeFile"
                    class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
            <input
                ref="fileInput"
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.pdf"
                @change="onFileChange"
                class="hidden"
            />
            <p v-if="errors.receipt" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ errors.receipt }}
            </p>
        </div>

        <!-- Transfer details -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'المبلغ المحول *' : 'Amount transferred *' }}
                </label>
                <input
                    v-model="form.transfer_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :placeholder="defaultAmount"
                    dir="ltr"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
                <p v-if="errors.transfer_amount" class="mt-1 text-xs text-rose-600">
                    {{ errors.transfer_amount }}
                </p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'تاريخ التحويل *' : 'Transfer date *' }}
                </label>
                <input
                    v-model="form.transfer_date"
                    type="date"
                    :max="new Date().toISOString().slice(0, 10)"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
                <p v-if="errors.transfer_date" class="mt-1 text-xs text-rose-600">
                    {{ errors.transfer_date }}
                </p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'البنك' : 'Bank name' }}
                </label>
                <input
                    v-model="form.bank_name"
                    type="text"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'مرجع التحويل' : 'Transfer reference' }}
                </label>
                <input
                    v-model="form.transfer_reference"
                    type="text"
                    :placeholder="locale === 'ar' ? 'رقم العملية...' : 'Transaction ID...'"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'اسم المرسل' : 'Sender name' }}
                </label>
                <input
                    v-model="form.sender_account_name"
                    type="text"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ locale === 'ar' ? 'حساب المرسل' : 'Sender account' }}
                </label>
                <input
                    v-model="form.sender_account_number"
                    type="text"
                    dir="ltr"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
            </div>
        </div>

        <p
            v-if="error"
            class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-800/50 dark:bg-rose-900/10 dark:text-rose-300"
        >
            {{ error }}
        </p>

        <div class="flex justify-end gap-2 pt-2">
            <button
                type="button"
                @click="emit('close')"
                :disabled="processing"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
            >
                {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
            </button>
            <button
                type="button"
                @click="submit"
                :disabled="processing || !form.receipt"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg v-if="processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                </svg>
                <span>{{ locale === 'ar' ? 'رفع الإيصال' : 'Upload receipt' }}</span>
            </button>
        </div>
    </div>
</template>
