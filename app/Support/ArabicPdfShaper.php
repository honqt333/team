<?php

declare(strict_types=1);

namespace App\Support;

use ArPHP\I18N\Arabic;

class ArabicPdfShaper
{
    private static ?Arabic $arabic = null;

    public static function getArabic(): Arabic
    {
        if (self::$arabic === null) {
            self::$arabic = new Arabic;
        }

        return self::$arabic;
    }

    /**
     * Shape Arabic text for PDF rendering with DomPDF.
     *
     * Decodes HTML entities for proper character recognition, shapes the Arabic
     * cursive glyphs, and strictly re-escapes HTML special characters (&, <, >)
     * so that user input containing escaped tags NEVER becomes executable HTML markup.
     */
    public static function shapeText(string $text): string
    {
        if (! preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace(["\xc2\xa0", '&nbsp;'], ' ', $decoded);

        // max_chars = 1000 (rely on HTML/CSS flow wrapping), hindo = false (keep Western numerals)
        $shaped = (string) @self::getArabic()->utf8Glyphs($decoded, 1000, false);

        return htmlspecialchars($shaped, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Walk HTML and shape Arabic within all text nodes.
     *
     * Preserves <style>, <script>, and HTML comment blocks untouched,
     * while safely shaping text nodes between '>' and '<'.
     */
    public static function shapeHtml(string $html): string
    {
        // Preserve comments, styles, and scripts
        $blocks = [];
        $htmlWithPlaceholders = preg_replace_callback(
            '#<!--.*?-->|<(style|script)[^>]*>.*?</\1>#si',
            function ($m) use (&$blocks) {
                $placeholder = '<!--__PRESERVED_BLOCK_'.count($blocks).'__-->';
                $blocks[$placeholder] = $m[0];

                return $placeholder;
            },
            $html
        ) ?? $html;

        // Shape all text nodes between > and <
        $shapedHtml = preg_replace_callback('/>([^<]+)</u', function ($matches) {
            $text = $matches[1];

            if (! preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
                return $matches[0];
            }

            $shaped = self::shapeText($text);

            return '>'.$shaped.'<';
        }, $htmlWithPlaceholders) ?? $htmlWithPlaceholders;

        // Restore preserved style, script, and comment blocks
        if (! empty($blocks)) {
            $shapedHtml = strtr($shapedHtml, $blocks);
        }

        return $shapedHtml;
    }
}
