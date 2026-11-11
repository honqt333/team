import { describe, expect, it, vi } from 'vitest';
import { mount, shallowMount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import TemplateDefaultA4 from './PrintTemplates/TemplateDefaultA4.vue';
import TemplateThermal80 from './PrintTemplates/TemplateThermal80.vue';
import PrintLayout from './PrintLayout.vue';
import PrintMoney from './PrintMoney.vue';
import InvoicePrint from '@/Pages/Invoices/Print.vue';
import { resolveDocumentSettings, resolveVisualSettings } from '@/Composables/usePrint';

const mockPageProps = {
    props: {
        tenant: {
            name: 'مركز تجربة',
            iban: 'SA0380000000608010167519',
            vat_number: '300000000000003',
            cr_number: '1010101010',
            tax_settings: { enabled: true, vat_enabled: true },
            print_settings: {
                visual: {
                    active_template: 'TemplateDefaultA4',
                    show_logo: false,
                    primary_color: '#dc2626',
                    footer_text: 'شكراً لتعاملكم معنا',
                },
                documents: {
                    invoice: {
                        template: 'TemplateModernA4',
                        title_ar: 'فاتورة مخصصة',
                        title_en: 'Custom Invoice',
                        terms: [{ text_ar: 'شرط مخصص أول', text_en: 'Custom term 1' }],
                        print_terms: true,
                        terms_first_page: true,
                        show_stamp: true,
                        show_qr_code: true,
                        show_iban: true,
                        show_customer_address: false,
                        signatures: [
                            { name_ar: 'توقيع المشرف', name_en: 'Supervisor', show: true },
                        ],
                    },
                    credit_note: {
                        template: 'TemplateThermal80',
                        title_ar: 'إشعار دائن مخصص',
                        title_en: 'Custom Credit Note',
                        show_qr_code: false,
                        show_iban: false,
                        show_stamp: false,
                        print_terms: false,
                        show_customer_address: true,
                    },
                },
            },
        },
    },
};

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mockPageProps,
    router: { visit: vi.fn() },
}));

const i18nPlugin = createI18n({
    legacy: false,
    locale: 'ar',
    missingWarn: false,
    fallbackWarn: false,
    messages: {
        ar: {
            common: {
                app_name: 'خدمة برو',
                code: 'الرمز',
                name: 'الاسم',
                phone: 'الهاتف',
                address: 'العنوان',
                vat: 'الضريبة',
                print: 'طباعة',
                close: 'إغلاق',
                back: 'رجوع',
                km: 'كم',
                discount: 'خصم',
            },
            company_profile: {
                profile: {
                    vat_number: 'الرقم الضريبي',
                    cr_number: 'السجل التجاري',
                    iban: 'رقم الآيبان',
                },
            },
            work_orders: {
                print_view: {
                    date: 'التاريخ',
                    customer: 'العميل',
                    vehicle: 'المركبة',
                    plate: 'اللوحة',
                    color: 'اللون',
                    odometer: 'العداد',
                    fuel_level: 'الوقود',
                    terms_conditions: 'الشروط والأحكام',
                    labor_services: 'الخدمات والأجور',
                    spare_parts: 'قطع الغيار',
                    calculation: 'الحساب',
                    technician: 'الفني',
                },
            },
            vehicles: { form: { make: 'الشركة' } },
            quotes: {
                form: { subtotal: 'المجموع الفرعي', total_discount: 'إجمالي الخصم' },
                show: { initial_assessment: 'الفحص المبدئي' },
            },
        },
    },
});

const baseData = {
    code: 'INV-1001',
    created_at: '2026-11-11',
    qr_code_url: 'https://example.com/qr.png',
    tax_enabled_snapshot: true,
    total_excl_tax: 100,
    total_tax: 15,
    total_incl_tax: 115,
    total_paid: 0,
    balance: 115,
    customer: {
        name: 'خالد',
        phone: '0500000000',
        address: 'الرياض - حي العليا',
        tax_number: '311111111111113',
    },
    vehicle: { make: 'تويوتا', plate: 'ABD 1234', color: 'white' },
    items: [{ service_name: 'صيانة دورية', qty: 1, unit_price: 100, is_part: false }],
};

const baseCenter = {
    name: 'مركز الصيانة الرئيسي',
    tax_number: '300000000000003',
    cr_number: '1010101010',
    phone: '0555555555',
    logo: '/storage/branch-logo.png',
    iban: 'SA0380000000608010167519',
    stamp_url: '/storage/stamp.png',
};

