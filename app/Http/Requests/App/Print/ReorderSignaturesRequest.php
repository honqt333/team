<?php

namespace App\Http\Requests\App\Print;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates POST /app/settings/print/signatures/reorder payloads.
 *
 * Wire format:
 *   {
 *     "order": ["uuid-a", "uuid-b", "uuid-c", ...],
 *     "document_type": "invoice"   // optional but recommended so the
 *                                 // reorder only affects one document
 *   }
 *
 * Rules:
 *   - `order` must be a non-empty array.
 *   - Each entry must be a string in UUID v4 format.
 *   - There must be no duplicate IDs in the array (a signature has a
 *     fixed position; placing it twice makes the operation ambiguous).
 *
 * The controller is responsible for verifying that:
 *   - every id in `order` actually belongs to this tenant's print_settings
 *   - the array length matches the existing signatures count (we never
 *     silently drop a signature during reorder — that would be data loss)
 *   - the array is a permutation of the existing ids (no extras, no misses)
 */
class ReorderSignaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['string', 'uuid', 'distinct'],
            'document_type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
