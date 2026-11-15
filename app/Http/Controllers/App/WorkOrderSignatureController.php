<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Exceptions\InvalidQuoteSignatureException;
use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Services\QuoteSignatureStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkOrderSignatureController extends Controller
{
    public function __construct(
        private readonly QuoteSignatureStorage $signatureStorage
    ) {}

    /**
     * Store a digital signature for a work order.
     */
    public function store(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:reception,delivery',
            'signature' => 'required|string',
        ]);

        $type = $validated['type'];
        $this->authorize('sign', [$workOrder, $type]);

        try {
            $path = $this->signatureStorage->storeWorkOrderSignature(
                $validated['signature'],
                $workOrder,
                $type
            );
        } catch (InvalidQuoteSignatureException $e) {
            return back()
                ->withErrors(['signature' => __('quotes.signature_invalid')])
                ->with('error', __('quotes.signature_invalid'));
        }

        $field = "{$type}_signature";
        $dateField = "{$type}_signed_at";

        if ($workOrder->$field) {
            Storage::disk('public')->delete($workOrder->$field);
        }

        $workOrder->update([
            $field => $path,
            $dateField => now(),
        ]);

        $workOrder->logActivity("signed_{$type}", __("work_orders.activities.actions.signed_{$type}"));

        return back()->with('success', __('common.saved_success'));
    }
}
