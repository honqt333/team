<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\NotificationService;
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

        // Cannot approve a quote without services or parts
        if ($quote->lines()->count() === 0 && $quote->parts()->count() === 0) {
            abort(400, 'Cannot approve an empty quote.');
        }

        // First, mark as approved
        $quote->update([
            'status' => Quote::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Then convert to work order
        try {
            $workOrder = $this->conversionService->convert($quote, auth()->user());
        } catch (\Throwable $e) {
            // Revert approval if conversion fails
            $quote->update([
                'status' => Quote::STATUS_DRAFT,
                'approved_at' => null,
                'approved_by' => null,
            ]);
            
            abort(500, 'Failed to convert quote to work order: ' . $e->getMessage());
        }

        // Notify quote creator about approval
        if ($quote->created_by && $quote->created_by !== auth()->id()) {
            NotificationService::notify(
                tenantId: $quote->tenant_id,
                userId: $quote->created_by,
                type: 'quote.approved',
                title: 'تمت الموافقة على عرض السعر #' . $quote->code,
                body: 'تمت الموافقة على عرض السعر وتحويله إلى أمر عمل',
                actionUrl: '/app/quotes/' . $quote->id,
                actorId: auth()->id(),
                icon: 'check',
            );
        }

        return redirect()->route('work-orders.show', $workOrder->id);
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

        // Cannot reject a quote without services or parts
        if ($quote->lines()->count() === 0 && $quote->parts()->count() === 0) {
            abort(400, 'Cannot reject an empty quote.');
        }

        $quote->update([
            'status' => Quote::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);

        // Notify quote creator about rejection
        if ($quote->created_by && $quote->created_by !== auth()->id()) {
            NotificationService::notify(
                tenantId: $quote->tenant_id,
                userId: $quote->created_by,
                type: 'quote.rejected',
                title: 'تم رفض عرض السعر #' . $quote->code,
                body: 'تم رفض عرض السعر',
                actionUrl: '/app/quotes/' . $quote->id,
                actorId: auth()->id(),
                icon: 'x',
            );
        }

        return redirect()->back();
    }
}
