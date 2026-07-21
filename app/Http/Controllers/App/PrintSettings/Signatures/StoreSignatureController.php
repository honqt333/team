<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\PrintSettings\Signatures;

use App\Http\Controllers\App\PrintSettings\Concerns\HandlesSignatureStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Print\UploadSignatureRequest;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /app/settings/print/signatures
 *
 * Uploads a new signature image and registers it in the tenant's
 * `print_settings.documents.{document_type}.signatures` array. See
 * HandlesSignatureStorage for the storage layout, MIME whitelist,
 * and JSON column conventions.
 *
 * Why a single-action controller:
 *   - One endpoint = one method = one class. The previous 641-line
 *     controller held 4 endpoints + 4 private helpers and was
 *     painful to navigate or unit-test in isolation.
 *   - Single-action controllers compose well — `__invoke()` is the
 *     only public surface, so route caching, testing, and future
 *     middleware additions stay obvious.
 */
class StoreSignatureController extends Controller
{
    use HandlesSignatureStorage;

    public function __invoke(UploadSignatureRequest $request): Response
    {
        $tenant = $request->user()->tenant;

        // If the user->tenant relationship is not eager-loaded and for
        // some reason returns null, fall back to a direct lookup by id.
        if (! $tenant) {
            $tenant = Tenant::find($request->user()->tenant_id);
        }

        // Defense-in-depth at the controller — the FormRequest already
        // enforces permission but we re-check here so a permission
        // change between request validation and storage still refuses
        // the upload.
        $this->authorize('update', $tenant);

        $file = $request->file('signature');

        // Re-derive the extension from the actual MIME type detected by
        // PHP's fileinfo (NOT from the client-supplied filename). This
        // prevents extension-spoofing attacks (e.g. "evil.php.png").
        $detectedExtension = $this->resolveExtensionFromMime($file);

        if ($detectedExtension === null) {
            // Should be impossible — the FormRequest already enforced
            // the MIME whitelist — but fail closed if it happens.
            abort(422, __('validation.mimes', [
                'attribute' => __('validation.attributes.signature'),
                'values' => 'png, jpg, jpeg, svg, webp',
            ]));
        }

        $ids = $this->signaturePath($tenant->id, $detectedExtension);
        $signatureId = $ids['id'];
        $path = $ids['path'];

        // Stream the file from a tmp path without copying it through
        // PHP memory first — safer for the 2MB cap on a shared PHP-FPM.
        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        $uploadedAt = now();

        // Bilingual labels: the print template renders sig.name_ar and
        // sig.name_en directly. We require both in the FormRequest, but
        // fall back to the legacy single `name` field for older clients
        // and curl-based smoke tests so the controller never returns
        // an empty name pair.
        $nameAr = $request->string('name_ar')->toString()
            ?: $request->string('name')->toString();
        $nameEn = $request->string('name_en')->toString()
            ?: $request->string('name')->toString();

        $signaturePayload = [
            'id' => $signatureId,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            // Legacy single-name field kept for clients that still
            // read it (no longer the canonical source of truth).
            'name' => $nameEn ?: $nameAr,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'uploaded_at' => $uploadedAt->toIso8601String(),
            'uploaded_by' => $request->user()->id,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        $documentType = $request->input('document_type');

        if ($documentType) {
            $signaturePayload['document_type'] = $documentType;
            $this->appendSignatureToPrintSettings($tenant, $documentType, $signaturePayload);
        }

        // Tenant does not have a logActivity trait (only WorkOrder does,
        // via HasWorkOrderOperations), so emit a structured log line
        // instead. The task contract allows skipping the call if the
        // convention does not exist; we keep a minimal audit trail.
        Log::info('print_settings.signature_uploaded', [
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'signature_id' => $signatureId,
            'document_type' => $documentType,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'path' => $path,
        ]);

        return $this->respondWithSignature($request, $signaturePayload);
    }
}
