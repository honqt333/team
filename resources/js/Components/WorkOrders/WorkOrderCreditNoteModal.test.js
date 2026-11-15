import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import axios from 'axios';
import WorkOrderCreditNoteModal from './WorkOrderCreditNoteModal.vue';
import en from '@/i18n/lang/en.json';

vi.mock('axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
    },
}));

globalThis.route = vi.fn(() => '/mock-route');

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en },
});

function mountModal(props = { show: true, workOrder: { id: 1, currency: 'SAR' } }) {
    return mount(WorkOrderCreditNoteModal, {
        props,
        global: {
            plugins: [i18n],
            stubs: {
                BaseModal: { template: '<div><slot /><slot name="footer" /></div>' },
                AppButton: { template: '<button><slot /></button>' },
                AppInput: {
                    props: ['modelValue'],
                    emits: ['update:modelValue'],
                    template:
                        '<input :value="modelValue" @input="$emit(\'update:modelValue\', Number($event.target.value))" />',
                },
                AppTextarea: { template: '<textarea />' },
                AppSelect: { template: '<select />' },
                AppCheckbox: { template: '<input type="checkbox" />' },
                ReceiptRefundIcon: true,
                ExclamationTriangleIcon: true,
                InformationCircleIcon: true,
                CheckCircleIcon: true,
            },
        },
    });
}

describe('WorkOrderCreditNoteModal preview calculations', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('matches server calculation for inclusive spare parts (partial and full)', async () => {
        axios.get.mockResolvedValueOnce({
            data: {
                is_possible: true,
                invoice: {
                    id: 1,
                    invoice_number: 'INV-1',
                    total_incl_tax: 230,
                    remaining_amount: 230,
                    tax_enabled_snapshot: true,
                    tax_rate: 15,
                },
                tax_settings: {
                    tax_enabled: true,
                    pricing_mode: 'inclusive',
                    tax_rate: 15,
                },
                services: [],
                parts: [
                    {
                        id: 10,
                        work_order_item_part_id: 10,
                        name: 'Oil Filter',
                        unit_price: 115,
                        original_qty: 2,
                        refundable_qty: 2,
                        remaining_refundable_qty: 2,
                        tax_rate: 15,
                        total: 230,
                    },
                ],
            },
        });

        const wrapper = mountModal();
        await flushPromises();

        // Partial return: 1 of 2 @ 115 incl -> subtotal 100.00, vat 15.00, total 115.00
        wrapper.vm.returnParts[10] = 1;
        await flushPromises();

        expect(wrapper.vm.totalRefundSubtotal).toBe(100);
        expect(wrapper.vm.totalRefundVat).toBe(15);
        expect(wrapper.vm.totalRefundAmount).toBe(115);

        // Full return: 2 of 2 @ 115 incl -> subtotal 200.00, vat 30.00, total 230.00
        wrapper.vm.returnParts[10] = 2;
        await flushPromises();

        expect(wrapper.vm.totalRefundSubtotal).toBe(200);
        expect(wrapper.vm.totalRefundVat).toBe(30);
        expect(wrapper.vm.totalRefundAmount).toBe(230);
    });

    it('matches server calculation for exclusive spare parts, no VAT, and mixed service + inclusive part', async () => {
        // 1. Exclusive
        axios.get.mockResolvedValueOnce({
            data: {
                is_possible: true,
                invoice: { id: 2, remaining_amount: 230, tax_enabled_snapshot: true, tax_rate: 15 },
                tax_settings: { tax_enabled: true, pricing_mode: 'exclusive', tax_rate: 15 },
                services: [],
                parts: [
                    { id: 20, name: 'Pad', unit_price: 100, original_qty: 2, refundable_qty: 2, tax_rate: 15, total: 200 },
                ],
            },
        });
        const w1 = mountModal();
        await flushPromises();
        w1.vm.returnParts[20] = 1;
        await flushPromises();
        expect(w1.vm.totalRefundSubtotal).toBe(100);
        expect(w1.vm.totalRefundVat).toBe(15);
        expect(w1.vm.totalRefundAmount).toBe(115);

        // 2. No VAT
        axios.get.mockResolvedValueOnce({
            data: {
                is_possible: true,
                invoice: { id: 3, remaining_amount: 200, tax_enabled_snapshot: false, tax_rate: 0 },
                tax_settings: { tax_enabled: false, pricing_mode: 'exclusive', tax_rate: 0 },
                services: [],
                parts: [
                    { id: 30, name: 'Pad', unit_price: 100, original_qty: 2, refundable_qty: 2, tax_rate: 0, total: 200 },
                ],
            },
        });
        const w2 = mountModal();
        await flushPromises();
        w2.vm.returnParts[30] = 2;
        await flushPromises();
        expect(w2.vm.totalRefundSubtotal).toBe(200);
        expect(w2.vm.totalRefundVat).toBe(0);
        expect(w2.vm.totalRefundAmount).toBe(200);

        // 3. Mixed service (100 excl) + inclusive part (1 of 2 @ 115 incl)
        axios.get.mockResolvedValueOnce({
            data: {
                is_possible: true,
                invoice: { id: 4, remaining_amount: 345, tax_enabled_snapshot: true, tax_rate: 15 },
                tax_settings: { tax_enabled: true, pricing_mode: 'inclusive', tax_rate: 15 },
                services: [
                    { id: 40, name: 'Labor', remaining_refundable_subtotal: 100, tax_rate: 15 },
                ],
                parts: [
                    { id: 41, name: 'Filter', unit_price: 115, original_qty: 2, refundable_qty: 2, tax_rate: 15, total: 230 },
                ],
            },
        });
        const w3 = mountModal();
        await flushPromises();
        w3.vm.returnServices[40] = 100;
        w3.vm.returnParts[41] = 1;
        await flushPromises();
        expect(w3.vm.totalRefundSubtotal).toBe(200);
        expect(w3.vm.totalRefundVat).toBe(30);
        expect(w3.vm.totalRefundAmount).toBe(230);
    });
});
