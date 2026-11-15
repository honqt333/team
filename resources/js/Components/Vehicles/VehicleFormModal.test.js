import { reactive } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { afterEach, describe, expect, it, vi } from 'vitest';
import VehicleFormModal from './VehicleFormModal.vue';
import en from '@/i18n/lang/en.json';

const mockReset = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial) => {
        const state = reactive({
            ...initial,
            errors: {},
            processing: false,
            reset: () => {
                mockReset();
                Object.assign(state, initial);
            },
            clearErrors: vi.fn(),
            setError: vi.fn(),
            data: () => ({ ...initial, customer_id: state.customer_id, plate_number: state.plate_number }),
            transform: () => ({ post: vi.fn(), put: vi.fn() }),
        });
        return state;
    },
    usePage: () => ({ props: { tenant: { country: 'SA' } } }),
}));

vi.mock('@/Composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: vi.fn().mockResolvedValue(true) }),
}));

vi.mock('@/Composables/useFormScrollToError', () => ({
    useFormScrollToError: () => ({ scrollToError: vi.fn() }),
}));

describe('VehicleFormModal', () => {
    afterEach(() => {
        vi.clearAllMocks();
    });

    const mountModal = (props = {}) =>
        mount(VehicleFormModal, {
            props: {
                show: true,
                customers: [
                    { id: 10, name: 'Customer Ten', phone: '0500000010' },
                    { id: 42, name: 'Customer Reloaded', phone: '0500000042' },
                ],
                makes: [],
                colors: [],
                dealers: [],
                ...props,
            },
            global: {
                plugins: [createI18n({ legacy: false, locale: 'en', messages: { en } })],
                mocks: { $t: (k) => k },
                stubs: {
                    BaseModal: { template: '<div><slot/><slot name="footer"/></div>' },
                    AppButton: { template: '<button><slot/></button>' },
                    AppInput: true,
                    AppSelect: true,
                    SearchableSelect: true,
                    SaudiPlateInput: true,
                    VehicleOdometerField: true,
                    ColorField: true,
                    CustomerFormModal: true,
                },
            },
        });

    it('handles customer-reloaded without ReferenceError and links customer_id', async () => {
        const wrapper = mountModal();

        window.dispatchEvent(new CustomEvent('customer-reloaded', { detail: { customerId: 42 } }));
        await flushPromises();

        expect(wrapper.vm.form.customer_id).toBe(42);
        wrapper.unmount();
    });

    it('opens, closes, and resets form state safely', async () => {
        const wrapper = mountModal();
        wrapper.vm.form.customer_id = 10;

        await wrapper.vm.handleClose();
        await flushPromises();

        expect(mockReset).toHaveBeenCalled();
        expect(wrapper.emitted('close')).toHaveLength(1);
        expect(wrapper.vm.form.customer_id).toBe('');
        wrapper.unmount();
    });
});
