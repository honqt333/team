import { describe, expect, it, vi } from 'vitest';
import { mount, shallowMount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import TemplateDefaultA4 from './PrintTemplates/TemplateDefaultA4.vue';
import TemplateThermal80 from './PrintTemplates/TemplateThermal80.vue';
import PrintLayout from './PrintLayout.vue';
import PrintMoney from './PrintMoney.vue';
import ReturnPrint from '@/Pages/Purchasing/Invoices/ReturnPrint.vue';
import PortalInvoiceShow from '@/Pages/Public/Portal/Invoices/Show.vue';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            tenant: {
                name: 'مركز الاختبار',
                vat_number: '300000000000003',
                cr_number: '1010101010',
                tax_settings: {
                    enabled: true,
                    vat_enabled: true,
                    show_amount_before_vat: true,
                },
            },
        },
    }),
    router: { visit: vi.fn() },
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
}));

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal<typeof import('vue-i18n')>();
    return {
        ...actual,
        useI18n: () => ({
            t: (key: string) => key,
            locale: { value: 'ar' },
        }),
    };
});

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                name: 'الاسم',
                phone: 'الهاتف',
                address: 'العنوان',
                km: 'كم',
                amount: 'المبلغ',
                notes: 'ملاحظات',
                sar: 'ر.س',
            },
            work_orders: {
                print_view: {
                    customer: 'العميل',
                    vehicle: 'المركبة',
                    plate: 'اللوحة',
                    color: 'اللون',
                    odometer: 'العداد',
                    fuel_level: 'الوقود',
                    received_from: 'استلمنا من',
                    amount_of: 'مبلغ وقدره',
                    for_label: 'وذلك عن',
                },
            },
            vehicles: { form: { make: 'الماركة' } },
            company_profile: { profile: { vat_number: 'الرقم الضريبي' } },
        },
    },
});

const globalConfig = {
    plugins: [i18n],
    stubs: {
        SaudiPlateDisplay: { template: '<span class="plate-stub" />' },
        PrintMoney: {
            props: ['amount', 'currencyCode', 'visualSettings'],
            template: '<span class="print-money">{{ Number(amount).toFixed(2) }}</span>',
        },
    },
};

