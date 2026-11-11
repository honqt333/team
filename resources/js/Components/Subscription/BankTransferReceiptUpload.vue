<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref, computed, watch, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    // The setup payload from subscribe() — contains plan, billing_cycle,
    // promo_code, price, VAT, and bank account list. No subscription
    // exists yet — it's created atomically when the receipt is uploaded.
    setup: { type: Object, required: true },
    paymentSettings: { type: Object, default: () => ({}) },
    vatSettings: { type: Object, default: () => ({ enabled: false, rate: 0, inclusive: false }) },
    // Optional: list of pending invoices for the tenant. When the tenant
    // has more than one unpaid invoice, the upload form exposes a
    // dropdown so they can explicitly link the receipt to the right one
    // instead of relying on the controller's "latest pending" fallback.
    pendingInvoices: { type: Array, default: () => [] },
});

const emit = defineEmits(['success', 'close']);

const locale = computed(() => document.documentElement.lang || 'ar');

// Read the CSRF token from Inertia's shared page props (always fresh
// per request) — the meta tag in the document can be stale if the
// session rotated or the user opened the page a long time ago.
const csrfToken = computed(
    () =>
        usePage().props.value?.csrf_token ||
        document.querySelector('meta[name="csrf-token"]')?.content
);

const form = ref({
    receipt: null,
    transfer_amount: '',
    transfer_date: new Date().toISOString().slice(0, 10),
    transfer_reference: '',
    bank_name: '',
    account_name: '',
    notes: '',
    // Optional: explicit invoice link. When null, the controller falls
    // back to the tenant's most recent pending payment (preserves the
    // old behaviour for the single-invoice happy path).
    subscription_invoice_id: null,
});

const invoiceOptions = computed(() => [
    {
        value: null,
        label: t('subscription.receipt.most_recent_default'),
    },
    ...props.pendingInvoices.map((inv) => ({
        value: inv.id,
        label: `${inv.invoice_number || `#${inv.id}`} — ${Number(inv.amount).toFixed(2)} SAR (${new Date(inv.due_date).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US')})`,
    })),
]);

const fileInput = ref(null);
const errors = ref({});
const error = ref(null);
const processing = ref(false);

// Real-time duplicate-reference check. The form polls the server with
// the typed value (debounced 500ms) and shows a red border + helper
// text the moment a match is found, before the user reaches Submit.
const checkingRef = ref(false);
const refCheckError = ref(null);
const refDebounceTimer = ref(null);

