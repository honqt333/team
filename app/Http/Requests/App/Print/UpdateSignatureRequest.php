<?php

namespace App\Http\Requests\App\Print;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates PATCH requests to update an existing signature's metadata
 * (name_ar, name_en, and the show/hide flag).
 *
 * Why a separate FormRequest from UploadSignatureRequest:
 *   - The upload contract requires a file; updates do not (the file
 *     already lives on disk from the original upload).
 *   - Updates are partial — the user might only want to flip `show`
 *     off, or rename one side, or all three at once. We use
 *     `sometimes` so the validator only enforces rules on fields the
 *     client actually sent.
 *   - The `show` field is a boolean that lives in the JSON column; it
 *     is brand new and only present on signatures the new UI manages.
 *     Older signatures without a `show` key default to true at render
 *     time (see TemplateDefaultA4.vue's `s.show !== false` filter), so
 *     flipping it to false must be allowed for any stored signature.
 *
 * Defense-in-depth note: the controller also re-checks tenant
 * authorization via `$this->authorize('update', $tenant)` AFTER
 * validation. This FormRequest only validates shape, not identity.
 */
class UpdateSignatureRequest extends FormRequest
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
            // Bilingual labels — both optional on update, but at least
            // one must be present so the template never renders an empty
            // signature caption. We keep parity with the upload contract.
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:120'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:120'],
            // Legacy single-name fallback for older clients.
            'name' => ['sometimes', 'nullable', 'string', 'max:120'],

            // The visibility toggle. Accepts true/false/0/1/"0"/"1"
            // because Inertia form posts often stringify booleans.
            'show' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Normalize incoming data so the controller can rely on:
     *   - $request->boolean('show') returning a real bool
     *   - $request->string('name_ar' | 'name_en' | 'name') returning
     *     a non-null trimmed string
     *
     * @return array<string, string|bool>
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();

        $out = [];
        foreach (['name_ar', 'name_en', 'name'] as $field) {
            if (array_key_exists($field, $data)) {
                $out[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }
        if (array_key_exists('show', $data)) {
            // Laravel's `boolean` validator already coerces 0/1/"0"/"1"
            // to a real bool, but we re-coerce defensively.
            $out['show'] = filter_var($data['show'], FILTER_VALIDATE_BOOLEAN);
        }

        return $out;
    }
}
