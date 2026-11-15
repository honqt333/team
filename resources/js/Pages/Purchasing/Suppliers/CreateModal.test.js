import { reactive, ref } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import CreateModal from './CreateModal.vue';
import en from '@/i18n/lang/en.json';

const mockMountWithRetry = vi.fn();
const mockDestroyMap = vi.fn();
const mockFetchLocation = vi.fn();
let mapAvailable = true;

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial) => {
        const state = reactive({
            ...initial,
            errors: {},
            processing: false,
            reset: () => Object.assign(state, initial),
            clearErrors: vi.fn(),
            data: () => ({ ...initial, phone: state.phone, whatsapp: state.whatsapp, tax_number: state.tax_number }),
            put: vi.fn(),
            post: vi.fn(),
        });
        return state;
    },
    usePage: () => ({ props: { tenant: { country: 'SA', currency: 'SAR' }, locale: 'en' } }),
}));

vi.mock('@/Composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: vi.fn().mockResolvedValue(true) }),
}));

vi.mock('@/Composables/useFormScrollToError', () => ({
    useFormScrollToError: () => ({ scrollToError: vi.fn() }),
}));

vi.mock('@/Composables/useNationalAddressMap', () => ({
    useNationalAddressMap: () => ({
        mapContainer: ref(null),
        mapReady: ref(mapAvailable),
        isLocating: ref(false),
        mountWithRetry: (...args) => {
            if (mapAvailable) {
                mockMountWithRetry(...args);
            }
        },
        fetchLocation: mockFetchLocation,
        clearAddressFields: vi.fn(),
        destroyMap: mockDestroyMap,
    }),
}));

describe('Purchasing/Suppliers/CreateModal', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        mapAvailable = true;
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    const mountModal = (props = {}) =>
        mount(CreateModal, {
            props: { show: true, ...props },
            global: {
                plugins: [createI18n({ legacy: false, locale: 'en', messages: { en } })],
                mocks: { $t: (k) => k },
                stubs: {
                    BaseModal: { template: '<div><slot/><slot name="footer"/></div>' },
                    AppButton: { template: '<button><slot/></button>' },
                    AppInput: true,
                    AppTextarea: true,
                    AppToggle: true,
                    SearchableSelect: true,
                    NationalAddressMapCanvas: true,
                },
            },
        });

    it('converts Arabic and English numerals in phone, whatsapp, and tax_number without ReferenceError', async () => {
        const wrapper = mountModal();

        wrapper.vm.onPhoneInput('phone', '٠٥٠ ١٢٣ ٤٥٦٧');
        wrapper.vm.onPhoneInput('whatsapp', '055 987 6543');
        wrapper.vm.form.tax_number = '٣٠٠١٢٣٤٥٦٧٨٩٠١٢';
        await flushPromises();

        expect(wrapper.vm.form.phone).toBe('0501234567');
        expect(wrapper.vm.form.whatsapp).toBe('0559876543');
        expect(wrapper.vm.form.tax_number).toBe('300123456789012');
        wrapper.unmount();
    });

    it('initializes map on open and destroys map on close safely', async () => {
        const wrapper = mountModal({ show: false });

        await wrapper.setProps({ show: true });
        await flushPromises();
        vi.advanceTimersByTime(300);

        expect(mockMountWithRetry).toHaveBeenCalled();

        await wrapper.setProps({ show: false });
        await flushPromises();
        expect(mockDestroyMap).toHaveBeenCalled();

        await wrapper.vm.handleClose();
        expect(wrapper.emitted('close')).toHaveLength(1);
        wrapper.unmount();
    });

    it('handles unavailable map dependencies gracefully without ReferenceError', async () => {
        mapAvailable = false;
        const wrapper = mountModal({ show: true });

        await flushPromises();
        vi.advanceTimersByTime(300);

        expect(mockMountWithRetry).not.toHaveBeenCalled();
        wrapper.unmount();
    });
});
