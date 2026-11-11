<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Exceptions\PaymentMismatchException;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;

/**
 * Checks that a gateway callback really settles the payment it is
 * attached to.
 *
 * A gateway saying "this transaction succeeded" is not enough. The
 * response has to line up with the payment we recorded when the
 * checkout was started: the transaction reference, the amount, the
 * currency. Without those checks a cheap successful transaction (or a
 * transaction that already settled another payment) could be replayed
 * to activate an unpaid subscription.
 */
class PaymentCallbackValidator
{
    /**
     * Cents of tolerance when comparing the gateway amount with the
     * recorded one — gateways round, and a one-cent difference must not
     * block a legitimate payment.
     */
    private const AMOUNT_TOLERANCE = 0.01;

    /**
     * Validate a gateway callback against the local payment record.
     *
     * @throws PaymentMismatchException
     */
    public function validate(SubscriptionPayment $payment, array $verifyResult): void
    {
        if (! ($verifyResult['success'] ?? false)) {
            throw new PaymentMismatchException(
                $verifyResult['message'] ?? 'عملية الدفع لم تنجح حسب بوابة الدفع.'
            );
        }

        $gatewayPaymentId = trim((string) ($verifyResult['payment_id'] ?? ''));

        if ($gatewayPaymentId === '') {
            throw new PaymentMismatchException('لم تُرجع بوابة الدفع أي مرجع للعملية، فلا يمكن مطابقتها مع الدفعة المسجلة.');
        }

        $this->assertReferenceMatches($payment, $gatewayPaymentId);
        $this->assertAmountMatches($payment, $verifyResult);
        $this->assertCurrencyMatches($payment, $verifyResult);
        $this->assertNotAlreadySettledElsewhere($payment, $gatewayPaymentId);
    }

    /**
     * The transaction reference recorded when the checkout started has
     * to be the one the gateway is reporting.
     *
     * Moyasar creates the transaction in the browser, so its reference
     * is unknown before the callback arrives. In that case there is
     * nothing to compare against, and the amount/currency checks carry
     * the verification instead.
     */
    private function assertReferenceMatches(SubscriptionPayment $payment, string $gatewayPaymentId): void
    {
        $recorded = trim((string) ($payment->gateway_payment_id ?? ''));

        if ($recorded === '') {
            return;
        }

        if (! hash_equals($recorded, $gatewayPaymentId)) {
            throw new PaymentMismatchException(
                'مرجع العملية لدى البوابة ('.$gatewayPaymentId.') لا يطابق المرجع المسجل لهذه الدفعة.'
            );
        }
    }

    /**
     * The amount the gateway collected has to be the amount we asked for.
     */
    private function assertAmountMatches(SubscriptionPayment $payment, array $verifyResult): void
    {
        if (! array_key_exists('amount', $verifyResult) || $verifyResult['amount'] === null || $verifyResult['amount'] === '') {
            throw new PaymentMismatchException('لم تُرجع بوابة الدفع أي مبلغ، فلا يمكن مطابقته مع الدفعة المسجلة.');
        }

        $gatewayAmount = (float) $verifyResult['amount'];
        $expectedAmount = (float) $payment->amount;

        if (abs($gatewayAmount - $expectedAmount) > self::AMOUNT_TOLERANCE) {
            throw new PaymentMismatchException(
                'مبلغ العملية لدى البوابة ('.number_format($gatewayAmount, 2, '.', '').') لا يطابق المبلغ المطلوب ('
                .number_format($expectedAmount, 2, '.', '').').'
            );
        }
    }

    /**
     * The currency has to be the one we asked for.
     */
    private function assertCurrencyMatches(SubscriptionPayment $payment, array $verifyResult): void
    {
        $gatewayCurrency = strtoupper(trim((string) ($verifyResult['currency'] ?? '')));
        $expectedCurrency = strtoupper(trim((string) ($payment->currency ?: PaymentSettings::getPlatformCurrency())));

        if ($gatewayCurrency === '') {
            throw new PaymentMismatchException('لم تُرجع بوابة الدفع أي عملة، فلا يمكن مطابقتها مع الدفعة المسجلة.');
        }

        if ($gatewayCurrency !== $expectedCurrency) {
            throw new PaymentMismatchException(
                'عملة العملية لدى البوابة ('.$gatewayCurrency.') لا تطابق العملة المطلوبة ('.$expectedCurrency.').'
            );
        }
    }

    /**
     * One gateway transaction may settle exactly one payment.
     *
     * The lookup deliberately ignores the tenant scope: the point is
     * that the same transaction cannot settle a payment belonging to
     * another tenant either.
     */
    private function assertNotAlreadySettledElsewhere(SubscriptionPayment $payment, string $gatewayPaymentId): void
    {
        $alreadySettled = SubscriptionPayment::withoutGlobalScopes()
            ->where('gateway', $payment->gateway)
            ->where('gateway_payment_id', $gatewayPaymentId)
            ->where('status', 'paid')
            ->where('id', '!=', $payment->id)
            ->exists();

        if ($alreadySettled) {
            throw new PaymentMismatchException(
                'هذه العملية مرتبطة بدفعة أخرى مكتملة، ولا يمكن استخدامها لتسوية دفعة مختلفة.'
            );
        }
    }
}
