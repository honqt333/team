<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\PrintSettings\Signatures;

use App\Http\Controllers\App\PrintSettings\Concerns\HandlesSignatureStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Print\ReorderSignaturesRequest;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /app/settings/print/signatures/reorder
 *
 * Reorder signatures within a single document type. The signature
 * order in the JSON array IS the order the print template renders
 * them (left → right in A4, top → bottom in Thermal).
 *
 * Safety rules:
 *   1. The submitted `order` MUST be a permutation of the existing
 *      signature ids. We never silently drop or duplicate a signature
 *      — reordering is a "shuffle", not a "remix".
 *   2. If `document_type` is given, we reorder only that document.
 *      If omitted, we apply the same order to ALL documents that
 *      contain the same set of signatures (rare; mostly for the
 *      library view where signatures are doc-agnostic).
 *   3. We never touch signatures from OTHER tenants.
 */
class ReorderSignaturesController extends Controller
{
    use HandlesSignatureStorage;

    public function __invoke(ReorderSignaturesRequest $request): Response
    {
        $tenant = auth()->user()->tenant;
        $this->authorize('update', $tenant);

        $validated = $request->validated();
        /** @var array<int, string> $order */
        $order = $validated['order'];
        $documentType = $validated['document_type'] ?? null;

        $current = $tenant->print_settings ?? [];
        $documents = $current['documents'] ?? [];

        // If document_type is given, target only that document.
        // Otherwise apply to every document whose signature set
        // matches `order`.
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

            // Build id => signature map for O(1) lookup.
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

        $payload = [
            'documents' => $touched,
            'order' => $order,
        ];

        // Reorder uses `reordered` as the flash key (not `signature`)
        // so a UI that reads `page.props.flash.reordered` can branch
        // off it without colliding with the upload/update paths.
        if ($request->header('X-Inertia')) {
            return back()->with('reordered', $payload);
        }

        return response()->json($payload, 200);
    }
}
