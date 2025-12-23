<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\QuoteConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $this->authorize('approve', $quote);

        if (!$quote->canBeApproved()) {
            abort(403, 'This quote cannot be approved.');
        }

        // Cannot approve a quote without services
        if ($quote->lines()->count() === 0) {
            abort(400, 'Cannot approve a quote without services.');
        }

        // First, mark as approved
        $quote->update([
            'status' => Quote::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Then convert to work order
        try {
            $this->conversionService->convert($quote, auth()->user());
        } catch (\Exception $e) {
            // Revert approval if conversion fails
            $quote->update([
                'status' => Quote::STATUS_DRAFT,
                'approved_at' => null,
                'approved_by' => null,
            ]);
            
            abort(500, 'Failed to convert quote to work order.');
        }

        return redirect()->back();
    }

    /**
     * Reject a quote.
     */
    public function reject(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('reject', $quote);

        if (!$quote->canBeRejected()) {
            abort(403, 'This quote cannot be rejected.');
        }

        // Cannot reject a quote without services
        if ($quote->lines()->count() === 0) {
            abort(400, 'Cannot reject a quote without services.');
        }

        $quote->update([
            'status' => Quote::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);

        return redirect()->back();
    }
}
