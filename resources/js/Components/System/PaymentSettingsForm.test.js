import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { reactive } from 'vue';
import { expect, it, vi } from 'vitest';
import { useForm } from '@inertiajs/vue3';
import PaymentSettingsForm from './PaymentSettingsForm.vue';
import en from '@/i18n/lang/en.json';

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal()),
    useForm: vi.fn(),
}));

it('submits saved bank settings, including a false requirement, without copying masked secrets', async () => {
    const put = vi.fn();
    useForm.mockImplementation((data) => reactive({ ...data, put, errors: {} }));
    const wrapper = mount(PaymentSettingsForm, {
        props: {
            settings: {
                bank_transfer_enabled: true,
                bank_transfer_require_receipt: false,
                bank_transfer_max_days: 18,
                bank_transfer_min_amount: 25,
                bank_transfer_receipt_instructions: 'Reference required',
                moyasar: { secret_key_masked: '******' },
            },
        },
        global: { plugins: [createI18n({ legacy: false, locale: 'en', messages: { en } })] },
    });
    const data = useForm.mock.calls[0][0];
    expect(data).toMatchObject({
        bank_transfer_require_receipt: false,
        bank_transfer_max_days: 18,
        bank_transfer_min_amount: 25,
        bank_transfer_receipt_instructions: 'Reference required',
        moyasar: { secret_key: '' },
    });
    await wrapper.get('form').trigger('submit');
    expect(put).toHaveBeenCalledWith(
        '/system/settings/payment',
        expect.objectContaining({ preserveScroll: true })
    );
});
