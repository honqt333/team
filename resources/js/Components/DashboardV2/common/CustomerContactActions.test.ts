import { describe, it, expect } from 'vitest';
import { createI18n } from 'vue-i18n';
import { mount } from '@vue/test-utils';
import CustomerContactActions from './CustomerContactActions.vue';

const global = {
    plugins: [
        createI18n({
            legacy: false,
            locale: 'en',
            messages: {
                en: {
                    common: { call: 'Call' },
                    customers: { quick: { whatsapp: 'WhatsApp' } },
                },
            },
        }),
    ],
};

describe('CustomerContactActions', () => {
    it('renders tel and WhatsApp links with encoded work order context', () => {
        const wrapper = mount(CustomerContactActions, {
            global,
            props: {
                phone: '+966 55 123 4567',
                whatsapp: '966551234567',
                workOrderCode: 'WO-101',
                plateNumber: 'ABC 1234',
                compact: false,
            },
        });

        const links = wrapper.findAll('a');
        expect(links).toHaveLength(2);
        expect(links[0].attributes('href')).toBe('tel:+966551234567');
        expect(links[1].attributes('href')).toBe(
            'https://wa.me/966551234567?text=WO-101%20-%20ABC%201234'
        );
    });

    it('renders nothing when neither phone nor whatsapp is provided', () => {
        const wrapper = mount(CustomerContactActions, {
            global,
            props: { phone: '', whatsapp: '' },
        });
        expect(wrapper.findAll('a')).toHaveLength(0);
    });
});
