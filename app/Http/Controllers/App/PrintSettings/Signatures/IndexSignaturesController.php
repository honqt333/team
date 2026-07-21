<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\PrintSettings\Signatures;

use App\Http\Controllers\App\PrintSettings\Concerns\HandlesSignatureStorage;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;

/**
 * GET /app/settings/print/signatures
 *
 * Read-only listing of all signatures across all document types.
 * The actual upload happens via the JSON endpoints; this page is
 * here for the rare case the admin wants to inspect the library
 * outside the modal flow.
 */
class IndexSignaturesController extends Controller
{
    use HandlesSignatureStorage;

    public function __invoke(): Response
    {
        /** @var Tenant $tenant */
        $tenant = auth()->user()->tenant;
        $signatures = $this->flattenAll($tenant);

        return Inertia::render('Settings/Print/Signatures/Index', [
            'signatures' => $signatures,
        ]);
    }

    /**
     * Flatten all signatures across all document types for the index
     * page. Each entry is annotated with `document_type` so the UI
     * can group by document.
     *
     * @return array<int, array<string, mixed>>
     */
    private function flattenAll(Tenant $tenant): array
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
