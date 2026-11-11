<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PaymentMethodSelector from './PaymentMethodSelector.vue';
import PromoCodeInput from './PromoCodeInput.vue';
import BankTransferReceiptUpload from './BankTransferReceiptUpload.vue';
import MoyasarPaymentForm from './MoyasarPaymentForm.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    plan: { type: Object, default: null }, // Selected plan
    enabledGateways: { type: Array, default: () => [] },
    paymentSettings: { type: Object, default: () => ({}) },
    bankAccounts: { type: Array, default: () => [] },
    bankInstructions: { type: String, default: null },
    companyInfo: { type: Object, default: () => ({}) },
    vatSettings: { type: Object, default: () => ({ enabled: true, rate: 15, inclusive: false }) },
});
const emit = defineEmits(['close', 'success']);

const { t, locale } = useI18n();

// Read the CSRF token from Inertia's shared page props (always fresh
// per request) — the meta tag in the document can be stale if the
// session rotated or the user opened the page a long time ago.
const csrfToken = computed(
    () =>
        usePage().props.value?.csrf_token ||
        document.querySelector('meta[name="csrf-token"]')?.content
);

const billingCycle = ref('yearly');
const promoInput = ref('');
const validPromo = ref(null);
const selectedGateway = ref('');
const pricing = ref(null);
const loading = ref(false);
const error = ref(null);
const success = ref(null);
const bankTransferResult = ref(null);
const gatewayFormData = ref(null); // for client-side forms (Moyasar)
const currentPaymentId = ref(null); // for post-redirect success page

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && props.plan) {
            billingCycle.value = 'yearly';
            promoInput.value = '';
            validPromo.value = null;
            const available = (props.enabledGateways || [])
                .map((g) => (typeof g === 'string' ? g : g?.name || g?.key))
                .filter(Boolean);
            const pm = props.paymentSettings || {};
            if (available.includes('moyasar') || pm.moyasar?.enabled) {
                selectedGateway.value = 'moyasar';
            } else if (available.includes('bank_transfer') || pm.bank_transfer_enabled) {
                selectedGateway.value = 'bank_transfer';
            } else if (available.includes('tap') || pm.tap?.enabled) {
                selectedGateway.value = 'tap';
            } else if (available.length > 0) {
                selectedGateway.value = available[0];
            } else {
                selectedGateway.value = 'bank_transfer';
            }
            pricing.value = null;
            error.value = null;
            success.value = null;
            bankTransferResult.value = null;
            gatewayFormData.value = null;
            currentPaymentId.value = null;
            calculate();
        }
    }
);

watch([billingCycle, validPromo], () => {
    if (props.open) calculate();
});

async function calculate() {
    if (!props.plan) return;
    loading.value = true;
    try {
        const response = await fetch('/app/settings/billing/calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.value || '',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                plan_id: props.plan.id,
                billing_cycle: billingCycle.value,
                promo_code: validPromo.value?.code || null,
            }),
        });
        pricing.value = await response.json();
    } catch (e) {
        error.value = t('subscription.pricing_error');
    } finally {
        loading.value = false;
    }
}

async function subscribe() {
    if (!selectedGateway.value) {
        error.value = t('subscription.choose_payment_method');
        return;
    }

    loading.value = true;
    error.value = null;
    try {
        const response = await fetch('/app/settings/billing/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.value || '',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                plan_id: props.plan.id,
                billing_cycle: billingCycle.value,
                gateway: selectedGateway.value,
                promo_code: validPromo.value?.code || null,
            }),
        });
        const data = await response.json();

        if (data.error) {
            error.value = data.error;
            return;
        }

        if (data.bank_transfer_setup) {
            // The controller returned a setup payload (not a subscription
            // yet) — the tenant still has to upload a receipt before any
            // subscription is recorded. Pass the setup data to the upload
            // component so it knows what plan/price/bank accounts to show.
            bankTransferResult.value = data;
            return;
        }

        if (data.bank_transfer) {
            // Backwards-compat: if the old field is present (shouldn't be
            // with the new controller), still treat it as a setup payload.
            bankTransferResult.value = data;
            return;
        }

        // For online gateways: redirect to payment gateway form
        if (data.form_data) {
            const form = data.form_data;

            // 1) Gateway returns a hosted payment URL (Tap, Tabby, Tamara)
            if (form.payment_url) {
                window.location.href = form.payment_url;
                return;
            }

            // 2) Gateway returns HTML form to inject and submit
            if (form.form_html) {
                const div = document.createElement('div');
                div.innerHTML = form.form_html;
                document.body.appendChild(div);
                const formEl = div.querySelector('form');
                if (formEl) formEl.submit();
                return;
            }

            // 3) Gateway returns client-side form data (Moyasar)
            //    Render the gateway's form inside the modal so the user
            //    can complete payment without leaving the page.
            if (form.gateway === 'moyasar' && form.publishable_key) {
                gatewayFormData.value = form;
                currentPaymentId.value = data.payment?.id || null;
                return;
            }

            // 4) Fallback — at this point we have nothing to render.
            //    This used to silently close the modal with "تم إرسال
            //    الطلب" which was confusing. Show an explicit error so
            //    the tenant knows the gateway didn't return usable data.
            error.value = t('subscription.subscription_error');
        }
    } catch (e) {
        error.value = t('subscription.subscription_error');
    } finally {
        loading.value = false;
    }
}

