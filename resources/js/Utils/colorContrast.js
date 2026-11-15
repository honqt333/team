/**
 * WCAG 2.1 color parsing, relative luminance, and contrast ratio utilities.
 *
 * Used by Customer Portal brand-tinted components (BrandMark, LanguageToggle,
 * CustomerPortalLayout) to guarantee >= 4.5:1 text contrast on custom
 * tenant-primary colors without falling back to approximate brightness checks.
 */

/**
 * Parse a CSS hex (#rgb, #rrggbb, #rrggbbaa) or rgb()/rgba() string into [r, g, b].
 * Returns null if the input cannot be parsed.
 */
export function parseColorRgb(color) {
    if (typeof color !== 'string') return null;
    const trimmed = color.trim();

    if (trimmed.startsWith('#')) {
        const hex = trimmed.slice(1);
        if (hex.length === 3 || hex.length === 4) {
            const r = parseInt(hex[0] + hex[0], 16);
            const g = parseInt(hex[1] + hex[1], 16);
            const b = parseInt(hex[2] + hex[2], 16);
            if ([r, g, b].some(Number.isNaN)) return null;
            return [r, g, b];
        }
        if (hex.length === 6 || hex.length === 8) {
            const r = parseInt(hex.slice(0, 2), 16);
            const g = parseInt(hex.slice(2, 4), 16);
            const b = parseInt(hex.slice(4, 6), 16);
            if ([r, g, b].some(Number.isNaN)) return null;
            return [r, g, b];
        }
        return null;
    }

    const rgbMatch = trimmed.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
    if (rgbMatch) {
        return [
            Math.min(255, Math.max(0, Number(rgbMatch[1]))),
            Math.min(255, Math.max(0, Number(rgbMatch[2]))),
            Math.min(255, Math.max(0, Number(rgbMatch[3]))),
        ];
    }

    return null;
}

/**
 * Compute WCAG 2.1 relative luminance for an [r, g, b] tuple (0..255).
 */
export function relativeLuminance([r, g, b]) {
    const [rs, gs, bs] = [r, g, b].map((channel) => {
        const c = channel / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

/**
 * Compute WCAG 2.1 contrast ratio between two [r, g, b] colors (1..21).
 */
export function contrastRatio(rgbA, rgbB) {
    const l1 = relativeLuminance(rgbA);
    const l2 = relativeLuminance(rgbB);
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Choose between lightText and darkText based on actual WCAG 2.1 contrast ratio
 * against the given background color.
 */
export function accessibleTextOn(bgColor, lightText = '#ffffff', darkText = '#0f172a') {
    const bgRgb = parseColorRgb(bgColor);
    if (!bgRgb) return lightText;

    const lightRgb = parseColorRgb(lightText) || [255, 255, 255];
    const darkRgb = parseColorRgb(darkText) || [15, 23, 42];

    const lightContrast = contrastRatio(lightRgb, bgRgb);
    const darkContrast = contrastRatio(darkRgb, bgRgb);

    return darkContrast > lightContrast ? darkText : lightText;
}
