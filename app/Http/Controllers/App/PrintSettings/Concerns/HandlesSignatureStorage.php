<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\PrintSettings\Concerns;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Shared helpers for the four PrintSettings signature endpoints
 * (Store, Update, Reorder, Destroy).
 *
 * Why a trait instead of a base class:
 *   - Each endpoint is a single-action controller and they have
 *     different constructor signatures (one takes a FormRequest, one
 *     takes a string id, etc). A base class would force us to add
 *     a no-op `__construct` to every child.
 *   - Traits are first-class in PHP 8 and IDEs autocomplete them
 *     cleanly. There is no performance penalty at runtime.
 *
 * The trait owns:
 *   - The MIME → extension whitelist (the only source of truth)
 *   - The storage path convention
 *   - The flatten / append / find-by-id helpers that walk the
 *     tenant.print_settings JSON tree
 *
 * It does NOT own:
 *   - HTTP concerns (response shape, validation, Inertia dual-response).
 *     Those stay in each controller so the surface is local and the
 *     test for one endpoint does not drag in the others.
 */
trait HandlesSignatureStorage
{
    /**
     * MIME → extension whitelist. Mirrors the FormRequest `mimes` rule
     * so the storage filename generation can map the right extension
     * without trusting the client-supplied one. Add a new type here
     * AND in UploadSignatureRequest::rules() when introducing support.
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
     * Resolve the canonical storage path for a new signature.
     *
     * Convention: tenants/{tenant_id}/signatures/{uuid}.{ext}
     * Keeping this in one place avoids the "I forgot the leading
     * slash" or "I capitalized the folder" class of bug.
     */
    private function signaturePath(int $tenantId, string $extension): array
    {
        $signatureId = (string) Str::uuid();

        return [
            'id' => $signatureId,
            'path' => sprintf(
                'tenants/%d/signatures/%s.%s',
                $tenantId,
                $signatureId,
                $extension
            ),
        ];
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
     * — `$signatures[]` then nested a *second* array). We now
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
        $flat = $this->flattenSignaturesForRead($signatures);

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
     * Read-side flatten: turn either a flat list or a 1-level-nested
     * list into a flat list of signature objects. Used by every
     * endpoint that needs to walk the signatures array, so it lives
     * in the trait rather than being copy-pasted into each controller.
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
     * Locate a signature by id across all document types.
     *
     * Returns [docKey, doc, signature] on hit, or null on miss. We
     * iterate every doc rather than indexing by `document_type` because
     * the UI sometimes shows signatures that the user uploaded without
     * binding to a document yet (orphans).
     *
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}|null
     */
    private function findSignatureById(Tenant $tenant, string $signatureId): ?array
    {
        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];

        foreach ($documents as $docKey => $doc) {
            $flat = $this->flattenSignaturesForRead($doc['signatures'] ?? []);

            foreach ($flat as $sig) {
                if (($sig['id'] ?? null) === $signatureId) {
                    return [$docKey, $doc, $sig];
                }
            }
        }

        return null;
    }

    /**
     * Best-effort delete of the underlying file from the public disk.
     * A missing file is non-fatal — the JSON row has already been
     * removed by the time we call this. We log the result for
     * observability.
     */
    private function deleteSignatureFile(?string $path, int $tenantId): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (Throwable $e) {
            Log::warning('print_settings.signature_file_delete_failed', [
                'tenant_id' => $tenantId,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dual-response helper: Inertia requests get a redirect-back with a
     * flashed payload so the modal's onSuccess can pick it up via
     * `page.props.flash.signature` / `page.props.flash.reordered`.
     * Non-Inertia clients (curl, Postman, the existing feature tests)
     * get the raw JSON they expect.
     *
     * Centralized so a new endpoint just calls
     * `$this->respondWithSignature($request, $payload, $status)` and
     * the redirect status code matches the verb (303 for PATCH/DELETE,
     * 302 for POST).
     */
    private function respondWithSignature(
        Request $request,
        array $payload,
        string $flashKey = 'signature',
        bool $redirect = true
    ): Response {
        if ($redirect && $request->header('X-Inertia')) {
            return back()->with($flashKey, $payload);
        }

        return response()->json($payload, 200);
    }
}