async function cancelPendingAttempt() {
    try {
        await fetch('/app/settings/billing/cancel-pending', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.value || '',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    } catch (e) {
        // silent catch
    }
}

async function close() {
    if (loading.value) return;
    if (gatewayFormData.value || bankTransferResult.value || currentPaymentId.value) {
        await cancelPendingAttempt();
    }
    bankTransferResult.value = null;
    gatewayFormData.value = null;
    currentPaymentId.value = null;
    success.value = null;
    emit('close');
}

function onReceiptUploaded() {
    success.value = t('subscription.receipt_uploaded');
    setTimeout(() => {
        emit('success');
        emit('close');
        router.reload();
    }, 2500);
}

/**
 * Handle a gateway client-side form completion event.
 * For Moyasar, the form posts the card to Moyasar's servers and then
 * redirects the browser to the callback_url we set in initiate(). The
 * `on_completed` fires mainly for 3DS failures / cancellations, so we
 * just close the modal here and let the redirect handle success.
 */
async function onGatewayCompleted(payment) {
    // The form's callback_url is the payment.callback route, so a successful
    // 3DS + payment will redirect the browser to /app/settings/billing/callback/{id}
    // and render the SubscriptionSuccess page. Closing the modal here for
    // cancelled/failed flows.
    if (payment && payment.status === 'paid') {
        // Browser will navigate away — nothing to do.
        return;
    }
    // User cancelled or 3DS failed — cancel pending attempt and reset
    await cancelPendingAttempt();
    gatewayFormData.value = null;
    currentPaymentId.value = null;
}

async function cancelGatewayForm() {
    await cancelPendingAttempt();
    gatewayFormData.value = null;
    currentPaymentId.value = null;
}

function onPromoApplied(data) {
    validPromo.value = data;
}
function onPromoCleared() {
    validPromo.value = null;
}

const planName = computed(() => {
    if (!props.plan) return '';
    return locale.value === 'ar' ? props.plan.name_ar : props.plan.name_en;
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(amount || 0);
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
            @click.self="close"
        >
            <div
                class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl dark:bg-slate-800 max-h-[90vh] overflow-y-auto"
            >
                <!-- Header -->
                <div
                    class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800"
                >
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ t('subscription.subscribe_now', 'اشترك الآن') }}
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ planName }}
                        </p>
                    </div>
                    <button
                        @click="close"
                        :disabled="loading"
                        class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 disabled:opacity-50 dark:hover:bg-slate-700"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div v-if="bankTransferResult" class="space-y-4 p-6">
                    <div class="text-center">
                        <div
                            class="mx-auto mb-3 inline-flex h-14 w-14 items-center justify-center rounded-full bg-selection-bg text-action-primary"
                        >
                            <svg
                                class="h-7 w-7"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{
                                t(
                                    'subscription.bank_transfer_title',
                                    'تم استلام طلبك — ارفع الإيصال لتفعيل الاشتراك'
                                )
                            }}
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{
                                t(
                                    'subscription.bank_transfer_hint',
                                    'حوّل المبلغ على أحد الحسابات أعلاه، ثم ارفع الإيصال هنا'
                                )
                            }}
                        </p>
                    </div>

                    <BankTransferReceiptUpload
                        :setup="bankTransferResult"
                        :pending-invoices="bankTransferResult?.pending_invoices || []"
                        :payment-settings="paymentSettings"
                        :vat-settings="vatSettings"
                        @success="onReceiptUploaded"
                        @close="emit('close')"
                    />
                </div>

                <!-- Gateway client-side form (Moyasar) -->
                <div v-else-if="gatewayFormData" class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            {{ t('subscription.enter_card_details') }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('subscription.auto_activate_notice') }}
                        </p>
                    </div>
                    <MoyasarPaymentForm
                        :form-data="gatewayFormData"
                        @completed="onGatewayCompleted"
                    />
                    <div class="mt-4 text-center">
                        <button
                            type="button"
                            class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
                            @click="cancelGatewayForm"
                        >
                            {{ t('subscription.cancel_and_go_back') }}
                        </button>
                    </div>
                </div>

                <div v-else-if="success" class="p-8 text-center">
                    <div
                        class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300"
                    >
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ success }}
                    </p>
                </div>

                <div v-else class="space-y-6 p-6">
                    <!-- Billing cycle: Annual only -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t('subscription.billing_cycle', 'فترة الاشتراك') }}
                        </label>
                        <div
                            class="rounded-xl border-2 border-action-primary bg-selection-bg p-4 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-lg bg-action-primary text-white flex items-center justify-center"
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
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ $t('subscription.annual_subscription_12_months') }}
                                    </p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $t('subscription.annual_cycle_desc') }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-lg font-black text-action-primary">
                                {{ formatCurrency(plan?.price_yearly) }} SAR
                            </span>
                        </div>
                    </div>

                    <!-- Promo code -->
                    <PromoCodeInput
                        v-model="promoInput"
                        :valid-promo="validPromo"
                        @applied="onPromoApplied"
                        @cleared="onPromoCleared"
                    />

                    <!-- Price summary -->
                    <div
                        v-if="pricing"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50"
                    >
                        <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('subscription.order_summary', 'ملخص الطلب') }}
                        </p>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-600 dark:text-slate-400">
                                    {{ t('subscription.subtotal', 'السعر الأساسي') }}
                                </span>
                                <span class="font-mono text-slate-900 dark:text-white">
                                    {{ formatCurrency(pricing.base_price) }} SAR
                                </span>
                            </div>
                            <div
                                v-if="pricing.discount > 0"
                                class="flex justify-between text-emerald-600 dark:text-emerald-400"
                            >
                                <span>{{ t('subscription.discount', 'الخصم') }}</span>
                                <span class="font-mono">
                                    - {{ formatCurrency(pricing.discount) }} SAR
                                </span>
                            </div>
                            <div
                                v-if="vatSettings.enabled && pricing.vat_amount > 0"
                                class="flex justify-between text-slate-500 dark:text-slate-400"
                            >
                                <span>
                                    {{
                                        t(
                                            'subscription.vat_label',
                                            `ضريبة القيمة المضافة (${vatSettings.rate}%)`,
                                            { rate: vatSettings.rate }
                                        )
                                    }}
                                </span>
                                <span class="font-mono">
                                    {{ formatCurrency(pricing.vat_amount) }} SAR
                                </span>
                            </div>
                            <div
                                class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold dark:border-slate-700"
                            >
                                <span class="text-slate-900 dark:text-white">
                                    {{ t('subscription.total', 'الإجمالي') }}
                                </span>
                                <span class="font-mono text-action-primary">
                                    {{ formatCurrency(pricing.total) }} SAR
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment methods -->
                    <PaymentMethodSelector
                        v-model="selectedGateway"
                        :enabled-gateways="enabledGateways"
                        :payment-methods="paymentSettings"
                        :bank-transfer-enabled="paymentSettings.bank_transfer_enabled"
                    />

                    <!-- Bank transfer details (if selected) -->
                    <div
                        v-if="selectedGateway === 'bank_transfer' && bankAccounts.length"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50"
                    >
                        <p class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('subscription.bank_accounts', 'الحسابات البنكية') }}
                        </p>
                        <div class="space-y-3">
                            <div
                                v-for="(acc, idx) in bankAccounts"
                                :key="idx"
                                class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ acc.bank_name }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
                                    {{ acc.account_name }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-xs text-slate-700 dark:text-slate-300"
                                    dir="ltr"
                                >
                                    {{ acc.iban || acc.account_number }}
                                </p>
                            </div>
                        </div>
                        <p
                            v-if="bankInstructions"
                            class="mt-3 whitespace-pre-line text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ bankInstructions }}
                        </p>
                    </div>

                    <!-- Error -->
                    <p
                        v-if="error"
                        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-800/50 dark:bg-rose-900/10 dark:text-rose-300"
                    >
                        {{ error }}
                    </p>
                </div>

                <!-- Footer -->
                <div
                    v-if="!success && !bankTransferResult"
                    class="sticky bottom-0 flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-900/50"
                >
                    <button
                        type="button"
                        @click="close"
                        :disabled="loading"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        {{ t('common.cancel', 'إلغاء') }}
                    </button>
                    <button
                        type="button"
                        @click="subscribe"
                        :disabled="loading || !pricing"
                        class="inline-flex items-center gap-2 rounded-lg bg-action-primary px-5 py-2 text-sm font-semibold text-white hover:bg-action-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg
                            v-if="loading"
                            class="h-4 w-4 animate-spin"
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
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        <span>{{ t('subscription.continue_to_payment', 'متابعة الدفع') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
