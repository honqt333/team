/**
 * Colour helpers for the tenant public site.
 *
 * The tenant picks their own brand colour. That colour is used for text in
 * a few places — the "main branch" badge, for example — and a tenant can
 * pick a colour that is unreadable on the page background.
 *
 * A measured example: a tenant whose brand colour was `#065f46` (dark
 * green) got a badge at 2.16:1 against the dark page background, which
 * fails WCAG AA (4.5:1) badly.
 *
 * These helpers produce a colour that is guaranteed to clear 4.5:1 against
 * the background it will be drawn on, while keeping the tenant's hue.
 */

/**
 * Parse a `#rgb` / `#rrggbb` colour into its channels.
 *
 * @returns {{r: number, g: number, b: number} | null}
 */
export function parseHex(hex) {
    if (typeof hex !== 'string') return null;

    const clean = hex.trim().replace('#', '');

    if (clean.length === 3) {
        return {
            r: parseInt(clean[0] + clean[0], 16),
            g: parseInt(clean[1] + clean[1], 16),
            b: parseInt(clean[2] + clean[2], 16),
        };
    }

    if (clean.length === 6) {
        return {
            r: parseInt(clean.slice(0, 2), 16),
            g: parseInt(clean.slice(2, 4), 16),
            b: parseInt(clean.slice(4, 6), 16),
        };
    }

    return null;
}

/**
 * The relative luminance of a colour, per WCAG 2.1.
 */
export function relativeLuminance({ r, g, b }) {
    const channel = (value) => {
        const c = value / 255;

        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    };

    return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

/**
 * The contrast ratio between two colours, per WCAG 2.1.
 */
export function contrastRatio(hexA, hexB) {
    const a = parseHex(hexA);
    const b = parseHex(hexB);

    if (!a || !b) return 1;

    const l1 = relativeLuminance(a);
    const l2 = relativeLuminance(b);

    return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
}

function toHex({ r, g, b }) {
    const part = (value) =>
        Math.max(0, Math.min(255, Math.round(value)))
            .toString(16)
            .padStart(2, '0');

    return `#${part(r)}${part(g)}${part(b)}`;
}

function mix(color, target, amount) {
    return {
        r: color.r + (target.r - color.r) * amount,
        g: color.g + (target.g - color.g) * amount,
        b: color.b + (target.b - color.b) * amount,
    };
}

/**
 * Produce a readable variant of a brand colour.
 *
 * The brand colour is mixed toward white (on a dark background) or toward
 * black (on a light background) until it clears the requested contrast
 * ratio. If it already clears it, the colour is returned unchanged — the
 * tenant's exact choice is preserved whenever it is usable.
 *
 * @param {string} hex         The tenant's brand colour.
 * @param {string} background  The background it will be drawn on.
 * @param {number} target      The required contrast ratio (default 4.5).
 */
export function readableVariant(hex, background, target = 4.5) {
    const color = parseHex(hex);

    if (!color) return hex;

    const bg = parseHex(background);

    if (!bg) return hex;

    // Already readable — keep the tenant's exact colour.
    if (contrastRatio(hex, background) >= target) return hex;

    const towards = relativeLuminance(bg) > 0.5 ? { r: 0, g: 0, b: 0 } : { r: 255, g: 255, b: 255 };

    // Binary search on the mix amount: contrast rises monotonically as the
    // colour approaches the target, so 12 iterations is plenty.
    let low = 0;
    let high = 1;
    let best = mix(color, towards, high);

    for (let i = 0; i < 12; i++) {
        const mid = (low + high) / 2;
        const candidate = mix(color, towards, mid);

        if (contrastRatio(toHex(candidate), background) >= target) {
            high = mid;
            best = candidate;
        } else {
            low = mid;
        }
    }

    return toHex(best);
}

/**
 * Darken a colour until WHITE text on top of it clears the contrast ratio.
 *
 * The tenant picks their brand colour, and it is used as the background of
 * the primary call-to-action button — which carries white text. A light
 * brand colour breaks that: a gold `#e5a448` with white text measured
 * 2.15:1, well under the 4.5:1 requirement.
 *
 * The brand colour itself is never changed. This produces a darker variant
 * used only as a button background, so the button stays legible whatever
 * the tenant picks.
 *
 * @param {string} hex         The brand colour.
 * @param {number} target      The required contrast against white (default 4.5).
 */
export function darkenForWhiteText(hex, target = 4.5) {
    const color = parseHex(hex);

    if (!color) return hex;

    // White on white is the worst case; a colour that already passes is
    // returned unchanged.
    if (contrastRatio(hex, '#ffffff') >= target) return hex;

    const black = { r: 0, g: 0, b: 0 };

    let low = 0;
    let high = 1;
    let best = mix(color, black, high);

    for (let i = 0; i < 12; i++) {
        const mid = (low + high) / 2;
        const candidate = mix(color, black, mid);

        if (contrastRatio(toHex(candidate), '#ffffff') >= target) {
            high = mid;
            best = candidate;
        } else {
            low = mid;
        }
    }

    return toHex(best);
}

/**
 * Mix two colours, `amount` being the share of `overlay` (0 - 1).
 *
 * Used to turn the translucent `color-mix(...)` brand tints into SOLID
 * colours. A translucent tint means the effective background depends on
 * whatever is behind it, which makes a contrast guarantee impossible to
 * compute. A solid mix can be measured exactly.
 */
export function mixColors(base, overlay, amount) {
    const a = parseHex(base);
    const b = parseHex(overlay);

    if (!a || !b) return base;

    return toHex(mix(a, b, amount));
}
