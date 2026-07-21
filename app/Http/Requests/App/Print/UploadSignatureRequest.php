<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Print;

use Closure;
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
                function (string $attribute, mixed $value, Closure $fail): void {
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

                        // SVG XSS hardening. Reject any markup that
                        // could execute script or fetch external content
                        // when the SVG is rendered in a browser context.
                        // Allowed content for a signature is paths,
                        // curves, and the document wrapper only.
                        $xssPatterns = [
                            '<script', '</script',
                            '<foreignobject', '</foreignobject',
                            '<iframe', '<embed', '<object',
                            '<handler', '<listener',
                            '<?xml-stylesheet', '<!--#exec',
                            'xlink:href',
                            'javascript:', 'vbscript:',
                            'data:text/html', 'data:application/xhtml',
                            'data:image',
                        ];
                        $lower = strtolower($head);

                        foreach ($xssPatterns as $needle) {
                            if (str_contains($lower, $needle)) {
                                $fail(__('validation.regex', [
                                    'attribute' => __('validation.attributes.signature'),
                                ]));

                                return;
                            }
                        }

                        // Event handler attributes (onload=, onerror=,
                        // onclick=, onbegin=, onmouseover=, ...).
                        if (preg_match('/\son[a-z]+\s*=/i', $head) === 1) {
                            $fail(__('validation.regex', [
                                'attribute' => __('validation.attributes.signature'),
                            ]));

                            return;
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
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    $name = $value->getClientOriginalName();

                    if (str_contains($name, "\0") || str_contains($name, '/') || str_contains($name, '\\')) {
                        $fail(__('validation.regex', ['attribute' => $attribute]));
                    }
                },
                // SVG XSS hardening — block active content inside uploaded SVGs.
                // We accept SVG (vector signatures) but must reject any markup
                // that could execute script in a browser context. The list of
                // patterns is intentionally narrow: signatures are simple
                // stroke/curve/path data. Anything more exotic is rejected.
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }
                    $clientMime = (string) $value->getMimeType();
                    $clientExt = strtolower($value->getClientOriginalExtension());

                    if ($clientExt !== 'svg' && ! str_contains($clientMime, 'svg')) {
                        // Raster image — XSS check is not applicable.
                        return;
                    }

                    // Read the whole file (cap at 64KB; signatures are small).
                    // Reading 64KB instead of 4KB so that obfuscated payloads
                    // split across many elements are still caught.
                    $raw = file_get_contents($value->getRealPath(), false, null, 0, 65536) ?: '';
                    $lower = strtolower($raw);

                    // Patterns that must never appear in a signature SVG.
                    // Match is case-insensitive and whole-tag tolerant.
                    $forbidden = [
                        '<script' => 'script element',
                        '</script' => 'script closing tag',
                        '<foreignobject' => 'foreignObject element',
                        '</foreignobject' => 'foreignObject closing tag',
                        '<iframe' => 'iframe element',
                        '<embed' => 'embed element',
                        '<object' => 'object element',
                        '<handler' => 'SVG event handler element',
                        '<listener' => 'SVG event listener element',
                        '<?xml-stylesheet' => 'XSLT stylesheet directive',
                        '<!--#exec' => 'SSI exec directive',
                        'xlink:href' => 'xlink:href attribute (XSS vector)',
                        'javascript:' => 'javascript: URI scheme',
                        'vbscript:' => 'vbscript: URI scheme',
                        'data:text/html' => 'data: text/html URI (XSS vector)',
                        'data:application/xhtml' => 'data: XHTML URI (XSS vector)',
                    ];

                    // Pre-translate once so the closure doesn't re-lookup on
                    // every fail() call. Note: the `validation.regex`
                    // message format uses `:attribute` (Laravel placeholder),
                    // which is automatically replaced by the validator when
                    // the failure is added. We pass the attribute key in the
                    // translator array so the final message is rendered
                    // correctly (e.g. "The signature format is invalid.").
                    $message = __('validation.regex', ['attribute' => $attribute]);

                    foreach ($forbidden as $needle => $description) {
                        if (str_contains($lower, strtolower($needle))) {
                            $fail($message);

                            return;
                        }
                    }

                    // Event handler attributes: on*= (onload, onerror, onclick, onbegin, ...).
                    // The attribute form is `<tag ... on<word>=` — we look for ` on` followed
                    // by a letter and `=` so we don't match words like "only" or "honor".
                    if (preg_match('/\son[a-z]+\s*=/i', $raw) === 1) {
                        $fail($message);

                        return;
                    }

                    // External entity / DTD — prevent XXE-style attacks even though
                    // the PHP DOMDocument we'd parse with (if we did) would block by
                    // default. Defense in depth.
                    if (preg_match('/<!ENTITY\s+/i', $raw) === 1
                        || preg_match('/<!DOCTYPE[^>]+SYSTEM/i', $raw) === 1) {
                        $fail($message);

                        return;
                    }
                },
            ],
            // Bilingual labels. The print templates render these directly
            // (sig.name_ar / sig.name_en) so both are required for every
            // signature the tenant stores. We keep the legacy `name` field
            // as an optional fallback for older API clients (and for the
            // curl-based smoke tests) — when present, the controller
            // copies it into the AR slot so the template still has data.
            'name_ar' => ['required_without:name', 'nullable', 'string', 'max:120'],
            'name_en' => ['required_without:name', 'nullable', 'string', 'max:120'],
            'name' => ['required_without_all:name_ar,name_en', 'nullable', 'string', 'max:120'],
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
            'name_ar.required_without' => __('validation.required', ['attribute' => __('validation.attributes.name_ar')]),
            'name_ar.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name_ar'), 'max' => 120]),
            'name_en.required_without' => __('validation.required', ['attribute' => __('validation.attributes.name_en')]),
            'name_en.max' => __('validation.max.string', ['attribute' => __('validation.attributes.name_en'), 'max' => 120]),
        ];
    }
}
