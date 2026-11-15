import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePrintTotals } from '@/Composables/usePrintTotals';
import TemplateDefaultA4 from '@/Components/Print/PrintTemplates/TemplateDefaultA4.vue';
import TemplateThermal80 from '@/Components/Print/PrintTemplates/TemplateThermal80.vue';
import Print from '@/Pages/Invoices/Print.vue';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { tenant: { tax_settings: { vat_enabled: true } } } }),
    router: { visit: vi.fn() },
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ locale: { value: 'ar' }, t: (k: string) => k }),
}));

const globalConfig = {
    mocks: {
        $t: (key: string) => key,
    },
};

describe('R2-B2 normalizeItems & local QR governance', () => {
    const { normalizeItems, calculateTotals } = usePrintTotals();

    it('preserves two legitimate identical items without id in the same array', () => {
        const line1 = {
            service_name: 'تغيير زيت',
            qty: 1,
            unit_price: 100,
            is_part: false,
            line_total_incl_tax: 115,
        };
        const line2 = {
            service_name: 'تغيير زيت',
            qty: 1,
            unit_price: 100,
            is_part: false,
            line_total_incl_tax: 115,
        };

        const result = normalizeItems({ items: [line1, line2] });
        expect(result).toHaveLength(2);
    });

    it('deduplicates overlapping items across items and parts while preserving order and identical un-id items', () => {
        const dupA = {
            service_name: 'فلتر زيت',
            qty: 1,
            unit_price: 50,
            is_part: true,
            line_total_incl_tax: 57.5,
        };
        const dupB = { ...dupA };
        const extraPart = {
            id: 99,
            service_name: 'فلتر هواء',
            qty: 1,
            unit_price: 80,
            is_part: true,
            line_total_incl_tax: 92,
        };

        const result = normalizeItems({
            items: [dupA, dupB],
            parts: [dupA, extraPart],
        });

        expect(result).toHaveLength(3);
        expect(result[0].service_name).toBe('فلتر زيت');
        expect(result[1].service_name).toBe('فلتر زيت');
        expect(result[2].service_name).toBe('فلتر هواء');
    });

    it('never recalculates or mutates stored database totals', () => {
        const totals = calculateTotals(
            [{ service_name: 'X', qty: 1, unit_price: 10, line_total_incl_tax: 11.5 }],
            {
                tax_enabled_snapshot: true,
                pricing_mode_snapshot: 'exclusive',
                total_excl_tax: 200,
                total_tax: 30,
                total_incl_tax: 230,
                total_paid: 100,
                balance: 130,
            }
        );
        expect(totals.total).toBe(230);
        expect(totals.vat).toBe(30);
        expect(totals.paid).toBe(100);
        expect(totals.balance).toBe(130);
    });

    it('enforces mandatory ZATCA QR even when template preference show_qr_code is false, while respecting preference when not mandatory', () => {
        const svgQr = 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=';
        const baseData = {
            code: 'INV-QR-1',
            currency_code: 'SAR',
            tax_enabled_snapshot: true,
            qr_code_url: svgQr,
            items: [
                {
                    id: 1,
                    service_name: 'خدمة',
                    qty: 1,
                    unit_price: 100,
                    is_part: false,
                    is_taxable: true,
                    tax_rate_snapshot: 15,
                    line_total_excl_tax: 100,
                    line_total_incl_tax: 115,
                },
            ],
        };

        // Mandatory QR stays visible even if show_qr_code is false
        const a4Mandatory = mount(TemplateDefaultA4, {
            props: {
                documentType: 'invoice',
                previewMode: false,
                data: { ...baseData, qr_mandatory: true },
                centerData: { name: 'الفرع' },
                documentSettings: { show_qr_code: false },
                visualSettings: { show_logo: false, show_qr_code: false },
            },
            global: globalConfig,
        });
        expect(a4Mandatory.html()).toContain(svgQr);

        const thermalMandatory = mount(TemplateThermal80, {
            props: {
                documentType: 'invoice',
                previewMode: false,
                data: { ...baseData, qr_mandatory: true },
                centerData: { name: 'الفرع' },
                documentSettings: { show_qr_code: false },
                visualSettings: { show_logo: false, show_qr_code: false },
            },
            global: globalConfig,
        });
        expect(thermalMandatory.html()).toContain(svgQr);

        // Non-mandatory QR respects show_qr_code: false
        const a4OptionalHidden = mount(TemplateDefaultA4, {
            props: {
                documentType: 'quotation',
                previewMode: false,
                data: { ...baseData, qr_mandatory: false },
                centerData: { name: 'الفرع' },
                documentSettings: { show_qr_code: false },
                visualSettings: { show_logo: false, show_qr_code: false },
            },
            global: globalConfig,
        });
        expect(a4OptionalHidden.html()).not.toContain(svgQr);
    });

    it('Invoices/Print.vue uses printDocument DTO and local SVG QR without qrserver.com', () => {
        const wrapper = mount(Print, {
            props: {
                invoice: { id: 1, invoice_number: 'INV-DTO-1', tenant: { print_settings: {} } },
                printDocument: {
                    document_type: 'parts_invoice',
                    code: 'INV-DTO-1',
                    issue_date: '2026-11-15',
                    currency_code: 'SAR',
                    seller: {
                        name: 'الفرع المجمد',
                        name_ar: 'الفرع المجمد',
                        name_en: 'Frozen Branch',
                        vat_number: '310000000000001',
                        cr_number: '1010111222',
                        is_company: false,
                    },
                    party: { name: 'عميل' },
                    vehicle: null,
                    items: [
                        {
                            id: 1,
                            service_name: 'بند موحد',
                            qty: 1,
                            unit_price: 100,
                            discount: 0,
                            is_part: true,
                            is_taxable: true,
                            tax_rate_snapshot: 15,
                            line_total_excl_tax: 100,
                            line_total_incl_tax: 115,
                        },
                    ],
                    totals: {
                        tax_enabled: true,
                        pricing_mode: 'exclusive',
                        subtotal_excl_tax: 100,
                        total_tax: 15,
                        grand_total_incl_tax: 115,
                        total_paid: 115,
                        bad_debt: 0,
                        balance: 0,
                    },
                    qr_data_uri: 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
                    qr_mandatory: true,
                },
            },
            global: {
                mocks: {
                    $t: (k: string) => k,
                    route: () => '/invoices/1',
                },
            },
        });

        const html = wrapper.html();
        expect(html).toContain('الفرع المجمد');
        expect(html).toContain('بند موحد');
        expect(html).toContain('data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=');
        expect(html).not.toContain('qrserver.com');
    });
});