function scheduleRefCheck() {
    if (refDebounceTimer.value) clearTimeout(refDebounceTimer.value);
    const val = form.value.transfer_reference?.trim();
    if (!val || val.length < 3) {
        refCheckError.value = null;
        return;
    }
    refDebounceTimer.value = setTimeout(async () => {
        checkingRef.value = true;
        try {
            const res = await fetch(
                `/app/settings/billing/bank-transfer-receipt/check-reference?ref=${encodeURIComponent(val)}`,
                { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
            );
            if (res.ok) {
                const data = await res.json();
                refCheckError.value = data.exists
                    ? t('subscription.receipt.reference_already_used')
                    : null;
            }
        } catch (_) {
            // Network blip — silently skip. The server-side validation
            // will still block the submit if needed.
        } finally {
            checkingRef.value = false;
        }
    }, 500);
}

watch(() => form.value.transfer_reference, scheduleRefCheck);

onUnmounted(() => {
    if (refDebounceTimer.value) clearTimeout(refDebounceTimer.value);
});

const maxFileSize = 5; // MB
const acceptedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

const defaultAmount = computed(() => {
    if (!props.setup?.total) return 0;
    // The controller pre-computes the total (subtotal + VAT) so we
    // display exactly what the tenant needs to transfer.
    return Number(props.setup.total).toFixed(2);
});

// Auto-fill the amount on mount. We prefill with the controller's
// computed total so the tenant doesn't have to type it. The field is
// still editable (so a tenant who overpaid or underpaid intentionally
// can record the actual figure) — we just give them a sensible default
// instead of an empty field. `watch` runs once on mount, then on
// any reactive change (so e.g. switching the invoice selector can
// update the default to that invoice's amount).
watch(
    () => defaultAmount.value,
    (val) => {
        if (val && !form.value.transfer_amount) {
            form.value.transfer_amount = val;
        }
    },
    { immediate: true }
);

// Show the invoice selector only when there's actual ambiguity — one
// pending invoice is the common case and a dropdown there just adds
// noise. The default selection is the first (most recent) pending one.
const showInvoiceSelector = computed(() => props.pendingInvoices.length > 0);

// ─── Progress indicator helpers ──────────────────────────────────────
// Each step has a "done" state derived from the form values; the UI
// shows a green check on completion so the tenant knows what remains.
// Step 1 is the receipt file; Step 2 is the amount (auto-filled, but
// we count it as "done" once the user has viewed the page); Step 3
// is the bank details (bank name + account name + reference).
const step2Done = computed(() => {
    const v = parseFloat(form.value.transfer_amount);
    return !isNaN(v) && v > 0;
});

const step3Done = computed(() =>
    Boolean(
        form.value.bank_name?.trim() &&
        form.value.account_name?.trim() &&
        form.value.transfer_reference?.trim() &&
        !refCheckError.value
    )
);

function stepStateClass(step) {
    if (
        (step === 1 && form.value.receipt) ||
        (step === 2 && step2Done.value) ||
        (step === 3 && step3Done.value)
    ) {
        return 'bg-emerald-500 text-white';
    }
    return 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
}

function stepLabelClass(step) {
    if (
        (step === 1 && form.value.receipt) ||
        (step === 2 && step2Done.value) ||
        (step === 3 && step3Done.value)
    ) {
        return 'font-semibold text-emerald-700 dark:text-emerald-300';
    }
    return 'text-gray-500 dark:text-gray-400';
}

function pickFile() {
    fileInput.value?.click();
}

function onFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > maxFileSize * 1024 * 1024) {
        errors.value.receipt =
            locale.value === 'ar'
                ? t('subscription.receipt.file_too_large', { maxFileSize: maxFileSize })
                : `File size exceeds ${maxFileSize} MB`;
        return;
    }
    if (!acceptedTypes.includes(file.type)) {
        errors.value.receipt = t('subscription.receipt.unsupported_type');
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
        errors.value.receipt = t('subscription.receipt.upload_required');
        return;
    }
    if (!form.value.transfer_amount) {
        errors.value.transfer_amount = t('subscription.receipt.amount_required');
        return;
    }
    if (!form.value.transfer_date) {
        errors.value.transfer_date = t('subscription.receipt.transfer_date_required');
        return;
    }
    // The server-side validation requires these three fields. Block
    // submit early so the user sees the field-level error rather than
    // a generic "Upload failed" toast from the server's 422 response.
    if (!form.value.bank_name?.trim()) {
        errors.value.bank_name = t('subscription.receipt.bank_name_required');
        return;
    }
    if (!form.value.account_name?.trim()) {
        errors.value.account_name = t('subscription.receipt.account_holder_required');
        return;
    }
    if (!form.value.transfer_reference?.trim()) {
        errors.value.transfer_reference = t('subscription.receipt.transfer_reference_required');
        return;
    }
    // Block submit if the real-time duplicate check already found a
    // matching reference — saves a round-trip to the server.
    if (refCheckError.value) {
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
        // Server validation requires these three as non-empty strings.
        // Send them unconditionally so a blank field doesn't 422 the
        // whole request. The Vue client already validates emptiness
        // before submit, so this is just a safety net.
        data.append('transfer_reference', form.value.transfer_reference || '');
        data.append('bank_name', form.value.bank_name || '');
        data.append('account_name', form.value.account_name || '');

        // Send the plan/cycle/promo from the setup payload so the
        // server can atomically create subscription + invoice + payment
        // + receipt in one transaction. No pre-existing subscription.
        if (props.setup?.plan?.id) {
            data.append('plan_id', props.setup.plan.id);
        }
        if (props.setup?.billing_cycle) {
            data.append('billing_cycle', props.setup.billing_cycle);
        }
        if (props.setup?.promo_code) {
            data.append('promo_code', props.setup.promo_code);
        }
        // Optional explicit invoice link — server uses this to scope the
        // receipt to a specific invoice instead of the implicit "latest
        // pending" fallback. Sent only when the user actually picked one
        // from the dropdown.
        if (form.value.subscription_invoice_id) {
            data.append('subscription_invoice_id', form.value.subscription_invoice_id);
        }

        const response = await fetch('/app/settings/billing/bank-transfer-receipt', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken.value || '',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: data,
        });

        const result = await response.json();
        if (response.ok) {
            emit('success', result);
        } else {
            if (result.errors) {
                errors.value = Object.fromEntries(
                    Object.entries(result.errors).map(([k, v]) => [k, v[0]])
                );
            }
            error.value = result.error || t('subscription.receipt.upload_failed');
        }
    } catch (e) {
        error.value = t('common.error_occurred');
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header info -->
        <div class="rounded-lg border border-action-primary/25 bg-selection-bg p-4">
            <p class="text-sm font-semibold text-selection-text">
                {{ $t('subscription.receipt.upload_instructions') }}
            </p>
            <p
                v-if="paymentSettings.bank_transfer_receipt_instructions"
                class="mt-1 whitespace-pre-line text-xs text-action-primary"
            >
                {{ paymentSettings.bank_transfer_receipt_instructions }}
            </p>
            <p v-else class="mt-1 text-xs text-action-primary">
                {{ $t('subscription.receipt.upload_help') }}
            </p>
        </div>

        <!-- Bank accounts panel — shows where the tenant needs to transfer
             the money. The most important information is at the top
             (bank name + account holder) so the tenant can locate the
             right account in their banking app; IBAN / account number
             follow for the actual transfer. -->
        <div
            v-if="setup.bank_accounts && setup.bank_accounts.length"
            class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/50 dark:bg-emerald-900/10"
        >
            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                {{ $t('subscription.receipt.transfer_to_account') }}
            </p>
            <div class="mt-2 space-y-2">
                <div
                    v-for="(acc, idx) in setup.bank_accounts"
                    :key="idx"
                    class="rounded border border-emerald-200 bg-white p-3 text-xs dark:border-emerald-800/50 dark:bg-gray-900/30"
                >
                    <p class="font-semibold text-emerald-700 dark:text-emerald-300">
                        {{ acc.bank_name || '—' }}
                    </p>
                    <p v-if="acc.account_name" class="text-gray-700 dark:text-gray-300">
                        {{ acc.account_name }}
                    </p>
                    <p v-if="acc.iban" class="font-mono text-gray-600 dark:text-gray-400" dir="ltr">
                        IBAN: {{ acc.iban }}
                    </p>
                    <p
                        v-if="acc.account_number"
                        class="font-mono text-gray-600 dark:text-gray-400"
                        dir="ltr"
                    >
                        {{ $t('system.payment_settings.bank_transfer.account_number') }}:
                        {{ acc.account_number }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Progress indicator — 3 steps with visual feedback. Helps the
             tenant see what they've completed and what remains. The
             third step (Transfer details) is "complete" only when ALL
             three required fields are filled. -->
        <div class="flex items-center gap-2 text-xs">
            <div class="flex flex-1 items-center gap-2">
                <div
                    :class="stepStateClass(1)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                >
                    {{ form.receipt ? '✓' : '1' }}
                </div>
                <span :class="stepLabelClass(1)">
                    {{ $t('subscription.receipt.receipt') }}
                </span>
            </div>
            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
            <div class="flex flex-1 items-center gap-2">
                <div
                    :class="stepStateClass(2)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                >
                    {{ step2Done ? '✓' : '2' }}
                </div>
                <span :class="stepLabelClass(2)">
                    {{ $t('common.amount') }}
                </span>
            </div>
            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
            <div class="flex flex-1 items-center gap-2">
                <div
                    :class="stepStateClass(3)"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                >
                    {{ step3Done ? '✓' : '3' }}
                </div>
                <span :class="stepLabelClass(3)">
                    {{ $t('subscription.receipt.bank_details') }}
                </span>
            </div>
        </div>

        <!-- File upload area -->
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                {{ $t('subscription.receipt.receipt_required') }}
            </label>
            <div
                v-if="!form.receipt"
                @click="pickFile"
                class="cursor-pointer rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition-colors hover:border-action-primary hover:bg-selection-bg dark:border-slate-600 dark:bg-slate-800/50"
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
                    {{ $t('subscription.receipt.click_to_upload') }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $t('subscription.receipt.accepted_formats') }}
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

        <!-- Invoice selector (only when tenant has pending invoices to
             disambiguate). Keeps the common single-invoice case clean
             while giving multi-invoice tenants a clear "which one are
             you paying?" choice. -->
        <div v-if="showInvoiceSelector">
            <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                {{ $t('invoices.invoice') }}
            </label>
            <SearchableSelect
                v-model="form.subscription_invoice_id"
                :options="invoiceOptions"
                option-label="label"
                option-value="value"
            />
            <p class="mt-1 text-xs text-slate-500">
                {{ $t('subscription.receipt.select_invoice') }}
            </p>
        </div>

        <!-- Transfer details -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label
                    class="mb-1.5 flex items-center justify-between text-sm font-semibold text-slate-900 dark:text-white"
                >
                    <span>{{ $t('subscription.receipt.amount_transferred') }}</span>
                    <span
                        v-if="defaultAmount"
                        class="inline-flex items-center gap-1 text-[10px] font-medium text-action-primary"
                        :title="
                            locale === 'ar'
                                ? 'المبلغ المتوقع حسب خطتك'
                                : 'Expected amount from your plan'
                        "
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                        {{ $t('subscription.receipt.suggested') }}: {{ defaultAmount }} SAR
                    </span>
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
                <p class="mt-1 text-xs text-slate-500">
                    {{ $t('subscription.receipt.amount_auto') }}
                </p>
                <p v-if="errors.transfer_amount" class="mt-1 text-xs text-rose-600">
                    {{ errors.transfer_amount }}
                </p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $t('subscription.receipt.transfer_date') }}
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
                    {{ $t('subscription.receipt.bank_name') }}
                </label>
                <input
                    v-model="form.bank_name"
                    type="text"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
                <p v-if="errors.bank_name" class="mt-1 text-xs text-rose-600">
                    {{ errors.bank_name }}
                </p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $t('subscription.receipt.transfer_reference') }}
                </label>
                <input
                    v-model="form.transfer_reference"
                    type="text"
                    :placeholder="locale === 'ar' ? 'رقم العملية...' : 'Transaction ID...'"
                    :class="
                        refCheckError
                            ? 'border-rose-500 focus:border-danger focus:ring-danger'
                            : 'border-slate-300 dark:border-slate-600'
                    "
                    class="w-full rounded-lg border bg-white px-3 py-2 text-sm font-mono dark:bg-slate-700 dark:text-white"
                    @input="scheduleRefCheck"
                />
                <p v-if="checkingRef" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ $t('subscription.receipt.checking_reference') }}
                </p>
                <p
                    v-else-if="refCheckError"
                    class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400"
                >
                    ⚠ {{ refCheckError }}
                </p>
                <p v-if="errors.transfer_reference" class="mt-1 text-xs text-rose-600">
                    {{ errors.transfer_reference }}
                </p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $t('subscription.receipt.account_holder_name') }}
                </label>
                <input
                    v-model="form.account_name"
                    type="text"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                />
                <p v-if="errors.account_name" class="mt-1 text-xs text-rose-600">
                    {{ errors.account_name }}
                </p>
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
                {{ $t('common.cancel') }}
            </button>
            <button
                type="button"
                @click="submit"
                :disabled="processing || !form.receipt"
                class="inline-flex items-center gap-2 rounded-lg bg-action-primary px-5 py-2 text-sm font-semibold text-white hover:bg-action-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
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
                <span>{{ $t('subscription.receipt.upload_receipt') }}</span>
            </button>
        </div>
    </div>
</template>