describe('Print settings reflection across unified print engine', () => {
    it('resolves all document and visual settings without dropping fields', () => {
        const docSettings = resolveDocumentSettings({
            tenantSettings: mockPageProps.props.tenant.print_settings,
            documentType: 'invoice',
        });
        const visSettings = resolveVisualSettings({
            tenantSettings: mockPageProps.props.tenant.print_settings,
            center: baseCenter,
        });

        expect(docSettings).toMatchObject({
            template: 'TemplateModernA4',
            title_ar: 'فاتورة مخصصة',
            title_en: 'Custom Invoice',
            print_terms: true,
            terms_first_page: true,
            show_stamp: true,
            show_qr_code: true,
            show_iban: true,
            show_customer_address: false,
        });
        expect(docSettings.terms).toHaveLength(1);
        expect(visSettings).toMatchObject({
            active_template: 'TemplateDefaultA4',
            show_logo: false,
            primary_color: '#dc2626',
            footer_text: 'شكراً لتعاملكم معنا',
            stamp_url: '/storage/stamp.png',
        });
    });

    it('renders all print settings on TemplateDefaultA4 including per-doc modern style, QR, IBAN, stamp, logo toggle, footer, and terms_first_page', () => {
        const docSettings = resolveDocumentSettings({
            tenantSettings: mockPageProps.props.tenant.print_settings,
            documentType: 'invoice',
        });
        const visSettings = resolveVisualSettings({
            tenantSettings: mockPageProps.props.tenant.print_settings,
            center: baseCenter,
        });

        const wrapper = mount(TemplateDefaultA4, {
            global: { plugins: [i18nPlugin] },
            props: {
                documentType: 'invoice',
                data: baseData,
                centerData: baseCenter,
                documentSettings: docSettings,
                visualSettings: visSettings,
            },
        });

        const html = wrapper.html();
        expect(html).toContain('فاتورة مخصصة');
        expect(html).toContain('SA0380000000608010167519'); // show_iban
        expect(html).toContain('https://example.com/qr.png'); // show_qr_code
        expect(html).toContain('/storage/stamp.png'); // show_stamp
        expect(html).toContain('شكراً لتعاملكم معنا'); // footer_text
        expect(html).toContain('شرط مخصص أول'); // terms
        expect(html).toContain('توقيع المشرف'); // signatures
        expect(html).not.toContain('الرياض - حي العليا'); // show_customer_address: false
        expect(html).not.toContain('/storage/branch-logo.png'); // show_logo: false
        expect(html).toContain('rounded-full'); // TemplateModernA4 active via documentSettings.template

        // Tax disabled -> QR code must NOT render even if qr_code_url is present
        const nonTaxWrapper = mount(TemplateDefaultA4, {
            global: { plugins: [i18nPlugin] },
            props: {
                documentType: 'invoice',
                data: { ...baseData, tax_enabled_snapshot: false, total_tax: 0 },
                centerData: baseCenter,
                documentSettings: docSettings,
                visualSettings: visSettings,
            },
        });
        expect(nonTaxWrapper.html()).not.toContain('https://example.com/qr.png');
        expect(nonTaxWrapper.html()).not.toContain('[QR]');
        nonTaxWrapper.unmount();

        wrapper.unmount();
    });

    it('renders custom title, QR, IBAN, footer, and sleek style on TemplateThermal80', () => {
        const docSettings = resolveDocumentSettings({
            tenantSettings: mockPageProps.props.tenant.print_settings,
            documentType: 'invoice',
        });
        docSettings.template = 'TemplateSleekThermal';
        const visSettings = resolveVisualSettings({
            tenantSettings: mockPageProps.props.tenant.print_settings,
            center: baseCenter,
        });

        const wrapper = mount(TemplateThermal80, {
            global: { plugins: [i18nPlugin] },
            props: {
                documentType: 'invoice',
                data: baseData,
                centerData: baseCenter,
                documentSettings: docSettings,
                visualSettings: visSettings,
            },
        });

        const html = wrapper.html();
        expect(html).toContain('فاتورة مخصصة');
        expect(html).toContain('SA0380000000608010167519');
        expect(html).toContain('https://example.com/qr.png');
        expect(html).toContain('شكراً لتعاملكم معنا');
        expect(html).toContain('border-slate-900'); // TemplateSleekThermal active via documentSettings.template

        // Tax disabled -> QR code must NOT render on Thermal80
        const nonTaxThermal = mount(TemplateThermal80, {
            global: { plugins: [i18nPlugin] },
            props: {
                documentType: 'invoice',
                data: { ...baseData, tax_enabled_snapshot: false, total_tax: 0 },
                centerData: baseCenter,
                documentSettings: docSettings,
                visualSettings: visSettings,
            },
        });
        expect(nonTaxThermal.html()).not.toContain('https://example.com/qr.png');
        nonTaxThermal.unmount();

        wrapper.unmount();
    });

    it('passes credit_note print settings and omits QR when tax is disabled in Invoices/Print.vue', () => {
        const wrapper = shallowMount(InvoicePrint, {
            global: {
                plugins: [i18nPlugin],
                mocks: { route: () => '/app/invoices/55' },
            },
            props: {
                invoice: {
                    id: 55,
                    type: 'credit_note',
                    invoice_number: 'CN-001',
                    center_id: 3,
                    tax_enabled_snapshot: true,
                    zatca_qr_tlv: 'AQI=',
                    center: {
                        name_ar: 'فرع الرياض',
                        logo_invoice_url: '/storage/center-invoice-logo.png',
                    },
                    tenant: mockPageProps.props.tenant,
                },
            },
        });

        const layout = wrapper.getComponent(PrintLayout);
        expect(layout.props('documentType')).toBe('credit_note');
        expect(layout.props('centerData').logo).toBe('/storage/center-invoice-logo.png');
        expect(layout.props('data').qr_code_url).toContain('AQI%3D');
        expect(layout.props('documentSettings')).toMatchObject({
            template: 'TemplateThermal80',
            title_ar: 'إشعار دائن مخصص',
            show_qr_code: false,
            show_iban: false,
            show_stamp: false,
            print_terms: false,
            show_customer_address: true,
        });
        wrapper.unmount();

        const nonTaxPrint = shallowMount(InvoicePrint, {
            global: {
                plugins: [i18nPlugin],
                mocks: { route: () => '/app/invoices/56' },
            },
            props: {
                invoice: {
                    id: 56,
                    type: 'invoice',
                    invoice_number: 'INV-002',
                    center_id: 3,
                    tax_enabled_snapshot: false,
                    zatca_qr_tlv: 'AQI=',
                    tenant: mockPageProps.props.tenant,
                },
            },
        });
        expect(nonTaxPrint.getComponent(PrintLayout).props('data').qr_code_url).toBe('');
        nonTaxPrint.unmount();
    });

    it('injects dynamic @page margins with generous top margin for A4 and switches to 80mm for thermal', async () => {
        const wrapper = shallowMount(PrintLayout, {
            global: { plugins: [i18nPlugin] },
            props: {
                documentType: 'invoice',
                data: baseData,
                documentSettings: { template: 'TemplateDefaultA4' },
                visualSettings: { active_template: 'TemplateDefaultA4' },
            },
        });

        const styleEl = document.head.querySelector('style[data-print-layout-page="true"]');
        expect(styleEl).not.toBeNull();
        expect(styleEl?.textContent).toContain('size: A4');
        expect(styleEl?.textContent).toContain('margin: 15mm 10mm 12mm 10mm');
        expect(wrapper.classes()).toContain('print:block');

        await wrapper.setProps({
            documentSettings: { template: 'TemplateThermal80' },
        });
        expect(styleEl?.textContent).toContain('size: 80mm auto');
        expect(styleEl?.textContent).toContain('margin: 4mm 3mm');

        wrapper.unmount();
        expect(document.head.querySelector('style[data-print-layout-page="true"]')).toBeNull();
    });

    it('renders PrintMoney governed by show_currency_symbol, currency_display_style, and tenant currency', async () => {
        const sarSvg = mount(PrintMoney, {
            global: { plugins: [i18nPlugin] },
            props: {
                amount: 150,
                currencyCode: 'SAR',
                visualSettings: { show_currency_symbol: true, currency_display_style: 'symbol' },
            },
        });
        expect(sarSvg.text()).toContain('150.00');
        expect(sarSvg.find('svg[aria-label="SAR"]').exists()).toBe(true);
        sarSvg.unmount();

        const sarText = mount(PrintMoney, {
            global: { plugins: [i18nPlugin] },
            props: {
                amount: 150,
                currencyCode: 'SAR',
                visualSettings: { show_currency_symbol: true, currency_display_style: 'text' },
            },
        });
        expect(sarText.text()).toContain('150.00');
        expect(sarText.text()).toContain('ر.س');
        expect(sarText.find('svg').exists()).toBe(false);
        sarText.unmount();

        const kwdMoney = mount(PrintMoney, {
            global: { plugins: [i18nPlugin] },
            props: {
                amount: 12.5,
                currencyCode: 'KWD',
                visualSettings: { show_currency_symbol: true, currency_display_style: 'symbol' },
            },
        });
        expect(kwdMoney.text()).toContain('12.500');
        expect(kwdMoney.text()).toContain('د.ك');
        kwdMoney.unmount();

        const hiddenSymbol = mount(PrintMoney, {
            global: { plugins: [i18nPlugin] },
            props: {
                amount: 250,
                currencyCode: 'AED',
                visualSettings: { show_currency_symbol: false, currency_display_style: 'symbol' },
            },
        });
        expect(hiddenSymbol.text()).toContain('250.00');
        expect(hiddenSymbol.text()).not.toContain('د.إ');
        expect(hiddenSymbol.text()).not.toContain('AED');
        hiddenSymbol.unmount();
    });
});
