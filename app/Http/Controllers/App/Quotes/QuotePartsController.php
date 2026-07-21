<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Quotes;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Models\Quote;
use App\Models\QuotePart;
use App\Models\Warehouse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuotePartsController extends Controller
{
    use AuthorizesRequests;

    /**
     * Add a part to the quote.
     */
    public function addPart(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (! $quote->canBeEdited()) {
            abort(403, 'Cannot add parts to a converted or approved quote.');
        }

        $validated = $request->validate([
            'source' => ['required', 'in:warehouse,external,customer'],
            'name' => ['required', 'string', 'max:255'],
            'part_id' => ['nullable', 'exists:parts,id'],
            'quote_line_id' => ['nullable', 'exists:quote_lines,id'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'exists:inventory_units,id'],
            'description' => ['nullable', 'string'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'include_in_package' => ['boolean'],
            'hide_on_print' => ['boolean'],
        ]);

        if ($validated['source'] === 'warehouse' && ! empty($validated['part_id']) && ! ($validated['include_in_package'] ?? false)) {
            $partId = (int) $validated['part_id'];
            $qty = (float) ($validated['qty'] ?: 1);
            $unitDiscount = $qty > 0 ? ((float) ($validated['discount'] ?? 0) / $qty) : 0;
            $finalPrice = (float) $validated['unit_price'] - $unitDiscount;

            $user = auth()->user();
            $warehouse = Warehouse::forCenter($user->current_center_id)->default()->first();

            if ($warehouse) {
                $balance = InventoryBalance::where('part_id', $partId)
                    ->where('warehouse_id', $warehouse->id)
                    ->first();

                if ($balance && $balance->min_sale_price > 0 && $finalPrice < (float) $balance->min_sale_price) {
                    return redirect()->back()->withErrors([
                        'unit_price' => __('pricing.final_price_below_minimum', [
                            'final' => number_format($finalPrice, 2),
                            'min' => number_format((float) $balance->min_sale_price, 2),
                        ]),
                    ]);
                }
            }
        }

        $quote->parts()->create($validated);

        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Update a part in the quote.
     */
    public function updatePart(Request $request, Quote $quote, QuotePart $quotePart): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (! $quote->canBeEdited()) {
            abort(403, 'Cannot update parts of a converted or approved quote.');
        }

        if ($quotePart->quote_id !== $quote->id) {
            abort(404);
        }

        $validated = $request->validate([
            'source' => ['sometimes', 'in:warehouse,external,customer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'part_id' => ['nullable', 'exists:parts,id'],
            'quote_line_id' => ['nullable', 'exists:quote_lines,id'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'exists:inventory_units,id'],
            'description' => ['nullable', 'string'],
            'qty' => ['sometimes', 'numeric', 'min:0.01'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'include_in_package' => ['boolean'],
            'hide_on_print' => ['boolean'],
        ]);

        $source = $validated['source'] ?? $quotePart->source;
        $partId = $validated['part_id'] ?? $quotePart->part_id;
        $includeInPackage = $validated['include_in_package'] ?? $quotePart->include_in_package;

        if ($source === 'warehouse' && ! empty($partId) && ! $includeInPackage) {
            $qty = (float) ($validated['qty'] ?? $quotePart->qty);
            $unitDiscount = $qty > 0 ? ((float) ($validated['discount'] ?? $quotePart->discount) / $qty) : 0;
            $unitPrice = (float) ($validated['unit_price'] ?? $quotePart->unit_price);
            $finalPrice = $unitPrice - $unitDiscount;

            $user = auth()->user();
            $warehouse = Warehouse::forCenter($user->current_center_id)->default()->first();

            if ($warehouse) {
                $balance = InventoryBalance::where('part_id', $partId)
                    ->where('warehouse_id', $warehouse->id)
                    ->first();

                if ($balance && $balance->min_sale_price > 0 && $finalPrice < (float) $balance->min_sale_price) {
                    return redirect()->back()->withErrors([
                        'unit_price' => __('pricing.final_price_below_minimum', [
                            'final' => number_format($finalPrice, 2),
                            'min' => number_format((float) $balance->min_sale_price, 2),
                        ]),
                    ]);
                }
            }
        }

        $quotePart->update($validated);

        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Delete a part from the quote.
     */
    public function deletePart(Quote $quote, QuotePart $quotePart): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (! $quote->canBeEdited()) {
            abort(403, 'Cannot delete parts from a converted or approved quote.');
        }

        if ($quotePart->quote_id !== $quote->id) {
            abort(404);
        }

        $quotePart->delete();

        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }
}
