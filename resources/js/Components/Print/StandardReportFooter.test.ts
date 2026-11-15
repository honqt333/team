import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import StandardReportFooter from './StandardReportFooter.vue';

const mockProps: Record<string, unknown> = {
    app_name: 'Khidmh Pro',
    tenant: null,
    auth: { user: { name: 'Tester' } },
};

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: mockProps }),
}));

vi.mock('vue-i18n', () => ({
    useI18n: () => ({ t: (key: string) => key }),
}));

describe('StandardReportFooter', () => {
    it('falls back to page.props.app_name when tenant name is absent', () => {
        mockProps.tenant = null;
        mockProps.app_name = 'Khidmh Pro';

        const wrapper = mount(StandardReportFooter, {
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        expect(wrapper.text()).toContain('Khidmh Pro');
    });

    it('prefers tenant.name over page.props.app_name when present', () => {
        mockProps.tenant = { name: 'Al-Noor Workshop' };
        mockProps.app_name = 'Khidmh Pro';

        const wrapper = mount(StandardReportFooter, {
            global: {
                mocks: {
                    $t: (key: string) => key,
                },
            },
        });

        expect(wrapper.text()).toContain('Al-Noor Workshop');
    });
});
