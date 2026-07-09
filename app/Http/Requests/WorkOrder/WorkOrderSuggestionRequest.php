<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for the AI service/part suggester endpoint.
 *
 * Schema source: docs/features/ai-service-suggester/design.md §3.
 */
final class WorkOrderSuggestionRequest extends FormRequest
{
    /**
     * Authorization is performed by the controller via
     * `$this->authorize('update', $workOrder)` — keep this permissive
     * here so the FormRequest does not short-circuit before route
     * model binding.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'complaint' => ['required', 'string', 'min:5', 'max:2000'],

            'vehicle' => ['nullable', 'array'],
            'vehicle.make' => ['nullable', 'string', 'max:80'],
            'vehicle.model' => ['nullable', 'string', 'max:80'],
            'vehicle.year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'vehicle.plate_number' => ['nullable', 'string', 'max:30'],
            'vehicle.odometer' => ['nullable', 'integer', 'min:0', 'max:9999999'],

            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }
}
