<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_websites', function (Blueprint $table) {
            $table->id();

            // 1:1 with tenants. The unique index makes it impossible
            // for a tenant to ever own two website rows, even under
            // a race condition. This is the ONLY FK that matters —
            // every other field is configuration data, no separate
            // identity required.
            $table->foreignId('tenant_id')
                ->unique()
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Display name shown in the hero + page title.
            // Nullable so a freshly seeded tenant can show the
            // legal_name fallback until the owner sets a custom one.
            $table->string('site_name', 255)->nullable();

            // Logo + cover image paths. Files are stored under
            // "tenants/{tenant_id}/website/" — same pattern used
            // for the tenant logo to keep disk layout predictable.
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();

            // Short tagline shown under the hero title. Plain text
            // (no rich text) — Phase 6 deliberately avoids a CMS.
            $table->string('tagline', 500)->nullable();

            // Public contact channels. We separate WhatsApp from
            // phone because they get rendered with different CTAs
            // (one is a wa.me link, the other is tel:).
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_whatsapp', 30)->nullable();
            $table->string('contact_email', 255)->nullable();

            // Free-form address shown on the public page. We
            // intentionally do NOT use CenterAddress here — the
            // tenant can present a single address that may differ
            // from any individual center (e.g. P.O. Box).
            $table->string('address_line', 500)->nullable();

            // JSON: { facebook, twitter, instagram, linkedin, tiktok, youtube }.
            // Stored as JSON to keep the schema flat. Validation on
            // write enforces a strict URL-or-empty per key.
            $table->json('social_links')->nullable();

            // Brand colors. Validation on write is strict: must
            // match /^#([0-9a-fA-F]{3}){1,2}$/. A separate
            // dedicated column for accent color lets the public
            // page wire two CSS variables without re-parsing.
            $table->string('primary_color', 9)->default('#4f46e5');
            $table->string('secondary_color', 9)->default('#0ea5e9');

            // Publication state. Only 'published' rows render the
            // public page (middleware 404s on draft/unpublished).
            // Use a string + CHECK rather than boolean so we can
            // add future states (e.g. 'archived') without a
            // migration.
            $table->string('status', 20)->default('draft');

            // SEO — optional, both fields. Kept short to keep the
            // <head> slim; longer content would belong in a CMS
            // block in a later phase.
            $table->string('seo_title', 70)->nullable();
            $table->string('seo_description', 200)->nullable();

            // Theme key — allow-listed. The Vue public page uses
            // this to pick a layout; unknown values are coerced
            // to 'default' at read time so a stale or injected
            // value cannot crash the page. Stored as a plain
            // string; the allow-list lives in code
            // (App\Support\TenantWebsite\ThemeRegistry) so adding
            // a new theme does not require a migration.
            $table->string('theme_key', 50)->default('default');

            // Audit fields — when the website was first published
            // and when it was last edited. populated_at is the
            // canonical "is this site live" timestamp; updated_at
            // is just touch-time.
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // We index status because the public lookup path
            // filters by it on every request ("WHERE status =
            // 'published' AND tenant_id = ?"). Without the index
            // the planner falls back to a full scan once a
            // tenant accumulates audit log rows.
            $table->index(['status', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_websites');
    }
};
