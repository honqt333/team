import { shallowMount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Print from './Print.vue';
import PrintLayout from '@/Components/Print/PrintLayout.vue';

vi.mock('@inertiajs/vue3', () => ({ usePage: () => ({ props: {} }), router: {} }));
vi.mock('vue-i18n', () => ({ useI18n: () => ({ locale: { value: 'en' } }) }));

describe('invoice print issuer', () => {
    it.each([null, ''])(
        'preserves missing registration fields (%s) after a tenant update',
        (missing) => {
            const wrapper = shallowMount(Print, {
                props: {
                    invoice: {
                        seller_name_en_snapshot: 'Original issuer',
                        seller_vat_snapshot: missing,
                        seller_cr_snapshot: missing,
                        tenant: { vat_number: 'later VAT', cr_number: 'later CR' },
                    },
                },
            });
            expect(wrapper.getComponent(PrintLayout).props('centerData')).toMatchObject({
                name: 'Original issuer',
                tax_number: missing,
                cr_number: missing,
            });
        }
    );
});
