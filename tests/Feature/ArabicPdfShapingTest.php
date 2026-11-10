<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\ArabicPdfShaper;
use Tests\TestCase;

class ArabicPdfShapingTest extends TestCase
{
    public function test_shaper_converts_arabic_characters_to_presentation_glyphs(): void
    {
        $raw = 'فاتورة ضريبية';
        $shaped = ArabicPdfShaper::shapeText($raw);

        // Raw unicode begins with standard FEH (U+0641)
        $this->assertNotSame($raw, $shaped);
        // Shaped string contains Arabic Presentation Forms
        $this->assertMatchesRegularExpression('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFC}]/u', $shaped);
    }

    public function test_shaper_preserves_html_tags_and_style_blocks(): void
    {
        $html = '<!DOCTYPE html><html><head><style>body { font-family: "IBM Plex Sans Arabic"; }</style></head>'
            .'<body><div class="header"><h1>فاتورة مبيعات</h1><p>رقم: <strong>INV-001</strong></p></div></body></html>';

        $shapedHtml = ArabicPdfShaper::shapeHtml($html);

        // Tags and attributes are preserved
        $this->assertStringContainsString('<!DOCTYPE html>', $shapedHtml);
        $this->assertStringContainsString('<style>body { font-family: "IBM Plex Sans Arabic"; }</style>', $shapedHtml);
        $this->assertStringContainsString('<div class="header">', $shapedHtml);
        $this->assertStringContainsString('<strong>INV-001</strong>', $shapedHtml);

        // The text content is shaped
        $this->assertStringNotContainsString('<h1>فاتورة مبيعات</h1>', $shapedHtml);
        $this->assertMatchesRegularExpression('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFC}]/u', $shapedHtml);
    }

    public function test_shaper_handles_non_arabic_text_cleanly(): void
    {
        $html = '<p>Invoice #12345 - Total: 500.00 SAR (2026-11-09)</p>';
        $shapedHtml = ArabicPdfShaper::shapeHtml($html);

        $this->assertSame($html, $shapedHtml);
    }

    public function test_shaper_handles_non_breaking_spaces_in_labels(): void
    {
        $html = '<span>المجموع الفرعي&nbsp;&nbsp;&nbsp;</span>';
        $shapedHtml = ArabicPdfShaper::shapeHtml($html);

        // Does not leave literal &nbsp; inside the shaped text
        $this->assertStringNotContainsString('&nbsp;', $shapedHtml);
        $this->assertMatchesRegularExpression('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFC}]/u', $shapedHtml);
    }

    /**
     * Security: User text containing escaped HTML entities must NEVER be decoded
     * into executable HTML tags (XSS / SSRF / HTML injection prevention).
     */
    public function test_escaped_html_entities_remain_escaped_and_never_become_html_elements(): void
    {
        $input = '<p>وصف &lt;img src=&quot;https://example.invalid/review.png&quot;&gt;</p>';
        $output = ArabicPdfShaper::shapeHtml($input);

        // Crucial security invariant: no active <img> element must be created
        $this->assertStringNotContainsString('<img', $output);
        $this->assertStringContainsString('&lt;img', $output);
    }

    public function test_escaped_scripts_and_styles_in_user_text_remain_escaped(): void
    {
        $input = '<p>ملاحظة &lt;script&gt;alert(1)&lt;/script&gt; &lt;style&gt;body{display:none}&lt;/style&gt;</p>';
        $output = ArabicPdfShaper::shapeHtml($input);

        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringNotContainsString('<style', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringContainsString('&lt;style&gt;', $output);
    }

    public function test_html_comments_are_preserved(): void
    {
        $input = '<div><!-- Important Comment --><span>مرحبا</span></div>';
        $output = ArabicPdfShaper::shapeHtml($input);

        $this->assertStringContainsString('<!-- Important Comment -->', $output);
        $this->assertMatchesRegularExpression('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFC}]/u', $output);
    }
}
