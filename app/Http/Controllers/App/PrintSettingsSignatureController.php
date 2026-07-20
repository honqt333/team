<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\Print\ReorderSignaturesRequest;
use App\Http\Requests\App\Print\UpdateSignatureRequest;
use App\Http\Requests\App\Print\UploadSignatureRequest;
use App\Models\Tenant;
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
    public function store(UploadSignatureRequest $request): \Symfony\Component\HttpFoundation\Response
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

        // Dual response shape: Inertia requests must receive a proper
        // Inertia response (a redirect or a full page render), never a
        // plain JSON body — otherwise the Inertia client throws
        //   "All Inertia requests must receive a valid Inertia response,
        //    however a plain JSON response was received."
        // and the modal's onSuccess handler never fires, even though the
        // upload succeeded (file is on disk, JSON column is updated).
        //
        // We detect Inertia by the standard `X-Inertia` header that every
        // Inertia request sets. For Inertia clients we flash the saved
        // signature payload into the session so the modal's
        //   page.props.flash.signature
        // reader picks it up on the redirect back. For non-Inertia clients
        // (curl, Postman, smoke tests, the existing feature tests) we
        // return the raw JSON they expect.
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

        // Dual response: see store() for the rationale. Inertia requests
        // get a redirect back; non-Inertia clients (curl, Postman, feature
        // tests) get the legacy 204 No Content.
        if (request()->header('X-Inertia')) {
            return back();
        }

        return response()->noContent();
    }

    /**
     * Update an existing signature's metadata — bilingual names and
     * visibility (`show`) flag.
     *
     * The signature file itself is NOT replaced here. To swap the
     * image, the user must delete + re-upload. This keeps the API
     * surface small and avoids a class of "I lost my old signature"
     * support tickets.
     *
     * What we DO update:
     *   - name_ar / name_en: the human-readable labels shown in the
     *     print layout. The legacy `name` field is mirrored into both
     *     so the template never renders an empty caption.
     *   - show: when false, the print template's `s.show !== false`
     *     filter hides the signature from the footer without removing
     *     it from storage. The user can re-enable it later.
     *
     * Concurrency: the JSON column is a single atomic read-modify-write
     * on the Tenant model. Two concurrent updates would race; this is
     * an admin-only endpoint with low traffic so the worst case is a
     * "your change was overwritten" report, not data corruption. If
     * the volume grows, switch to a row-level lock or JSONB column
     * with native concurrency control.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(UpdateSignatureRequest $request, string $signatureId)
    {
        $tenant = auth()->user()->tenant;
        $this->authorize('update', $tenant);

        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];
        $found = false;
        $updated = null;

        foreach ($documents as $docKey => $doc) {
            $signatures = $doc['signatures'] ?? [];
            $flat = $this->flattenSignaturesForRead($signatures);
            $kept = [];
            foreach ($flat as $sig) {
                if (($sig['id'] ?? null) === $signatureId) {
                    $found = true;
                    $data = $request->validatedWithDefaults();

                    // Bilingual labels — at least one must be present
                    // so the template never has an empty caption. If
                    // the client sent `name` only, copy it into both.
                    if (isset($data['name_ar'])) {
                        $sig['name_ar'] = (string) $data['name_ar'];
                    }
                    if (isset($data['name_en'])) {
                        $sig['name_en'] = (string) $data['name_en'];
                    }
                    if (isset($data['name']) && ! isset($sig['name_ar']) && ! isset($sig['name_en'])) {
                        $sig['name_ar'] = (string) $data['name'];
                        $sig['name_en'] = (string) $data['name'];
                    }
                    // Keep the legacy `name` mirror in sync.
                    $sig['name'] = $sig['name_en'] ?? $sig['name_ar'] ?? '';

                    // Visibility — boolean. Absent key means "no change".
                    if (array_key_exists('show', $data)) {
                        $sig['show'] = (bool) $data['show'];
                    }

                    $sig['updated_at'] = now()->format('Y-m-d H:i:s');
                    $sig['updated_by'] = auth()->user()?->name;

                    $updated = $sig;
                }
                $kept[] = $sig;
            }
            if ($found) {
                $documents[$docKey]['signatures'] = $kept;
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

        Log::info('print_settings.signature_updated', [
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'signature_id' => $signatureId,
            'fields' => array_keys($request->validatedWithDefaults()),
        ]);

        // Same dual-response pattern as store() and destroy().
        if ($request->header('X-Inertia')) {
            return back()->with('signature', $updated);
        }

        return response()->json($updated, 200);
    }

    /**
     * Reorder signatures within a single document type.
     *
     * The signature order in the JSON array IS the order the print
     * template renders them (left → right in A4, top → bottom in
     * Thermal). Reordering = rewriting the array in the requested
     * sequence.
     *
     * Safety rules enforced by the controller (in addition to the
     * FormRequest's shape validation):
     *   1. The submitted `order` MUST be a permutation of the existing
     *      signature ids. We never silently drop or duplicate a
     *      signature — reordering is a "shuffle", not a "remix".
     *   2. If `document_type` is given, we reorder only that document.
     *      If omitted, we apply the same order to ALL documents that
     *      contain the same set of signatures (rare; mostly for the
     *      library view where signatures are doc-agnostic).
     *   3. We never touch signatures from OTHER tenants. The
     *      `$signatureId` is matched against THIS tenant's JSON only.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reorder(ReorderSignaturesRequest $request)
    {
        $tenant = auth()->user()->tenant;
        $this->authorize('update', $tenant);

        $validated = $request->validated();
        /** @var array<int, string> $order */
        $order = $validated['order'];
        $documentType = $validated['document_type'] ?? null;

        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];

        // If document_type is given, target only that document. Otherwise
        // apply to every document whose signature set matches `order`.
        $targetDocKeys = $documentType
            ? [$documentType]
            : array_keys($documents);

        $touched = [];
        $mismatch = false;

        foreach ($targetDocKeys as $docKey) {
            if (! isset($documents[$docKey])) {
                if ($documentType) {
                    $mismatch = true;

                    break;
                }

                continue;
            }

            $signatures = $documents[$docKey]['signatures'] ?? [];
            $flat = $this->flattenSignaturesForRead($signatures);

            // Build id -> signature map for O(1) lookup.
            $byId = [];
            foreach ($flat as $sig) {
                $id = $sig['id'] ?? null;
                if ($id) {
                    $byId[$id] = $sig;
                }
            }

            // Validate permutation: the set of ids in $order must equal
            // the set of ids currently stored for this document.
            $existingIds = array_keys($byId);
            $submittedIds = $order;

            sort($existingIds);
            $sortedSubmitted = $submittedIds;
            sort($sortedSubmitted);

            if ($existingIds !== $sortedSubmitted) {
                $mismatch = true;

                break;
            }

            // Rebuild the array in the new order.
            $reordered = [];
            foreach ($order as $id) {
                $reordered[] = $byId[$id];
            }

            $documents[$docKey]['signatures'] = $reordered;
            $documents[$docKey]['updated_at'] = now()->format('Y-m-d H:i:s');
            $documents[$docKey]['updated_by'] = auth()->user()?->name;
            $touched[] = $docKey;
        }

        if ($mismatch) {
            return response()->json([
                'message' => 'The submitted order does not match the current signatures. Reload the page and try again.',
            ], 422);
        }

        $current['documents'] = $documents;
        $tenant->print_settings = $current;
        $tenant->save();

        Log::info('print_settings.signatures_reordered', [
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'documents' => $touched,
            'count' => count($order),
        ]);

        // Dual response: Inertia clients get a redirect-back with the
        // new order in flash, so the modal can re-render without a
        // full page reload. Non-Inertia clients get the raw JSON.
        if ($request->header('X-Inertia')) {
            return back()->with('reordered', [
                'documents' => $touched,
                'order' => $order,
            ]);
        }

        return response()->json([
            'documents' => $touched,
            'order' => $order,
        ], 200);
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
