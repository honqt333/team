<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Domain-realistic local scorer used when no real provider is configured
 * (or when an explicit `mock` provider is requested for a determinism
 * guarantee in tests).
 *
 * Approach:
 *   - Tokenize the complaint and each catalog item's bilingual text.
 *   - For each item, score the **token overlap** (bidirectional) between
 *     the complaint tokens and the item tokens, normalized by the larger
 *     token set so a single very long match does not skew short ones.
 *   - Clamp confidence into [0.1, 0.99].
 *   - Stable sort by (confidence desc, then preserved order) so the first
 *     suggestion wins ties deterministically.
 *
 * This scorer is intentionally local and pure — no HTTP, no DB, no
 * facade. That makes it trivial to unit-test and safe to call from
 * `WorkOrderSuggestionService` when the provider resolves to
 * `MockProvider` for the suggester feature.
 *
 * Spec: docs/features/ai-service-suggester/design.md §7.
 */
class MockSuggester
{
    /**
     * Generate ranked suggestions for the given complaint.
     *
     * @param Collection<int, array{item_type: string, item_id: int, name_ar: string, name_en: string, description_ar?: string, description_en?: string}> $catalog
     * @return array<int, array{item_type: string, item_id: int, name: string, name_en?: string, reason: string, confidence: float, qty: int}>
     */
    public function suggestForComplaint(
        string $complaint,
        Collection $catalog,
        int $limit,
        string $locale,
    ): array {
        $effectiveLimit = max(4, $limit);
        $complaintTokens = $this->tokenize($complaint);

        if ($complaintTokens === [] || $catalog->isEmpty()) {
            return [];
        }

        $scored = $catalog
            ->map(function (array $item) use ($complaintTokens) {
                $itemTokens = $this->tokenize(implode(' ', array_filter([
                    (string) ($item['name_ar'] ?? ''),
                    (string) ($item['name_en'] ?? ''),
                    (string) ($item['description_ar'] ?? ''),
                    (string) ($item['description_en'] ?? ''),
                ])));

                $score = $this->score($complaintTokens, $itemTokens);
                $confidence = $this->confidence($score);

                return [
                    'item' => $item,
                    'score' => $score,
                    'confidence' => $confidence,
                    'matched_tokens' => $this->matchedTokens($complaintTokens, $itemTokens),
                ];
            })
            // Sort by confidence desc, preserving original catalog order
            // on ties (Eloquent's map + sort desc is stable).
            ->sortByDesc(function (array $row) {
                return $row['confidence'];
            })
            ->values()
            ->take($effectiveLimit);

        $results = [];

        foreach ($scored as $row) {
            /** @var array<string, mixed> $item */
            $item = $row['item'];

            $localizedName = $locale === 'en'
                ? ((string) ($item['name_en'] ?? '') ?: (string) ($item['name_ar'] ?? ''))
                : ((string) ($item['name_ar'] ?? '') ?: (string) ($item['name_en'] ?? ''));

            $results[] = [
                'item_type' => (string) ($item['item_type'] ?? 'service'),
                'item_id' => (int) ($item['item_id'] ?? 0),
                'name' => $localizedName,
                'name_en' => isset($item['name_en']) ? (string) $item['name_en'] : null,
                'reason' => $this->reason($row['matched_tokens'], $row['confidence']),
                'confidence' => $row['confidence'],
                'qty' => $this->initialQty((string) ($item['item_type'] ?? 'service'), $row['confidence']),
            ];
        }

        return $results;
    }

    /**
     * Normalize a body of text into a set of lowercase tokens.
     *
     * Handles Arabic by dropping diacritics and using
     * `mb_strtolower` so casing is consistent across scripts.
     *
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $normalized = Str::lower(trim($text));

        if ($normalized === '') {
            return [];
        }

        // Drop Arabic diacritics (U+064B..U+0652, U+0670, U+0640) so
        // 'فَحْص' matches 'فحص'.
        $normalized = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $normalized) ?? $normalized;

        // Split on non-letter/digit characters; Unicode-aware.
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $normalized) ?: [];

        $tokens = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $tokens[] = (string) $part;
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Score a candidate item against a complaint.
     *
     * Uses Szymkiewicz–Simpson-style overlap over the smaller set,
     * normalized by the larger of the two — gives a value in [0, 1].
     *
     * @param array<int, string> $complaintTokens
     * @param array<int, string> $itemTokens
     */
    private function score(array $complaintTokens, array $itemTokens): float
    {
        if ($complaintTokens === [] || $itemTokens === []) {
            return 0.0;
        }

        $complaintSet = array_flip($complaintTokens);
        $matched = 0;

        foreach ($itemTokens as $token) {
            if (isset($complaintSet[$token])) {
                $matched++;
            }
        }

        $denominator = max(count($complaintTokens), count($itemTokens));

        return $denominator === 0 ? 0.0 : $matched / $denominator;
    }

    /**
     * @param array<int, string> $complaintTokens
     * @param array<int, string> $itemTokens
     * @return array<int, string>
     */
    private function matchedTokens(array $complaintTokens, array $itemTokens): array
    {
        $complaintSet = array_flip($complaintTokens);
        $hits = [];

        foreach ($itemTokens as $token) {
            if (isset($complaintSet[$token])) {
                $hits[] = $token;
            }
        }

        return $hits;
    }

    /**
     * Clamp raw overlap score to a confidence in [0.1, 0.99].
     *
     * As specified in the contract: `confidence = min(0.99, max(0.1, $score))`.
     */
    private function confidence(float $score): float
    {
        return min(0.99, max(0.10, $score));
    }

    /**
     * Build a short human-readable reason explaining why the item
     * matched. Bilingual — falls back to English for an `en` locale.
     *
     * @param array<int, string> $matched
     */
    private function reason(array $matched, float $confidence): string
    {
        if ($matched === []) {
            return 'اقتراح فحص وقائي بناءً على شكوى العميل.'; // preventive inspection fallback
        }

        $tier = $this->confidenceTier($confidence);
        $preview = implode('، ', array_slice($matched, 0, 3)); // Arabic comma

        $template = match ($tier) {
            'high' => 'تطابق قوي مع شكوى العميل: '.$preview,
            'medium' => 'تطابق متوسط مع شكوى العميل: '.$preview,
            default => 'تطابق منخفض مع شكوى العميل: '.$preview,
        };

        return mb_substr($template, 0, 240);
    }

    private function confidenceTier(float $confidence): string
    {
        if ($confidence >= 0.7) {
            return 'high';
        }

        if ($confidence >= 0.4) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Initial qty heuristic — parts tend to come in 1..2, services in 1.
     */
    private function initialQty(string $itemType, float $confidence): int
    {
        if ($itemType === 'part' && $confidence >= 0.4) {
            return 2;
        }

        return 1;
    }
}
