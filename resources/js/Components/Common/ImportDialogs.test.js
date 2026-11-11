import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { afterEach, expect, it, vi } from 'vitest';
import axios from 'axios';
import CustomerImportModal from '@/Components/Customers/CustomerImportModal.vue';
import SupplierImportModal from '@/Components/Purchasing/SupplierImportModal.vue';
import ImportFileField from './ImportFileField.vue';
import ImportResult from './ImportResult.vue';
import en from '@/i18n/lang/en.json';

vi.mock('axios', () => ({ default: { post: vi.fn() } }));
afterEach(() => {
    vi.unstubAllGlobals();
    document.querySelector('meta[name="csrf-token"]')?.remove();
});

it.each([
    ['customer', CustomerImportModal],
    ['supplier', SupplierImportModal],
])('shows partial %s results and refreshes the parent once', async (kind, component) => {
    const result = { imported: 1, errors: ['Duplicate on row 3'] };
    const file = new File(['rows'], 'rows.csv');
    vi.stubGlobal('route', (name) => name);
    const csrf = document.createElement('meta');
    csrf.name = 'csrf-token';
    csrf.content = 'test-token';
    document.head.appendChild(csrf);
    const post = vi.fn().mockResolvedValue({ ok: true, json: async () => result });
    vi.stubGlobal('fetch', post);
    axios.post.mockResolvedValue({ data: result });
    const wrapper = mount(component, {
        props: { show: true },
        global: {
            plugins: [createI18n({ legacy: false, locale: 'en', messages: { en } })],
            mocks: { route: (name) => name },
            stubs: {
                BaseModal: { template: '<div><slot/><footer><slot name="footer"/></footer></div>' },
            },
        },
    });
    wrapper.getComponent(ImportFileField).vm.$emit('update:modelValue', file);
    await wrapper.vm.$nextTick();
    await wrapper.get('footer button:last-child').trigger('click');
    await flushPromises();
    const payload = kind === 'customer' ? post.mock.calls[0][1].body : axios.post.mock.calls[0][1];
    expect(payload.get('file').name).toBe(file.name);
    expect(wrapper.getComponent(ImportResult).props('result')).toEqual(result);
    expect(wrapper.emitted('imported')).toHaveLength(1);
    expect(wrapper.emitted('close')).toBeUndefined();
    wrapper.unmount();
});
