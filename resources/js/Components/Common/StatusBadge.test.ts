import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import Badge from './StatusBadge.vue';
import BillingBadge from '../Billing/StatusBadge.vue';
import PortalBadge from '../CustomerPortal/StatusBadge.vue';
import DashboardBadge from '../DashboardV2/common/StatusBadge.vue';
import { nextTick } from 'vue';

describe('shared badges', () => {
    it('renders a label and optional decorative dot', async () => {
        const wrapper = mount(Badge, { props: { label: 'Ready' } });
        expect(wrapper.text()).toBe('Ready');
        expect(wrapper.find('[aria-hidden="true"]').exists()).toBe(true);
        await wrapper.setProps({ showDot: false });
        expect(wrapper.find('[aria-hidden="true"]').exists()).toBe(false);
        wrapper.unmount();
    });
    it('updates billing translations when the locale changes', async () => {
        const i18n = createI18n({
            legacy: false,
            locale: 'en',
            messages: {
                en: { hr: { payroll: { paid: 'Paid' } } },
                ar: { hr: { payroll: { paid: 'مدفوعة' } } },
            },
        });
        const wrapper = mount(BillingBadge, {
            props: { status: 'paid' },
            global: { plugins: [i18n] },
        });
        expect(wrapper.text()).toBe('Paid');
        i18n.global.locale.value = 'ar';
        await nextTick();
        expect(wrapper.text()).toBe('مدفوعة');
        wrapper.unmount();
    });
    it('preserves portal-specific labels, colors and optional dots', () => {
        const wrapper = mount(PortalBadge, { props: { status: 'on_hold', label: 'Waiting' } });
        expect(wrapper.text()).toBe('Waiting');
        expect(wrapper.attributes('style')).toContain('rgb(254, 226, 226)');
        expect(wrapper.find('[aria-hidden="true"]').exists()).toBe(false);
        wrapper.unmount();
    });
    it('retains the dashboard fallback for unknown statuses', () => {
        const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: {} } });
        const wrapper = mount(DashboardBadge, {
            props: { status: 'new_status' },
            global: { plugins: [i18n] },
        });
        expect(wrapper.text()).toBe('new_status');
        expect(wrapper.find('[aria-hidden="true"]').exists()).toBe(true);
        wrapper.unmount();
    });
});
