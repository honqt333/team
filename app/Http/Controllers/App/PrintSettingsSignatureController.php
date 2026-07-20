<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Print\UploadSignatureRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PrintSettingsSignatureController extends Controller
{
    /**
     * Allowed MIME types for signature uploads. Mirrors the FormRequest
     * `mimes` rule, kept here so the storage filename generation can map
     * the right extension without trusting the client-supplied one.
     *
     * @var array<string, string> ext => mime
     */
    private const EXTENSION_MIME_MAP = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];

    /**
     * Show the print-signatures management page.
     *
     * Currently a thin read-only listing — the actual upload happens via
     * the JSON endpoint below, the print settings index page consumes the
     * signatures from the tenant `print_settings.documents.*.signatures`
     * array.
     */
    public function index(): Response
    {
        $tenant = auth()->user()->tenant;
        $signatures = $this->flattenSignatures($tenant);

        return Inertia::render('Settings/Print/Signatures/Index', [
            'signatures' => $signatures,
        ]);
    }

    /**
     * Store a new signature image and append its metadata to the tenant
     * `print_settings` JSON.
     *
     * Storage layout:
     *   storage/app/public/tenants/{tenant_id}/signatures/{uuid}.{ext}
     *
     * Response (200):
     *   {
     *     "id":         "uuid-v4",
     *     "url":        "/storage/tenants/.../signatures/{uuid}.{ext}",
     *     "name":       "...",
     *     "uploaded_at":"2026-07-11T16:00:00+00:00",
     *     "size":       12345,
     *     "mime_type":  "image/png",
     *     "document_type": "invoice"   // null if uploaded without a target
     *   }
     */
    public function store(UploadSignatureRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $tenant = $request->user()->tenant;

        // If the user->tenant relationship is not eager-loaded and for
        // some reason returns null, fall back to a direct lookup by id.
        if (! $tenant) {
            $tenant = Tenant::find($request->user()->tenant_id);
        }

        // Final defense-in-depth check at the controller — the FormRequest
        // already enforces permission but we re-check here so that a
        // permission change between request validation and storage still
        // refuses the upload.
        $this->authorize('update', $tenant);

        $file = $request->file('signature');

        // Re-derive the extension from the actual MIME type detected by
        // PHP's fileinfo (NOT from the client-supplied filename). This
        // prevents extension-spoofing attacks (e.g. "evil.php.png").
        $detectedExtension = $this->resolveExtensionFromMime($file);
        if ($detectedExtension === null) {
            // Should be impossible — the FormRequest already enforced the
            // MIME whitelist — but fail closed if it happens.
            abort(422, __('validation.mimes', [
                'attribute' => __('validation.attributes.signature'),
                'values' => 'png, jpg, jpeg, svg, webp',
            ]));
        }

        $signatureId = (string) Str::uuid();
        $path = sprintf(
            'tenants/%d/signatures/%s.%s',
            $tenant->id,
            $signatureId,
            $detectedExtension
        );

        // Use putFileAs via a temporary file. The point of using putFileAs
        // over storeAs is that putFileAs streams the file from a tmp path
        // without copying it through PHP memory first — safer for the 2MB
        // cap on a shared PHP-FPM.
        Storage::disk('public')->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        $uploadedAt = now();

        // Bilingual labels: the print template renders sig.name_ar and
        // sig.name_en directly. We require both in the FormRequest, but
        // fall back to the legacy single `name` field for older clients
        // and curl-based smoke tests so the controller never returns an
        // empty name pair.
        $nameAr = $request->string('name_ar')->toString()
            ?: $request->string('name')->toString();
        $nameEn = $request->string('name_en')->toString()
            ?: $request->string('name')->toString();

        $signaturePayload = [
            'id' => $signatureId,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            // Legacy single-name field kept for clients that still read it
            // (it is no longer the canonical source of truth).
            'name' => $nameEn ?: $nameAr,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'uploaded_at' => $uploadedAt->toIso8601String(),
            'uploaded_by' => $request->user()->id,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];

        $documentType = $request->input('document_type');

        // If the caller passed a `document_type`, append the signature to
        // the tenant print_settings JSON for that document. We do this
        // atomically against the cast `array` so Laravel encodes JSON.
        if ($documentType) {
            $signaturePayload['document_type'] = $documentType;
            $this->appendSignatureToPrintSettings($tenant, $documentType, $signaturePayload);
        }

        // Observability — Tenant does not have a logActivity trait (only
        // WorkOrder does, via HasWorkOrderOperations), so emit a structured
        // log line instead. The task contract allows skipping the call if
        // the convention does not exist; we keep a minimal audit trail.
        Log::info('print_settings.signature_uploaded', [
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'signature_id' => $signatureId,
            'document_type' => $documentType,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'path' => $path,
        ]);

        // Two response shapes:
        //
        //   1. Inertia call (X-Inertia: true header from
        //      Inertia.useForm().post(...)) → flash the payload and
        //      redirect back so the page receives an Inertia response
        //      it can render. The SignatureModal.vue reads
        //      `page.props.flash.signature` in onSuccess.
        //
        //   2. Plain API / curl call → return the JSON payload as-is
        //      so external consumers (mobile apps, smoke tests) still
        //      get a stable contract.
        if ($request->header('X-Inertia')) {
            return back()->with('signature', $signaturePayload);
        }

        return response()->json($signaturePayload, 200);
    }

    /**
     * Delete a signature by id.
     *
     * The id is the UUID we minted in `store()`. We search every document
     * type under the tenant and remove the matching entry — this is
     * symmetric with the Inertia save flow which also stores signatures
     * by document_type.
     *
     * If the signature was not found anywhere, return 404. If it WAS
     * found, we also try to delete the underlying file from disk to
     * keep storage clean. The file delete is best-effort: a missing
     * file (e.g. already removed by an admin) does not roll back the
     * database row removal.
     *
     * Response: 204 No Content on success.
     */
    public function destroy(string $signatureId)
    {
        $tenant = auth()->user()->tenant;
        $this->authorize('update', $tenant);

        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];
        $found = false;
        $removedFilePath = null;

        foreach ($documents as $docKey => $doc) {
            $signatures = $doc['signatures'] ?? [];
            // Same defensive flatten as appendSignatureToPrintSettings so
            // we can locate a signature even if the stored shape is
            // nested from a previous bug.
            $flat = $this->flattenSignaturesForRead($signatures);
            $kept = [];
            foreach ($flat as $sig) {
                if (($sig['id'] ?? null) === $signatureId) {
                    $found = true;
                    if (! empty($sig['path'])) {
                        $removedFilePath = $sig['path'];
                    }

                    continue;
                }
                $kept[] = $sig;
            }
            if ($found) {
                $documents[$docKey]['signatures'] = $kept;
                // If the deleted signature was the primary footer
                // reference on this document, clear that too so the
                // template does not keep referencing a non-existent id.
                if (($documents[$docKey]['signature']['id'] ?? null) === $signatureId) {
                    $documents[$docKey]['signature'] = null;
                }
                $documents[$docKey]['updated_at'] = now()->format('Y-m-d H:i:s');
                $documents[$docKey]['updated_by'] = auth()->user()?->name;
                break;
            }
        }

        if (! $found) {
            return response()->json(['message' => 'Signature not found'], 404);
        }

        $current['documents'] = $documents;
        $tenant->print_settings = $current;
        $tenant->save();

        // Best-effort file delete. A missing file is non-fatal.
        if ($removedFilePath) {
            try {
                Storage::disk('public')->delete($removedFilePath);
            } catch (\Throwable $e) {
                Log::warning('print_settings.signature_file_delete_failed', [
                    'tenant_id' => $tenant->id,
                    'path' => $removedFilePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('print_settings.signature_deleted', [
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'signature_id' => $signatureId,
        ]);

        // Mirror the store() dual-response contract: Inertia calls
        // (X-Inertia: true) get a flash + back() redirect so the page
        // re-renders cleanly; plain API/curl calls get 204 No Content.
        if (request()->header('X-Inertia')) {
            return back()->with('success', __('Signature deleted.'));
        }

        return response()->noContent();
    }

    /**
     * Read-side flatten: turn either a flat list or a 1-level-nested
     * list into a flat list of signature objects. Used by destroy() to
     * locate a signature by id even if the saved shape is the legacy
     * nested format.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flattenSignaturesForRead(mixed $signatures): array
    {
        $flat = [];
        foreach ((array) $signatures as $item) {
            if (is_array($item) && array_is_list($item)) {
                foreach ($item as $sub) {
                    if (is_array($sub)) {
                        $flat[] = $sub;
                    }
                }
            } elseif (is_array($item)) {
                $flat[] = $item;
            }
        }

        return $flat;
    }

    /**
     * Map a PHP-detected MIME type to a safe, lowercase file extension.
     * Returns null if the MIME is not in the whitelist.
     */
    private function resolveExtensionFromMime(UploadedFile $file): ?string
    {
        $mime = $file->getMimeType();

        foreach (self::EXTENSION_MIME_MAP as $extension => $allowed) {
            if ($mime === $allowed) {
                return $extension;
            }
        }

        // Some servers / tmp file drivers report octet-stream until the
        // file is fully written. Trust the client extension ONLY as a
        // last-resort hint, AND only if it's in our whitelist.
        $clientExt = strtolower($file->getClientOriginalExtension());
        if (array_key_exists($clientExt, self::EXTENSION_MIME_MAP)) {
            return $clientExt;
        }

        return null;
    }

    /**
     * Append a signature payload to `print_settings.documents.{type}.signatures`
     * in the tenant JSON column, preserving the rest of the JSON.
     *
     * The signatures field is unconditionally a flat array of signature
     * objects. The previous implementation used `$signatures[] = $payload`
     * which silently produced a nested array whenever a caller had saved
     * the document via PUT /app/settings/system with a `signatures: []`
     * payload of its own (e.g. the Inertia form re-submits the full
     * documents object on Save, so `signatures` was already an array
     // — `$signatures[]` then nested a *second* array). We now
     * normalise the existing value with array_merge and reject any
     * shape that isn't a list of signature objects.
     */
    private function appendSignatureToPrintSettings(Tenant $tenant, string $documentType, array $signaturePayload): void
    {
        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];
        $document = $documents[$documentType] ?? [];
        $signatures = $document['signatures'] ?? [];

        // Defensive: flatten if the saved shape is already nested. The
        // expected shape is a list of {id, name_ar, name_en, url, ...}
        // associative objects; anything else is treated as orphan and
        // dropped, except the payload we are about to add.
        $flat = [];
        foreach ((array) $signatures as $item) {
            if (is_array($item) && array_is_list($item)) {
                // nested list — recurse one level
                foreach ($item as $sub) {
                    if (is_array($sub) && isset($sub['id'])) {
                        $flat[] = $sub;
                    }
                }
            } elseif (is_array($item) && isset($item['id'])) {
                $flat[] = $item;
            }
        }

        $flat[] = $signaturePayload;

        $document['signatures'] = $flat;
        $document['updated_at'] = now()->format('Y-m-d H:i:s');
        $document['updated_by'] = auth()->user()?->name;

        $documents[$documentType] = $document;
        $current['documents'] = $documents;

        $tenant->print_settings = $current;
        $tenant->save();
    }

    /**
     * Flatten all signatures across all document types for the index page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flattenSignatures(Tenant $tenant): array
    {
        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];

        $flat = [];
        foreach ($documents as $type => $doc) {
            foreach (($doc['signatures'] ?? []) as $sig) {
                $flat[] = array_merge($sig, ['document_type' => $type]);
            }
        }

        return $flat;
    }
}
