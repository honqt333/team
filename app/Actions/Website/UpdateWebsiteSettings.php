<?php

declare(strict_types=1);

namespace App\Actions\Website;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateWebsiteSettings
{
    /**
     * Execute the action.
     *
     * @param array<string, mixed> $settings
     */
    public function execute(array $settings): bool
    {
        $imageKeys = ['website_logo', 'website_favicon'];
        $oldImageUrls = $this->getCurrentImageUrls($imageKeys);

        DB::transaction(function () use ($settings, $imageKeys, $oldImageUrls) {
            foreach ($settings as $key => $value) {
                // Handle file uploads (already processed by controller)
                if ($value instanceof UploadedFile) {
                    if (in_array($key, $imageKeys)) {
                        $path = $value->store('website', 'public');
                        $value = asset('storage/'.$path);

                        // Delete old image file
                        $oldUrl = $oldImageUrls[$key] ?? null;

                        if ($oldUrl) {
                            $this->deleteOldFile($oldUrl);
                        }
                    }
                }

                // Handle lists - encode as JSON
                if (is_array($value)) {
                    // Handle file uploads inside lists (e.g., banners)
                    if ($key === 'landing_banners_list') {
                        foreach ($value as $index => &$item) {
                            if (isset($item['image']) && $item['image'] instanceof UploadedFile) {
                                $path = $item['image']->store('website/banners', 'public');
                                $item['image'] = asset('storage/'.$path);
                            }
                        }
                    }
                    $value = json_encode($value);
                }

                // SANITIZE SCRIPTS TO PREVENT XSS
                if (in_array($key, ['website_scripts_header', 'website_scripts_footer', 'website_chatbot_script'])) {
                    $value = $this->sanitizeScripts($value);
                }

                // Skip extremely long values
                if (is_string($value) && strlen($value) > 50000) {
                    Log::warning('Website setting value too long, skipped', ['key' => $key]);
                    continue;
                }

                // Convert key format: website_title_ar -> website.title.ar
                $dbKey = str_replace('_', '.', $key);

                $group = str_starts_with($dbKey, 'landing.') ? 'landing' : 'website';

                Setting::set($dbKey, $value, $group);
            }
        });

        // Log activity
        $this->logActivity($settings);

        return true;
    }

    /**
     * Get current image URLs before update for cleanup.
     *
     * @param array<string> $keys
     * @return array<string, string|null>
     */
    private function getCurrentImageUrls(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $dbKey = str_replace('_', '.', $key);
            $setting = Setting::whereNull('tenant_id')->where('key', $dbKey)->first();
            $result[$key] = $setting?->value;
        }

        return $result;
    }

    /**
     * Delete old file from storage when replaced.
     */
    private function deleteOldFile(string $url): void
    {
        // Extract path from URL (e.g., /storage/website/logo.png -> website/logo.png)
        if (str_starts_with($url, asset('storage/'))) {
            $path = str_replace(asset('storage/'), '', $url);

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * Log the settings update activity.
     *
     * @param array<string, mixed> $settings
     */
    private function logActivity(array $settings): void
    {
        if (! function_exists('activity')) {
            Log::info('Website settings updated', [
                'admin_id' => Auth::id(),
                'settings_count' => count($settings),
            ]);

            return;
        }

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'settings_updated' => array_keys($settings),
                'count' => count($settings),
            ])
            ->log('website_settings_updated');
    }

    /**
     * Sanitize script content to prevent XSS attacks.
     * Removes dangerous tags and attributes while preserving legitimate script content.
     */
    private function sanitizeScripts(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        // Remove dangerous tags that could execute malicious code
        $dangerousPatterns = [
            '/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', // Remove all script tags
            '/javascript\s*:/i', // Remove javascript: protocol
            '/on\w+\s*=\s*["\'][^"\']*["\']/i', // Remove event handlers like onclick, onload
            '/expression\s*\([^)]*\)/i', // Remove CSS expression()
            '/url\s*\(\s*["\']?\s*javascript:[^)]*\)/i', // Remove url(javascript:...)
        ];

        foreach ($dangerousPatterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        // If it's meant to be a script, allow only safe script content
        // Strip HTML except for basic formatting and script-safe elements
        $value = strip_tags($value, '<script><div><span><p><br><strong><em><u><a><img><iframe>');

        return Str::limit($value, 100000);
    }
}
