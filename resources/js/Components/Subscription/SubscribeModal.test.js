import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { afterEach, describe, expect, it, vi } from 'vitest';
import SubscribeModal from './SubscribeModal.vue';
import PaymentMethodSelector from './PaymentMethodSelector.vue';
import en from '@/i18n/lang/en.json';

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal()),
    usePage: () => ({ props: { value: { csrf_token: 'test-csrf' } } }),
}));

const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } });

const baseProps = {
    open: false,
    plan: {
        id: 2,
        name_ar: 'الباقة الأساسية',
        name_en: 'Basic Plan',
        price_yearly: 1990,
        currency: 'SAR',
    },
    enabledGateways: ['bank_transfer'],
    paymentSettings: {
        bank_transfer_enabled: true,
        bank_transfer_min_amount: 100,
    },
    vatSettings: { enabled: true, rate: 15, inclusive: false },
};

describe('SubscribeModal pricing null safety', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders safely when pricing is null during initial load and blocks submit until pricing resolves', async () => {
        let resolveCalculate;
        const fetchMock = vi.fn(
            () =>
                new Promise((resolve) => {
                    resolveCalculate = resolve;
                })
        );
        vi.stubGlobal('fetch', fetchMock);

        const wrapper = mount(SubscribeModal, {
            props: baseProps,
            global: {
                plugins: [i18n],
                stubs: {
                    BaseModal: { template: '<div><slot /><slot name="footer" /></div>' },
                    Teleport: true,
                },
            },
        });

        await wrapper.setProps({ open: true });

        // Before /calculate resolves, pricing is null: no crash, no PaymentMethodSelector, submit disabled
        expect(wrapper.findComponent(PaymentMethodSelector).exists()).toBe(false);
        const submitBtn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Proceed to payment') || b.attributes('disabled') !== undefined);
        expect(submitBtn?.attributes('disabled')).toBeDefined();

        resolveCalculate({
            ok: true,
            json: async () => ({
                plan: baseProps.plan,
                base_price: 1990,
                discount: 0,
                subtotal: 1990,
                vat_enabled: true,
                vat_rate: 15,
                vat_amount: 298.5,
                total: 2288.5,
                currency: 'SAR',
            }),
        });

        await flushPromises();

        expect(wrapper.findComponent(PaymentMethodSelector).exists()).toBe(true);
        expect(wrapper.findComponent(PaymentMethodSelector).props('totalAmount')).toBe(2288.5);
    });

    it('disables bank_transfer in PaymentMethodSelector when totalAmount is null', () => {
        const wrapper = mount(PaymentMethodSelector, {
            props: {
                modelValue: 'bank_transfer',
                enabledGateways: ['bank_transfer'],
                paymentMethods: { bank_transfer_enabled: true, bank_transfer_min_amount: 100 },
                bankTransferEnabled: true,
                totalAmount: null,
            },
            global: { plugins: [i18n] },
        });

        const bankButton = wrapper.find('button');
        expect(bankButton.attributes('disabled')).toBeDefined();
    });
});
