import { describe, expect, it } from 'vitest';
import {
    accessibleTextOn,
    contrastRatio,
    parseColorRgb,
    relativeLuminance,
} from './colorContrast';

describe('colorContrast WCAG utilities', () => {
    it('parses short hex, full hex, and rgb strings', () => {
        expect(parseColorRgb('#fff')).toEqual([255, 255, 255]);
        expect(parseColorRgb('#2563eb')).toEqual([37, 99, 235]);
        expect(parseColorRgb('rgb(253, 224, 71)')).toEqual([253, 224, 71]);
        expect(parseColorRgb('invalid')).toBeNull();
    });

    it('computes accurate WCAG relative luminance and contrast ratio', () => {
        expect(relativeLuminance([0, 0, 0])).toBe(0);
        expect(relativeLuminance([255, 255, 255])).toBeCloseTo(1, 5);
        expect(contrastRatio([255, 255, 255], [0, 0, 0])).toBeCloseTo(21, 2);
        expect(contrastRatio([255, 255, 255], [37, 99, 235])).toBeGreaterThan(4.5);
    });

    it('picks dark text on light tenant-primary colors and white text on dark colors', () => {
        expect(accessibleTextOn('#fde047')).toBe('#0f172a');
        expect(accessibleTextOn('#e2e8f0')).toBe('#0f172a');
        expect(accessibleTextOn('#7dd3fc')).toBe('#0f172a');
        expect(accessibleTextOn('#2563eb')).toBe('#ffffff');
        expect(accessibleTextOn('#0f172a')).toBe('#ffffff');
    });
});
