import { describe, it, expect } from 'vitest';
import { buildPrintSignatures, isCustomerSignature } from './printSignatures';

/**
 * The customer signature carries a `show` flag in the document
 * settings: the workshop decides whether it is printed. The two pages
 * that build the print payload copied the saved row and then forced
 * `show: true` on it, so the saved choice never reached the template
 * and a hidden signature was printed anyway.
 */
describe('buildPrintSignatures', () => {
    const settingsWith = (signatures) => ({ signatures });

    it('keeps the saved "hidden" choice of the customer signature', () => {
        const list = buildPrintSignatures(
            settingsWith([
                {
                    name_ar: 'توقيع المستشار الفني',
                    name_en: 'Service Advisor Signature',
                    show: true,
                },
                { name_ar: 'توقيع العميل للاعتماد', name_en: 'Customer Signature', show: false },
            ]),
            '/storage/signatures/quotes/7.png'
        );

        const customer = list.find((s) => isCustomerSignature(s));

        expect(customer.show).toBe(false);
    });

    it('still replaces the URL of the customer signature with the one that was given', () => {
        const list = buildPrintSignatures(
            settingsWith([
                {
                    name_ar: 'توقيع العميل للاعتماد',
                    name_en: 'Customer Signature',
                    show: false,
                    url: '/old.png',
                },
            ]),
            '/storage/signatures/quotes/7.png'
        );

        const customer = list.find((s) => isCustomerSignature(s));

        expect(customer.url).toBe('/storage/signatures/quotes/7.png');
        expect(customer.show).toBe(false);
    });

    it('leaves the other rows exactly as they are', () => {
        const advisor = {
            name_ar: 'توقيع المستشار الفني',
            name_en: 'Service Advisor Signature',
            show: true,
        };

        // No customer signature: no customer row is added.
        const list = buildPrintSignatures(settingsWith([advisor]), null);

        expect(list[0]).toEqual(advisor);
        expect(list).toHaveLength(1);
    });

    it('falls back to the two default rows when nothing was configured', () => {
        const list = buildPrintSignatures({}, null);

        expect(list).toHaveLength(2);
        expect(list.every((s) => s.show === true)).toBe(true);
    });

    it('adds a customer row when the customer signed but no row was configured', () => {
        const list = buildPrintSignatures(
            settingsWith([
                {
                    name_ar: 'توقيع المستشار الفني',
                    name_en: 'Service Advisor Signature',
                    show: true,
                },
            ]),
            '/storage/signatures/quotes/7.png'
        );

        const customer = list.find((s) => isCustomerSignature(s));

        expect(customer).toBeDefined();
        expect(customer.url).toBe('/storage/signatures/quotes/7.png');
        expect(customer.show).toBe(true);
    });

    it('recognises the customer row by its Arabic and its English name', () => {
        expect(isCustomerSignature({ name_ar: 'توقيع العميل للاعتماد' })).toBe(true);
        expect(isCustomerSignature({ name_en: 'Customer Signature' })).toBe(true);
        expect(isCustomerSignature({ name_en: 'Service Advisor Signature' })).toBe(false);
    });
});
