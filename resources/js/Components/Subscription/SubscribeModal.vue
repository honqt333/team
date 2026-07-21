<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
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

const locale = computed(() => document.documentElement.lang || 'ar');

const billingCycle = ref('monthly');
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
            billingCycle.value = 'monthly';
            promoInput.value = '';
            validPromo.value = null;
            selectedGateway.value = props.enabledGateways[0]?.name || 'bank_transfer';
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
        const response = await fetch('/app/settings/company/subscription/calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
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
        error.value = locale.value === 'ar' ? 'خطأ في حساب السعر' : 'Pricing error';
    } finally {
        loading.value = false;
    }
}

async function subscribe() {
    if (!selectedGateway.value) {
        error.value = locale.value === 'ar' ? 'اختر وسيلة الدفع' : 'Choose a payment method';
        return;
    }

    loading.value = true;
    error.value = null;
    try {
        const response = await fetch('/app/settings/company/subscription/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
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

        if (data.bank_transfer) {
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
            error.value =
                locale.value === 'ar'
                    ? 'لم نتمكن من تحضير نموذج الدفع. حاول مرة أخرى أو اختر وسيلة دفع أخرى.'
                    : 'Could not prepare the payment form. Please try again or choose another payment method.';
        }
    } catch (e) {
        error.value = locale.value === 'ar' ? 'خطأ في إنشاء الاشتراك' : 'Subscription error';
    } finally {
        loading.value = false;
    }
}

function close() {
    if (loading.value) return;
    bankTransferResult.value = null;
    gatewayFormData.value = null;
    currentPaymentId.value = null;
    success.value = null;
    emit('close');
}

function onReceiptUploaded() {
    success.value =
        locale.value === 'ar'
            ? 'تم رفع الإيصال بنجاح. سيتم تفعيل اشتراكك فور مراجعته من الإدارة.'
            : 'Receipt uploaded successfully. Your subscription will be activated after review.';
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
function onGatewayCompleted(payment) {
    // The form's callback_url is the payment.callback route, so a successful
    // 3DS + payment will redirect the browser to /app/settings/company/subscription/callback/{id}
    // and render the SubscriptionSuccess page. Closing the modal here for
    // cancelled/failed flows.
    if (payment && payment.status === 'paid') {
        // Browser will navigate away — nothing to do.
        return;
    }
    // User cancelled or 3DS failed — close and let them try again.
    gatewayFormData.value = null;
}

function cancelGatewayForm() {
    gatewayFormData.value = null;
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
                            {{ locale === 'ar' ? 'اشترك الآن' : 'Subscribe now' }}
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
                            class="mx-auto mb-3 inline-flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300"
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
                                locale === 'ar'
                                    ? 'تم استلام طلبك — ارفع الإيصال لتفعيل الاشتراك'
                                    : 'Order received — Upload receipt to activate'
                            }}
                        </h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{
                                locale === 'ar'
                                    ? 'حوّل المبلغ على أحد الحسابات أعلاه، ثم ارفع الإيصال هنا'
                                    : 'Transfer the amount to one of the bank accounts above, then upload the receipt here'
                            }}
                        </p>
                    </div>

                    <BankTransferReceiptUpload
                        :subscription="bankTransferResult.subscription"
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
                            {{ locale === 'ar' ? 'أدخل بيانات البطاقة' : 'Enter card details' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{
                                locale === 'ar'
                                    ? 'سيتم تفعيل اشتراكك تلقائياً بعد إتمام الدفع'
                                    : 'Your subscription will be activated automatically after payment'
                            }}
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
                            {{ locale === 'ar' ? 'إلغاء والعودة' : 'Cancel and go back' }}
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
                    <!-- Billing cycle selector -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-slate-900 dark:text-white"
                        >
                            {{ locale === 'ar' ? 'فترة الاشتراك' : 'Billing cycle' }}
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="billingCycle = 'monthly'"
                                :class="[
                                    'rounded-lg border-2 p-3 text-center transition-all',
                                    billingCycle === 'monthly'
                                        ? 'border-indigo-600 bg-indigo-50/50 dark:border-indigo-400 dark:bg-indigo-900/20'
                                        : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600',
                                ]"
                            >
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ locale === 'ar' ? 'شهري' : 'Monthly' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(plan?.price_monthly) }} SAR
                                </p>
                            </button>
                            <button
                                type="button"
                                @click="billingCycle = 'yearly'"
                                :class="[
                                    'rounded-lg border-2 p-3 text-center transition-all relative',
                                    billingCycle === 'yearly'
                                        ? 'border-indigo-600 bg-indigo-50/50 dark:border-indigo-400 dark:bg-indigo-900/20'
                                        : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600',
                                ]"
                            >
                                <span
                                    v-if="plan?.yearly_discount"
                                    class="absolute -top-2 right-3 rounded-full bg-emerald-500 px-2 py-0.5 text-[10px] font-bold text-white"
                                >
                                    {{
                                        locale === 'ar'
                                            ? `وفر ${plan.yearly_discount}%`
                                            : `Save ${plan.yearly_discount}%`
                                    }}
                                </span>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ locale === 'ar' ? 'سنوي' : 'Yearly' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ formatCurrency(plan?.price_yearly) }} SAR
                                </p>
                            </button>
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
                            {{ locale === 'ar' ? 'ملخص الطلب' : 'Order summary' }}
                        </p>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-600 dark:text-slate-400">
                                    {{ locale === 'ar' ? 'السعر الأساسي' : 'Subtotal' }}
                                </span>
                                <span class="font-mono text-slate-900 dark:text-white">
                                    {{ formatCurrency(pricing.base_price) }} SAR
                                </span>
                            </div>
                            <div
                                v-if="pricing.discount > 0"
                                class="flex justify-between text-emerald-600 dark:text-emerald-400"
                            >
                                <span>{{ locale === 'ar' ? 'الخصم' : 'Discount' }}</span>
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
                                        locale === 'ar'
                                            ? `ضريبة القيمة المضافة (${vatSettings.rate}%)`
                                            : `VAT (${vatSettings.rate}%)`
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
                                    {{ locale === 'ar' ? 'الإجمالي' : 'Total' }}
                                </span>
                                <span class="font-mono text-indigo-600 dark:text-indigo-400">
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
                            {{ locale === 'ar' ? 'الحسابات البنكية' : 'Bank accounts' }}
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
                        {{ locale === 'ar' ? 'إلغاء' : 'Cancel' }}
                    </button>
                    <button
                        type="button"
                        @click="subscribe"
                        :disabled="loading || !pricing"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
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
                        <span>{{ locale === 'ar' ? 'متابعة الدفع' : 'Continue to payment' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
