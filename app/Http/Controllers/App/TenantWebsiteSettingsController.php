<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\TenantWebsite;
use App\Support\Media\ImageType;
use App\Support\TenantWebsite\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tenant-facing website settings. The endpoints under
 * /app/settings/website are mounted in the authenticated
 * + tenant.active + center.context route group, so by
 * the time we reach these methods:
 *
 *   - auth()->user() is a User belonging to a tenant
 *   - auth()->user()->tenant_id is the current tenant
 *
 * CRITICAL: there is no path-parameter tenant here. The
 * controller ALWAYS operates on the auth user's tenant
 * (auth()->user()->tenant_id), NEVER on a tenant_id from
 * the URL or request payload. This is what blocks a user
 * in tenant A from editing tenant B's website — the
 * request body or URL is not consulted.
 */
class TenantWebsiteSettingsController extends Controller
{
    /**
     * Show the website settings form. The form receives
     * the full payload (including the theme allow-list) so
     * the Vue page can render a dropdown without a second
     * round-trip.
     */
    public function edit(): Response
    {
        $tenantId = $this->currentTenantId();
        $website = $this->getOrCreateWebsite($tenantId);

        return Inertia::render('Settings/Website', [
            'website' => $website->toAdminPayload(),
            'theme_options' => ThemeRegistry::optionsForSelect(),
        ]);
    }

    /**
     * Persist updates. Validates strictly:
     *   - colors must be 3- or 6-digit hex
     *   - status must be one of the known states
     *   - theme_key must be in the allow-list (returns 422
     *     on violation — this is the explicit "reject" path
     *     the spec calls out for theme_key)
     *   - social links: each URL must be http/https or empty
     *   - file uploads: image type, max 4MB, stored under
     *     tenants/{id}/website/
     */
    public function update(Request $request): RedirectResponse
    {
        $tenantId = $this->currentTenantId();
        $website = $this->getOrCreateWebsite($tenantId);

        $data = $this->validatePayload($request, $tenantId, $website);

        // Handle file uploads BEFORE persisting so a failed
        // upload can be reported before the partial update.
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->storeImage($request->file('logo'), $tenantId, 'logo', $website->logo_path);
        }

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $this->storeImage($request->file('cover'), $tenantId, 'cover', $website->cover_path);
        }

        // Status transitions: stamp published_at on the
        // first publish so the audit trail is correct.
        $newStatus = $data['status'] ?? $website->status;
        $oldStatus = $website->status;

        if ($newStatus === TenantWebsite::STATUS_PUBLISHED && $oldStatus !== TenantWebsite::STATUS_PUBLISHED) {
            $data['published_at'] = now();
        } elseif ($newStatus !== TenantWebsite::STATUS_PUBLISHED) {
            // Unpublishing clears the timestamp so the next
            // publish gets a fresh date.
            $data['published_at'] = null;
        }

        $website->fill($data);
        $website->save();

        return back()->with('success', __('tenant_site.admin.saved'));
    }

    /**
     * Delete a single image (logo or cover). Validates
     * the key against a literal allow-list.
     */
    public function deleteImage(Request $request): JsonResponse
    {
        $request->validate([
            'key' => ['required', 'string', Rule::in(['logo', 'cover'])],
        ]);

        $tenantId = $this->currentTenantId();
        $website = $this->getOrCreateWebsite($tenantId);

        $column = $request->input('key').'_path';
        $current = $website->{$column};

        if ($current && Storage::disk('public')->exists($current)) {
            Storage::disk('public')->delete($current);
        }

        $website->{$column} = null;
        $website->save();

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function currentTenantId(): int
    {
        $tenantId = auth()->user()?->tenant_id;

        if (! $tenantId) {
            abort(403, __('tenant_site.errors.no_tenant'));
        }

        return (int) $tenantId;
    }

    /**
     * Get-or-create the website row. We create on demand
     * the first time the user visits /app/settings/website
     * so the form always has a row to update.
     */
    private function getOrCreateWebsite(int $tenantId): TenantWebsite
    {
        return TenantWebsite::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'status' => TenantWebsite::STATUS_DRAFT,
                'primary_color' => '#4f46e5',
                'secondary_color' => '#0ea5e9',
                'theme_key' => ThemeRegistry::DEFAULT_THEME,
            ]
        );
    }

    /**
     * Strict validation. The theme_key check uses
     * Rule::in(ThemeRegistry::allowedKeys()) so an
     * injected/missing key is rejected at the validator
     * layer. This is the explicit "reject" path the
     * spec asks for.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, int $tenantId, TenantWebsite $website): array
    {
        $allowedKeys = ThemeRegistry::allowedKeys();

        return $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],

            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:500'],

            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }

                if (! preg_match('#^https?://[^\s]+$#i', $value)) {
                    $fail(__('tenant_site.admin.errors.social_url_invalid', ['field' => $attribute]));
                }
            }],

            'primary_color' => ['required', 'string', 'max:9', function ($attribute, $value, $fail) {
                if (TenantWebsite::safeHex($value) === null) {
                    $fail(__('tenant_site.admin.errors.color_invalid'));
                }
            }],
            'secondary_color' => ['required', 'string', 'max:9', function ($attribute, $value, $fail) {
                if (TenantWebsite::safeHex($value) === null) {
                    $fail(__('tenant_site.admin.errors.color_invalid'));
                }
            }],

            'status' => ['required', Rule::in([
                TenantWebsite::STATUS_DRAFT,
                TenantWebsite::STATUS_PUBLISHED,
                TenantWebsite::STATUS_UNPUBLISHED,
            ])],

            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:200'],

            'theme_key' => ['required', 'string', Rule::in($allowedKeys)],

            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'cover' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ], [], [
            'theme_key' => __('tenant_site.admin.fields.theme_key'),
        ]);
    }

    /**
     * Store an image under tenants/{tenantId}/website/.
     * Deletes the old file if present.
     *
     * The extension of an uploaded file is chosen by the client, so it is
     * never used here: the type is read from the bytes, and the extension
     * written to disk comes from that. SVG is refused — it is served
     * straight from the public disk, and it can carry scripts.
     */
    private function storeImage(UploadedFile $file, int $tenantId, string $slot, ?string $oldPath): string
    {
        $binary = (string) file_get_contents($file->getRealPath());

        $extension = ImageType::extensionFor($binary);

        if ($extension === null) {
            throw ValidationException::withMessages([
                $slot => __('tenant_site.admin.errors.image_type'),
            ]);
        }

        // The old file is only removed once the new one is known to be
        // good — a rejected upload must not leave the tenant without a
        // logo.
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $filename = $slot.'_'.time().'.'.$extension;
        $directory = "tenants/{$tenantId}/website";

        Storage::disk('public')->put("{$directory}/{$filename}", $binary);

        return "{$directory}/{$filename}";
    }
}
