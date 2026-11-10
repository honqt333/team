<?php

declare(strict_types=1);
use App\Support\ArabicPdfShaper;
use Tests\TestCase;

class ArabicShaperSecurityReviewTest extends TestCase
{
    public function test_escaped_user_text_becomes_an_active_image_element(): void
    {
        $input = '<p>وصف &lt;img src=&quot;https://example.invalid/review.png&quot;&gt;</p>';
        $output = ArabicPdfShaper::shapeHtml($input);
        $this->assertStringNotContainsString('<img', $input);
        $this->assertStringContainsString('<img src="https://example.invalid/review.png">', $output);
    }
}
