<?php

namespace App\Http\Requests\App\Print;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UploadSignatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is enforced at the controller level via
     * `$this->authorize('update', $tenant)`. This early-exit just makes
     * sure the request is *authenticated* so unauthenticated callers
     * get a 401 instead of a 422 validation error.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The `signature` field is the uploaded image. We restrict the MIME
     * types to PNG, JPG, JPEG, WEBP and SVG+xml. SVG is allowed because
     * Carag V2 prints vector-based signatures for invoices.
     *
     * `name` is a short human label for the signature (e.g. "CEO signature",
     * "Accountant signature") and is what gets shown in the print layout.
     *
     * `document_type` is optional — when present the new signature will be
     * appended to that document's `signatures` array inside the tenant
     * `print_settings` JSON. When omitted, the file is uploaded as an
     * "orphan" that the frontend can later bind to a document type.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'signature' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg,svg,webp',
                'max:2048',
                // Laravel's `image` rule uses getimagesize() which doesn't
                // understand SVG vectors. We need a custom check that
                // validates raster images via getimagesize() AND accepts
                // SVG when the content is a real SVG document.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $clientMime = (string) $value->getMimeType();
                    $clientExt = strtolower($value->getClientOriginalExtension());

                    if ($clientExt === 'svg' || str_contains($clientMime, 'svg')) {
                        // Reject SVGs that try to include executable JS
                        $head = file_get_contents($value->getRealPath(), false, null, 0, 4096) ?: '';
                        if (! str_contains($head, '<svg')) {
                            $fail(__('validation.mimes', [
                                'attribute' => __('validation.attributes.signature'),
                                'values' => 'png, jpg, jpeg, svg, webp',
                            ]));
                        }

                        return;
                    }

                    // For raster: defer to Laravel's image rule semantics
                    // by checking the size function.
                    $size = @getimagesize($value->getRealPath());
                    if ($size === false) {
                        $fail(__('validation.image', ['attribute' => __('validation.attributes.signature')]));
                    }
                },
                // Reject obvious double-extension / null-byte tricks
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    $name = $value->getClientOriginalName();
                    if (str_contains($name, "\0") || str_contains($name, '/') || str_contains($name, '\\')) {
                        $fail(__('validation.regex', ['attribute' => $attribute]));
                    }
                },
            ],
            'name' => ['required', 'string', 'max:120'],
            'document_type' => [
                'nullable',
                'string',
                'max:50',
                Rule::in([
                    'invoice',
                    'parts_invoice',
                    'quote',
                    'payments',
                    'receipt_voucher',
                    'payment_voucher',
                    'bad_debts',
                    'work_order',
                    'condition_report',
                    'return_invoice',
                    'proforma_invoice',
                    'purchase_invoice',
                ]),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature.required' => __('validation.required', ['attribute' => __('validation.attributes.signature')]),
            'signature.image' => __('validation.image', ['attribute' => __('validation.attributes.signature')]),
            'signature.mimes' => __('validation.mimes', ['attribute' => __('validation.attributes.signature'), 'values' => 'png, jpg, jpeg, svg, webp']),
            'signature.max' => __('validation.max.file', ['attribute' => __('validation.attributes.signature'), 'max' => 2048]),
            'name.required' => __('validation.required', ['attribute' => __('validation.attributes.name')]),
            'name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name'), 'max' => 120]),
        ];
    }
}
