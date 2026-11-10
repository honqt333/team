<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Exceptions\InvalidQuoteSignatureException;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\NotificationService;
use App\Services\QuoteConversionService;
use App\Services\QuoteSignatureStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteApprovalController extends Controller
{
    public function __construct(
        private QuoteConversionService $conversionService
    ) {}

    /**
     * Approve a quote and convert it to a work order.
     */
    public function approve(Request $request, Quote $quote): RedirectResponse
    {
        return DB::transaction(function () use ($request, $quote) {

            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            $this->authorize('approve', $quote);

            if (! $quote->canBeApproved()) {
                abort(403, __('quotes.public_cannot_approve'));
            }

            if ($quote->isExpired()) {
                return back()->with('error', __('quotes.public_expired'));
            }

            // Cannot approve a quote without services or parts
            if ($quote->lines()->count() === 0 && $quote->parts()->count() === 0) {
                abort(400, __('quotes.empty_decision'));
            }

            // Handle optional customer signature on approval
            $signaturePath = null;

            if ($request->filled('signature')) {
                // The signature is written to the public disk, so it has to
                // be an inert image: the type is taken from the bytes and
                // active formats (SVG) are refused.
                try {
                    $signaturePath = app(QuoteSignatureStorage::class)
                        ->store((string) $request->input('signature'), $quote);
                } catch (InvalidQuoteSignatureException $e) {
                    return back()
                        ->withInput()
                        ->withErrors(['signature' => __('quotes.signature_invalid')]);
                }
            }

            // First, mark as approved
            $quote->update([
                'status' => Quote::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'signature_path' => $signaturePath ?? $quote->signature_path,
            ]);

            // Then convert to work order
            $workOrder = $this->conversionService->convert($quote, auth()->user());

            $quote->logActivity('approve', ['channel' => 'staff']);

            // Notify quote creator about approval
            if ($quote->created_by && $quote->created_by !== auth()->id()) {
                NotificationService::notify(
                    tenantId: $quote->tenant_id,
                    userId: $quote->created_by,
                    type: 'quote.approved',
                    title: __('quotes.approved_title', ['code' => $quote->code]),
                    body: __('quotes.approved_converted_body'),
                    actionUrl: '/app/quotes/'.$quote->id,
                    actorId: auth()->id(),
                    icon: 'check',
                );
            }

            return redirect()->route('work-orders.show', $workOrder->id);
        });
    }

    /**
     * Reject a quote.
     */
    public function reject(Request $request, Quote $quote): RedirectResponse
    {
        return DB::transaction(function () use ($quote) {

            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            $this->authorize('reject', $quote);

            if (! $quote->canBeRejected()) {
                abort(403, __('quotes.public_cannot_reject'));
            }

            // Cannot reject a quote without services or parts
            if ($quote->lines()->count() === 0 && $quote->parts()->count() === 0) {
                abort(400, __('quotes.empty_decision'));
            }

            if ($quote->isExpired()) {
                return back()->with('error', __('quotes.public_expired'));
            }

            $quote->update([
                'status' => Quote::STATUS_REJECTED,
                'rejected_at' => now(),
            ]);

            $quote->logActivity('reject', ['channel' => 'staff']);

            // Notify quote creator about rejection
            if ($quote->created_by && $quote->created_by !== auth()->id()) {
                NotificationService::notify(
                    tenantId: $quote->tenant_id,
                    userId: $quote->created_by,
                    type: 'quote.rejected',
                    title: __('quotes.rejected_title', ['code' => $quote->code]),
                    body: __('quotes.rejected_body'),
                    actionUrl: '/app/quotes/'.$quote->id,
                    actorId: auth()->id(),
                    icon: 'x',
                );
            }

            return redirect()->back();
        });
    }
}
