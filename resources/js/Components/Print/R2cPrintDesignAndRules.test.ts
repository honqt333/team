import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import ar from '@/i18n/lang/ar.json';
import en from '@/i18n/lang/en.json';
import TemplateDefaultA4 from './PrintTemplates/TemplateDefaultA4.vue';
import TemplateThermal80 from './PrintTemplates/TemplateThermal80.vue';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            tenant: {
                currency: 'SAR',
                print_settings: { visual: { show_currency_symbol: true, currency_display_style: 'text' } },
                tax_settings: { show_amount_before_vat: true },
            },
        },
    }),
    router: { visit: vi.fn() },
}));

function makeI18n(locale = 'ar') {
    return createI18n({
        legacy: false,
        locale,
        messages: { ar, en },
    });
}

const baseData = {
    code: 'INV-R2C-001',
    created_at: '2026-11-15',
    currency_code: 'SAR',
    tax_enabled_snapshot: true,
    pricing_mode_snapshot: 'exclusive',
    total_excl_tax: 200,
    total_tax: 30,
    total_incl_tax: 230,
    total_paid: 100,
    balance: 130,
    qr_code_url: 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
    qr_mandatory: true,
    customer: { name: 'عميل الاختبار', phone: '+966501112233' },
    items: [
        { service_name: 'خدمة متطابقة', qty: 1, unit_price: 100, is_part: false },
        { service_name: 'خدمة متطابقة', qty: 1, unit_price: 100, is_part: false },
    ],
};

describe('R2-C Shared Print Design, Field Rules & Localization', () => {
    it('hides paid and remaining balance on quotation and proforma_invoice in both A4 and Thermal80', () => {
        for (const docType of ['quotation', 'proforma_invoice']) {
            for (const Comp of [TemplateDefaultA4, TemplateThermal80]) {
                const wrapper = mount(Comp, {
                    props: {
                        documentType: docType,
                        data: { ...baseData, qr_mandatory: false, tax_enabled_snapshot: false },
                        centerData: { name: 'مركز الاختبار' },
                        documentSettings: { show_payments: true },
                        visualSettings: { show_logo: false },
                    },
                    global: { plugins: [makeI18n('en')] },
                });
                const text = wrapper.text();
                expect(text).not.toContain('Total Paid:');
                expect(text).not.toContain('Remaining Balance:');
            }
        }
    });

    it('enforces mandatory QR even when show_qr_code is false and hides QR when untaxed', () => {
        for (const Comp of [TemplateDefaultA4, TemplateThermal80]) {
            const taxed = mount(Comp, {
                props: {
                    documentType: 'invoice',
                    data: baseData,
                    centerData: { name: 'مركز الاختبار' },
                    documentSettings: { show_qr_code: false },
                    visualSettings: { show_qr_code: false, show_logo: false },
                },
                global: { plugins: [makeI18n('ar')] },
            });
            expect(taxed.find('img[alt="QR"]').exists()).toBe(true);

            const untaxed = mount(Comp, {
                props: {
                    documentType: 'invoice',
                    data: { ...baseData, tax_enabled_snapshot: false, qr_mandatory: false },
                    centerData: { name: 'مركز الاختبار' },
                    documentSettings: { show_qr_code: true },
                    visualSettings: { show_qr_code: true, show_logo: false },
                },
                global: { plugins: [makeI18n('ar')] },
            });
            expect(untaxed.find('img[alt="QR"]').exists()).toBe(false);
        }
    });

    it('displays missing_snapshots warning on issued documents without fabricating platform identity', () => {
        for (const Comp of [TemplateDefaultA4, TemplateThermal80]) {
            const wrapper = mount(Comp, {
                props: {
                    documentType: 'invoice',
                    data: {
                        ...baseData,
                        status: 'valid',
                        missing_snapshots: ['seller_name_snapshot', 'seller_vat_snapshot'],
                    },
                    centerData: { name: '' },
                    documentSettings: {},
                    visualSettings: { show_logo: false },
                },
                global: { plugins: [makeI18n('en')] },
            });
            const notice = wrapper.find('[data-testid="missing-snapshots-warning"]');
            expect(notice.exists()).toBe(true);
            expect(notice.text()).toContain('seller_name_snapshot');
        }
    });

    it('renders credit note reference invoice and return reason in AR and EN', () => {
        for (const locale of ['ar', 'en']) {
            const wrapper = mount(TemplateDefaultA4, {
                props: {
                    documentType: 'credit_note',
                    data: {
                        ...baseData,
                        original_invoice_number: 'INV-ORIG-99',
                        return_reason: 'Defective part',
                    },
                    centerData: { name: 'Test Center' },
                    documentSettings: {},
                    visualSettings: { show_logo: false },
                },
                global: { plugins: [makeI18n(locale)] },
            });
            expect(wrapper.text()).toContain('INV-ORIG-99');
            expect(wrapper.text()).toContain('Defective part');
        }
    });
});
