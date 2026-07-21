<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\PrintSettings\Signatures;

use App\Http\Controllers\App\PrintSettings\Concerns\HandlesSignatureStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Print\UpdateSignatureRequest;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * PATCH /app/settings/print/signatures/{id}
 *
 * Update an existing signature's metadata — bilingual names and the
 * visibility (`show`) flag. The signature file itself is NOT replaced
 * here; to swap the image, the user must delete + re-upload. This
 * keeps the API surface small and avoids the "I lost my old
 * signature" support-ticket class.
 *
 * Why a single-action controller: see StoreSignatureController.
 */
class UpdateSignatureController extends Controller
{
    use HandlesSignatureStorage;

    public function __invoke(UpdateSignatureRequest $request, string $signatureId): Response
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

                    // Bilingual labels. If only `name` was sent (legacy
                    // clients / curl smoke tests), copy it into both
                    // so the template never renders an empty caption.
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

        return $this->respondWithSignature($request, $updated ?? []);
    }
}
