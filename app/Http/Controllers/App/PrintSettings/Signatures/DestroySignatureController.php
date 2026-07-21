<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\PrintSettings\Signatures;

use App\Http\Controllers\App\PrintSettings\Concerns\HandlesSignatureStorage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * DELETE /app/settings/print/signatures/{id}
 *
 * Remove a signature from the tenant's `print_settings.documents.*`
 * JSON column AND delete the underlying file from disk. The id is
 * the UUID minted in StoreSignatureController.
 *
 * We search every document type because a signature can be bound
 * to more than one document (the library tab shows them all) and
 * we want symmetric removal — the user's intent is "delete this
 * signature", not "remove it from this document only".
 *
 * Response: 204 No Content for non-Inertia clients (curl/Postman);
 * 302/303 redirect-back for Inertia clients so the modal's
 * onSuccess can re-render the library.
 */
class DestroySignatureController extends Controller
{
    use HandlesSignatureStorage;

    public function __invoke(string $signatureId): Response
    {
        $tenant = auth()->user()->tenant;
        $this->authorize('update', $tenant);

        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];
        $found = false;
        $removedFilePath = null;

        foreach ($documents as $docKey => $doc) {
            $signatures = $doc['signatures'] ?? [];
            // Same defensive flatten as StoreSignatureController so we
            // can locate a signature even if the stored shape is
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
                // template does not keep referencing a non-existent
                // id.
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

        // Best-effort file delete. A missing file is non-fatal — the
        // JSON row has already been removed.
        $this->deleteSignatureFile($removedFilePath, $tenant->id);

        Log::info('print_settings.signature_deleted', [
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'signature_id' => $signatureId,
        ]);

        // Inertia clients get a redirect-back; non-Inertia get 204.
        if (request()->header('X-Inertia')) {
            return back();
        }

        return response()->noContent();
    }
}
