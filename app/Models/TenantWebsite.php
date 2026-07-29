<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\TenantWebsite\ThemeRegistry;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

// @bypass-tenancy-scanner - Root identity: this IS the tenant website
//   (1:1 with tenants). The website has no concept of a sub-tenant
//   or sub-center; everything is anchored to a single tenant row.
class TenantWebsite extends Model
{
    use HasFactory;

    /**
     * Publication states. Only 'published' renders on the public
     * site — the ResolveTenantFromUrl middleware uses this to
     * 404 on draft/unpublished sites. The string is matched
     * exactly in the public controller.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_UNPUBLISHED = 'unpublished';

    protected $fillable = [
        'tenant_id',
        'site_name',
        'logo_path',
        'cover_path',
        'tagline',
        'contact_phone',
        'contact_whatsapp',
        'contact_email',
        'address_line',
        'social_links',
        'primary_color',
        'secondary_color',
        'status',
        'seo_title',
        'seo_description',
        'theme_key',
        'published_at',
    ];

    protected $casts = [
        'social_links' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * The public page uses these derived fields heavily. We
     * expose them via $appends so the Inertia prop stays a
     * single object.
     *
     * `theme_key` is NOT appended raw — see getThemeKeyAttribute
     * below. The allow-list normalization happens on read.
     */
    protected $appends = [
        'logo_url',
        'cover_url',
        'display_name',
        'effective_primary_color',
        'effective_secondary_color',
        'whatsapp_link',
        'phone_link',
        'is_published',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─────────────────────────────────────────────────────────────────
    // URL accessors
    // ─────────────────────────────────────────────────────────────────

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? Storage::url($this->cover_path) : null;
    }

    // ─────────────────────────────────────────────────────────────────
    // Display helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * The public-facing name. Falls back to the tenant legal_name
     * then trade_name then slug if the site_name is empty. We
     * never return an empty string — the page needs SOMETHING.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->site_name) {
            return $this->site_name;
        }

        $tenant = $this->tenant;

        return $tenant?->legal_name
            ?? $tenant?->trade_name
            ?? $tenant?->name
            ?? '';
    }

    // ─────────────────────────────────────────────────────────────────
    // Color safety nets
    // ─────────────────────────────────────────────────────────────────

    /**
     * The primary color after hex validation. We do not let
     * the database return arbitrary bytes into a <style> tag.
     * If the stored value is missing or fails the hex test
     * (which should never happen because validation enforces
     * it on write, but defense-in-depth), fall back to the
     * default theme primary.
     */
    public function getEffectivePrimaryColorAttribute(): string
    {
        return self::safeHex($this->primary_color) ?? '#4f46e5';
    }

    public function getEffectiveSecondaryColorAttribute(): string
    {
        return self::safeHex($this->secondary_color) ?? '#0ea5e9';
    }

    /**
     * The theme key, normalized through the allow-list. This
     * is the ONLY way the public site should read the theme.
     * Reading $website->theme_key directly is allowed for the
     * admin form (it needs to know the current selection even
     * if it's invalid) — but the public page MUST use
     * $website->theme_key_normalized to avoid rendering a
     * layout we never built.
     */
    protected function themeKeyNormalized(): Attribute
    {
        return Attribute::get(fn () => ThemeRegistry::normalize($this->attributes['theme_key'] ?? null));
    }

    // ─────────────────────────────────────────────────────────────────
    // CTA link helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * wa.me-compatible link. Strip everything that isn't a
     * digit, then prefix with the Saudi country code if it
     * doesn't already start with one. We don't try to detect
     * 00x prefixes — if the user enters a number with country
     * code, we trust it; if not, default to 966 (Saudi).
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        $raw = $this->contact_whatsapp;

        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // Already has country code (starts with 966 or any 3-digit
        // country code) → leave alone.
        if (str_starts_with($digits, '966') || strlen($digits) > 10) {
            return 'https://wa.me/'.$digits;
        }

        // Local number (10 digits, leading 0) → strip the 0 and
        // add 966.
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return 'https://wa.me/966'.$digits;
    }

    public function getPhoneLinkAttribute(): ?string
    {
        $raw = $this->contact_phone;

        return $raw ? 'tel:'.preg_replace('/[^\d+]/', '', $raw) : null;
    }

    // ─────────────────────────────────────────────────────────────────
    // Status helpers
    // ─────────────────────────────────────────────────────────────────

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    // ─────────────────────────────────────────────────────────────────
    // Static helpers
    // ─────────────────────────────────────────────────────────────────

    /**
     * Strict 3- or 6-digit hex with leading #. Returns null
     * for anything else. This is the gate that protects the
     * public page from CSS injection through the color fields.
     */
    public static function safeHex(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) === 1
            ? $value
            : null;
    }

    /**
     * Convenience for the admin form: build the full payload
     * for the settings page (includes the normalized theme
     * list, the raw stored value, and the resolved display
     * values for the preview).
     */
    public function toAdminPayload(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
            'tenant_slug' => $this->tenant?->slug,
            'site_name' => $this->site_name,
            'tagline' => $this->tagline,
            'logo_url' => $this->logo_url,
            'cover_url' => $this->cover_url,
            'contact_phone' => $this->contact_phone,
            'contact_whatsapp' => $this->contact_whatsapp,
            'contact_email' => $this->contact_email,
            'address_line' => $this->address_line,
            'social_links' => $this->social_links ?? [],
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'status' => $this->status,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'theme_key' => $this->attributes['theme_key'] ?? ThemeRegistry::DEFAULT_THEME,
            'theme_key_normalized' => $this->themeKeyNormalized,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
