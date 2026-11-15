<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import PaymentMethodSelector from './PaymentMethodSelector.vue';
import BankAccountCard from './BankAccountCard.vue';
import PromoCodeInput from './PromoCodeInput.vue';
import BankTransferReceiptUpload from './BankTransferReceiptUpload.vue';
import MoyasarPaymentForm from './MoyasarPaymentForm.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    plan: { type: Object, default: null }, // Selected plan
    enabledGateways: { type: Array, default: () => [] },
    paymentSettings: { type: Object, default: () => ({}) },
    paymentIcons: { type: Object, default: () => ({ style: 'colored', logos: {} }) },
    bankAccounts: { type: Array, default: () => [] },
    bankInstructions: { type: String, default: null },
    companyInfo: { type: Object, default: () => ({}) },
    vatSettings: { type: Object, default: () => ({ enabled: true, rate: 15, inclusive: false }) },
    initialGateway: { type: String, default: '' },
    billingState: { type: Object, default: null },
});
const emit = defineEmits(['close', 'success']);

const { t, locale } = useI18n();
const page = usePage();

// Read the CSRF token from Inertia's shared page props (always fresh
// per request) — the meta tag in the document can be stale if the
// session rotated or the user opened the page a long time ago.
const csrfToken = computed(
    () =>
        page.props?.csrf_token ||
        page.props?.value?.csrf_token ||
        document.querySelector('meta[name="csrf-token"]')?.content
);

const { activeCurrencyCode: currencyCode } = useNumberFormat();

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
            const defaultGw = pm.default_gateway;
            if (props.billingState?.display_status === 'partially_paid') {
                selectedGateway.value = 'bank_transfer';
            } else if (props.initialGateway) {
                selectedGateway.value = props.initialGateway;
            } else if (defaultGw && (available.includes(defaultGw) || pm[defaultGw]?.enabled)) {
                selectedGateway.value = defaultGw;
            } else if (available.includes('moyasar') || pm.moyasar?.enabled) {
                selectedGateway.value = 'moyasar';
            } else if (available.includes('tap') || pm.tap?.enabled) {
                selectedGateway.value = 'tap';
            } else if (available.includes('paytabs') || pm.paytabs?.enabled) {
                selectedGateway.value = 'paytabs';
            } else if (available.includes('bank_transfer') || pm.bank_transfer_enabled) {
                selectedGateway.value = 'bank_transfer';
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

async function extractErrorMessage(response, fallback) {
    try {
        const data = await response.json();
        if (data?.error) return data.error;
        if (data?.errors && typeof data.errors === 'object') {
            const firstError = Object.values(data.errors).flat()[0];
            if (firstError) return firstError;
        }
        if (data?.message) return data.message;
    } catch {
        // Non-JSON response (e.g. HTML error page)
    }
    return fallback;
}

async function calculate() {
    if (!props.plan) return;
    loading.value = true;
    error.value = null;
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
        if (!response.ok) {
            error.value = await extractErrorMessage(response, t('subscription.pricing_error'));
            return;
        }
        pricing.value = await response.json();
    } catch (e) {
        error.value = t('subscription.pricing_error');
    } finally {
        loading.value = false;
    }
}

async function subscribe() {
    if (!pricing.value) {
        error.value = t('subscription.pricing_error');
        return;
    }
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
        if (!response.ok) {
            error.value = await extractErrorMessage(response, t('subscription.subscription_error'));
            return;
        }
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
    if (props.billingState?.allowed_actions?.can_cancel_pending !== true) {
        return;
    }
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
    <BaseModal :show="!!open" size="lg" :closeable="!loading" @close="close">
        <template #title>
            <div>
                <div class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ t('subscription.subscribe_now', 'اشترك الآن') }}
                </div>
                <p class="mt-0.5 text-xs font-normal text-slate-500 dark:text-slate-400">
                    {{ planName }}
                </p>
            </div>
        </template>

        <div v-if="bankTransferResult" class="space-y-4">
            <div class="text-center">
                <div
                    class="mx-auto mb-3 inline-flex h-14 w-14 items-center justify-center rounded-full bg-selection-bg text-action-primary"
                >
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <MoyasarPaymentForm :form-data="gatewayFormData" @completed="onGatewayCompleted" />
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
                <label class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white">
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
                        {{ formatCurrency(plan?.price_yearly) }} {{ currencyCode }}
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
                            {{ formatCurrency(pricing.base_price) }} {{ currencyCode }}
                        </span>
                    </div>
                    <div
                        v-if="pricing.discount > 0"
                        class="flex justify-between text-emerald-600 dark:text-emerald-400"
                    >
                        <span>{{ t('subscription.discount', 'الخصم') }}</span>
                        <span class="font-mono">- {{ formatCurrency(pricing.discount) }} {{ currencyCode }}</span>
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
                        <span class="font-mono">{{ formatCurrency(pricing.vat_amount) }} {{ currencyCode }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold dark:border-slate-700"
                    >
                        <span class="text-slate-900 dark:text-white">
                            {{ t('subscription.total', 'الإجمالي') }}
                        </span>
                        <span class="font-mono text-action-primary">
                            {{ formatCurrency(pricing.total) }} {{ currencyCode }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Partial payment gateway lock notice -->
            <p
                v-if="billingState?.display_status === 'partially_paid'"
                class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-medium text-amber-800 dark:border-amber-800/50 dark:bg-amber-900/20 dark:text-amber-200"
            >
                {{ t('system.billing.page.state.gateway_locked_partial') }}
            </p>

            <!-- Payment methods -->
            <PaymentMethodSelector
                v-if="pricing"
                v-model="selectedGateway"
                :enabled-gateways="enabledGateways"
                :payment-methods="paymentSettings"
                :payment-icons="paymentIcons"
                :bank-transfer-enabled="paymentSettings.bank_transfer_enabled"
                :default-gateway="paymentSettings?.default_gateway || ''"
                :total-amount="pricing?.total ?? null"
                :locked-gateway="
                    billingState?.display_status === 'partially_paid' ? 'bank_transfer' : ''
                "
                :locked-reason="
                    billingState?.display_status === 'partially_paid'
                        ? t('system.billing.page.state.gateway_locked_partial')
                        : ''
                "
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
                    <BankAccountCard
                        v-for="(acc, idx) in bankAccounts"
                        :key="idx"
                        :account="acc"
                    />
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

        <template v-if="!success && !bankTransferResult && !gatewayFormData" #footer>
            <div class="w-full space-y-2.5">
                <div class="flex items-center justify-end gap-2 w-full">
                    <AppButton
                        variant="secondary"
                        size="md"
                        :disabled="loading"
                        @click="close"
                    >
                        {{ t('common.cancel', 'إلغاء') }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :loading="loading"
                        :disabled="loading || !pricing"
                        @click="subscribe"
                    >
                        {{ t('subscription.continue_to_payment', 'متابعة الدفع') }}
                    </AppButton>
                </div>
                <p
                    class="flex items-center justify-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400"
                >
                    <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>{{ t('subscription.secure_payment_note') }}</span>
                </p>
            </div>
        </template>
    </BaseModal>
</template>