describe('R2-B1 Critical Print Fixes', () => {
    it('F-01: Portal invoice show wraps invoice in .print-container and hides action bar on print', () => {
        const wrapper = mount(PortalInvoiceShow, {
            props: {
                tenant: { slug: 'demo-shop', site_name: 'ورشة الاختبار' },
                locale: { current: 'ar', available: ['ar', 'en'] },
                invoice: {
                    id: 10,
                    invoice_number: 'INV-2026-0010',
                    type: 'invoice',
                    subtype: 'simplified',
                    payment_status: 'paid',
                    issue_date: '2026-11-15',
                    currency_code: 'SAR',
                    tax_enabled_snapshot: true,
                    total_excl_tax: 100,
                    total_tax: 15,
                    total_incl_tax: 115,
                    total_paid: 115,
                    balance: 0,
                    customer_name: 'سعيد الحربي',
                    lines: [
                        {
                            id: 1,
                            description: 'تغيير زيت',
                            qty: 1,
                            unit_price: 100,
                            discount_amount: 0,
                            tax_amount: 15,
                            line_total_excl_tax: 100,
                            line_total_incl_tax: 115,
                        },
                    ],
                    payments: [],
                },
            },
            global: {
                plugins: [i18n],
                stubs: {
                    CustomerPortalLayout: {
                        template: '<div class="portal-layout-stub"><slot /></div>',
                    },
                    AppPrintButton: {
                        template: '<button class="print-btn-stub" @click="$emit(\'click\')" />',
                    },
                },
            },
        });

        const article = wrapper.find('article');
        expect(article.exists()).toBe(true);
        expect(article.classes()).toContain('print-container');
        expect(wrapper.html()).toContain('INV-2026-0010');
        expect(wrapper.html()).toContain('تغيير زيت');
        expect(wrapper.find('.print\\:hidden').exists()).toBe(true);
    });

    it('F-03: TemplateDefaultA4 never leaks dummy data when previewMode is false', () => {
        const wrapper = mount(TemplateDefaultA4, {
            props: {
                documentType: 'invoice',
                previewMode: false,
                data: {
                    code: '',
                    currency_code: 'SAR',
                    tax_enabled_snapshot: false,
                    customer: {},
                    vehicle: {},
                    items: [],
                },
                centerData: { name: 'مركز النخبة' },
                documentSettings: {},
                visualSettings: { show_logo: false },
            },
            global: globalConfig,
        });

        const html = wrapper.html();
        expect(html).not.toContain('T202620001');
        expect(html).not.toContain('احمد الزهراني');
        expect(html).not.toContain('Ahmad Alzahrani');
        expect(html).not.toContain('+966 555 785 658');
        expect(html).not.toContain('مرسيدس');
        expect(html).not.toContain('Mercedes');
        expect(html).not.toContain('تغيير اقمشة');
    });

    it('F-03: TemplateThermal80 never leaks dummy data when previewMode is false', () => {
        const wrapper = mount(TemplateThermal80, {
            props: {
                documentType: 'invoice',
                previewMode: false,
                data: {
                    code: '',
                    currency_code: 'SAR',
                    tax_enabled_snapshot: false,
                    customer: {},
                    vehicle: {},
                    items: [],
                },
                centerData: { name: 'مركز النخبة' },
                documentSettings: {},
                visualSettings: { show_logo: false },
            },
            global: globalConfig,
        });

        const html = wrapper.html();
        expect(html).not.toContain('WO-100293');
        expect(html).not.toContain('أحمد عبد الله');
        expect(html).not.toContain('Ahmed Abdullah');
        expect(html).not.toContain('تويوتا');
        expect(html).not.toContain('Toyota');
        expect(html).not.toContain('45,210');
        expect(html).not.toContain('فحص كمبيوتر وبرمجة كاملة');
    });

    it('F-03: TemplateDefaultA4 and TemplateThermal80 still show preview data when previewMode is true', () => {
        const a4Preview = mount(TemplateDefaultA4, {
            props: {
                documentType: 'invoice',
                previewMode: true,
                data: { currency_code: 'SAR', tax_enabled_snapshot: true },
                centerData: { name: 'معاينة' },
                documentSettings: {},
                visualSettings: {},
            },
            global: globalConfig,
        });
        expect(a4Preview.html()).toContain('تغيير اقمشة');

        const thermalPreview = mount(TemplateThermal80, {
            props: {
                documentType: 'invoice',
                previewMode: true,
                data: { currency_code: 'SAR', tax_enabled_snapshot: true },
                centerData: { name: 'معاينة' },
                documentSettings: {},
                visualSettings: {},
            },
            global: globalConfig,
        });
        expect(thermalPreview.html()).toContain('فحص كمبيوتر وبرمجة كاملة');
    });

    it('F-06: ReturnPrint uses real center, real tax flag, and real refunded_amount/remaining_refund', () => {
        const returnGlobal = {
            mocks: {
                route: (name: string, id?: number) => `/${name}/${id ?? ''}`,
            },
        };
        const partialWrapper = shallowMount(ReturnPrint, {
            global: returnGlobal,
            props: {
                returnInvoice: {
                    id: 5,
                    code: 'PRET-0005',
                    return_date: '2026-11-15',
                    currency_code: 'SAR',
                    subtotal: 200,
                    tax_amount: 30,
                    total: 230,
                    refunded_amount: 100,
                    remaining_refund: 130,
                    center: {
                        name_ar: 'فرع الرياض',
                        phone: '0501112233',
                        vat_number: '310000000000003',
                    },
                    tenant: {
                        name: 'شركة الصيانة',
                        cr_number: '1010999888',
                        tax_settings: { vat_enabled: true },
                    },
                    purchase_invoice: {
                        supplier: { name: 'مورد القطع', phone: '0550001122', tax_number: '3999' },
                    },
                    lines: [
                        {
                            qty: 2,
                            unit_cost: 100,
                            tax_rate: 15,
                            tax_amount: 30,
                            total: 230,
                            part: { name: 'فلتر هواء', sku: 'FLT-1' },
                        },
                    ],
                },
            },
        });

        const layout = partialWrapper.getComponent(PrintLayout);
        expect(layout.props('data')).toMatchObject({
            code: 'PRET-0005',
            tax_enabled_snapshot: true,
            total_excl_tax: 200,
            total_tax: 30,
            total_incl_tax: 230,
            total_paid: 100,
            balance: 130,
        });
        expect(layout.props('centerData')).toMatchObject({
            name: 'فرع الرياض',
            tax_number: '310000000000003',
            phone: '0501112233',
        });

        const unpaidNoTaxWrapper = shallowMount(ReturnPrint, {
            global: returnGlobal,
            props: {
                returnInvoice: {
                    id: 6,
                    code: 'PRET-0006',
                    return_date: '2026-11-15',
                    currency_code: 'SAR',
                    subtotal: 200,
                    tax_amount: 0,
                    total: 200,
                    refunded_amount: 0,
                    remaining_refund: 200,
                    tenant: {
                        name: 'ورشة بدون ضريبة',
                        tax_settings: { vat_enabled: false },
                    },
                    purchase_invoice: {
                        supplier: { name: 'مورد محلي' },
                    },
                    lines: [
                        {
                            qty: 1,
                            unit_cost: 200,
                            tax_rate: 0,
                            tax_amount: 0,
                            total: 200,
                            part: { name: 'بطارية', sku: 'BAT-1' },
                        },
                    ],
                },
            },
        });

        const noTaxLayout = unpaidNoTaxWrapper.getComponent(PrintLayout);
        expect(noTaxLayout.props('data')).toMatchObject({
            tax_enabled_snapshot: false,
            total_paid: 0,
            balance: 200,
        });
    });

    it('F-07: TemplateThermal80 passes props.data to lineSubtotalDisplay for inclusive pricing parity with TemplateDefaultA4', () => {
        const inclusiveData = {
            code: 'INV-INC-01',
            currency_code: 'SAR',
            tax_enabled_snapshot: true,
            pricing_mode_snapshot: 'inclusive',
            tax_rate_snapshot: 15,
            total_excl_tax: 100,
            total_tax: 15,
            total_incl_tax: 115,
            items: [
                {
                    service_name: 'خدمة صيانة شاملة',
                    qty: 1,
                    unit_price: 115,
                    discount: 0,
                    is_part: false,
                    is_taxable: true,
                    tax_rate_snapshot: 15,
                },
            ],
        };

        const thermalWrapper = mount(TemplateThermal80, {
            props: {
                documentType: 'invoice',
                previewMode: false,
                data: inclusiveData,
                centerData: { name: 'مركز الشامل' },
                documentSettings: {},
                visualSettings: { show_logo: false },
            },
            global: globalConfig,
        });

        const a4Wrapper = mount(TemplateDefaultA4, {
            props: {
                documentType: 'invoice',
                previewMode: false,
                data: inclusiveData,
                centerData: { name: 'مركز الشامل' },
                documentSettings: {},
                visualSettings: { show_logo: false },
            },
            global: globalConfig,
        });

        // Both templates should show 100.00 (tax-exclusive subtotal) for the 115.00 inclusive item when show_amount_before_vat is true
        const thermalFirstMoney = thermalWrapper.findAll('.print-money').map((w) => w.text());
        const a4Money = a4Wrapper.findAll('.print-money').map((w) => w.text());
        expect(thermalFirstMoney).toContain('100.00');
        expect(a4Money).toContain('100.00');
    });

    it('Direct sales parts_invoice renders items from data.parts or data.items without duplication on A4 and Thermal80', () => {
        const lineA = {
            id: 101,
            service_name: 'فلتر هواء أصلي',
            qty: 2,
            unit_price: 50,
            discount: 0,
            is_part: true,
            is_taxable: true,
            tax_rate_snapshot: 15,
            line_total_excl_tax: 100,
            tax_amount: 15,
            line_total_incl_tax: 115,
        };
        const lineB = {
            id: 102,
            service_name: 'شمعات احتراق',
            qty: 4,
            unit_price: 25,
            discount: 0,
            is_part: true,
            is_taxable: true,
            tax_rate_snapshot: 15,
            line_total_excl_tax: 100,
            tax_amount: 15,
            line_total_incl_tax: 115,
        };

        const partsOnlyData = {
            code: 'INV-PARTS-01',
            currency_code: 'SAR',
            tax_enabled_snapshot: true,
            pricing_mode_snapshot: 'exclusive',
            total_excl_tax: 200,
            total_tax: 30,
            total_incl_tax: 230,
            parts: [lineA, lineB],
        };

        const a4FromParts = mount(TemplateDefaultA4, {
            props: {
                documentType: 'parts_invoice',
                previewMode: false,
                data: partsOnlyData,
                centerData: { name: 'مركز القطع' },
                documentSettings: {},
                visualSettings: { show_logo: false },
            },
            global: globalConfig,
        });
        expect(a4FromParts.html()).toContain('فلتر هواء أصلي');
        expect(a4FromParts.html()).toContain('شمعات احتراق');

        const thermalFromParts = mount(TemplateThermal80, {
            props: {
                documentType: 'parts_invoice',
                previewMode: false,
                data: partsOnlyData,
                centerData: { name: 'مركز القطع' },
                documentSettings: {},
                visualSettings: { show_logo: false },
            },
            global: globalConfig,
        });
        expect(thermalFromParts.html()).toContain('فلتر هواء أصلي');
        expect(thermalFromParts.html()).toContain('شمعات احتراق');

        // Both items and parts provided with overlapping lineA -> no duplication
        const bothData = {
            ...partsOnlyData,
            items: [lineA, lineB],
            parts: [lineA],
        };
        const a4NoDup = mount(TemplateDefaultA4, {
            props: {
                documentType: 'parts_invoice',
                previewMode: false,
                data: bothData,
                centerData: { name: 'مركز القطع' },
                documentSettings: {},
                visualSettings: { show_logo: false },
            },
            global: globalConfig,
        });
        const occurrences = a4NoDup.html().match(/فلتر هواء أصلي/g) || [];
        expect(occurrences.length).toBe(1);
    });
});
