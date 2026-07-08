<?php

namespace App\Http\Controllers\App\Quotes;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuotePart;
use App\Models\Service;
use App\Support\PricingHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteServiceController extends Controller
{
    use AuthorizesRequests;

    /**
     * Add a service line to the quote.
     */
    public function addService(Request $request, Quote $quote): RedirectResponse
    {
        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot add services to a converted quote.');
        }

        if ($request->input('department_id') === 'packages') {
            $request->merge(['department_id' => null]);
        }

        $validated = $request->validate([
            'service_id' => ['nullable', 'exists:services,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'description' => ['required_without:service_id', 'nullable', 'string', 'max:500'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'pending_parts' => ['nullable', 'array'],
            'pending_parts.*.source' => ['required_with:pending_parts', 'in:warehouse,external,customer'],
            'pending_parts.*.name' => ['required_with:pending_parts', 'string', 'max:255'],
            'pending_parts.*.qty' => ['required_with:pending_parts', 'numeric', 'min:0.01'],
            'pending_parts.*.unit_price' => ['required_with:pending_parts', 'numeric', 'min:0'],
            'pending_parts.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $service = $validated['service_id'] ? Service::find($validated['service_id']) : null;

        $unitPrice = (float) $validated['unit_price'];
        $discountType = $validated['discount_type'] ?? 'none';
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        
        if ($service && !$service->allow_price_override) {
            $unitPrice = (float) $service->base_price;
        }
        
        $minPrice = $service ? (float) ($service->min_price ?? 0) : 0;
        if ($minPrice > 0) {
            $discountAmount = PricingHelper::computeDiscountAmount($unitPrice, $discountType, $discountValue);
            $finalPrice = max(0, $unitPrice - $discountAmount);
            
            if ($finalPrice < $minPrice) {
                return redirect()->back()->withErrors([
                    'unit_price' => __('pricing.final_price_below_minimum', [
                        'final' => number_format($finalPrice, 2),
                        'min' => number_format($minPrice, 2),
                    ])
                ]);
            }
        }

        $line = QuoteLine::create([
            'quote_id' => $quote->id,
            'service_id' => $validated['service_id'],
            'department_id' => $validated['department_id'],
            'description' => $validated['description'] ?? ($service ? $service->name : 'أخرى'),
            'qty' => $validated['qty'],
            'unit_price' => $unitPrice,
            'base_price_snapshot' => $service?->base_price ?? $unitPrice,
            'min_price_snapshot' => $service ? ($service->min_price ?? 0) : 0,
            'discount_type' => $validated['discount_type'] ?? 'none',
            'discount_value' => $validated['discount_value'] ?? 0,
        ]);

        if (!empty($request->pending_parts)) {
            foreach ($request->pending_parts as $partData) {
                QuotePart::create([
                    'quote_id' => $quote->id,
                    'quote_line_id' => $line->id,
                    'part_id' => $partData['part_id'] ?? null,
                    'source' => $partData['source'],
                    'name' => $partData['name'],
                    'part_number' => $partData['part_number'] ?? null,
                    'unit_id' => $partData['unit_id'] ?? null,
                    'description' => $partData['description'] ?? null,
                    'qty' => $partData['qty'],
                    'unit_price' => $partData['unit_price'],
                    'discount' => $partData['discount'] ?? 0,
                    'include_in_package' => $partData['include_in_package'] ?? true,
                    'hide_on_print' => $partData['hide_on_print'] ?? false,
                ]);
            }
        }

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Update a service line in the quote.
     */
    public function updateService(Request $request, Quote $quote, QuoteLine $line): RedirectResponse
    {
        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot update services in a converted quote.');
        }

        $this->authorize('update', $line);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $service = $line->service;
        $unitPrice = (float) $validated['unit_price'];
        $discountType = $validated['discount_type'] ?? 'none';
        $discountValue = (float) ($validated['discount_value'] ?? 0);
        
        if ($service) {
            if (!$service->allow_price_override) {
                $unitPrice = (float) $service->base_price;
            }
            
            $minPrice = (float) ($service->min_price ?? 0);
            if ($minPrice > 0) {
                $discountAmount = PricingHelper::computeDiscountAmount($unitPrice, $discountType, $discountValue);
                $finalPrice = max(0, $unitPrice - $discountAmount);
                
                if ($finalPrice < $minPrice) {
                    return redirect()->back()->withErrors([
                        'unit_price' => __('pricing.final_price_below_minimum', [
                            'final' => number_format($finalPrice, 2),
                            'min' => number_format($minPrice, 2),
                        ])
                    ]);
                }
            }
        }

        $line->update([
            'description' => $validated['description'] ?? $line->description,
            'qty' => $validated['qty'],
            'unit_price' => $unitPrice,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
        ]);

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Delete a service line from the quote.
     */
    public function deleteService(Quote $quote, QuoteLine $line): RedirectResponse
    {
        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot delete services from a converted quote.');
        }

        $this->authorize('delete', $line);

        $line->delete();

        $quote->refresh();
        $quote->recalculateTotals();
        $quote->save();

        return redirect()->back();
    }

    /**
     * Add a department to the quote.
     */
    public function addDepartment(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot modify departments of a converted quote.');
        }

        $validated = $request->validate([
            'department_id' => ['required'],
        ]);

        if ($validated['department_id'] === 'packages') {
            $quote->update(['show_packages_section' => true]);
            return redirect()->back();
        }

        $request->validate([
            'department_id' => ['exists:departments,id'],
        ]);

        $quote->departments()->syncWithoutDetaching([$validated['department_id']]);

        return redirect()->back();
    }

    /**
     * Remove a department from the quote.
     */
    public function removeDepartment(Quote $quote, string $department_id): RedirectResponse
    {
        $this->authorize('update', $quote);

        if (!$quote->canBeEdited()) {
            abort(403, 'Cannot modify departments of a converted quote.');
        }

        if ($department_id === 'packages') {
            $hasPackages = $quote->lines()
                ->whereHas('service', fn($q) => $q->where('type', Service::TYPE_PACKAGE))
                ->exists();

            if ($hasPackages) {
                return redirect()->back()->withErrors([
                    'error' => __('quotes.cannot_remove_package_department_with_items')
                ]);
            }

            $quote->update(['show_packages_section' => false]);

            return redirect()->back();
        }

        if (!is_numeric($department_id)) {
            abort(404);
        }
        $department = (int) $department_id;

        $hasServices = $quote->lines()
            ->whereHas('service', fn($q) => $q->where('department_id', $department))
            ->exists();

        if ($hasServices) {
            abort(403, 'Cannot remove department that has services.');
        }

        $quote->departments()->detach($department);

        return redirect()->back();
    }
}
